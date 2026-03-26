<?php
require_once __DIR__ . '/csrf_check.php';
/**
 * API: obtenerHistorialCostes.php
 * Devuelve el historial de entradas de stock y cambios de CMP para un producto.
 */
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/EntradaStockPDO.php';

    // session_start(); // Handled by csrf_check.php

    // Solo usuarios autenticados
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autenticado']);
        exit;
    }

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if (!$id) {
        echo json_encode(['ok' => false, 'error' => 'ID de producto no proporcionado']);
        exit;
    }

    $historial = EntradaStockPDO::historialProducto($id);

    echo json_encode([
        'ok' => true,
        'historial' => $historial
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error del servidor: ' . $e->getMessage()]);
}
