<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';

http_response_code(404);
$pageTitle = 'Page Not Found';
$isAuthenticated = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"><meta name="description" content="Page not found - NajmiNet."><link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <meta name="robots" content="noindex">
  <title><?= htmlspecialchars($pageTitle) ?> — NajmiNet</title>
  <script>document.documentElement.classList.add('js');</script>
  <link rel="stylesheet" href="/assets/fonts/fonts-2.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/fonts/fonts-2.css') ?>"><link rel="preload" as="font" type="font/woff2" href="/assets/fonts/inter-latin-6ab57b19.woff2" crossorigin><link rel="preload" as="font" type="font/woff2" href="/assets/fonts/sora-latin-46cba0e3.woff2" crossorigin>
  <link href="/assets/vendor/bootstrap-5.3.2/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/form-controls.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/form-controls.css') ?>">
<link rel="stylesheet" href="/assets/css/responsive.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/responsive.css') ?>">
<link href="/assets/css/style.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/style.css') ?>" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/responsive.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/responsive.css') ?>">
<link href="/assets/css/not-found.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/not-found.css') ?>" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/buttons.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/buttons.css') ?>">
</head>
<body>
<main class="not-found-page">
  <div class="back-btn-row back-btn-row--overlay"><?php $backHref = '/landing.php'; $backLabel = 'Back'; $backAriaLabel = 'Back to home'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/back-button.php'; ?></div>
  <div class="not-found-orb not-found-orb--one" aria-hidden="true"></div>
  <div class="not-found-orb not-found-orb--two" aria-hidden="true"></div>
  <section class="container not-found-content">
    <div class="glass not-found-card reveal">
      <div class="not-found-icon" aria-hidden="true">⌕</div>
      <p class="not-found-code">404</p>
      <span class="glass-pill not-found-kicker">Page Not Found</span>
      <h1>Oops! We could not find that page.</h1>
      <p class="not-found-message">The page you're looking for doesn't exist or may have been moved.</p>
      <p class="not-found-copy">The address may be incorrect, or the page may no longer be available. Let’s get you back on track.</p>
      <div class="not-found-actions nn-actions nn-actions--center">
        <a href="/landing.php" class="nn-btn nn-btn--primary nn-btn--lg"><span class="nn-icon nn-icon--home" aria-hidden="true"></span>Go to home</a>
        <?php if ($isAuthenticated): ?>
          <a href="/dashboard/dashboard.php" class="nn-btn nn-btn--secondary nn-btn--lg">Dashboard</a>
        <?php endif; ?>
      </div>
    </div>
  </section>
</main>
<script src="/assets/js/script.js?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/js/script.js') ?>"></script>
<script src="/assets/js/buttons.js?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/js/buttons.js') ?>"></script>
</body>
</html>
