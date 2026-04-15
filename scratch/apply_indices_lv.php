<?php
/**
 * Script de optimización adicional para ElectroBazar TPV (Analítica).
 * Añade índices necesarios en lineas_venta.
 */
require_once 'model/DBPDO.php';

echo "Iniciando optimización de tablas de detalles...\n";

$sqlIndices = [
    // Índices para LINEAS DE VENTA
    "CREATE INDEX idx_lv_venta_dev ON lineas_venta(id_venta, devuelta)",
    "CREATE INDEX idx_lv_producto_dev ON lineas_venta(id_producto, devuelta)",
    "CREATE INDEX idx_lv_total ON lineas_venta(total_linea)"
];

foreach ($sqlIndices as $sql) {
    try {
        echo "Ejecutando: $sql ... ";
        DBPDO::ejecutarConsulta($sql);
        echo "OK\n";
    } catch (PDOException $e) {
        if ($e->getCode() == '42000' || strpos($e->getMessage(), 'Duplicate key') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "Saltado (Ya existe)\n";
        } else {
            echo "ERROR: " . $e->getMessage() . "\n";
        }
    }
}

echo "Optimización completada.\n";
