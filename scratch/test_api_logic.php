<?php
require_once 'model/DBPDO.php';
require_once 'model/Producto.php';
require_once 'model/ProductoPDO.php';

echo "--- TEST LISTAR PRODUCTOS (accesorios) ---\n";
try {
    $lista = ProductoPDO::listarProductos(false, 100, 0, '', 'accesorios');
    echo "Encontrados: " . count($lista) . " productos\n";
    if (count($lista) > 0) {
        $p = $lista[0];
        echo "Primer producto: " . $p->getNombre() . " | Categoria: " . $p->getCategoria() . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n--- TEST LISTAR PRODUCTOS (movil) ---\n";
try {
    $lista = ProductoPDO::listarProductos(false, 100, 0, '', 'movil');
    echo "Encontrados: " . count($lista) . " productos\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
