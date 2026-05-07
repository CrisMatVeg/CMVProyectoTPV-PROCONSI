<?php

/**
 * TEST: Documentos legales — tipos de identificación del destinatario y tipos de factura
 *
 * PARTE A — Validación de formatos de identificación (NIF, CIF, NIE, NIF-IVA, Pasaporte)
 *   Prueba Validador.php y la estructura XML generada para cada IDType.
 *   Sin inserción en BD. Los XMLs generados se borran en finally.
 *
 * PARTE B — Tipos de documento de factura (F1, F2, R1, R5, Anulación)
 *   Prueba VeriFactuService::procesarAlta/Anulacion() para cada tipo.
 *   Verifica estructura XML y validación XSD (la validación ocurre dentro de procesarAlta).
 *
 * Ejecución: php tests/test_document_types_validation.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/Validador.php';
require_once __DIR__ . '/../model/VeriFactuService.php';

$passed = 0;
$failed = 0;

// ── Helpers ─────────────────────────────────────────────────────────────────

const NS_INFO = "https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroInformacion.xsd";

function passf(string $label, int &$p): void { echo "[PASS] $label\n"; $p++; }
function failf(string $label, string $why,  int &$f): void { echo "[FAIL] $label — $why\n"; $f++; }

function assertBool(string $label, bool $esperado, bool $actual, int &$p, int &$f): void
{
    if ($esperado === $actual) {
        passf($label, $p);
    } else {
        $e = $esperado ? 'true' : 'false';
        $a = $actual   ? 'true' : 'false';
        failf($label, "esperado=$e, obtenido=$a", $f);
    }
}

/** Carga el XML generado, registra namespaces y devuelve SimpleXMLElement o null. */
function loadXml(array $res, string $label, int &$p, int &$f): ?SimpleXMLElement
{
    if (!$res['ok']) {
        failf("$label: generación fallida", $res['error'] ?? 'desconocido', $f);
        return null;
    }
    passf("$label: XML generado y validado por XSD", $p);

    if (!file_exists($res['path'])) {
        failf("$label: fichero XML no existe en disco", $res['path'], $f);
        return null;
    }

    $xml = simplexml_load_file($res['path']);
    if (!$xml) {
        failf("$label: XML no parseable", '', $f);
        return null;
    }
    $xml->registerXPathNamespace('sf',   NS_INFO);
    $xml->registerXPathNamespace('sfLR', "https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroLR.xsd");
    return $xml;
}

function xpathPresente(SimpleXMLElement $xml, string $xpath, string $label, int &$p, int &$f): void
{
    $r = $xml->xpath($xpath);
    if ($r !== false && count($r) > 0) { passf($label, $p); }
    else { failf($label, "XPath '$xpath' sin resultados", $f); }
}

function xpathAusente(SimpleXMLElement $xml, string $xpath, string $label, int &$p, int &$f): void
{
    $r = $xml->xpath($xpath);
    if ($r === false || count($r) === 0) { passf($label, $p); }
    else { failf($label, "XPath '$xpath' no debería existir (encontrado " . count($r) . " nodo/s)", $f); }
}

function xpathValor(SimpleXMLElement $xml, string $xpath, string $esperado, string $label, int &$p, int &$f): void
{
    $r = $xml->xpath($xpath);
    if ($r !== false && count($r) > 0 && (string)$r[0] === $esperado) {
        passf($label, $p);
    } else {
        $v = ($r !== false && count($r) > 0) ? (string)$r[0] : '(sin nodo)';
        failf($label, "esperado='$esperado', obtenido='$v' para '$xpath'", $f);
    }
}

// Hash único por serie para evitar colisiones de nombre de fichero
function mkHash(string $seed): string { return strtoupper(hash('sha256', $seed . microtime(true))); }

$vfService = new VeriFactuService();
$prevHash  = mkHash('PREV');
$hGen      = date('Y-m-d\TH:i:sP');

// ════════════════════════════════════════════════════════════════════════════
// PARTE A — Tipos de identificación del destinatario
// ════════════════════════════════════════════════════════════════════════════
echo "=== PARTE A: Tipos de identificación del destinatario ===\n\n";

// ── A.1: Validador — formato NIF español (8 dígitos + letra de control) ─────
echo "-- A.1: NIF español (Validador::validarDNI_NIE) --\n";
// 12345678Z: 12345678 % 23 = 14 → letra 'Z' ✓
assertBool('NIF válido 12345678Z',    true,  (bool)Validador::validarDNI_NIE('12345678Z'), $passed, $failed);
assertBool('NIF mayúsculas 12345678z', true,  (bool)Validador::validarDNI_NIE('12345678z'), $passed, $failed); // acepta minúsculas
assertBool('NIF inválido 12345678A',  false, (bool)Validador::validarDNI_NIE('12345678A'), $passed, $failed);
assertBool('NIF corto 1234567Z',      false, (bool)Validador::validarDNI_NIE('1234567Z'),  $passed, $failed);
assertBool('NIF vacío',               false, (bool)Validador::validarDNI_NIE(''),           $passed, $failed);

// ── A.2: Validador — NIE (X/Y/Z + 7 dígitos + letra de control) ─────────────
echo "-- A.2: NIE (Validador::validarDNI_NIE) --\n";
// X1234567: reemplazo X→0, 01234567 % 23 = 19 → letra 'L' ✓
assertBool('NIE válido X1234567L',   true,  (bool)Validador::validarDNI_NIE('X1234567L'), $passed, $failed);
// Y1234567: Y→1, "11234567" % 23 = 10 → índice 10 en "TRWAGMYFPDXBNJZSQVHLCKE" = 'X' ✓
assertBool('NIE Y1234567X (Y→1)',     true,  (bool)Validador::validarDNI_NIE('Y1234567X'), $passed, $failed);
assertBool('NIE inválido X1234567A', false, (bool)Validador::validarDNI_NIE('X1234567A'), $passed, $failed);
assertBool('NIE sin letra final',    false, (bool)Validador::validarDNI_NIE('X1234567'),  $passed, $failed);

// ── A.3: Validador — CIF (empresa española) ────────────────────────────────
echo "-- A.3: CIF empresa (Validador::validarCIF) --\n";
// A3838383B: suma=47, dec=50, res=3, digitoControl=3 → letraControl='C'... usar letra que sí coincida.
// Verificado empíricamente: A3838383B → true (rama letra: 'B' === $letraControl donde $letraControl='B')
assertBool('CIF válido A3838383B',    true,  (bool)Validador::validarCIF('A3838383B'),  $passed, $failed);
assertBool('CIF inválido A3838383A',  false, (bool)Validador::validarCIF('A3838383A'),  $passed, $failed);
assertBool('CIF sin letra inicial',   false, (bool)Validador::validarCIF('12345678A'),  $passed, $failed);
assertBool('CIF demasiado corto',     false, (bool)Validador::validarCIF('A123456'),    $passed, $failed);

// ── A.4: Validador — validarDocumento() (NIF o CIF) ────────────────────────
echo "-- A.4: validarDocumento() (NIF ∪ CIF) --\n";
assertBool('validarDocumento NIF válido',  true,  (bool)Validador::validarDocumento('12345678Z'), $passed, $failed);
assertBool('validarDocumento NIE válido',  true,  (bool)Validador::validarDocumento('X1234567L'), $passed, $failed);
assertBool('validarDocumento CIF válido',  true,  (bool)Validador::validarDocumento('A3838383B'), $passed, $failed);
assertBool('validarDocumento inválido',    false, (bool)Validador::validarDocumento('INVALIDO'),   $passed, $failed);
assertBool('validarDocumento vacío',       true,  (bool)Validador::validarDocumento(''),           $passed, $failed); // vacío = válido (la obligatoriedad la gestiona otra capa)

// ── A.5: Validador — NIF-IVA intracomunitario ──────────────────────────────
echo "-- A.5: NIF-IVA intracomunitario (Validador::validarNifIva) --\n";
// DE: longitud esperada 11. 'DE123456789' = 11 caracteres ✓
assertBool('NIF-IVA alemán DE123456789',       true,  (bool)Validador::validarNifIva('DE123456789',  'DE'), $passed, $failed);
assertBool('NIF-IVA francés FR12345678901',    true,  (bool)Validador::validarNifIva('FR12345678901','FR'), $passed, $failed);
assertBool('NIF-IVA sin prefijo país',         false, (bool)Validador::validarNifIva('123456789',    'DE'), $passed, $failed);
assertBool('NIF-IVA vacío',                    false, (bool)Validador::validarNifIva('',              'DE'), $passed, $failed);
assertBool('NIF-IVA país vacío',               false, (bool)Validador::validarNifIva('DE123456789',  ''),   $passed, $failed);

echo "\n";

// ── A.6: Estructura XML — IDType='01' (NIF/CIF/NIE) → usa <sf:NIF> ────────
echo "-- A.6: XML IDType=01 (NIF) → <sf:NIF> --\n";
$pathA6 = null;
try {
    $datosA6 = [
        'numero_serie'         => 'F-' . date('jnY') . '-TEST001',
        'fecha_expedicion'     => date('d-m-Y'),
        'tipo_factura'         => 'F1',
        'base_imponible'       => 82.64,
        'cuota_total'          => 17.36,
        'importe_total'        => 100.00,
        'hash_actual'          => mkHash('A6'),
        'hash_anterior'        => $prevHash,
        'fecha_hora_gen'       => $hGen,
        'serie_anterior'       => 'T-' . date('jnY') . '-TEST000',
        'fecha_anterior'       => date('d-m-Y'),
        'destinatario_nif'     => '12345678Z',
        'destinatario_nombre'  => 'Cliente NIF Test',
        'destinatario_id_type' => '01',
    ];
    $res  = $vfService->procesarAlta($datosA6);
    $pathA6 = $res['path'] ?? null;
    $xml = loadXml($res, 'A6: IDType=01 (NIF)', $passed, $failed);
    if ($xml) {
        xpathPresente($xml, '//sf:Destinatarios/sf:IDDestinatario/sf:NIF', 'A6: <sf:NIF> presente para IDType=01', $passed, $failed);
        xpathValor($xml,    '//sf:Destinatarios/sf:IDDestinatario/sf:NIF', '12345678Z', 'A6: valor NIF correcto', $passed, $failed);
        xpathAusente($xml,  '//sf:Destinatarios/sf:IDDestinatario/sf:IDOtro', 'A6: <sf:IDOtro> ausente para IDType=01', $passed, $failed);
    }
} finally {
    if ($pathA6 && file_exists($pathA6)) unlink($pathA6);
    echo "\n";
}

// ── A.7: Estructura XML — IDType='02' (NIF-IVA) → usa <sf:IDOtro> ─────────
echo "-- A.7: XML IDType=02 (NIF-IVA intracomunitario) → <sf:IDOtro> --\n";
$pathA7 = null;
try {
    $datosA7 = [
        'numero_serie'         => 'F-' . date('jnY') . '-TEST002',
        'fecha_expedicion'     => date('d-m-Y'),
        'tipo_factura'         => 'F1',
        'base_imponible'       => 82.64,
        'cuota_total'          => 17.36,
        'importe_total'        => 100.00,
        'hash_actual'          => mkHash('A7'),
        'hash_anterior'        => $prevHash,
        'fecha_hora_gen'       => $hGen,
        'serie_anterior'       => 'F-' . date('jnY') . '-TEST001',
        'fecha_anterior'       => date('d-m-Y'),
        'destinatario_nif'     => 'DE123456789',
        'destinatario_nombre'  => 'Empresa Alemana GmbH',
        'destinatario_id_type' => '02',
        'destinatario_pais'    => 'DE',
    ];
    $res  = $vfService->procesarAlta($datosA7);
    $pathA7 = $res['path'] ?? null;
    $xml = loadXml($res, 'A7: IDType=02 (NIF-IVA)', $passed, $failed);
    if ($xml) {
        xpathPresente($xml, '//sf:Destinatarios/sf:IDDestinatario/sf:IDOtro',           'A7: <sf:IDOtro> presente para NIF-IVA', $passed, $failed);
        xpathValor($xml,    '//sf:IDOtro/sf:IDType',    '02',          'A7: IDType=02',         $passed, $failed);
        xpathValor($xml,    '//sf:IDOtro/sf:CodigoPais','DE',          'A7: CodigoPais=DE',      $passed, $failed);
        xpathValor($xml,    '//sf:IDOtro/sf:ID',        'DE123456789', 'A7: ID=DE123456789',     $passed, $failed);
        xpathAusente($xml,  '//sf:Destinatarios/sf:IDDestinatario/sf:NIF', 'A7: <sf:NIF> ausente para IDType=02', $passed, $failed);
    }
} finally {
    if ($pathA7 && file_exists($pathA7)) unlink($pathA7);
    echo "\n";
}

// ── A.8: Estructura XML — IDType='03' (Pasaporte) → usa <sf:IDOtro> ────────
echo "-- A.8: XML IDType=03 (Pasaporte) → <sf:IDOtro> --\n";
$pathA8 = null;
try {
    $datosA8 = [
        'numero_serie'         => 'F-' . date('jnY') . '-TEST003',
        'fecha_expedicion'     => date('d-m-Y'),
        'tipo_factura'         => 'F1',
        'base_imponible'       => 82.64,
        'cuota_total'          => 17.36,
        'importe_total'        => 100.00,
        'hash_actual'          => mkHash('A8'),
        'hash_anterior'        => $prevHash,
        'fecha_hora_gen'       => $hGen,
        'serie_anterior'       => 'F-' . date('jnY') . '-TEST002',
        'fecha_anterior'       => date('d-m-Y'),
        'destinatario_nif'     => 'AB1234567',
        'destinatario_nombre'  => 'John Doe',
        'destinatario_id_type' => '03',
        'destinatario_pais'    => 'US',
    ];
    $res  = $vfService->procesarAlta($datosA8);
    $pathA8 = $res['path'] ?? null;
    $xml = loadXml($res, 'A8: IDType=03 (Pasaporte)', $passed, $failed);
    if ($xml) {
        xpathPresente($xml, '//sf:IDOtro',               'A8: <sf:IDOtro> presente para Pasaporte', $passed, $failed);
        xpathValor($xml,    '//sf:IDOtro/sf:IDType', '03', 'A8: IDType=03',                         $passed, $failed);
        xpathValor($xml,    '//sf:IDOtro/sf:CodigoPais', 'US', 'A8: CodigoPais=US',                 $passed, $failed);
        xpathAusente($xml,  '//sf:Destinatarios/sf:IDDestinatario/sf:NIF', 'A8: <sf:NIF> ausente para Pasaporte', $passed, $failed);
    }
} finally {
    if ($pathA8 && file_exists($pathA8)) unlink($pathA8);
    echo "\n";
}

// ── A.9: Estructura XML — IDType='06' (Otro documento) → usa <sf:IDOtro> ──
echo "-- A.9: XML IDType=06 (Otro documento) → <sf:IDOtro> --\n";
$pathA9 = null;
try {
    $datosA9 = [
        'numero_serie'         => 'F-' . date('jnY') . '-TEST004',
        'fecha_expedicion'     => date('d-m-Y'),
        'tipo_factura'         => 'F1',
        'base_imponible'       => 82.64,
        'cuota_total'          => 17.36,
        'importe_total'        => 100.00,
        'hash_actual'          => mkHash('A9'),
        'hash_anterior'        => $prevHash,
        'fecha_hora_gen'       => $hGen,
        'serie_anterior'       => 'F-' . date('jnY') . '-TEST003',
        'fecha_anterior'       => date('d-m-Y'),
        'destinatario_nif'     => 'DOC-EXTRANJERO-999',
        'destinatario_nombre'  => 'Cliente Otro Documento',
        'destinatario_id_type' => '06',
        'destinatario_pais'    => 'MX',
    ];
    $res  = $vfService->procesarAlta($datosA9);
    $pathA9 = $res['path'] ?? null;
    $xml = loadXml($res, 'A9: IDType=06 (Otro)', $passed, $failed);
    if ($xml) {
        xpathPresente($xml, '//sf:IDOtro',              'A9: <sf:IDOtro> presente', $passed, $failed);
        xpathValor($xml,    '//sf:IDOtro/sf:IDType', '06', 'A9: IDType=06',         $passed, $failed);
        xpathAusente($xml,  '//sf:Destinatarios/sf:IDDestinatario/sf:NIF', 'A9: <sf:NIF> ausente', $passed, $failed);
    }
} finally {
    if ($pathA9 && file_exists($pathA9)) unlink($pathA9);
    echo "\n";
}

// ════════════════════════════════════════════════════════════════════════════
// PARTE B — Tipos de documento de factura
// ════════════════════════════════════════════════════════════════════════════
echo "=== PARTE B: Tipos de documento de factura ===\n\n";

// ── B.1: F2 — Ticket simplificado ──────────────────────────────────────────
echo "-- B.1: F2 (ticket simplificado) --\n";
$pathB1 = null;
try {
    $datos = [
        'numero_serie'     => 'T-' . date('jnY') . '-TEST101',
        'fecha_expedicion' => date('d-m-Y'),
        'tipo_factura'     => 'F2',
        'base_imponible'   => 82.64,
        'cuota_total'      => 17.36,
        'importe_total'    => 100.00,
        'hash_actual'      => mkHash('B1'),
        'hash_anterior'    => $prevHash,
        'fecha_hora_gen'   => $hGen,
        'serie_anterior'   => 'F-' . date('jnY') . '-TEST004',
        'fecha_anterior'   => date('d-m-Y'),
    ];
    $res  = $vfService->procesarAlta($datos);
    $pathB1 = $res['path'] ?? null;
    $xml = loadXml($res, 'B1: F2', $passed, $failed);
    if ($xml) {
        xpathValor($xml,   '//sf:TipoFactura', 'F2', 'B1: TipoFactura=F2', $passed, $failed);
        xpathValor($xml,   '//sf:FacturaSinIdentifDestinatarioArt61d', 'S', 'B1: FacturaSinIdentifDestinatarioArt61d=S', $passed, $failed);
        xpathAusente($xml, '//sf:Destinatarios', 'B1: Destinatarios ausente', $passed, $failed);
        xpathPresente($xml,'//sf:Encadenamiento/sf:RegistroAnterior', 'B1: RegistroAnterior presente', $passed, $failed);
    }
} finally {
    if ($pathB1 && file_exists($pathB1)) unlink($pathB1);
    echo "\n";
}

// ── B.2: F1 con NIF — Factura completa con destinatario ────────────────────
echo "-- B.2: F1 con NIF (factura completa) --\n";
$pathB2 = null;
try {
    $datos = [
        'numero_serie'        => 'F-' . date('jnY') . '-TEST102',
        'fecha_expedicion'    => date('d-m-Y'),
        'tipo_factura'        => 'F1',
        'base_imponible'      => 82.64,
        'cuota_total'         => 17.36,
        'importe_total'       => 100.00,
        'hash_actual'         => mkHash('B2'),
        'hash_anterior'       => $prevHash,
        'fecha_hora_gen'      => $hGen,
        'serie_anterior'      => 'T-' . date('jnY') . '-TEST101',
        'fecha_anterior'      => date('d-m-Y'),
        'destinatario_nif'    => '12345678Z',
        'destinatario_nombre' => 'Cliente Prueba S.L.',
    ];
    $res  = $vfService->procesarAlta($datos);
    $pathB2 = $res['path'] ?? null;
    $xml = loadXml($res, 'B2: F1 con NIF', $passed, $failed);
    if ($xml) {
        xpathValor($xml,   '//sf:TipoFactura', 'F1', 'B2: TipoFactura=F1', $passed, $failed);
        xpathPresente($xml,'//sf:Destinatarios', 'B2: Destinatarios presente', $passed, $failed);
        xpathValor($xml,   '//sf:Destinatarios//sf:NIF', '12345678Z', 'B2: NIF destinatario correcto', $passed, $failed);
        xpathAusente($xml, '//sf:FacturaSinIdentifDestinatarioArt61d', 'B2: FacturaSinIdentif ausente', $passed, $failed);
    }
} finally {
    if ($pathB2 && file_exists($pathB2)) unlink($pathB2);
    echo "\n";
}

// ── B.3: PrimerRegistro — hash_anterior nulo ──────────────────────────────
echo "-- B.3: PrimerRegistro (hash_anterior = null) --\n";
$pathB3 = null;
try {
    $datos = [
        'numero_serie'     => 'T-' . date('jnY') . '-TEST103',
        'fecha_expedicion' => date('d-m-Y'),
        'tipo_factura'     => 'F2',
        'base_imponible'   => 10.00,
        'cuota_total'      => 2.10,
        'importe_total'    => 12.10,
        'hash_actual'      => mkHash('B3'),
        'hash_anterior'    => null,
        'fecha_hora_gen'   => $hGen,
    ];
    $res  = $vfService->procesarAlta($datos);
    $pathB3 = $res['path'] ?? null;
    $xml = loadXml($res, 'B3: PrimerRegistro', $passed, $failed);
    if ($xml) {
        xpathValor($xml,   '//sf:Encadenamiento/sf:PrimerRegistro', 'S', 'B3: PrimerRegistro=S', $passed, $failed);
        xpathAusente($xml, '//sf:Encadenamiento/sf:RegistroAnterior', 'B3: RegistroAnterior ausente', $passed, $failed);
    }
} finally {
    if ($pathB3 && file_exists($pathB3)) unlink($pathB3);
    echo "\n";
}

// ── B.4: R1 — Rectificativa completa (con NIF) ────────────────────────────
echo "-- B.4: R1 (rectificativa completa, TipoRectificativa=S) --\n";
$pathB4 = null;
try {
    $datos = [
        'numero_serie'              => 'F-' . date('jnY') . '-TEST104',
        'fecha_expedicion'          => date('d-m-Y'),
        'tipo_factura'              => 'R1',
        'tipo_rectificativa'        => 'S',
        'base_imponible'            => -82.64,
        'cuota_total'               => -17.36,
        'importe_total'             => -100.00,
        'base_rectificada'          => 82.64,
        'cuota_rectificada'         => 17.36,
        'factura_rectificada_serie' => 'F-' . date('jnY') . '-TEST102',
        'factura_rectificada_fecha' => date('d-m-Y'),
        'hash_actual'               => mkHash('B4'),
        'hash_anterior'             => $prevHash,
        'fecha_hora_gen'            => $hGen,
        'serie_anterior'            => 'T-' . date('jnY') . '-TEST103',
        'fecha_anterior'            => date('d-m-Y'),
        'destinatario_nif'          => '12345678Z',
        'destinatario_nombre'       => 'Cliente Prueba S.L.',
    ];
    $res  = $vfService->procesarAlta($datos);
    $pathB4 = $res['path'] ?? null;
    $xml = loadXml($res, 'B4: R1', $passed, $failed);
    if ($xml) {
        xpathValor($xml,   '//sf:TipoFactura', 'R1', 'B4: TipoFactura=R1', $passed, $failed);
        xpathValor($xml,   '//sf:TipoRectificativa', 'S', 'B4: TipoRectificativa=S', $passed, $failed);
        xpathPresente($xml,'//sf:FacturasRectificadas', 'B4: FacturasRectificadas presente', $passed, $failed);
        xpathPresente($xml,'//sf:ImporteRectificacion', 'B4: ImporteRectificacion presente (TipoRectificativa=S)', $passed, $failed);
        xpathPresente($xml,'//sf:Destinatarios', 'B4: Destinatarios presente (R1 no es simplificada)', $passed, $failed);
        xpathAusente($xml, '//sf:FacturaSinIdentifDestinatarioArt61d', 'B4: FacturaSinIdentif ausente', $passed, $failed);
    }
} finally {
    if ($pathB4 && file_exists($pathB4)) unlink($pathB4);
    echo "\n";
}

// ── B.5: R5 — Rectificativa simplificada (sin destinatario) ───────────────
echo "-- B.5: R5 (rectificativa simplificada, sin destinatario) --\n";
$pathB5 = null;
try {
    $datos = [
        'numero_serie'              => 'A-' . date('jnY') . '-TEST105',
        'fecha_expedicion'          => date('d-m-Y'),
        'tipo_factura'              => 'R5',
        'tipo_rectificativa'        => 'S',
        'base_imponible'            => -82.64,
        'cuota_total'               => -17.36,
        'importe_total'             => -100.00,
        'base_rectificada'          => 82.64,
        'cuota_rectificada'         => 17.36,
        'factura_rectificada_serie' => 'T-' . date('jnY') . '-TEST101',
        'factura_rectificada_fecha' => date('d-m-Y'),
        'hash_actual'               => mkHash('B5'),
        'hash_anterior'             => $prevHash,
        'fecha_hora_gen'            => $hGen,
        'serie_anterior'            => 'F-' . date('jnY') . '-TEST104',
        'fecha_anterior'            => date('d-m-Y'),
    ];
    $res  = $vfService->procesarAlta($datos);
    $pathB5 = $res['path'] ?? null;
    $xml = loadXml($res, 'B5: R5', $passed, $failed);
    if ($xml) {
        xpathValor($xml,   '//sf:TipoFactura', 'R5', 'B5: TipoFactura=R5', $passed, $failed);
        xpathPresente($xml,'//sf:TipoRectificativa', 'B5: TipoRectificativa presente', $passed, $failed);
        xpathPresente($xml,'//sf:FacturasRectificadas', 'B5: FacturasRectificadas presente', $passed, $failed);
        xpathAusente($xml, '//sf:Destinatarios', 'B5: Destinatarios ausente (R5 es simplificada)', $passed, $failed);
        xpathAusente($xml, '//sf:FacturaSinIdentifDestinatarioArt61d', 'B5: FacturaSinIdentif ausente (solo para F2)', $passed, $failed);
    }
} finally {
    if ($pathB5 && file_exists($pathB5)) unlink($pathB5);
    echo "\n";
}

// ── B.6: Anulación ────────────────────────────────────────────────────────
echo "-- B.6: Anulación (procesarAnulacion) --\n";
$pathB6 = null;
try {
    $datos = [
        'numero_serie'     => 'T-' . date('jnY') . '-TEST101',
        'fecha_expedicion' => date('d-m-Y'),
        'hash_actual'      => mkHash('B6'),
        'hash_anterior'    => $prevHash,
        'fecha_hora_gen'   => $hGen,
        'serie_anterior'   => 'A-' . date('jnY') . '-TEST105',
        'fecha_anterior'   => date('d-m-Y'),
    ];
    $res  = $vfService->procesarAnulacion($datos);
    $pathB6 = $res['path'] ?? null;
    $xml = loadXml($res, 'B6: Anulación', $passed, $failed);
    if ($xml) {
        xpathPresente($xml, '//sf:RegistroAnulacion', 'B6: RegistroAnulacion presente', $passed, $failed);
        xpathAusente($xml,  '//sf:RegistroAlta',      'B6: RegistroAlta ausente', $passed, $failed);
        xpathPresente($xml, '//sf:IDFactura/sf:NumSerieFacturaAnulada', 'B6: NumSerieFacturaAnulada presente', $passed, $failed);
        xpathValor($xml,    '//sf:IDFactura/sf:NumSerieFacturaAnulada', $datos['numero_serie'], 'B6: NumSerieFacturaAnulada correcto', $passed, $failed);
    }
} finally {
    if ($pathB6 && file_exists($pathB6)) unlink($pathB6);
    echo "\n";
}

// ── B.7: Desglose automático (sin array desgloses) ────────────────────────
echo "-- B.7: Desglose automático (sin desgloses explícitos) --\n";
$pathB7 = null;
try {
    $datos = [
        'numero_serie'     => 'T-' . date('jnY') . '-TEST107',
        'fecha_expedicion' => date('d-m-Y'),
        'tipo_factura'     => 'F2',
        'base_imponible'   => 82.64,
        'cuota_total'      => 17.36,
        'importe_total'    => 100.00,
        'hash_actual'      => mkHash('B7'),
        'hash_anterior'    => $prevHash,
        'fecha_hora_gen'   => $hGen,
        'serie_anterior'   => 'A-' . date('jnY') . '-TEST105',
        'fecha_anterior'   => date('d-m-Y'),
        // Sin 'desgloses' → fallback automático
    ];
    $res  = $vfService->procesarAlta($datos);
    $pathB7 = $res['path'] ?? null;
    $xml = loadXml($res, 'B7: desglose automático', $passed, $failed);
    if ($xml) {
        xpathPresente($xml,'//sf:Desglose/sf:DetalleDesglose', 'B7: DetalleDesglose generado automáticamente', $passed, $failed);
        // 82.64 base, 17.36 cuota → tasa ≈ 21% → snapped a 21.00
        xpathValor($xml,   '//sf:DetalleDesglose/sf:TipoImpositivo', '21.00', 'B7: TipoImpositivo snapped a 21.00', $passed, $failed);
    }
} finally {
    if ($pathB7 && file_exists($pathB7)) unlink($pathB7);
    echo "\n";
}

echo "--- RESULTADO: $passed pasados, $failed fallidos ---\n";
exit($failed > 0 ? 1 : 0);
