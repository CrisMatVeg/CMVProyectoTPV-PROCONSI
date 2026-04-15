<?php
session_start();
require_once 'config/confDB.php';
require_once 'core/DBPDO.php';
require_once 'model/VentaPDO.php';
require_once 'model/UsuarioPDO.php';

try {
    $desde = '2020-01-01';
    $hasta = '2026-12-31';
    echo "Testing VentaPDO::obtenerKPIs...\n";
    $res = VentaPDO::obtenerKPIs($desde, $hasta);
    echo "RESULT:\n";
    print_r($res);
    
    echo "\nTesting VentaPDO::obtenerCharts...\n";
    $charts = [
        'evolucion' => VentaPDO::obtenerMargenesDetallados($desde, $hasta),
        'categorias' => VentaPDO::obtenerVentasPorCategoria($desde, $hasta)
    ];
    echo "CHARTS LOADED OK\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
