<?php
/**
 * includes/sidebar.php
 * --------------------------------------------------------------------
 * Shared sidebar nav. Include after layout_head.php.
 * Set $activePage before including, matching one of the keys below,
 * e.g.  $activePage = 'company';
 * Below 1024px the sidebar is an off-canvas drawer (assets/js/sidebar.js):
 * put the page's menu button in its .topbar as
 *   <button class="nn-btn nn-btn--primary nn-btn--icon menu-toggle" type="button" aria-controls="appSidebar"
 *           aria-expanded="false" aria-label="Open navigation"><span class="nn-icon nn-icon--menu" aria-hidden="true"></span></button>
 * --------------------------------------------------------------------
 */
$activePage = $activePage ?? '';
$subscriptionStatus = $currentSubscription['subscription_status'] ?? null;
$isSubscriptionActive = $subscriptionStatus === 'active';
if (empty($_SESSION['logout_csrf'])) {
    $_SESSION['logout_csrf'] = bin2hex(random_bytes(32));
}

$navItems = [
    'overview'  => ['icon' => '🏠', 'label' => 'Overview',  'href' => '/dashboard/dashboard.php'],
    'company'   => ['icon' => '🏢', 'label' => 'Company',   'href' => '/company.php'],
    'reports'   => ['icon' => '📊', 'label' => 'Reports',   'href' => '/reports.php'],
    'balance'   => ['icon' => '💰', 'label' => 'Balance',   'href' => '/balance.php'],
    'employees' => ['icon' => '👥', 'label' => 'Employees', 'href' => '/employees.php'],
    'payroll'   => ['icon' => '📋', 'label' => 'Payroll',   'href' => '/payroll.php'],
    'quote'     => ['icon' => '📝', 'label' => 'Quotes',    'href' => '/quote.php'],
    'products'  => ['icon' => '📦', 'label' => 'Products',  'href' => '/products.php'],
    'suppliers' => ['icon' => '🏬', 'label' => 'Suppliers', 'href' => '/suppliers.php'],
    'invoices'  => ['icon' => '📄', 'label' => 'Invoices',  'href' => '/invoices.php'],
    'tva'       => ['icon' => '🧾', 'label' => 'TVA',       'href' => '/tva.php'],
    'documents' => ['icon' => '📁', 'label' => 'Documents', 'href' => '/documents.php'],
    'taxes'     => ['icon' => '💵', 'label' => 'Taxes',     'href' => '/taxes.php'],
    'admin'     => ['icon' => '⚙️', 'label' => 'Admin',     'href' => '/admin.php'],
    'account'   => ['icon' => '🔒', 'label' => 'Account',   'href' => '/account.php'],
];
?>
<aside class="sidebar" id="appSidebar" aria-label="Dashboard navigation">
  <div class="sidebar-head">
    <div class="brand"><div class="brand-mark">N</div>NajmiNet</div>
    <button type="button" class="nn-btn nn-btn--ghost nn-btn--icon drawer-close" data-drawer-close aria-label="Close navigation"><span class="nn-icon nn-icon--close" aria-hidden="true"></span></button>
  </div>
  <nav class="nav">
    <?php foreach ($navItems as $key => $item): ?>
      <a class="nav-item <?= $activePage === $key ? 'active' : '' ?>" href="<?= htmlspecialchars($item['href']) ?>"<?= $activePage === $key ? ' aria-current="page"' : '' ?>>
        <span class="ic" aria-hidden="true"><?= $item['icon'] ?></span><?= htmlspecialchars($item['label']) ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="sidebar-footer">
    <?php if ($isSubscriptionActive): ?>
      <div class="plan-badge plan-badge--pro"><span class="dot"></span>Professional Plan</div>
    <?php else: ?>
      <a class="nn-btn nn-btn--primary nn-btn--block" href="/subscribe.php">Upgrade</a>
    <?php endif; ?>
    <form action="/auth/logout.php" method="post" class="sidebar-logout-form">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['logout_csrf']) ?>">
      <button type="submit" class="nn-btn nn-btn--secondary nn-btn--block"><span class="nn-icon nn-icon--log-out" aria-hidden="true"></span>Log out</button>
    </form>
  </div>
</aside>
<div class="drawer-backdrop" data-drawer-backdrop hidden></div>
<script src="/assets/js/sidebar.js?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/js/sidebar.js') ?>" defer></script>
<script src="/assets/js/buttons.js?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/js/buttons.js') ?>" defer></script>
