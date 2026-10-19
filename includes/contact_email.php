<?php
/**
 * Public contact email shown on contact.php, from CONTACT_EMAIL in .env.local.
 *
 * Returns null when the key is empty, is not a valid address, or still uses a
 * reserved documentation domain (example.com, .example, .test, …: the
 * placeholder in .env.local.example); contact.php then leaves the email
 * block out.
 */
require_once __DIR__ . '/load_env.php';

function contact_email_address(): ?string
{
    $email = trim((string) getenv('CONTACT_EMAIL'));
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        return null;
    }

    $domain = strtolower(substr(strrchr($email, '@'), 1));
    if (preg_match('/(^|\.)example\.(com|net|org)$|\.(example|test|invalid|localhost)$/', $domain) === 1) {
        return null;
    }

    return $email;
}
