<?php
require_once __DIR__ . '/csrf_check.php';
header('Content-Type: application/json; charset=utf-8');
// session_start(); // Handled by csrf_check.php

if (!isset($_SESSION['usuarioActualTPV'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/ProductoPDO.php';

    // Obtener todos los productos activos
    $productos = ProductoPDO::listarProductos(true);
    $data = [];

    foreach ($productos as $p) {
        $data[] = [
            'id' => (int)$p->getId(),
            'stock' => (int)$p->getStockActual()
        ];
    }

    echo json_encode(['ok' => true, 'productos' => $data]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
