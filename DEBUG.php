<?php
/**
 * SCRIPT DE DIAGNÓSTICO DEL SISTEMA
 * Verifica la salud de la BD y la integridad de los archivos.
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'config/confDBPDO.php';
    require_once 'model/DBPDO.php';

    echo "--- DIAGNÓSTICO ELECTROBAZAR TPV ---\n\n";

    $tablas = ['usuarios', 'productos', 'ventas', 'lineas_venta', 'clientes'];

    foreach($tablas as $tabla) {
        echo "Verificando tabla: $tabla\n";
        try {
            $q = DBPDO::ejecutarConsulta("DESCRIBE $tabla");
            while($row = $q->fetch(PDO::FETCH_ASSOC)) {
                echo "  - {$row['Field']} ({$row['Type']})\n";
            }
        } catch (Exception $e) {
            echo "  [ERROR]: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    echo "Verificando Archivos Críticos:\n";
    $files = [
        'model/UsuarioPDO.php',
        'model/VentaPDO.php',
        'controller/cDashboard.php',
        'view/vDashboard.php'
    ];

    foreach($files as $f) {
        if(file_exists($f)) {
            echo "  [OK] $f existe.\n";
            // Check syntax
            $output = shell_exec("php -l $f");
            echo "       Check sintaxis: " . ($output ? trim($output) : "N/A") . "\n";
        } else {
            echo "  [MISSING] $f NO EXISTE.\n";
        }
    }

} catch (Throwable $e) {
    echo "\n[ERROR CRÍTICO]: " . $e->getMessage() . "\n";
}
