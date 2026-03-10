<?php

/**
 * API: obtenerHistorialStock.php
 * Devuelve el historial de movimientos de un producto.
 */
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/MovimientoStockPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';

    session_start();
    if (!isset($_SESSION['usuarioActualTPV'])) {
        throw new Exception('No autenticado');
    }

    $idProducto = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($idProducto <= 0) {
        throw new Exception('ID de producto no válido');
    }

    $historial = MovimientoStockPDO::obtenerHistorialPorProducto($idProducto);

    echo json_encode(['ok' => true, 'historial' => $historial]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
