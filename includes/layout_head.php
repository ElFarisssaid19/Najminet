<?php
/**
 * includes/layout_head.php
 * --------------------------------------------------------------------
 * Include at the top of every tool page, after your PHP data-fetching
 * block. Expects an optional $pageTitle variable to be set beforehand.
 * --------------------------------------------------------------------
 */
require_once __DIR__ . '/session.php';
$pageTitle = $pageTitle ?? 'Dashboard';
$pageStylesheets = $pageStylesheets ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="robots" content="noindex">
<meta name="description" content="NajmiNet accounting dashboard."><link rel="icon" href="/favicon.svg" type="image/svg+xml">
<title><?= htmlspecialchars($pageTitle) ?> — NajmiNet</title>
<link rel="stylesheet" href="/assets/fonts/fonts-5.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/fonts/fonts-5.css') ?>"><link rel="preload" as="font" type="font/woff2" href="/assets/fonts/sora-latin-46cba0e3.woff2" crossorigin><link rel="preload" as="font" type="font/woff2" href="/assets/fonts/space-grotesk-latin-4ecc7e89.woff2" crossorigin>
<link rel="stylesheet" href="/assets/css/form-controls.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/form-controls.css') ?>">
<link rel="stylesheet" href="/assets/css/responsive.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/responsive.css') ?>">
<link rel="stylesheet" href="/assets/css/app.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/app.css') ?>">
<link rel="stylesheet" href="/assets/css/buttons.css?v=<?= @filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/buttons.css') ?>">
<?php foreach ($pageStylesheets as $stylesheet): ?>
<link rel="stylesheet" href="<?= htmlspecialchars($stylesheet) ?>">
<?php endforeach; ?>
</head>
<body>
