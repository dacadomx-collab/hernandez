<?php

declare(strict_types=1);

// =============================================================================
// helpers/obra_access.php — Verificación de acceso a obra por rol
// 'admin' y 'presidente' tienen acceso a todas las obras. 'staff' solo a las
// asignadas en usuarios_obras (Módulo 1 y 2).
// =============================================================================

function usuarioTieneAccesoObra(\PDO $pdo, int $idUsuario, string $rol, int $idObra): bool
{
    if ($rol !== 'staff') {
        return true;
    }

    $stmt = $pdo->prepare(
        'SELECT 1 FROM `usuarios_obras` WHERE `id_usuario` = :id_usuario AND `id_obra` = :id_obra LIMIT 1'
    );
    $stmt->execute([':id_usuario' => $idUsuario, ':id_obra' => $idObra]);

    return $stmt->fetchColumn() !== false;
}
