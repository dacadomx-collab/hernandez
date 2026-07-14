<?php

declare(strict_types=1);

// =============================================================================
// api/permisos_update.php — Actualiza un semáforo de permisos de una obra
// Método: POST {id_obra, campo, valor} | Auth: Sesión PHP + Role: admin, staff, presidente
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/session_guard.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/obra_access.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

checkAccess(['admin', 'staff', 'presidente']);

asfl_log('REQUEST', ['endpoint' => 'permisos_update.php', 'method' => $_SERVER['REQUEST_METHOD']]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Método no permitido.', 405);
}

try {
    $payload = json_decode((string) file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
} catch (\JsonException) {
    send_error('Payload JSON inválido.', 400);
}

// Whitelist estricta: el nombre de columna nunca se parametriza en PDO, por lo
// que se valida contra esta lista cerrada antes de interpolarlo en el SQL.
$camposPermitidos = ['estatus_subdivision', 'estatus_licencia', 'estatus_terminacion'];
$valoresPermitidos = ['rojo', 'amarillo', 'verde'];

$idObra = (int) ($payload['id_obra'] ?? 0);
$campo  = (string) ($payload['campo'] ?? '');
$valor  = (string) ($payload['valor'] ?? '');

if ($idObra <= 0) {
    send_error('id_obra es requerido.', 422);
}
if (!in_array($campo, $camposPermitidos, true)) {
    send_error('Campo de permiso inválido.', 422);
}
if (!in_array($valor, $valoresPermitidos, true)) {
    send_error('Valor de semáforo inválido.', 422);
}

try {
    $database = new Database();
    $pdo      = $database->getConnection();

    if (!usuarioTieneAccesoObra($pdo, (int) $_SESSION['id_usuario'], (string) $_SESSION['rol'], $idObra)) {
        send_error('No tienes acceso a esta obra.', 403);
    }

    // Asegura que exista el registro 1:1 antes de actualizar.
    $insert = $pdo->prepare('INSERT IGNORE INTO `permisos_obra` (`id_obra`) VALUES (:id_obra)');
    $insert->execute([':id_obra' => $idObra]);

    $stmt = $pdo->prepare("UPDATE `permisos_obra` SET `{$campo}` = :valor WHERE `id_obra` = :id_obra");
    $stmt->execute([':valor' => $valor, ':id_obra' => $idObra]);

    asfl_log('RESPONSE', ['endpoint' => 'permisos_update.php', 'status' => 'success', 'id_obra' => $idObra, 'campo' => $campo, 'valor' => $valor]);

    send_success('Semáforo actualizado.', ['id_obra' => $idObra, 'campo' => $campo, 'valor' => $valor]);

} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [permisos_update] ' . $e->getMessage());
    send_error('Error al actualizar el permiso.', 500);
}
