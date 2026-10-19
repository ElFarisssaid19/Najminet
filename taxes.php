<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/middleware/subscription_guard.php';
require_once __DIR__ . '/classes/SimpleListManager.php';

$userId  = (int) $_SESSION['user_id'];
$manager = new SimpleListManager($pdo, 'tax_brackets', [
    'bracket_type', 'min_amount', 'max_amount', 'rate', 'deduction',
]);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $manager->delete((int) $_POST['delete'], $userId);
    header('Location: /taxes.php?deleted=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = in_array($_POST['bracket_type'] ?? '', ['cnss', 'ir'], true) ? $_POST['bracket_type'] : null;
    $data = [
        'bracket_type' => $type,
        'min_amount'   => (float) ($_POST['min_amount'] ?? 0),
        'max_amount'   => $_POST['max_amount'] === '' ? null : (float) $_POST['max_amount'],
        'rate'         => (float) ($_POST['rate'] ?? 0),
        'deduction'    => (float) ($_POST['deduction'] ?? 0),
    ];

    if ($type === null) {
        $errors[] = 'Invalid bracket type.';
    }
    if ($data['rate'] < 0 || $data['rate'] > 100) {
        $errors[] = 'Rate must be between 0 and 100.';
    }

    if (empty($errors)) {
        $manager->create($userId, $data);
        header('Location: /taxes.php?saved=1#' . $type);
        exit;
    }
}

$allBrackets = $manager->getAll($userId);
$cnssBrackets = array_values(array_filter($allBrackets, fn($b) => $b['bracket_type'] === 'cnss'));
$irBrackets   = array_values(array_filter($allBrackets, fn($b) => $b['bracket_type'] === 'ir'));

$pageTitle  = 'Taxes';
$activePage = 'taxes';
require __DIR__ . '/includes/layout_head.php';
require __DIR__ . '/includes/sidebar.php';

function bracketRows(array $brackets, string $type): string
{
    if (empty($brackets)) {
        return '<div class="empty-state"><p>No tax brackets configured. Use the form above to get started.</p></div>';
    }
    $html = '<table class="data-table data-table--stack"><thead><tr><th>From</th><th>To</th><th>Rate</th><th>Deduction</th><th></th></tr></thead><tbody>';
    foreach ($brackets as $b) {
        $to = $b['max_amount'] !== null ? number_format((float) $b['max_amount'], 0) : '∞';
        $html .= '<tr>'
            . '<td data-label="From">' . number_format((float) $b['min_amount'], 0) . ' MAD</td>'
            . '<td data-label="To">' . htmlspecialchars($to) . ($b['max_amount'] !== null ? ' MAD' : '') . '</td>'
            . '<td data-label="Rate">' . number_format((float) $b['rate'], 2) . '%</td>'
            . '<td data-label="Deduction">' . number_format((float) $b['deduction'], 2) . ' MAD</td>'
            . '<td class="cell-actions"><div class="table-actions"><form method="POST" action="/taxes.php" class="inline-delete" data-confirm="Delete this bracket?">' . AuthSecurity::csrfField()
            . '<input type="hidden" name="delete" value="' . (int) $b['id'] . '"><button type="submit" class="nn-btn nn-btn--danger nn-btn--icon" aria-label="Delete" title="Delete"><span class="nn-icon nn-icon--trash" aria-hidden="true"></span></button></form></div></td>'
            . '</tr>';
    }
    return $html . '</tbody></table>';
}
?>
<link rel="stylesheet" href="/assets/css/tools-extra.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/tools-extra.css') ?>">

<main class="main">
  <div class="topbar"><button class="nn-btn nn-btn--primary nn-btn--icon menu-toggle" type="button" aria-controls="appSidebar" aria-expanded="false" aria-label="Open navigation"><span class="nn-icon nn-icon--menu" aria-hidden="true"></span></button></div>

  <div class="page-header">
    <div><h1>💵 Taxes</h1><p>Configure CNSS and Income Tax (IR) brackets used automatically in payroll calculations.</p></div>
  </div>

  <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">✓ Bracket added successfully.</div><?php endif; ?>
  <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">✓ Bracket removed.</div><?php endif; ?>
  <?php if (!empty($errors)): ?><div class="alert alert-error"><?= htmlspecialchars(implode(' ', $errors)) ?></div><?php endif; ?>

  <div class="card" id="cnss" style="margin-bottom:20px;">
    <div class="card-title"><span class="badge-ic">🏥</span>CNSS contribution brackets</div>

    <form method="POST" class="field-row" style="align-items:flex-end;"><?= AuthSecurity::csrfField() ?>
      <input type="hidden" name="bracket_type" value="cnss">
      <div class="field"><label>From (MAD)</label><input type="number" inputmode="decimal" step="0.01" min="0" name="min_amount"></div>
      <div class="field"><label>To (MAD, blank = no limit)</label><input type="number" inputmode="decimal" step="0.01" min="0" name="max_amount"></div>
      <div class="field"><label>Rate (%)</label><input type="number" inputmode="decimal" step="0.01" min="0" max="100" name="rate"></div>
      <div class="field"><label>Deduction (MAD)</label><input type="number" inputmode="decimal" step="0.01" min="0" name="deduction" value="0"></div>
      <div class="field"><button type="submit" class="nn-btn nn-btn--primary"><span class="nn-icon nn-icon--plus" aria-hidden="true"></span>Add</button></div>
    </form>

    <?= bracketRows($cnssBrackets, 'cnss') ?>
  </div>

  <div class="card" id="ir">
    <div class="card-title"><span class="badge-ic">📅</span>Monthly income tax brackets (IR)</div>

    <form method="POST" class="field-row" style="align-items:flex-end;"><?= AuthSecurity::csrfField() ?>
      <input type="hidden" name="bracket_type" value="ir">
      <div class="field"><label>From (MAD)</label><input type="number" inputmode="decimal" step="0.01" min="0" name="min_amount"></div>
      <div class="field"><label>To (MAD, blank = no limit)</label><input type="number" inputmode="decimal" step="0.01" min="0" name="max_amount"></div>
      <div class="field"><label>Rate (%)</label><input type="number" inputmode="decimal" step="0.01" min="0" max="100" name="rate"></div>
      <div class="field"><label>Deduction (MAD)</label><input type="number" inputmode="decimal" step="0.01" min="0" name="deduction" value="0"></div>
      <div class="field"><button type="submit" class="nn-btn nn-btn--primary"><span class="nn-icon nn-icon--plus" aria-hidden="true"></span>Add</button></div>
    </form>

    <?= bracketRows($irBrackets, 'ir') ?>

    <div class="alert" style="background:rgba(79,127,255,0.08); border:1px solid rgba(79,127,255,0.25); color:var(--text-secondary); margin-top:18px;">
      💡 These brackets are automatically used by the Payroll tool. CNSS is applied on monthly gross salary; IR is calculated on the salary after CNSS is deducted.
    </div>
  </div>
</main>

</body>
</html>
