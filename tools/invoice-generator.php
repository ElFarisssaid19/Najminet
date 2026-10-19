<?php
require_once __DIR__ . '/../includes/session.php';
$pageTitle = "Free Invoice Generator | NajmiNet Innovations";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"><meta name="description" content="Free invoice generator: create and print a professional invoice in minutes."><link rel="icon" href="/favicon.svg" type="image/svg+xml">
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
    <h1 class="mt-3 mb-3 reveal">📄 Free Invoice Generator</h1>
    <p class="section-sub mx-auto reveal">Create a professional invoice in seconds and print or save as PDF.</p>
  </div>
</header>

<section class="section pt-0">
  <div class="container">
    <div class="glass reveal glass-pad-lg" id="invoiceCard">

      <div class="row g-4 mb-4">
        <div class="col-md-6">
          <label for="fromName" class="form-label small fw-600" style="color:var(--text-h);">From (your company)</label>
          <input type="text" id="fromName" autocomplete="organization" class="form-control form-control-glass mb-2" placeholder="Company name" value="NajmiNet Innovations">
          <textarea id="fromAddr" class="form-control form-control-glass" rows="2" placeholder="Address, ICE, phone...">Chichaoua, Morocco</textarea>
        </div>
        <div class="col-md-6">
          <label for="toName" class="form-label small fw-600" style="color:var(--text-h);">Bill to (client)</label>
          <input type="text" id="toName" class="form-control form-control-glass mb-2" placeholder="Client name">
          <textarea id="toAddr" class="form-control form-control-glass" rows="2" placeholder="Client address..."></textarea>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <label for="invNumber" class="form-label small fw-600" style="color:var(--text-h);">Invoice #</label>
          <input type="text" id="invNumber" class="form-control form-control-glass" value="INV-0001">
        </div>
        <div class="col-md-4">
          <label for="invDate" class="form-label small fw-600" style="color:var(--text-h);">Date</label>
          <input type="date" id="invDate" class="form-control form-control-glass">
        </div>
        <div class="col-md-4">
          <label for="vatRate" class="form-label small fw-600" style="color:var(--text-h);">VAT rate</label>
          <select id="vatRate" class="form-control form-control-glass">
            <option value="20">20%</option>
            <option value="14">14%</option>
            <option value="10">10%</option>
            <option value="7">7%</option>
            <option value="0">0% (exempt)</option>
          </select>
        </div>
      </div>

      <table class="table invoice-table items-stack mb-2">
        <thead>
          <tr>
            <th style="width:45%;">Description</th>
            <th style="width:12%;">Qty</th>
            <th style="width:18%;">Unit Price (MAD)</th>
            <th style="width:18%;">Total</th>
            <th class="no-print" style="width:7%;"></th>
          </tr>
        </thead>
        <tbody id="itemsBody"></tbody>
      </table>

      <button type="button" id="addRow" class="nn-btn nn-btn--secondary no-print mb-4"><span class="nn-icon nn-icon--plus" aria-hidden="true"></span>Add line item</button>

      <div class="row justify-content-end">
        <div class="col-md-5">
          <div class="d-flex justify-content-between py-2">
            <span style="color:var(--text-body);">Subtotal (HT)</span>
            <span class="mono" id="subTotal">0.00 MAD</span>
          </div>
          <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--border);">
            <span style="color:var(--text-body);">VAT</span>
            <span class="mono" id="vatTotal">0.00 MAD</span>
          </div>
          <div class="d-flex justify-content-between py-2">
            <span class="fw-600" style="color:var(--text-h);">Total (TTC)</span>
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
