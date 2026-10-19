<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

$token = $_POST['csrf_token'] ?? '';
if (!is_string($token) || empty($_SESSION['logout_csrf']) || !hash_equals($_SESSION['logout_csrf'], $token)) {
    http_response_code(403);
    exit('Invalid logout request.');
}

AuthSecurity::forgetCurrentDevice($pdo);
$_SESSION = [];

// Remove the session cookie as well as the server-side session data.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'] ?? '/',
        'domain' => $params['domain'] ?? '',
        'secure' => (bool) ($params['secure'] ?? false),
        'httponly' => (bool) ($params['httponly'] ?? true),
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}

session_destroy();
header('Location: /landing.php');
exit;
