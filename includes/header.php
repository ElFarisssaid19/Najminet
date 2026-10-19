


<?php
require_once __DIR__ . '/session.php';
$headerIsAuthenticated = isset($_SESSION['user_id']);
$headerHasSubscription = $hasActiveSubscription ?? false;
if ($headerIsAuthenticated && empty($_SESSION['logout_csrf'])) {
  $_SESSION['logout_csrf'] = bin2hex(random_bytes(32));
}
?>
<!-- ============ NAVBAR ============ -->
<!-- Below 1024px the links move into the #mobileNav offcanvas (Bootstrap),
     opened by the menu button; see the nav-* classes in assets/css/style.css. -->
<nav class="navbar-glass" aria-label="Main">
  <div class="container d-flex align-items-center justify-content-between gap-2">
    <a href="/index.php" class="brand d-flex align-items-center gap-2">
      NajmiNet
    </a>

    <ul class="nav-desktop align-items-center gap-2 list-unstyled mb-0">
      <li><a href="/index.php#tools" class="nav-pill">Free tools</a></li>
      <li><a href="/index.php#pricing" class="nav-pill">Pricing</a></li>
      <li><a href="/index.php#features" class="nav-pill">Features</a></li>
      <li><a href="/index.php#faq" class="nav-pill">FAQ</a></li>
    </ul>

  <div class="d-flex align-items-center gap-2">
    <?php if ($headerIsAuthenticated): ?>
    <a href="<?= $headerHasSubscription ? '/dashboard/dashboard.php' : '/index.php#pricing' ?>" class="nn-btn nn-btn--primary nn-btn--sm nav-desktop-inline"><?= $headerHasSubscription ? 'Dashboard' : 'View plans' ?></a>
    <form action="/logout.php" method="post" class="nav-desktop-block logout-form">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['logout_csrf']) ?>">
      <button type="submit" class="nn-btn nn-btn--secondary nn-btn--sm"><span class="nn-icon nn-icon--log-out" aria-hidden="true"></span>Log out</button>
    </form>
    <?php else: ?>
      <a href="/auth/login.php" class="nn-btn nn-btn--ghost nn-btn--sm nav-desktop-inline">Log in</a>
      <a href="/auth/register.php" class="nn-btn nn-btn--primary nn-btn--sm nav-desktop-inline">Sign up</a>
    <?php endif; ?>

    <button type="button" class="nn-btn nn-btn--secondary nn-btn--icon navbar-toggler-custom nav-mobile-only"
            data-bs-toggle="offcanvas"
            data-bs-target="#mobileNav"
            aria-controls="mobileNav"
            aria-expanded="false"
            aria-label="Open menu">
        <span class="nn-icon nn-icon--menu" aria-hidden="true"></span>
    </button>

  </div>
  </div>
</nav>

<div class="offcanvas offcanvas-end nav-mobile-only offcanvas-custom" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavTitle">
  <div class="offcanvas-header">
    <span class="brand" id="mobileNavTitle">NajmiNet</span>
    <button type="button" class="nn-btn nn-btn--ghost nn-btn--icon" data-bs-dismiss="offcanvas" aria-label="Close menu"><span class="nn-icon nn-icon--close" aria-hidden="true"></span></button>
  </div>
  <div class="offcanvas-body">
    <ul class="list-unstyled d-flex flex-column gap-2">
      <li><a href="/index.php#tools" class="nav-pill">Free tools</a></li>
      <li><a href="/index.php#pricing" class="nav-pill">Pricing</a></li>
      <li><a href="/index.php#features" class="nav-pill">Features</a></li>
      <li><a href="/index.php#faq" class="nav-pill">FAQ</a></li>
      <?php if ($headerIsAuthenticated): ?>
      <li><a href="<?= $headerHasSubscription ? '/dashboard/dashboard.php' : '/index.php#pricing' ?>" class="nav-pill"><?= $headerHasSubscription ? 'Dashboard' : 'View plans' ?></a></li>
      <li><span class="nav-pill nav-user">👋 <?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></span></li>
      <li><form action="/logout.php" method="post" class="logout-form"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['logout_csrf']) ?>"><button type="submit" class="nn-btn nn-btn--secondary nn-btn--block"><span class="nn-icon nn-icon--log-out" aria-hidden="true"></span>Log out</button></form></li>
      <?php else: ?>
      <li><a href="/auth/login.php" class="nav-pill">Log in</a></li>
      <li><a href="/auth/register.php" class="nav-pill">Sign up</a></li>
      <?php endif; ?>
    </ul>
  </div>
</div>
