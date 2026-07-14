<?php

declare(strict_types=1);

// =============================================================================
// api/permisos_get.php — Lee el semáforo de permisos de una obra
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

asfl_log('REQUEST', ['endpoint' => 'permisos_get.php', 'method' => $_SERVER['REQUEST_METHOD']]);

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
        'SELECT `estatus_subdivision`, `estatus_licencia`, `estatus_terminacion`
         FROM `permisos_obra` WHERE `id_obra` = :id_obra LIMIT 1'
    );
    $stmt->execute([':id_obra' => $idObra]);
    $permisos = $stmt->fetch(\PDO::FETCH_ASSOC);

    if ($permisos === false) {
        $insert = $pdo->prepare('INSERT INTO `permisos_obra` (`id_obra`) VALUES (:id_obra)');
        $insert->execute([':id_obra' => $idObra]);

        $permisos = [
            'estatus_subdivision' => 'rojo',
            'estatus_licencia'    => 'rojo',
            'estatus_terminacion' => 'rojo',
        ];
    }

    send_success('Permisos obtenidos.', $permisos);

} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [permisos_get] ' . $e->getMessage());
    send_error('Error al obtener los permisos.', 500);
}
