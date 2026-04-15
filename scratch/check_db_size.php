<?php
require_once 'config/confDBPDO.php';
require_once 'core/DBPDO.php';

try {
    $q = DBPDO::ejecutarConsulta("SELECT COUNT(*) as total FROM ventas", []);
    $ventas = $q->fetch(PDO::FETCH_ASSOC)['total'];
    
    $q = DBPDO::ejecutarConsulta("SELECT COUNT(*) as total FROM lineas_venta", []);
    $lineas = $q->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "Ventas: $ventas\n";
    echo "Lineas: $lineas\n";
    
    $q = DBPDO::ejecutarConsulta("SHOW INDEX FROM ventas", []);
    echo "Indices Ventas:\n";
    print_r($q->fetchAll(PDO::FETCH_ASSOC));
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
