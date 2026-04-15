<?php
require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/../model/DBPDO.php';

header('Content-Type: text/plain');

try {
    $q = DBPDO::ejecutarConsulta("SELECT id, nombre, aplicada, tipo_cliente, roles_segmento, scope, categoria, producto_ids FROM tarifas_precios WHERE nombre LIKE '%Mayoristas%'");
    $data = $q->fetchAll(PDO::FETCH_ASSOC);
    print_r($data);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
