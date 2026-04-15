<?php
require_once 'config/confDBPDO.php';
require_once 'model/DBPDO.php';

$tables = ['ventas', 'lineas_venta', 'clientes', 'productos'];

foreach ($tables as $table) {
    echo "--- Table: $table ---\n";
    $q = DBPDO::ejecutarConsulta("SHOW INDEX FROM $table");
    while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
        echo "Index: {$row['Key_name']} | Column: {$row['Column_name']} | Unique: {$row['Non_unique']}\n";
    }
    echo "\n";
}
