<?php

declare(strict_types=1);

// =============================================================================
// api/gastos_create.php — Registra un gasto (Captura Express de recibo)
// Método: POST multipart/form-data {id_obra, concepto, monto, fecha_gasto, foto?}
// Auth: Sesión PHP + Role: admin, staff, presidente
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/session_guard.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/input_sanitizer.php';
require_once __DIR__ . '/../helpers/obra_access.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

checkAccess(['admin', 'staff', 'presidente']);

asfl_log('REQUEST', ['endpoint' => 'gastos_create.php', 'method' => $_SERVER['REQUEST_METHOD']]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Método no permitido.', 405);
}

$idObra     = (int) ($_POST['id_obra'] ?? 0);
$concepto   = sanitize_string((string) ($_POST['concepto'] ?? ''), 100);
$montoRaw   = (string) ($_POST['monto'] ?? '');
$fechaGasto = (string) ($_POST['fecha_gasto'] ?? '');

if ($idObra <= 0) {
    send_error('id_obra es requerido.', 422);
}
if ($concepto === '') {
    send_error('El concepto es requerido.', 422);
}
if (!is_numeric($montoRaw) || (float) $montoRaw <= 0) {
    send_error('El monto debe ser un número mayor a 0.', 422);
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaGasto) || !strtotime($fechaGasto)) {
    send_error('La fecha del gasto es inválida (formato YYYY-MM-DD).', 422);
}

$monto = round((float) $montoRaw, 2);

// ── Captura Express: foto de recibo (opcional) ──────────────────────────────
$fotoTicketPath = null;
$mimesPermitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
$maxBytes = 8 * 1024 * 1024; // 8 MB

if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        send_error('Error al subir la foto del recibo.', 422);
    }
    if ($_FILES['foto']['size'] > $maxBytes) {
        send_error('La foto excede el tamaño máximo permitido (8 MB).', 422);
    }

    $mimeReal = (string) mime_content_type($_FILES['foto']['tmp_name']);
    if (!isset($mimesPermitidos[$mimeReal])) {
        send_error('Formato de imagen no permitido. Usa JPG, PNG o WEBP.', 422);
    }

    $extension    = $mimesPermitidos[$mimeReal];
    $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $extension;
    $rutaDestino   = dirname(__DIR__) . '/uploads/gastos/' . $nombreArchivo;

    if (!move_uploaded_file($_FILES['foto']['tmp_name'], $rutaDestino)) {
        send_error('No se pudo guardar la foto del recibo.', 500);
    }

    $fotoTicketPath = 'uploads/gastos/' . $nombreArchivo;
}

try {
    $database = new Database();
    $pdo      = $database->getConnection();

    if (!usuarioTieneAccesoObra($pdo, (int) $_SESSION['id_usuario'], (string) $_SESSION['rol'], $idObra)) {
        send_error('No tienes acceso a esta obra.', 403);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO `gastos` (`id_obra`, `id_usuario`, `concepto`, `monto`, `fecha_gasto`, `foto_ticket`)
         VALUES (:id_obra, :id_usuario, :concepto, :monto, :fecha_gasto, :foto_ticket)'
    );
    $stmt->execute([
        ':id_obra'     => $idObra,
        ':id_usuario'  => (int) $_SESSION['id_usuario'],
        ':concepto'    => $concepto,
        ':monto'       => $monto,
        ':fecha_gasto' => $fechaGasto,
        ':foto_ticket' => $fotoTicketPath,
    ]);

    $idGasto = (int) $pdo->lastInsertId();

    asfl_log('RESPONSE', ['endpoint' => 'gastos_create.php', 'status' => 'success', 'id_gasto' => $idGasto]);

    send_success('Gasto registrado.', [
        'id'          => $idGasto,
        'id_obra'     => $idObra,
        'concepto'    => $concepto,
        'monto'       => $monto,
        'fecha_gasto' => $fechaGasto,
        'foto_ticket' => $fotoTicketPath,
    ], 201);

} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [gastos_create] ' . $e->getMessage());
    send_error('Error al registrar el gasto.', 500);
}
