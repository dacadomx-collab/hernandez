<?php

declare(strict_types=1);

// =============================================================================
// api/session_guard.php — Guard de Sesión PHP Nativa + RBAC (Pte_Hernandez_LaPazBCS)
// Reemplaza al guard JWT (archivado en backups_y_pruebas/auth_middleware.php)
// tras el cambio de auth_login.php a $_SESSION. Mandamiento #14: CORS ≠ Auth.
//
// Uso en cualquier endpoint/vista protegida:
//   require_once __DIR__ . '/session_guard.php';
//   checkAccess(['admin', 'presidente']);
// =============================================================================

require_once __DIR__ . '/../helpers/response.php';

/**
 * Verifica que exista una sesión PHP activa y que su rol esté dentro de
 * $allowedRoles. Termina la ejecución con 401 (sin sesión) o 403 (rol sin
 * permiso) respondiendo el Response Contract estricto {status,message,data}.
 *
 * @param array<int,string> $allowedRoles Roles válidos: 'admin', 'staff', 'presidente'
 */
function checkAccess(array $allowedRoles): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ]);
    }

    if (empty($_SESSION['id_usuario']) || empty($_SESSION['rol'])) {
        send_error('No autenticado. Inicia sesión para continuar.', 401);
    }

    $rol = (string) $_SESSION['rol'];

    if (!in_array($rol, $allowedRoles, true)) {
        send_error('No tienes permisos para acceder a este recurso.', 403);
    }
}
