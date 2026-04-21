<?php

/**
 * Script de prueba: Cola de Envíos AEAT
 * Verifica que el sistema encole correctamente y intente procesar.
 */

require_once __DIR__ . '/../model/AeatQueueService.php';
require_once __DIR__ . '/../model/DBPDO.php';

echo "=== TEST: COLA DE ENVIOS AEAT ===\n";

try {
    $queue = new AeatQueueService();
    
    // 1. Simular una venta existente (buscamos una id válida)
    $db = DBPDO::getPDO();
    $idVentaRow = $db->query("SELECT id FROM ventas ORDER BY id DESC LIMIT 1")->fetch();
    $idVenta = $idVentaRow ? $idVentaRow['id'] : 1;

    // 2. Encolar un XML ficticio
    echo "Encolando envío ficticio para venta ID: $idVenta...\n";
    $xmlPath = __DIR__ . '/dummy_aeat_test.xml';
    file_put_contents($xmlPath, '<?xml version="1.0" encoding="UTF-8"?><test>Contenido Dummy</test>');
    
    $queue->encolar($idVenta, $xmlPath);
    echo "[OK] Registro añadido a cola_envios.\n";

    // 3. Procesar la cola
    echo "Procesando cola (esto intentará conectar con AEAT si hay certificado)...\n";
    $resultado = $queue->procesarCola();

    echo "[INFO] Resultado del proceso: Éxitos: {$resultado['exitos']}, Fallos: {$resultado['fallos']}\n";
    
    // 4. Verificar estado final en BD
    $stmt = $db->prepare("SELECT estado, intentos, ultimo_error FROM cola_envios WHERE id_venta = :id ORDER BY id DESC LIMIT 1");
    $stmt->execute([':id' => $idVenta]);
    $row = $stmt->fetch();
    
    echo "[INFO] Estado en BD: {$row['estado']}\n";
    echo "[INFO] Intentos realizados: {$row['intentos']}\n";
    if ($row['ultimo_error']) {
        echo "[INFO] Último mensaje: " . substr($row['ultimo_error'], 0, 100) . "...\n";
    }

} catch (Exception $e) {
    echo "[ERROR] Fallo en el test: " . $e->getMessage() . "\n";
}

echo "=== FIN DEL TEST ===\n";
