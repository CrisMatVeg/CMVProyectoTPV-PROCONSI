<?php

/**
 * Script de Tarea Programada (Cron)
 * Procesa la cola de envíos VeriFactu a la AEAT.
 * Ejecución sugerida: cada 5 minutos.
 * Comandos: php scripts/verifactu_cron.php
 */

if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ejecutarse desde la línea de comandos (CLI).\n");
}

require_once __DIR__ . '/../model/AeatQueueService.php';

echo "[" . date('Y-m-d H:i:s') . "] Iniciando procesamiento de cola AEAT...\n";

try {
    $service = new AeatQueueService();
    $resultado = $service->procesarCola();

    echo "[RESULTADO] Éxitos: {$resultado['exitos']}, Fallos: {$resultado['fallos']}\n";
    
} catch (Exception $e) {
    echo "[ERROR] Fallo crítico en el cron: " . $e->getMessage() . "\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Fin del procesamiento.\n";
