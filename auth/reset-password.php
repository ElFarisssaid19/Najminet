<?php
declare(strict_types=1);

session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/AuthSecurity.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/password_fields.php';

$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$errors = [];
$complete = false;

// The account this link belongs to, or null for an unknown, used or expired
// link. Checked on GET too, so a dead link never shows a form.
$stmt = $pdo->prepare('SELECT u.full_name, u.email FROM password_reset_tokens r JOIN users u ON u.id = r.user_id WHERE r.token_hash = ? AND r.expires_at > NOW() AND r.used_at IS NULL LIMIT 1');
$stmt->execute([hash('sha256', $token)]);
$resetUser = $stmt->fetch() ?: null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $resetUser !== null) {
    if (!AuthSecurity::validCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session has expired. Please try again.';
    }
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    $errors = array_merge($errors, PasswordPolicy::errors($password, $resetUser['full_name'], $resetUser['email']));
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (empty($errors)) {
        if (AuthSecurity::consumePasswordReset($pdo, $token, $password) === null) {
            $resetUser = null; // used or expired in the meantime
        } else {
            $complete = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="description" content="Choose a new NajmiNet password."><link rel="icon" href="/favicon.svg" type="image/svg+xml">
<title>Choose a new password | NajmiNet</title>
<link rel="stylesheet" href="/assets/fonts/fonts-6.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/fonts/fonts-6.css') ?>"><link rel="preload" as="font" type="font/woff2" href="/assets/fonts/sora-latin-46cba0e3.woff2" crossorigin><link rel="preload" as="font" type="font/woff2" href="/assets/fonts/space-grotesk-latin-4ecc7e89.woff2" crossorigin>
<link rel="stylesheet" href="/assets/vendor/fontawesome-6.5.1/fa.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/vendor/fontawesome-6.5.1/fa.css') ?>">
<link rel="stylesheet" href="<?= asset_url('/assets/css/responsive.css') ?>">
<link rel="stylesheet" href="<?= asset_url('/assets/css/form-controls.css') ?>">
<link rel="stylesheet" href="<?= asset_url('/assets/css/login.css') ?>">
<link rel="stylesheet" href="<?= asset_url('/assets/css/auth-forms.css') ?>">
<link rel="stylesheet" href="<?= asset_url('/assets/css/buttons.css') ?>">
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
      <h1 class="auth-title">Choose a new password</h1>
<?php if ($complete): ?>
      <p class="auth-subtitle">Your password has been changed and you have been signed out on every device. Log in with your new password.</p>
      <a class="nn-btn nn-btn--primary nn-btn--lg nn-btn--block" href="/auth/login.php"><span class="nn-icon nn-icon--log-in" aria-hidden="true"></span>Log in</a>
<?php elseif ($resetUser === null): ?>
      <div class="error-box" role="alert"><div>This reset link is invalid or has expired.</div></div>
      <a class="nn-btn nn-btn--secondary nn-btn--lg nn-btn--block" href="/auth/forgot-password.php"><span class="nn-icon nn-icon--send" aria-hidden="true"></span>Request a new link</a>
<?php else: ?>
<?php if ($errors): ?>
      <div class="error-box" role="alert"><?php foreach ($errors as $error): ?><div><?= htmlspecialchars($error) ?></div><?php endforeach; ?></div>
<?php endif; ?>
      <!-- novalidate: auth-forms.js shows specific messages instead of the browser's tooltips. -->
      <form method="post" novalidate data-password-form>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(AuthSecurity::csrfToken()) ?>">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <div class="form-field">
          <label for="password">New password</label>
          <div class="input-wrap">
            <i class="fa-solid fa-lock leading" aria-hidden="true"></i>
            <input id="password" type="password" name="password" class="form-control-glass" placeholder="Create a strong password" required autocomplete="new-password" data-password-input aria-describedby="password-rules">
            <button type="button" class="nn-btn nn-btn--ghost nn-btn--icon toggle-pass" data-toggle-password="password" aria-label="Show password" aria-pressed="false"><span class="nn-icon nn-icon--eye" aria-hidden="true"></span></button>
          </div>
          <p class="field-message" data-field-message="password" aria-live="polite" hidden></p>
          <?= password_checklist('password-rules') ?>
        </div>
        <div class="form-field">
          <label for="confirm_password">Confirm password</label>
          <div class="input-wrap">
            <i class="fa-solid fa-lock leading" aria-hidden="true"></i>
            <input id="confirm_password" type="password" name="confirm_password" class="form-control-glass" placeholder="Repeat your password" required autocomplete="new-password" data-password-confirm>
            <button type="button" class="nn-btn nn-btn--ghost nn-btn--icon toggle-pass" data-toggle-password="confirm_password" aria-label="Show password" aria-pressed="false"><span class="nn-icon nn-icon--eye" aria-hidden="true"></span></button>
          </div>
          <p class="field-message" data-field-message="confirm_password" aria-live="polite" hidden></p>
        </div>
        <button class="nn-btn nn-btn--primary nn-btn--lg nn-btn--block" type="submit"><span class="nn-icon nn-icon--key" aria-hidden="true"></span>Reset password</button>
      </form>
      <?= password_policy_config(PasswordPolicy::identityTokens($resetUser['full_name'], $resetUser['email'])) ?>
      <script src="<?= asset_url('/assets/js/auth-forms.js') ?>"></script>
<?php endif; ?>
    </div>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/password-reset-panel.php'; ?>
  </div>
</main>
<script src="<?= asset_url('/assets/js/buttons.js') ?>"></script>
</body>
</html>
