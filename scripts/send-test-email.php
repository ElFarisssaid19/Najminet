<?php
declare(strict_types=1);

/**
 * scripts/send-test-email.php
 * --------------------------------------------------------------------
 * Sends one test email through the configured SMTP account, so the
 * MAIL_* settings can be checked without going through a real flow
 * such as a password reset.
 *
 * Usage (inside the web container):
 *     docker compose exec web php scripts/send-test-email.php you@example.com
 *
 * The recipient defaults to MAIL_REPLY_TO, then MAIL_FROM. No credential
 * is ever printed: only the host, port, encryption and addresses.
 * --------------------------------------------------------------------
 */

// The project directory is the Apache web root, so this file is reachable
// over HTTP. Refuse to run anywhere but the command line.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/load_env.php';
require_once __DIR__ . '/../includes/Mailer.php';

$recipient = $argv[1]
    ?? (trim((string) getenv('MAIL_REPLY_TO')) ?: trim((string) getenv('MAIL_FROM')));

if (!filter_var((string) $recipient, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php scripts/send-test-email.php recipient@example.com\n");
    fwrite(STDERR, "No valid recipient given and MAIL_REPLY_TO / MAIL_FROM are not set.\n");
    exit(1);
}

printf(
    "Host       : %s:%s (%s)\nUsername   : %s\nPassword   : %s\nFrom       : %s <%s>\nReply-To   : %s\nRecipient  : %s\n\n",
    getenv('MAIL_HOST') ?: '(unset)',
    getenv('MAIL_PORT') ?: '587',
    getenv('MAIL_ENCRYPTION') ?: '(none)',
    getenv('MAIL_USERNAME') ?: '(unset)',
    getenv('MAIL_PASSWORD') ? '(set, hidden)' : '(unset)',
    getenv('MAIL_FROM_NAME') ?: '(unset)',
    getenv('MAIL_FROM') ?: '(unset)',
    (getenv('MAIL_REPLY_TO') ?: getenv('MAIL_FROM')) ?: '(unset)',
    $recipient
);

$sentAt = date('Y-m-d H:i:s');
$html = '<p>This is a NajmiNet SMTP test message.</p><p>Sent at ' . htmlspecialchars($sentAt, ENT_QUOTES, 'UTF-8') . '.</p>';
$text = "This is a NajmiNet SMTP test message.\n\nSent at {$sentAt}.";

if (Mailer::send((string) $recipient, 'NajmiNet SMTP test', $html, $text)) {
    echo "Result     : sent\n";
    exit(0);
}

echo "Result     : FAILED — see the PHP error log for the SMTP error.\n";
echo "             docker compose logs web | tail -20\n";
exit(1);
