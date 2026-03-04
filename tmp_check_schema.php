<?php
require_once 'config/confDBPDO.php';
require_once 'model/DBPDO.php';

$tables = ['financieras', 'comisiones_plazo', 'ventas_financiacion'];

foreach ($tables as $t) {
    echo "\nTable: $t\n";
    try {
        $res = DBPDO::ejecutarConsulta("DESCRIBE $t");
        while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
            echo " - " . $row['Field'] . "\n";
        }
    } catch (Exception $e) {
        echo " ERROR: " . $e->getMessage() . "\n";
    }
}
