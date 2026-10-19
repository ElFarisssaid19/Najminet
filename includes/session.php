<?php
declare(strict_types=1);

/**
 * Starts the PHP session once. Include it at the top of a page, before
 * any output, when the page (or includes/header.php) reads $_SESSION.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
