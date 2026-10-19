<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/middleware/subscription_guard.php';
require_once __DIR__ . '/classes/SimpleListManager.php';

$userId = (int) $_SESSION['user_id'];

$sections = [
    'positions'      => ['label' => 'Positions',      'icon' => '💼', 'desc' => 'Manage job positions and titles in your organization.'],
    'departments'    => ['label' => 'Departments',     'icon' => '🏢', 'desc' => 'Organize your company structure with departments.'],
    'contract_types' => ['label' => 'Contract types',  'icon' => '📋', 'desc' => 'Define employee contract types and terms.'],
];

$managers = [];
foreach ($sections as $table => $meta) {
    $managers[$table] = new SimpleListManager($pdo, $table, ['name', 'description']);
}

// ---- Delete ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'], $_POST['table']) && array_key_exists($_POST['table'], $sections)) {
    $managers[$_POST['table']]->delete((int) $_POST['delete'], $userId);
    header('Location: /admin.php?deleted=1#' . $_POST['table']);
    exit;
}

// ---- Create ----
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $table = $_POST['table'] ?? '';
    $name  = trim($_POST['name'] ?? '');

    if (!array_key_exists($table, $sections)) {
        $errors[] = 'Invalid section.';
    } elseif ($name === '') {
        $errors[] = 'Name is required.';
    }

    if (empty($errors)) {
        $managers[$table]->create($userId, [
            'name'        => $name,
            'description' => trim($_POST['description'] ?? ''),
        ]);
        header('Location: /admin.php?saved=1#' . $table);
        exit;
    }
}

$pageTitle  = 'Admin';
$activePage = 'admin';
require __DIR__ . '/includes/layout_head.php';
require __DIR__ . '/includes/sidebar.php';
?>
<link rel="stylesheet" href="/assets/css/tools-extra.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/tools-extra.css') ?>">

<main class="main">
  <div class="topbar"><button class="nn-btn nn-btn--primary nn-btn--icon menu-toggle" type="button" aria-controls="appSidebar" aria-expanded="false" aria-label="Open navigation"><span class="nn-icon nn-icon--menu" aria-hidden="true"></span></button></div>

  <div class="page-header">
    <div><h1>⚙️ Admin dashboard</h1><p>Manage your system settings, organization structure, and reference data.</p></div>
  </div>

  <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">✓ Saved successfully.</div><?php endif; ?>
  <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">✓ Removed successfully.</div><?php endif; ?>
  <?php if (!empty($errors)): ?><div class="alert alert-error"><?= htmlspecialchars(implode(' ', $errors)) ?></div><?php endif; ?>

  <div class="card-title" style="margin:6px 0 16px;"><span class="badge-ic">🏗️</span>Organization management</div>

  <div class="admin-sections">
    <?php foreach ($sections as $table => $meta):
      $items = $managers[$table]->getAll($userId); ?>
      <div class="card" id="<?= $table ?>">
        <div class="card-title"><span class="badge-ic"><?= $meta['icon'] ?></span><?= htmlspecialchars($meta['label']) ?></div>
        <p style="color:var(--text-secondary); font-size:12.5px; margin:-10px 0 16px;"><?= htmlspecialchars($meta['desc']) ?></p>

        <form method="POST" class="inline-form"><?= AuthSecurity::csrfField() ?>
          <input type="hidden" name="table" value="<?= $table ?>">
          <input type="text" name="name" placeholder="Add new…" required>
          <button type="submit" class="nn-btn nn-btn--primary"><span class="nn-icon nn-icon--plus" aria-hidden="true"></span>Add</button>
        </form>

        <?php if (empty($items)): ?>
          <p style="color:var(--text-muted); font-size:12.5px;">Nothing here yet.</p>
        <?php else: ?>
          <?php foreach ($items as $item): ?>
            <div class="simple-list-row">
              <span><?= htmlspecialchars($item['name']) ?></span>
              <form method="POST" action="/admin.php" class="inline-delete" data-confirm="Delete this item?"><?= AuthSecurity::csrfField() ?>
                <input type="hidden" name="table" value="<?= $table ?>"><input type="hidden" name="delete" value="<?= (int) $item['id'] ?>">
                <button type="submit" class="nn-btn nn-btn--danger nn-btn--icon" aria-label="Delete" title="Delete"><span class="nn-icon nn-icon--trash" aria-hidden="true"></span></button>
              </form>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</main>

</body>
</html>
