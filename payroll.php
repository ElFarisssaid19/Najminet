<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/middleware/subscription_guard.php';
require_once __DIR__ . '/classes/PayrollManager.php';

$userId  = (int) $_SESSION['user_id'];
$manager = new PayrollManager($pdo);

$monthNames = [
    1=>'January', 2=>'February', 3=>'March', 4=>'April', 5=>'May', 6=>'June',
    7=>'July', 8=>'August', 9=>'September', 10=>'October', 11=>'November', 12=>'December',
];

$currentYear = (int) date('Y');
$year  = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?: $currentYear;
$year  = max(2020, min($year, $currentYear + 3));

$month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT);
if ($month !== null && ($month < 1 || $month > 12)) {
    $month = null;
}

$notice = null;

// ---- Generate or finalize a run ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postYear  = filter_input(INPUT_POST, 'run_year', FILTER_VALIDATE_INT);
    $postMonth = filter_input(INPUT_POST, 'run_month', FILTER_VALIDATE_INT);
    $action    = $_POST['action'] ?? '';

    if ($postYear && $postMonth && $postMonth >= 1 && $postMonth <= 12) {
        if ($action === 'generate') {
            $manager->generateMonth($userId, $postYear, $postMonth);
        } elseif ($action === 'finalize') {
            $run = $manager->getMonth($userId, $postYear, $postMonth);
            if ($run) {
                $manager->saveMonth($userId, $postYear, $postMonth, $run['lines_data'], 'finalized');
            }
        }
        header('Location: /payroll.php?year=' . $postYear . '&month=' . $postMonth . '&saved=1');
        exit;
    }
}

if (isset($_GET['saved'])) {
    $notice = 'Payroll updated successfully.';
}

$yearData = $manager->getYear($userId, $year);
$run = $month !== null ? $yearData[$month] : null;

$pageTitle  = 'Payroll';
$activePage = 'payroll';
require __DIR__ . '/includes/layout_head.php';
require __DIR__ . '/includes/sidebar.php';
?>
<link rel="stylesheet" href="/assets/css/tools-extra.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/tools-extra.css') ?>">

<main class="main">
  <div class="topbar"><button class="nn-btn nn-btn--primary nn-btn--icon menu-toggle" type="button" aria-controls="appSidebar" aria-expanded="false" aria-label="Open navigation"><span class="nn-icon nn-icon--menu" aria-hidden="true"></span></button></div>

  <?php if ($month !== null): ?>
    <div class="back-btn-row"><?php $backHref = '/payroll.php?year=' . $year; $backLabel = 'Back to payroll'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/back-button.php'; ?></div>
  <?php endif; ?>

  <div class="page-header">
    <div><h1>📋 Payroll</h1><p>Generate and review monthly payroll runs for your team.</p></div>
    <div class="year-nav">
      <a class="nn-btn nn-btn--secondary nn-btn--sm" href="/payroll.php?year=<?= $year - 1 ?>" aria-label="Previous year, <?= $year - 1 ?>"><span class="nn-icon nn-icon--chevron-left" aria-hidden="true"></span><?= $year - 1 ?></a>
      <span class="year-current" aria-current="page"><?= $year ?></span>
      <a class="nn-btn nn-btn--secondary nn-btn--sm" href="/payroll.php?year=<?= $year + 1 ?>" aria-label="Next year, <?= $year + 1 ?>"><?= $year + 1 ?><span class="nn-icon nn-icon--chevron-right" aria-hidden="true"></span></a>
    </div>
  </div>

  <?php if ($notice): ?><div class="alert alert-success">✓ <?= htmlspecialchars($notice) ?></div><?php endif; ?>

  <?php if ($month !== null): ?>
    <div class="card">
      <div class="card-title"><span class="badge-ic">📋</span><?= htmlspecialchars($monthNames[$month]) ?> <?= $year ?> payroll</div>

      <?php if (!$run || empty($run['lines_data'])): ?>
        <div class="empty-state">
          <div class="big">📋</div>
          <h3>No payroll generated yet</h3>
          <p>Generate this month's payroll from your active employees.</p>
          <form method="POST" style="margin-top:16px;"><?= AuthSecurity::csrfField() ?>
            <input type="hidden" name="run_year" value="<?= $year ?>">
            <input type="hidden" name="run_month" value="<?= $month ?>">
            <input type="hidden" name="action" value="generate">
            <button type="submit" class="nn-btn nn-btn--primary">Generate payroll</button>
          </form>
        </div>
      <?php else: ?>
        <p class="scroll-hint" aria-hidden="true">Swipe sideways to see every column →</p>
        <div class="table-scroll" role="region" aria-label="Payroll lines" tabindex="0">
        <table class="data-table payroll-table">
          <thead><tr><th>Employee</th><th>Gross</th><th>CNSS</th><th>IR</th><th>Net</th></tr></thead>
          <tbody>
            <?php foreach ($run['lines_data'] as $line): ?>
            <tr>
              <td class="cell-strong" style="text-align:left;"><?= htmlspecialchars($line['employee_name']) ?></td>
              <td><?= number_format((float) $line['gross'], 2) ?></td>
              <td><?= number_format((float) $line['cnss'], 2) ?></td>
              <td><?= number_format((float) $line['ir'], 2) ?></td>
              <td class="cell-strong"><?= number_format((float) $line['net'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>

        <div class="balance-total assets"><span>Total net payroll</span><span class="val"><?= number_format((float) $run['total_net'], 2) ?> MAD</span></div>

        <div class="save-row nn-actions nn-actions--end" style="margin-top:16px;">
          <form method="POST"><?= AuthSecurity::csrfField() ?><input type="hidden" name="run_year" value="<?= $year ?>"><input type="hidden" name="run_month" value="<?= $month ?>"><input type="hidden" name="action" value="generate">
            <button type="submit" class="nn-btn nn-btn--secondary" data-confirm="Regenerate this payroll? Its lines are recalculated from current salaries and it goes back to draft, even if it was finalized."><span class="nn-icon nn-icon--rotate" aria-hidden="true"></span>Regenerate</button>
          </form>
          <?php if ($run['status'] !== 'finalized'): ?>
          <form method="POST"><?= AuthSecurity::csrfField() ?><input type="hidden" name="run_year" value="<?= $year ?>"><input type="hidden" name="run_month" value="<?= $month ?>"><input type="hidden" name="action" value="finalize">
            <button type="submit" class="nn-btn nn-btn--primary"><span class="nn-icon nn-icon--check" aria-hidden="true"></span>Finalize payroll</button>
          </form>
          <?php else: ?>
            <span class="status paid">Finalized</span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="card-title"><span class="badge-ic">🗓️</span>Select a month</div>
      <div class="month-grid">
        <?php foreach ($monthNames as $num => $name):
          $data = $yearData[$num]; ?>
          <a class="month-card <?= $data ? 'has-data' : '' ?>" href="/payroll.php?year=<?= $year ?>&month=<?= $num ?>">
            <div class="m-name"><?= htmlspecialchars($name) ?></div>
            <?php if ($data): ?>
              <div class="m-amount"><?= number_format((float) $data['total_net'], 0) ?> MAD<?= $data['status'] === 'finalized' ? ' ✓' : '' ?></div>
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
