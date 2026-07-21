<?php

declare(strict_types=1);

// =============================================================================
// api/biometria_backend.php — Desbloqueo Biométrico (WebAuthn) para Módulo 5
// Auth: Sesión PHP + Role: ÚNICAMENTE presidente
//
// Usa la librería vendorizada libs/webauthn (lbuchs/WebAuthn v2.2.0, MIT) —
// hace la verificación criptográfica real (CBOR/COSE, firma ES256/RS256,
// sign_count anti-clonación). Nunca se implementa esa verificación a mano.
//
// Acciones (GET ?accion= | POST {"accion": ...}):
//   estado              GET  {}                                    → si el usuario ya tiene credencial registrada
//   registro_challenge  GET  {}                                    → reto para navigator.credentials.create()
//   guardar_credencial  POST {clientDataJSON, attestationObject}    → valida y guarda en usuarios_biometria
//   login_challenge     GET  {}                                    → reto para navigator.credentials.get()
//   verificar           POST {credential_id, clientDataJSON, authenticatorData, signature}
//                                                                   → valida firma y abre la ventana de 120s (igual que el PIN)
//
// Todos los campos binarios (challenge, clientDataJSON, attestationObject,
// authenticatorData, signature, credential_id) viajan como string base64url
// entre el navegador y este endpoint.
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/session_guard.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';
require_once __DIR__ . '/../libs/webauthn/src/WebAuthn.php';

use lbuchs\WebAuthn\WebAuthn;
use lbuchs\WebAuthn\Binary\ByteBuffer;
use lbuchs\WebAuthn\Attestation\AuthenticatorData;
use lbuchs\WebAuthn\WebAuthnException;

checkAccess(['presidente']);

// Misma ventana que el PIN de privacidad (api/modulo_5_backend.php::SUD_PIN_VENTANA_SEGUNDOS)
// — el desbloqueo biométrico es un método alterno de abrir la misma ventana de sesión.
const BIOMETRIA_VENTANA_SEGUNDOS = 120;

function getRpId(): string
{
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    return explode(':', $host)[0];
}

function crearWebAuthn(): WebAuthn
{
    // 'none' fuerza attestation anónima (estándar para RPs que no necesitan
    // certificar el fabricante del autenticador) — evita depender de cadenas
    // de certificados raíz de Apple/Android/TPM que no vendorizamos.
    return new WebAuthn('Pte. Hernandez LaPazBCS', getRpId(), ['none'], true);
}

function base64UrlDecode(string $data): string
{
    return (string) base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4), true);
}

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $metodo === 'GET'
    ? (string) ($_GET['accion'] ?? '')
    : (string) (json_decode((string) file_get_contents('php://input'), true)['accion'] ?? '');

asfl_log('REQUEST', ['endpoint' => 'biometria_backend.php', 'method' => $metodo, 'accion' => $accion]);

try {
    $database = new Database();
    $pdo      = $database->getConnection();

    match (true) {
        $metodo === 'GET' && $accion === 'estado'             => accionEstado($pdo),
        $metodo === 'GET' && $accion === 'registro_challenge' => accionRegistroChallenge(),
        $metodo === 'POST' && $accion === 'guardar_credencial' => accionGuardarCredencial($pdo),
        $metodo === 'GET' && $accion === 'login_challenge'    => accionLoginChallenge($pdo),
        $metodo === 'POST' && $accion === 'verificar'          => accionVerificar($pdo),
        default => send_error('Acción o método no soportado.', 404),
    };

} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [biometria_backend] ' . $e->getMessage());
    send_error('Error al procesar la solicitud.', 500);
}

// -----------------------------------------------------------------------------

function accionEstado(\PDO $pdo): never
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM `usuarios_biometria` WHERE `id_usuario` = :id_usuario');
    $stmt->execute([':id_usuario' => (int) $_SESSION['id_usuario']]);

    send_success('Estado obtenido.', ['tiene_credencial' => ((int) $stmt->fetchColumn()) > 0]);
}

function accionRegistroChallenge(): never
{
    try {
        $webAuthn = crearWebAuthn();
        $createArgs = $webAuthn->getCreateArgs(
            (string) $_SESSION['id_usuario'],
            (string) $_SESSION['usuario'],
            (string) $_SESSION['nombre'],
            20,
            false,
            true,
            false // false = 'platform' (biométrico embebido: FaceID/TouchID/Windows Hello), no llaves USB externas
        );
    } catch (WebAuthnException $e) {
        error_log('[' . date('Y-m-d H:i:s') . '] [biometria_backend::registro_challenge] ' . $e->getMessage());
        send_error('No se pudo iniciar el registro biométrico.', 500);
    }

    $_SESSION['webauthn_reg_challenge'] = $webAuthn->getChallenge()->getBinaryString();

    send_success('Reto de registro generado.', (array) json_decode((string) json_encode($createArgs), true));
}

function accionGuardarCredencial(\PDO $pdo): never
{
    $payload = json_decode((string) file_get_contents('php://input'), true) ?: [];

    $clientDataJSON    = (string) ($payload['clientDataJSON'] ?? '');
    $attestationObject = (string) ($payload['attestationObject'] ?? '');
    $challengeBin       = (string) ($_SESSION['webauthn_reg_challenge'] ?? '');

    if ($clientDataJSON === '' || $attestationObject === '') {
        send_error('Datos de la credencial incompletos.', 422);
    }
    if ($challengeBin === '') {
        send_error('No hay un registro biométrico en curso. Intenta de nuevo.', 422);
    }

    unset($_SESSION['webauthn_reg_challenge']); // uso único, sin importar el resultado

    try {
        $webAuthn = crearWebAuthn();
        $data = $webAuthn->processCreate(
            base64UrlDecode($clientDataJSON),
            base64UrlDecode($attestationObject),
            $challengeBin,
            true
        );
    } catch (WebAuthnException $e) {
        error_log('[' . date('Y-m-d H:i:s') . '] [biometria_backend::guardar_credencial] ' . $e->getMessage());
        send_error('No se pudo verificar la credencial biométrica.', 422);
    }

    $credentialId = $data->credentialId->jsonSerialize();

    $existe = $pdo->prepare('SELECT 1 FROM `usuarios_biometria` WHERE `credential_id` = :credential_id LIMIT 1');
    $existe->execute([':credential_id' => $credentialId]);
    if ($existe->fetchColumn() !== false) {
        send_error('Esta credencial biométrica ya está registrada.', 409);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO `usuarios_biometria` (`id_usuario`, `credential_id`, `public_key`, `sign_count`)
         VALUES (:id_usuario, :credential_id, :public_key, :sign_count)'
    );
    $stmt->execute([
        ':id_usuario'    => (int) $_SESSION['id_usuario'],
        ':credential_id' => $credentialId,
        ':public_key'    => $data->credentialPublicKey,
        ':sign_count'    => $data->signatureCounter ?? 0,
    ]);

    send_success('Credencial biométrica registrada.', ['id' => (int) $pdo->lastInsertId()], 201);
}

function accionLoginChallenge(\PDO $pdo): never
{
    $stmt = $pdo->prepare('SELECT `credential_id` FROM `usuarios_biometria` WHERE `id_usuario` = :id_usuario');
    $stmt->execute([':id_usuario' => (int) $_SESSION['id_usuario']]);
    $credenciales = $stmt->fetchAll(\PDO::FETCH_COLUMN);

    if (empty($credenciales)) {
        send_error('No tienes biometría registrada.', 404);
    }

    try {
        $webAuthn = crearWebAuthn();
        $credentialIds = array_map(static fn (string $id) => ByteBuffer::fromBase64Url($id), $credenciales);
        $getArgs = $webAuthn->getGetArgs($credentialIds, 20, true, true, true, true, true, true);
    } catch (WebAuthnException $e) {
        error_log('[' . date('Y-m-d H:i:s') . '] [biometria_backend::login_challenge] ' . $e->getMessage());
        send_error('No se pudo iniciar la verificación biométrica.', 500);
    }

    $_SESSION['webauthn_login_challenge'] = $webAuthn->getChallenge()->getBinaryString();

    send_success('Reto de verificación generado.', (array) json_decode((string) json_encode($getArgs), true));
}

function accionVerificar(\PDO $pdo): never
{
    $payload = json_decode((string) file_get_contents('php://input'), true) ?: [];

    $credentialId      = (string) ($payload['credential_id'] ?? '');
    $clientDataJSON    = (string) ($payload['clientDataJSON'] ?? '');
    $authenticatorData = (string) ($payload['authenticatorData'] ?? '');
    $signature         = (string) ($payload['signature'] ?? '');
    $challengeBin       = (string) ($_SESSION['webauthn_login_challenge'] ?? '');

    if ($credentialId === '' || $clientDataJSON === '' || $authenticatorData === '' || $signature === '') {
        send_error('Datos de verificación incompletos.', 422);
    }
    if ($challengeBin === '') {
        send_error('No hay una verificación biométrica en curso. Intenta de nuevo.', 422);
    }

    unset($_SESSION['webauthn_login_challenge']); // uso único, sin importar el resultado

    // La credencial debe pertenecer al usuario en sesión — no solo existir.
    $stmt = $pdo->prepare(
        'SELECT `id`, `public_key`, `sign_count` FROM `usuarios_biometria`
         WHERE `credential_id` = :credential_id AND `id_usuario` = :id_usuario LIMIT 1'
    );
    $stmt->execute([':credential_id' => $credentialId, ':id_usuario' => (int) $_SESSION['id_usuario']]);
    $registro = $stmt->fetch(\PDO::FETCH_ASSOC);

    if ($registro === false) {
        send_error('Credencial biométrica no reconocida.', 403);
    }

    try {
        $webAuthn = crearWebAuthn();
        $authenticatorDataBin = base64UrlDecode($authenticatorData);

        $webAuthn->processGet(
            base64UrlDecode($clientDataJSON),
            $authenticatorDataBin,
            base64UrlDecode($signature),
            (string) $registro['public_key'],
            $challengeBin,
            (int) $registro['sign_count'],
            true
        );

        $nuevoContador = (new AuthenticatorData($authenticatorDataBin))->getSignCount();
    } catch (WebAuthnException $e) {
        error_log('[' . date('Y-m-d H:i:s') . '] [biometria_backend::verificar] ' . $e->getMessage());
        send_error('Verificación biométrica fallida.', 401);
    }

    $update = $pdo->prepare('UPDATE `usuarios_biometria` SET `sign_count` = :sign_count WHERE `id` = :id');
    $update->execute([':sign_count' => $nuevoContador, ':id' => (int) $registro['id']]);

    $_SESSION['sud_pin_ok_hasta'] = time() + BIOMETRIA_VENTANA_SEGUNDOS;

    send_success('Verificación biométrica exitosa.', ['vigente_segundos' => BIOMETRIA_VENTANA_SEGUNDOS]);
}
