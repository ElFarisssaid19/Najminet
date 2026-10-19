<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/middleware/auth_guard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!AuthSecurity::validCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid request.');
    }

    AuthSecurity::forgetAllDevices($pdo, (int) $_SESSION['user_id']);
    $_SESSION = [];
    session_destroy();
    header('Location: /auth/login.php?all_devices=1');
    exit;
}

$pageTitle = 'Account security';
$activePage = 'account';
require __DIR__ . '/includes/layout_head.php';
require __DIR__ . '/includes/sidebar.php';
?>
<main class="main"><div class="topbar"><button class="nn-btn nn-btn--primary nn-btn--icon menu-toggle" type="button" aria-controls="appSidebar" aria-expanded="false" aria-label="Open navigation"><span class="nn-icon nn-icon--menu" aria-hidden="true"></span></button></div><div class="page-header"><div><h1>Account security</h1><p>Manage your active sign-ins.</p></div></div><section class="card"><div class="card-title">Log out of all devices</div><p>This removes all Remember Me sessions and signs every device out on its next request.</p><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars(AuthSecurity::csrfToken()) ?>"><button class="nn-btn nn-btn--danger" type="submit" data-confirm="Log out of every device, including this one?"><span class="nn-icon nn-icon--log-out" aria-hidden="true"></span>Log out of all devices</button></form></section></main>
