<?php
/**
 * SCRIPT DE REPARACIÓN DE ESQUEMA (TPV Profesional)
 * Añade las columnas faltantes en ventas y lineas_venta tras la reconstrucción.
 */
header('Content-Type: text/plain; charset=utf-8');

try {
    require_once 'config/confDBPDO.php';
    require_once 'model/DBPDO.php';

    echo "--- INICIANDO REPARACIÓN DE ESQUEMA ---\n\n";

    // 1. REPARAR TABLA VENTAS
    echo "[1/2] Reparando tabla 'ventas'...\n";
    $sqlVentas = "ALTER TABLE ventas 
        ADD COLUMN tipo_cliente ENUM('particular', 'empresa') DEFAULT 'particular' AFTER id_cliente,
        ADD COLUMN nombre_cliente VARCHAR(100) DEFAULT NULL AFTER tipo_cliente,
        ADD COLUMN nif_cliente VARCHAR(20) DEFAULT NULL AFTER nombre_cliente,
        ADD COLUMN descuento_pct DECIMAL(5,2) DEFAULT 0.00 AFTER metodo_pago,
        ADD COLUMN descuento_amt DECIMAL(10,2) DEFAULT 0.00 AFTER descuento_pct,
        ADD COLUMN base_imponible DECIMAL(10,2) DEFAULT 0.00 AFTER descuento_amt,
        ADD COLUMN iva_amt DECIMAL(10,2) DEFAULT 0.00 AFTER base_imponible";
    
    DBPDO::ejecutarConsulta($sqlVentas);
    echo "    > Columnas añadidas a 'ventas'.\n";

    // 2. REPARAR TABLA LINEAS_VENTA
    echo "\n[2/2] Reparando tabla 'lineas_venta'...\n";
    $sqlLineas = "ALTER TABLE lineas_venta 
        ADD COLUMN nombre_producto VARCHAR(100) DEFAULT '' AFTER id_producto,
        ADD COLUMN codigo_producto VARCHAR(50) DEFAULT '' AFTER nombre_producto";
    
    DBPDO::ejecutarConsulta($sqlLineas);
    echo "    > Columnas añadidas a 'lineas_venta'.\n";

    echo "\n--- REPARACIÓN COMPLETADA ---\n";
    echo "Vuelve a probar el login.";

} catch (Throwable $e) {
    echo "\n[ERROR]: " . $e->getMessage() . "\n";
}
