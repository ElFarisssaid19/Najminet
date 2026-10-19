<?php
declare(strict_types=1);

if (empty($_SESSION['user_id']) || !AuthSecurity::sessionIsValid($pdo)) {
    $_SESSION = [];
    header('Location: /auth/login.php');
    exit;
}
