<?php
declare(strict_types=1);


/**
 * PasswordPolicy
 * --------------------------------------------------------------------
 * The single source of truth for password strength. Every place that
 * sets a password (registration, password reset) validates through
 * here. assets/js/password-policy.js mirrors these rules for the live
 * checklist, but only the server decides.
 *
 * Rule ids, shared with the client checklist:
 *   length     at least MIN_LENGTH characters
 *   uppercase  an uppercase letter
 *   lowercase  a lowercase letter
 *   digit      a digit
 *   special    a character that is not a letter, digit or space
 *   identity   no part of the user's full name or email name
 *   common     not a common password, including with digits or symbols
 *              around it and look-alike swaps (Password123!, P@ssw0rd),
 *              and none of the BLOCKED_FRAGMENTS anywhere
 *
 * The common list is assets/data/common-passwords.json: SecLists'
 * "10k-most-common" (MIT, see common-passwords.LICENSE). None of its
 * entries meets the other rules as-is, so the useful check is on the
 * password's core: lower-cased, with leading and trailing digits and
 * symbols removed, and with look-alike characters undone.
 * --------------------------------------------------------------------
 */
final class PasswordPolicy
{
    public const MIN_LENGTH = 12;

    /** Rule ids, in display order. */
    public const RULES = ['length', 'uppercase', 'lowercase', 'digit', 'special', 'identity', 'common'];

    /** Checklist wording per rule; %d is MIN_LENGTH. */
    private const RULE_LABELS = [
        'length' => 'Be at least %d characters long',
        'uppercase' => 'Include an uppercase letter (A–Z)',
        'lowercase' => 'Include a lowercase letter (a–z)',
        'digit' => 'Include a number (0–9)',
        'special' => 'Include a special character (! @ # $ …)',
        'identity' => 'Not contain your name or email',
        'common' => 'Not be a common password (like Password123!)',
    ];

    /** Error message per broken rule; %d is MIN_LENGTH. */
    private const ERROR_MESSAGES = [
        'length' => 'Password must be at least %d characters long.',
        'uppercase' => 'Password must include an uppercase letter.',
        'lowercase' => 'Password must include a lowercase letter.',
        'digit' => 'Password must include a number.',
        'special' => 'Password must include a special character.',
        'identity' => 'Password must not contain your name or email address.',
        'common' => 'This password is too common. Choose something less predictable.',
    ];

    /** Name and email fragments shorter than this are not checked. */
    private const MIN_IDENTITY_LENGTH = 3;

    /** Cores shorter than this are not compared with the common list. */
    private const MIN_CORE_LENGTH = 4;

    /** Look-alike characters undone before the common-list lookup. */
    private const LOOKALIKES = ['@' => 'a', '4' => 'a', '3' => 'e', '1' => 'i', '!' => 'i', '0' => 'o', '$' => 's', '5' => 's', '7' => 't'];

    /**
     * Rejected anywhere in the password (MyPassword#2024), as the previous
     * policy did. Kept short on purpose: matching every list entry inside
     * a password would reject ordinary passphrases.
     */
    private const BLOCKED_FRAGMENTS = ['password', 'qwerty', 'letmein', 'welcome', 'admin', '123456', 'football', 'monkey'];

    private const COMMON_LIST = __DIR__ . '/../assets/data/common-passwords.json';

    /**
     * Ids of the rules the password breaks, in RULES order. Empty when
     * the password is acceptable.
     *
     * @return list<string>
     */
    public static function failedRules(string $password, string $fullName = '', string $email = ''): array
    {
        $passes = [
            'length' => mb_strlen($password) >= self::MIN_LENGTH,
            'uppercase' => preg_match('/\p{Lu}/u', $password) === 1,
            'lowercase' => preg_match('/\p{Ll}/u', $password) === 1,
            'digit' => preg_match('/\p{Nd}/u', $password) === 1,
            'special' => preg_match('/[^\p{L}\p{N}\s]/u', $password) === 1,
            'identity' => !self::containsIdentity($password, $fullName, $email),
            'common' => !self::isCommon($password),
        ];

        return array_keys(array_filter($passes, static fn (bool $passed): bool => !$passed));
    }

    /**
     * One message per broken rule.
     *
     * @return list<string>
     */
    public static function errors(string $password, string $fullName = '', string $email = ''): array
    {
        return array_map(
            static fn (string $rule): string => sprintf(self::ERROR_MESSAGES[$rule], self::MIN_LENGTH),
            self::failedRules($password, $fullName, $email)
        );
    }

    /** Checklist wording for a rule id. */
    public static function ruleLabel(string $rule): string
    {
        return sprintf(self::RULE_LABELS[$rule], self::MIN_LENGTH);
    }

    /**
     * Lower-case fragments of the full name and of the email name (the
     * part before "@") that a password must not contain.
     *
     * @return list<string>
     */
    public static function identityTokens(string $fullName, string $email): array
    {
        $emailName = mb_strtolower(trim(explode('@', $email)[0]));
        preg_match_all('/\p{L}+/u', mb_strtolower($fullName) . ' ' . $emailName, $words);

        $tokens = array_filter(
            [$emailName, ...$words[0]],
            static fn (string $token): bool => mb_strlen($token) >= self::MIN_IDENTITY_LENGTH
        );

        return array_values(array_unique($tokens));
    }

    public static function containsIdentity(string $password, string $fullName, string $email): bool
    {
        $lower = mb_strtolower($password);
        foreach (self::identityTokens($fullName, $email) as $token) {
            if (str_contains($lower, $token)) {
                return true;
            }
        }

        return false;
    }

    public static function isCommon(string $password): bool
    {
        $common = self::commonPasswords();
        $lower = mb_strtolower($password);
        $core = (string) preg_replace('/^[^\p{L}]+|[^\p{L}]+$/u', '', $lower);

        foreach (self::BLOCKED_FRAGMENTS as $fragment) {
            if (str_contains($lower, $fragment) || str_contains(strtr($lower, self::LOOKALIKES), $fragment)) {
                return true;
            }
        }

        foreach ([$lower, $core, strtr($core, self::LOOKALIKES)] as $index => $candidate) {
            if ($index > 0 && mb_strlen($candidate) < self::MIN_CORE_LENGTH) {
                continue;
            }
            if (isset($common[$candidate])) {
                return true;
            }
        }

        return false;
    }

    /**
     * The constants assets/js/auth-forms.js needs to mirror these rules,
     * so the browser and the server can never disagree on a threshold.
     *
     * @return array<string, mixed>
     */
    public static function clientConfig(): array
    {
        return [
            'minLength' => self::MIN_LENGTH,
            'minIdentityLength' => self::MIN_IDENTITY_LENGTH,
            'minCoreLength' => self::MIN_CORE_LENGTH,
            'lookalikes' => self::LOOKALIKES,
            'blockedFragments' => self::BLOCKED_FRAGMENTS,
            'commonListUrl' => '/assets/data/common-passwords.json?v=' . filemtime(self::COMMON_LIST),
        ];
    }

    /** @return array<string, true> */
    private static function commonPasswords(): array
    {
        static $list = null;
        if ($list === null) {
            $lines = json_decode((string) file_get_contents(self::COMMON_LIST), true) ?: [];
            $list = array_fill_keys(array_map('mb_strtolower', $lines), true);
        }

        return $list;
    }
}
