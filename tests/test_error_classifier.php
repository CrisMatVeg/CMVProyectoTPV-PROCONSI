<?php

/**
 * TEST: VeriFactuErrorService — Clasificación de errores
 * Prueba clasificar() para todas las categorías, rangos y casos límite.
 * Sin BD, sin ficheros, sin cleanup necesario.
 * Ejecución: php tests/test_error_classifier.php
 */

require_once __DIR__ . '/../model/VeriFactuErrorService.php';

$passed = 0;
$failed = 0;

function assert_cat(string $label, string $expected, string $actual, int &$p, int &$f): void
{
    if ($expected === $actual) {
        echo "[PASS] $label\n";
        $p++;
    } else {
        echo "[FAIL] $label — esperado '$expected', obtenido '$actual'\n";
        $f++;
    }
}

echo "=== TEST: VeriFactuErrorService::clasificar() ===\n\n";

// ── CAT_EXITO (solo 3000) ───────────────────────────────────────────────────
echo "-- CAT_EXITO --\n";
assert_cat('3000 → CAT_EXITO (duplicado = éxito)',
    VeriFactuErrorService::CAT_EXITO, VeriFactuErrorService::clasificar('3000'), $passed, $failed);

// ── CAT_SUBSANAR (2000–2999, aceptado con errores) ─────────────────────────
echo "-- CAT_SUBSANAR --\n";
assert_cat('2000 → CAT_SUBSANAR (hash incorrecto)',
    VeriFactuErrorService::CAT_SUBSANAR, VeriFactuErrorService::clasificar('2000'), $passed, $failed);
assert_cat('2001 → CAT_SUBSANAR (NIF destinatario no censado)',
    VeriFactuErrorService::CAT_SUBSANAR, VeriFactuErrorService::clasificar('2001'), $passed, $failed);
assert_cat('2500 → CAT_SUBSANAR (medio del rango)',
    VeriFactuErrorService::CAT_SUBSANAR, VeriFactuErrorService::clasificar('2500'), $passed, $failed);
assert_cat('2999 → CAT_SUBSANAR (límite superior)',
    VeriFactuErrorService::CAT_SUBSANAR, VeriFactuErrorService::clasificar('2999'), $passed, $failed);

// ── CAT_CONEXION (errores de red/HTTP, reintentos automáticos) ─────────────
echo "-- CAT_CONEXION --\n";
assert_cat('CURL_ERROR → CAT_CONEXION',
    VeriFactuErrorService::CAT_CONEXION, VeriFactuErrorService::clasificar('CURL_ERROR'), $passed, $failed);
assert_cat('1 → CAT_CONEXION (límite inferior rango 1-99)',
    VeriFactuErrorService::CAT_CONEXION, VeriFactuErrorService::clasificar('1'), $passed, $failed);
assert_cat('7 → CAT_CONEXION (cURL error típico: timeout)',
    VeriFactuErrorService::CAT_CONEXION, VeriFactuErrorService::clasificar('7'), $passed, $failed);
assert_cat('28 → CAT_CONEXION (cURL: operation timed out)',
    VeriFactuErrorService::CAT_CONEXION, VeriFactuErrorService::clasificar('28'), $passed, $failed);
assert_cat('99 → CAT_CONEXION (límite superior rango 1-99)',
    VeriFactuErrorService::CAT_CONEXION, VeriFactuErrorService::clasificar('99'), $passed, $failed);
assert_cat('400 → CAT_CONEXION (Bad Request HTTP)',
    VeriFactuErrorService::CAT_CONEXION, VeriFactuErrorService::clasificar('400'), $passed, $failed);
assert_cat('500 → CAT_CONEXION (Internal Server Error)',
    VeriFactuErrorService::CAT_CONEXION, VeriFactuErrorService::clasificar('500'), $passed, $failed);
assert_cat('503 → CAT_CONEXION (Service Unavailable)',
    VeriFactuErrorService::CAT_CONEXION, VeriFactuErrorService::clasificar('503'), $passed, $failed);
assert_cat('599 → CAT_CONEXION (límite superior HTTP errors)',
    VeriFactuErrorService::CAT_CONEXION, VeriFactuErrorService::clasificar('599'), $passed, $failed);
assert_cat('5000 → CAT_CONEXION (límite inferior errores técnicos)',
    VeriFactuErrorService::CAT_CONEXION, VeriFactuErrorService::clasificar('5000'), $passed, $failed);
assert_cat('9999 → CAT_CONEXION (error técnico alto)',
    VeriFactuErrorService::CAT_CONEXION, VeriFactuErrorService::clasificar('9999'), $passed, $failed);

// ── CAT_RECHAZO (1000-1999 y 4000-4999) ────────────────────────────────────
echo "-- CAT_RECHAZO (1xxx) --\n";
assert_cat('1000 → CAT_RECHAZO (límite inferior)',
    VeriFactuErrorService::CAT_RECHAZO, VeriFactuErrorService::clasificar('1000'), $passed, $failed);
assert_cat('1100 → CAT_RECHAZO (valor/tipo incorrecto)',
    VeriFactuErrorService::CAT_RECHAZO, VeriFactuErrorService::clasificar('1100'), $passed, $failed);
assert_cat('1108 → CAT_RECHAZO (NIF emisor ≠ certificado)',
    VeriFactuErrorService::CAT_RECHAZO, VeriFactuErrorService::clasificar('1108'), $passed, $failed);
assert_cat('1124 → CAT_RECHAZO (tipo impositivo inválido)',
    VeriFactuErrorService::CAT_RECHAZO, VeriFactuErrorService::clasificar('1124'), $passed, $failed);
assert_cat('1142 → CAT_RECHAZO (error desglose IVA)',
    VeriFactuErrorService::CAT_RECHAZO, VeriFactuErrorService::clasificar('1142'), $passed, $failed);
assert_cat('1189 → CAT_RECHAZO (falta bloque destinatarios F1)',
    VeriFactuErrorService::CAT_RECHAZO, VeriFactuErrorService::clasificar('1189'), $passed, $failed);
assert_cat('1239 → CAT_RECHAZO (NIF destinatario inválido)',
    VeriFactuErrorService::CAT_RECHAZO, VeriFactuErrorService::clasificar('1239'), $passed, $failed);
assert_cat('1999 → CAT_RECHAZO (límite superior 1xxx)',
    VeriFactuErrorService::CAT_RECHAZO, VeriFactuErrorService::clasificar('1999'), $passed, $failed);

echo "-- CAT_RECHAZO (4xxx) --\n";
assert_cat('4000 → CAT_RECHAZO (límite inferior)',
    VeriFactuErrorService::CAT_RECHAZO, VeriFactuErrorService::clasificar('4000'), $passed, $failed);
assert_cat('4102 → CAT_RECHAZO (XML no cumple esquema)',
    VeriFactuErrorService::CAT_RECHAZO, VeriFactuErrorService::clasificar('4102'), $passed, $failed);
assert_cat('4103 → CAT_RECHAZO (error parsing XML)',
    VeriFactuErrorService::CAT_RECHAZO, VeriFactuErrorService::clasificar('4103'), $passed, $failed);
assert_cat('4107 → CAT_RECHAZO (NIF no censado AEAT)',
    VeriFactuErrorService::CAT_RECHAZO, VeriFactuErrorService::clasificar('4107'), $passed, $failed);
assert_cat('4112 → CAT_RECHAZO (sin permisos suficientes)',
    VeriFactuErrorService::CAT_RECHAZO, VeriFactuErrorService::clasificar('4112'), $passed, $failed);
assert_cat('4141 → CAT_RECHAZO (acceso suspendido temporalmente)',
    VeriFactuErrorService::CAT_RECHAZO, VeriFactuErrorService::clasificar('4141'), $passed, $failed);
assert_cat('4999 → CAT_RECHAZO (límite superior 4xxx)',
    VeriFactuErrorService::CAT_RECHAZO, VeriFactuErrorService::clasificar('4999'), $passed, $failed);

// ── Casos límite y gaps ─────────────────────────────────────────────────────
echo "-- Límites y gaps --\n";
// 3001 y 3002 son 3xxx pero no 3000 → deben ser RECHAZO (caen al default)
assert_cat('3001 → CAT_RECHAZO (3xxx distinto de 3000)',
    VeriFactuErrorService::CAT_RECHAZO, VeriFactuErrorService::clasificar('3001'), $passed, $failed);
assert_cat('3002 → CAT_RECHAZO (registro no existe)',
    VeriFactuErrorService::CAT_RECHAZO, VeriFactuErrorService::clasificar('3002'), $passed, $failed);
// 100-399: no encaja en ningún rango positivo → fallback CAT_RECHAZO
assert_cat('100 → CAT_RECHAZO (gap 100-399, ninguna regla positiva)',
    VeriFactuErrorService::CAT_RECHAZO, VeriFactuErrorService::clasificar('100'), $passed, $failed);
assert_cat('200 → CAT_RECHAZO (gap 100-399)',
    VeriFactuErrorService::CAT_RECHAZO, VeriFactuErrorService::clasificar('200'), $passed, $failed);
assert_cat('399 → CAT_RECHAZO (gap 100-399, límite superior)',
    VeriFactuErrorService::CAT_RECHAZO, VeriFactuErrorService::clasificar('399'), $passed, $failed);
// Código desconocido en gap 3001-3999 (no es 3000, no es 4000+) → CAT_RECHAZO (default)
assert_cat('3500 → CAT_RECHAZO (gap 3001-3999, ninguna regla lo captura)',
    VeriFactuErrorService::CAT_RECHAZO, VeriFactuErrorService::clasificar('3500'), $passed, $failed);

// ── Comprobación de mensajes conocidos ────────────────────────────────────
echo "-- obtenerMensaje() --\n";
$msg2000 = VeriFactuErrorService::obtenerMensaje('2000');
if (stripos($msg2000, 'huella') !== false) {
    echo "[PASS] obtenerMensaje('2000') contiene 'huella'\n";
    $passed++;
} else {
    echo "[FAIL] obtenerMensaje('2000') = '$msg2000' (se esperaba mención de 'huella')\n";
    $failed++;
}

$msg1189 = VeriFactuErrorService::obtenerMensaje('1189');
if (stripos($msg1189, 'destinatario') !== false || stripos($msg1189, 'Falta') !== false) {
    echo "[PASS] obtenerMensaje('1189') contiene 'destinatario'/'Falta'\n";
    $passed++;
} else {
    echo "[FAIL] obtenerMensaje('1189') = '$msg1189'\n";
    $failed++;
}

$msgDesconocido = VeriFactuErrorService::obtenerMensaje('9999');
if (stripos($msgDesconocido, 'desconocido') !== false || stripos($msgDesconocido, '9999') !== false) {
    echo "[PASS] obtenerMensaje('9999') indica error desconocido\n";
    $passed++;
} else {
    echo "[FAIL] obtenerMensaje('9999') = '$msgDesconocido'\n";
    $failed++;
}

echo "\n--- RESULTADO: $passed pasados, $failed fallidos ---\n";
exit($failed > 0 ? 1 : 0);
