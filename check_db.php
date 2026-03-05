<?php
require_once 'config/confDBPDO.php';
require_once 'model/DBPDO.php';

$q = DBPDO::ejecutarConsulta('SHOW TABLES');
echo "TABLES:\n";
while ($r = $q->fetch(PDO::FETCH_NUM)) {
    echo $r[0] . "\n";
}

echo "\nPRODUCTOS SCHEMA:\n";
$q = DBPDO::ejecutarConsulta('DESCRIBE productos');
while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
    echo "{$r['Field']} - {$r['Type']}\n";
}
