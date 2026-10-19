<?php
declare(strict_types=1);

/**
 * newsletter/unsubscribe.php
 * --------------------------------------------------------------------
 * One-click unsubscribe. The token in the link is the only credential,
 * so no login or confirmation step is needed — clicking is enough.
 *
 * Clicking the same link twice shows the same page, so a mail client
 * that pre-fetches the link cannot produce a confusing error.
 * --------------------------------------------------------------------
 */

session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/newsletter_page.php';
require_once __DIR__ . '/../classes/NewsletterManager.php';

$token = (string) ($_GET['token'] ?? '');
$subscriber = (new NewsletterManager($pdo))->unsubscribeByToken($token);

if ($subscriber === null) {
    http_response_code(404);
    echo newsletter_render_page(
        'This link is no longer valid',
        'It may have expired or already been used. Please subscribe again to get a new link.',
        false
    );
    exit;
}

echo newsletter_render_page(
    'You have been unsubscribed',
    'You will no longer receive the NajmiNet newsletter. You can subscribe again whenever you like.'
);
