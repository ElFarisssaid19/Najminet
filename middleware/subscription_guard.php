<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../classes/SubscriptionManager.php';

// Protected pages must only be reached by an authenticated user.
if (empty($_SESSION['user_id']) || !AuthSecurity::sessionIsValid($pdo)) {
    $_SESSION = [];
    header('Location: /auth/login.php');
    exit;
}

// Every form on a protected page posts the session's CSRF token
// (AuthSecurity::csrfField()); a POST without it is refused here, before
// the page's own handler runs.
AuthSecurity::requireValidCsrf();

// Expiry is persisted before checking access. The shared sidebar reuses this
// value when it is rendered by dashboard pages.
$subscriptionManager = new SubscriptionManager($pdo);
$currentSubscription = $subscriptionManager->getAccessStatus((int) $_SESSION['user_id']);

if (($currentSubscription['subscription_status'] ?? null) !== 'active') {
    $_SESSION['subscription_message'] = 'An active subscription is required to access this page.';
    header('Location: /subscribe.php');
    exit;
}
