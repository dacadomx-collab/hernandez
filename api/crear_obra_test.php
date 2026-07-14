<?php

declare(strict_types=1);

// =============================================================================
// api/crear_obra_test.php — Script TEMPORAL de seed de una obra de prueba
// Mandamiento #13: Aislamiento de Entornos — SOLO corre si APP_ENV=local.
// ⚠️ ELIMINAR ANTES DE DESPLIEGUE A PRODUCCIÓN.
// =============================================================================

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../helpers/response.php';

$database = new Database();
$envCheck = parse_ini_file(dirname(__DIR__) . '/.env', false, INI_SCANNER_RAW) ?: [];

if (($envCheck['APP_ENV'] ?? '') !== 'local') {
    send_error('Script deshabilitado fuera de APP_ENV=local.', 403);
}

try {
    $pdo  = $database->getConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO `obras` (`nombre`, `descripcion`, `ubicacion`, `estatus`)
         VALUES (:nombre, :descripcion, :ubicacion, :estatus)'
    );
    $stmt->execute([
        ':nombre'      => 'Obra de Prueba — Fraccionamiento Las Palmas',
        ':descripcion' => 'Registro de prueba para validar el Módulo 1.',
        ':ubicacion'   => 'La Paz, BCS',
        ':estatus'     => 'activa',
    ]);

    send_success('Obra de prueba creada.', ['id' => (int) $pdo->lastInsertId()]);

} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [crear_obra_test] ' . $e->getMessage());
    send_error('Error al crear la obra de prueba.', 500);
}
