<?php

declare(strict_types=1);

// =============================================================================
// api/auth_logout.php — Cierre de sesión PHP nativa
// =============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];
session_destroy();

header('Location: ../login.php');
exit;
