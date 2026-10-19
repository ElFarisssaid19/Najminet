<?php
require_once __DIR__ . '/includes/session.php';
$pageTitle = "About Us | NajmiNet Innovations";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"><meta name="description" content="About NajmiNet Innovations: who we are and why we build accounting tools for Moroccan businesses."><link rel="icon" href="/favicon.svg" type="image/svg+xml">
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
    <span class="glass-pill eyebrow reveal">Our Story</span>
    <h1 class="mt-3 mb-3 reveal">About NajmiNet Innovations</h1>
    <p class="section-sub mx-auto reveal">Building the accounting tools Moroccan businesses actually need.</p>
  </div>
</header>

<section class="section pt-0">
  <div class="container">
    <div class="row g-4 align-items-center mb-5">
      <div class="col-lg-7 reveal">
        <div class="glass glass-pad-lg">
          <h2 class="h3 mb-3">Why we built NajmiNet</h2>
          <p class="mb-3">NajmiNet Innovations was created to simplify the day-to-day financial administration that every Moroccan business has to deal with — invoicing, VAT/TVA declarations, CNSS payroll contributions, and CGNC-format balance sheets.</p>
          <p class="mb-0">Instead of juggling spreadsheets, paperwork, and multiple disconnected tools, we bring everything into a single, modern platform — so business owners and accountants can spend less time on admin and more time growing their business.</p>
        </div>
      </div>
      <div class="col-lg-5 reveal">
        <div class="glass ticket-card">
          <div class="icon-badge">🎯</div>
          <h3 class="h5 mb-2">Our Mission</h3>
          <p class="small mb-0">Make professional-grade accounting accessible to every business in Morocco, regardless of size.</p>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-md-4 reveal">
        <div class="glass ticket-card">
          <div class="icon-badge">🇲🇦</div>
          <h3 class="h5 mb-2">Built for Morocco</h3>
          <p class="small mb-0">CNSS payroll, TVA rates, and CGNC balance sheet formats — fully compliant with local regulations.</p>
        </div>
      </div>
      <div class="col-md-4 reveal">
        <div class="glass ticket-card">
          <div class="icon-badge">⚡</div>
          <h3 class="h5 mb-2">Fast & Simple</h3>
          <p class="small mb-0">No accounting degree required. Generate invoices and reports in minutes, not hours.</p>
        </div>
      </div>
      <div class="col-md-4 reveal">
        <div class="glass ticket-card">
          <div class="icon-badge">🔒</div>
          <h3 class="h5 mb-2">Secure by Design</h3>
          <p class="small mb-0">Your financial data stays protected with modern security practices.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include('includes/footer.php'); ?>
