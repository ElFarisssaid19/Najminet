<?php
require_once __DIR__ . '/includes/session.php';
$pageTitle = "Terms and Conditions | NajmiNet Innovations";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"><meta name="description" content="NajmiNet terms of service: plans, payments, acceptable use and your rights."><link rel="icon" href="/favicon.svg" type="image/svg+xml">
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
    <h1 class="mt-3 mb-3 reveal">Terms and Conditions</h1>
    <p class="section-sub mx-auto reveal">Last updated: July 2026</p>
  </div>
</header>

<section class="section pt-0">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-9 reveal">
        <div class="glass glass-pad-lg">

          <h2 class="h5 mb-2">1. Acceptance of Terms</h2>
          <p class="mb-4">By accessing or using NajmiNet Innovations ("the Service"), you agree to be bound by these Terms and Conditions. If you do not agree, please do not use the Service.</p>

          <h2 class="h5 mb-2">2. Description of Service</h2>
          <p class="mb-4">NajmiNet provides accounting and financial management tools, including invoicing, VAT/TVA management, payroll (CNSS) processing, and balance sheet (CGNC) generation for businesses.</p>

          <h2 class="h5 mb-2">3. Account Responsibilities</h2>
          <p class="mb-4">You are responsible for maintaining the confidentiality of your account credentials and for all activity that occurs under your account.</p>

          <h2 class="h5 mb-2">4. Subscription and Billing</h2>
          <p class="mb-4">The Professional plan is a one-time payment that gives access for 30 days from the date of payment. It does not renew automatically and you are not charged again; to keep access after the 30 days, you make a new payment at checkout.</p>

          <h2 class="h5 mb-2">5. Data Accuracy</h2>
          <p class="mb-4">While NajmiNet strives for accuracy in tax and payroll calculations, you remain responsible for verifying figures before official submission to relevant authorities.</p>

          <h2 class="h5 mb-2">6. Limitation of Liability</h2>
          <p class="mb-4">NajmiNet Innovations is provided "as is" without warranties of any kind. We are not liable for indirect, incidental, or consequential damages arising from use of the Service.</p>

          <h2 class="h5 mb-2">7. Changes to Terms</h2>
          <p class="mb-0">We may update these Terms from time to time. Continued use of the Service after changes constitutes acceptance of the revised Terms.</p>

        </div>
      </div>
    </div>
  </div>
</section>

<?php include('includes/footer.php'); ?>
