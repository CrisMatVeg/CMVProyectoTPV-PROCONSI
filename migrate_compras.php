<?php
require_once 'config/confDBPDO.php';

try {
    $db = new PDO(DSN, USERNAME, PASSWORD);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Iniciando migración de Compras y Proveedores...\n";

    // 1. Tabla Proveedores
    $sqlProveedores = "
        CREATE TABLE IF NOT EXISTS proveedores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cif_nif VARCHAR(20) NOT NULL UNIQUE,
            nombre VARCHAR(100) NOT NULL,
            direccion VARCHAR(255),
            telefono VARCHAR(20),
            email VARCHAR(100),
            aplica_re BOOLEAN NOT NULL DEFAULT FALSE,
            notas TEXT,
            activo BOOLEAN NOT NULL DEFAULT TRUE,
            fecha_alta DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $db->exec($sqlProveedores);
    echo "- Tabla 'proveedores' configurada.\n";

    // 2. Tabla Facturas de Compra (Cabecera)
    $sqlFacturas = "
        CREATE TABLE IF NOT EXISTS facturas_compra (
            id INT AUTO_INCREMENT PRIMARY KEY,
            proveedor_id INT NOT NULL,
            numero_factura VARCHAR(50) NOT NULL,
            fecha DATE NOT NULL,
            base_imponible DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            iva_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            re_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $db->exec($sqlFacturas);
    echo "- Tabla 'facturas_compra' configurada.\n";

    // 3. Tabla Líneas de Compra (Detalle)
    $sqlLineasCompra = "
        CREATE TABLE IF NOT EXISTS lineas_compra (
            id INT AUTO_INCREMENT PRIMARY KEY,
            factura_id INT NOT NULL,
            producto_id INT NOT NULL,
            cantidad INT NOT NULL,
            precio_coste_neto DECIMAL(10,2) NOT NULL,
            iva_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            re_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            FOREIGN KEY (factura_id) REFERENCES facturas_compra(id) ON DELETE CASCADE,
            FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $db->exec($sqlLineasCompra);
    echo "- Tabla 'lineas_compra' configurada.\n";

    // 4. Modificar lineas_venta para guardar el coste unitario histórico
    $sqlAlterVentas = "
        ALTER TABLE lineas_venta 
        ADD COLUMN precio_coste_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00 
        AFTER importe_unitario;
    ";

    // Check si existe la columna primero
    $stmt = $db->query("SHOW COLUMNS FROM lineas_venta LIKE 'precio_coste_unitario'");
    if ($stmt->rowCount() == 0) {
        $db->exec($sqlAlterVentas);
        echo "- Columna 'precio_coste_unitario' añadida a 'lineas_venta'.\n";
    } else {
        echo "- La columna 'precio_coste_unitario' ya existía en 'lineas_venta'.\n";
    }

    echo "\n¡Migración completada con éxito!\n";
} catch (PDOException $e) {
    echo "Error BD: " . $e->getMessage() . "\n";
}
