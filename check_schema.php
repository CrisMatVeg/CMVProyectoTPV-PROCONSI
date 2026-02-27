<?php
require_once 'config/confDBPDO.php';
require_once 'model/DBPDO.php';

try {
    $q = DBPDO::ejecutarConsulta("DESCRIBE cierres_fiscales");
    echo "COLUMNS IN cierres_fiscales:\n";
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }

    $q = DBPDO::ejecutarConsulta("DESCRIBE ventas");
    echo "\nCOLUMNS IN ventas:\n";
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage();
}
