<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/middleware/subscription_guard.php';
require_once __DIR__ . '/classes/SimpleListManager.php';

$userId  = (int) $_SESSION['user_id'];
$manager = new SimpleListManager($pdo, 'products', [
    'name', 'reference', 'category', 'unit_price', 'unit', 'stock_quantity',
]);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $manager->delete((int) $_POST['delete'], $userId);
    header('Location: /products.php?deleted=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name'           => trim($_POST['name'] ?? ''),
        'reference'      => trim($_POST['reference'] ?? ''),
        'category'       => trim($_POST['category'] ?? ''),
        'unit_price'     => (float) ($_POST['unit_price'] ?? 0),
        'unit'           => trim($_POST['unit'] ?? ''),
        'stock_quantity' => (int) ($_POST['stock_quantity'] ?? 0),
    ];

    if ($data['name'] === '') {
        $errors['name'] = 'This field is required.';
    }
    if ($data['unit_price'] < 0) {
        $errors['unit_price'] = 'Price cannot be negative.';
    }

    if (empty($errors)) {
        $editId = (int) ($_POST['id'] ?? 0);
        $editId > 0 ? $manager->update($editId, $userId, $data) : $manager->create($userId, $data);
        header('Location: /products.php?saved=1');
        exit;
    }
}

$editing = isset($_GET['id']) ? $manager->getById((int) $_GET['id'], $userId) : null;
$showForm = isset($_GET['new']) || $editing !== null;
$products = $manager->getAll($userId);

$pageTitle  = 'Products';
$activePage = 'products';
require __DIR__ . '/includes/layout_head.php';
require __DIR__ . '/includes/sidebar.php';
?>
<link rel="stylesheet" href="/assets/css/tools-extra.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/tools-extra.css') ?>">

<main class="main">
  <div class="topbar"><button class="nn-btn nn-btn--primary nn-btn--icon menu-toggle" type="button" aria-controls="appSidebar" aria-expanded="false" aria-label="Open navigation"><span class="nn-icon nn-icon--menu" aria-hidden="true"></span></button></div>

  <?php if ($showForm): ?>
    <div class="back-btn-row"><?php $backHref = '/products.php'; $backLabel = 'Back to products'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/back-button.php'; ?></div>
  <?php endif; ?>

  <div class="page-header">
    <div><h1>📦 Products</h1><p>Manage your product catalog and stock.</p></div>
    <?php if (!$showForm): ?><div class="page-header-actions"><a href="/products.php?new=1" class="nn-btn nn-btn--primary"><span class="nn-icon nn-icon--plus" aria-hidden="true"></span>New product</a></div><?php endif; ?>
  </div>

  <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">✓ Product saved successfully.</div><?php endif; ?>
  <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">✓ Product removed.</div><?php endif; ?>
  <?php if (!empty($errors)): ?><div class="alert alert-error">Please fix the errors below before saving.</div><?php endif; ?>

  <?php if ($showForm): ?>
    <div class="card">
      <div class="card-title"><span class="badge-ic">📦</span><?= $editing ? 'Edit product' : 'New product' ?></div>
      <form method="POST"><?= AuthSecurity::csrfField() ?>
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $editing['id'] ?>"><?php endif; ?>

        <div class="field <?= isset($errors['name']) ? 'invalid' : '' ?>">
          <label>Product name <span class="req">*</span></label>
          <input type="text" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>">
          <span class="hint">This field is required.</span>
        </div>

        <div class="field-row">
          <div class="field">
            <label>Reference</label>
            <input type="text" name="reference" value="<?= htmlspecialchars($editing['reference'] ?? '') ?>">
          </div>
          <div class="field">
            <label>Category</label>
            <input type="text" name="category" value="<?= htmlspecialchars($editing['category'] ?? '') ?>">
          </div>
        </div>

        <div class="field-row">
          <div class="field <?= isset($errors['unit_price']) ? 'invalid' : '' ?>">
            <label>Unit price (MAD)</label>
            <input type="number" inputmode="decimal" step="0.01" min="0" name="unit_price" value="<?= htmlspecialchars((string) ($editing['unit_price'] ?? '0')) ?>">
            <span class="hint">Price cannot be negative.</span>
          </div>
          <div class="field">
            <label>Unit</label>
            <input type="text" name="unit" placeholder="e.g. pcs, kg, box" value="<?= htmlspecialchars($editing['unit'] ?? '') ?>">
          </div>
        </div>

        <div class="field">
          <label>Stock quantity</label>
          <input type="number" inputmode="numeric" min="0" name="stock_quantity" value="<?= htmlspecialchars((string) ($editing['stock_quantity'] ?? '0')) ?>">
        </div>

        <div class="save-row nn-actions nn-actions--end">
          <a href="/products.php" class="nn-btn nn-btn--ghost">Cancel</a>
          <button type="submit" class="nn-btn nn-btn--primary">Save product</button>
        </div>
      </form>
    </div>
  <?php else: ?>
    <div class="card">
      <?php if (empty($products)): ?>
        <div class="empty-state"><div class="big">📦</div><h3>No products yet</h3><p>Add your first product to get started.</p></div>
      <?php else: ?>
        <table class="data-table data-table--stack">
          <thead><tr><th>Name</th><th>Reference</th><th>Category</th><th>Price</th><th>Stock</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
              <td data-label="Name" class="cell-strong"><?= htmlspecialchars($p['name']) ?></td>
              <td data-label="Reference" class="cell-muted"><?= htmlspecialchars($p['reference'] ?: '—') ?></td>
              <td data-label="Category"><?= htmlspecialchars($p['category'] ?: '—') ?></td>
              <td data-label="Price"><?= number_format((float) $p['unit_price'], 2) ?> MAD</td>
              <td data-label="Stock"><?= (int) $p['stock_quantity'] ?> <?= htmlspecialchars($p['unit'] ?: '') ?></td>
              <td class="cell-actions">
                <div class="table-actions">
                  <a class="nn-btn nn-btn--ghost nn-btn--icon" href="/products.php?id=<?= (int) $p['id'] ?>" aria-label="Edit" title="Edit"><span class="nn-icon nn-icon--pen" aria-hidden="true"></span></a>
                  <form method="POST" action="/products.php" class="inline-delete" data-confirm="Delete this product?"><?= AuthSecurity::csrfField() ?><input type="hidden" name="delete" value="<?= (int) $p['id'] ?>"><button type="submit" class="nn-btn nn-btn--danger nn-btn--icon" aria-label="Delete" title="Delete"><span class="nn-icon nn-icon--trash" aria-hidden="true"></span></button></form>
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

</body>
</html>
