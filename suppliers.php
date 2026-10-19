<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/middleware/subscription_guard.php';
require_once __DIR__ . '/classes/SimpleListManager.php';

$userId  = (int) $_SESSION['user_id'];
$manager = new SimpleListManager($pdo, 'suppliers', [
    'name', 'contact_person', 'email', 'phone', 'address',
]);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $manager->delete((int) $_POST['delete'], $userId);
    header('Location: /suppliers.php?deleted=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name'           => trim($_POST['name'] ?? ''),
        'contact_person' => trim($_POST['contact_person'] ?? ''),
        'email'          => trim($_POST['email'] ?? ''),
        'phone'          => trim($_POST['phone'] ?? ''),
        'address'        => trim($_POST['address'] ?? ''),
    ];

    if ($data['name'] === '') {
        $errors['name'] = 'This field is required.';
    }

    if (empty($errors)) {
        $editId = (int) ($_POST['id'] ?? 0);
        $editId > 0 ? $manager->update($editId, $userId, $data) : $manager->create($userId, $data);
        header('Location: /suppliers.php?saved=1');
        exit;
    }
}

$editing = isset($_GET['id']) ? $manager->getById((int) $_GET['id'], $userId) : null;
$showForm = isset($_GET['new']) || $editing !== null;
$suppliers = $manager->getAll($userId);

$pageTitle  = 'Suppliers';
$activePage = 'suppliers';
require __DIR__ . '/includes/layout_head.php';
require __DIR__ . '/includes/sidebar.php';
?>
<link rel="stylesheet" href="/assets/css/tools-extra.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/tools-extra.css') ?>">

<main class="main">
  <div class="topbar"><button class="nn-btn nn-btn--primary nn-btn--icon menu-toggle" type="button" aria-controls="appSidebar" aria-expanded="false" aria-label="Open navigation"><span class="nn-icon nn-icon--menu" aria-hidden="true"></span></button></div>

  <?php if ($showForm): ?>
    <div class="back-btn-row"><?php $backHref = '/suppliers.php'; $backLabel = 'Back to suppliers'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/back-button.php'; ?></div>
  <?php endif; ?>

  <div class="page-header">
    <div><h1>🏬 Suppliers</h1><p>Manage your suppliers and their contact information.</p></div>
    <?php if (!$showForm): ?><div class="page-header-actions"><a href="/suppliers.php?new=1" class="nn-btn nn-btn--primary"><span class="nn-icon nn-icon--plus" aria-hidden="true"></span>New supplier</a></div><?php endif; ?>
  </div>

  <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">✓ Supplier saved successfully.</div><?php endif; ?>
  <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">✓ Supplier removed.</div><?php endif; ?>
  <?php if (!empty($errors)): ?><div class="alert alert-error">Please fix the errors below before saving.</div><?php endif; ?>

  <?php if ($showForm): ?>
    <div class="card">
      <div class="card-title"><span class="badge-ic">🏬</span><?= $editing ? 'Edit supplier' : 'New supplier' ?></div>
      <form method="POST"><?= AuthSecurity::csrfField() ?>
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $editing['id'] ?>"><?php endif; ?>

        <div class="field <?= isset($errors['name']) ? 'invalid' : '' ?>">
          <label>Supplier name <span class="req">*</span></label>
          <input type="text" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>">
          <span class="hint">This field is required.</span>
        </div>

        <div class="field">
          <label>Contact person</label>
          <input type="text" name="contact_person" value="<?= htmlspecialchars($editing['contact_person'] ?? '') ?>">
        </div>

        <div class="field-row">
          <div class="field">
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($editing['email'] ?? '') ?>">
          </div>
          <div class="field">
            <label>Phone</label>
            <input type="tel" name="phone" autocomplete="tel" value="<?= htmlspecialchars($editing['phone'] ?? '') ?>">
          </div>
        </div>

        <div class="field">
          <label>Address</label>
          <input type="text" name="address" value="<?= htmlspecialchars($editing['address'] ?? '') ?>">
        </div>

        <div class="save-row nn-actions nn-actions--end">
          <a href="/suppliers.php" class="nn-btn nn-btn--ghost">Cancel</a>
          <button type="submit" class="nn-btn nn-btn--primary">Save supplier</button>
        </div>
      </form>
    </div>
  <?php else: ?>
    <div class="card">
      <?php if (empty($suppliers)): ?>
        <div class="empty-state"><div class="big">🏬</div><h3>No suppliers yet</h3><p>Add your first supplier to get started.</p></div>
      <?php else: ?>
        <table class="data-table data-table--stack">
          <thead><tr><th>Name</th><th>Contact</th><th>Email</th><th>Phone</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($suppliers as $s): ?>
            <tr>
              <td data-label="Name" class="cell-strong"><?= htmlspecialchars($s['name']) ?></td>
              <td data-label="Contact"><?= htmlspecialchars($s['contact_person'] ?: '—') ?></td>
              <td data-label="Email" class="cell-muted"><?= htmlspecialchars($s['email'] ?: '—') ?></td>
              <td data-label="Phone" class="cell-muted"><?= htmlspecialchars($s['phone'] ?: '—') ?></td>
              <td class="cell-actions">
                <div class="table-actions">
                  <a class="nn-btn nn-btn--ghost nn-btn--icon" href="/suppliers.php?id=<?= (int) $s['id'] ?>" aria-label="Edit" title="Edit"><span class="nn-icon nn-icon--pen" aria-hidden="true"></span></a>
                  <form method="POST" action="/suppliers.php" class="inline-delete" data-confirm="Delete this supplier?"><?= AuthSecurity::csrfField() ?><input type="hidden" name="delete" value="<?= (int) $s['id'] ?>"><button type="submit" class="nn-btn nn-btn--danger nn-btn--icon" aria-label="Delete" title="Delete"><span class="nn-icon nn-icon--trash" aria-hidden="true"></span></button></form>
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
