<?php
require_once __DIR__ . '/../includes/session.php';
$pageTitle = "Expense Notes | NajmiNet Innovations";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"><meta name="description" content="Free expense note builder: list expenses, total them and print the report."><link rel="icon" href="/favicon.svg" type="image/svg+xml">
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
    <h1 class="mt-3 mb-3 reveal">📝 Expense Notes</h1>
    <p class="section-sub mx-auto reveal">Build a clean expense report and export or print it in seconds.</p>
  </div>
</header>

<section class="section pt-0">
  <div class="container">
    <div class="glass reveal glass-pad-lg">

      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <label for="employee" class="form-label small fw-600" style="color:var(--text-h);">Employee / Submitted by</label>
          <input type="text" id="employee" autocomplete="name" class="form-control form-control-glass" placeholder="Your name">
        </div>
        <div class="col-md-3">
          <label for="period" class="form-label small fw-600" style="color:var(--text-h);">Period</label>
          <input type="month" id="period" class="form-control form-control-glass">
        </div>
        <div class="col-md-3">
          <label for="currency" class="form-label small fw-600" style="color:var(--text-h);">Currency</label>
          <select id="currency" class="form-control form-control-glass">
            <option value="MAD">MAD</option>
            <option value="EUR">EUR</option>
            <option value="USD">USD</option>
          </select>
        </div>
      </div>

      <table class="table expense-table items-stack mb-2">
        <thead>
          <tr>
            <th style="width:16%;">Date</th>
            <th style="width:20%;">Category</th>
            <th style="width:34%;">Description</th>
            <th style="width:18%;">Amount</th>
            <th class="no-print" style="width:12%;"></th>
          </tr>
        </thead>
        <tbody id="itemsBody"></tbody>
      </table>

      <button type="button" id="addRow" class="nn-btn nn-btn--secondary no-print mb-4"><span class="nn-icon nn-icon--plus" aria-hidden="true"></span>Add expense</button>

      <div class="row justify-content-end">
        <div class="col-md-5">
          <div class="d-flex justify-content-between py-3">
            <span class="fw-600" style="color:var(--text-h);">Total expenses</span>
            <span class="mono" id="grandTotal" style="color:var(--emerald); font-size:1.4rem; font-weight:700;">0.00 MAD</span>
          </div>
        </div>
      </div>

      <div class="text-center mt-4 no-print">
        <button type="button" id="printBtn" class="nn-btn nn-btn--primary nn-btn--lg"><span class="nn-icon nn-icon--print" aria-hidden="true"></span>Print or save as PDF</button>
      </div>
    </div>
  </div>
</section>

<?php include($_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'); ?>



</body>
</html>
