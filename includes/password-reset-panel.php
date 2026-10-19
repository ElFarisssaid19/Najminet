<?php
/**
 * includes/password-reset-panel.php
 * --------------------------------------------------------------------
 * The explanation panel beside the forgot-password and reset-password
 * forms. Every line says what the code actually does; change them
 * together:
 *  - "works once, expires after 30 minutes": AuthSecurity::
 *    createPasswordReset() stores the link with INTERVAL 30 MINUTE, and
 *    consumePasswordReset() marks it used, then deletes all of that
 *    user's reset links (the email in auth/forgot-password.php also says
 *    30 minutes).
 *  - "a new link cancels any earlier one": createPasswordReset() deletes
 *    the user's earlier links before storing the new one.
 *  - "password rules": PasswordPolicy::errors(), checked by
 *    auth/reset-password.php before the password is changed.
 *  - "signs you out on every device": consumePasswordReset() deletes the
 *    user's "Remember me" tokens and increments user_auth_versions, which
 *    middleware/auth_guard.php and subscription_guard.php check on every
 *    protected page.
 * --------------------------------------------------------------------
 */
?>
<section class="auth-decor-side auth-decor-side--security" aria-labelledby="resetPanelTitle">
  <div class="auth-decor-inner">
    <p class="security-panel-label">Account security</p>
    <span class="security-panel-accent" aria-hidden="true"></span>
    <h2 id="resetPanelTitle">How password reset works</h2>
    <p class="security-panel-description">We email a reset link to the address on your NajmiNet account.</p>
    <ul class="security-panel-features">
      <li>The link works once and expires after 30 minutes.</li>
      <li>Asking for a new link cancels any earlier one.</li>
      <li>Your new password must meet our password rules.</li>
      <li>Resetting your password signs you out on every device, including “Remember me” sign-ins.</li>
    </ul>
  </div>
</section>
