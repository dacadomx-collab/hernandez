<?php

declare(strict_types=1);

// =============================================================================
// api/gastos_listar.php — Lista los gastos registrados de una obra
// Método: GET ?id_obra= | Auth: Sesión PHP + Role: admin, staff, presidente
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/session_guard.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/input_sanitizer.php';
require_once __DIR__ . '/../helpers/obra_access.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

checkAccess(['admin', 'staff', 'presidente']);

asfl_log('REQUEST', ['endpoint' => 'gastos_listar.php', 'method' => $_SERVER['REQUEST_METHOD']]);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Método no permitido.', 405);
}

$idObra = sanitize_int($_GET['id_obra'] ?? null, 0);

if ($idObra <= 0) {
    send_error('id_obra es requerido.', 422);
}

try {
    $database = new Database();
    $pdo      = $database->getConnection();

    if (!usuarioTieneAccesoObra($pdo, (int) $_SESSION['id_usuario'], (string) $_SESSION['rol'], $idObra)) {
        send_error('No tienes acceso a esta obra.', 403);
    }

    $stmt = $pdo->prepare(
        'SELECT g.`id`, g.`concepto`, g.`monto`, g.`fecha_gasto`, g.`foto_ticket`, u.`nombre` AS `registrado_por`
         FROM `gastos` g
         INNER JOIN `usuarios` u ON u.`id` = g.`id_usuario`
         WHERE g.`id_obra` = :id_obra
         ORDER BY g.`fecha_gasto` DESC, g.`id` DESC'
    );
    $stmt->execute([':id_obra' => $idObra]);
    $gastos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    send_success('Gastos obtenidos.', ['gastos' => $gastos]);

} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [gastos_listar] ' . $e->getMessage());
    send_error('Error al obtener los gastos.', 500);
}
