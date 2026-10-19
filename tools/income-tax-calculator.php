<?php
require_once __DIR__ . '/../includes/session.php';
$pageTitle = "Income Tax Calculator Morocco | NajmiNet Innovations";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"><meta name="description" content="Free Moroccan income tax (IR) calculator."><link rel="icon" href="/favicon.svg" type="image/svg+xml">
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
    <h1 class="mt-3 mb-3 reveal">💰 Income Tax Calculator Morocco</h1>
    <p class="section-sub mx-auto reveal">Estimate your income tax (IR) using the progressive tax brackets.</p>
  </div>
</header>

<section class="section pt-0">
  <div class="container">
    <div class="row g-4 justify-content-center">

      <!-- Inputs -->
      <div class="col-lg-6 reveal">
        <div class="glass glass-pad">
          <h2 class="h5 mb-4">Your income</h2>

          <div class="mb-3">
            <label for="period" class="form-label small" style="color:var(--text-h); font-weight:600;">Income period</label>
            <select id="period" class="form-control form-control-glass">
              <option value="annual">Annual net taxable income</option>
              <option value="monthly">Monthly net taxable salary</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="income" class="form-label small" style="color:var(--text-h); font-weight:600;">Amount (MAD)</label>
            <input type="number" inputmode="decimal" id="income" class="form-control form-control-glass" placeholder="e.g. 120000" min="0" step="0.01" value="120000">
          </div>

          <div class="mb-2">
            <label for="dependents" class="form-label small" style="color:var(--text-h); font-weight:600;">Family situation</label>
            <select id="dependents" class="form-control form-control-glass">
              <option value="0">No dependents</option>
              <option value="1">1 dependent (−360 MAD/year)</option>
              <option value="2">2 dependents (−720 MAD/year)</option>
              <option value="3">3 dependents (−1,080 MAD/year)</option>
              <option value="4">4 dependents (−1,440 MAD/year)</option>
              <option value="5">5 dependents (−1,800 MAD/year, max)</option>
            </select>
          </div>
          <div class="nn-actions nn-actions--fill mt-4">
            <button type="button" id="calculateIRBtn" class="nn-btn nn-btn--primary nn-btn--lg">Calculate</button>
            <button type="button" id="resetIRBtn" class="nn-btn nn-btn--secondary nn-btn--lg"><span class="nn-icon nn-icon--undo" aria-hidden="true"></span>Reset</button>
          </div>

          <p id="errorIR" class="text-danger small mt-3 d-none"></p>
          <p class="small mb-0" style="opacity:.7;">Based on commonly published IR brackets (0% / 10% / 20% / 30% / 34% / 38%) and standard family deduction (180 MAD/dependent/year, capped at 5). Always confirm current rates with the General Tax Code (CGI).</p>
        </div>
      </div>

      <!-- Results -->
      <div class="col-lg-5 reveal">
        <div class="glass glass-pad">
          <h2 class="h5 mb-4">Result (annual)</h2>
          <div class="d-flex justify-content-between py-3" style="border-bottom:1px solid var(--border);">
            <span class="small" style="color:var(--text-body);">Taxable income</span>
            <span class="mono" id="resIncome" style="color:var(--text-h);">0.00 MAD</span>
          </div>
          <div class="d-flex justify-content-between py-3" style="border-bottom:1px solid var(--border);">
            <span class="small" style="color:var(--text-body);">Marginal tax rate</span>
            <span class="mono" id="resRate" style="color:var(--blue);">0%</span>
          </div>
          <div class="d-flex justify-content-between py-3" style="border-bottom:1px solid var(--border);">
            <span class="small" style="color:var(--text-body);">Family deduction</span>
            <span class="mono" id="resDeduction" style="color:var(--text-h);">0.00 MAD</span>
          </div>
          <div class="d-flex justify-content-between py-3">
            <span class="fw-600" style="color:var(--text-h);">Annual IR due</span>
            <span class="mono" id="resIR" style="color:var(--emerald); font-size:1.6rem; font-weight:700;">0.00 MAD</span>
          </div>
          <p class="small mt-3 mb-0" id="resMonthly" style="color:var(--text-body);"></p>
        </div>
      </div>

    </div>
  </div>
</section>

<?php include($_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'); ?>

</body>
</html>
