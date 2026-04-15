<?php
require_once 'model/DBPDO.php';

echo "--- DISTRIBUCIÓN DE PROVEEDORES EN PRODUCTOS DE PRUEBA ---\n";
$res = DBPDO::ejecutarConsulta("SELECT id_proveedor, count(*) as total FROM productos WHERE categoria = 'STRESS_TEST' GROUP BY id_proveedor");
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
    $provId = $row['id_proveedor'] ?: 'SIN PROVEEDOR';
    echo "Proveedor ID: $provId | Productos: {$row['total']}\n";
}

echo "\n--- PROVEEDORES EXISTENTES ---\n";
$res = DBPDO::ejecutarConsulta("SELECT id, nombre FROM proveedores");
while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']} | Nombre: {$row['nombre']}\n";
}
?>
