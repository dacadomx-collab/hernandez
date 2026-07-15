<?php

declare(strict_types=1);

// =============================================================================
// api/modulo_4_backend.php — Backend del Módulo 4: Tiempo con la Familia
// Auth: Sesión PHP + Role: ÚNICAMENTE presidente
//
// Acciones (GET ?accion= | POST {"accion": ...}):
//   listar     GET  {}
//   crear      POST {titulo, descripcion?, fecha_inicio, fecha_fin, forzar?}
//   completar  POST {id}
//
// Alerta de Colisión: `tareas.fecha_asignacion` es DATE (sin hora) — la
// colisión solo puede detectarse a nivel de día, no de hora exacta (ver nota
// en el Codex). Si hay colisión y `forzar` no es true, NO se inserta: se
// responde con data.colision=true para que el frontend pida confirmación.
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/session_guard.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/input_sanitizer.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

checkAccess(['presidente']);

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $metodo === 'GET'
    ? (string) ($_GET['accion'] ?? '')
    : (string) (json_decode((string) file_get_contents('php://input'), true)['accion'] ?? '');

asfl_log('REQUEST', ['endpoint' => 'modulo_4_backend.php', 'method' => $metodo, 'accion' => $accion]);

try {
    $database = new Database();
    $pdo      = $database->getConnection();

    match (true) {
        $metodo === 'GET' && $accion === 'listar'     => accionListar($pdo),
        $metodo === 'POST' && $accion === 'crear'     => accionCrear($pdo),
        $metodo === 'POST' && $accion === 'completar' => accionCompletar($pdo),
        default => send_error('Acción o método no soportado.', 404),
    };

} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [modulo_4_backend] ' . $e->getMessage());
    send_error('Error al procesar la solicitud.', 500);
}

// -----------------------------------------------------------------------------

function accionListar(\PDO $pdo): never
{
    $stmt = $pdo->prepare(
        'SELECT `id`, `titulo`, `descripcion`, `fecha_inicio`, `fecha_fin`, `estatus`
         FROM `agenda_familiar`
         WHERE `id_usuario` = :id_usuario
         ORDER BY `fecha_inicio` ASC'
    );
    $stmt->execute([':id_usuario' => (int) $_SESSION['id_usuario']]);

    send_success('Eventos obtenidos.', ['eventos' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
}

function accionCrear(\PDO $pdo): never
{
    $payload = json_decode((string) file_get_contents('php://input'), true) ?: [];

    $titulo       = sanitize_string((string) ($payload['titulo'] ?? ''), 255);
    $descripcion  = sanitize_string((string) ($payload['descripcion'] ?? ''), 1000);
    $fechaInicio  = (string) ($payload['fecha_inicio'] ?? '');
    $fechaFin     = (string) ($payload['fecha_fin'] ?? '');
    $forzar       = (bool) ($payload['forzar'] ?? false);

    if ($titulo === '') {
        send_error('El título es requerido.', 422);
    }
    if (strtotime($fechaInicio) === false || strtotime($fechaFin) === false) {
        send_error('Fecha de inicio/fin inválida.', 422);
    }
    if (strtotime($fechaFin) < strtotime($fechaInicio)) {
        send_error('La fecha de fin no puede ser anterior a la de inicio.', 422);
    }

    // ── Alerta de Colisión: ¿hay alguna tarea de obra el mismo día? ─────────
    $check = $pdo->prepare(
        "SELECT COUNT(*) FROM `tareas`
         WHERE `estatus` != 'completada'
           AND `fecha_asignacion` BETWEEN DATE(:fecha_inicio) AND DATE(:fecha_fin)"
    );
    $check->execute([':fecha_inicio' => $fechaInicio, ':fecha_fin' => $fechaFin]);
    $hayColision = ((int) $check->fetchColumn()) > 0;

    if ($hayColision && !$forzar) {
        send_success('Colisión detectada.', [
            'colision' => true,
            'mensaje'  => '¡Atención! Este horario empalma con una tarea de obra.',
        ]);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO `agenda_familiar` (`id_usuario`, `titulo`, `descripcion`, `fecha_inicio`, `fecha_fin`)
         VALUES (:id_usuario, :titulo, :descripcion, :fecha_inicio, :fecha_fin)'
    );
    $stmt->execute([
        ':id_usuario'   => (int) $_SESSION['id_usuario'],
        ':titulo'       => $titulo,
        ':descripcion'  => $descripcion !== '' ? $descripcion : null,
        ':fecha_inicio' => $fechaInicio,
        ':fecha_fin'    => $fechaFin,
    ]);

    send_success('Evento familiar agendado.', ['colision' => false, 'id' => (int) $pdo->lastInsertId()], 201);
}

function accionCompletar(\PDO $pdo): never
{
    $payload = json_decode((string) file_get_contents('php://input'), true) ?: [];
    $id = (int) ($payload['id'] ?? 0);

    if ($id <= 0) {
        send_error('id es requerido.', 422);
    }

    $stmt = $pdo->prepare(
        "UPDATE `agenda_familiar` SET `estatus` = 'completada' WHERE `id` = :id AND `id_usuario` = :id_usuario"
    );
    $stmt->execute([':id' => $id, ':id_usuario' => (int) $_SESSION['id_usuario']]);

    if ($stmt->rowCount() === 0) {
        send_error('Evento no encontrado.', 404);
    }

    send_success('Evento marcado como completado.', ['id' => $id]);
}
