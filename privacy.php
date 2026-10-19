<?php
require_once __DIR__ . '/includes/session.php';
$pageTitle = "Privacy Policy | NajmiNet Innovations";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"><meta name="description" content="NajmiNet privacy policy: what data we collect, why, and how we protect it."><link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <title><?php echo $pageTitle; ?></title>
  <script>document.documentElement.classList.add('js');</script>

  <link rel="stylesheet" href="/assets/fonts/fonts-2.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/fonts/fonts-2.css') ?>"><link rel="preload" as="font" type="font/woff2" href="/assets/fonts/inter-latin-6ab57b19.woff2" crossorigin><link rel="preload" as="font" type="font/woff2" href="/assets/fonts/sora-latin-46cba0e3.woff2" crossorigin>
  <link href="/assets/vendor/bootstrap-5.3.2/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/form-controls.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/form-controls.css') ?>">
<link rel="stylesheet" href="/assets/css/responsive.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/responsive.css') ?>">
<link href="/assets/css/style.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/style.css') ?>" rel="stylesheet"><link rel="stylesheet" href="/assets/css/buttons.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/buttons.css') ?>">
</head>
<body>

<?php include('includes/header.php'); ?>
<div class="container back-btn-row"><?php $backHref = '/landing.php'; $backLabel = 'Back'; $backAriaLabel = 'Back to home'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/back-button.php'; ?></div>

<header class="hero" style="padding:80px 0 40px;">
  <div class="hero-overlay"></div>
  <div class="container hero-content text-center">
    <span class="glass-pill eyebrow reveal">Legal</span>
    <h1 class="mt-3 mb-3 reveal">Privacy Policy</h1>
    <p class="section-sub mx-auto reveal">Last updated: July 2026</p>
  </div>
</header>

<section class="section pt-0">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-9 reveal">
        <div class="glass glass-pad-lg">

          <h2 class="h5 mb-2">1. Information We Collect</h2>
          <p class="mb-4">We collect information you provide directly (name, email, company details, invoicing/payroll data you enter) and technical information (IP address, browser type) needed to operate the Service.</p>

          <h2 class="h5 mb-2">2. How We Use Your Information</h2>
          <p class="mb-4">Your data is used to provide and improve the Service, process transactions, send important account notifications, and respond to support requests.</p>

          <h2 class="h5 mb-2">3. Data Storage & Security</h2>
          <p class="mb-4">We apply reasonable technical and organizational measures to protect your data against unauthorized access, alteration, or loss.</p>

          <h2 class="h5 mb-2">4. Data Sharing</h2>
          <p class="mb-4">We do not sell your personal or financial data. Information may be shared with service providers strictly necessary to operate the platform (e.g. hosting), under confidentiality obligations.</p>

          <h2 class="h5 mb-2">5. Your Rights</h2>
          <p class="mb-4">You may request access to, correction of, or deletion of your personal data at any time by contacting us.</p>

          <h2 class="h5 mb-2">6. Cookies</h2>
          <p class="mb-4">We use essential cookies/local storage to keep you logged in and remember application preferences.</p>

          <h2 class="h5 mb-2">7. Contact</h2>
          <p class="mb-0">For any privacy-related questions, reach out via our <a href="contact.php" style="color:var(--blue); font-weight:600;">Contact page</a>.</p>

        </div>
      </div>
    </div>
  </div>
</section>

<?php include('includes/footer.php'); ?>
