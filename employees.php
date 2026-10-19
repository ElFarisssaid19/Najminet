<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/middleware/subscription_guard.php';
require_once __DIR__ . '/classes/SimpleListManager.php';

$userId  = (int) $_SESSION['user_id'];
$manager = new SimpleListManager($pdo, 'employees', [
    'full_name', 'position', 'department', 'email', 'phone', 'salary', 'hire_date', 'status',
]);

$errors  = [];
$success = false;

// ---- Delete ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $manager->delete((int) $_POST['delete'], $userId);
    header('Location: /employees.php?deleted=1');
    exit;
}

// ---- Create / Update ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'full_name'  => trim($_POST['full_name'] ?? ''),
        'position'   => trim($_POST['position'] ?? ''),
        'department' => trim($_POST['department'] ?? ''),
        'email'      => trim($_POST['email'] ?? ''),
        'phone'      => trim($_POST['phone'] ?? ''),
        'salary'     => (float) ($_POST['salary'] ?? 0),
        'hire_date'  => $_POST['hire_date'] ?: null,
        'status'     => in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'active',
    ];

    if ($data['full_name'] === '') {
        $errors['full_name'] = 'This field is required.';
    }
    if ($data['salary'] < 0) {
        $errors['salary'] = 'Salary cannot be negative.';
    }

    if (empty($errors)) {
        $editId = (int) ($_POST['id'] ?? 0);
        if ($editId > 0) {
            $manager->update($editId, $userId, $data);
        } else {
            $manager->create($userId, $data);
        }
        header('Location: /employees.php?saved=1');
        exit;
    }
}

$editing = null;
if (isset($_GET['id'])) {
    $editing = $manager->getById((int) $_GET['id'], $userId);
}
$showForm = isset($_GET['new']) || $editing !== null;

$employees = $manager->getAll($userId);

$pageTitle  = 'Employees';
$activePage = 'employees';
require __DIR__ . '/includes/layout_head.php';
require __DIR__ . '/includes/sidebar.php';
?>
<link rel="stylesheet" href="/assets/css/tools-extra.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/tools-extra.css') ?>">

<main class="main">
  <div class="topbar"><button class="nn-btn nn-btn--primary nn-btn--icon menu-toggle" type="button" aria-controls="appSidebar" aria-expanded="false" aria-label="Open navigation"><span class="nn-icon nn-icon--menu" aria-hidden="true"></span></button></div>

  <?php if ($showForm): ?>
    <div class="back-btn-row"><?php $backHref = '/employees.php'; $backLabel = 'Back to employees'; include $_SERVER['DOCUMENT_ROOT'] . '/includes/back-button.php'; ?></div>
  <?php endif; ?>

  <div class="page-header">
    <div>
      <h1>👥 Employees</h1>
      <p>Manage your team members and their information.</p>
    </div>
    <?php if (!$showForm): ?>
      <div class="page-header-actions">
        <a href="/employees.php?new=1" class="nn-btn nn-btn--primary"><span class="nn-icon nn-icon--plus" aria-hidden="true"></span>New employee</a>
      </div>
    <?php endif; ?>
  </div>

  <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">✓ Employee saved successfully.</div><?php endif; ?>
  <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">✓ Employee removed.</div><?php endif; ?>
  <?php if (!empty($errors)): ?><div class="alert alert-error">Please fix the errors below before saving.</div><?php endif; ?>

  <?php if ($showForm): ?>
    <div class="card">
      <div class="card-title"><span class="badge-ic">👤</span><?= $editing ? 'Edit employee' : 'New employee' ?></div>
      <form method="POST"><?= AuthSecurity::csrfField() ?>
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $editing['id'] ?>"><?php endif; ?>

        <div class="field-row">
          <div class="field <?= isset($errors['full_name']) ? 'invalid' : '' ?>">
            <label>Full name <span class="req">*</span></label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($editing['full_name'] ?? '') ?>">
            <span class="hint">This field is required.</span>
          </div>
          <div class="field">
            <label>Status</label>
            <input type="text" name="status" list="statusOptions" value="<?= htmlspecialchars($editing['status'] ?? 'active') ?>">
            <datalist id="statusOptions"><option value="active"><option value="inactive"></datalist>
          </div>
        </div>

        <div class="field-row">
          <div class="field">
            <label>Position</label>
            <input type="text" name="position" placeholder="e.g. Accountant" value="<?= htmlspecialchars($editing['position'] ?? '') ?>">
          </div>
          <div class="field">
            <label>Department</label>
            <input type="text" name="department" placeholder="e.g. Finance" value="<?= htmlspecialchars($editing['department'] ?? '') ?>">
          </div>
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

        <div class="field-row">
          <div class="field <?= isset($errors['salary']) ? 'invalid' : '' ?>">
            <label>Monthly salary (MAD)</label>
            <input type="number" inputmode="decimal" step="0.01" min="0" name="salary" value="<?= htmlspecialchars((string) ($editing['salary'] ?? '0')) ?>">
            <span class="hint">Salary cannot be negative.</span>
          </div>
          <div class="field">
            <label>Hire date</label>
            <input type="date" name="hire_date" value="<?= htmlspecialchars($editing['hire_date'] ?? '') ?>">
          </div>
        </div>

        <div class="save-row nn-actions nn-actions--end">
          <a href="/employees.php" class="nn-btn nn-btn--ghost">Cancel</a>
          <button type="submit" class="nn-btn nn-btn--primary">Save employee</button>
        </div>
      </form>
    </div>
  <?php else: ?>
    <div class="card">
      <?php if (empty($employees)): ?>
        <div class="empty-state">
          <div class="big">👥</div>
          <h3>No employees yet</h3>
          <p>Add your first team member to get started.</p>
        </div>
      <?php else: ?>
        <table class="data-table data-table--stack">
          <thead><tr><th>Name</th><th>Position</th><th>Department</th><th>Salary</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($employees as $emp): ?>
            <tr>
              <td data-label="Name" class="cell-strong"><?= htmlspecialchars($emp['full_name']) ?></td>
              <td data-label="Position"><?= htmlspecialchars($emp['position'] ?: '—') ?></td>
              <td data-label="Department"><?= htmlspecialchars($emp['department'] ?: '—') ?></td>
              <td data-label="Salary"><?= number_format((float) $emp['salary'], 0) ?> MAD</td>
              <td data-label="Status"><span class="<?= $emp['status'] === 'active' ? 'status paid' : 'badge-neutral' ?>"><?= ucfirst($emp['status']) ?></span></td>
              <td class="cell-actions">
                <div class="table-actions">
                  <a class="nn-btn nn-btn--ghost nn-btn--icon" href="/employees.php?id=<?= (int) $emp['id'] ?>" aria-label="Edit" title="Edit"><span class="nn-icon nn-icon--pen" aria-hidden="true"></span></a>
                  <form method="POST" action="/employees.php" class="inline-delete" data-confirm="Delete this employee?"><?= AuthSecurity::csrfField() ?><input type="hidden" name="delete" value="<?= (int) $emp['id'] ?>"><button type="submit" class="nn-btn nn-btn--danger nn-btn--icon" aria-label="Delete" title="Delete"><span class="nn-icon nn-icon--trash" aria-hidden="true"></span></button></form>
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
