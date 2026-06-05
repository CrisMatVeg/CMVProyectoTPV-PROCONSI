<?php
require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/../model/VeriFactuQrService.php';

echo "--- TEST GENERACIÓN QR VERIFACTU ---\n";

$ventaSimulada = [
    'fecha' => '2026-04-17 10:45:00',
    'total' => 125.50,
    'numero_ticket' => 1234,
    'numero_ticket_formato' => 'FAC-2026-1234',
    'hash_actual' => 'A1B2C3D4E5F6A1B2C3D4E5F6A1B2C3D4E5F6A1B2C3D4E5F6A1B2C3D4E5F6A1B2'
];

$nifEmisor = 'B12345678';

try {
    echo "1. Generando URL AEAT...\n";
    $url = VeriFactuQrService::generarUrlAEAT($ventaSimulada, $nifEmisor);
    echo "URL: $url\n";

    $expectedBase = "https://www2.agenciatributaria.gob.es/wlpl/AVAC-HCCD/ES/S/H/VV";
    $hasBase = strpos($url, $expectedBase) === 0;
    $hasHash = strpos($url, 'hash=A1B2C3D4') !== false;
    $hasNif = strpos($url, 'nif=B12345678') !== false;

    if ($hasBase && $hasHash && $hasNif) {
        echo "✅ URL generada correctamente con base y hash (8 caracteres).\n";
    } else {
        echo "❌ Error en el formato de la URL.\n";
        if (!$hasBase) echo "   - Base URL incorrecta.\n";
        if (!$hasHash) echo "   - Falta el parámetro 'hash' o largo incorrecto.\n";
    }

    echo "\n2. Generando código QR Base64...\n";
    $base64 = VeriFactuQrService::generarQrBase64($url);
    
    if (strpos($base64, 'data:image/png;base64,') === 0) {
        echo "✅ QR Base64 generado correctamente (Longitud: " . strlen($base64) . " bytes).\n";
    } else {
        echo "❌ Error al generar el QR.\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
}
