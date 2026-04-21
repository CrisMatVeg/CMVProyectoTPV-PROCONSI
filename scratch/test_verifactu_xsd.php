<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/confApp.php';
require_once __DIR__ . '/../model/VeriFactuService.php';

$vf = new VeriFactuService();

// Mock data for Altra
$datosAlta = [
    'numero_serie'     => 'T-2026-9999',
    'fecha_expedicion' => '21-04-2026',
    'tipo_factura'     => 'F1',
    'base_imponible'   => 100.00,
    'cuota_total'      => 21.00,
    'importe_total'    => 121.00,
    'hash_actual'      => strtoupper(hash('sha256', 'test_alta')),
    'hash_anterior'    => null, // Primer registro
    'fecha_hora_gen'   => date('Y-m-d\TH:i:sP')
];

echo "Testing ALTA...\n";
$resAlta = $vf->procesarAlta($datosAlta);
if ($resAlta['ok']) {
    echo "Alta XML generated: " . $resAlta['path'] . "\n";
} else {
    echo "Alta ERROR: " . $resAlta['error'] . "\n";
}

// Mock data for Anulacion
$datosAnul = [
    'numero_serie'     => 'T-2026-9999',
    'fecha_expedicion' => '21-04-2026',
    'hash_actual'      => strtoupper(hash('sha256', 'test_anul')),
    'hash_anterior'    => $resAlta['hash'] ?? strtoupper(hash('sha256', 'dummy_prev')),
    'fecha_hora_gen'   => date('Y-m-d\TH:i:sP'),
    'serie_anterior'   => 'T-2026-9999',
    'fecha_anterior'   => '21-04-2026'
];

echo "Testing ANULACION...\n";
$resAnul = $vf->procesarAnulacion($datosAnul);
if ($resAnul['ok']) {
    echo "Anulacion XML generated: " . $resAnul['path'] . "\n";
} else {
    echo "Anulacion ERROR: " . $resAnul['error'] . "\n";
}
