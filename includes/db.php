<?php

require_once __DIR__ . '/session.php';

// Load environment variables from .env.local (if present).
// This makes getenv('KEY') and $_ENV['KEY'] available globally.
require_once __DIR__ . '/load_env.php';

$host   = getenv('DB_HOST');
$port   = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_NAME');
$user   = getenv('DB_USER');
$pass   = getenv('DB_PASS');

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    // The details (DB user, host, SQLSTATE) go to the server log only;
    // visitors get a generic page.
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(503);
    header('Retry-After: 60');
    exit('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"><link rel="icon" href="/favicon.svg" type="image/svg+xml">'
        . '<title>Temporarily unavailable</title></head>'
        . '<body><p>The service is temporarily unavailable. Please try again in a few minutes.</p></body></html>');
}

require_once __DIR__ . '/AuthSecurity.php';
AuthSecurity::restoreRememberedSession($pdo);
