<?php
/**
 * includes/load_env.php
 * ---------------------------------------------------------------
 * Lightweight .env file loader.
 *
 * Reads KEY=VALUE pairs from the project root. .env.local holds the
 * real values (git-ignored; template: .env.local.example). An optional
 * .env is read first, so .env.local overrides it when both exist; a
 * missing file is skipped. Values are made available via:
 *   - putenv("KEY=VALUE")   → getenv("KEY") works
 *   - $_ENV["KEY"]          → $_ENV superglobal populated
 *
 * Include ONCE at the top of any entry-point script. Because
 * includes/db.php already requires this file, every page that
 * needs the database automatically has access to all env vars.
 * ---------------------------------------------------------------
 */

// Prevent double-loading
if (defined('ENV_LOADED')) {
    return;
}
define('ENV_LOADED', true);

foreach ([__DIR__ . '/../.env', __DIR__ . '/../.env.local'] as $envPath) {
    if (!is_file($envPath) || !is_readable($envPath)) {
        continue;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        continue;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments (lines starting with #)
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Split on the FIRST = only (values may contain =)
        $eqPos = strpos($line, '=');
        if ($eqPos === false) {
            continue;
        }

        $key   = trim(substr($line, 0, $eqPos));
        $value = trim(substr($line, $eqPos + 1));

        if ($key === '') {
            continue;
        }

        // Remove surrounding quotes (single or double) if present
        if ((str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
    }
}
