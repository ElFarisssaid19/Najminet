<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/middleware/subscription_guard.php';
require_once __DIR__ . '/classes/TvaManager.php';

$userId  = (int) $_SESSION['user_id'];
$manager = new TvaManager($pdo);

$quarterLabels = [1 => 'Quarter 1 (Jan – Mar)', 2 => 'Quarter 2 (Apr – Jun)', 3 => 'Quarter 3 (Jul – Sep)', 4 => 'Quarter 4 (Oct – Dec)'];

$currentYear = (int) date('Y');
$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?: $currentYear;
$year = max(2020, min($year, $currentYear + 3));

$quarter = filter_input(INPUT_GET, 'quarter', FILTER_VALIDATE_INT);
if ($quarter !== null && ($quarter < 1 || $quarter > 4)) {
    $quarter = null;
}

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postYear    = filter_input(INPUT_POST, 'declaration_year', FILTER_VALIDATE_INT);
    $postQuarter = filter_input(INPUT_POST, 'quarter', FILTER_VALIDATE_INT);
    $collected   = filter_input(INPUT_POST, 'amount_collected', FILTER_VALIDATE_FLOAT);
    $deductible  = filter_input(INPUT_POST, 'amount_deductible', FILTER_VALIDATE_FLOAT);

    if (!$postYear || !$postQuarter || $postQuarter < 1 || $postQuarter > 4) {
        $errors[] = 'Invalid year or quarter.';
    }
    if ($collected === false || $collected === null || $collected < 0) {
        $errors[] = 'Please enter a valid collected amount.';
    }
    if ($deductible === false || $deductible === null || $deductible < 0) {
        $errors[] = 'Please enter a valid deductible amount.';
    }

    if (empty($errors)) {
        $manager->saveQuarter($userId, $postYear, $postQuarter, (float) $collected, (float) $deductible, 'submitted');
        header('Location: /tva.php?year=' . $postYear . '&saved=1');
        exit;
    }
    $year    = $postYear ?: $year;
    $quarter = $postQuarter ?: $quarter;
}

if (isset($_GET['saved'])) {
    $success = true;
}

$yearData = $manager->getYear($userId, $year);
$editing  = $quarter !== null ? $yearData[$quarter] : null;

$pageTitle  = 'TVA Declarations';
$activePage = 'tva';
require __DIR__ . '/includes/layout_head.php';
require __DIR__ . '/includes/sidebar.php';
?>
<link rel="stylesheet" href="/assets/css/tools-extra.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/tools-extra.css') ?>">

<main class="main">
  <div class="topbar"><button class="nn-btn nn-btn--primary nn-btn--icon menu-toggle" type="button" aria-controls="appSidebar" aria-expanded="false" aria-label="Open navigation"><span class="nn-icon nn-icon--menu" aria-hidden="true"></span></button></div>

  <?php if ($quarter !== null): ?>
    <div class="back-btn-row"><?php $backHref = '/tva.php?year=' . $year; $backLabel = 'Back to TVA'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/back-button.php'; ?></div>
  <?php endif; ?>

  <div class="page-header">
    <div><h1>🧾 TVA declarations</h1><p>Upload your quarterly TVA declarations. Each quarter covers 3 months of the year.</p></div>
    <div class="year-nav">
      <a class="nn-btn nn-btn--secondary nn-btn--sm" href="/tva.php?year=<?= $year - 1 ?>" aria-label="Previous year, <?= $year - 1 ?>"><span class="nn-icon nn-icon--chevron-left" aria-hidden="true"></span><?= $year - 1 ?></a>
      <span class="year-current" aria-current="page"><?= $year ?></span>
      <a class="nn-btn nn-btn--secondary nn-btn--sm" href="/tva.php?year=<?= $year + 1 ?>" aria-label="Next year, <?= $year + 1 ?>"><?= $year + 1 ?><span class="nn-icon nn-icon--chevron-right" aria-hidden="true"></span></a>
    </div>
  </div>

  <?php if ($success): ?><div class="alert alert-success">✓ Declaration saved successfully.</div><?php endif; ?>
  <?php if (!empty($errors)): ?><div class="alert alert-error"><?= htmlspecialchars(implode(' ', $errors)) ?></div><?php endif; ?>

  <?php if ($quarter !== null): ?>
    <div class="card">
      <div class="card-title"><span class="badge-ic">🧾</span><?= htmlspecialchars($quarterLabels[$quarter]) ?> — <?= $year ?></div>
      <form method="POST"><?= AuthSecurity::csrfField() ?>
        <input type="hidden" name="declaration_year" value="<?= $year ?>">
        <input type="hidden" name="quarter" value="<?= $quarter ?>">

        <div class="field-row">
          <div class="field">
            <label>TVA collected (MAD) <span class="req">*</span></label>
            <input type="number" inputmode="decimal" step="0.01" min="0" name="amount_collected" value="<?= htmlspecialchars((string) ($editing['amount_collected'] ?? '')) ?>">
          </div>
          <div class="field">
            <label>TVA deductible (MAD) <span class="req">*</span></label>
            <input type="number" inputmode="decimal" step="0.01" min="0" name="amount_deductible" value="<?= htmlspecialchars((string) ($editing['amount_deductible'] ?? '')) ?>">
          </div>
        </div>

        <div class="save-row nn-actions nn-actions--end">
          <a href="/tva.php?year=<?= $year ?>" class="nn-btn nn-btn--ghost">Cancel</a>
          <button type="submit" class="nn-btn nn-btn--primary">Save declaration</button>
        </div>
      </form>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="card-title"><span class="badge-ic">ℹ️</span>About TVA declarations</div>
      <p style="color:var(--text-secondary); font-size:13.5px; margin:0 0 20px;">Upload your quarterly TVA (Value Added Tax) declarations. Each quarter covers 3 months of the year.</p>

      <div class="month-grid" style="grid-template-columns:repeat(4, 1fr);">
        <?php foreach ($quarterLabels as $num => $label):
          $data = $yearData[$num];
          $net  = $data ? TvaManager::netDue((float) $data['amount_collected'], (float) $data['amount_deductible']) : null; ?>
          <a class="month-card <?= $data ? 'has-data' : '' ?>" href="/tva.php?year=<?= $year ?>&quarter=<?= $num ?>">
            <div class="m-name"><?= htmlspecialchars($label) ?></div>
            <?php if ($data): ?>
              <div class="m-amount">Net due: <?= number_format($net, 0) ?> MAD</div>
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
