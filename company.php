<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/includes/db.php';                     // exposes $pdo
require_once __DIR__ . '/middleware/subscription_guard.php';
require_once __DIR__ . '/classes/CompanyManager.php';

$userId  = (int) $_SESSION['user_id'];
$manager = new CompanyManager($pdo);

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'company_name'        => trim($_POST['company_name'] ?? ''),
        'legal_name'          => trim($_POST['legal_name'] ?? ''),
        'description'         => trim($_POST['description'] ?? ''),
        'vat_number'          => trim($_POST['vat_number'] ?? ''),
        'registration_number' => trim($_POST['registration_number'] ?? ''),
        'cie_number'          => trim($_POST['cie_number'] ?? ''),
        'bank_name'           => trim($_POST['bank_name'] ?? ''),
        'bank_account_number' => trim($_POST['bank_account_number'] ?? ''),
    ];

    // Server-side validation — Company Name is the only required field.
    if ($data['company_name'] === '') {
        $errors['company_name'] = 'This field is required.';
    }
    if (mb_strlen($data['company_name']) > 150) {
        $errors['company_name'] = 'Company name is too long (150 characters max).';
    }

    if (empty($errors)) {
        $manager->save($userId, $data);
        $success = true;
    }
}

// Reload current data (fresh from DB after save, or existing values on first load)
$company = $manager->getByUser($userId) ?? [
    'company_name' => '', 'legal_name' => '', 'description' => '',
    'vat_number' => '', 'registration_number' => '', 'cie_number' => '',
    'bank_name' => '', 'bank_account_number' => '',
];

// On a failed POST, keep what the user just typed instead of the stale DB copy
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$success) {
    $company = array_merge($company, $data);
}

$pageTitle  = 'Edit Company Information';
$activePage = 'company';
require __DIR__ . '/includes/layout_head.php';
require __DIR__ . '/includes/sidebar.php';
?>

<main class="main">
  <div class="topbar">
    <button class="nn-btn nn-btn--primary nn-btn--icon menu-toggle" type="button" aria-controls="appSidebar" aria-expanded="false" aria-label="Open navigation"><span class="nn-icon nn-icon--menu" aria-hidden="true"></span></button>
  </div>

  <div class="page-header">
    <div>
      <h1>✏️ Edit company information</h1>
      <p>Fill in your company information to get started.</p>
    </div>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success">✓ Company information saved successfully.</div>
  <?php elseif (!empty($errors)): ?>
    <div class="alert alert-error">Please fix the errors below before saving.</div>
  <?php endif; ?>

  <form method="POST"><?= AuthSecurity::csrfField() ?>
    <div class="card" style="margin-bottom:20px;">
      <div class="card-title"><span class="badge-ic">📋</span>Basic information</div>

      <div class="field <?= isset($errors['company_name']) ? 'invalid' : '' ?>">
        <label>Company name <span class="req">*</span></label>
        <input type="text" name="company_name" placeholder="Your company name"
               value="<?= htmlspecialchars($company['company_name']) ?>" maxlength="150">
        <span class="hint"><?= htmlspecialchars($errors['company_name'] ?? 'This field is required.') ?></span>
      </div>

      <div class="field">
        <label>Legal name</label>
        <input type="text" name="legal_name" placeholder="Full legal name of the company"
               value="<?= htmlspecialchars($company['legal_name'] ?? '') ?>" maxlength="150">
      </div>

      <div class="field">
        <label>Description</label>
        <textarea name="description" placeholder="Brief description of your company"><?= htmlspecialchars($company['description'] ?? '') ?></textarea>
      </div>

      <div class="field-row">
        <div class="field">
          <label>VAT number</label>
          <input type="text" name="vat_number" placeholder="VAT number"
                 value="<?= htmlspecialchars($company['vat_number'] ?? '') ?>" maxlength="50">
        </div>
        <div class="field">
          <label>Registration number</label>
          <input type="text" name="registration_number" placeholder="Company registration number"
                 value="<?= htmlspecialchars($company['registration_number'] ?? '') ?>" maxlength="50">
        </div>
      </div>

      <div class="field">
        <label>CIE (Company ID)</label>
        <input type="text" name="cie_number" placeholder="Company identification number or code"
               value="<?= htmlspecialchars($company['cie_number'] ?? '') ?>" maxlength="50">
      </div>
    </div>

    <div class="card">
      <div class="card-title"><span class="badge-ic">🏦</span>Bank information</div>

      <div class="field-row">
        <div class="field">
          <label>Bank name</label>
          <input type="text" name="bank_name" placeholder="Name of your bank"
                 value="<?= htmlspecialchars($company['bank_name'] ?? '') ?>" maxlength="150">
        </div>
        <div class="field">
          <label>Bank account number</label>
          <input type="text" name="bank_account_number" placeholder="Account number"
                 value="<?= htmlspecialchars($company['bank_account_number'] ?? '') ?>" maxlength="50">
        </div>
      </div>

      <div class="save-row nn-actions nn-actions--end">
        <button type="submit" class="nn-btn nn-btn--primary">Save changes</button>
      </div>
    </div>
  </form>
</main>

</body>
</html>
