<?php

declare(strict_types=1);

// =============================================================================
// modulo_1_cuentas.php — Módulo 1: Cuentas Contables y Semáforos de Permisos
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

$nombre = (string) $_SESSION['nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuentas Contables — Pte_Hernandez_LaPazBCS</title>
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
        <h1 class="app-header__title">Cuentas Contables</h1>
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
        </section>

        <section class="card arf-col-2" id="semaforos-section">
            <h2>Semáforo de Permisos</h2>
            <div class="semaforo-grupo" data-campo="estatus_subdivision">
                <span class="semaforo-etiqueta">Subdivisión</span>
                <div class="semaforo-dots">
                    <button type="button" class="semaforo-dot semaforo-dot--rojo" data-valor="rojo" aria-label="Subdivisión: rojo"></button>
                    <button type="button" class="semaforo-dot semaforo-dot--amarillo" data-valor="amarillo" aria-label="Subdivisión: amarillo"></button>
                    <button type="button" class="semaforo-dot semaforo-dot--verde" data-valor="verde" aria-label="Subdivisión: verde"></button>
                </div>
            </div>
            <div class="semaforo-grupo" data-campo="estatus_licencia">
                <span class="semaforo-etiqueta">Licencia</span>
                <div class="semaforo-dots">
                    <button type="button" class="semaforo-dot semaforo-dot--rojo" data-valor="rojo" aria-label="Licencia: rojo"></button>
                    <button type="button" class="semaforo-dot semaforo-dot--amarillo" data-valor="amarillo" aria-label="Licencia: amarillo"></button>
                    <button type="button" class="semaforo-dot semaforo-dot--verde" data-valor="verde" aria-label="Licencia: verde"></button>
                </div>
            </div>
            <div class="semaforo-grupo" data-campo="estatus_terminacion">
                <span class="semaforo-etiqueta">Terminación</span>
                <div class="semaforo-dots">
                    <button type="button" class="semaforo-dot semaforo-dot--rojo" data-valor="rojo" aria-label="Terminación: rojo"></button>
                    <button type="button" class="semaforo-dot semaforo-dot--amarillo" data-valor="amarillo" aria-label="Terminación: amarillo"></button>
                    <button type="button" class="semaforo-dot semaforo-dot--verde" data-valor="verde" aria-label="Terminación: verde"></button>
                </div>
            </div>
        </section>

        <section class="card" id="presupuesto-section">
            <h2>Estado de Presupuesto</h2>
            <div id="presupuesto-estado"></div>
        </section>

        <section class="card arf-col-2">
            <h2>Captura Express de Gasto</h2>
            <p id="gasto-error" role="alert"></p>
            <form id="gasto-form" autocomplete="off">
                <label for="id_presupuesto">Concepto (Presupuesto)</label>
                <select id="id_presupuesto" name="id_presupuesto" required></select>

                <label for="monto">Monto</label>
                <input type="number" id="monto" name="monto" min="0.01" step="0.01" required>

                <label for="fecha_gasto">Fecha</label>
                <input type="date" id="fecha_gasto" name="fecha_gasto" required>

                <label for="foto">Foto del recibo</label>
                <input type="file" id="foto" name="foto" accept="image/*" capture="environment">

                <button type="submit" id="gasto-submit">Registrar Gasto</button>
            </form>
        </section>

        <section class="card arf-col-2">
            <h2>Gastos Registrados</h2>
            <ul id="gastos-lista"></ul>
        </section>
    </main>

    <script src="assets/js/modulo_1_cuentas.js" defer></script>
</body>
</html>
