<?php
declare(strict_types=1);
session_start();



require_once __DIR__ . '/../includes/db.php';                     // exposes $pdo
require_once __DIR__ . '/../middleware/subscription_guard.php';   // protects this page
require_once __DIR__ . '/../classes/CompanyManager.php';
require_once __DIR__ . '/../classes/ReportManager.php';
require_once __DIR__ . '/../classes/BalanceSheetManager.php';

$userId   = (int) $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'there';
// Display only (the stored name is unchanged): capitalise each word, so
// "said el fariss" or "SAID EL FARISS" shows as "Said El Fariss"; letters
// typed in capitals inside a word ("McDonald") are kept.
$displayName = mb_strtoupper($userName) === $userName ? mb_strtolower($userName) : $userName;
$displayName = preg_replace_callback('/(^|[\s-])(\p{Ll})/u', static fn (array $m): string => $m[1] . mb_strtoupper($m[2]), $displayName);
$nameWords = preg_split('/\s+/u', trim($displayName)) ?: [''];
$initials = mb_strtoupper(mb_substr($nameWords[0], 0, 1) . (count($nameWords) > 1 ? mb_substr(end($nameWords), 0, 1) : mb_substr($nameWords[0], 1, 1)));

$companyManager = new CompanyManager($pdo);
$reportManager  = new ReportManager($pdo);
$balanceManager = new BalanceSheetManager($pdo);

$currentYear = (int) date('Y');

// ---------------------------------------------------------------
// 1) Company profile completeness
// ---------------------------------------------------------------
$company = $companyManager->getByUser($userId);

$profileFields = [
    'company_name'        => 'Company name',
    'legal_name'          => 'Legal name',
    'vat_number'           => 'VAT number',
    'registration_number' => 'Registration number',
    'bank_name'            => 'Bank name',
    'bank_account_number' => 'Bank account number',
];

$filledCount = 0;
$profileChecklist = [];
foreach ($profileFields as $key => $label) {
    $filled = $company !== null && trim((string) ($company[$key] ?? '')) !== '';
    if ($filled) {
        $filledCount++;
    }
    $profileChecklist[] = ['label' => $label, 'filled' => $filled];
}
$profileTotal      = count($profileFields);
$profilePercent    = (int) round(($filledCount / $profileTotal) * 100);
$profileStatusText = $company === null ? 'Not started' : ($filledCount === $profileTotal ? 'Complete' : 'Incomplete');

// ---------------------------------------------------------------
// 2) Expense reports for the current year
// ---------------------------------------------------------------
$monthNames = [
    1=>'Jan', 2=>'Feb', 3=>'Mar', 4=>'Apr', 5=>'May', 6=>'Jun',
    7=>'Jul', 8=>'Aug', 9=>'Sep', 10=>'Oct', 11=>'Nov', 12=>'Dec',
];

$yearData    = $reportManager->getYear($userId, $currentYear);
$yearTotal   = $reportManager->getYearTotal($userId, $currentYear);
$monthsFiled = count(array_filter($yearData, fn($m) => $m !== null));

// Chart data: amount per month, 0 where nothing was filed yet
$monthlyAmounts = [];
foreach ($yearData as $month => $data) {
    $monthlyAmounts[] = $data !== null ? (float) $data['amount'] : 0;
}

// Most recently filed months, newest first, for the "Recent reports" list
$recentReports = [];
for ($m = 12; $m >= 1; $m--) {
    if ($yearData[$m] !== null) {
        $recentReports[] = [
            'month'  => $monthNames[$m],
            'amount' => (float) $yearData[$m]['amount'],
            'status' => $yearData[$m]['status'],
        ];
    }
    if (count($recentReports) >= 4) {
        break;
    }
}

// ---------------------------------------------------------------
// 3) Balance sheet for the current year
// ---------------------------------------------------------------
$balanceSheet = $balanceManager->getByYear($userId, $currentYear);
$totalAssets  = $balanceSheet ? BalanceSheetManager::sumLineItems($balanceSheet['assets_data']) : 0.0;
$totalLiab    = $balanceSheet ? BalanceSheetManager::sumLineItems($balanceSheet['liabilities_data']) : 0.0;
$isBalanced   = abs($totalAssets - $totalLiab) < 0.01;
$hasBalanceSheet = $balanceSheet !== null;

function reportStatusClass(string $status): string
{
    return $status === 'submitted' ? 'paid' : 'pending';
}
function reportStatusLabel(string $status): string
{
    return $status === 'submitted' ? 'Submitted' : 'Draft';
}

$pageTitle  = 'Dashboard';
$activePage = 'overview';
require __DIR__ . '/../includes/layout_head.php';   // fonts + assets/css/app.css
require __DIR__ . '/../includes/sidebar.php';
?>
<link rel="stylesheet" href="/dashboard/dashboard.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/dashboard/dashboard.css') ?>">
<script src="/assets/vendor/chartjs-4.4.0/chart.umd.min.js?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/vendor/chartjs-4.4.0/chart.umd.min.js') ?>"></script>

<main class="main">
  <div class="topbar">
    <button class="nn-btn nn-btn--primary nn-btn--icon menu-toggle" type="button" aria-controls="appSidebar" aria-expanded="false" aria-label="Open navigation"><span class="nn-icon nn-icon--menu" aria-hidden="true"></span></button>
    <div class="avatar" aria-hidden="true"><?= htmlspecialchars($initials) ?></div>
  </div>

  <div class="greeting">
    <h1>Hello, <?= htmlspecialchars($displayName) ?>. Welcome to your professional dashboard.</h1>
    <p>Here's what's happening with your business today.</p>
  </div>

  <div class="metrics-row">
    <a class="metric-card" href="/company.php">
      <div class="metric-ic" style="background:rgba(79,127,255,0.16); color:#7fa3ff;">🏢</div>
      <div class="metric-value"><?= htmlspecialchars($profileStatusText) ?></div>
      <div class="metric-label">Company profile (<?= $profilePercent ?>%)</div>
    </a>
    <a class="metric-card" href="/reports.php">
      <div class="metric-ic" style="background:rgba(245,185,66,0.16); color:#f5b942;">🧾</div>
      <div class="metric-value"><?= number_format($yearTotal, 0) ?> MAD</div>
      <div class="metric-label">Total expenses (<?= $currentYear ?>)</div>
    </a>
    <a class="metric-card" href="/balance.php">
      <div class="metric-ic" style="background:rgba(61,214,140,0.16); color:#3dd68c;">💰</div>
      <div class="metric-value"><?= number_format($totalAssets, 0) ?> MAD</div>
      <div class="metric-label">Total assets (<?= $currentYear ?>)</div>
    </a>
    <a class="metric-card" href="/reports.php">
      <div class="metric-ic" style="background:rgba(139,92,246,0.16); color:#8b5cf6;">📊</div>
      <div class="metric-value"><?= $monthsFiled ?> / 12</div>
      <div class="metric-label">Months reported</div>
    </a>
  </div>

  <div class="grid-2">
    <div class="card">
      <div class="card-header"><h2>Monthly expenses — <?= $currentYear ?></h2></div>
      <div class="chart-wrap"><canvas id="expensesChart"></canvas></div>
    </div>

    <div class="card">
      <div class="card-header"><h2>Recent expense reports</h2><a class="nn-btn nn-btn--ghost nn-btn--sm" href="/reports.php">View all</a></div>
      <?php if (empty($recentReports)): ?>
        <p style="color:var(--text-muted); font-size:13.5px;">No reports filed yet for <?= $currentYear ?>. <a class="text-link" href="/reports.php">Add your first report</a></p>
      <?php else: ?>
        <?php foreach ($recentReports as $r): ?>
        <div class="list-row">
          <div class="list-left">
            <div class="list-ic">🧾</div>
            <div>
              <div class="list-name"><?= htmlspecialchars($r['month']) ?> <?= $currentYear ?></div>
              <div class="list-sub"><?= number_format($r['amount'], 2) ?> MAD</div>
            </div>
          </div>
          <span class="status <?= reportStatusClass($r['status']) ?>"><?= reportStatusLabel($r['status']) ?></span>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="grid-2-bottom">
    <div class="card">
      <div class="card-header"><h2>Balance sheet snapshot</h2><a class="nn-btn nn-btn--ghost nn-btn--sm" href="/balance.php">View full sheet</a></div>
      <?php if (!$hasBalanceSheet): ?>
        <p style="color:var(--text-muted); font-size:13.5px;">No balance sheet for <?= $currentYear ?> yet. <a class="text-link" href="/balance.php">Create one</a></p>
      <?php else: ?>
        <div class="decl-row">
          <div class="decl-dot" style="background:var(--success);"></div>
          <div style="flex:1;">
            <div class="decl-title">Total assets</div>
            <div class="decl-due"><?= number_format($totalAssets, 2) ?> MAD</div>
          </div>
        </div>
        <div class="decl-row">
          <div class="decl-dot" style="background:var(--danger);"></div>
          <div style="flex:1;">
            <div class="decl-title">Total liabilities</div>
            <div class="decl-due"><?= number_format($totalLiab, 2) ?> MAD</div>
          </div>
        </div>
        <div class="decl-row">
          <div class="decl-dot" style="background:<?= $isBalanced ? 'var(--success)' : 'var(--warning)' ?>;"></div>
          <div style="flex:1;">
            <div class="decl-title"><?= $isBalanced ? 'Balanced' : 'Not balanced' ?></div>
            <div class="decl-due"><?= $isBalanced ? 'Assets equal liabilities' : 'Difference: ' . number_format(abs($totalAssets - $totalLiab), 2) . ' MAD' ?></div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="card-header"><h2>Company profile</h2><a class="nn-btn nn-btn--ghost nn-btn--sm" href="/company.php">Edit profile</a></div>
      <?php foreach ($profileChecklist as $item): ?>
        <div class="rank-row">
          <div class="rank-num"><?= $item['filled'] ? '✅' : '⬜' ?></div>
          <div class="rank-name"><?= htmlspecialchars($item['label']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</main>

<script>
window.__monthlyExpenses = <?= json_encode($monthlyAmounts) ?>;
window.__monthLabels = <?= json_encode(array_values($monthNames)) ?>;
</script>
<script src="/dashboard/dashboard.js?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/dashboard/dashboard.js') ?>"></script>
</body>
</html>
