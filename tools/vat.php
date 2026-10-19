<?php
require_once __DIR__ . '/../includes/session.php';
$pageTitle = "VAT Calculator Morocco | NajmiNet Innovations";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"><meta name="description" content="Free Moroccan VAT (TVA) calculator: add or remove VAT at 20%, 14%, 10% or 7%."><link rel="icon" href="/favicon.svg" type="image/svg+xml">
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
    <h1 class="mt-3 mb-3 reveal">VAT Calculator Morocco</h1>
    <p class="section-sub mx-auto reveal">Instantly calculate TVA amounts using official Moroccan VAT rates.</p>
  </div>
</header>

<section class="section pt-0">
  <div class="container">
    <div class="row g-4 justify-content-center">

      <!-- Inputs -->
      <div class="col-lg-6 reveal">
        <div class="glass glass-pad">
          <h2 class="h5 mb-4">Enter your amount</h2>

          <div class="mb-3">
            <label for="direction" class="form-label small" style="color:var(--text-h); font-weight:600;">Calculation direction</label>
            <select id="direction" class="form-control form-control-glass">
              <option value="ht-to-ttc">I have the amount excl. VAT (HT) → find TTC</option>
              <option value="ttc-to-ht">I have the amount incl. VAT (TTC) → find HT</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="amount" class="form-label small" style="color:var(--text-h); font-weight:600;">Amount (MAD)</label>
            <input type="number" inputmode="decimal" id="amount" class="form-control form-control-glass" placeholder="e.g. 1000" min="0" step="0.01" value="1000">
          </div>

          <div class="mb-2">
            <label for="rate" class="form-label small" style="color:var(--text-h); font-weight:600;">VAT Rate</label>
            <select id="rate" class="form-control form-control-glass">
              <option value="20">20% — Standard rate</option>
              <option value="14">14% — Reduced rate (transport, certain foods, energy)</option>
              <option value="10">10% — Reduced rate (hotels, restaurants, banking)</option>
              <option value="7">7% — Reduced rate (water, medicines, school supplies)</option>
            </select>
          </div>
          <p class="small mb-4" style="opacity:.7;">Rates are indicative — always confirm with the current General Tax Code (CGI) for your specific activity.</p>
          <button type="button" id="calculateBtn" class="nn-btn nn-btn--primary nn-btn--lg nn-btn--block">Calculate</button>
        </div>
      </div>

      <!-- Results -->
      <div class="col-lg-5 reveal">
        <div class="glass glass-pad">
          <h2 class="h5 mb-4">Result</h2>
          <div class="d-flex justify-content-between align-items-center py-3" style="border-bottom:1px solid var(--border);">
            <span class="small" style="color:var(--text-body);">Amount excl. VAT (HT)</span>
            <span class="mono h5 mb-0" id="resHT" style="color:var(--text-h);">0.00 MAD</span>
          </div>
          <div class="d-flex justify-content-between align-items-center py-3" style="border-bottom:1px solid var(--border);">
            <span class="small" style="color:var(--text-body);">VAT amount</span>
            <span class="mono h5 mb-0" id="resVAT" style="color:var(--blue);">0.00 MAD</span>
          </div>
          <div class="d-flex justify-content-between align-items-center py-3">
            <span class="fw-600" style="color:var(--text-h);">Total incl. VAT (TTC)</span>
            <span class="mono" id="resTTC" style="color:var(--emerald); font-size:1.6rem; font-weight:700;">0.00 MAD</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<?php include($_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'); ?>


</body>
</html>
