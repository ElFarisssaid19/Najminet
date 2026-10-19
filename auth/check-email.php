<?php
declare(strict_types=1);

/**
 * auth/check-email.php
 * --------------------------------------------------------------------
 * Live "is this email already registered?" check for the register form
 * (called when the email field loses focus).
 *
 * POST csrf_token, email → JSON {"status": …}
 *   available   no account uses this email
 *   registered  an email/password account exists
 *   google      the account signs in with Google and has no password
 *   both        the account has a password and also signs in with Google
 *   invalid     not an email address
 *   rate_limited, error
 *
 * The answer reveals who has an account, so the endpoint needs the
 * register form's CSRF token and shares a per-IP budget with the
 * register handler (AuthSecurity::EMAIL_LOOKUP_*). The register handler
 * always checks again on submit.
 * --------------------------------------------------------------------
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../classes/RateLimiter.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

/** Sends the JSON reply and stops. */
function email_check_respond(int $httpStatus, string $status): never
{
    http_response_code($httpStatus);
    echo json_encode(['status' => $status]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    email_check_respond(405, 'error');
}

if (!AuthSecurity::validCsrfToken($_POST['csrf_token'] ?? null)) {
    email_check_respond(403, 'error');
}

$limiter = new RateLimiter($pdo);
$ipAddress = RateLimiter::clientIp();
if ($limiter->tooManyAttempts(
    AuthSecurity::EMAIL_LOOKUP_ACTION,
    $ipAddress,
    AuthSecurity::EMAIL_LOOKUP_MAX_ATTEMPTS,
    AuthSecurity::EMAIL_LOOKUP_WINDOW_SECONDS
)) {
    email_check_respond(429, 'rate_limited');
}
$limiter->recordAttempt(AuthSecurity::EMAIL_LOOKUP_ACTION, $ipAddress);

$email = AuthSecurity::normaliseEmail((string) ($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    email_check_respond(422, 'invalid');
}

$accountType = AuthSecurity::existingAccountType($pdo, $email);
email_check_respond(200, match ($accountType) {
    null => 'available',
    'google', 'both' => $accountType,
    default => 'registered',
});
