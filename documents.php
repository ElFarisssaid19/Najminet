<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/middleware/subscription_guard.php';
require_once __DIR__ . '/classes/DocumentManager.php';

$userId  = (int) $_SESSION['user_id'];
$manager = new DocumentManager($pdo);

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $manager->delete((int) $_POST['delete'], $userId);
    header('Location: /documents.php?deleted=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $title    = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');

    if ($title === '') {
        $errors[] = 'Please give the document a title.';
    }

    if (empty($errors)) {
        $error = $manager->upload($userId, $title, $category, $_FILES['document']);
        if ($error !== null) {
            $errors[] = $error;
        } else {
            header('Location: /documents.php?saved=1');
            exit;
        }
    }
}

if (isset($_GET['saved'])) {
    $success = true;
}

$documents = $manager->getAll($userId);

$pageTitle  = 'Documents';
$activePage = 'documents';
require __DIR__ . '/includes/layout_head.php';
require __DIR__ . '/includes/sidebar.php';
?>
<link rel="stylesheet" href="/assets/css/tools-extra.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/tools-extra.css') ?>">

<main class="main">
  <div class="topbar"><button class="nn-btn nn-btn--primary nn-btn--icon menu-toggle" type="button" aria-controls="appSidebar" aria-expanded="false" aria-label="Open navigation"><span class="nn-icon nn-icon--menu" aria-hidden="true"></span></button></div>

  <div class="page-header">
    <div><h1>📁 Documents</h1><p>Store and organize your business documents.</p></div>
  </div>

  <?php if ($success): ?><div class="alert alert-success">✓ Document uploaded successfully.</div><?php endif; ?>
  <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">✓ Document removed.</div><?php endif; ?>
  <?php if (!empty($errors)): ?><div class="alert alert-error"><?= htmlspecialchars(implode(' ', $errors)) ?></div><?php endif; ?>

  <div class="card" style="margin-bottom:20px;">
    <div class="card-title"><span class="badge-ic">⬆️</span>Upload a document</div>
    <form method="POST" enctype="multipart/form-data"><?= AuthSecurity::csrfField() ?>
      <div class="field-row">
        <div class="field">
          <label>Title <span class="req">*</span></label>
          <input type="text" name="title" placeholder="e.g. Lease agreement">
        </div>
        <div class="field">
          <label>Category</label>
          <input type="text" name="category" placeholder="e.g. Contracts, Legal, Invoices">
        </div>
      </div>
      <div class="upload-box">
        📎 Drop a file or browse — PDF, Word, Excel, or image (10 MB max)
        <br><input type="file" name="document" required>
      </div>
      <div class="save-row nn-actions nn-actions--end" style="margin-top:16px;">
        <button type="submit" class="nn-btn nn-btn--primary">Upload document</button>
      </div>
    </form>
  </div>

  <div class="card">
    <?php if (empty($documents)): ?>
      <div class="empty-state"><div class="big">📁</div><h3>No documents yet</h3><p>Upload your first document above.</p></div>
    <?php else: ?>
      <table class="data-table data-table--stack">
        <thead><tr><th>Title</th><th>Category</th><th>Size</th><th>Uploaded</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($documents as $doc): ?>
          <tr>
            <td data-label="Title" class="cell-strong"><?= htmlspecialchars($doc['title']) ?></td>
            <td data-label="Category"><?= $doc['category'] ? '<span class="badge-blue">' . htmlspecialchars($doc['category']) . '</span>' : '—' ?></td>
            <td data-label="Size" class="cell-muted"><?= DocumentManager::formatSize((int) $doc['file_size']) ?></td>
            <td data-label="Uploaded" class="cell-muted"><?= htmlspecialchars($doc['uploaded_at']) ?></td>
            <td class="cell-actions">
              <div class="table-actions">
                <a class="nn-btn nn-btn--ghost nn-btn--icon" href="/document.php?id=<?= (int) $doc['id'] ?>" aria-label="Download" title="Download"><span class="nn-icon nn-icon--download" aria-hidden="true"></span></a>
                <form method="POST" action="/documents.php" class="inline-delete" data-confirm="Delete this document?"><?= AuthSecurity::csrfField() ?><input type="hidden" name="delete" value="<?= (int) $doc['id'] ?>"><button type="submit" class="nn-btn nn-btn--danger nn-btn--icon" aria-label="Delete" title="Delete"><span class="nn-icon nn-icon--trash" aria-hidden="true"></span></button></form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</main>

</body>
</html>
