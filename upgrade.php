<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/includes/session.php';
if (empty($_SESSION['user_id'])) { header('Location: /auth/login.php'); exit; }
$message = $_SESSION['subscription_message'] ?? 'Your subscription is no longer active.';
unset($_SESSION['subscription_message']);
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"><meta name="description" content="Upgrade your NajmiNet plan."><link rel="icon" href="/favicon.svg" type="image/svg+xml"><title>Upgrade | NajmiNet</title><link href="/assets/vendor/bootstrap-5.3.2/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="/assets/css/form-controls.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/form-controls.css') ?>">
<link rel="stylesheet" href="/assets/css/responsive.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/responsive.css') ?>">
<link href="/assets/css/style.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/style.css') ?>" rel="stylesheet"><link rel="stylesheet" href="/assets/css/buttons.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/buttons.css') ?>"></head><body><?php include __DIR__ . '/includes/header.php'; ?><main class="container py-5" style="max-width:680px"><section class="glass p-5 rounded-4"><span class="badge-recommended">Upgrade required</span><h1 class="mt-3">Keep your business moving</h1><p><?= htmlspecialchars($message) ?></p><a class="nn-btn nn-btn--primary nn-btn--lg" href="/checkout.php?plan=professional">Upgrade now</a></section></main><script defer src="/assets/vendor/bootstrap-5.3.2/bootstrap.bundle.min.js"></script><script src="/assets/js/script.js?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/js/script.js') ?>"></script><script src="/assets/js/buttons.js?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/js/buttons.js') ?>"></script></body></html>
