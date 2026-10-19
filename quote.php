<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/middleware/subscription_guard.php';
require_once __DIR__ . '/classes/QuoteManager.php';

$userId  = (int) $_SESSION['user_id'];
$manager = new QuoteManager($pdo);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $manager->delete((int) $_POST['delete'], $userId);
    header('Location: /quote.php?deleted=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientName = trim($_POST['client_name'] ?? '');
    $status     = in_array($_POST['status'] ?? '', ['draft', 'sent', 'accepted', 'rejected'], true) ? $_POST['status'] : 'draft';
    $editId     = (int) ($_POST['id'] ?? 0);

    $descriptions = $_POST['item_description'] ?? [];
    $quantities   = $_POST['item_quantity'] ?? [];
    $prices       = $_POST['item_price'] ?? [];
    $items = [];
    foreach ($descriptions as $i => $desc) {
        $items[] = ['description' => $desc, 'quantity' => $quantities[$i] ?? 0, 'unit_price' => $prices[$i] ?? 0];
    }

    if ($clientName === '') {
        $errors[] = 'Client name is required.';
    }

    if (empty($errors)) {
        if ($editId > 0) {
            $manager->update($editId, $userId, $clientName, $items, $status);
        } else {
            $number = $manager->nextQuoteNumber($userId);
            $manager->create($userId, $number, $clientName, $items, $status);
        }
        header('Location: /quote.php?saved=1');
        exit;
    }
}

$editing  = isset($_GET['id']) ? $manager->getById((int) $_GET['id'], $userId) : null;
$showForm = isset($_GET['new']) || $editing !== null;
$quotes   = $manager->getAll($userId);
$nextNumber = $editing['quote_number'] ?? $manager->nextQuoteNumber($userId);

$pageTitle  = 'Quote';
$activePage = 'quote';
require __DIR__ . '/includes/layout_head.php';
require __DIR__ . '/includes/sidebar.php';
?>
<link rel="stylesheet" href="/assets/css/tools-extra.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/tools-extra.css') ?>">

<main class="main">
  <div class="topbar"><button class="nn-btn nn-btn--primary nn-btn--icon menu-toggle" type="button" aria-controls="appSidebar" aria-expanded="false" aria-label="Open navigation"><span class="nn-icon nn-icon--menu" aria-hidden="true"></span></button></div>

  <?php if ($showForm): ?>
    <div class="back-btn-row"><?php $backHref = '/quote.php'; $backLabel = 'Back to quotes'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/back-button.php'; ?></div>
  <?php endif; ?>

  <div class="page-header">
    <div><h1>📝 Quote</h1><p>Create and manage client quotes.</p></div>
    <?php if (!$showForm): ?><div class="page-header-actions"><a href="/quote.php?new=1" class="nn-btn nn-btn--primary"><span class="nn-icon nn-icon--plus" aria-hidden="true"></span>New quote</a></div><?php endif; ?>
  </div>

  <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">✓ Quote saved successfully.</div><?php endif; ?>
  <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">✓ Quote removed.</div><?php endif; ?>
  <?php if (!empty($errors)): ?><div class="alert alert-error"><?= htmlspecialchars(implode(' ', $errors)) ?></div><?php endif; ?>

  <?php if ($showForm):
    $items = $editing['items_data'] ?? [['description' => '', 'quantity' => 1, 'unit_price' => 0]];
    if (empty($items)) { $items = [['description' => '', 'quantity' => 1, 'unit_price' => 0]]; }
  ?>
    <form method="POST" id="quoteForm"><?= AuthSecurity::csrfField() ?>
      <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $editing['id'] ?>"><?php endif; ?>
      <div class="card" style="margin-bottom:20px;">
        <div class="card-title"><span class="badge-ic">📝</span><?= $editing ? 'Edit quote ' . htmlspecialchars($nextNumber) : 'New quote — ' . htmlspecialchars($nextNumber) ?></div>

        <div class="field-row">
          <div class="field">
            <label>Client name <span class="req">*</span></label>
            <input type="text" name="client_name" value="<?= htmlspecialchars($editing['client_name'] ?? '') ?>">
          </div>
          <div class="field">
            <label>Status</label>
            <input type="text" name="status" list="quoteStatus" value="<?= htmlspecialchars($editing['status'] ?? 'draft') ?>">
            <datalist id="quoteStatus"><option value="draft"><option value="sent"><option value="accepted"><option value="rejected"></datalist>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-title"><span class="badge-ic">📋</span>Line items</div>
        <div class="item-headers"><div>Description</div><div>Qty</div><div>Unit price</div><div>Total</div><div></div></div>
        <div id="itemsContainer">
          <?php foreach ($items as $item): ?>
          <div class="item-row">
            <input type="text" name="item_description[]" aria-label="Description" placeholder="Item description" value="<?= htmlspecialchars($item['description']) ?>">
            <span class="item-cell" data-label="Qty"><input type="number" inputmode="decimal" step="0.01" min="0" name="item_quantity[]" aria-label="Quantity" value="<?= htmlspecialchars((string) $item['quantity']) ?>" oninput="recalcQuote()"></span>
            <span class="item-cell" data-label="Unit price"><input type="number" inputmode="decimal" step="0.01" min="0" name="item_price[]" aria-label="Unit price" value="<?= htmlspecialchars((string) $item['unit_price']) ?>" oninput="recalcQuote()"></span>
            <span class="item-cell" data-label="Total"><span class="line-total">0.00 MAD</span></span>
            <button type="button" class="nn-btn nn-btn--danger nn-btn--icon remove-row" aria-label="Remove line" onclick="this.closest('.item-row').remove(); recalcQuote();"><span class="nn-icon nn-icon--trash" aria-hidden="true"></span></button>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="nn-btn nn-btn--secondary add-row-btn" onclick="addItemRow()"><span class="nn-icon nn-icon--plus" aria-hidden="true"></span>Add line item</button>
        <div class="balance-total assets"><span>Total</span><span class="val" id="quoteTotal">0.00 MAD</span></div>
      </div>

      <div class="save-row nn-actions nn-actions--end" style="margin-top:16px;">
        <a href="/quote.php" class="nn-btn nn-btn--ghost">Cancel</a>
        <button type="submit" class="nn-btn nn-btn--primary">Save quote</button>
      </div>
    </form>
  <?php else: ?>
    <div class="card">
      <?php if (empty($quotes)): ?>
        <div class="empty-state"><div class="big">📝</div><h3>No quotes yet</h3><p>Create your first quote to get started.</p></div>
      <?php else: ?>
        <table class="data-table data-table--stack">
          <thead><tr><th>#</th><th>Client</th><th>Total</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($quotes as $q): ?>
            <tr>
              <td data-label="#" class="cell-strong"><?= htmlspecialchars($q['quote_number']) ?></td>
              <td data-label="Client"><?= htmlspecialchars($q['client_name']) ?></td>
              <td data-label="Total"><?= number_format((float) $q['total_amount'], 2) ?> MAD</td>
              <td data-label="Status"><span class="badge-purple"><?= ucfirst($q['status']) ?></span></td>
              <td class="cell-actions">
                <div class="table-actions">
                  <a class="nn-btn nn-btn--ghost nn-btn--icon" href="/quote.php?id=<?= (int) $q['id'] ?>" aria-label="Edit" title="Edit"><span class="nn-icon nn-icon--pen" aria-hidden="true"></span></a>
                  <form method="POST" action="/quote.php" class="inline-delete" data-confirm="Delete this quote?"><?= AuthSecurity::csrfField() ?><input type="hidden" name="delete" value="<?= (int) $q['id'] ?>"><button type="submit" class="nn-btn nn-btn--danger nn-btn--icon" aria-label="Delete" title="Delete"><span class="nn-icon nn-icon--trash" aria-hidden="true"></span></button></form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</main>

<script>
function addItemRow() {
  const container = document.getElementById('itemsContainer');
  const row = document.createElement('div');
  row.className = 'item-row';
  row.innerHTML = `
    <input type="text" name="item_description[]" aria-label="Description" placeholder="Item description">
    <span class="item-cell" data-label="Qty"><input type="number" inputmode="decimal" step="0.01" min="0" name="item_quantity[]" aria-label="Quantity" value="1" oninput="recalcQuote()"></span>
    <span class="item-cell" data-label="Unit price"><input type="number" inputmode="decimal" step="0.01" min="0" name="item_price[]" aria-label="Unit price" value="0" oninput="recalcQuote()"></span>
    <span class="item-cell" data-label="Total"><span class="line-total">0.00 MAD</span></span>
    <button type="button" class="nn-btn nn-btn--danger nn-btn--icon remove-row" aria-label="Remove line" onclick="this.closest('.item-row').remove(); recalcQuote();"><span class="nn-icon nn-icon--trash" aria-hidden="true"></span></button>
  `;
  container.appendChild(row);
}

function recalcQuote() {
  let total = 0;
  document.querySelectorAll('#itemsContainer .item-row').forEach(row => {
    const qty = parseFloat(row.querySelector('[name="item_quantity[]"]').value) || 0;
    const price = parseFloat(row.querySelector('[name="item_price[]"]').value) || 0;
    const lineTotal = qty * price;
    row.querySelector('.line-total').textContent = lineTotal.toFixed(2) + ' MAD';
    total += lineTotal;
  });
  const totalEl = document.getElementById('quoteTotal');
  if (totalEl) totalEl.textContent = total.toFixed(2) + ' MAD';
}
document.addEventListener('DOMContentLoaded', recalcQuote);
</script>

</body>
</html>
