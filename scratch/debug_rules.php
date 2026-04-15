<?php
// debug_rules.php
require_once 'config/confDBPDO.php';
require_once 'model/DBPDO.php';
require_once 'model/PriceEngine.php';
require_once 'model/TarifaPrecioPDO.php';
require_once 'model/PromocionPDO.php';

// Simulate an ID. I'll try to find one from the DB first.
try {
    $q = DBPDO::ejecutarConsulta("SELECT id, nombre, categoria FROM productos LIMIT 1");
    $p = $q->fetch(PDO::FETCH_ASSOC);
    if (!$p) {
        die("No products found in DB\n");
    }
    echo "Testing product: " . $p['nombre'] . " (ID: " . $p['id'] . ", Cat: " . $p['categoria'] . ")\n";

    echo "\n--- All Active Rates in DB ---\n";
    $tarifasDB = DBPDO::ejecutarConsulta("SELECT id, nombre, scope, categoria, producto_ids, activo, aplicada, fecha_aplicacion FROM tarifas_precios")->fetchAll(PDO::FETCH_ASSOC);
    print_r($tarifasDB);

    echo "\n--- All Active Promos in DB ---\n";
    $promosDB = DBPDO::ejecutarConsulta("SELECT id, descripcion, categoria_code, producto_ids, activo FROM promociones")->fetchAll(PDO::FETCH_ASSOC);
    print_r($promosDB);

    echo "\n--- Rules returned by PriceEngine::getRulesForProduct ---\n";
    $rules = PriceEngine::getRulesForProduct($p['id']);
    print_r($rules);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
