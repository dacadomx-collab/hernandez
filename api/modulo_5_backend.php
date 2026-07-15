<?php

declare(strict_types=1);

// =============================================================================
// api/modulo_5_backend.php — Backend del Módulo 5: Llamamiento SUD
// Auth: Sesión PHP + Role: ÚNICAMENTE presidente
//
// Acciones (GET ?accion= | POST {"accion": ...}):
//   listar         GET  {}                    → los registros con pin_requerido=1 vienen
//                                                enmascarados (título/descripción) salvo
//                                                que la sesión tenga el PIN vigente.
//   crear          POST {titulo, descripcion?, categoria}
//   completar      POST {id}
//   verificar_pin  POST {pin}                 → valida contra SUD_PIN_HASH (.env),
//                                                abre una ventana de 2 minutos en sesión.
//
// Seguridad: el enmascarado ocurre en el servidor, no es un truco visual de
// JS — sin PIN vigente, el HTML/JSON nunca contiene el texto confidencial.
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/session_guard.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/input_sanitizer.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

checkAccess(['presidente']);

const SUD_PIN_VENTANA_SEGUNDOS = 120; // 2 minutos — igual al bloqueo por inactividad del frontend

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $metodo === 'GET'
    ? (string) ($_GET['accion'] ?? '')
    : (string) (json_decode((string) file_get_contents('php://input'), true)['accion'] ?? '');

asfl_log('REQUEST', ['endpoint' => 'modulo_5_backend.php', 'method' => $metodo, 'accion' => $accion]);

try {
    $database = new Database();
    $pdo      = $database->getConnection();

    match (true) {
        $metodo === 'GET' && $accion === 'listar'          => accionListar($pdo),
        $metodo === 'POST' && $accion === 'crear'          => accionCrear($pdo),
        $metodo === 'POST' && $accion === 'completar'      => accionCompletar($pdo),
        $metodo === 'POST' && $accion === 'verificar_pin'  => accionVerificarPin(),
        default => send_error('Acción o método no soportado.', 404),
    };

} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [modulo_5_backend] ' . $e->getMessage());
    send_error('Error al procesar la solicitud.', 500);
}

// -----------------------------------------------------------------------------

function pinVigente(): bool
{
    $desbloqueadoHasta = (int) ($_SESSION['sud_pin_ok_hasta'] ?? 0);
    return $desbloqueadoHasta > time();
}

function accionListar(\PDO $pdo): never
{
    $stmt = $pdo->prepare(
        'SELECT `id`, `titulo`, `descripcion`, `categoria`, `estatus`, `pin_requerido`
         FROM `pendientes_sud`
         WHERE `id_usuario` = :id_usuario
         ORDER BY `created_at` DESC'
    );
    $stmt->execute([':id_usuario' => (int) $_SESSION['id_usuario']]);
    $registros = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    $desbloqueado = pinVigente();

    $registros = array_map(static function (array $r) use ($desbloqueado): array {
        if ((int) $r['pin_requerido'] === 1 && !$desbloqueado) {
            $r['titulo']      = '🔒 Contenido confidencial';
            $r['descripcion'] = null;
            $r['bloqueado']   = true;
        } else {
            $r['bloqueado'] = false;
        }
        return $r;
    }, $registros);

    send_success('Pendientes obtenidos.', ['pendientes' => $registros, 'pin_desbloqueado' => $desbloqueado]);
}

function accionCrear(\PDO $pdo): never
{
    $payload = json_decode((string) file_get_contents('php://input'), true) ?: [];

    $categoriasPermitidas = ['urgente_confidencial', 'operativo', 'largo_plazo'];
    $titulo      = sanitize_string((string) ($payload['titulo'] ?? ''), 255);
    $descripcion = sanitize_string((string) ($payload['descripcion'] ?? ''), 1000);
    $categoria   = (string) ($payload['categoria'] ?? '');

    if ($titulo === '') {
        send_error('El título es requerido.', 422);
    }
    if (!in_array($categoria, $categoriasPermitidas, true)) {
        send_error('Categoría inválida.', 422);
    }

    $pinRequerido = $categoria === 'urgente_confidencial' ? 1 : 0;

    $stmt = $pdo->prepare(
        'INSERT INTO `pendientes_sud` (`id_usuario`, `titulo`, `descripcion`, `categoria`, `pin_requerido`)
         VALUES (:id_usuario, :titulo, :descripcion, :categoria, :pin_requerido)'
    );
    $stmt->execute([
        ':id_usuario'    => (int) $_SESSION['id_usuario'],
        ':titulo'        => $titulo,
        ':descripcion'   => $descripcion !== '' ? $descripcion : null,
        ':categoria'     => $categoria,
        ':pin_requerido' => $pinRequerido,
    ]);

    send_success('Pendiente registrado.', ['id' => (int) $pdo->lastInsertId()], 201);
}

function accionCompletar(\PDO $pdo): never
{
    $payload = json_decode((string) file_get_contents('php://input'), true) ?: [];
    $id = (int) ($payload['id'] ?? 0);

    if ($id <= 0) {
        send_error('id es requerido.', 422);
    }

    $stmt = $pdo->prepare(
        "UPDATE `pendientes_sud` SET `estatus` = 'completado' WHERE `id` = :id AND `id_usuario` = :id_usuario"
    );
    $stmt->execute([':id' => $id, ':id_usuario' => (int) $_SESSION['id_usuario']]);

    if ($stmt->rowCount() === 0) {
        send_error('Pendiente no encontrado.', 404);
    }

    send_success('Pendiente marcado como completado.', ['id' => $id]);
}

function accionVerificarPin(): never
{
    $payload = json_decode((string) file_get_contents('php://input'), true) ?: [];
    $pin = (string) ($payload['pin'] ?? '');

    $env  = parse_ini_file(dirname(__DIR__) . '/.env', false, INI_SCANNER_RAW) ?: [];
    $hash = (string) ($env['SUD_PIN_HASH'] ?? '');

    if ($hash === '' || !password_verify($pin, $hash)) {
        send_error('PIN incorrecto.', 401);
    }

    $_SESSION['sud_pin_ok_hasta'] = time() + SUD_PIN_VENTANA_SEGUNDOS;

    send_success('PIN verificado.', ['vigente_segundos' => SUD_PIN_VENTANA_SEGUNDOS]);
}
