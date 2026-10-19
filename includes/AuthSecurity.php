<?php
declare(strict_types=1);

final class AuthSecurity
{
    private const REMEMBER_COOKIE = 'najminet_remember';
    private const DEVICE_COOKIE = 'najminet_device';
    private const REMEMBER_LIFETIME = 2592000;
    private const RESET_LIFETIME = 1800;

    /**
     * Telling a visitor that an email is registered reveals who has an
     * account, so the live check (auth/check-email.php) and the register
     * form share one per-IP budget for those answers.
     */
    public const EMAIL_LOOKUP_ACTION = 'email_lookup';
    public const EMAIL_LOOKUP_MAX_ATTEMPTS = 10;
    public const EMAIL_LOOKUP_WINDOW_SECONDS = 900;

    public static function csrfToken(): string
    {
        if (empty($_SESSION['auth_csrf'])) {
            $_SESSION['auth_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['auth_csrf'];
    }

    public static function validCsrfToken(mixed $token): bool
    {
        return is_string($token)
            && isset($_SESSION['auth_csrf'])
            && hash_equals($_SESSION['auth_csrf'], $token);
    }

    /** Hidden input carrying the session's CSRF token, for any POST form. */
    public static function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::csrfToken()) . '">';
    }

    /** Token sent by fetch() in the X-CSRF-Token header (JSON endpoints). */
    public static function validCsrfHeader(): bool
    {
        return self::validCsrfToken($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
    }

    /**
     * Stops a POST request whose form token is missing or wrong (HTTP 403).
     * Does nothing for other methods.
     */
    public static function requireValidCsrf(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !self::validCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            exit('Your session has expired or the request was not sent by this site. Go back, reload the page and try again.');
        }
    }

    /**
     * Google sign-in is only trusted for an address Google has verified;
     * otherwise anyone could claim an email they do not own and take over
     * the matching account.
     */
    public static function googleEmailVerified(array $profile): bool
    {
        $verified = $profile['email_verified'] ?? null;

        return $verified === true || $verified === 'true';
    }

    /**
     * Token for a named public form (the newsletter box, for example).
     *
     * Kept under its own session key so logging in — which clears the auth
     * token — cannot invalidate a form an anonymous visitor is filling in.
     */
    public static function formCsrfToken(string $form): string
    {
        $key = 'csrf_' . $form;
        if (empty($_SESSION[$key])) {
            $_SESSION[$key] = bin2hex(random_bytes(32));
        }

        return $_SESSION[$key];
    }

    public static function validFormCsrfToken(string $form, mixed $token): bool
    {
        $key = 'csrf_' . $form;

        return is_string($token)
            && isset($_SESSION[$key])
            && hash_equals($_SESSION[$key], $token);
    }

    // Password strength rules live in PasswordPolicy (includes/PasswordPolicy.php).

    /** Emails are compared and stored trimmed and lower-cased. */
    public static function normaliseEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /**
     * How an email is already registered, or null when it is free
     * (case-insensitive, trimmed):
     *   'password'  signs in with a password only
     *   'google'    signs in with Google and never chose a password
     *   'both'      has a password and has also signed in with Google
     */
    public static function existingAccountType(PDO $pdo, string $email): ?string
    {
        $stmt = $pdo->prepare('SELECT oauth_provider, has_password FROM users WHERE LOWER(email) = ? LIMIT 1');
        $stmt->execute([self::normaliseEmail($email)]);
        $user = $stmt->fetch();
        if ($user === false) {
            return null;
        }

        $usesGoogle = ($user['oauth_provider'] ?? null) === 'google';
        $hasPassword = (bool) $user['has_password'];

        return match (true) {
            $usesGoogle && $hasPassword => 'both',
            $usesGoogle => 'google',
            default => 'password',
        };
    }

    public static function establishSession(PDO $pdo, array $user): void
    {
        $version = self::authVersion($pdo, (int) $user['id']);
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = (string) $user['full_name'];
        $_SESSION['user_email'] = (string) $user['email'];
        $_SESSION['auth_version'] = $version;
        unset($_SESSION['auth_csrf']);
    }

    public static function sessionIsValid(PDO $pdo): bool
    {
        if (empty($_SESSION['user_id']) || !isset($_SESSION['auth_version'])) {
            return false;
        }

        return hash_equals(
            (string) self::authVersion($pdo, (int) $_SESSION['user_id']),
            (string) $_SESSION['auth_version']
        );
    }

    public static function createRememberToken(PDO $pdo, int $userId): void
    {
        $deviceId = self::deviceId();
        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $deviceHash = hash('sha256', $deviceId);
        $expiresAt = (new DateTimeImmutable('+30 days'))->format('Y-m-d H:i:s');

        $pdo->prepare('DELETE FROM user_remember_tokens WHERE user_id = ? AND device_hash = ?')
            ->execute([$userId, $deviceHash]);
        $pdo->prepare('DELETE FROM user_remember_tokens WHERE expires_at <= NOW()')->execute();
        $pdo->prepare(
            'INSERT INTO user_remember_tokens (user_id, selector, token_hash, device_hash, expires_at) VALUES (?, ?, ?, ?, ?)'
        )->execute([$userId, $selector, hash('sha256', $validator), $deviceHash, $expiresAt]);

        self::setCookie(self::REMEMBER_COOKIE, $selector . ':' . $validator, time() + self::REMEMBER_LIFETIME);
    }

    public static function restoreRememberedSession(PDO $pdo): void
    {
        if (!empty($_SESSION['user_id']) || empty($_COOKIE[self::REMEMBER_COOKIE])) {
            return;
        }

        $parts = explode(':', (string) $_COOKIE[self::REMEMBER_COOKIE], 2);
        if (count($parts) !== 2 || !preg_match('/\A[a-f0-9]{24}\z/', $parts[0]) || !preg_match('/\A[a-f0-9]{64}\z/', $parts[1])) {
            self::clearRememberCookie();
            return;
        }

        $stmt = $pdo->prepare(
            'SELECT t.id AS token_id, t.token_hash, t.device_hash, u.id, u.full_name, u.email FROM user_remember_tokens t JOIN users u ON u.id = t.user_id WHERE t.selector = ? AND t.expires_at > NOW() LIMIT 1'
        );
        $stmt->execute([$parts[0]]);
        $token = $stmt->fetch();
        if (!$token || !hash_equals($token['token_hash'], hash('sha256', $parts[1])) || !hash_equals($token['device_hash'], hash('sha256', self::deviceId()))) {
            if ($token) {
                $pdo->prepare('DELETE FROM user_remember_tokens WHERE id = ?')->execute([(int) $token['token_id']]);
            }
            self::clearRememberCookie();
            return;
        }

        self::establishSession($pdo, $token);
        $pdo->prepare('UPDATE user_remember_tokens SET last_used_at = NOW() WHERE id = ?')->execute([(int) $token['token_id']]);
    }

    public static function forgetCurrentDevice(PDO $pdo): void
    {
        if (!empty($_SESSION['user_id'])) {
            $pdo->prepare('DELETE FROM user_remember_tokens WHERE user_id = ? AND device_hash = ?')
                ->execute([(int) $_SESSION['user_id'], hash('sha256', self::deviceId())]);
        }
        self::clearRememberCookie();
    }

    public static function forgetAllDevices(PDO $pdo, int $userId): void
    {
        $pdo->prepare('DELETE FROM user_remember_tokens WHERE user_id = ?')->execute([$userId]);
        $pdo->prepare('INSERT INTO user_auth_versions (user_id, version) VALUES (?, 2) ON DUPLICATE KEY UPDATE version = version + 1')->execute([$userId]);
        self::clearRememberCookie();
    }

    public static function createPasswordReset(PDO $pdo, string $email): ?string
    {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user) {
            return null;
        }

        $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = ? OR expires_at <= NOW()')->execute([(int) $user['id']]);
        $token = bin2hex(random_bytes(32));
        $pdo->prepare(
            'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))'
        )->execute([(int) $user['id'], hash('sha256', $token)]);

        return $token;
    }

    public static function consumePasswordReset(PDO $pdo, string $token, string $password): ?array
    {
        if (!preg_match('/\A[a-f0-9]{64}\z/', $token)) {
            return null;
        }

        $stmt = $pdo->prepare(
            'SELECT r.id, r.user_id, u.full_name, u.email FROM password_reset_tokens r JOIN users u ON u.id = r.user_id WHERE r.token_hash = ? AND r.expires_at > NOW() AND r.used_at IS NULL LIMIT 1'
        );
        $stmt->execute([hash('sha256', $token)]);
        $reset = $stmt->fetch();
        if (!$reset) {
            return null;
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ? AND used_at IS NULL')->execute([(int) $reset['id']]);
            $pdo->prepare('UPDATE users SET password_hash = ?, has_password = 1 WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), (int) $reset['user_id']]);
            $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?')->execute([(int) $reset['user_id']]);
            $pdo->prepare('DELETE FROM user_remember_tokens WHERE user_id = ?')->execute([(int) $reset['user_id']]);
            $pdo->prepare('INSERT INTO user_auth_versions (user_id, version) VALUES (?, 2) ON DUPLICATE KEY UPDATE version = version + 1')->execute([(int) $reset['user_id']]);
            $pdo->commit();
        } catch (Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }

        return $reset;
    }

    private static function deviceId(): string
    {
        if (empty($_COOKIE[self::DEVICE_COOKIE]) || !preg_match('/\A[a-f0-9]{64}\z/', (string) $_COOKIE[self::DEVICE_COOKIE])) {
            $deviceId = bin2hex(random_bytes(32));
            self::setCookie(self::DEVICE_COOKIE, $deviceId, time() + self::REMEMBER_LIFETIME);
            return $deviceId;
        }

        return (string) $_COOKIE[self::DEVICE_COOKIE];
    }

    private static function authVersion(PDO $pdo, int $userId): int
    {
        $pdo->prepare('INSERT IGNORE INTO user_auth_versions (user_id) VALUES (?)')->execute([$userId]);
        $stmt = $pdo->prepare('SELECT version FROM user_auth_versions WHERE user_id = ?');
        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }

    private static function clearRememberCookie(): void
    {
        self::setCookie(self::REMEMBER_COOKIE, '', time() - 3600);
    }

    private static function setCookie(string $name, string $value, int $expires): void
    {
        setcookie($name, $value, [
            'expires' => $expires,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
