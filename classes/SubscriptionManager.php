<?php
declare(strict_types=1);

/**
 * Owns subscription state only. Payment providers call activateProfessional()
 * after they have independently verified a successful payment.
 */
final class SubscriptionManager
{
    public function __construct(private PDO $pdo) {}

    /** Starts the one and only 14-day trial for an account. */
    public function startTrial(int $userId): bool
    {
        $statement = $this->pdo->prepare(
            "UPDATE users
             SET plan = 'trial', subscription_status = 'active', trial_used = 1,
                 subscription_start = CURRENT_TIMESTAMP,
                 subscription_end = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 14 DAY)
             WHERE id = :user_id AND trial_used = 0"
        );

        $statement->execute([':user_id' => $userId]);
        return $statement->rowCount() === 1;
    }

    /** Activates or renews Professional for 30 days after confirmed payment. */
    public function activateProfessional(
        int $userId,
        ?string $customerId = null,
        ?string $subscriptionId = null
    ): bool {
        $statement = $this->pdo->prepare(
            "UPDATE users
             SET plan = 'professional', subscription_status = 'active',
                 subscription_start = CURRENT_TIMESTAMP,
                 subscription_end = DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 30 DAY),
                 stripe_customer_id = :customer_id,
                 stripe_subscription_id = :subscription_id
             WHERE id = :user_id"
        );

        return $statement->execute([
            ':user_id' => $userId,
            ':customer_id' => $customerId,
            ':subscription_id' => $subscriptionId,
        ]);
    }

    /** Marks one expired subscription as expired. */
    public function expireSubscription(int $userId): bool
    {
        $statement = $this->pdo->prepare(
            "UPDATE users SET subscription_status = 'expired'
             WHERE id = :user_id
               AND subscription_status = 'active'
               AND subscription_end IS NOT NULL
               AND subscription_end <= CURRENT_TIMESTAMP"
        );
        return $statement->execute([':user_id' => $userId]);
    }

    /** Updates expiry state before every subscription access check. */
    public function updateExpiredSubscriptions(?int $userId = null): bool
    {
        $sql = "UPDATE users SET subscription_status = 'expired'
                WHERE subscription_status = 'active'
                  AND subscription_end IS NOT NULL
                  AND subscription_end <= CURRENT_TIMESTAMP";
        $parameters = [];

        if ($userId !== null) {
            $sql .= ' AND id = :user_id';
            $parameters[':user_id'] = $userId;
        }

        return $this->pdo->prepare($sql)->execute($parameters);
    }

    public function canStartTrial(int $userId): bool
    {
        $statement = $this->pdo->prepare('SELECT trial_used FROM users WHERE id = :user_id LIMIT 1');
        $statement->execute([':user_id' => $userId]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        return $user !== false && (int) $user['trial_used'] === 0;
    }

    public function isSubscriptionActive(int $userId): bool
    {
        $subscription = $this->getAccessStatus($userId);
        return $subscription !== null && $subscription['subscription_status'] === 'active';
    }

    /**
     * @return array{plan: ?string, subscription_status: ?string, subscription_start: ?string, subscription_end: ?string, trial_used: int}|null
     */
    public function getAccessStatus(int $userId): ?array
    {
        $this->updateExpiredSubscriptions($userId);

        $statement = $this->pdo->prepare(
            'SELECT plan, subscription_status, subscription_start, subscription_end, trial_used
             FROM users WHERE id = :user_id LIMIT 1'
        );
        $statement->execute([':user_id' => $userId]);
        $subscription = $statement->fetch(PDO::FETCH_ASSOC);

        if ($subscription === false) {
            return null;
        }

        $subscription['trial_used'] = (int) $subscription['trial_used'];
        return $subscription;
    }

    /**
     * Creates a Stripe-hosted Checkout session. Card data never reaches this
     * application; Stripe returns to payment_success.php after checkout.
     */
    public function createCheckoutSession(int $userId, string $plan = 'professional'): array
    {
        if ($plan !== 'professional') {
            throw new InvalidArgumentException('Invalid plan selected.');
        }

        $secretKey = getenv('STRIPE_SECRET_KEY') ?: '';
        $priceId = getenv('STRIPE_PROFESSIONAL_PRICE_ID') ?: '';
        $appUrl = rtrim(getenv('APP_URL') ?: '', '/');

        if ($secretKey === '' || $priceId === '' || $appUrl === '') {
            throw new RuntimeException('Stripe checkout is not configured.');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Stripe checkout requires the PHP cURL extension.');
        }

        $payload = [
            'mode' => 'subscription',
            'line_items[0][price]' => $priceId,
            'line_items[0][quantity]' => '1',
            'client_reference_id' => (string) $userId,
            'metadata[user_id]' => (string) $userId,
            'subscription_data[metadata][user_id]' => (string) $userId,
            'success_url' => $appUrl . '/payment_success.php?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $appUrl . '/subscribe.php',
        ];
        $response = $this->stripeRequest('POST', 'checkout/sessions', $secretKey, $payload);

        if (empty($response['id']) || empty($response['url'])) {
            throw new RuntimeException('Stripe did not return a checkout session.');
        }

        return ['session_id' => $response['id'], 'redirect_url' => $response['url']];
    }

    /** Returns verified Stripe identifiers for a completed checkout session. */
    public function verifyPayment(string $sessionId, int $userId): ?array
    {
        $secretKey = getenv('STRIPE_SECRET_KEY') ?: '';
        if ($secretKey === '' || !preg_match('/^cs_[A-Za-z0-9_]+$/', $sessionId)) {
            return null;
        }

        $session = $this->stripeRequest('GET', 'checkout/sessions/' . rawurlencode($sessionId), $secretKey);
        $metadataUserId = (int) ($session['metadata']['user_id'] ?? 0);
        $paymentStatus = $session['payment_status'] ?? '';
        if (($session['status'] ?? '') !== 'complete'
            || !in_array($paymentStatus, ['paid', 'no_payment_required'], true)
            || $metadataUserId !== $userId) {
            return null;
        }

        $customerId = is_string($session['customer'] ?? null) ? $session['customer'] : null;
        $subscriptionId = is_string($session['subscription'] ?? null) ? $session['subscription'] : null;
        return $subscriptionId === null ? null : compact('customerId', 'subscriptionId');
    }

    private function stripeRequest(string $method, string $path, string $secretKey, array $payload = []): array
    {
        $curl = curl_init('https://api.stripe.com/v1/' . $path);
        if ($curl === false) {
            throw new RuntimeException('Unable to initialize Stripe checkout.');
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secretKey],
        ];
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($payload);
        }
        curl_setopt_array($curl, $options);
        $body = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        $decoded = is_string($body) ? json_decode($body, true) : null;
        if ($statusCode < 200 || $statusCode >= 300 || !is_array($decoded)) {
            throw new RuntimeException('Stripe could not process the checkout request.');
        }
        return $decoded;
    }
}
