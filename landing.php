<?php

session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/classes/SubscriptionManager.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit;
}

$isSignedIn = isset($_SESSION['user_id']);
$hasActiveSubscription = $isSignedIn
    && (new SubscriptionManager($pdo))->isSubscriptionActive((int) $_SESSION['user_id']);
if ($isSignedIn && empty($_SESSION['logout_csrf'])) {
    $_SESSION['logout_csrf'] = bin2hex(random_bytes(32));
}

// Newsletter: CSRF token for the footer form, plus the flash message left
// behind by a no-JavaScript POST to /newsletter/subscribe.php.
$newsletterCsrf = AuthSecurity::formCsrfToken('newsletter');
$newsletterFlash = $_SESSION['newsletter_flash'] ?? null;
unset($_SESSION['newsletter_flash']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"><meta name="description" content="NajmiNet: invoicing, VAT/TVA, CNSS payroll and financial reports for Moroccan businesses, in one secure platform."><link rel="icon" href="/favicon.svg" type="image/svg+xml">
<title>NajmiNet Innovations | Smart Accounting for Modern Businesses</title>

<link rel="stylesheet" href="/assets/fonts/fonts-3.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/fonts/fonts-3.css') ?>"><link rel="preload" as="font" type="font/woff2" href="/assets/fonts/sora-latin-c7aee659.woff2" crossorigin><link rel="preload" as="font" type="font/woff2" href="/assets/fonts/space-grotesk-latin-4ecc7e89.woff2" crossorigin>
<link rel="stylesheet" href="/assets/vendor/fontawesome-6.5.1/fa.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/vendor/fontawesome-6.5.1/fa.css') ?>">

<!-- ?v= changes whenever the file does, so a cached old copy is never reused with newer markup. -->
<link rel="stylesheet" href="/assets/css/responsive.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/responsive.css') ?>">
<link rel="stylesheet" href="/assets/css/form-controls.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/form-controls.css') ?>">
<link rel="stylesheet" href="/assets/css/landing.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/landing.css') ?>">
<link rel="stylesheet" href="/assets/css/buttons.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/buttons.css') ?>">
</head>
<body>

<!-- ========== NAVBAR ========== -->
<header class="navbar" id="navbar">
    <div class="navbar__inner">
        <a href="/" class="brand">
            <span class="brand__icon"><i class="fa-solid fa-n"></i></span>
            <span class="brand__text">
                NajmiNet
                <small>Innovations</small>
            </span>
        </a>

        <nav class="nav-links" id="navLinks" aria-label="Main">
            <a href="#features">Features</a>
            <a href="/About.php">About</a>
            <a href="/contact.php">Contact</a>
            <?php if (!$isSignedIn): ?>
            <!-- Shown in the mobile menu, where the header buttons are hidden. -->
            <a href="/auth/login.php" class="nav-links__auth">Log in</a>
            <a href="/auth/register.php" class="nav-links__auth">Sign up</a>
            <?php endif; ?>
        </nav>

        <div class="navbar__actions">
            <?php if ($isSignedIn): ?>
                <a href="<?= $hasActiveSubscription ? '/dashboard/dashboard.php' : '/index.php#pricing' ?>" class="nn-btn nn-btn--primary nn-btn--sm header-cta"><?= $hasActiveSubscription ? 'Dashboard' : 'View plans' ?></a>
                <form action="/logout.php" method="post" class="logout-form header-cta">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['logout_csrf']) ?>">
                    <button type="submit" class="nn-btn nn-btn--secondary nn-btn--sm"><span class="nn-icon nn-icon--log-out" aria-hidden="true"></span>Log out</button>
                </form>
            <?php else: ?>
                <a href="/auth/login.php" class="nn-btn nn-btn--ghost nn-btn--sm header-cta">Log in</a>
                <a href="/auth/register.php" class="nn-btn nn-btn--primary nn-btn--sm header-cta">Sign up</a>
            <?php endif; ?>

            <button type="button" class="nn-btn nn-btn--secondary nn-btn--icon burger" id="burger" aria-controls="navLinks" aria-expanded="false" aria-label="Open menu">
                <span class="nn-icon nn-icon--menu" aria-hidden="true"></span>
            </button>
        </div>
    </div>
</header>

<!-- ========== HERO ========== -->
<section class="hero">
    <div class="hero__inner">
        <div class="hero__content" data-animate="fade-up">
            <span class="badge"><i class="fa-solid fa-wand-magic-sparkles"></i> Complete Accounting Platform</span>

            <h1 class="hero__title">
                Smart Accounting<br>
                for Modern<br>
                <span class="text-gradient">Businesses</span>
            </h1>

            <p class="hero__subtitle">
                NajmiNet Innovations is an all-in-one platform to manage invoices,
                VAT, payroll, expenses, and financial reports with ease and efficiency.
            </p>

            <div class="hero__cta nn-actions">
                <?php if ($hasActiveSubscription): ?>
                <a href="/dashboard/dashboard.php" class="nn-btn nn-btn--primary nn-btn--lg">Open dashboard<span class="nn-icon nn-icon--arrow-right" aria-hidden="true"></span></a>
                <?php elseif ($isSignedIn): ?>
                <a href="/index.php#pricing" class="nn-btn nn-btn--primary nn-btn--lg">View plans<span class="nn-icon nn-icon--arrow-right" aria-hidden="true"></span></a>
                <?php else: ?>
                <a href="/auth/register.php" class="nn-btn nn-btn--primary nn-btn--lg"><span class="nn-icon nn-icon--user-plus" aria-hidden="true"></span>Sign up</a>
                <a href="/auth/login.php" class="nn-btn nn-btn--secondary nn-btn--lg"><span class="nn-icon nn-icon--log-in" aria-hidden="true"></span>Log in</a>
                <?php endif; ?>
            </div>

            <ul class="hero__badges">
                <li><i class="fa-solid fa-shield-halved"></i> Secure</li>
                <li><i class="fa-solid fa-bolt"></i> Fast</li>
                <li><i class="fa-solid fa-circle-check"></i> Reliable</li>
                <li><i class="fa-solid fa-headset"></i> 24/7 Support</li>
            </ul>
        </div>

        <div class="hero__mockup" data-animate="fade-left">
            <div class="mockup mockup--tools">
                <div class="mockup__header">
                    <span class="mockup__brand">
                        <span class="mockup__brand-icon" ><i class="fa-solid fa-n"></i></span>
                        NajmiNet <small>Tools</small>
                    </span>
                    <span class="mockup__pill"><i class="fa-solid fa-bolt"></i> Live calculation</span>
                </div>

                <div class="mockup__tools-grid">
                    <!-- VAT / TVA card -->
                    <div class="tool-card">
                        <div class="tool-card__head">
                            <span class="tool-card__icon icon--green"><i class="fa-solid fa-percent"></i></span>
                            <div>
                                <h2>VAT / TVA Calculator</h2>
                                <small>Standard rate 20%</small>
                            </div>
                        </div>

                        <div class="tool-field">
                            <label>Amount excl. VAT</label>
                            <div class="tool-input">12,500 <span>DH</span></div>
                        </div>
                        <div class="tool-field">
                            <label>VAT rate</label>
                            <div class="tool-input">20 <span>%</span></div>
                        </div>

                        <div class="tool-divider"></div>

                        <div class="tool-result">
                            <span>VAT amount</span>
                            <strong>2,500 DH</strong>
                        </div>
                        <div class="tool-result tool-result--total">
                            <span>Total incl. VAT</span>
                            <strong>15,000 DH</strong>
                        </div>
                    </div>

                    <!-- CNSS card -->
                    <div class="tool-card">
                        <div class="tool-card__head">
                            <span class="tool-card__icon icon--amber"><i class="fa-solid fa-users"></i></span>
                            <div>
                                <h2>CNSS Calculator</h2>
                                <small>Employee &amp; employer share</small>
                            </div>
                        </div>

                        <div class="tool-field">
                            <label>Gross salary</label>
                            <div class="tool-input">8,000 <span>DH</span></div>
                        </div>

                        <div class="tool-bars">
                            <div class="tool-bar">
                                <span>Employee 4.48%</span>
                                <div class="bar"><div class="bar__fill bar__fill--violet" style="width:45%"></div></div>
                                <strong>358 DH</strong>
                            </div>
                            <div class="tool-bar">
                                <span>Employer 21.09%</span>
                                <div class="bar"><div class="bar__fill bar__fill--blue" style="width:80%"></div></div>
                                <strong>1,687 DH</strong>
                            </div>
                        </div>

                        <div class="tool-divider"></div>

                        <div class="tool-result tool-result--total">
                            <span>Net salary</span>
                            <strong>7,642 DH</strong>
                        </div>
                    </div>
                </div>

                <div class="mockup__footer">
                    <i class="fa-solid fa-circle-check"></i> Compliant with Moroccan tax &amp; CNSS regulations
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== TRUSTED BY ========== -->
<section class="trusted">
    <p class="trusted__label">Trusted by businesses worldwide</p>
    <div class="trusted__logos">
        <span><i class="fa-solid fa-cube"></i> TechCorp</span>
        <span><i class="fa-solid fa-layer-group"></i> BuildFast</span>
        <span><i class="fa-regular fa-circle"></i> CreativeStudio</span>
        <span><i class="fa-solid fa-signal"></i> FinancePro</span>
        <span><i class="fa-solid fa-globe"></i> GlobalTrade</span>
        <span><i class="fa-solid fa-xmark"></i> InnovateX</span>
    </div>
</section>

<!-- ========== FEATURES ========== -->
<section class="features" id="features">
    <div class="section-head" data-animate="fade-up">
        <span class="eyebrow">Powerful Features</span>
        <h2>Everything You Need<br>to Run Your Business</h2>
        <p>A complete suite of accounting tools designed to save you time, reduce errors, and help your business grow.</p>
    </div>

    <div class="features__grid">
        <div class="feature-card" data-animate="fade-up">
            <span class="feature-card__icon icon--violet"><i class="fa-solid fa-file-invoice"></i></span>
            <h3>Smart Invoicing</h3>
            <p>Create professional invoices in seconds and get paid faster.</p>
        </div>
        <div class="feature-card" data-animate="fade-up">
            <span class="feature-card__icon icon--green"><i class="fa-solid fa-percent"></i></span>
            <h3>VAT / TVA Management</h3>
            <p>Calculate, collect, and file VAT with accuracy and ease.</p>
        </div>
        <div class="feature-card" data-animate="fade-up">
            <span class="feature-card__icon icon--amber"><i class="fa-solid fa-users"></i></span>
            <h3>Payroll (CNSS)</h3>
            <p>Manage employee salaries and CNSS contributions effortlessly.</p>
        </div>
        <div class="feature-card" data-animate="fade-up">
            <span class="feature-card__icon icon--blue"><i class="fa-solid fa-chart-line"></i></span>
            <h3>Financial Reports</h3>
            <p>Get detailed insights with balance sheets, profit &amp; loss, and more.</p>
        </div>
        <div class="feature-card" data-animate="fade-up">
            <span class="feature-card__icon icon--pink"><i class="fa-solid fa-wallet"></i></span>
            <h3>Expense Management</h3>
            <p>Track and organize your business expenses easily.</p>
        </div>
        <div class="feature-card" data-animate="fade-up">
            <span class="feature-card__icon icon--teal"><i class="fa-solid fa-lock"></i></span>
            <h3>Secure &amp; Reliable</h3>
            <p>Your data is protected with top-level security and daily backups.</p>
        </div>
    </div>
</section>


<!-- ========== CTA BANNER ========== -->
<section class="cta-banner on-brand" data-animate="fade-up">
    <div class="cta-banner__content">
        <span class="badge badge--inverse"><i class="fa-solid fa-rocket"></i> Get Started Today</span>
        <h2>Join thousands of businesses<br>growing with NajmiNet</h2>
        <p>Start managing your accounting the smart way.</p>
    </div>
    <div class="cta-banner__actions nn-actions">
        <a href="/auth/register.php" class="nn-btn nn-btn--primary nn-btn--lg"><span class="nn-icon nn-icon--user-plus" aria-hidden="true"></span>Sign up</a>
        <a href="/auth/login.php" class="nn-btn nn-btn--secondary nn-btn--lg"><span class="nn-icon nn-icon--log-in" aria-hidden="true"></span>Log in</a>
    </div>
    <i class="fa-solid fa-rocket cta-banner__rocket"></i>
</section>

<!-- ========== FOOTER ========== -->
<footer class="footer" id="contact">
    <div class="footer__top">
        <div class="footer__brand">
            <a href="/" class="brand">
                <span class="brand__icon"><i class="fa-solid fa-n"></i></span>
                <span class="brand__text">NajmiNet<small>Innovations</small></span>
            </a>
            <p>All-in-one accounting solution for modern businesses. Invoices, VAT, Payroll, Reports and more.</p>
        </div>

        <div class="footer__col">
            <h2 class="footer__title">Platform</h2>
            <a href="#features">Features</a>
            <a href="/About.php">About Us</a>
            <a href="/contact.php">Contact</a>
        </div>

        <div class="footer__col">
            <h2 class="footer__title">Support</h2>
            <a href="/contact.php">Contact Support</a>
        </div>

        <div class="footer__col">
            <h2 class="footer__title">Legal</h2>
            <a href="/terms.php">Terms of Service</a>
            <a href="/privacy.php">Privacy Policy</a>
            
        </div>

        <div class="footer__col footer__newsletter" id="newsletter">
            <h2 class="footer__title">Newsletter</h2>
            <p>Subscribe to get the latest updates and tips for your business.</p>
            <form class="newsletter-form"
                  id="newsletterForm"
                  method="post"
                  action="/newsletter/subscribe.php"
                  data-sending="Sending…"
                  data-error="Something went wrong. Please try again later.">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($newsletterCsrf) ?>">
                <!-- Honeypot: hidden from people, irresistible to bots. -->
                <div class="newsletter-trap" aria-hidden="true">
                    <label for="newsletterWebsite">Website</label>
                    <input type="text" id="newsletterWebsite" name="website" tabindex="-1" autocomplete="off">
                </div>
                <input type="email"
                       name="email"
                       placeholder="Enter your email"
                       aria-label="Email"
                       autocomplete="email"
                       required>
                <button type="submit" class="nn-btn nn-btn--primary nn-btn--icon" aria-label="Subscribe">
                    <span class="nn-icon nn-icon--send" aria-hidden="true"></span>
                </button>
            </form>
            <p class="newsletter-msg<?= $newsletterFlash ? ($newsletterFlash['ok'] ? ' is-ok' : ' is-error') : '' ?>"
               id="newsletterMsg"
               role="status"
               aria-live="polite"><?= $newsletterFlash ? htmlspecialchars((string) $newsletterFlash['message']) : '' ?></p>
        </div>
    </div>

    <div class="footer__bottom">
        <p>&copy; <?php echo date('Y'); ?> NajmiNet Innovations. All rights reserved.</p>
    </div>
</footer>

<button type="button" class="nn-btn nn-btn--primary nn-btn--icon scroll-top" id="scrollTop" aria-label="Scroll to top">
    <span class="nn-icon nn-icon--arrow-up" aria-hidden="true"></span>
</button>

<script src="/assets/js/landing.js?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/js/landing.js') ?>"></script>
<script src="/assets/js/buttons.js?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/js/buttons.js') ?>"></script>
</body>
</html>
