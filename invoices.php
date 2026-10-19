<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/middleware/subscription_guard.php';
require_once __DIR__ . '/classes/InvoiceManager.php';

$userId  = (int) $_SESSION['user_id'];
$manager = new InvoiceManager($pdo);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $manager->delete((int) $_POST['delete'], $userId);
    header('Location: /invoices.php?deleted=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clientName = trim($_POST['client_name'] ?? '');
    $issueDate  = $_POST['issue_date'] ?? null;
    $dueDate    = $_POST['due_date'] ?? null;
    $status     = in_array($_POST['status'] ?? '', ['draft', 'pending', 'paid', 'overdue'], true) ? $_POST['status'] : 'draft';
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
            $manager->update($editId, $userId, $clientName, $issueDate, $dueDate, $items, $status);
        } else {
            $number = $manager->nextInvoiceNumber($userId);
            $manager->create($userId, $number, $clientName, $issueDate, $dueDate, $items, $status);
        }
        header('Location: /invoices.php?saved=1');
        exit;
    }
}

$editing  = isset($_GET['id']) ? $manager->getById((int) $_GET['id'], $userId) : null;
$showForm = isset($_GET['new']) || $editing !== null;
$invoices = $manager->getAll($userId);
$nextNumber = $editing['invoice_number'] ?? $manager->nextInvoiceNumber($userId);

function invoiceStatusClass(string $status): string
{
    return match ($status) { 'paid' => 'status paid', 'pending' => 'status pending', 'overdue' => 'status overdue', default => 'badge-neutral' };
}

$pageTitle  = 'Invoices';
$activePage = 'invoices';
require __DIR__ . '/includes/layout_head.php';
require __DIR__ . '/includes/sidebar.php';
?>
<link rel="stylesheet" href="/assets/css/tools-extra.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/tools-extra.css') ?>">

<main class="main">
  <div class="topbar"><button class="nn-btn nn-btn--primary nn-btn--icon menu-toggle" type="button" aria-controls="appSidebar" aria-expanded="false" aria-label="Open navigation"><span class="nn-icon nn-icon--menu" aria-hidden="true"></span></button></div>

  <?php if ($showForm): ?>
    <div class="back-btn-row"><?php $backHref = '/invoices.php'; $backLabel = 'Back to invoices'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/back-button.php'; ?></div>
  <?php endif; ?>

  <div class="page-header">
    <div><h1>📄 Invoices</h1><p>Create and track client invoices.</p></div>
    <?php if (!$showForm): ?><div class="page-header-actions"><a href="/invoices.php?new=1" class="nn-btn nn-btn--primary"><span class="nn-icon nn-icon--plus" aria-hidden="true"></span>New invoice</a></div><?php endif; ?>
  </div>

  <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">✓ Invoice saved successfully.</div><?php endif; ?>
  <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">✓ Invoice removed.</div><?php endif; ?>
  <?php if (!empty($errors)): ?><div class="alert alert-error"><?= htmlspecialchars(implode(' ', $errors)) ?></div><?php endif; ?>

  <?php if ($showForm):
    $items = $editing['items_data'] ?? [['description' => '', 'quantity' => 1, 'unit_price' => 0]];
    if (empty($items)) { $items = [['description' => '', 'quantity' => 1, 'unit_price' => 0]]; }
  ?>
    <form method="POST" id="invoiceForm"><?= AuthSecurity::csrfField() ?>
      <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $editing['id'] ?>"><?php endif; ?>
      <div class="card" style="margin-bottom:20px;">
        <div class="card-title"><span class="badge-ic">📄</span><?= $editing ? 'Edit invoice ' . htmlspecialchars($nextNumber) : 'New invoice — ' . htmlspecialchars($nextNumber) ?></div>

        <div class="field-row">
          <div class="field">
            <label>Client name <span class="req">*</span></label>
            <input type="text" name="client_name" value="<?= htmlspecialchars($editing['client_name'] ?? '') ?>">
          </div>
          <div class="field">
            <label>Status</label>
            <input type="text" name="status" list="invStatus" value="<?= htmlspecialchars($editing['status'] ?? 'draft') ?>">
            <datalist id="invStatus"><option value="draft"><option value="pending"><option value="paid"><option value="overdue"></datalist>
          </div>
        </div>

        <div class="field-row">
          <div class="field">
            <label>Issue date</label>
            <input type="date" name="issue_date" value="<?= htmlspecialchars($editing['issue_date'] ?? '') ?>">
          </div>
          <div class="field">
            <label>Due date</label>
            <input type="date" name="due_date" value="<?= htmlspecialchars($editing['due_date'] ?? '') ?>">
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
            <span class="item-cell" data-label="Qty"><input type="number" inputmode="decimal" step="0.01" min="0" name="item_quantity[]" aria-label="Quantity" value="<?= htmlspecialchars((string) $item['quantity']) ?>" oninput="recalcInvoice()"></span>
            <span class="item-cell" data-label="Unit price"><input type="number" inputmode="decimal" step="0.01" min="0" name="item_price[]" aria-label="Unit price" value="<?= htmlspecialchars((string) $item['unit_price']) ?>" oninput="recalcInvoice()"></span>
            <span class="item-cell" data-label="Total"><span class="line-total">0.00 MAD</span></span>
            <button type="button" class="nn-btn nn-btn--danger nn-btn--icon remove-row" aria-label="Remove line" onclick="this.closest('.item-row').remove(); recalcInvoice();"><span class="nn-icon nn-icon--trash" aria-hidden="true"></span></button>
          </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="nn-btn nn-btn--secondary add-row-btn" onclick="addInvoiceRow()"><span class="nn-icon nn-icon--plus" aria-hidden="true"></span>Add line item</button>
        <div class="balance-total assets"><span>Total</span><span class="val" id="invoiceTotal">0.00 MAD</span></div>
      </div>

      <div class="save-row nn-actions nn-actions--end" style="margin-top:16px;">
        <a href="/invoices.php" class="nn-btn nn-btn--ghost">Cancel</a>
        <button type="submit" class="nn-btn nn-btn--primary">Save invoice</button>
      </div>
    </form>
  <?php else: ?>
    <div class="card">
      <?php if (empty($invoices)): ?>
        <div class="empty-state"><div class="big">📄</div><h3>No invoices yet</h3><p>Create your first invoice to get started.</p></div>
      <?php else: ?>
        <table class="data-table data-table--stack">
          <thead><tr><th>#</th><th>Client</th><th>Due date</th><th>Total</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($invoices as $inv): ?>
            <tr>
              <td data-label="#" class="cell-strong"><?= htmlspecialchars($inv['invoice_number']) ?></td>
              <td data-label="Client"><?= htmlspecialchars($inv['client_name']) ?></td>
              <td data-label="Due date" class="cell-muted"><?= htmlspecialchars($inv['due_date'] ?: '—') ?></td>
              <td data-label="Total"><?= number_format((float) $inv['total_amount'], 2) ?> MAD</td>
              <td data-label="Status"><span class="<?= invoiceStatusClass($inv['status']) ?>"><?= ucfirst($inv['status']) ?></span></td>
              <td class="cell-actions">
                <div class="table-actions">
                  <a class="nn-btn nn-btn--ghost nn-btn--icon" href="/invoices.php?id=<?= (int) $inv['id'] ?>" aria-label="Edit" title="Edit"><span class="nn-icon nn-icon--pen" aria-hidden="true"></span></a>
                  <form method="POST" action="/invoices.php" class="inline-delete" data-confirm="Delete this invoice?"><?= AuthSecurity::csrfField() ?><input type="hidden" name="delete" value="<?= (int) $inv['id'] ?>"><button type="submit" class="nn-btn nn-btn--danger nn-btn--icon" aria-label="Delete" title="Delete"><span class="nn-icon nn-icon--trash" aria-hidden="true"></span></button></form>
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
function addInvoiceRow() {
  const container = document.getElementById('itemsContainer');
  const row = document.createElement('div');
  row.className = 'item-row';
  row.innerHTML = `
    <input type="text" name="item_description[]" aria-label="Description" placeholder="Item description">
    <span class="item-cell" data-label="Qty"><input type="number" inputmode="decimal" step="0.01" min="0" name="item_quantity[]" aria-label="Quantity" value="1" oninput="recalcInvoice()"></span>
    <span class="item-cell" data-label="Unit price"><input type="number" inputmode="decimal" step="0.01" min="0" name="item_price[]" aria-label="Unit price" value="0" oninput="recalcInvoice()"></span>
    <span class="item-cell" data-label="Total"><span class="line-total">0.00 MAD</span></span>
    <button type="button" class="nn-btn nn-btn--danger nn-btn--icon remove-row" aria-label="Remove line" onclick="this.closest('.item-row').remove(); recalcInvoice();"><span class="nn-icon nn-icon--trash" aria-hidden="true"></span></button>
  `;
  container.appendChild(row);
}

function recalcInvoice() {
  let total = 0;
  document.querySelectorAll('#itemsContainer .item-row').forEach(row => {
    const qty = parseFloat(row.querySelector('[name="item_quantity[]"]').value) || 0;
    const price = parseFloat(row.querySelector('[name="item_price[]"]').value) || 0;
    const lineTotal = qty * price;
    row.querySelector('.line-total').textContent = lineTotal.toFixed(2) + ' MAD';
    total += lineTotal;
  });
  const totalEl = document.getElementById('invoiceTotal');
  if (totalEl) totalEl.textContent = total.toFixed(2) + ' MAD';
}
document.addEventListener('DOMContentLoaded', recalcInvoice);
</script>

</body>
</html>
