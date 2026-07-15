<?php

declare(strict_types=1);

// =============================================================================
// index.php — Front Controller de entrada raíz
// Redirige a dashboard.php (sesión activa) o login.php (sin sesión).
// =============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

if (!empty($_SESSION['usuario']) && !empty($_SESSION['rol'])) {
    header('Location: dashboard.php', true, 302);
    exit;
}

header('Location: login.php', true, 302);
exit;
