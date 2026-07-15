<?php

declare(strict_types=1);

// =============================================================================
// api/admin_obras_backend.php — Alta y edición de Obras (Administración)
// Auth: Sesión PHP + Role: admin, presidente
//
// Acciones (GET ?accion= | POST {"accion": ...}):
//   listar      GET  {}                → todas las obras (sin filtro de staff)
//   crear       POST {nombre, descripcion?, ubicacion?, estatus?}
//   actualizar  POST {id, nombre, descripcion?, ubicacion?, estatus}
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/session_guard.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/input_sanitizer.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

checkAccess(['admin', 'presidente']);

const ESTATUS_PERMITIDOS = ['activa', 'pausada', 'finalizada'];

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $metodo === 'GET'
    ? (string) ($_GET['accion'] ?? '')
    : (string) (json_decode((string) file_get_contents('php://input'), true)['accion'] ?? '');

asfl_log('REQUEST', ['endpoint' => 'admin_obras_backend.php', 'method' => $metodo, 'accion' => $accion]);

try {
    $database = new Database();
    $pdo      = $database->getConnection();

    match (true) {
        $metodo === 'GET' && $accion === 'listar'      => accionListar($pdo),
        $metodo === 'POST' && $accion === 'crear'      => accionCrear($pdo),
        $metodo === 'POST' && $accion === 'actualizar' => accionActualizar($pdo),
        default => send_error('Acción o método no soportado.', 404),
    };

} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [admin_obras_backend] ' . $e->getMessage());
    send_error('Error al procesar la solicitud.', 500);
}

// -----------------------------------------------------------------------------

function accionListar(\PDO $pdo): never
{
    $stmt = $pdo->query('SELECT `id`, `nombre`, `descripcion`, `ubicacion`, `estatus` FROM `obras` ORDER BY `nombre` ASC');
    send_success('Obras obtenidas.', ['obras' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
}

function accionCrear(\PDO $pdo): never
{
    $payload = json_decode((string) file_get_contents('php://input'), true) ?: [];

    $nombre      = sanitize_string((string) ($payload['nombre'] ?? ''), 150);
    $descripcion = sanitize_string((string) ($payload['descripcion'] ?? ''), 2000);
    $ubicacion   = sanitize_string((string) ($payload['ubicacion'] ?? ''), 255);
    $estatus     = (string) ($payload['estatus'] ?? 'activa');

    if ($nombre === '') {
        send_error('El nombre de la obra es requerido.', 422);
    }
    if (!in_array($estatus, ESTATUS_PERMITIDOS, true)) {
        send_error('Estatus inválido.', 422);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO `obras` (`nombre`, `descripcion`, `ubicacion`, `estatus`)
         VALUES (:nombre, :descripcion, :ubicacion, :estatus)'
    );
    $stmt->execute([
        ':nombre'      => $nombre,
        ':descripcion' => $descripcion !== '' ? $descripcion : null,
        ':ubicacion'   => $ubicacion !== '' ? $ubicacion : null,
        ':estatus'     => $estatus,
    ]);

    send_success('Obra creada.', ['id' => (int) $pdo->lastInsertId()], 201);
}

function accionActualizar(\PDO $pdo): never
{
    $payload = json_decode((string) file_get_contents('php://input'), true) ?: [];

    $id          = (int) ($payload['id'] ?? 0);
    $nombre      = sanitize_string((string) ($payload['nombre'] ?? ''), 150);
    $descripcion = sanitize_string((string) ($payload['descripcion'] ?? ''), 2000);
    $ubicacion   = sanitize_string((string) ($payload['ubicacion'] ?? ''), 255);
    $estatus     = (string) ($payload['estatus'] ?? '');

    if ($id <= 0) {
        send_error('id es requerido.', 422);
    }
    if ($nombre === '') {
        send_error('El nombre de la obra es requerido.', 422);
    }
    if (!in_array($estatus, ESTATUS_PERMITIDOS, true)) {
        send_error('Estatus inválido.', 422);
    }

    $existe = $pdo->prepare('SELECT 1 FROM `obras` WHERE `id` = :id LIMIT 1');
    $existe->execute([':id' => $id]);
    if ($existe->fetchColumn() === false) {
        send_error('Obra no encontrada.', 404);
    }

    $stmt = $pdo->prepare(
        'UPDATE `obras` SET `nombre` = :nombre, `descripcion` = :descripcion, `ubicacion` = :ubicacion, `estatus` = :estatus
         WHERE `id` = :id'
    );
    $stmt->execute([
        ':nombre'      => $nombre,
        ':descripcion' => $descripcion !== '' ? $descripcion : null,
        ':ubicacion'   => $ubicacion !== '' ? $ubicacion : null,
        ':estatus'     => $estatus,
        ':id'          => $id,
    ]);

    send_success('Obra actualizada.', ['id' => $id]);
}
