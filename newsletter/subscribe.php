<?php
declare(strict_types=1);

/**
 * newsletter/subscribe.php
 * --------------------------------------------------------------------
 * Handles the public newsletter form (footer of landing.php).
 *
 * Answers JSON when called by fetch(), otherwise stores a flash message
 * and redirects back to the form. The reply is deliberately identical
 * whether the address is new, pending, unsubscribed or already
 * confirmed, so the form cannot be used to test who is on the list.
 * --------------------------------------------------------------------
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/NewsletterMailer.php';
require_once __DIR__ . '/../classes/NewsletterManager.php';
require_once __DIR__ . '/../classes/RateLimiter.php';

const NEWSLETTER_MAX_ATTEMPTS = 5;
const NEWSLETTER_WINDOW_SECONDS = 900; // 15 minutes

$wantsJson = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch'
    || str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

/** Sends the reply in whichever shape the caller expects, then stops. */
function newsletter_respond(bool $ok, string $message, bool $wantsJson, int $status = 200): never
{
    if ($wantsJson) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $_SESSION['newsletter_flash'] = ['ok' => $ok, 'message' => $message];
    header('Location: /landing.php#newsletter');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    newsletter_respond(false, 'Something went wrong. Please try again later.', $wantsJson, 405);
}

if (!AuthSecurity::validFormCsrfToken('newsletter', $_POST['csrf_token'] ?? null)) {
    newsletter_respond(false, 'Your session has expired. Please try again.', $wantsJson, 400);
}

// Hidden field a human never sees and never fills in. Bots that fill every
// input get the success message without touching the database.
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    newsletter_respond(true, 'Almost there! Check your inbox to confirm your subscription.', $wantsJson);
}

$email = trim((string) ($_POST['email'] ?? ''));
$ipAddress = RateLimiter::clientIp();

$limiter = new RateLimiter($pdo);
if ($limiter->tooManyAttempts('newsletter_subscribe', $ipAddress, NEWSLETTER_MAX_ATTEMPTS, NEWSLETTER_WINDOW_SECONDS)) {
    newsletter_respond(false, 'Too many attempts. Please try again in a few minutes.', $wantsJson, 429);
}
$limiter->recordAttempt('newsletter_subscribe', $ipAddress);

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
    newsletter_respond(false, 'Please enter a valid email address.', $wantsJson, 422);
}

try {
    $subscribers = new NewsletterManager($pdo);
    $result = $subscribers->subscribe($email, $ipAddress);

    if ($result['confirm_token'] !== null && $result['unsubscribe_token'] !== null) {
        // A delivery failure must not change what the visitor is told, or
        // the form would reveal which addresses exist. It is logged inside
        // the mailer instead.
        NewsletterMailer::sendConfirmation(
            $result['email'],
            $result['confirm_token'],
            $result['unsubscribe_token']
        );
    }
} catch (Throwable $exception) {
    error_log('Newsletter subscription failed: ' . $exception->getMessage());
    newsletter_respond(false, 'Something went wrong. Please try again later.', $wantsJson, 500);
}

newsletter_respond(true, 'Almost there! Check your inbox to confirm your subscription.', $wantsJson);
