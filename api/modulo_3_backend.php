<?php

declare(strict_types=1);

// =============================================================================
// api/modulo_3_backend.php — Backend del Módulo 3: 40 Pendientes
// Auth: Sesión PHP + Role: ÚNICAMENTE presidente
//
// Acciones (GET ?accion= | POST {"accion": ...}):
//   listar    GET  {}                     → todos los pendientes del presidente en sesión
//   crear     POST {titulo, categoria}
//   completar POST {id}
//   eliminar  POST {id}
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

asfl_log('REQUEST', ['endpoint' => 'modulo_3_backend.php', 'method' => $metodo, 'accion' => $accion]);

try {
    $database = new Database();
    $pdo      = $database->getConnection();

    match (true) {
        $metodo === 'GET' && $accion === 'listar'      => accionListar($pdo),
        $metodo === 'POST' && $accion === 'crear'      => accionCrear($pdo),
        $metodo === 'POST' && $accion === 'completar'  => accionCompletar($pdo),
        $metodo === 'POST' && $accion === 'eliminar'   => accionEliminar($pdo),
        default => send_error('Acción o método no soportado.', 404),
    };

} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [modulo_3_backend] ' . $e->getMessage());
    send_error('Error al procesar la solicitud.', 500);
}

// -----------------------------------------------------------------------------

function accionListar(\PDO $pdo): never
{
    $stmt = $pdo->prepare(
        'SELECT `id`, `titulo`, `categoria`, `estatus`
         FROM `pendientes`
         WHERE `id_usuario` = :id_usuario
         ORDER BY `created_at` DESC'
    );
    $stmt->execute([':id_usuario' => (int) $_SESSION['id_usuario']]);

    send_success('Pendientes obtenidos.', ['pendientes' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
}

function accionCrear(\PDO $pdo): never
{
    $payload = json_decode((string) file_get_contents('php://input'), true) ?: [];

    $categoriasPermitidas = ['urgente', 'no_importante', 'largo_plazo'];
    $titulo    = sanitize_string((string) ($payload['titulo'] ?? ''), 255);
    $categoria = (string) ($payload['categoria'] ?? '');

    if ($titulo === '') {
        send_error('El título es requerido.', 422);
    }
    if (!in_array($categoria, $categoriasPermitidas, true)) {
        send_error('Categoría inválida.', 422);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO `pendientes` (`id_usuario`, `titulo`, `categoria`) VALUES (:id_usuario, :titulo, :categoria)'
    );
    $stmt->execute([
        ':id_usuario' => (int) $_SESSION['id_usuario'],
        ':titulo'     => $titulo,
        ':categoria'  => $categoria,
    ]);

    send_success('Pendiente creado.', ['id' => (int) $pdo->lastInsertId()], 201);
}

function accionCompletar(\PDO $pdo): never
{
    $payload = json_decode((string) file_get_contents('php://input'), true) ?: [];
    $id = (int) ($payload['id'] ?? 0);

    if ($id <= 0) {
        send_error('id es requerido.', 422);
    }

    $stmt = $pdo->prepare(
        "UPDATE `pendientes` SET `estatus` = 'completado' WHERE `id` = :id AND `id_usuario` = :id_usuario"
    );
    $stmt->execute([':id' => $id, ':id_usuario' => (int) $_SESSION['id_usuario']]);

    if ($stmt->rowCount() === 0) {
        send_error('Pendiente no encontrado.', 404);
    }

    send_success('Pendiente marcado como completado.', ['id' => $id]);
}

function accionEliminar(\PDO $pdo): never
{
    $payload = json_decode((string) file_get_contents('php://input'), true) ?: [];
    $id = (int) ($payload['id'] ?? 0);

    if ($id <= 0) {
        send_error('id es requerido.', 422);
    }

    $stmt = $pdo->prepare('DELETE FROM `pendientes` WHERE `id` = :id AND `id_usuario` = :id_usuario');
    $stmt->execute([':id' => $id, ':id_usuario' => (int) $_SESSION['id_usuario']]);

    if ($stmt->rowCount() === 0) {
        send_error('Pendiente no encontrado.', 404);
    }

    send_success('Pendiente eliminado.', ['id' => $id]);
}
