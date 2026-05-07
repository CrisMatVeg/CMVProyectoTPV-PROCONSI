<?php

/**
 * TEST: AeatQueueService — Escenarios de error y transiciones de estado
 * Usa PHP Reflection para acceder a los métodos privados marcarError() y marcarEnviado().
 * Crea ventas de prueba (hash_actual=NULL → borrado seguro por los triggers de inalterabilidad).
 * Todos los datos de prueba se eliminan en bloques finally.
 * Ejecución: php tests/test_queue_error_scenarios.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/DBPDO.php';
require_once __DIR__ . '/../model/AeatQueueService.php';
require_once __DIR__ . '/../model/VeriFactuErrorService.php';

$passed = 0;
$failed = 0;

// ── Helpers ─────────────────────────────────────────────────────────────────

function passf(string $label, int &$p): void
{
    echo "[PASS] $label\n";
    $p++;
}

function failf(string $label, string $motivo, int &$f): void
{
    echo "[FAIL] $label — $motivo\n";
    $f++;
}

function assertField(string $label, array $row, string $campo, string $esperado, int &$p, int &$f): void
{
    $actual = $row[$campo] ?? '__MISSING__';
    if ($actual === $esperado) {
        passf($label, $p);
    } else {
        failf($label, "campo '$campo': esperado='$esperado', obtenido='$actual'", $f);
    }
}

function fetchCola(PDO $db, int $id): array
{
    $stmt = $db->prepare("SELECT * FROM cola_envios WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function fetchVenta(PDO $db, int $id): array
{
    $stmt = $db->prepare("SELECT * FROM ventas WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function insertVenta(PDO $db, int $ticket): int
{
    $stmt = $db->prepare(
        "INSERT INTO ventas (numero_ticket, metodo_pago, subtotal, base_imponible, iva_amt, total, hash_actual)
         VALUES (?, 'efectivo', 10.00, 8.26, 1.74, 10.00, NULL)"
    );
    $stmt->execute([$ticket]);
    return (int)$db->lastInsertId();
}

/** $offsetSecs negativo = hace N segundos (más antiguo). xml_path='' porque el campo es NOT NULL en BD */
function insertCola(PDO $db, int $idVenta, int $offsetSecs = 0): int
{
    if ($offsetSecs < 0) {
        $n = abs($offsetSecs);
        $stmt = $db->prepare(
            "INSERT INTO cola_envios (id_venta, xml_path, estado, creado_en)
             VALUES (?, '', 'pendiente', DATE_SUB(CURRENT_TIMESTAMP, INTERVAL ? SECOND))"
        );
        $stmt->execute([$idVenta, $n]);
    } else {
        $stmt = $db->prepare("INSERT INTO cola_envios (id_venta, xml_path, estado) VALUES (?, '', 'pendiente')");
        $stmt->execute([$idVenta]);
    }
    return (int)$db->lastInsertId();
}

function cleanVenta(PDO $db, int $id): void
{
    // Eliminar entradas de cola primero (por FK), luego la venta
    $db->exec("DELETE FROM cola_envios WHERE id_venta = $id");
    $db->exec("DELETE FROM ventas WHERE id = $id AND hash_actual IS NULL");
}

// ── Setup Reflection ────────────────────────────────────────────────────────

$queue    = new AeatQueueService();
$refClass = new ReflectionClass($queue);

$refMarcarError   = $refClass->getMethod('marcarError');
$refMarcarEnviado = $refClass->getMethod('marcarEnviado');

$db = DBPDO::getPDO();

// Limpieza preventiva de ejecuciones anteriores fallidas
$db->exec("DELETE c FROM cola_envios c JOIN ventas v ON c.id_venta = v.id WHERE v.numero_ticket BETWEEN 9999901 AND 9999910");
$db->exec("DELETE FROM ventas WHERE numero_ticket BETWEEN 9999901 AND 9999910 AND hash_actual IS NULL");

echo "=== TEST: AeatQueueService — Escenarios de error ===\n\n";

// ═══════════════════════════════════════════════════════════════════════════
// ESCENARIO A: CAT_CONEXION → reintento en 60s, sin bloqueo de cadena
// ═══════════════════════════════════════════════════════════════════════════
echo "--- Escenario A: CAT_CONEXION ---\n";
$idVentaA = 0;
$idColaA  = 0;
$idVentaA2 = 0;
$idColaA2  = 0;
try {
    $idVentaA  = insertVenta($db, 9999901);
    $idColaA   = insertCola($db, $idVentaA, -5);   // 5 s antes (más antiguo)
    $idVentaA2 = insertVenta($db, 9999902);
    $idColaA2  = insertCola($db, $idVentaA2, 0);   // ahora (subsiguiente)

    $colaAntes = fetchCola($db, $idColaA);

    $refMarcarError->invoke($queue, $idColaA, 'Error cURL(7): Connection refused', VeriFactuErrorService::CAT_CONEXION, '');

    $cola  = fetchCola($db, $idColaA);
    $venta = fetchVenta($db, $idVentaA);
    $colaB = fetchCola($db, $idColaA2);

    assertField('A: cola.estado = pendiente (reintento)',   $cola,  'estado', 'pendiente', $passed, $failed);
    assertField('A: ventas.estado_envio_aeat = pendiente',  $venta, 'estado_envio_aeat', 'pendiente', $passed, $failed);
    assertField('A: cola subsiguiente NO bloqueada',        $colaB, 'estado', 'pendiente', $passed, $failed);

    $intAntes = (int)($colaAntes['intentos'] ?? 0);
    $intDes   = (int)($cola['intentos'] ?? 0);
    if ($intDes === $intAntes + 1) {
        passf("A: intentos incrementado a $intDes", $passed);
    } else {
        failf('A: intentos no incrementado', "antes=$intAntes, después=$intDes", $failed);
    }

    $proximo = strtotime($cola['fecha_proximo_intento'] ?? '');
    $now     = time();
    if ($proximo >= $now + 55 && $proximo <= $now + 70) {
        passf('A: fecha_proximo_intento ≈ NOW+60s', $passed);
    } else {
        failf('A: fecha_proximo_intento inesperada', "diff=" . ($proximo - $now) . "s", $failed);
    }

    if (!empty($cola['ultimo_error'])) {
        passf('A: ultimo_error poblado', $passed);
    } else {
        failf('A: ultimo_error vacío', '', $failed);
    }
} finally {
    cleanVenta($db, $idVentaA);
    cleanVenta($db, $idVentaA2);
    echo "\n";
}

// ═══════════════════════════════════════════════════════════════════════════
// ESCENARIO B: CAT_SUBSANAR → error_critico en cola, subsanacion_pendiente
//              en ventas, sin bloqueo en cascada de items subsiguientes
// ═══════════════════════════════════════════════════════════════════════════
echo "--- Escenario B: CAT_SUBSANAR (sin cascada) ---\n";
$idVentaB1 = 0; $idColaB1 = 0;
$idVentaB2 = 0; $idColaB2 = 0;
try {
    $idVentaB1 = insertVenta($db, 9999903);
    $idColaB1  = insertCola($db, $idVentaB1, -5);  // más antiguo
    $idVentaB2 = insertVenta($db, 9999904);
    $idColaB2  = insertCola($db, $idVentaB2, 0);   // subsiguiente

    $refMarcarError->invoke($queue, $idColaB1, '[2000] El cálculo de la huella suministrada es incorrecta.', VeriFactuErrorService::CAT_SUBSANAR, '');

    $colaB1  = fetchCola($db, $idColaB1);
    $ventaB1 = fetchVenta($db, $idVentaB1);
    $colaB2  = fetchCola($db, $idColaB2);

    assertField('B: cola_error.estado = error_critico',              $colaB1, 'estado', 'error_critico', $passed, $failed);
    assertField('B: ventas_error.estado = subsanacion_pendiente',    $ventaB1, 'estado_envio_aeat', 'subsanacion_pendiente', $passed, $failed);
    assertField('B: cola_siguiente.estado = pendiente (sin cascada)', $colaB2, 'estado', 'pendiente', $passed, $failed);
} finally {
    cleanVenta($db, $idVentaB1);
    cleanVenta($db, $idVentaB2);
    // Limpiar posibles ventas con subsanacion_pendiente de nuestro rango de prueba
    $db->exec("UPDATE ventas SET estado_envio_aeat='pendiente' WHERE numero_ticket BETWEEN 9999901 AND 9999910 AND estado_envio_aeat='subsanacion_pendiente' AND hash_actual IS NULL");
    echo "\n";
}

// ═══════════════════════════════════════════════════════════════════════════
// ESCENARIO C: CAT_RECHAZO → error_critico + BLOQUEO EN CASCADA
// El bloqueo usa: UPDATE ... WHERE estado='pendiente' AND creado_en > :fechaActual
// Item C1 se inserta con -5s para que C2 (ahora) tenga creado_en > C1.creado_en
// ═══════════════════════════════════════════════════════════════════════════
echo "--- Escenario C: CAT_RECHAZO (bloqueo en cascada) ---\n";
$idVentaC1 = 0; $idColaC1 = 0;
$idVentaC2 = 0; $idColaC2 = 0;
try {
    $idVentaC1 = insertVenta($db, 9999905);
    $idColaC1  = insertCola($db, $idVentaC1, -5);  // 5 s antes → será el item con error
    $idVentaC2 = insertVenta($db, 9999906);
    $idColaC2  = insertCola($db, $idVentaC2, 0);   // ahora → debe quedar bloqueado

    $refMarcarError->invoke($queue, $idColaC1, '[4102] El XML no cumple el esquema. Falta campo obligatorio.', VeriFactuErrorService::CAT_RECHAZO, '');

    $colaC1  = fetchCola($db, $idColaC1);
    $ventaC1 = fetchVenta($db, $idVentaC1);
    $colaC2  = fetchCola($db, $idColaC2);
    $ventaC2 = fetchVenta($db, $idVentaC2);

    assertField('C: cola_error.estado = error_critico',     $colaC1, 'estado', 'error_critico', $passed, $failed);
    assertField('C: ventas_error.estado = error_critico',   $ventaC1, 'estado_envio_aeat', 'error_critico', $passed, $failed);
    assertField('C: cola_siguiente.estado = bloqueado',     $colaC2, 'estado', 'bloqueado', $passed, $failed);
    assertField('C: ventas_siguiente.estado = bloqueado',   $ventaC2, 'estado_envio_aeat', 'bloqueado', $passed, $failed);
} finally {
    cleanVenta($db, $idVentaC1);
    cleanVenta($db, $idVentaC2);
    echo "\n";
}

// ═══════════════════════════════════════════════════════════════════════════
// ESCENARIO D: CAT_EXITO (código 3000, duplicado) → marca como enviado
// marcarError redirige internamente a marcarEnviado cuando cat=CAT_EXITO
// ═══════════════════════════════════════════════════════════════════════════
echo "--- Escenario D: CAT_EXITO (duplicado 3000 = éxito) ---\n";
$idVentaD = 0; $idColaD = 0;
try {
    $idVentaD = insertVenta($db, 9999907);
    $idColaD  = insertCola($db, $idVentaD);

    $refMarcarError->invoke($queue, $idColaD, '[3000] Registro de facturación duplicado.', VeriFactuErrorService::CAT_EXITO, '<RespuestaAEAT>Duplicado</RespuestaAEAT>');

    $cola  = fetchCola($db, $idColaD);
    $venta = fetchVenta($db, $idVentaD);

    assertField('D: cola.estado = enviado',              $cola,  'estado', 'enviado', $passed, $failed);
    assertField('D: ventas.estado_envio_aeat = enviado', $venta, 'estado_envio_aeat', 'enviado', $passed, $failed);
} finally {
    cleanVenta($db, $idVentaD);
    echo "\n";
}

// ═══════════════════════════════════════════════════════════════════════════
// ESCENARIO E: marcarEnviado() directamente → enviado + respuesta_aeat guardada
// ═══════════════════════════════════════════════════════════════════════════
echo "--- Escenario E: marcarEnviado() directamente ---\n";
$idVentaE = 0; $idColaE = 0;
try {
    $idVentaE = insertVenta($db, 9999908);
    $idColaE  = insertCola($db, $idVentaE);

    $rawResp = '<RespuestaRegistro><EstadoRegistro>Correcto</EstadoRegistro></RespuestaRegistro>';
    $refMarcarEnviado->invoke($queue, $idColaE, $idVentaE, $rawResp);

    $cola  = fetchCola($db, $idColaE);
    $venta = fetchVenta($db, $idVentaE);

    assertField('E: cola.estado = enviado',              $cola,  'estado', 'enviado', $passed, $failed);
    assertField('E: ventas.estado_envio_aeat = enviado', $venta, 'estado_envio_aeat', 'enviado', $passed, $failed);

    if (!empty($cola['respuesta_aeat'])) {
        passf('E: respuesta_aeat guardada en cola_envios', $passed);
    } else {
        failf('E: respuesta_aeat vacía', '', $failed);
    }

    // intentos debe haberse incrementado
    if ((int)($cola['intentos'] ?? 0) >= 1) {
        passf('E: intentos >= 1', $passed);
    } else {
        failf('E: intentos no incrementado', "valor=" . ($cola['intentos'] ?? 'NULL'), $failed);
    }
} finally {
    cleanVenta($db, $idVentaE);
    echo "\n";
}

// ── Cleanup final de seguridad ───────────────────────────────────────────────
$db->exec("DELETE c FROM cola_envios c JOIN ventas v ON c.id_venta = v.id WHERE v.numero_ticket BETWEEN 9999901 AND 9999910");
$db->exec("DELETE FROM ventas WHERE numero_ticket BETWEEN 9999901 AND 9999910 AND hash_actual IS NULL");

echo "--- RESULTADO: $passed pasados, $failed fallidos ---\n";
exit($failed > 0 ? 1 : 0);
