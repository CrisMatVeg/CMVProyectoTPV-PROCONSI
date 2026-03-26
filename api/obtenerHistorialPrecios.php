<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: obtenerHistorialPrecios.php
 * Devuelve el histórico de cambios de precio base de un producto
 * desde la tabla auditoria_precios_base.
 */

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';

    // session_start(); // Handled by csrf_check.php

    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autenticado']);
        exit;
    }

    $idProducto = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if (!$idProducto) {
        echo json_encode(['ok' => false, 'error' => 'ID de producto requerido']);
        exit;
    }

    $sql = "SELECT 
                apb.id,
                apb.precio_old,
                apb.precio_new,
                apb.motivo,
                apb.fecha,
                u.nombre AS nombre_usuario
            FROM auditoria_precios_base apb
            LEFT JOIN usuarios u ON apb.id_usuario = u.id
            WHERE apb.id_producto = :id
            ORDER BY apb.fecha DESC
            LIMIT 200";

    $q = DBPDO::ejecutarConsulta($sql, [':id' => $idProducto]);
    $historial = $q->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'historial' => $historial]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
