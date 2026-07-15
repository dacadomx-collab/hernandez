<?php

declare(strict_types=1);

// =============================================================================
// api/modulo_2_backend.php — Backend del Módulo 2: Planificación de Obra
// Auth: Sesión PHP + Role: admin, staff, presidente
//
// Acciones (GET ?accion= | POST {"accion": ...}):
//   listar           GET  {id_obra, fecha?}      → tareas de una obra en una fecha (default hoy)
//   crear            POST {id_obra, id_usuario_asignado, descripcion, telefono_contacto?, fecha_asignacion}
//   cambiar_estatus  POST {id, estatus}
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/session_guard.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/input_sanitizer.php';
require_once __DIR__ . '/../helpers/obra_access.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

checkAccess(['admin', 'staff', 'presidente']);

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $metodo === 'GET'
    ? (string) ($_GET['accion'] ?? '')
    : (string) (json_decode((string) file_get_contents('php://input'), true)['accion'] ?? '');

asfl_log('REQUEST', ['endpoint' => 'modulo_2_backend.php', 'method' => $metodo, 'accion' => $accion]);

try {
    $database = new Database();
    $pdo      = $database->getConnection();

    match (true) {
        $metodo === 'GET' && $accion === 'listar'          => accionListar($pdo),
        $metodo === 'POST' && $accion === 'crear'           => accionCrear($pdo),
        $metodo === 'POST' && $accion === 'cambiar_estatus' => accionCambiarEstatus($pdo),
        default => send_error('Acción o método no soportado.', 404),
    };

} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [modulo_2_backend] ' . $e->getMessage());
    send_error('Error al procesar la solicitud.', 500);
}

// -----------------------------------------------------------------------------

function accionListar(\PDO $pdo): never
{
    $idObra = sanitize_int($_GET['id_obra'] ?? null, 0);
    $fecha  = (string) ($_GET['fecha'] ?? date('Y-m-d'));

    if ($idObra <= 0) {
        send_error('id_obra es requerido.', 422);
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !strtotime($fecha)) {
        send_error('Fecha inválida.', 422);
    }
    if (!usuarioTieneAccesoObra($pdo, (int) $_SESSION['id_usuario'], (string) $_SESSION['rol'], $idObra)) {
        send_error('No tienes acceso a esta obra.', 403);
    }

    $stmt = $pdo->prepare(
        'SELECT t.`id`, t.`descripcion`, t.`telefono_contacto`, t.`fecha_asignacion`, t.`estatus`,
                u.`nombre` AS `asignado_a`
         FROM `tareas` t
         INNER JOIN `usuarios` u ON u.`id` = t.`id_usuario_asignado`
         WHERE t.`id_obra` = :id_obra AND t.`fecha_asignacion` = :fecha
         ORDER BY t.`id` DESC'
    );
    $stmt->execute([':id_obra' => $idObra, ':fecha' => $fecha]);

    send_success('Tareas obtenidas.', ['tareas' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
}

function accionCrear(\PDO $pdo): never
{
    $payload = json_decode((string) file_get_contents('php://input'), true) ?: [];

    $idObra            = (int) ($payload['id_obra'] ?? 0);
    $idUsuarioAsignado = (int) ($payload['id_usuario_asignado'] ?? 0);
    $descripcion       = sanitize_string((string) ($payload['descripcion'] ?? ''), 1000);
    $telefonoContacto  = sanitize_string((string) ($payload['telefono_contacto'] ?? ''), 15);
    $fechaAsignacion   = (string) ($payload['fecha_asignacion'] ?? '');

    if ($idObra <= 0) {
        send_error('id_obra es requerido.', 422);
    }
    if ($idUsuarioAsignado <= 0) {
        send_error('id_usuario_asignado es requerido.', 422);
    }
    if ($descripcion === '') {
        send_error('La descripción es requerida.', 422);
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaAsignacion) || !strtotime($fechaAsignacion)) {
        send_error('Fecha de asignación inválida (formato YYYY-MM-DD).', 422);
    }
    if (!usuarioTieneAccesoObra($pdo, (int) $_SESSION['id_usuario'], (string) $_SESSION['rol'], $idObra)) {
        send_error('No tienes acceso a esta obra.', 403);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO `tareas` (`id_obra`, `id_usuario_asignado`, `descripcion`, `telefono_contacto`, `fecha_asignacion`)
         VALUES (:id_obra, :id_usuario_asignado, :descripcion, :telefono_contacto, :fecha_asignacion)'
    );
    $stmt->execute([
        ':id_obra'              => $idObra,
        ':id_usuario_asignado'  => $idUsuarioAsignado,
        ':descripcion'          => $descripcion,
        ':telefono_contacto'    => $telefonoContacto !== '' ? $telefonoContacto : null,
        ':fecha_asignacion'     => $fechaAsignacion,
    ]);

    send_success('Tarea creada.', ['id' => (int) $pdo->lastInsertId()], 201);
}

function accionCambiarEstatus(\PDO $pdo): never
{
    $payload = json_decode((string) file_get_contents('php://input'), true) ?: [];

    $valoresPermitidos = ['pendiente', 'en_proceso', 'completada'];
    $id      = (int) ($payload['id'] ?? 0);
    $estatus = (string) ($payload['estatus'] ?? '');

    if ($id <= 0) {
        send_error('id es requerido.', 422);
    }
    if (!in_array($estatus, $valoresPermitidos, true)) {
        send_error('Estatus inválido.', 422);
    }

    // Verifica acceso a la obra dueña de la tarea antes de mutar.
    $check = $pdo->prepare('SELECT `id_obra` FROM `tareas` WHERE `id` = :id LIMIT 1');
    $check->execute([':id' => $id]);
    $tarea = $check->fetch(\PDO::FETCH_ASSOC);

    if ($tarea === false) {
        send_error('Tarea no encontrada.', 404);
    }
    if (!usuarioTieneAccesoObra($pdo, (int) $_SESSION['id_usuario'], (string) $_SESSION['rol'], (int) $tarea['id_obra'])) {
        send_error('No tienes acceso a esta obra.', 403);
    }

    $stmt = $pdo->prepare('UPDATE `tareas` SET `estatus` = :estatus WHERE `id` = :id');
    $stmt->execute([':estatus' => $estatus, ':id' => $id]);

    send_success('Estatus actualizado.', ['id' => $id, 'estatus' => $estatus]);
}
