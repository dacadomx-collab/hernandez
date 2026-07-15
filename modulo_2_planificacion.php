<?php

declare(strict_types=1);

// =============================================================================
// modulo_2_planificacion.php — Módulo 2: Planificación de Obra
// RBAC: admin, staff, presidente
// =============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

if (empty($_SESSION['id_usuario']) || empty($_SESSION['rol']) || !in_array($_SESSION['rol'], ['admin', 'staff', 'presidente'], true)) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planificación de Obra — Pte_Hernandez_LaPazBCS</title>
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
        <h1 class="app-header__title">Planificación de Obra</h1>
        <button type="button" class="hamburger-btn" id="hamburger-toggle" aria-expanded="false" aria-controls="hamburger-menu">☰</button>
    </header>

    <nav class="hamburger-menu" id="hamburger-menu">
        <a href="dashboard.php">← Dashboard</a>
        <a href="api/auth_logout.php">Cerrar sesión</a>
    </nav>

    <main class="container arf-grid">
        <section class="card arf-col-2">
            <label for="obra-select">Obra</label>
            <select id="obra-select"></select>

            <label for="fecha-select">Fecha</label>
            <input type="date" id="fecha-select">
        </section>

        <section class="card arf-col-2">
            <h2>Nueva Tarea</h2>
            <p id="tarea-error" role="alert"></p>
            <form id="tarea-form" autocomplete="off">
                <label for="id_usuario_asignado">Asignar a</label>
                <select id="id_usuario_asignado" name="id_usuario_asignado" required></select>

                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="2" placeholder="Ej. Revisar avance de la barda perimetral" required></textarea>

                <label for="telefono_contacto">Teléfono de contacto (10 dígitos, opcional)</label>
                <input type="tel" id="telefono_contacto" name="telefono_contacto" pattern="[0-9]{10}" placeholder="6121234567">

                <button type="submit" id="tarea-submit">Asignar Tarea</button>
            </form>
        </section>

        <section class="card arf-col-2">
            <h2>Tareas del Día</h2>
            <ul id="tareas-lista"></ul>
        </section>
    </main>

    <script src="assets/js/modulo_2_planificacion.js" defer></script>
</body>
</html>
