<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/middleware/subscription_guard.php';
require_once __DIR__ . '/classes/ReportManager.php';

$userId  = (int) $_SESSION['user_id'];
$manager = new ReportManager($pdo);

$monthNames = [
    1=>'January', 2=>'February', 3=>'March', 4=>'April', 5=>'May', 6=>'June',
    7=>'July', 8=>'August', 9=>'September', 10=>'October', 11=>'November', 12=>'December',
];

// ---- Server-side validated year / month from the query string ----
$currentYear = (int) date('Y');
$year  = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?: $currentYear;
$year  = max(2020, min($year, $currentYear + 3)); // sane bounds, never trust the raw value

$month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT);
if ($month !== null && ($month < 1 || $month > 12)) {
    $month = null; // invalid month -> fall back to the grid view
}

$errors  = [];
$success = false;

// ---- Handle saving a single month's report ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postYear  = filter_input(INPUT_POST, 'report_year', FILTER_VALIDATE_INT);
    $postMonth = filter_input(INPUT_POST, 'report_month', FILTER_VALIDATE_INT);
    $amount    = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $notes     = trim($_POST['notes'] ?? '');

    if (!$postYear || $postYear < 2020 || $postYear > $currentYear + 3) {
        $errors[] = 'Invalid year.';
    }
    if (!$postMonth || $postMonth < 1 || $postMonth > 12) {
        $errors[] = 'Invalid month.';
    }
    if ($amount === false || $amount === null || $amount < 0) {
        $errors[] = 'Please enter a valid amount (0 or more).';
    }

    if (empty($errors)) {
        $manager->saveMonth($userId, $postYear, $postMonth, (float) $amount, $notes ?: null, 'submitted');
        header('Location: /reports.php?year=' . $postYear . '&saved=1');
        exit;
    }

    // Keep editing the same month if validation failed
    $year  = $postYear ?: $year;
    $month = $postMonth ?: $month;
}

if (isset($_GET['saved'])) {
    $success = true;
}

$yearData  = $manager->getYear($userId, $year);
$yearTotal = $manager->getYearTotal($userId, $year);

$editing = $month !== null ? ($yearData[$month] ?? null) : null;

$pageTitle  = 'Monthly Expense Reports';
$activePage = 'reports';
require __DIR__ . '/includes/layout_head.php';
require __DIR__ . '/includes/sidebar.php';
?>

<main class="main">
  <div class="topbar">
    <button class="nn-btn nn-btn--primary nn-btn--icon menu-toggle" type="button" aria-controls="appSidebar" aria-expanded="false" aria-label="Open navigation"><span class="nn-icon nn-icon--menu" aria-hidden="true"></span></button>
  </div>

  <?php if ($month !== null): ?>
    <div class="back-btn-row"><?php $backHref = '/reports.php?year=' . $year; $backLabel = 'Back to reports'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/back-button.php'; ?></div>
  <?php endif; ?>

  <div class="page-header">
    <div>
      <h1>📊 Monthly expense reports</h1>
      <p>Total declared for <?= $year ?>: <strong><?= number_format($yearTotal, 2) ?> MAD</strong></p>
    </div>
    <div class="year-nav">
      <a class="nn-btn nn-btn--secondary nn-btn--sm" href="/reports.php?year=<?= $year - 1 ?>" aria-label="Previous year, <?= $year - 1 ?>"><span class="nn-icon nn-icon--chevron-left" aria-hidden="true"></span><?= $year - 1 ?></a>
      <span class="year-current" aria-current="page"><?= $year ?></span>
      <a class="nn-btn nn-btn--secondary nn-btn--sm" href="/reports.php?year=<?= $year + 1 ?>" aria-label="Next year, <?= $year + 1 ?>"><?= $year + 1 ?><span class="nn-icon nn-icon--chevron-right" aria-hidden="true"></span></a>
    </div>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success">✓ Report saved successfully.</div>
  <?php elseif (!empty($errors)): ?>
    <div class="alert alert-error"><?= htmlspecialchars(implode(' ', $errors)) ?></div>
  <?php endif; ?>

  <?php if ($editing !== null || $month !== null): ?>
    <!-- ============ EDIT VIEW for one month ============ -->
    <div class="card">
      <div class="card-title">
        <span class="badge-ic">📝</span>
        <?= htmlspecialchars($monthNames[$month]) ?> <?= $year ?>
      </div>

      <form method="POST"><?= AuthSecurity::csrfField() ?>
        <input type="hidden" name="report_year" value="<?= $year ?>">
        <input type="hidden" name="report_month" value="<?= $month ?>">

        <div class="field">
          <label>Total expenses (MAD) <span class="req">*</span></label>
          <input type="number" inputmode="decimal" step="0.01" min="0" name="amount" placeholder="0.00"
                 value="<?= htmlspecialchars((string) ($editing['amount'] ?? '')) ?>">
        </div>

        <div class="field">
          <label>Notes</label>
          <textarea name="notes" placeholder="Optional notes about this month's expenses"><?= htmlspecialchars($editing['notes'] ?? '') ?></textarea>
        </div>

        <div class="save-row nn-actions nn-actions--end">
          <a href="/reports.php?year=<?= $year ?>" class="nn-btn nn-btn--ghost">Cancel</a>
          <button type="submit" class="nn-btn nn-btn--primary">Save report</button>
        </div>
      </form>
    </div>
  <?php else: ?>
    <!-- ============ GRID VIEW: 12 months ============ -->
    <div class="card">
      <div class="card-title"><span class="badge-ic">🗓️</span>Select a month</div>
      <div class="month-grid">
        <?php foreach ($monthNames as $num => $name):
          $data = $yearData[$num]; ?>
          <a class="month-card <?= $data ? 'has-data' : '' ?>" href="/reports.php?year=<?= $year ?>&month=<?= $num ?>">
            <div class="m-name"><?= htmlspecialchars($name) ?></div>
            <?php if ($data): ?>
              <div class="m-amount"><?= number_format((float) $data['amount'], 0) ?> MAD</div>
            <?php else: ?>
              <div class="m-empty">+</div>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</main>

</body>
</html>
