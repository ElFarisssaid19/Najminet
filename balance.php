<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/middleware/subscription_guard.php';
require_once __DIR__ . '/classes/BalanceSheetManager.php';

$userId  = (int) $_SESSION['user_id'];
$manager = new BalanceSheetManager($pdo);

$currentYear = (int) date('Y');
$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?: $currentYear;
$year = max(2020, min($year, $currentYear + 3));

$success = false;
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postYear = filter_input(INPUT_POST, 'sheet_year', FILTER_VALIDATE_INT);
    if (!$postYear || $postYear < 2020 || $postYear > $currentYear + 3) {
        $errors[] = 'Invalid year.';
    }

    // Rebuild line items from parallel arrays — never trust structure/length blindly.
    $assetLabels  = $_POST['assets_label']  ?? [];
    $assetAmounts = $_POST['assets_amount'] ?? [];
    $liabLabels   = $_POST['liabilities_label']  ?? [];
    $liabAmounts  = $_POST['liabilities_amount'] ?? [];

    $assets = [];
    foreach ($assetLabels as $i => $label) {
        $assets[] = ['label' => $label, 'amount' => $assetAmounts[$i] ?? 0];
    }
    $liabilities = [];
    foreach ($liabLabels as $i => $label) {
        $liabilities[] = ['label' => $label, 'amount' => $liabAmounts[$i] ?? 0];
    }

    if (empty($errors)) {
        // BalanceSheetManager::save() sanitizes every row server-side (see classes/BalanceSheetManager.php)
        $manager->save($userId, $postYear, $assets, $liabilities);
        header('Location: /balance.php?year=' . $postYear . '&saved=1');
        exit;
    }
}

if (isset($_GET['saved'])) {
    $success = true;
}

$sheet = $manager->getByYear($userId, $year);
$assets      = $sheet['assets_data'] ?? [];
$liabilities = $sheet['liabilities_data'] ?? [];

// Always show at least one empty row so the form has something to type into
if (empty($assets))      { $assets = [['label' => '', 'amount' => '']]; }
if (empty($liabilities)) { $liabilities = [['label' => '', 'amount' => '']]; }

$totalAssets = BalanceSheetManager::sumLineItems($assets);
$totalLiab   = BalanceSheetManager::sumLineItems($liabilities);

$pageTitle  = 'Balance Sheet';
$activePage = 'balance';
require __DIR__ . '/includes/layout_head.php';
require __DIR__ . '/includes/sidebar.php';
?>

<main class="main">
  <div class="topbar">
    <button class="nn-btn nn-btn--primary nn-btn--icon menu-toggle" type="button" aria-controls="appSidebar" aria-expanded="false" aria-label="Open navigation"><span class="nn-icon nn-icon--menu" aria-hidden="true"></span></button>
  </div>

  <div class="page-header">
    <div>
      <h1>💰 Balance sheet</h1>
      <p>Simplified assets &amp; liabilities overview for <?= $year ?>.</p>
    </div>
    <div class="year-nav">
      <a class="nn-btn nn-btn--secondary nn-btn--sm" href="/balance.php?year=<?= $year - 1 ?>" aria-label="Previous year, <?= $year - 1 ?>"><span class="nn-icon nn-icon--chevron-left" aria-hidden="true"></span><?= $year - 1 ?></a>
      <span class="year-current" aria-current="page"><?= $year ?></span>
      <a class="nn-btn nn-btn--secondary nn-btn--sm" href="/balance.php?year=<?= $year + 1 ?>" aria-label="Next year, <?= $year + 1 ?>"><?= $year + 1 ?><span class="nn-icon nn-icon--chevron-right" aria-hidden="true"></span></a>
    </div>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success">✓ Balance sheet saved successfully.</div>
  <?php elseif (!empty($errors)): ?>
    <div class="alert alert-error"><?= htmlspecialchars(implode(' ', $errors)) ?></div>
  <?php endif; ?>

  <form method="POST" id="balanceForm"><?= AuthSecurity::csrfField() ?>
    <input type="hidden" name="sheet_year" value="<?= $year ?>">

    <div class="balance-columns">
      <div class="card">
        <div class="card-title"><span class="badge-ic">📈</span>Assets (Actif)</div>
        <div id="assetsRows">
          <?php foreach ($assets as $item): ?>
            <div class="line-item-row">
              <input type="text" name="assets_label[]" placeholder="e.g. Cash, Inventory, Equipment" value="<?= htmlspecialchars($item['label']) ?>">
              <input type="number" inputmode="decimal" step="0.01" name="assets_amount[]" placeholder="0.00" value="<?= htmlspecialchars((string) $item['amount']) ?>">
              <button type="button" class="nn-btn nn-btn--danger nn-btn--icon remove-row" aria-label="Remove line" onclick="this.closest('.line-item-row').remove(); recalc();"><span class="nn-icon nn-icon--trash" aria-hidden="true"></span></button>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="nn-btn nn-btn--secondary add-row-btn" onclick="addRow('assetsRows','assets_label[]','assets_amount[]')"><span class="nn-icon nn-icon--plus" aria-hidden="true"></span>Add line item</button>
        <div class="balance-total assets"><span>Total assets</span><span class="val" id="totalAssets"><?= number_format($totalAssets, 2) ?> MAD</span></div>
      </div>

      <div class="card">
        <div class="card-title"><span class="badge-ic">📉</span>Liabilities (Passif)</div>
        <div id="liabilitiesRows">
          <?php foreach ($liabilities as $item): ?>
            <div class="line-item-row">
              <input type="text" name="liabilities_label[]" placeholder="e.g. Loans, Supplier debts, Taxes due" value="<?= htmlspecialchars($item['label']) ?>">
              <input type="number" inputmode="decimal" step="0.01" name="liabilities_amount[]" placeholder="0.00" value="<?= htmlspecialchars((string) $item['amount']) ?>">
              <button type="button" class="nn-btn nn-btn--danger nn-btn--icon remove-row" aria-label="Remove line" onclick="this.closest('.line-item-row').remove(); recalc();"><span class="nn-icon nn-icon--trash" aria-hidden="true"></span></button>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="nn-btn nn-btn--secondary add-row-btn" onclick="addRow('liabilitiesRows','liabilities_label[]','liabilities_amount[]')"><span class="nn-icon nn-icon--plus" aria-hidden="true"></span>Add line item</button>
        <div class="balance-total liabilities"><span>Total liabilities</span><span class="val" id="totalLiabilities"><?= number_format($totalLiab, 2) ?> MAD</span></div>
      </div>
    </div>

    <div class="card" style="margin-top:18px;">
      <div id="balanceCheck" class="balance-check <?= abs($totalAssets - $totalLiab) < 0.01 ? 'ok' : 'off' ?>">
        <?php if (abs($totalAssets - $totalLiab) < 0.01): ?>
          ✓ Balanced — assets equal liabilities.
        <?php else: ?>
          ⚠ Difference of <?= number_format(abs($totalAssets - $totalLiab), 2) ?> MAD between assets and liabilities.
        <?php endif; ?>
      </div>
      <div class="save-row nn-actions nn-actions--end" style="margin-top:16px;">
        <button type="submit" class="nn-btn nn-btn--primary">Save balance sheet</button>
      </div>
    </div>
  </form>
</main>

<script>
function addRow(containerId, labelName, amountName) {
  const container = document.getElementById(containerId);
  const row = document.createElement('div');
  row.className = 'line-item-row';
  row.innerHTML = `
    <input type="text" name="${labelName}" placeholder="Label">
    <input type="number" inputmode="decimal" step="0.01" name="${amountName}" placeholder="0.00">
    <button type="button" class="nn-btn nn-btn--danger nn-btn--icon remove-row" aria-label="Remove line" onclick="this.closest('.line-item-row').remove(); recalc();"><span class="nn-icon nn-icon--trash" aria-hidden="true"></span></button>
  `;
  container.appendChild(row);
}

function recalc() {
  const sum = (selector) => Array.from(document.querySelectorAll(selector))
    .reduce((acc, el) => acc + (parseFloat(el.value) || 0), 0);

  const totalAssets = sum('input[name="assets_amount[]"]');
  const totalLiab = sum('input[name="liabilities_amount[]"]');

  document.getElementById('totalAssets').textContent = totalAssets.toFixed(2) + ' MAD';
  document.getElementById('totalLiabilities').textContent = totalLiab.toFixed(2) + ' MAD';

  const diff = Math.abs(totalAssets - totalLiab);
  const checkEl = document.getElementById('balanceCheck');
  if (diff < 0.01) {
    checkEl.className = 'balance-check ok';
    checkEl.textContent = '✓ Balanced — assets equal liabilities.';
  } else {
    checkEl.className = 'balance-check off';
    checkEl.textContent = `⚠ Difference of ${diff.toFixed(2)} MAD between assets and liabilities.`;
  }
}

document.getElementById('balanceForm').addEventListener('input', (e) => {
  if (e.target.matches('input[type="number"]')) recalc();
});
</script>

</body>
</html>
