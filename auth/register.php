<?php
session_start();
require $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/password_fields.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/RateLimiter.php';

// Sign-up attempts allowed per IP in the window (each submitted form counts).
const REGISTER_MAX_PER_IP = 10;
const REGISTER_WINDOW_SECONDS = 900;

$pageTitle = "Create Account | NajmiNet Innovations";
$errors = [];
$emailTaken = null; // 'password' or 'google' when the email already has an account

/**
 * "Already registered" message with its links, as safe HTML. Used for the
 * server's answer and for the templates the live email check shows.
 */
function email_taken_message(string $accountType): string
{
    return match ($accountType) {
        'both' => 'This email is already registered. Log in <a href="/auth/login.php">with your password</a> or <a href="/auth/oauth/google.php">with Google</a>, or <a href="/auth/forgot-password.php">reset your password</a>.',
        'google' => 'This email is linked to Google. <a href="/auth/oauth/google.php">Sign in with Google</a>.',
        default => 'This email is already registered. <a href="/auth/login.php">Log in</a> or <a href="/auth/forgot-password.php">reset your password</a>.',
    };
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $company  = trim((string) ($_POST['company_name'] ?? ''));
    $email    = AuthSecurity::normaliseEmail((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirm  = (string) ($_POST['confirm_password'] ?? '');
    $validCsrf = AuthSecurity::validCsrfToken($_POST['csrf_token'] ?? null);
    $limiter = new RateLimiter($pdo);
    $ipAddress = RateLimiter::clientIp();
    // Counted before anything else, so a throttled request learns nothing.
    $rateLimited = $validCsrf && !$limiter->attempt('register_ip', $ipAddress, REGISTER_MAX_PER_IP, REGISTER_WINDOW_SECONDS);

    if (!$validCsrf) $errors[] = 'Your session has expired. Please try again.';
    if ($rateLimited) {
        http_response_code(429);
        $errors[] = 'Too many sign-up attempts. Please wait 15 minutes and try again.';
    }
    if ($fullName === '') $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif ($validCsrf && !$rateLimited && ($accountType = AuthSecurity::existingAccountType($pdo, $email)) !== null) {
        // Same per-IP budget as the live check (auth/check-email.php), so
        // this form cannot be used to test addresses instead.
        if ($limiter->tooManyAttempts(AuthSecurity::EMAIL_LOOKUP_ACTION, $ipAddress, AuthSecurity::EMAIL_LOOKUP_MAX_ATTEMPTS, AuthSecurity::EMAIL_LOOKUP_WINDOW_SECONDS)) {
            $errors[] = 'Too many attempts. Please wait a few minutes and try again.';
        } else {
            $limiter->recordAttempt(AuthSecurity::EMAIL_LOOKUP_ACTION, $ipAddress);
            $emailTaken = $accountType;
        }
    }
    $errors = array_merge($errors, PasswordPolicy::errors($password, $fullName, $email));
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors) && $emailTaken === null) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "INSERT INTO users (full_name, company_name, email, password_hash, has_password)
             VALUES (?, ?, ?, ?, 1)"
        );
        try {
            $stmt->execute([$fullName, $company, $email, $hash]);
        } catch (PDOException $exception) {
            // Two sign-ups racing for the same address: the unique key on
            // users.email decides, and the loser gets the normal message.
            if ((int) ($exception->errorInfo[1] ?? 0) !== 1062) {
                throw $exception;
            }
            $emailTaken = AuthSecurity::existingAccountType($pdo, $email) ?? 'password';
        }

        if ($emailTaken === null) {
            AuthSecurity::establishSession($pdo, [
                'id' => (int) $pdo->lastInsertId(),
                'full_name' => $fullName,
                'email' => $email,
            ]);

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
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"><meta name="description" content="Create a NajmiNet account: invoices, VAT, payroll and reports for your business."><link rel="icon" href="/favicon.svg" type="image/svg+xml">
<title><?php echo $pageTitle; ?></title>

<link rel="stylesheet" href="/assets/fonts/fonts-4.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/fonts/fonts-4.css') ?>"><link rel="preload" as="font" type="font/woff2" href="/assets/fonts/sora-latin-c7aee659.woff2" crossorigin><link rel="preload" as="font" type="font/woff2" href="/assets/fonts/space-grotesk-latin-4ecc7e89.woff2" crossorigin>
<link rel="stylesheet" href="/assets/vendor/fontawesome-6.5.1/fa.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/vendor/fontawesome-6.5.1/fa.css') ?>">
<link href="/assets/vendor/bootstrap-5.3.2/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/responsive.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/responsive.css') ?>">
<link rel="stylesheet" href="/assets/css/form-controls.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/form-controls.css') ?>">
<link rel="stylesheet" href="/assets/css/login.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/login.css') ?>">
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
  <?php $backHref = '/landing.php'; $backAriaLabel = 'Back to home'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/back-button.php'; ?>
</header>

<main class="auth-shell auth-container">
  <div class="auth-card">

    <!-- Register form -->
    <div class="auth-form-side">
      <span class="auth-eyebrow">Get started</span>
      <h1 class="auth-title">Create your account</h1>
      <p class="auth-subtitle">Start managing your invoicing, VAT, and payroll in minutes.</p>

      <?php if (!empty($errors) || $emailTaken !== null): ?>
        <div class="error-box" role="alert">
          <?php if ($emailTaken !== null): ?>
            <div>⚠️ <?= email_taken_message($emailTaken) ?></div>
          <?php endif; ?>
          <?php foreach ($errors as $err): ?>
            <div>⚠️ <?php echo htmlspecialchars($err); ?></div>
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
          Sign up with Google
        </a>
      </div>

      <div class="divider">or continue with email</div>

      <!-- novalidate: auth-forms.js shows specific messages instead of the browser's tooltips. -->
      <form method="POST" action="/auth/register.php" id="registerForm" novalidate data-password-form>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(AuthSecurity::csrfToken()) ?>">
        <div class="form-field">
          <label for="full_name">Full name</label>
          <div class="input-wrap">
            <i class="fa-solid fa-user leading" aria-hidden="true"></i>
            <input type="text" id="full_name" name="full_name" class="form-control-glass" placeholder="Your full name" required autocomplete="name"
                   data-identity="name" data-required-message="Full name is required."
                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
          </div>
          <p class="field-message" data-field-message="full_name" aria-live="polite" hidden></p>
        </div>

        <div class="form-field">
          <label for="company_name">Company name (optional)</label>
          <div class="input-wrap">
            <i class="fa-solid fa-building leading" aria-hidden="true"></i>
            <input type="text" id="company_name" name="company_name" class="form-control-glass" placeholder="Your company" autocomplete="organization" value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>">
          </div>
        </div>

        <div class="form-field">
          <label for="email">Email</label>
          <div class="input-wrap">
            <i class="fa-solid fa-envelope leading" aria-hidden="true"></i>
            <input type="email" id="email" name="email" class="form-control-glass" placeholder="you@company.com" required autocomplete="email"
                   data-identity="email" data-required-message="Please enter a valid email address." data-email-message="Please enter a valid email address."
                   value="<?php echo htmlspecialchars($email ?? ''); ?>">
          </div>
          <p class="field-message" data-field-message="email" aria-live="polite" hidden></p>
        </div>

        <div class="form-field">
          <label for="password">Password</label>
          <div class="input-wrap">
            <i class="fa-solid fa-lock leading" aria-hidden="true"></i>
            <input type="password" id="password" name="password" class="form-control-glass" placeholder="Create a strong password" required autocomplete="new-password"
                   data-password-input aria-describedby="password-rules">
            <button type="button" class="nn-btn nn-btn--ghost nn-btn--icon toggle-pass" data-toggle-password="password" aria-label="Show password" aria-pressed="false"><span class="nn-icon nn-icon--eye" aria-hidden="true"></span></button>
          </div>
          <p class="field-message" data-field-message="password" aria-live="polite" hidden></p>
          <?= password_checklist('password-rules') ?>
        </div>

        <div class="form-field">
          <label for="confirm_password">Confirm password</label>
          <div class="input-wrap">
            <i class="fa-solid fa-lock leading" aria-hidden="true"></i>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control-glass" placeholder="Repeat your password" required autocomplete="new-password"
                   data-password-confirm>
            <button type="button" class="nn-btn nn-btn--ghost nn-btn--icon toggle-pass" data-toggle-password="confirm_password" aria-label="Show password" aria-pressed="false"><span class="nn-icon nn-icon--eye" aria-hidden="true"></span></button>
          </div>
          <p class="field-message" data-field-message="confirm_password" aria-live="polite" hidden></p>
        </div>

        <!-- Messages shown by the live email check (registre.js). -->
        <template id="emailTakenTemplates">
          <span data-status="registered"><?= email_taken_message('password') ?></span>
          <span data-status="google"><?= email_taken_message('google') ?></span>
          <span data-status="both"><?= email_taken_message('both') ?></span>
        </template>

        <button type="submit" class="nn-btn nn-btn--primary nn-btn--lg nn-btn--block">
          <span class="nn-icon nn-icon--user-plus" aria-hidden="true"></span>Create account
        </button>
      </form>

      <p class="switch-line">Already have an account? <a href="/auth/login.php">Log in</a></p>
    </div>

    <!-- Decorative welcome panel -->
    <div class="auth-decor-side on-brand">
      <div class="auth-decor-inner">
        <span class="decor-badge"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> Complete accounting platform</span>
        <h2 class="decor-title">Welcome to NajmiNet</h2>
        <p class="decor-text">
          Manage invoicing, VAT/TVA, CNSS payroll, and financial reports all in one modern,
          secure platform built for Moroccan businesses.
        </p>
        <ul class="decor-list">
          <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Smart invoicing &amp; VAT management</li>
          <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> CNSS payroll made simple</li>
          <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Secure and reliable</li>
        </ul>
        <a href="/auth/login.php" class="nn-btn nn-btn--primary">
          <span class="nn-icon nn-icon--log-in" aria-hidden="true"></span>Log in
        </a>
      </div>
    </div>

  </div>
</main>

<?= password_policy_config() ?>
<script src="<?= asset_url('/assets/js/auth-forms.js') ?>"></script>
<script src="<?= asset_url('/assets/js/registre.js') ?>"></script>
<script src="<?= asset_url('/assets/js/buttons.js') ?>"></script>
</body>
</html>
