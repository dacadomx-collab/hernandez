<?php

declare(strict_types=1);

// =============================================================================
// admin_obras.php — Alta y edición de Obras (Administración)
// RBAC: admin, presidente
// =============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

if (empty($_SESSION['id_usuario']) || empty($_SESSION['rol']) || !in_array($_SESSION['rol'], ['admin', 'presidente'], true)) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alta de Obras — Pte_Hernandez_LaPazBCS</title>
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
        <h1 class="app-header__title">Alta de Obras</h1>
        <button type="button" class="hamburger-btn" id="hamburger-toggle" aria-expanded="false" aria-controls="hamburger-menu">☰</button>
    </header>

    <nav class="hamburger-menu" id="hamburger-menu">
        <a href="dashboard.php">← Dashboard</a>
        <a href="api/auth_logout.php">Cerrar sesión</a>
    </nav>

    <main class="container arf-grid">
        <section class="card arf-col-2">
            <h2 id="obra-form-titulo">Nueva Obra</h2>
            <p id="obra-error" role="alert"></p>
            <form id="obra-form" autocomplete="off">
                <input type="hidden" id="obra-id" value="">

                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" required>

                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="2"></textarea>

                <label for="ubicacion">Ubicación</label>
                <input type="text" id="ubicacion" name="ubicacion">

                <label for="estatus">Estatus</label>
                <select id="estatus" name="estatus">
                    <option value="activa">Activa</option>
                    <option value="pausada">Pausada</option>
                    <option value="finalizada">Finalizada</option>
                </select>

                <button type="submit" id="obra-submit">Guardar Obra</button>
                <button type="button" id="obra-cancelar" class="is-hidden">Cancelar edición</button>
            </form>
        </section>

        <section class="card arf-col-2">
            <h2>Obras Registradas</h2>
            <ul id="obras-lista"></ul>
        </section>
    </main>

    <script src="assets/js/admin_obras.js" defer></script>
</body>
</html>
