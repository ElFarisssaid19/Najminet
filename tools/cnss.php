<?php
require_once __DIR__ . '/../includes/session.php';
$pageTitle = "CNSS Payroll Calculator | NajmiNet Innovations";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"><meta name="description" content="Free CNSS payroll contribution calculator for Morocco."><link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <title><?php echo $pageTitle; ?></title>
  <script>document.documentElement.classList.add('js');</script>

  <link rel="stylesheet" href="/assets/fonts/fonts-2.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/fonts/fonts-2.css') ?>"><link rel="preload" as="font" type="font/woff2" href="/assets/fonts/inter-latin-6ab57b19.woff2" crossorigin><link rel="preload" as="font" type="font/woff2" href="/assets/fonts/sora-latin-46cba0e3.woff2" crossorigin>
  <link href="/assets/vendor/bootstrap-5.3.2/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/form-controls.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/form-controls.css') ?>">
<link rel="stylesheet" href="/assets/css/responsive.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/responsive.css') ?>">
<link href="/assets/css/style.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/style.css') ?>" rel="stylesheet"><link rel="stylesheet" href="/assets/css/buttons.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/buttons.css') ?>">
</head>
<body>

<?php include($_SERVER['DOCUMENT_ROOT'].'/includes/header.php'); ?>
<div class="container back-btn-row"><?php $backHref = '/index.php#tools'; $backLabel = 'Back to tools'; $backAriaLabel = 'Back to tools'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/back-button.php'; ?></div>

<header class="hero" style="padding:80px 0 40px;">
  <div class="hero-overlay"></div>
  <div class="container hero-content text-center">
    <span class="glass-pill eyebrow reveal">Free Tool</span>
    <h1 class="mt-3 mb-3 reveal">CNSS Payroll Calculator</h1>
    <p class="section-sub mx-auto reveal">Estimate employee &amp; employer CNSS + AMO contributions on a gross monthly salary.</p>
  </div>
</header>

<section class="section pt-0">
  <div class="container">
    <div class="row g-4 justify-content-center">

      <!-- Inputs -->
      <div class="col-lg-6 reveal">
        <div class="glass glass-pad">
          <h2 class="h5 mb-4">Employee salary</h2>
          <div class="mb-3">
            <label for="salary" class="form-label small" style="color:var(--text-h); font-weight:600;">Gross monthly salary (MAD)</label>
            <input type="number" inputmode="decimal" id="salary" class="form-control form-control-glass" placeholder="e.g. 8000" min="0" step="0.01" value="8000">
          </div>
          <p class="small mb-4" style="opacity:.7;">Estimates use commonly published CNSS/AMO rates and the standard CNSS ceiling (plafond) of 6,000 MAD/month. Always verify current official rates with your accountant or CNSS before official declarations.</p>
          <button type="button" id="calculateCnssBtn" class="nn-btn nn-btn--primary nn-btn--lg nn-btn--block">Calculate</button>
        </div>
      </div>

      <!-- Results -->
      <div class="col-lg-5 reveal">
        <div class="glass glass-pad-tight">
          <h2 class="h5 mb-3">Employee deductions</h2>
          <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--border);">
            <span class="small" style="color:var(--text-body);">CNSS (4.48%, capped)</span>
            <span class="mono" id="empCnss" style="color:var(--text-h);">0.00 MAD</span>
          </div>
          <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--border);">
            <span class="small" style="color:var(--text-body);">AMO (2.26%)</span>
            <span class="mono" id="empAmo" style="color:var(--text-h);">0.00 MAD</span>
          </div>
          <div class="d-flex justify-content-between py-2 mb-3" style="border-bottom:1px solid var(--border);">
            <span class="fw-600" style="color:var(--text-h);">Net salary (approx.)</span>
            <span class="mono" id="netSalary" style="color:var(--emerald); font-size:1.3rem; font-weight:700;">0.00 MAD</span>
          </div>

          <h2 class="h5 mb-3 mt-4">Employer contributions</h2>
          <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--border);">
            <span class="small" style="color:var(--text-body);">CNSS (8.98%, capped)</span>
            <span class="mono" id="erCnss" style="color:var(--text-h);">0.00 MAD</span>
          </div>
          <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--border);">
            <span class="small" style="color:var(--text-body);">Allocations familiales (6.4%)</span>
            <span class="mono" id="erAf" style="color:var(--text-h);">0.00 MAD</span>
          </div>
          <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--border);">
            <span class="small" style="color:var(--text-body);">AMO (4.11%)</span>
            <span class="mono" id="erAmo" style="color:var(--text-h);">0.00 MAD</span>
          </div>
          <div class="d-flex justify-content-between py-2">
            <span class="fw-600" style="color:var(--text-h);">Total employer cost</span>
            <span class="mono" id="totalCost" style="color:var(--blue); font-size:1.3rem; font-weight:700;">0.00 MAD</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<?php include($_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'); ?>


</body>
</html>
