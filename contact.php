<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/contact_whatsapp.php';
require_once __DIR__ . '/includes/contact_email.php';
require_once __DIR__ . '/includes/AuthSecurity.php';

// $contactNotice is set by contact_handler.php when it shows this page again.
$contactNotice ??= (($_GET['sent'] ?? '') === '1')
    ? ['ok' => true, 'message' => "✓ Message sent successfully! We'll get back to you soon."]
    : null;
$old = static fn (string $field): string => htmlspecialchars((string) ($contactNotice !== null && !$contactNotice['ok'] ? ($_POST[$field] ?? '') : ''));
$pageTitle = "Contact Us | NajmiNet Innovations";
$whatsappUrl = contact_whatsapp_url();
$contactEmail = contact_email_address();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"><meta name="description" content="Contact NajmiNet Innovations: questions about accounting tools, plans or your account."><link rel="icon" href="/favicon.svg" type="image/svg+xml">
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




<!-- ============ PAGE HEADER ============ -->
<header class="hero" style="padding:80px 0 40px;">
  <div class="hero-overlay"></div>
  <div class="container hero-content text-center">
    <span class="glass-pill eyebrow reveal">Get in Touch</span>
    <h1 class="mt-3 mb-3 reveal">Contact Us</h1>
    <p class="section-sub mx-auto reveal">Have a question about invoicing, payroll, or VAT management? Our team is here to help.</p>

    <?php if ($contactNotice !== null): ?>
      <div class="glass-pill mt-4 mx-auto px-4 py-2" role="<?= $contactNotice['ok'] ? 'status' : 'alert' ?>" style="max-width:520px; color:var(--<?= $contactNotice['ok'] ? 'emerald' : 'rose' ?>, <?= $contactNotice['ok'] ? '#34d399' : '#f87171' ?>); font-weight:600;">
        <?= htmlspecialchars($contactNotice['message']) ?>
      </div>
    <?php endif; ?>
  </div>
</header>

<!-- ============ CONTACT CONTENT ============ -->
<section class="section pt-0">
  <div class="container">
    <div class="row g-4 align-items-start">
      <!-- Contact form -->
      <div class="col-lg-7 reveal">
        <div class="glass glass-pad">
          <h2 class="h3 mb-4">Send us a message</h2>
<form action="/contact_handler.php" method="POST" id="contact-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(AuthSecurity::formCsrfToken('contact')) ?>">
            <!-- Honeypot: hidden from people, irresistible to bots. -->
            <div class="hp-field" aria-hidden="true">
              <label for="contactWebsite">Website</label>
              <input type="text" id="contactWebsite" name="website" tabindex="-1" autocomplete="off">
            </div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small" style="color:var(--text-h); font-weight:600;">Full Name</label>
                <input type="text" name="name" autocomplete="name" class="form-control form-control-glass" placeholder="Your name" required maxlength="150" value="<?= $old('name') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label small" style="color:var(--text-h); font-weight:600;">Email</label>
                <input type="email" name="email" autocomplete="email" class="form-control form-control-glass" placeholder="you@company.com" required maxlength="190" value="<?= $old('email') ?>">
              </div>
              <div class="col-12">
                <label class="form-label small" style="color:var(--text-h); font-weight:600;">Subject</label>
                <input type="text" name="subject" class="form-control form-control-glass" placeholder="How can we help?" maxlength="200" value="<?= $old('subject') ?>">
              </div>
              <div class="col-12">
                <label class="form-label small" style="color:var(--text-h); font-weight:600;">Message</label>
                <textarea name="message" rows="5" class="form-control form-control-glass" placeholder="Tell us more about your request..." required maxlength="5000"><?= $old('message') ?></textarea>
              </div>
              <div class="col-12">
                <button type="submit" class="nn-btn nn-btn--primary nn-btn--lg nn-btn--block"><span class="nn-icon nn-icon--send" aria-hidden="true"></span>Send message</button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Contact info -->
      <div class="col-lg-5 reveal">
        <?php if ($whatsappUrl): ?>
        <div class="glass ticket-card mb-4">
          <div class="icon-badge">📱</div>
          <h3 class="h5 mb-2">WhatsApp</h3>
          <p class="small mb-3">Fastest way to reach us — chat directly with our team.</p>
          <a href="<?= htmlspecialchars($whatsappUrl) ?>" class="nn-btn nn-btn--secondary" target="_blank" rel="noopener">Chat on WhatsApp<span class="nn-icon nn-icon--external" aria-hidden="true"></span><span class="nn-sr-only"> (opens WhatsApp)</span></a>
        </div>
        <?php endif; ?>
        <?php if ($contactEmail): ?>
        <div class="glass ticket-card mb-4">
          <div class="icon-badge">✉️</div>
          <h3 class="h5 mb-2">Email</h3>
          <p class="small mb-0"><?= htmlspecialchars($contactEmail) ?></p>
        </div>
        <?php endif; ?>
        <div class="glass ticket-card">
          <div class="icon-badge">📍</div>
          <h3 class="h5 mb-2">Location</h3>
          <p class="small mb-0">Morocco — serving businesses nationwide</p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include('includes/footer.php'); ?>
