<?php
require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/../model/DBPDO.php';

/**
 * Script: migrate_verifactu.php
 * Proposito: Añadir las columnas necesarias para el encadenamiento VeriFactu.
 */

try {
    $db = DBPDO::getPDO();
    echo "Verificando tabla 'ventas'...\n";

    // 1. Añadir hash_actual
    try {
        $db->exec("ALTER TABLE ventas ADD COLUMN hash_actual CHAR(64) DEFAULT NULL AFTER es_factura");
        echo "Columna 'hash_actual' añadida.\n";
    } catch (Exception $e) { echo "Nota: " . $e->getMessage() . "\n"; }

    // 2. Añadir hash_anterior
    try {
        $db->exec("ALTER TABLE ventas ADD COLUMN hash_anterior CHAR(64) DEFAULT NULL AFTER hash_actual");
        echo "Columna 'hash_anterior' añadida.\n";
    } catch (Exception $e) { echo "Nota: " . $e->getMessage() . "\n"; }

    // 3. Añadir fecha_hora_gen_fiscal
    try {
        $db->exec("ALTER TABLE ventas ADD COLUMN fecha_hora_gen_fiscal DATETIME DEFAULT NULL AFTER hash_anterior");
        echo "Columna 'fecha_hora_gen_fiscal' añadida.\n";
    } catch (Exception $e) { echo "Nota: " . $e->getMessage() . "\n"; }

    // 4. Añadir estado_envio_aeat
    try {
        $db->exec("ALTER TABLE ventas ADD COLUMN estado_envio_aeat ENUM('pendiente', 'enviado', 'error', 'omitido') DEFAULT 'omitido' AFTER fecha_hora_gen_fiscal");
        echo "Columna 'estado_envio_aeat' añadida.\n";
    } catch (Exception $e) { echo "Nota: " . $e->getMessage() . "\n"; }

    // 5. Añadir codigo_qr
    try {
        $db->exec("ALTER TABLE ventas ADD COLUMN codigo_qr TEXT DEFAULT NULL AFTER estado_envio_aeat");
        echo "Columna 'codigo_qr' añadida.\n";
    } catch (Exception $e) { echo "Nota: " . $e->getMessage() . "\n"; }

    // 6. Crear tabla cola_envios
    echo "Verificando tabla 'cola_envios'...\n";
    $sqlCola = "CREATE TABLE IF NOT EXISTS cola_envios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_venta INT NOT NULL,
        xml_path VARCHAR(255) NOT NULL,
        intentos INT DEFAULT 0,
        ultimo_error TEXT NULL,
        estado ENUM('pendiente', 'enviado', 'error_critico') DEFAULT 'pendiente',
        fecha_proximo_intento DATETIME DEFAULT CURRENT_TIMESTAMP,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_cola_venta FOREIGN KEY (id_venta) REFERENCES ventas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    try {
        $db->exec($sqlCola);
        echo "Tabla 'cola_envios' verificada/creada.\n";
    } catch (Exception $e) {
        throw new Exception("Error al crear tabla 'cola_envios': " . $e->getMessage());
    }

    echo "\nMigración completada con éxito.\n";

} catch (Exception $e) {
    echo "ERROR CRÍTICO: " . $e->getMessage() . "\n";
}
