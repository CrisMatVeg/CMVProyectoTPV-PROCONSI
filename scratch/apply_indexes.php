<?php
session_start();
require_once 'config/confDBPDO.php';
require_once 'core/DBPDO.php';

try {
    echo "Adding index idx_ventas_fecha...\n";
    // Usamos error_reporting(0) para ignorar si ya existe el índice en versiones antiguas de MySQL
    try {
        DBPDO::ejecutarConsulta("ALTER TABLE ventas ADD INDEX idx_ventas_fecha (fecha)", []);
        echo "Index idx_ventas_fecha added successfully.\n";
    } catch (Exception $e) {
        echo "Note: " . $e->getMessage() . "\n";
    }

    echo "Adding index idx_ventas_perf...\n";
    try {
        DBPDO::ejecutarConsulta("ALTER TABLE ventas ADD INDEX idx_ventas_perf (estado, metodo_pago, fecha)", []);
        echo "Index idx_ventas_perf added successfully.\n";
    } catch (Exception $e) {
        echo "Note: " . $e->getMessage() . "\n";
    }

    echo "Optimizing tables...\n";
    DBPDO::ejecutarConsulta("OPTIMIZE TABLE ventas, lineas_venta", []);
    echo "Done.\n";

} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
}
