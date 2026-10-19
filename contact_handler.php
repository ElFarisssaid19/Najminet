<?php
declare(strict_types=1);

/**
 * contact_handler.php
 * --------------------------------------------------------------------
 * Target of the form on contact.php. Each message is stored in
 * contact_messages, then a copy is emailed to CONTACT_TO (defaults to
 * MAIL_FROM) with the visitor as Reply-To.
 *
 * Protected by a CSRF token, the same honeypot as the newsletter and a
 * per-IP rate limit. The visitor is told "sent" only once the message
 * is saved; a mail problem after that is logged, not shown.
 * --------------------------------------------------------------------
 */

session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/Mailer.php';
require_once __DIR__ . '/classes/RateLimiter.php';

const CONTACT_MAX_PER_IP = 5;
const CONTACT_WINDOW_SECONDS = 900;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /contact.php');
    exit;
}

/** Shows the contact page again with a message (and the visitor's input). */
function contact_fail(int $status, string $message): never
{
    http_response_code($status);
    $contactNotice = ['ok' => false, 'message' => $message];
    require __DIR__ . '/contact.php';
    exit;
}

if (!AuthSecurity::validFormCsrfToken('contact', $_POST['csrf_token'] ?? null)) {
    contact_fail(403, 'Your session has expired. Please send your message again.');
}

// Hidden field a person never fills in: bots get the usual success page,
// and nothing is stored or sent.
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    header('Location: /contact.php?sent=1');
    exit;
}

$ipAddress = RateLimiter::clientIp();
if (!(new RateLimiter($pdo))->attempt('contact_ip', $ipAddress, CONTACT_MAX_PER_IP, CONTACT_WINDOW_SECONDS)) {
    contact_fail(429, 'Too many messages from your network. Please wait 15 minutes and try again.');
}

$name    = mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 150);
$email   = trim((string) ($_POST['email'] ?? ''));
$subject = mb_substr(trim((string) ($_POST['subject'] ?? '')), 0, 200) ?: 'New contact message';
$message = mb_substr(trim((string) ($_POST['message'] ?? '')), 0, 5000);

if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
    contact_fail(422, 'Please fill in your name, a valid email address and a message.');
}

try {
    $pdo->prepare('INSERT INTO contact_messages (name, email, subject, message, ip_address) VALUES (?, ?, ?, ?, ?)')
        ->execute([$name, $email, $subject, $message, $ipAddress]);
    $messageId = (int) $pdo->lastInsertId();
} catch (PDOException $exception) {
    error_log('Contact message could not be saved: ' . $exception->getMessage());
    contact_fail(500, 'Your message could not be sent. Please try again later.');
}

$recipient = trim((string) getenv('CONTACT_TO')) ?: trim((string) getenv('MAIL_FROM'));
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$html = sprintf(
    '<p><strong>From:</strong> %s &lt;%s&gt;</p><p><strong>Subject:</strong> %s</p><p style="white-space:pre-wrap;">%s</p>',
    $escape($name),
    $escape($email),
    $escape($subject),
    $escape($message)
);
$text = "From: {$name} <{$email}>\nSubject: {$subject}\n\n{$message}\n";

if ($recipient === '') {
    error_log("Contact message #{$messageId} saved but not emailed: neither CONTACT_TO nor MAIL_FROM is set.");
} elseif (Mailer::send($recipient, 'NajmiNet contact: ' . $subject, $html, $text, $email)) {
    $pdo->prepare('UPDATE contact_messages SET emailed_at = NOW() WHERE id = ?')->execute([$messageId]);
} else {
    error_log("Contact message #{$messageId} saved but the email copy could not be sent.");
}

header('Location: /contact.php?sent=1');
exit;
