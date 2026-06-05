<?php
require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/../model/DBPDO.php';
$db = DBPDO::getPDO();
$stmt = $db->query("DESCRIBE ventas");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . "\n";
}
echo "--- TABLES ---\n";
$stmt = $db->query("SHOW TABLES");
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $table) {
    echo $table . "\n";
}
