<?php
session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/classes/SubscriptionManager.php';

// This public page lets visitors compare plans. Signed-in users can select a
// plan here; account creation itself never creates a subscription.
$pricingMessage = '';
$startUrl = isset($_SESSION['user_id']) ? '#pricing' : '/auth/register.php';
$subscriptions = new SubscriptionManager($pdo);
// Read by includes/header.php: subscribed users get "Dashboard" instead of "View plans".
$hasActiveSubscription = isset($_SESSION['user_id'])
    && $subscriptions->isSubscriptionActive((int) $_SESSION['user_id']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['subscription_action'] ?? '') === 'start_trial') {
    if (empty($_SESSION['user_id'])) {
        header('Location: /auth/register.php');
        exit;
    }
    AuthSecurity::requireValidCsrf();

    // startTrial() itself refuses accounts that can't have a trial, even if the form is forced.
    if ($subscriptions->startTrial((int) $_SESSION['user_id'])) {
        header('Location: /dashboard/dashboard.php');
        exit;
    }

    $pricingMessage = $subscriptions->trialRefusal((int) $_SESSION['user_id'])
        ?? 'The free trial could not be started. Please try again.';
}
// Why a signed-in user can't start the trial (null: they can). "Start free trial" is only shown when null.
$trialRefusal = isset($_SESSION['user_id']) ? $subscriptions->trialRefusal((int) $_SESSION['user_id']) : null;
?>
<?php
$pageTitle = "NajmiNet Innovations | Invoicing, VAT & CNSS Payroll Software in Morocco";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"><meta name="description" content="NajmiNet: free Moroccan accounting tools (VAT, CNSS, income tax, invoices, expense notes) and a full business dashboard."><link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <title><?php echo $pageTitle; ?></title>
  <script>document.documentElement.classList.add('js');</script>

  <link rel="stylesheet" href="/assets/fonts/fonts-2.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/fonts/fonts-2.css') ?>"><link rel="preload" as="font" type="font/woff2" href="/assets/fonts/inter-latin-6ab57b19.woff2" crossorigin><link rel="preload" as="font" type="font/woff2" href="/assets/fonts/sora-latin-46cba0e3.woff2" crossorigin>
  <link href="/assets/vendor/bootstrap-5.3.2/bootstrap.min.css" rel="stylesheet">

  <link rel="stylesheet" href="/assets/css/form-controls.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/form-controls.css') ?>">
<link rel="stylesheet" href="/assets/css/responsive.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/responsive.css') ?>">
<link href="/assets/css/style.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/style.css') ?>" rel="stylesheet">
  <link href="/assets/css/index.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/index.css') ?>" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/buttons.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/buttons.css') ?>">
</head>
<body>

<?php include('includes/header.php'); ?>

<!-- ============ HERO ============ -->
<header class="hero">
  <div class="hero-grid" aria-hidden="true"></div>
  <div class="hero-orb hero-orb--one" aria-hidden="true"></div>
  <div class="hero-orb hero-orb--two" aria-hidden="true"></div>
  <div class="particle particle--1" aria-hidden="true"></div><div class="particle particle--2" aria-hidden="true"></div><div class="particle particle--3" aria-hidden="true"></div><div class="particle particle--4" aria-hidden="true"></div><div class="particle particle--5" aria-hidden="true"></div><div class="particle particle--6" aria-hidden="true"></div>
  <div class="hero-icon hero-icon--dollar" aria-hidden="true"><span>＄</span></div>
  <div class="hero-icon hero-icon--percent" aria-hidden="true"><span>%</span></div>
  <div class="hero-icon hero-icon--calculator" aria-hidden="true"><span>⌗</span></div>
  <div class="hero-icon hero-icon--wallet" aria-hidden="true"><span>◫</span></div>
  <div class="container hero-content">
    <div class="hero-center">
      <span class="eyebrow-pill">✦ All-in-one accounting platform</span>
      <h1 class="hero-title">Smarter Accounting,<br><span>Better Business</span></h1>
      <p class="hero-copy">Generate invoices, calculate VAT, manage expenses, and handle payroll with ease. Everything you need to run your business smoothly.</p>
      <div class="hero-actions nn-actions nn-actions--center">
        <a href="<?= htmlspecialchars($startUrl) ?>" class="nn-btn nn-btn--primary nn-btn--lg">Get started<span class="nn-icon nn-icon--arrow-right" aria-hidden="true"></span></a>
        <a href="#features" class="nn-btn nn-btn--secondary nn-btn--lg">Explore features</a>
      </div>
      <div class="hero-proof"><span>✓ No card required</span><span>✓ 14 days free</span><span>✓ Secure by design</span></div>
    </div>
  </div>
  <div class="hero-horizon" aria-hidden="true"></div>
</header>
<section class="logo-strip"><div class="container"><p>ONE PLACE FOR YOUR ESSENTIAL FINANCIAL WORK</p><div><span>INVOICES</span><span>PAYROLL</span><span>TVA</span><span>EXPENSES</span><span>REPORTING</span></div></div></section>
<!-- ============ FREE TOOLS ============ -->
<section class="section" id="tools">
  <div class="container">
    <div class="text-center mb-5 reveal">
      <h2 class="section-title">Free tools</h2>
      <p class="section-sub mx-auto">Try our free calculators and generators to simplify your financial management</p>
    </div>

    <div class="row g-4">
      <div class="col-md-4 reveal"><a href="/tools/vat.php" class="text-decoration-none"><div class="glass ticket-card"><div class="icon-badge">🧮</div><h3 class="h5">VAT Calculator Morocco</h3><p class="mb-0 small">Instantly calculate TVA amounts for your invoices.</p></div></a></div>
      <div class="col-md-4 reveal"><a href="/tools/cnss.php" class="text-decoration-none"><div class="glass ticket-card"><div class="icon-badge">💼</div><h3 class="h5">CNSS Payroll Calculator</h3><p class="mb-0 small">Compute employee payroll with CNSS contributions.</p></div></a></div>
      <div class="col-md-4 reveal"><a href="/tools/invoice-generator.php" class="text-decoration-none"><div class="glass ticket-card"><div class="icon-badge">📄</div><h3 class="h5">Free Invoice Generator</h3><p class="mb-0 small">Generate professional invoices in seconds.</p></div></a></div>
      <div class="col-md-6 reveal"><a href="/tools/income-tax-calculator.php" class="text-decoration-none"><div class="glass ticket-card"><div class="icon-badge">💰</div><h3 class="h5">Income Tax Calculator Morocco</h3><p class="mb-0 small">Estimate your IR (income tax) with current brackets.</p></div></a></div>
      <div class="col-md-6 reveal"><a href="/tools/expense-note-builder.php" class="text-decoration-none"><div class="glass ticket-card"><div class="icon-badge">📝</div><h3 class="h5">Expense Notes</h3><p class="mb-0 small">Build and export clean expense reports.</p></div></a></div>
    </div>

    <div class="glass trust-strip mt-5 mx-auto reveal" style="max-width:640px;">
      <span>100% Digital</span><span>⚡ Fast Processing</span><span>📄 PDF Export</span>
    </div>
  </div>
</section>

<!-- ============ PRICING ============ -->
<section class="section" id="pricing">
  <div class="container">
    <div class="text-center mb-5 reveal">
      <h2 class="section-title">Pricing Plans</h2>
      <p class="section-sub mx-auto">Choose the plan that fits your business needs</p>
    </div>

    <div class="row g-4 justify-content-center">
      <div class="col-md-5 reveal">
        <div class="glass price-card">
          <p class="text-uppercase small fw-600 mb-1" style="color:var(--blue); letter-spacing:.04em;">14-Day Free Trial</p>
          <p class="small mb-3">Test all features for 14 days with reasonable limits</p>
          <div class="price-value">Free</div>
          <ul class="price-list mt-4">
            <li><span class="check-emerald">✓</span> 14-day free trial</li>
            <li><span class="check-emerald">✓</span> Up to 10 invoices</li>
            <li><span class="check-emerald">✓</span> Up to 5 payroll runs</li>
            <li><span class="check-emerald">✓</span> Up to 15 employees</li>
            <li><span class="check-emerald">✓</span> 0.5GB storage</li>
            <li><span class="check-emerald">✓</span> All features included</li>
          </ul>
          <?php if ($pricingMessage !== ''): ?>
            <p class="small mt-3 mb-0" role="alert"><?= htmlspecialchars($pricingMessage) ?></p>
          <?php elseif ($trialRefusal !== null): ?>
            <p class="small mt-3 mb-0"><?= htmlspecialchars($trialRefusal) ?></p>
          <?php endif; ?>
          <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="/auth/register.php" class="nn-btn nn-btn--secondary nn-btn--block">Start free trial</a>
          <?php elseif ($trialRefusal === null): ?>
            <form method="post"><?= AuthSecurity::csrfField() ?>
              <input type="hidden" name="subscription_action" value="start_trial">
              <button type="submit" class="nn-btn nn-btn--secondary nn-btn--block">Start free trial</button>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-md-5 reveal">
        <div class="glass price-card featured">
          <span class="badge-recommended">Recommended</span>
          <p class="text-uppercase small fw-600 mb-1" style="color:var(--emerald); letter-spacing:.04em;">Professional Plan</p>
          <p class="small mb-3">Unlimited access to all features</p>
          <div class="price-value">$10<span class="fs-6" style="color:var(--text-body);"> / 30 days</span></div>
          <ul class="price-list mt-4">
            <li><span class="check-emerald">✓</span> Unlimited invoices</li>
            <li><span class="check-emerald">✓</span> Unlimited payrolls</li>
            <li><span class="check-emerald">✓</span> Unlimited employees</li>
            <li><span class="check-emerald">✓</span> All features included</li>
          </ul>
          <a href="<?= isset($_SESSION['user_id']) ? '/checkout.php?plan=professional' : '/auth/register.php' ?>" class="nn-btn nn-btn--primary nn-btn--block">Subscribe</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ FEATURES ============ -->
<section class="section" id="features">
  <div class="container">
    <div class="text-center mb-5 reveal">
      <h2 class="section-title">Powerful Features for Modern Businesses</h2>
    </div>

    <div class="row g-4">
      <div class="col-md-6 col-lg-3 reveal"><div class="glass ticket-card"><div class="icon-badge">📑</div><h3 class="h6">Professional Invoices</h3><p class="small mb-0">Create, manage, and send beautiful invoices with PDF export.</p></div></div>
      <div class="col-md-6 col-lg-3 reveal"><div class="glass ticket-card"><div class="icon-badge">📊</div><h3 class="h6">Monthly Reports</h3><p class="small mb-0">Organize expenses with an intuitive 12-month grid view.</p></div></div>
      <div class="col-md-6 col-lg-3 reveal"><div class="glass ticket-card"><div class="icon-badge">👥</div><h3 class="h6">Payroll Management</h3><p class="small mb-0">Complete payroll system with CNSS calculations and payslips.</p></div></div>
      <div class="col-md-6 col-lg-3 reveal"><div class="glass ticket-card"><div class="icon-badge">📄</div><h3 class="h6">TVA Management</h3><p class="small mb-0">Track and manage TVA declarations with automated calculations.</p></div></div>
      <div class="col-md-6 col-lg-3 reveal"><div class="glass ticket-card"><div class="icon-badge">📥</div><h3 class="h6">PDF Export</h3><p class="small mb-0">Generate professional PDF reports instantly.</p></div></div>
      <div class="col-md-6 col-lg-3 reveal"><div class="glass ticket-card"><div class="icon-badge">⚙️</div><h3 class="h6">Tax Configuration</h3><p class="small mb-0">Configure CNSS, IR, and other tax settings.</p></div></div>
      <div class="col-md-6 col-lg-3 reveal"><div class="glass ticket-card"><div class="icon-badge">🏢</div><h3 class="h6">Company Information</h3><p class="small mb-0">Manage company details, address, and tax information.</p></div></div>
      <div class="col-md-6 col-lg-3 reveal"><div class="glass ticket-card"><div class="icon-badge">🎨</div><h3 class="h6">Modern Design</h3><p class="small mb-0">Responsive interface across desktop, tablet, and mobile.</p></div></div>
    </div>
  </div>
</section>

<!-- ============ HOW IT WORKS ============ -->
<section class="section how-it-works" id="about">
  <div class="container">
    <div class="text-center mb-5"><span class="section-kicker">A clearer way to work</span><h2 class="section-title">From first invoice to monthly close—without the spreadsheet chaos.</h2><p class="section-sub mx-auto">Set up once, then let NajmiNet keep your everyday finance work moving.</p></div>
    <div class="row g-4">
      <div class="col-md-4"><article class="step-card reveal"><span>01</span><h3>Set up your business</h3><p>Add your company profile and customise your financial settings in a few minutes.</p></article></div>
      <div class="col-md-4"><article class="step-card reveal"><span>02</span><h3>Keep work flowing</h3><p>Create invoices, log expenses and manage payroll from one connected workspace.</p></article></div>
      <div class="col-md-4"><article class="step-card reveal"><span>03</span><h3>Make confident decisions</h3><p>See your financial position clearly with reports built around your business.</p></article></div>
    </div>
  </div>
</section>

<!-- ============ FAQ ============ -->
<section class="section faq-section" id="faq">
  <div class="container"><div class="row justify-content-center"><div class="col-lg-8"><div class="text-center mb-5"><span class="section-kicker">Questions, answered</span><h2 class="section-title">Everything you need to get started.</h2></div>
    <div class="faq-list">
      <details class="faq-item reveal"><summary>What is included in the free trial?<span>+</span></summary><p>For 14 days, you can explore all NajmiNet features with trial limits for invoices, payroll runs, employees and storage.</p></details>
      <details class="faq-item reveal"><summary>Do I need a credit card to start?<span>+</span></summary><p>No. You can begin the free trial without entering payment details.</p></details>
      <details class="faq-item reveal"><summary>Can I upgrade after my trial?<span>+</span></summary><p>Yes. Choose the Professional plan whenever you are ready and continue right where you left off.</p></details>
    </div>
  </div></div></div>
</section>

<!-- ============ FINAL CTA (only for visitors who can start the trial) ============ -->
<?php if ($trialRefusal === null): ?>
<section class="section pt-0">
  <div class="container">
    <div class="glass final-cta reveal">
      <div class="text-center">
        <span class="section-kicker">Start today</span>
        <h2 class="section-title">Give your finances a calmer home.</h2>
        <p class="section-sub mx-auto mb-4">Start with 14 days free and see what a more organised business feels like.</p>
      </div>
      <div class="nn-actions nn-actions--center"><a href="<?= htmlspecialchars($startUrl) ?>" class="nn-btn nn-btn--primary nn-btn--lg">Start free trial<span class="nn-icon nn-icon--arrow-right" aria-hidden="true"></span></a></div>
    </div>
  </div>
</section>
<?php endif; ?>
<script src="/assets/js/index.js?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/js/index.js') ?>"></script>
<?php include('includes/footer.php'); ?>
