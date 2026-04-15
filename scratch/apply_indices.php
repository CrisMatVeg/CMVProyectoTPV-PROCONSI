<?php
/**
 * Script de optimización de base de datos para ElectroBazar TPV.
 * Añade índices necesarios para mejorar el rendimiento de la paginación profunda.
 */
require_once 'model/DBPDO.php';

echo "Iniciando optimización de base de datos...\n";

$sqlIndices = [
    // Índices para VENTAS (Más usados para filtrado y ordenación)
    "CREATE INDEX idx_ventas_fecha ON ventas(fecha)",
    "CREATE INDEX idx_ventas_cajero ON ventas(id_usuario)",
    "CREATE INDEX idx_ventas_z ON ventas(num_z)",
    "CREATE INDEX idx_ventas_tipo ON ventas(tipo_documento)",
    
    // Índices para CIERRES FISCALES
    "CREATE INDEX idx_cierres_fecha ON cierres_fiscales(fecha)",
    
    // Índices para DEUDAS
    "CREATE INDEX idx_deudas_cierre ON caja_deudas(id_cierre_fiscal)"
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
