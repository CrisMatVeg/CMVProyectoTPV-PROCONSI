<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: entradaStock.php
 * Registra una entrada de stock y actualiza el coste medio ponderado (CMP) del producto.
 */
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/EntradaStockPDO.php';

    // session_start(); // Handled by csrf_check.php

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
        exit;
    }

    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autenticado']);
        exit;
    }

    // Solo admins pueden registrar entradas de stock
    if ($_SESSION['usuarioActualTPV']->getRol() !== 'admin') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Sin permisos de administrador']);
        exit;
    }

    $datos = json_decode(file_get_contents('php://input'), true);

    $idProducto  = isset($datos['id_producto']) ? (int)$datos['id_producto'] : 0;
    $cantidad    = isset($datos['cantidad']) ? (int)$datos['cantidad'] : 0;
    $precioCoste = isset($datos['precio_coste']) ? (float)$datos['precio_coste'] : 0;
    $notas       = isset($datos['notas']) ? trim($datos['notas']) : '';

    if ($idProducto <= 0) {
        echo json_encode(['ok' => false, 'error' => 'ID de producto no válido']);
        exit;
    }
    if ($cantidad <= 0) {
        echo json_encode(['ok' => false, 'error' => 'La cantidad debe ser mayor que 0']);
        exit;
    }
    if ($precioCoste < 0) {
        echo json_encode(['ok' => false, 'error' => 'El precio de coste no puede ser negativo']);
        exit;
    }

    $idUsuario = $_SESSION['usuarioActualTPV']->getId();

    $resultado = EntradaStockPDO::registrarEntrada(
        $idProducto,
        $cantidad,
        $precioCoste,
        $idUsuario,
        $notas
    );

    echo json_encode([
        'ok'  => true,
        'msg' => "Stock actualizado. Nuevo CMP: " . number_format($resultado['cmp_resultante'], 2, ',', '.') . " €",
        'data' => $resultado,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error del servidor: ' . $e->getMessage()]);
}
