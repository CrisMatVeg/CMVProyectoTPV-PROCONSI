<?php
require_once 'model/DBPDO.php';

echo "--- REPARTIENDO PRODUCTOS STRESS_TEST --- \n";

// Mover los primeros 5000 productos a Distribuciones Pérez (ID 2)
$res = DBPDO::ejecutarConsulta("UPDATE productos SET id_proveedor = 2 WHERE categoria = 'STRESS_TEST' LIMIT 5000");

echo "Actualización completada.\n";

$res = DBPDO::ejecutarConsulta("SELECT id_proveedor, count(*) as total FROM productos WHERE categoria = 'STRESS_TEST' GROUP BY id_proveedor");
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
    echo "Proveedor ID: " . ($row['id_proveedor'] ?: 'NONE') . " | Total: " . $row['total'] . "\n";
}
?>
