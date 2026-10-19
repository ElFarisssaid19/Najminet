<?php
declare(strict_types=1);

/**
 * Subscription landing page.
 *
 * No payment processing occurs here. A future Stripe, PayPal, or Lemon
 * integration should update users.subscription_status to "active" after a
 * confirmed payment, which automatically unlocks all protected pages.
 */
session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/classes/SubscriptionManager.php';

if (empty($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

// Reuse the central access-status query used by the subscription middleware.
$subscriptionManager = new SubscriptionManager($pdo);
$currentSubscription = $subscriptionManager->getAccessStatus((int) $_SESSION['user_id']);
$subscriptionMessage = $_SESSION['subscription_message'] ?? '';
unset($_SESSION['subscription_message']);

$pageTitle = 'Subscribe';
$activePage = '';
require __DIR__ . '/includes/layout_head.php';
require __DIR__ . '/includes/sidebar.php';
?>

<main class="main">
  <div class="topbar">
    <button class="nn-btn nn-btn--primary nn-btn--icon menu-toggle" type="button" aria-controls="appSidebar" aria-expanded="false" aria-label="Open navigation"><span class="nn-icon nn-icon--menu" aria-hidden="true"></span></button>
  </div>

  <div class="back-btn-row"><?php $backHref = '/index.php#pricing'; $backLabel = 'Back to plans'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/back-button.php'; ?></div>

  <div class="page-header">
    <div>
      <h1>Unlock NajmiNet Premium</h1>
      <p>Manage your accounting work with the complete NajmiNet toolkit.</p>
    </div>
  </div>

  <?php if ($subscriptionMessage !== ''): ?>
    <div class="alert alert-error"><?= htmlspecialchars($subscriptionMessage) ?></div>
  <?php endif; ?>

  <section class="card" style="max-width:680px;">
    <div class="card-title"><span class="badge-ic">✦</span>Choose your plan</div>
    <p>Subscribe to regain full access to the NajmiNet dashboard.</p>

    <div class="field-row" style="margin:24px 0;">
      <?php if ((int) ($currentSubscription['trial_used'] ?? 1) === 0): ?>
        <div class="card" style="padding:18px;">
          <strong>14-Day Free Trial</strong>
          <div style="font-size:24px; margin-top:8px;">Free</div>
          <form method="post" action="/index.php" style="margin-top:16px;"><?= AuthSecurity::csrfField() ?>
            <input type="hidden" name="subscription_action" value="start_trial">
            <button class="nn-btn nn-btn--secondary" type="submit">Start free trial</button>
          </form>
        </div>
      <?php endif; ?>
      <div class="card" style="padding:18px;">
        <strong>Professional</strong>
        <div style="font-size:24px; margin-top:8px;">$10 <span style="font-size:13px; color:var(--text-secondary);">/ 30 days</span></div>
        <a class="nn-btn nn-btn--primary" style="margin-top:16px;" href="/checkout.php?plan=professional">Subscribe</a>
      </div>
    </div>
  </section>
</main>

</body>
</html>
