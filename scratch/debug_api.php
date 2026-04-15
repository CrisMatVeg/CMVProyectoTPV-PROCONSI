<?php
require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/../model/DBPDO.php';
require_once __DIR__ . '/../model/PriceEngine.php';

header('Content-Type: text/plain');

$idProducto = $_GET['id'] ?? 1; // Default to 1 for testing
echo "DEBUGGING RULES FOR PRODUCT ID: $idProducto\n";
echo "==========================================\n\n";

try {
    // 1. Check Product existence
    $q = DBPDO::ejecutarConsulta("SELECT id, nombre, categoria FROM productos WHERE id = :id", [':id' => $idProducto]);
    $p = $q->fetch(PDO::FETCH_ASSOC);
    if (!$p) {
        echo "ERROR: Product NOT found in database.\n";
        exit;
    }
    echo "PRODUCT FOUND: {$p['nombre']} (Cat: {$p['categoria']})\n\n";

    // 2. Check ALL Active Rates
    echo "ACTIVE RATES (Total in DB):\n";
    $q = DBPDO::ejecutarConsulta("SELECT id, nombre, scope, aplicada, activo FROM tarifas_precios WHERE activo = 1");
    $tarifas = $q->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($tarifas) . " active rates.\n";
    foreach ($tarifas as $t) {
        echo " - Rate ID: {$t['id']}, Name: {$t['nombre']}, Scope: {$t['scope']}, Aplicada: {$t['aplicada']}\n";
    }
    echo "\n";

    // 3. Check ALL Active Promotions
    echo "ACTIVE PROMOTIONS (Total in DB):\n";
    $q = DBPDO::ejecutarConsulta("SELECT id, descripcion, activo FROM promociones WHERE activo = 1");
    $promos = $q->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($promos) . " active promos.\n";
    foreach ($promos as $pr) {
        echo " - Promo ID: {$pr['id']}, Desc: {$pr['descripcion']}\n";
    }
    echo "\n";

    // 4. Run the Engine call
    echo "ENGINE RESULTS (PriceEngine::getRulesForProduct):\n";
    $res = PriceEngine::getRulesForProduct($idProducto);
    echo "The Engine returned " . count($res) . " rules.\n";
    print_r($res);

} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage();
}
