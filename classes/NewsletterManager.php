<?php
declare(strict_types=1);

/**
 * NewsletterManager
 * --------------------------------------------------------------------
 * Double opt-in newsletter list. All SQL for `newsletter_subscribers`
 * lives here.
 *
 * Unlike the business managers, these rows belong to no account: the
 * newsletter box is public, so there is no user_id to scope by. A row is
 * reachable only by email (server-side) or by a raw token that exists
 * solely in an emailed link.
 *
 * Only token hashes are stored. A raw token is returned once, by the call
 * that issues it, and cannot be recovered afterwards — so a future
 * mailing rotates the unsubscribe token (rotateUnsubscribeToken) to get a
 * fresh link for that send.
 * --------------------------------------------------------------------
 */
class NewsletterManager
{
    /** A confirmation link is valid for 48 hours. */
    public const CONFIRM_TTL_HOURS = 48;

    public function __construct(private PDO $pdo) {}

    /**
     * Records a subscription request.
     *
     * @return array{outcome: 'created'|'resent'|'already_confirmed', email: string, confirm_token: ?string, unsubscribe_token: ?string}
     */
    public function subscribe(string $email, string $ipAddress): array
    {
        $email = $this->normaliseEmail($email);
        $existing = $this->findByEmail($email);

        // Nothing to do for a live subscriber: re-sending a confirmation
        // would let anyone spam a known address through this form.
        if ($existing !== null && $existing['status'] === 'confirmed') {
            return [
                'outcome' => 'already_confirmed',
                'email' => $email,
                'confirm_token' => null,
                'unsubscribe_token' => null,
            ];
        }

        $confirmToken = $this->newToken();
        $unsubscribeToken = $this->newToken();

        if ($existing === null) {
            $this->pdo->prepare(
                'INSERT INTO newsletter_subscribers
                    (email, status, confirm_token_hash, unsubscribe_token_hash, token_expires_at, ip_address)
                 VALUES
                    (:email, \'pending\', :confirm, :unsubscribe, DATE_ADD(NOW(), INTERVAL :ttl HOUR), :ip)'
            )->execute([
                ':email' => $email,
                ':confirm' => $this->hash($confirmToken),
                ':unsubscribe' => $this->hash($unsubscribeToken),
                ':ttl' => self::CONFIRM_TTL_HOURS,
                ':ip' => $ipAddress,
            ]);

            return [
                'outcome' => 'created',
                'email' => $email,
                'confirm_token' => $confirmToken,
                'unsubscribe_token' => $unsubscribeToken,
            ];
        }

        // Pending or previously unsubscribed: issue a fresh link and put the
        // row back into pending.
        $this->pdo->prepare(
            'UPDATE newsletter_subscribers
                SET status = \'pending\',
                    confirm_token_hash = :confirm,
                    unsubscribe_token_hash = :unsubscribe,
                    token_expires_at = DATE_ADD(NOW(), INTERVAL :ttl HOUR),
                    ip_address = :ip,
                    unsubscribed_at = NULL
              WHERE id = :id'
        )->execute([
            ':confirm' => $this->hash($confirmToken),
            ':unsubscribe' => $this->hash($unsubscribeToken),
            ':ttl' => self::CONFIRM_TTL_HOURS,
            ':ip' => $ipAddress,
            ':id' => (int) $existing['id'],
        ]);

        return [
            'outcome' => 'resent',
            'email' => $email,
            'confirm_token' => $confirmToken,
            'unsubscribe_token' => $unsubscribeToken,
        ];
    }

    /**
     * Confirms a pending subscription.
     *
     * Returns the subscriber on the one call that flips the row, and null
     * for an expired, unknown or already-used link — so a double click
     * cannot notify the admin twice.
     *
     * @return array{id: int, email: string}|null
     */
    public function confirmByToken(string $token): ?array
    {
        if (!$this->looksLikeToken($token)) {
            return null;
        }

        $statement = $this->pdo->prepare(
            'SELECT id, email FROM newsletter_subscribers
              WHERE confirm_token_hash = :confirm
                AND status = \'pending\'
                AND token_expires_at > NOW()
              LIMIT 1'
        );
        $statement->execute([':confirm' => $this->hash($token)]);
        $subscriber = $statement->fetch(PDO::FETCH_ASSOC);

        if ($subscriber === false) {
            return null;
        }

        $update = $this->pdo->prepare(
            'UPDATE newsletter_subscribers
                SET status = \'confirmed\',
                    confirmed_at = NOW(),
                    confirm_token_hash = NULL,
                    token_expires_at = NULL
              WHERE id = :id AND status = \'pending\''
        );
        $update->execute([':id' => (int) $subscriber['id']]);

        if ($update->rowCount() !== 1) {
            return null;
        }

        return [
            'id' => (int) $subscriber['id'],
            'email' => (string) $subscriber['email'],
        ];
    }

    /**
     * One-click unsubscribe. Idempotent for the same link: the token is
     * kept so a second click still lands on the "you're unsubscribed" page.
     *
     * @return array{email: string}|null
     */
    public function unsubscribeByToken(string $token): ?array
    {
        if (!$this->looksLikeToken($token)) {
            return null;
        }

        $statement = $this->pdo->prepare(
            'SELECT id, email FROM newsletter_subscribers
              WHERE unsubscribe_token_hash = :unsubscribe LIMIT 1'
        );
        $statement->execute([':unsubscribe' => $this->hash($token)]);
        $subscriber = $statement->fetch(PDO::FETCH_ASSOC);

        if ($subscriber === false) {
            return null;
        }

        $this->pdo->prepare(
            'UPDATE newsletter_subscribers
                SET status = \'unsubscribed\',
                    unsubscribed_at = COALESCE(unsubscribed_at, NOW()),
                    confirm_token_hash = NULL,
                    token_expires_at = NULL
              WHERE id = :id'
        )->execute([':id' => (int) $subscriber['id']]);

        return [
            'email' => (string) $subscriber['email'],
        ];
    }

    /**
     * Issues a fresh unsubscribe token and returns it in the clear, for the
     * one-click link in an outgoing mailing. Call it once per send.
     */
    public function rotateUnsubscribeToken(string $email): ?string
    {
        $token = $this->newToken();
        $statement = $this->pdo->prepare(
            'UPDATE newsletter_subscribers SET unsubscribe_token_hash = :unsubscribe WHERE email = :email'
        );
        $statement->execute([
            ':unsubscribe' => $this->hash($token),
            ':email' => $this->normaliseEmail($email),
        ]);

        return $statement->rowCount() === 1 ? $token : null;
    }

    /** @return array{id: int, email: string, status: string}|null */
    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, email, status FROM newsletter_subscribers WHERE email = :email LIMIT 1'
        );
        $statement->execute([':email' => $this->normaliseEmail($email)]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    private function normaliseEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function newToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    private function looksLikeToken(string $token): bool
    {
        return (bool) preg_match('/\A[a-f0-9]{64}\z/', $token);
    }
}
