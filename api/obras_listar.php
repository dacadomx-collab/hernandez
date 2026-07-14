<?php

declare(strict_types=1);

// =============================================================================
// api/obras_listar.php — Lista de obras visibles para el usuario en sesión
// Método: GET | Auth: Sesión PHP + Role: admin, staff, presidente
// 'staff' solo ve las obras asignadas vía usuarios_obras; admin/presidente ven todas.
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/session_guard.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

checkAccess(['admin', 'staff', 'presidente']);

asfl_log('REQUEST', ['endpoint' => 'obras_listar.php', 'method' => $_SERVER['REQUEST_METHOD']]);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Método no permitido.', 405);
}

try {
    $database = new Database();
    $pdo      = $database->getConnection();

    if ($_SESSION['rol'] === 'staff') {
        $stmt = $pdo->prepare(
            'SELECT o.`id`, o.`nombre`, o.`estatus`
             FROM `obras` o
             INNER JOIN `usuarios_obras` uo ON uo.`id_obra` = o.`id`
             WHERE uo.`id_usuario` = :id_usuario
             ORDER BY o.`nombre` ASC'
        );
        $stmt->execute([':id_usuario' => (int) $_SESSION['id_usuario']]);
    } else {
        $stmt = $pdo->query('SELECT `id`, `nombre`, `estatus` FROM `obras` ORDER BY `nombre` ASC');
    }

    $obras = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    send_success('Obras obtenidas.', ['obras' => $obras]);

} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [obras_listar] ' . $e->getMessage());
    send_error('Error al obtener las obras.', 500);
}
