<?php

declare(strict_types=1);

// =============================================================================
// api/usuarios_listar.php — Lista mínima de usuarios (id, nombre, rol)
// Método: GET | Auth: Sesión PHP + Role: admin, staff, presidente
// No expone `usuario` ni `password_hash` — solo lo necesario para selects de UI.
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/session_guard.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

checkAccess(['admin', 'staff', 'presidente']);

asfl_log('REQUEST', ['endpoint' => 'usuarios_listar.php', 'method' => $_SERVER['REQUEST_METHOD']]);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Método no permitido.', 405);
}

try {
    $database = new Database();
    $pdo      = $database->getConnection();

    $stmt = $pdo->query('SELECT `id`, `nombre`, `rol` FROM `usuarios` ORDER BY `nombre` ASC');
    $usuarios = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    send_success('Usuarios obtenidos.', ['usuarios' => $usuarios]);

} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [usuarios_listar] ' . $e->getMessage());
    send_error('Error al obtener los usuarios.', 500);
}
