<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/load_env.php';

final class Mailer
{
    /**
     * Sends an HTML email through the configured SMTP account.
     *
     * Every setting comes from the environment (see .env.example); no host,
     * address or credential is hardcoded here. For Gmail, use
     * MAIL_HOST=smtp.gmail.com, MAIL_PORT=587, MAIL_ENCRYPTION=tls and an
     * app password (not the account password) in MAIL_PASSWORD.
     *
     * Returns false instead of throwing, so a failed email never breaks the
     * page that triggered it. Details go to the PHP error log.
     */
    public static function send(string $to, string $subject, string $htmlBody, string $textBody, ?string $replyToOverride = null): bool
    {
        $mail = new PHPMailer(true);
        $password = (string) getenv('MAIL_PASSWORD');

        try {
            $host = trim((string) getenv('MAIL_HOST'));
            $username = trim((string) getenv('MAIL_USERNAME'));
            $from = trim((string) getenv('MAIL_FROM'));
            $fromName = trim((string) getenv('MAIL_FROM_NAME'));
            // Replies should reach a mailbox a human reads; fall back to the
            // From address when MAIL_REPLY_TO is not configured.
            $replyTo = trim((string) getenv('MAIL_REPLY_TO')) ?: $from;
            // A contact message is answered to the visitor who wrote it.
            $replyToName = $fromName !== '' ? $fromName : $replyTo;
            if ($replyToOverride !== null && filter_var($replyToOverride, FILTER_VALIDATE_EMAIL)) {
                $replyTo = $replyToOverride;
                $replyToName = '';
            }
            $port = (int) (getenv('MAIL_PORT') ?: 587);
            $encryption = strtolower(trim((string) getenv('MAIL_ENCRYPTION')));

            if ($host === '' || $username === '' || $password === '' || $from === '') {
                throw new \RuntimeException('Missing required SMTP configuration.');
            }

            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;
            $mail->Port = $port;
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->Timeout = 20;
            // Never echo the SMTP conversation: it carries the credentials.
            $mail->SMTPDebug = SMTP::DEBUG_OFF;

            if ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }

            $mail->setFrom($from, $fromName !== '' ? $fromName : $from);
            $mail->addReplyTo($replyTo, $replyToName);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody;

            $mail->send();
            return true;
        } catch (Exception | \RuntimeException $exception) {
            error_log(sprintf(
                'SMTP email delivery failed: %s',
                self::scrubSecrets($mail->ErrorInfo ?: $exception->getMessage(), $password)
            ));
            return false;
        }
    }

    /**
     * Keeps the SMTP password out of the error log even if a server response
     * or exception message happens to quote it back at us.
     */
    private static function scrubSecrets(string $message, string $password): string
    {
        if ($password === '') {
            return $message;
        }

        return str_replace(
            [$password, base64_encode($password)],
            '[redacted]',
            $message
        );
    }
}
