<?php
declare(strict_types=1);

/**
 * RateLimiter
 * --------------------------------------------------------------------
 * Counts attempts per (action, identifier) inside a sliding window.
 *
 * The identifier is hashed before it is stored, so the table never holds
 * an IP address or an email address in clear text.
 *
 * Built to be shared: the newsletter form uses it today, and login can
 * reuse it with action 'login_ip' / 'login_email' and the same window.
 *
 *   $limiter = new RateLimiter($pdo);
 *   if ($limiter->tooManyAttempts('login_email', $email, 5, 900)) { ... }
 *   $limiter->recordAttempt('login_email', $email);   // on failure
 *   $limiter->clearAttempts('login_email', $email);   // on success
 * --------------------------------------------------------------------
 */
class RateLimiter
{
    /** Rows older than this are deleted opportunistically. */
    private const PURGE_AFTER_SECONDS = 86400;

    public function __construct(private PDO $pdo) {}

    /** True when (action, identifier) already used up $maxAttempts in the window. */
    public function tooManyAttempts(string $action, string $identifier, int $maxAttempts, int $windowSeconds): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM rate_limits
             WHERE action = :action
               AND identifier_hash = :identifier
               AND attempted_at > DATE_SUB(NOW(), INTERVAL :window SECOND)'
        );
        $statement->bindValue(':action', $action);
        $statement->bindValue(':identifier', $this->hash($identifier));
        $statement->bindValue(':window', $windowSeconds, PDO::PARAM_INT);
        $statement->execute();

        return (int) $statement->fetchColumn() >= $maxAttempts;
    }

    public function recordAttempt(string $action, string $identifier): void
    {
        $this->pdo->prepare(
            'INSERT INTO rate_limits (action, identifier_hash, attempted_at) VALUES (?, ?, NOW())'
        )->execute([$action, $this->hash($identifier)]);

        $this->purgeExpired();
    }

    /**
     * Counts one attempt and tells whether it is allowed: false once
     * (action, identifier) already used up $maxAttempts in the window.
     * A refused attempt is not recorded, so waiting is enough to recover.
     */
    public function attempt(string $action, string $identifier, int $maxAttempts, int $windowSeconds): bool
    {
        if ($this->tooManyAttempts($action, $identifier, $maxAttempts, $windowSeconds)) {
            return false;
        }
        $this->recordAttempt($action, $identifier);

        return true;
    }

    public function clearAttempts(string $action, string $identifier): void
    {
        $this->pdo->prepare('DELETE FROM rate_limits WHERE action = ? AND identifier_hash = ?')
            ->execute([$action, $this->hash($identifier)]);
    }

    /** The client address, taken only from REMOTE_ADDR. */
    public static function clientIp(): string
    {
        // Proxy headers such as X-Forwarded-For are attacker-controlled and
        // would let anyone reset their own counter, so they are ignored.
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    private function hash(string $identifier): string
    {
        return hash('sha256', mb_strtolower(trim($identifier)));
    }

    /** Runs rarely — only one attempt in fifty pays for the cleanup. */
    private function purgeExpired(): void
    {
        if (random_int(1, 50) !== 1) {
            return;
        }

        $statement = $this->pdo->prepare(
            'DELETE FROM rate_limits WHERE attempted_at < DATE_SUB(NOW(), INTERVAL :window SECOND)'
        );
        $statement->bindValue(':window', self::PURGE_AFTER_SECONDS, PDO::PARAM_INT);
        $statement->execute();
    }
}
