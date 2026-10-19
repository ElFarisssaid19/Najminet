<?php
declare(strict_types=1);

/**
 * billing/paypal-create-order.php
 * --------------------------------------------------------------------
 * Called by the PayPal button on checkout.php. Creates the order on the
 * server (price from PayPalCheckout, never from the browser) and returns
 * only its id. POST, signed-in user, CSRF token in the X-CSRF-Token header.
 * --------------------------------------------------------------------
 */

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../classes/PayPalCheckout.php';
require_once __DIR__ . '/../classes/RateLimiter.php';

const CREATE_ORDER_MAX_PER_USER = 20;
const CREATE_ORDER_WINDOW_SECONDS = 3600;

header('Content-Type: application/json');
header('Cache-Control: no-store');

function paypal_json(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    paypal_json(405, ['error' => 'Method not allowed.']);
}
if (!isset($_SESSION['user_id'])) {
    paypal_json(401, ['error' => 'Please sign in again.']);
}
if (!AuthSecurity::validCsrfHeader()) {
    paypal_json(403, ['error' => 'Your session has expired. Reload the page and try again.']);
}

$userId = (int) $_SESSION['user_id'];
if (!(new RateLimiter($pdo))->attempt('paypal_create', (string) $userId, CREATE_ORDER_MAX_PER_USER, CREATE_ORDER_WINDOW_SECONDS)) {
    paypal_json(429, ['error' => 'Too many checkout attempts. Please wait and try again.']);
}

try {
    $orderId = (new PayPalCheckout($pdo))->createOrder($userId);
} catch (Throwable $exception) {
    error_log('PayPal create order failed for user ' . $userId . ': ' . $exception->getMessage());
    paypal_json(502, ['error' => 'PayPal is unavailable right now. You have not been charged.']);
}

paypal_json(200, ['id' => $orderId]);
