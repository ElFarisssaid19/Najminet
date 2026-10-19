<?php
declare(strict_types=1);

/**
 * newsletter/confirm.php
 * --------------------------------------------------------------------
 * Target of the confirmation link. Flips a pending subscriber to
 * confirmed, then notifies NEWSLETTER_ADMIN_EMAIL.
 *
 * The notification is best-effort: if it fails, the subscriber still
 * sees the success page and the failure is logged.
 * --------------------------------------------------------------------
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/NewsletterMailer.php';
require_once __DIR__ . '/../includes/newsletter_page.php';
require_once __DIR__ . '/../classes/NewsletterManager.php';

$token = (string) ($_GET['token'] ?? '');
$subscriber = (new NewsletterManager($pdo))->confirmByToken($token);

if ($subscriber === null) {
    http_response_code(404);
    echo newsletter_render_page(
        'This link is no longer valid',
        'It may have expired or already been used. Please subscribe again to get a new link.',
        false
    );
    exit;
}

// Never let a mail problem turn a successful confirmation into an error.
try {
    NewsletterMailer::notifyAdmin($subscriber['email'], date('Y-m-d H:i:s'));
} catch (Throwable $exception) {
    error_log('Newsletter admin notification threw: ' . $exception->getMessage());
}

echo newsletter_render_page(
    'Subscription confirmed',
    'You are on the list. Thanks for subscribing to the NajmiNet newsletter.'
);
