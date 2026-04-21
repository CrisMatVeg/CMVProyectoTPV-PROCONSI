<?php

/**
 * Script de prueba CLI: Verificación del Motor VeriFactu
 * Simula el procesamiento de una venta y la generación del XML fiscal.
 */

// 1. Configuración del entorno
require_once __DIR__ . '/../model/VentaPDO.php';
require_once __DIR__ . '/../model/VeriFactuService.php';
require_once __DIR__ . '/../model/DBPDO.php';

echo "=== TEST: MOTOR DE FACTURACIÓN VERIFACTU ===\n";

try {
    $vfService = new VeriFactuService();

    // 2. Datos de prueba (Simulación de una factura simplificada F2)
    $numeroTicket = 9999; 
    $fechaVenta = date('Y-m-d H:i:s');
    $numeroSerie = "T-" . date('jnY') . "-" . $numeroTicket;
    
    $datosPrueba = [
        'numero_serie'     => $numeroSerie,
        'fecha_expedicion' => date('d-m-Y'),
        'tipo_factura'     => 'F2',
        'base_imponible'   => 82.64,
        'cuota_total'      => 17.36,
        'importe_total'    => 100.00,
        'hash_actual'      => strtoupper(hash('sha256', 'CADENA_DE_PRUEBA_HASH_ACTUAL')),
        'hash_anterior'    => strtoupper(hash('sha256', 'CADENA_DE_PRUEBA_HASH_ANTERIOR')),
        'fecha_hora_gen'   => date('Y-m-d\TH:i:sP'),
        'serie_anterior'   => 'T-16042026-9998',
        'fecha_anterior'   => '16-04-2026'
    ];

    echo "Generando XML para la serie: {$datosPrueba['numero_serie']}...\n";

    // 3. Procesar Alta
    $resultado = $vfService->procesarAlta($datosPrueba);

    if ($resultado['ok']) {
        echo "[OK] XML generado correctamente.\n";
        echo "[INFO] Ruta: " . $resultado['path'] . "\n";
        echo "[INFO] Hash Actual: " . $resultado['hash'] . "\n";
        
        // Verificar existencia del archivo
        if (file_exists($resultado['path'])) {
            echo "[OK] El archivo físico existe en el almacenamiento.\n";
            $xmlContent = file_get_contents($resultado['path']);
            echo "[INFO] Tamaño del archivo: " . strlen($xmlContent) . " bytes.\n";
            
            if (strpos($xmlContent, '<ds:Signature') === false) {
                echo "[AVISO] El XML no contiene firma (es normal si no se ha configurado un certificado .p12 válido).\n";
            } else {
                echo "[OK] El XML contiene una sección de firma digital.\n";
            }
        }
    } else {
        echo "[ERROR] Falló la generación del XML.\n";
        echo "[DETALLE] " . $resultado['error'] . "\n";
    }

} catch (Exception $e) {
    echo "[CRITICAL] Error en el script de prueba: " . $e->getMessage() . "\n";
}

echo "=== FIN DEL TEST ===\n";
