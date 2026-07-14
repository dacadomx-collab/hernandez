<?php

declare(strict_types=1);

// =============================================================================
// dashboard.php — Dashboard Principal (Menú hamburguesa + 5 Tarjetas)
// RBAC: admin/staff ven Módulos 1-2. presidente ve los 5 Módulos.
// =============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

checkAccessOrRedirect(['admin', 'staff', 'presidente']);

$rol           = (string) $_SESSION['rol'];
$nombre        = (string) $_SESSION['nombre'];
$esPresidente  = $rol === 'presidente';

/**
 * Redirige a login.php (en vez de responder JSON 401/403 vía checkAccess())
 * cuando la petición es de navegación de página, no de API.
 */
function checkAccessOrRedirect(array $allowedRoles): void
{
    if (empty($_SESSION['id_usuario']) || empty($_SESSION['rol']) || !in_array($_SESSION['rol'], $allowedRoles, true)) {
        header('Location: login.php');
        exit;
    }
}

$modulos = [
    ['id' => 1, 'nombre' => 'Cuentas Contables',        'icono' => '💰', 'href' => 'modulo_1_cuentas.php',      'roles' => ['admin', 'staff', 'presidente']],
    ['id' => 2, 'nombre' => 'Planificación de Obra',    'icono' => '🏗️', 'href' => 'modulo_2_planificacion.php', 'roles' => ['admin', 'staff', 'presidente']],
    ['id' => 3, 'nombre' => '40 Pendientes',            'icono' => '🎙️', 'href' => 'modulo_3_pendientes.php',    'roles' => ['presidente']],
    ['id' => 4, 'nombre' => 'Tiempo con la Familia',    'icono' => '👨‍👩‍👧', 'href' => 'modulo_4_familia.php',       'roles' => ['presidente']],
    ['id' => 5, 'nombre' => 'Pendientes Llamamiento SUD', 'icono' => '⛪', 'href' => 'modulo_5_sud.php',          'roles' => ['presidente']],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Pte_Hernandez_LaPazBCS</title>
    <link rel="icon" href="favicon.ico">
    <!-- Configuración PWA y Pantalla Completa Móvil -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Pte. Hernández">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/img/icon-192.png">
    <link rel="preload" href="assets/css/main.css" as="style">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body data-rol="<?= htmlspecialchars($rol, ENT_QUOTES, 'UTF-8') ?>">
    <div class="pwa-install-banner is-hidden" id="pwa-install-banner">
        <span>Toca aquí para agregar Pte. Hernández a tu pantalla de inicio</span>
        <button type="button" id="pwa-install-dismiss" aria-label="Cerrar">✕</button>
    </div>

    <header class="app-header">
        <h1 class="app-header__title">Hola, <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></h1>
        <button type="button" class="hamburger-btn" id="hamburger-toggle" aria-expanded="false" aria-controls="hamburger-menu">☰</button>
    </header>

    <nav class="hamburger-menu" id="hamburger-menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="api/auth_logout.php">Cerrar sesión</a>
    </nav>

    <main class="container arf-grid">
        <?php foreach ($modulos as $modulo): ?>
            <?php if (!in_array($rol, $modulo['roles'], true)) { continue; } ?>
            <a
                href="<?= htmlspecialchars($modulo['href'], ENT_QUOTES, 'UTF-8') ?>"
                class="card card-modulo col-6 col-md-4 col-lg-2<?= $modulo['id'] >= 3 ? ' modulo-privado' : '' ?>"
                data-modulo-id="<?= (int) $modulo['id'] ?>"
            >
                <span class="card-modulo__icono" aria-hidden="true"><?= $modulo['icono'] ?></span>
                <span class="card-modulo__nombre"><?= htmlspecialchars($modulo['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        <?php endforeach; ?>
    </main>

    <?php if ($esPresidente): ?>
        <button type="button" class="panic-btn" id="panic-btn" title="Modo Oculto" aria-label="Botón de pánico: ocultar módulos privados">🛡️</button>
    <?php endif; ?>

    <script src="assets/js/dashboard.js" defer></script>
</body>
</html>
