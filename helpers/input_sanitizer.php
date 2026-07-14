<?php

declare(strict_types=1);

// =============================================================================
// helpers/input_sanitizer.php — Sanitización de Entrada (AXON_DCD Security Standard)
// Mandamiento #2: Seguridad Nivel Militar. Usar SIEMPRE sobre input externo
// (body JSON, $_GET, $_POST, headers) antes de procesarlo o persistirlo.
// =============================================================================

/**
 * Sanitiza una cadena de texto: recorta espacios, elimina etiquetas HTML/JS
 * y normaliza caracteres de control. No reemplaza el uso de Prepared
 * Statements — esto previene XSS al momento de RENDERIZAR, no SQLi.
 */
function sanitize_string(string $value, int $maxLength = 255): string
{
    $value = trim($value);
    $value = strip_tags($value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? '';

    return mb_substr($value, 0, $maxLength);
}

/**
 * Sanitiza un entero proveniente de input externo. Retorna $default si el
 * valor no es numérico.
 */
function sanitize_int(mixed $value, int $default = 0): int
{
    if (!is_numeric($value)) {
        return $default;
    }

    return (int) $value;
}

/**
 * Sanitiza un correo electrónico: trim + lowercase + filtro de caracteres
 * inválidos vía filter_var. Retorna cadena vacía si el formato es inválido.
 */
function sanitize_email(string $value): string
{
    $value = strtolower(trim($value));
    $clean = filter_var($value, FILTER_SANITIZE_EMAIL);

    return $clean !== false ? $clean : '';
}

/**
 * Sanitiza un array asociativo plano (sin anidamiento) aplicando
 * sanitize_string() a cada valor string. Útil para payloads JSON simples.
 *
 * @param array<string,mixed> $payload
 * @return array<string,mixed>
 */
function sanitize_array(array $payload, int $maxLength = 255): array
{
    $clean = [];
    foreach ($payload as $key => $value) {
        $clean[$key] = is_string($value) ? sanitize_string($value, $maxLength) : $value;
    }

    return $clean;
}
