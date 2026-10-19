<?php
declare(strict_types=1);

session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/AuthSecurity.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/Mailer.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/RateLimiter.php';

// Reset requests allowed per window, per IP and per email address. Every
// request counts, whether or not the address has an account.
const RESET_MAX_PER_IP = 10;
const RESET_MAX_PER_EMAIL = 3;
const RESET_WINDOW_SECONDS = 900;

$sent = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && AuthSecurity::validCsrfToken($_POST['csrf_token'] ?? null)) {
    $email = trim((string) ($_POST['email'] ?? ''));
    $limiter = new RateLimiter($pdo);
    $allowed = $limiter->attempt('reset_ip', RateLimiter::clientIp(), RESET_MAX_PER_IP, RESET_WINDOW_SECONDS)
        && (!filter_var($email, FILTER_VALIDATE_EMAIL)
            || $limiter->attempt('reset_email', AuthSecurity::normaliseEmail($email), RESET_MAX_PER_EMAIL, RESET_WINDOW_SECONDS));
    if (!$allowed) {
        http_response_code(429);
        $error = 'Too many reset requests. Please wait 15 minutes and try again.';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $token = AuthSecurity::createPasswordReset($pdo, $email);
        if ($token !== null) {
            $baseUrl = rtrim((string) getenv('APP_URL'), '/');
            if ($baseUrl !== '') {
                $url = $baseUrl . '/auth/reset-password.php?token=' . urlencode($token);
                $textBody = "Use this link within 30 minutes to reset your password:\n\n" . $url;
                $htmlBody = '<p>Use this link within 30 minutes to reset your password:</p><p><a href="'
                    . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">Reset your password</a></p>';
                Mailer::send($email, 'Reset your NajmiNet password', $htmlBody, $textBody);
            } else {
                error_log('Password reset email not sent: APP_URL is not configured.');
            }
        }
    }
    $sent = $error === '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="description" content="Reset your NajmiNet password by email."><link rel="icon" href="/favicon.svg" type="image/svg+xml">
<title>Reset password | NajmiNet</title>
<link rel="stylesheet" href="/assets/fonts/fonts-6.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/fonts/fonts-6.css') ?>"><link rel="preload" as="font" type="font/woff2" href="/assets/fonts/sora-latin-46cba0e3.woff2" crossorigin><link rel="preload" as="font" type="font/woff2" href="/assets/fonts/space-grotesk-latin-4ecc7e89.woff2" crossorigin>
<link rel="stylesheet" href="/assets/vendor/fontawesome-6.5.1/fa.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/vendor/fontawesome-6.5.1/fa.css') ?>">
<link rel="stylesheet" href="/assets/css/responsive.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/responsive.css') ?>">
<link rel="stylesheet" href="/assets/css/form-controls.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/form-controls.css') ?>">
<link rel="stylesheet" href="/assets/css/login.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/login.css') ?>">
<link rel="stylesheet" href="/assets/css/buttons.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/buttons.css') ?>">
</head>
<body class="auth-page">

<div class="bg-fx" aria-hidden="true">
  <span class="bg-fx__blob bg-fx__blob--1"></span>
  <span class="bg-fx__blob bg-fx__blob--2"></span>
</div>

<header class="auth-topbar auth-container">
  <a href="/landing.php" class="brand">
    <span class="brand__icon" aria-hidden="true"><i class="fa-solid fa-n"></i></span>
    <span class="brand__text">NajmiNet</span>
  </a>
  <?php $backHref = '/auth/login.php'; $backLabel = 'Back to login'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/back-button.php'; ?>
</header>

<main class="auth-shell auth-container">
  <div class="auth-card">
    <div class="auth-form-side">
      <div class="auth-mark" aria-hidden="true"><i class="fa-solid fa-key"></i></div>
      <h1 class="auth-title">Reset your password</h1>
<?php if ($sent): ?>
      <p class="auth-subtitle">If that email is registered, a reset link has been sent. It works once and expires after 30 minutes.</p>
<?php else: ?>
<?php if ($error !== ''): ?>
      <div class="error-box" role="alert"><div><?= htmlspecialchars($error) ?></div></div>
<?php endif; ?>
      <p class="auth-subtitle">Enter your email address and we'll send you a link to reset your password.</p>
      <form method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(AuthSecurity::csrfToken()) ?>">
        <div class="form-field">
          <label for="email">Email address</label>
          <div class="input-wrap">
            <i class="fa-solid fa-envelope leading" aria-hidden="true"></i>
            <input id="email" type="email" name="email" class="form-control-glass" placeholder="you@example.com" required autocomplete="email" autocapitalize="none" spellcheck="false">
          </div>
        </div>
        <button class="nn-btn nn-btn--primary nn-btn--lg nn-btn--block" type="submit"><span class="nn-icon nn-icon--send" aria-hidden="true"></span>Send reset link</button>
      </form>
<?php endif; ?>
    </div>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/password-reset-panel.php'; ?>
  </div>
</main>
<script src="/assets/js/buttons.js?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/js/buttons.js') ?>"></script>
</body>
</html>
