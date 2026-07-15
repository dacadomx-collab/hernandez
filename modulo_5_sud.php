<?php

declare(strict_types=1);

// =============================================================================
// modulo_5_sud.php — Módulo 5: Pendientes Llamamiento SUD
// RBAC: únicamente presidente. Privacidad extra: PIN + bloqueo por inactividad
// de 2 minutos para los registros 'urgente_confidencial'.
// =============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

if (empty($_SESSION['id_usuario']) || empty($_SESSION['rol']) || $_SESSION['rol'] !== 'presidente') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Llamamiento SUD — Pte_Hernandez_LaPazBCS</title>
    <link rel="icon" href="favicon.ico">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Pte. Hernández">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/img/icon-192.png">
    <link rel="preload" href="assets/css/main.css" as="style">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <header class="app-header">
        <h1 class="app-header__title">Llamamiento SUD</h1>
        <button type="button" class="hamburger-btn" id="hamburger-toggle" aria-expanded="false" aria-controls="hamburger-menu">☰</button>
    </header>

    <nav class="hamburger-menu" id="hamburger-menu">
        <a href="dashboard.php">← Dashboard</a>
        <a href="api/auth_logout.php">Cerrar sesión</a>
    </nav>

    <main class="container arf-grid">
        <section class="card arf-col-2">
            <h2>Registrar Asunto</h2>
            <p id="sud-error" role="alert"></p>
            <form id="sud-form" autocomplete="off">
                <label for="categoria">Categoría</label>
                <select id="categoria" name="categoria">
                    <option value="urgente_confidencial">Urgente / Confidencial</option>
                    <option value="operativo">Operativo</option>
                    <option value="largo_plazo">A Largo Plazo</option>
                </select>

                <label for="titulo">Título</label>
                <input type="text" id="titulo" name="titulo" required>

                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="2"></textarea>

                <button type="submit">Registrar</button>
            </form>
        </section>

        <section class="arf-grid pendientes-columnas">
            <div class="card pendientes-columna" data-categoria="urgente_confidencial">
                <h3>🔒 Urgente / Confidencial</h3>
                <button type="button" id="pin-desbloquear-btn" class="btn-pin">Desbloquear con PIN</button>
                <ul class="sud-lista"></ul>
            </div>
            <div class="card pendientes-columna" data-categoria="operativo">
                <h3>Operativo</h3>
                <ul class="sud-lista"></ul>
            </div>
            <div class="card pendientes-columna" data-categoria="largo_plazo">
                <h3>A Largo Plazo</h3>
                <ul class="sud-lista"></ul>
            </div>
        </section>
    </main>

    <div class="pin-modal is-hidden" id="pin-modal">
        <div class="pin-modal__card card">
            <h3>PIN de Privacidad</h3>
            <p id="pin-error" role="alert"></p>
            <input type="password" id="pin-input" inputmode="numeric" maxlength="6" placeholder="••••••" autocomplete="off">
            <div class="pin-modal__acciones">
                <button type="button" id="pin-confirmar-btn">Confirmar</button>
                <button type="button" id="pin-cancelar-btn">Cancelar</button>
            </div>
        </div>
    </div>

    <script src="assets/js/modulo_5_sud.js" defer></script>
</body>
</html>
