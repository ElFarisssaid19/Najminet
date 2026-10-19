<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/classes/SubscriptionManager.php';
require_once __DIR__ . '/classes/PayPalCheckout.php';
if (empty($_SESSION['user_id'])) { header('Location: /auth/login.php'); exit; }
if (($_GET['plan'] ?? '') !== 'professional') { header('Location: /index.php#pricing'); exit; }
$checkoutError = '';
// Stripe is disabled: payment is now handled by PayPal Checkout below.
/*
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $session = (new SubscriptionManager($pdo))->createCheckoutSession((int) $_SESSION['user_id']);
        $_SESSION['pending_checkout_session'] = $session['session_id'];
        header('Location: ' . $session['redirect_url']);
        exit;
    } catch (RuntimeException $exception) {
        $checkoutError = 'Checkout is temporarily unavailable. Please contact support or try again later.';
    }
}
*/
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"><meta name="description" content="Pay securely for your NajmiNet plan."><link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <title>Checkout | NajmiNet</title>
  <link rel="stylesheet" href="/assets/fonts/fonts-6.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/fonts/fonts-6.css') ?>"><link rel="preload" as="font" type="font/woff2" href="/assets/fonts/sora-latin-46cba0e3.woff2" crossorigin><link rel="preload" as="font" type="font/woff2" href="/assets/fonts/space-grotesk-latin-4ecc7e89.woff2" crossorigin>
  <link rel="stylesheet" href="/assets/css/form-controls.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/form-controls.css') ?>">
<link rel="stylesheet" href="/assets/css/responsive.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/responsive.css') ?>">
<link rel="stylesheet" href="/assets/css/app.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/app.css') ?>">
  <link rel="stylesheet" href="/assets/css/responsive.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/responsive.css') ?>">
<link rel="stylesheet" href="/assets/css/checkout.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/checkout.css') ?>">
<link rel="stylesheet" href="/assets/css/buttons.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/buttons.css') ?>">
</head>
<body class="subscription-page">
  <div class="background-fx" aria-hidden="true"><span class="blob blob-one"></span><span class="blob blob-two"></span><span class="blob blob-three"></span></div>

  <header class="subscription-nav">
    <a class="subscription-brand" href="/index.php" aria-label="NajmiNet homepage"><span>N</span> NajmiNet</a>
  </header>

  <div class="back-btn-row back-btn-row--checkout">
    <?php $backHref = '/index.php#pricing'; $backLabel = 'Back to plans'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/back-button.php'; ?>
  </div>

  <main class="subscription-shell">
    <section class="checkout-card glass-card animate-fade-up">
      <div class="glass-reflection" aria-hidden="true"></div>
      <span class="plan-badge"><i></i> Professional Plan</span>
      <h1>Complete your<br>subscription</h1>
      <p class="intro">Unlock the full NajmiNet workspace and keep your financial operations moving without limits.</p>

      <div class="checkout-content">
        <ul class="feature-list" aria-label="Professional Plan features">
          <li>Unlimited invoices</li><li>Unlimited payroll</li><li>Unlimited employees</li>
        </ul>
        <div class="price-box">
          <span>Your subscription</span>
          <strong>$10<small> / 30 days</small></strong>
          <p>One-time payment for 30 days of access. Nothing renews automatically.</p>
        </div>
      </div>

      <!-- Stripe is disabled: the POST form no longer triggers createCheckoutSession(). -->
      <div class="checkout-actions">
        <div class="checkout-action-buttons">
          <div id="paypal-button-container" style="flex:1;"></div>
        </div>
        <p id="paypal-error" role="alert" style="display:none;color:#f87171;margin-top:0.5rem;"></p>
      </div>
      <p class="secure-note">⌁ Secure checkout · Your payment is processed safely</p>
    </section>
  </main>
  <script src="https://www.paypal.com/sdk/js?client-id=<?= urlencode((string) getenv('PAYPAL_CLIENT_ID')) ?>&currency=<?= PayPalCheckout::PLAN_CURRENCY ?>&intent=capture"></script>
  <script>
  // The order is created and captured on the server (billing/); the price
  // never comes from this page. The CSRF token travels in a header.
  (function () {
    var csrfToken = <?= json_encode(AuthSecurity::csrfToken()) ?>;
    var errorBox = document.getElementById('paypal-error');

    function showError(message) {
      errorBox.textContent = message;
      errorBox.style.display = 'block';
    }

    function postJson(url, body) {
      return fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
        body: JSON.stringify(body || {})
      }).then(function (res) {
        return res.json().catch(function () { return {}; }).then(function (data) {
          return { ok: res.ok, data: data };
        });
      });
    }

    paypal.Buttons({
      style: { shape: 'pill', color: 'blue', layout: 'vertical', label: 'pay' },
      createOrder: function () {
        errorBox.style.display = 'none';
        return postJson('/billing/paypal-create-order.php').then(function (result) {
          if (!result.ok || !result.data.id) {
            throw new Error(result.data.error || 'Checkout is unavailable right now. You have not been charged.');
          }
          return result.data.id;
        });
      },
      onApprove: function (data) {
        return postJson('/billing/paypal-capture.php', { orderID: data.orderID }).then(function (result) {
          if (!result.data.success) {
            showError(result.data.message || 'The payment could not be confirmed.');
            return;
          }
          document.querySelector('.checkout-card').innerHTML =
            '<div class="payment-success-message" style="text-align:center; padding:3rem 1rem;">' +
            '<div style="font-size:3rem; line-height:1; margin-bottom:1rem;">✓</div>' +
            '<h2 style="margin-bottom:0.5rem;">Payment successful</h2>' +
            '<p class="intro">Your subscription is now active. Redirecting to your dashboard…</p></div>';
          setTimeout(function () { window.location.href = '/dashboard/dashboard.php'; }, 1800);
        });
      },
      onError: function (err) {
        console.error(err);
        showError(err && err.message && err.message.indexOf('You have not been charged') !== -1
          ? err.message
          : 'An error occurred with PayPal. Please try again.');
      }
    }).render('#paypal-button-container');
  })();
  </script>
  <script src="/assets/js/subscription-ui.js?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/js/subscription-ui.js') ?>"></script>
  <script src="/assets/js/buttons.js?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/js/buttons.js') ?>"></script>
</body>
</html>
