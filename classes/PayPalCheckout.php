<?php
declare(strict_types=1);

/**
 * PayPalCheckout
 * --------------------------------------------------------------------
 * Server side of the Professional plan checkout (PayPal Orders API v2).
 *
 *   createOrder()        the price comes from the constants below, never
 *                        from the browser; the order is recorded in
 *                        `payments` as CREATED (pending) for its user
 *   captureAndActivate() only the session user's own pending order is
 *                        captured; PayPal's answer must say COMPLETED for
 *                        exactly this amount, currency, payee
 *                        (PAYPAL_MERCHANT_ID) and user; the plan is then
 *                        activated in one transaction, so a payment can
 *                        never activate twice
 *
 * Credentials: PAYPAL_MODE, PAYPAL_CLIENT_ID, PAYPAL_CLIENT_SECRET and
 * PAYPAL_MERCHANT_ID in .env.local.
 * --------------------------------------------------------------------
 */
final class PayPalCheckout
{
    public const PLAN_AMOUNT = '10.00';
    public const PLAN_CURRENCY = 'USD';
    public const PLAN_DAYS = 30;
    private const PLAN_DESCRIPTION = 'NajmiNet Professional plan (30 days)';

    private ?string $accessToken = null;

    public function __construct(private PDO $pdo) {}

    /** PayPal order ids are short upper-case alphanumeric strings. */
    public static function isValidOrderId(string $orderId): bool
    {
        return preg_match('/\A[A-Z0-9]{10,32}\z/', $orderId) === 1;
    }

    /** Creates the PayPal order for $userId and records it as pending. Returns the order id. */
    public function createOrder(int $userId): string
    {
        [$status, $order] = $this->request('POST', '/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'custom_id' => (string) $userId,
                'description' => self::PLAN_DESCRIPTION,
                'amount' => ['currency_code' => self::PLAN_CURRENCY, 'value' => self::PLAN_AMOUNT],
            ]],
        ], 'najminet-create-' . bin2hex(random_bytes(12)));

        $orderId = (string) ($order['id'] ?? '');
        if ($status >= 300 || !self::isValidOrderId($orderId)) {
            throw new RuntimeException('PayPal order creation failed (HTTP ' . $status . ').');
        }

        $this->pdo->prepare(
            "INSERT INTO payments (user_id, provider, order_id, amount, currency, status) VALUES (?, 'paypal', ?, ?, ?, 'CREATED')"
        )->execute([$userId, $orderId, self::PLAN_AMOUNT, self::PLAN_CURRENCY]);

        return $orderId;
    }

    /**
     * Captures the session user's pending order and activates the plan.
     *
     * @return array{ok: bool, status: int, message: string}
     */
    public function captureAndActivate(int $userId, string $orderId): array
    {
        if (!self::isValidOrderId($orderId)) {
            return self::result(false, 400, 'Invalid order.');
        }

        $stmt = $this->pdo->prepare(
            "SELECT id, amount, currency FROM payments
             WHERE order_id = ? AND user_id = ? AND provider = 'paypal' AND status = 'CREATED' LIMIT 1"
        );
        $stmt->execute([$orderId, $userId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($payment === false) {
            // Unknown, another user's, or already used.
            return self::result(false, 409, 'This payment cannot be used. Please start the checkout again.');
        }

        $merchantId = trim((string) getenv('PAYPAL_MERCHANT_ID'));
        if ($merchantId === '') {
            error_log('PayPal capture refused: PAYPAL_MERCHANT_ID is not configured.');
            return self::result(false, 503, 'Payments are temporarily unavailable. You have not been charged.');
        }

        // Capture once per order (PayPal-Request-Id makes a retry safe), then
        // read the full order back: that is what gets verified.
        [$captureStatus, $capture] = $this->request('POST', "/v2/checkout/orders/{$orderId}/capture", null, 'najminet-capture-' . $orderId);
        $issue = (string) ($capture['details'][0]['issue'] ?? '');
        if ($captureStatus >= 300 && $issue !== 'ORDER_ALREADY_CAPTURED') {
            error_log(sprintf('PayPal capture failed for order %s: HTTP %d %s', $orderId, $captureStatus, $issue));
            return self::result(false, 402, 'The payment was not completed. You have not been charged.');
        }
        [$orderStatus, $order] = $this->request('GET', "/v2/checkout/orders/{$orderId}");
        if ($orderStatus !== 200) {
            error_log(sprintf('PayPal order lookup failed for %s: HTTP %d', $orderId, $orderStatus));
            return self::result(false, 502, 'The payment could not be confirmed. Please contact support.');
        }

        $problem = self::verifyCapturedOrder($order, $orderId, $userId, (string) $payment['amount'], (string) $payment['currency'], $merchantId);
        if ($problem !== null) {
            error_log(sprintf('PayPal order %s rejected for user %d: %s', $orderId, $userId, $problem));
            $this->pdo->prepare("UPDATE payments SET status = 'REJECTED' WHERE id = ? AND status = 'CREATED'")->execute([(int) $payment['id']]);
            return self::result(false, 422, 'The payment could not be confirmed. Please contact support.');
        }

        $captureId = (string) ($order['purchase_units'][0]['payments']['captures'][0]['id'] ?? '');
        $this->pdo->beginTransaction();
        try {
            // Only the request that flips CREATED → COMPLETED may activate the plan.
            $flip = $this->pdo->prepare(
                "UPDATE payments SET status = 'COMPLETED', capture_id = ?, completed_at = NOW() WHERE id = ? AND status = 'CREATED'"
            );
            $flip->execute([$captureId, (int) $payment['id']]);
            if ($flip->rowCount() !== 1) {
                $this->pdo->rollBack();
                return self::result(false, 409, 'This payment has already been used.');
            }
            $this->pdo->prepare(
                "UPDATE users
                    SET plan = 'professional', subscription_status = 'active', payment_provider = 'paypal',
                        paypal_order_id = ?, subscription_start = NOW(),
                        subscription_end = DATE_ADD(NOW(), INTERVAL " . self::PLAN_DAYS . " DAY)
                  WHERE id = ?"
            )->execute([$orderId, $userId]);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            error_log(sprintf('Activation failed for PayPal order %s: %s', $orderId, $exception->getMessage()));
            return self::result(false, 500, 'Your payment was received but the plan could not be activated. Please contact support.');
        }

        return self::result(true, 200, 'Your subscription is now active.');
    }

    /**
     * Checks PayPal's order (GET /v2/checkout/orders/{id}) against what was
     * sold. Returns null when it is acceptable, otherwise the reason.
     */
    public static function verifyCapturedOrder(array $order, string $orderId, int $userId, string $amount, string $currency, string $merchantId): ?string
    {
        $units = $order['purchase_units'] ?? [];
        $unit = $units[0] ?? [];
        $captures = $unit['payments']['captures'] ?? [];
        $capture = $captures[0] ?? [];
        $money = static fn (mixed $value): string => number_format((float) $value, 2, '.', '');

        return match (true) {
            ($order['id'] ?? '') !== $orderId => 'order id mismatch',
            ($order['status'] ?? '') !== 'COMPLETED' => 'order status ' . ($order['status'] ?? 'missing'),
            count($units) !== 1 => 'unexpected number of purchase units',
            $merchantId === '' || ($unit['payee']['merchant_id'] ?? '') !== $merchantId => 'payee is not PAYPAL_MERCHANT_ID',
            (string) ($unit['custom_id'] ?? '') !== (string) $userId => 'order belongs to another user',
            count($captures) !== 1 || ($capture['status'] ?? '') !== 'COMPLETED' => 'capture not completed',
            ($capture['amount']['currency_code'] ?? '') !== $currency => 'currency mismatch',
            $money($capture['amount']['value'] ?? -1) !== $money($amount) => 'amount mismatch',
            default => null,
        };
    }

    /** @return array{0: int, 1: array<mixed>} HTTP status and decoded JSON */
    private function request(string $method, string $path, ?array $body = null, ?string $requestId = null): array
    {
        $headers = ['Authorization: Bearer ' . $this->accessToken(), 'Content-Type: application/json', 'Prefer: return=representation'];
        if ($requestId !== null) {
            $headers[] = 'PayPal-Request-Id: ' . $requestId;
        }
        $ch = curl_init(self::baseUrl() . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 20,
        ] + ($body !== null || $method === 'POST' ? [CURLOPT_POSTFIELDS => $body !== null ? json_encode($body) : ''] : []));
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        return [$status, is_string($response) ? (json_decode($response, true) ?? []) : []];
    }

    private function accessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }
        $ch = curl_init(self::baseUrl() . '/v1/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_USERPWD => (string) getenv('PAYPAL_CLIENT_ID') . ':' . (string) getenv('PAYPAL_CLIENT_SECRET'),
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_TIMEOUT => 20,
        ]);
        $token = json_decode((string) curl_exec($ch), true)['access_token'] ?? null;
        curl_close($ch);
        if (!is_string($token) || $token === '') {
            throw new RuntimeException('PayPal authentication failed.');
        }

        return $this->accessToken = $token;
    }

    private static function baseUrl(): string
    {
        return getenv('PAYPAL_MODE') === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
    }

    /** @return array{ok: bool, status: int, message: string} */
    private static function result(bool $ok, int $status, string $message): array
    {
        return ['ok' => $ok, 'status' => $status, 'message' => $message];
    }
}
