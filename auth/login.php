<?php
session_start();
require $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/SubscriptionManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/RateLimiter.php';

// Failed sign-ins allowed per window: per account (guessing one password)
// and per IP (trying many accounts). Only failures count.
const LOGIN_MAX_FAILURES_PER_EMAIL = 5;
const LOGIN_MAX_FAILURES_PER_IP = 20;
const LOGIN_WINDOW_SECONDS = 900;

$pageTitle = "Login | NajmiNet Innovations";
$errors = [];

// Surface any Google OAuth error (see /auth/oauth/google.php) using the existing error box
if (isset($_GET['error'])) {
    $oauthErrors = [
        'invalid_state' => 'Google sign-in session expired. Please try again.',
        'oauth_failed'  => 'Google sign-in failed. Please try again.',
        'no_email'      => 'Your Google account has no email address we can use.',
        'email_unverified' => 'Your Google email address is not verified. Verify it with Google first, or sign up with email and a password.',
    ];
    $errors[] = $oauthErrors[$_GET['error']] ?? 'Google sign-in could not be completed.';
}

// If already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!AuthSecurity::validCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session has expired. Please try again.';
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    }

    $limiter = new RateLimiter($pdo);
    $ipAddress = RateLimiter::clientIp();
    $emailKey = AuthSecurity::normaliseEmail($email);
    if (empty($errors) && (
        $limiter->tooManyAttempts('login_email', $emailKey, LOGIN_MAX_FAILURES_PER_EMAIL, LOGIN_WINDOW_SECONDS)
        || $limiter->tooManyAttempts('login_ip', $ipAddress, LOGIN_MAX_FAILURES_PER_IP, LOGIN_WINDOW_SECONDS)
    )) {
        http_response_code(429);
        $errors[] = 'Too many failed sign-in attempts. Please wait 15 minutes and try again, or reset your password.';
    }

    if (empty($errors)) {

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {

            $limiter->recordAttempt('login_email', $emailKey);
            $limiter->recordAttempt('login_ip', $ipAddress);
            $errors[] = 'Invalid email or password.';

        } else {

            $limiter->clearAttempts('login_email', $emailKey);
            AuthSecurity::establishSession($pdo, $user);
            if (isset($_POST['remember'])) {
                AuthSecurity::createRememberToken($pdo, (int) $user['id']);
            }

            header('Location: /index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"><meta name="description" content="Sign in to your NajmiNet account to open your accounting dashboard."><link rel="icon" href="/favicon.svg" type="image/svg+xml">
<title><?php echo $pageTitle; ?></title>

<link rel="stylesheet" href="/assets/fonts/fonts-4.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/fonts/fonts-4.css') ?>"><link rel="preload" as="font" type="font/woff2" href="/assets/fonts/sora-latin-c7aee659.woff2" crossorigin><link rel="preload" as="font" type="font/woff2" href="/assets/fonts/space-grotesk-latin-4ecc7e89.woff2" crossorigin>
<link rel="stylesheet" href="/assets/vendor/fontawesome-6.5.1/fa.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/vendor/fontawesome-6.5.1/fa.css') ?>">
<link href="/assets/vendor/bootstrap-5.3.2/bootstrap.min.css" rel="stylesheet">
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
  <?php $backHref = '/landing.php'; $backAriaLabel = 'Back to home'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/back-button.php'; ?>
</header>

<main class="auth-shell auth-container">
  <div class="auth-card">

    <!-- Login form -->
    <div class="auth-form-side">
      <span class="auth-eyebrow">Welcome back</span>
      <h1 class="auth-title">Sign in to your account</h1>
      <p class="auth-subtitle">Access your dashboard and manage your business.</p>

      <?php if (!empty($errors)): ?>
        <div class="error-box" role="alert">
          <?php foreach ($errors as $error): ?>
            <div>⚠️ <?php echo htmlspecialchars($error); ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="social-row">
        <a href="/auth/oauth/google.php" class="nn-btn nn-btn--secondary nn-btn--lg nn-btn--block">
          <svg width="19" height="19" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="width:19px;height:19px;flex-shrink:0;">
            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
            <path fill="#FBBC05" d="M10.53 28.59A14.5 14.5 0 019.5 24c0-1.61.28-3.14.78-4.59l-7.98-6.19A23.96 23.96 0 000 24c0 3.87.92 7.52 2.56 10.78l7.97-6.19z"/>
            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.97 6.19C6.51 42.62 14.62 48 24 48z"/>
          </svg>
          Sign in with Google
        </a>
      </div>

      <div class="divider">or continue with email</div>

      <form method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(AuthSecurity::csrfToken()) ?>">
        <div class="form-field">
          <label for="email">Email</label>
          <div class="input-wrap">
            <i class="fa-solid fa-envelope leading" aria-hidden="true"></i>
            <input type="email" id="email" name="email" class="form-control-glass" placeholder="you@company.com" required autocomplete="username" autocapitalize="none" spellcheck="false" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
          </div>
        </div>

        <div class="form-field">
          <label for="password">Password</label>
          <div class="input-wrap">
            <i class="fa-solid fa-lock leading" aria-hidden="true"></i>
            <input type="password" id="password" name="password" class="form-control-glass" placeholder="Enter your password" required autocomplete="current-password">
            <button type="button" class="nn-btn nn-btn--ghost nn-btn--icon toggle-pass" data-toggle-password="password" aria-label="Show password" aria-pressed="false"><span class="nn-icon nn-icon--eye" aria-hidden="true"></span></button>
          </div>
        </div>

        <div class="row-between">
          <label class="checkbox-line">
            <input type="checkbox" name="remember"> Remember me
          </label>
          <a href="/auth/forgot-password.php">Forgot password?</a>
        </div>

        <button type="submit" class="nn-btn nn-btn--primary nn-btn--lg nn-btn--block">
          <span class="nn-icon nn-icon--log-in" aria-hidden="true"></span>Log in
        </button>
      </form>

      <p class="switch-line">Don't have an account? <a href="/auth/register.php">Sign up</a></p>
    </div>

    <!-- Decorative welcome panel -->
    <div class="auth-decor-side on-brand">
      <div class="auth-decor-inner">
        <span class="decor-badge"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> Complete accounting platform</span>
        <h2 class="decor-title">New here?</h2>
        <p class="decor-text">
          Join NajmiNet Innovations and manage invoicing, VAT/TVA, CNSS payroll, and financial reports
          all in one modern, secure platform built for Moroccan businesses.
        </p>
        <ul class="decor-list">
          <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Smart invoicing &amp; VAT management</li>
          <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> CNSS payroll made simple</li>
          <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Secure, reliable, 24/7 support</li>
        </ul>
        <a href="/auth/register.php" class="nn-btn nn-btn--primary">
          <span class="nn-icon nn-icon--user-plus" aria-hidden="true"></span>Sign up
        </a>
      </div>
    </div>

  </div>
</main>

<script src="/assets/js/buttons.js?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/js/buttons.js') ?>"></script>

</body>
</html>
