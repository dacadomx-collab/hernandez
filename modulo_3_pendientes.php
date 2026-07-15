<?php

declare(strict_types=1);

// =============================================================================
// modulo_3_pendientes.php — Módulo 3: 40 Pendientes (Dictado de Voz)
// RBAC: únicamente presidente
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
    <title>40 Pendientes — Pte_Hernandez_LaPazBCS</title>
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
        <h1 class="app-header__title">40 Pendientes</h1>
        <button type="button" class="hamburger-btn" id="hamburger-toggle" aria-expanded="false" aria-controls="hamburger-menu">☰</button>
    </header>

    <nav class="hamburger-menu" id="hamburger-menu">
        <a href="dashboard.php">← Dashboard</a>
        <a href="api/auth_logout.php">Cerrar sesión</a>
    </nav>

    <main class="container arf-grid">
        <section class="card arf-col-2">
            <h2>Agregar Pendiente</h2>
            <p id="pendiente-error" role="alert"></p>
            <form id="pendiente-form" autocomplete="off">
                <label for="categoria">Categoría</label>
                <select id="categoria" name="categoria">
                    <option value="urgente">Urgente</option>
                    <option value="no_importante">No importante</option>
                    <option value="largo_plazo">Largo plazo</option>
                </select>

                <label for="pendiente-texto">Pendiente</label>
                <div class="pendiente-input-row">
                    <input type="text" id="pendiente-texto" name="titulo" placeholder="Dicta o escribe un pendiente..." required>
                    <button type="button" id="mic-btn" title="Dictar por voz" aria-label="Dictar pendiente por voz">🎙️</button>
                </div>
                <button type="submit">Agregar</button>
            </form>
        </section>

        <section class="arf-grid pendientes-columnas">
            <div class="card pendientes-columna" data-categoria="urgente">
                <h3>Urgente</h3>
                <ul class="pendientes-lista"></ul>
            </div>
            <div class="card pendientes-columna" data-categoria="no_importante">
                <h3>No Importante</h3>
                <ul class="pendientes-lista"></ul>
            </div>
            <div class="card pendientes-columna" data-categoria="largo_plazo">
                <h3>Largo Plazo</h3>
                <ul class="pendientes-lista"></ul>
            </div>
        </section>
    </main>

    <script src="assets/js/modulo_3_pendientes.js" defer></script>
</body>
</html>
