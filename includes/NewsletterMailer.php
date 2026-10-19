<?php
declare(strict_types=1);

require_once __DIR__ . '/Mailer.php';
require_once __DIR__ . '/../classes/NewsletterManager.php';

/**
 * NewsletterMailer
 * --------------------------------------------------------------------
 * Builds and sends the two newsletter emails:
 *
 *   - the double opt-in confirmation
 *   - a short notification to NEWSLETTER_ADMIN_EMAIL once they confirm
 *
 * Every method returns a bool and never throws, so a mail problem can
 * never break the page that triggered it.
 * --------------------------------------------------------------------
 */
final class NewsletterMailer
{
    /** Double opt-in confirmation email. */
    public static function sendConfirmation(
        string $email,
        string $confirmToken,
        string $unsubscribeToken
    ): bool {
        $baseUrl = self::baseUrl();
        if ($baseUrl === null) {
            return false;
        }

        $message = self::buildConfirmation(
            $baseUrl . '/newsletter/confirm.php?token=' . urlencode($confirmToken),
            $baseUrl . '/newsletter/unsubscribe.php?token=' . urlencode($unsubscribeToken)
        );

        return Mailer::send($email, $message['subject'], $message['html'], $message['text']);
    }

    /**
     * Composes the confirmation email. Separate from delivery so the
     * wording, the links and the text alternative can be checked without
     * an SMTP server.
     *
     * @return array{subject: string, html: string, text: string}
     */
    public static function buildConfirmation(string $confirmUrl, string $unsubscribeUrl): array
    {
        $heading = 'Confirm your subscription';
        $intro = 'Click the button below to start receiving the NajmiNet newsletter.';
        $button = 'Confirm subscription';
        $expiry = sprintf('This link expires in %d hours.', NewsletterManager::CONFIRM_TTL_HOURS);
        $ignore = 'If you did not request this, you can safely ignore this email.';
        $unsubscribeLabel = 'Unsubscribe';

        $html = self::wrapHtml(sprintf(
            '<h1 style="font-size:20px;margin:0 0 16px;">%s</h1>'
            . '<p style="margin:0 0 20px;">%s</p>'
            . '<p style="margin:0 0 24px;"><a href="%s" style="background:#7c5cff;color:#ffffff;'
            . 'text-decoration:none;padding:12px 22px;border-radius:8px;display:inline-block;">%s</a></p>'
            . '<p style="margin:0 0 8px;color:#666;font-size:13px;">%s</p>'
            . '<p style="margin:0 0 24px;color:#666;font-size:13px;">%s</p>'
            . '<p style="margin:0;color:#888;font-size:12px;">'
            . '<a href="%s" style="color:#888;">%s</a></p>',
            self::escape($heading),
            self::escape($intro),
            self::escape($confirmUrl),
            self::escape($button),
            self::escape($expiry),
            self::escape($ignore),
            self::escape($unsubscribeUrl),
            self::escape($unsubscribeLabel)
        ));

        $text = $heading . "\n\n" . $intro . "\n\n" . $confirmUrl . "\n\n"
            . $expiry . "\n" . $ignore . "\n\n" . $unsubscribeLabel . ': ' . $unsubscribeUrl . "\n";

        return [
            'subject' => 'Confirm your NajmiNet newsletter subscription',
            'html' => $html,
            'text' => $text,
        ];
    }

    /**
     * Tells the site owner that someone confirmed. Failure is logged and
     * swallowed.
     */
    public static function notifyAdmin(string $subscriberEmail, string $confirmedAt): bool
    {
        $admin = trim((string) getenv('NEWSLETTER_ADMIN_EMAIL'));

        if ($admin === '' || !filter_var($admin, FILTER_VALIDATE_EMAIL)) {
            error_log('Newsletter admin notification skipped: NEWSLETTER_ADMIN_EMAIL is not a valid address.');
            return false;
        }

        $rows = [
            'Subscriber' => $subscriberEmail,
            'Confirmed at' => $confirmedAt,
        ];

        $list = '';
        foreach ($rows as $label => $value) {
            $list .= sprintf(
                '<li style="margin-bottom:6px;"><strong>%s:</strong> %s</li>',
                self::escape($label),
                self::escape($value)
            );
        }

        $html = self::wrapHtml(sprintf(
            '<h1 style="font-size:18px;margin:0 0 16px;">New newsletter subscriber</h1>'
            . '<ul style="margin:0;padding-inline-start:18px;">%s</ul>',
            $list
        ));

        $text = "New newsletter subscriber\n\n";
        foreach ($rows as $label => $value) {
            $text .= "{$label}: {$value}\n";
        }

        $sent = Mailer::send($admin, 'NajmiNet — new newsletter subscriber', $html, $text);

        if (!$sent) {
            error_log(sprintf('Newsletter admin notification could not be sent for %s.', $subscriberEmail));
        }

        return $sent;
    }

    /**
     * Public base URL for emailed links.
     *
     * Only APP_URL is trusted. The Host header is attacker-controlled, so
     * falling back to it would allow poisoned confirmation links.
     */
    private static function baseUrl(): ?string
    {
        $baseUrl = rtrim((string) getenv('APP_URL'), '/');

        if ($baseUrl === '') {
            error_log('Newsletter email not sent: APP_URL is not configured.');
            return null;
        }

        return $baseUrl;
    }

    private static function wrapHtml(string $body): string
    {
        return sprintf(
            '<!doctype html><html lang="en"><body style="margin:0;padding:24px;'
            . 'background:#f6f7fb;font-family:Arial,Helvetica,sans-serif;color:#1a1a2e;">'
            . '<div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:28px;">%s'
            . '<hr style="border:none;border-top:1px solid #eee;margin:24px 0 12px;">'
            . '<p style="margin:0;color:#999;font-size:12px;">NajmiNet Innovations</p>'
            . '</div></body></html>',
            $body
        );
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
