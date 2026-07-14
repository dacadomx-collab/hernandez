<?php

declare(strict_types=1);

// =============================================================================
// api/crear_usuarios_test.php — Script TEMPORAL de seed de usuarios de prueba
// Mandamiento #13: Aislamiento de Entornos — SOLO corre si APP_ENV=local.
// ⚠️ ELIMINAR ANTES DE DESPLIEGUE A PRODUCCIÓN (ver checklist F en el Codex).
// =============================================================================

require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../helpers/response.php';

$database = new Database();
$envCheck = parse_ini_file(dirname(__DIR__) . '/.env', false, INI_SCANNER_RAW) ?: [];

if (($envCheck['APP_ENV'] ?? '') !== 'local') {
    send_error('Script deshabilitado fuera de APP_ENV=local.', 403);
}

$usuariosTest = [
    ['nombre' => 'Presidente Hernández', 'usuario' => 'presidente', 'rol' => 'presidente'],
    ['nombre' => 'Administrador',        'usuario' => 'admin',      'rol' => 'admin'],
    ['nombre' => 'Staff de Prueba',      'usuario' => 'staff',      'rol' => 'staff'],
];

$hash = password_hash('123456', PASSWORD_BCRYPT);

try {
    $pdo  = $database->getConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO `usuarios` (`nombre`, `usuario`, `password_hash`, `rol`)
         VALUES (:nombre, :usuario, :password_hash, :rol)
         ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`), `password_hash` = VALUES(`password_hash`), `rol` = VALUES(`rol`)'
    );

    $resultado = [];
    foreach ($usuariosTest as $u) {
        $stmt->execute([
            ':nombre'        => $u['nombre'],
            ':usuario'       => $u['usuario'],
            ':password_hash' => $hash,
            ':rol'           => $u['rol'],
        ]);
        $resultado[] = ['usuario' => $u['usuario'], 'rol' => $u['rol'], 'password' => '123456'];
    }

    send_success('Usuarios de prueba creados/actualizados.', $resultado);

} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [crear_usuarios_test] ' . $e->getMessage());
    send_error('Error al crear usuarios de prueba.', 500);
}
