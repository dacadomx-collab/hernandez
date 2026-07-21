<?php

declare(strict_types=1);

// =============================================================================
// api/presupuestos_backend.php — Presupuesto vs. Gasto Real por Obra (Módulo 1)
// Auth: Sesión PHP + Role: admin, staff, presidente ('crear' solo admin/presidente)
//
// Acciones (GET ?accion= | POST {"accion": ...}):
//   listar  GET  {id_obra}                              → conceptos presupuestados + monto_gastado (subquery gastos)
//   crear   POST {id_obra, etapa, concepto, monto_objetivo} → solo admin/presidente
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/session_guard.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/input_sanitizer.php';
require_once __DIR__ . '/../helpers/obra_access.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

checkAccess(['admin', 'staff', 'presidente']);

const ETAPAS_PERMITIDAS = ['obras_base', 'obra_negra', 'terminacion'];

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $metodo === 'GET'
    ? (string) ($_GET['accion'] ?? '')
    : (string) (json_decode((string) file_get_contents('php://input'), true)['accion'] ?? '');

asfl_log('REQUEST', ['endpoint' => 'presupuestos_backend.php', 'method' => $metodo, 'accion' => $accion]);

try {
    $database = new Database();
    $pdo      = $database->getConnection();

    match (true) {
        $metodo === 'GET' && $accion === 'listar' => accionListar($pdo),
        $metodo === 'POST' && $accion === 'crear' => accionCrear($pdo),
        default => send_error('Acción o método no soportado.', 404),
    };

} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [presupuestos_backend] ' . $e->getMessage());
    send_error('Error al procesar la solicitud.', 500);
}

// -----------------------------------------------------------------------------

function accionListar(\PDO $pdo): never
{
    $idObra = sanitize_int($_GET['id_obra'] ?? null, 0);

    if ($idObra <= 0) {
        send_error('id_obra es requerido.', 422);
    }
    if (!usuarioTieneAccesoObra($pdo, (int) $_SESSION['id_usuario'], (string) $_SESSION['rol'], $idObra)) {
        send_error('No tienes acceso a esta obra.', 403);
    }

    $stmt = $pdo->prepare(
        'SELECT p.`id`, p.`id_obra`, p.`etapa`, p.`concepto`, p.`monto_objetivo`,
                COALESCE(SUM(g.`monto`), 0) AS `monto_gastado`
         FROM `presupuestos_obra` p
         LEFT JOIN `gastos` g ON g.`id_presupuesto` = p.`id`
         WHERE p.`id_obra` = :id_obra
         GROUP BY p.`id`, p.`id_obra`, p.`etapa`, p.`concepto`, p.`monto_objetivo`
         ORDER BY FIELD(p.`etapa`, \'obras_base\', \'obra_negra\', \'terminacion\'), p.`concepto` ASC'
    );
    $stmt->execute([':id_obra' => $idObra]);

    $presupuestos = array_map(static function (array $r): array {
        $r['monto_objetivo'] = (float) $r['monto_objetivo'];
        $r['monto_gastado']  = (float) $r['monto_gastado'];
        return $r;
    }, $stmt->fetchAll(\PDO::FETCH_ASSOC));

    send_success('Presupuestos obtenidos.', ['presupuestos' => $presupuestos]);
}

function accionCrear(\PDO $pdo): never
{
    if (!in_array((string) $_SESSION['rol'], ['admin', 'presidente'], true)) {
        send_error('No tienes permisos para definir presupuestos.', 403);
    }

    $payload = json_decode((string) file_get_contents('php://input'), true) ?: [];

    $idObra        = (int) ($payload['id_obra'] ?? 0);
    $etapa         = (string) ($payload['etapa'] ?? '');
    $concepto      = sanitize_string((string) ($payload['concepto'] ?? ''), 100);
    $montoObjetivo = (string) ($payload['monto_objetivo'] ?? '');

    if ($idObra <= 0) {
        send_error('id_obra es requerido.', 422);
    }
    if (!in_array($etapa, ETAPAS_PERMITIDAS, true)) {
        send_error('Etapa inválida.', 422);
    }
    if ($concepto === '') {
        send_error('El concepto es requerido.', 422);
    }
    if (!is_numeric($montoObjetivo) || (float) $montoObjetivo <= 0) {
        send_error('El monto objetivo debe ser un número mayor a 0.', 422);
    }

    $existeObra = $pdo->prepare('SELECT 1 FROM `obras` WHERE `id` = :id_obra LIMIT 1');
    $existeObra->execute([':id_obra' => $idObra]);
    if ($existeObra->fetchColumn() === false) {
        send_error('Obra no encontrada.', 404);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO `presupuestos_obra` (`id_obra`, `etapa`, `concepto`, `monto_objetivo`)
         VALUES (:id_obra, :etapa, :concepto, :monto_objetivo)'
    );
    $stmt->execute([
        ':id_obra'        => $idObra,
        ':etapa'          => $etapa,
        ':concepto'       => $concepto,
        ':monto_objetivo' => round((float) $montoObjetivo, 2),
    ]);

    send_success('Concepto de presupuesto creado.', ['id' => (int) $pdo->lastInsertId()], 201);
}
