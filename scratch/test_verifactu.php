<?php
/**
 * Test script for VeriFactu Chaining and Hashing
 * Runs from CLI
 */

define("DBNAME", "dbelectrobazar-tpv"); // Mocking for internal encrypt key
require_once __DIR__ . '/../model/VentaPDO.php';
require_once __DIR__ . '/../model/DBPDO.php';
require_once __DIR__ . '/../config/confDBPDO.php';

echo "=== VeriFactu Hashing Test ===\n";

$datos1 = [
    'nif_emisor'       => 'B12345678',
    'numero_serie'     => 'T-1742026-1001',
    'fecha_expedicion' => '17-04-2026',
    'tipo_factura'     => 'F2',
    'cuota_total'      => 21.00,
    'importe_total'    => 121.00,
    'fecha_hora_gen'   => '2026-04-17T08:20:00+02:00'
];

$hash1 = VentaPDO::generarHashVeriFactu($datos1, null);
echo "Hash 1 (Primer registro): $hash1\n";

$datos2 = [
    'nif_emisor'       => 'B12345678',
    'numero_serie'     => 'T-1742026-1002',
    'fecha_expedicion' => '17-04-2026',
    'tipo_factura'     => 'F2',
    'cuota_total'      => 10.50,
    'importe_total'    => 60.50,
    'fecha_hora_gen'   => '2026-04-17T08:21:00+02:00'
];

$hash2 = VentaPDO::generarHashVeriFactu($datos2, $hash1);
echo "Hash 2 (Encadenado a Hash 1): $hash2\n";

echo "\nNormalization Test:\n";
$testVals = [
    "121.00" => "121",
    "121.50" => "121.5",
    "121.55" => "121.55",
    " 121.00 " => "121",
    "B12345678" => "B12345678"
];

$reflect = new ReflectionClass('VentaPDO');
$method = $reflect->getMethod('normalizarValorHash');
$method->setAccessible(true);

foreach ($testVals as $input => $expected) {
    $out = $method->invoke(null, $input);
    echo "Input: '$input' -> Output: '$out' (Expected: '$expected') - " . ($out === $expected ? "OK" : "FAIL") . "\n";
}
