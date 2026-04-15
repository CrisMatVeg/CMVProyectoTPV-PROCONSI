<?php
require_once 'model/DBPDO.php';
require_once 'model/CategoriaPDO.php';
require_once 'model/ProductoPDO.php';

echo "--- CATEGORIAS ---\n";
$cats = CategoriaPDO::listarTodas();
foreach ($cats as $c) {
    echo "ID: {$c['id']} | Codigo: '{$c['codigo']}' | Nombre: '{$c['nombre']}'\n";
}

echo "\n--- MUESTRA DE PRODUCTOS (categoria field) ---\n";
$res = DBPDO::ejecutarConsulta("SELECT id, nombre, categoria FROM productos LIMIT 10");
while ($p = $res->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$p['id']} | Nombre: '{$p['nombre']}' | categoria: '{$p['categoria']}'\n";
}
?>
