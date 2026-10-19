<?php
declare(strict_types=1);

/**
 * document.php?id=N
 * --------------------------------------------------------------------
 * Downloads one of the signed-in user's documents. Files are stored
 * outside the web root (DocumentManager::defaultStorageDir()), so this is
 * the only way to reach them: the lookup is scoped to the session user,
 * and any other id answers 404.
 * --------------------------------------------------------------------
 */

session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/middleware/subscription_guard.php';
require_once __DIR__ . '/classes/DocumentManager.php';

$manager = new DocumentManager($pdo);
$doc = $manager->getById((int) ($_GET['id'] ?? 0), (int) $_SESSION['user_id']);
$path = $doc !== null ? $manager->filePath($doc) : null;

if ($path === null) {
    http_response_code(404);
    exit('Document not found.');
}

$name = DocumentManager::downloadName($doc);
$asciiName = preg_replace('/[^A-Za-z0-9 ._-]/', '_', $name);

header('Content-Type: ' . DocumentManager::mimeType($doc));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($name));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($path);
