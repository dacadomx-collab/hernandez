<?php

declare(strict_types=1);

// =============================================================================
// api/auth_login.php — Login con Sesión PHP + RBAC (Pte_Hernandez_LaPazBCS)
// Endpoint: POST /api/auth_login.php
// Mandamiento #2: Seguridad Nivel Militar | Mandamiento #14: CORS ≠ Auth
//
// Schema real (knowledge/02_CODEX_Y_SCHEMA_MAESTRO.md):
//   usuarios (id, nombre, usuario UNIQUE, password_hash, rol ENUM('admin','staff','presidente'))
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/input_sanitizer.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

asfl_log('REQUEST', ['endpoint' => 'auth_login.php', 'method' => $_SERVER['REQUEST_METHOD']]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Método no permitido.', 405);
}

try {
    $payload = json_decode((string) file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
} catch (\JsonException) {
    send_error('Payload JSON inválido.', 400);
}

$usuario  = sanitize_string((string) ($payload['usuario'] ?? ''), 50);
$password = (string) ($payload['password'] ?? '');

if ($usuario === '') {
    send_error('El usuario es requerido.', 422);
}
if ($password === '') {
    send_error('La contraseña es requerida.', 422);
}

try {
    $database = new Database();
    $pdo      = $database->getConnection();

    $stmt = $pdo->prepare('SELECT `id`, `nombre`, `usuario`, `password_hash`, `rol` FROM `usuarios` WHERE `usuario` = :usuario LIMIT 1');
    $stmt->execute([':usuario' => $usuario]);
    $user = $stmt->fetch(\PDO::FETCH_ASSOC);

    // Anti-enumeración de usuarios (timing attack): siempre se ejecuta password_verify()
    // contra un hash — real o "dummy" — para que responder "usuario no existe" tarde
    // lo mismo que "contraseña incorrecta".
    $hashParaVerificar = $user !== false ? (string) $user['password_hash'] : '$2y$10$invalidinvalidinvalidu.invalidinvalidinvalidinvalidinva';

    if ($user === false || !password_verify($password, $hashParaVerificar)) {
        asfl_log('RESPONSE', ['endpoint' => 'auth_login.php', 'status' => 'error', 'reason' => 'credenciales_invalidas']);
        send_error('Credenciales inválidas.', 401);
    }

    session_regenerate_id(true);

    $_SESSION['id_usuario'] = (int) $user['id'];
    $_SESSION['nombre']     = (string) $user['nombre'];
    $_SESSION['usuario']    = (string) $user['usuario'];
    $_SESSION['rol']        = (string) $user['rol'];

    asfl_log('RESPONSE', ['endpoint' => 'auth_login.php', 'status' => 'success', 'id_usuario' => $user['id'], 'rol' => $user['rol']]);

    send_success('Autenticación exitosa.', [
        'id_usuario' => (int) $user['id'],
        'nombre'     => (string) $user['nombre'],
        'usuario'    => (string) $user['usuario'],
        'rol'        => (string) $user['rol'],
    ]);

} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [auth_login] ' . $e->getMessage());
    send_error('Error interno al procesar el inicio de sesión.', 500);
}
