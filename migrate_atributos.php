<?php
require_once __DIR__ . '/config/confDBPDO.php';
require_once __DIR__ . '/model/DBPDO.php';

try {
    $sql = "ALTER TABLE productos ADD COLUMN IF NOT EXISTS atributos TEXT DEFAULT NULL AFTER categoria";
    DBPDO::ejecutarConsulta($sql);
    echo "OK: Columna 'atributos' añadida correctamente a la tabla 'productos'.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
