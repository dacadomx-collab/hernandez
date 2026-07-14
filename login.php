<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — Pte_Hernandez_LaPazBCS</title>
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
<body class="login-body">
    <main class="login-shell">
        <section class="card login-card">
            <header class="login-card__header">
                <h1 class="login-card__title">Pte. Hernández</h1>
                <p class="login-card__subtitle">Sistema de Gestión — La Paz, BCS</p>
            </header>

            <p id="login-error" role="alert"></p>

            <form id="login-form" autocomplete="off">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" required autocomplete="username" autofocus>

                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">

                <button type="submit" id="login-submit">Entrar</button>
            </form>
        </section>
    </main>

    <script src="assets/js/login.js" defer></script>
</body>
</html>
