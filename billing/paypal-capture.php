<?php
declare(strict_types=1);

/**
 * billing/paypal-capture.php
 * --------------------------------------------------------------------
 * Called by the PayPal button on checkout.php after the buyer approves.
 * Captures the session user's own pending order and activates the plan;
 * all checks are in PayPalCheckout::captureAndActivate().
 * POST, JSON body {"orderID": "..."}, CSRF token in the X-CSRF-Token header.
 * --------------------------------------------------------------------
 */

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../classes/PayPalCheckout.php';

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
    paypal_json(405, ['success' => false, 'message' => 'Method not allowed.']);
}
if (!isset($_SESSION['user_id'])) {
    paypal_json(401, ['success' => false, 'message' => 'Please sign in again.']);
}
if (!AuthSecurity::validCsrfHeader()) {
    paypal_json(403, ['success' => false, 'message' => 'Your session has expired. Reload the page and try again.']);
}

$input = json_decode((string) file_get_contents('php://input'), true);
$orderId = is_array($input) && is_string($input['orderID'] ?? null) ? $input['orderID'] : '';

try {
    $result = (new PayPalCheckout($pdo))->captureAndActivate((int) $_SESSION['user_id'], $orderId);
} catch (Throwable $exception) {
    error_log('PayPal capture error for order ' . (PayPalCheckout::isValidOrderId($orderId) ? $orderId : '(invalid)') . ': ' . $exception->getMessage());
    $result = ['ok' => false, 'status' => 502, 'message' => 'The payment could not be confirmed. Please try again or contact support.'];
}

paypal_json($result['status'], ['success' => $result['ok'], 'message' => $result['message']]);
