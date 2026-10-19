<?php
/**
 * WhatsApp contact link, built from CONTACT_WHATSAPP in .env.local: the number
 * in international format, digits only, country code first (e.g. 2126XXXXXXXX).
 * Spaces, dashes and a leading "+" are tolerated.
 *
 * Returns null when the key is empty or is not a valid international number;
 * contact.php and includes/footer.php then leave their WhatsApp link out.
 */
require_once __DIR__ . '/load_env.php';

function contact_whatsapp_url(): ?string
{
    $number = preg_replace('/[\s-]+/', '', (string) getenv('CONTACT_WHATSAPP'));
    if (str_starts_with($number, '+')) {
        $number = substr($number, 1);
    }

    // wa.me wants the full number without "+" or a leading 0: 8 to 15 digits (E.164).
    if (preg_match('/^[1-9][0-9]{7,14}$/', $number) !== 1) {
        return null;
    }

    return 'https://wa.me/' . $number;
}
