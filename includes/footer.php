<?php
require_once __DIR__ . '/contact_whatsapp.php';
$footerWhatsapp = contact_whatsapp_url();
?>
<!-- ============ FOOTER ============ -->
<footer class="site-footer">
  <div class="container">
    <div class="row g-4 mb-4">
      <div class="col-md-4">
        <p class="brand mb-2">NajmiNet Innovations</p>
        <p class="small mb-0">© 2026 NajmiNet Innovations. All rights reserved.</p>
      </div>
      <div class="col-6 col-md-2">
        <h2 class="h6 footer-title">Tools</h2>
        <ul class="list-unstyled d-flex flex-column gap-2">
          <li><a href="/tools/vat.php">VAT Calculator</a></li>
          <li><a href="/tools/cnss.php">CNSS Calculator</a></li>
          <li><a href="/tools/invoice-generator.php">Invoice Generator</a></li>
          <li><a href="/tools/income-tax-calculator.php">Income Tax Calculator</a></li>
          <li><a href="/tools/expense-note-builder.php">Expense Notes</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-2">
        <h2 class="h6 footer-title">Company</h2>
        <ul class="list-unstyled d-flex flex-column gap-2">
          <li><a href="/About.php">About Us</a></li>
          <li><a href="/contact.php">Contact Us</a></li>
          <li><a href="/terms.php">Terms and Conditions</a></li>
          <li><a href="/privacy.php">Privacy Policy</a></li>
        </ul>
      </div>
      <div class="col-md-4">
        <h2 class="h6 footer-title">Get in Touch</h2>
        <p class="small <?= $footerWhatsapp ? 'mb-3' : 'mb-0' ?>">Have questions? We're here to help.</p>
        <?php if ($footerWhatsapp): ?>
        <a href="<?= htmlspecialchars($footerWhatsapp) ?>" class="nn-btn nn-btn--secondary" target="_blank" rel="noopener">Chat on WhatsApp<span class="nn-icon nn-icon--external" aria-hidden="true"></span><span class="nn-sr-only"> (opens WhatsApp)</span></a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</footer>

<script src="/assets/vendor/bootstrap-5.3.2/bootstrap.bundle.min.js"></script>
<script src="/assets/js/script.js?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/js/script.js') ?>"></script>
<script src="/assets/js/buttons.js?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/js/buttons.js') ?>"></script>
</body>
</html>
