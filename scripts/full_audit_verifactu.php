<?php
require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/../model/DBPDO.php';

function auditTable($tableName) {
    echo "\n--- TABLE: $tableName ---\n";
    try {
        $db = DBPDO::getPDO();
        $stmt = $db->query("DESCRIBE $tableName");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            echo "{$row['Field']} ({$row['Type']})\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

auditTable('ventas');
auditTable('cola_envios');
auditTable('configuracion');

echo "\n--- VERIFACTU CONFIGS ---\n";
try {
    $db = DBPDO::getPDO();
    $stmt = $db->query("SELECT * FROM configuracion WHERE clave LIKE '%aeat%' OR clave LIKE '%verifactu%' OR clave LIKE '%empresa%'");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "{$row['clave']} = {$row['valor']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
