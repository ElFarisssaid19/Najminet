<?php
declare(strict_types=1);

require_once __DIR__ . '/PasswordPolicy.php';

/**
 * Markup shared by the forms that set a password (register, reset).
 * assets/js/auth-forms.js makes it live. Without JavaScript the
 * checklist still lists the rules, and the server enforces them either
 * way.
 */

/** Strength meter and rule checklist, placed under the password input. */
function password_checklist(string $id = 'password-rules'): string
{
    $items = '';
    foreach (PasswordPolicy::RULES as $rule) {
        $items .= sprintf(
            '<li data-rule="%s" data-state="unmet"><span class="rule-icon" aria-hidden="true"></span><span>%s</span></li>',
            $rule,
            htmlspecialchars(PasswordPolicy::ruleLabel($rule))
        );
    }

    return sprintf(
        '<div class="password-strength" data-password-strength hidden>'
        . '<div class="password-strength__bar" aria-hidden="true"><span></span><span></span><span></span></div>'
        . '<p class="password-strength__label">%s: <strong data-strength-label></strong></p></div>'
        . '<div class="password-rules" id="%s"><p class="password-rules__title">%s</p><ul data-password-checklist>%s</ul></div>',
        'Strength',
        htmlspecialchars($id),
        'Your password must:',
        $items
    );
}

/**
 * Settings for assets/js/auth-forms.js: the policy constants plus the
 * English labels. $identityTokens is for pages where the name and
 * email are not form fields (password reset).
 *
 * @param list<string> $identityTokens
 */
function password_policy_config(array $identityTokens = []): string
{
    $config = PasswordPolicy::clientConfig() + [
        'identityTokens' => $identityTokens,
        'strength' => [
            'weak' => 'Weak',
            'medium' => 'Medium',
            'strong' => 'Strong',
        ],
        'messages' => [
            'rulesUnmet' => 'Your password does not meet all the rules yet.',
            'mismatch' => 'Passwords do not match.',
        ],
    ];

    return '<script type="application/json" id="passwordPolicyConfig">'
        . json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        . '</script>';
}

/** URL with a ?v= that changes with the file, so browsers never mix old and new assets. */
function asset_url(string $path): string
{
    $file = dirname(__DIR__) . $path;

    return is_file($file) ? $path . '?v=' . filemtime($file) : $path;
}
