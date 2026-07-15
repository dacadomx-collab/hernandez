<?php

declare(strict_types=1);

// =============================================================================
// modulo_4_familia.php — Módulo 4: Tiempo con la Familia
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
    <title>Tiempo con la Familia — Pte_Hernandez_LaPazBCS</title>
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
        <h1 class="app-header__title">Tiempo con la Familia</h1>
        <button type="button" class="hamburger-btn" id="hamburger-toggle" aria-expanded="false" aria-controls="hamburger-menu">☰</button>
    </header>

    <nav class="hamburger-menu" id="hamburger-menu">
        <a href="dashboard.php">← Dashboard</a>
        <a href="api/auth_logout.php">Cerrar sesión</a>
    </nav>

    <main class="container arf-grid">
        <section class="card arf-col-2">
            <h2>Agendar Actividad Familiar</h2>
            <p id="evento-error" role="alert"></p>
            <p id="evento-colision" class="alerta-colision is-hidden" role="alert"></p>
            <form id="evento-form" autocomplete="off">
                <label for="titulo">Título</label>
                <input type="text" id="titulo" name="titulo" placeholder="Ej. Cena familiar" required>

                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="2"></textarea>

                <label for="fecha_inicio">Inicio</label>
                <input type="datetime-local" id="fecha_inicio" name="fecha_inicio" required>

                <label for="fecha_fin">Fin</label>
                <input type="datetime-local" id="fecha_fin" name="fecha_fin" required>

                <button type="submit" id="evento-submit">Agendar</button>
            </form>
        </section>

        <section class="card arf-col-2">
            <h2>Próximas Actividades</h2>
            <ul id="eventos-lista"></ul>
        </section>
    </main>

    <script src="assets/js/modulo_4_familia.js" defer></script>
</body>
</html>
