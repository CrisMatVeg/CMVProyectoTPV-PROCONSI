<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: compras.php
 * Gestiona facturas y albaranes de compra.
 */

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/CompraPDO.php';

    // session_start(); // Handled by csrf_check.php

    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            $type = $_GET['type'] ?? 'facturas';
            if (isset($_GET['id'])) {
                if ($type === 'albaran') {
                    echo json_encode(CompraPDO::obtenerDetalleAlbaran($_GET['id']));
                } else {
                    echo json_encode(CompraPDO::obtenerDetalleFactura($_GET['id']));
                }
            } else {
                if ($type === 'albaranes_pendientes') {
                    echo json_encode(CompraPDO::listarAlbaranes(true));
                } elseif ($type === 'albaranes') {
                    echo json_encode(CompraPDO::listarAlbaranes(false));
                } else {
                    echo json_encode(CompraPDO::listarFacturas());
                }
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            $action = $data['action'] ?? 'registrar_albaran';

            if ($action === 'registrar_albaran') {
                if (!isset($data['proveedor_id']) || !isset($data['lineas']) || empty($data['lineas'])) {
                    echo json_encode(['ok' => false, 'error' => 'Datos de albarán incompletos']);
                    break;
                }
                $id = CompraPDO::registrarAlbaran(
                    $data['proveedor_id'],
                    $data['numero_albaran'] ?? 'S/N',
                    $data['fecha'] ?? date('Y-m-d'),
                    $data['lineas']
                );
                echo json_encode(['ok' => true, 'success' => (bool)$id, 'id' => $id]);
            } elseif ($action === 'registrar_factura') {
                if (!isset($data['proveedor_id']) || !isset($data['ids_albaranes']) || empty($data['ids_albaranes'])) {
                    echo json_encode(['ok' => false, 'error' => 'Datos de factura incompletos']);
                    break;
                }
                $id = CompraPDO::registrarFactura(
                    $data['proveedor_id'],
                    $data['numero_factura'],
                    $data['fecha'],
                    $data['ids_albaranes'],
                    $data['metodo_pago'] ?? 'banco'
                );
                echo json_encode(['ok' => true, 'success' => (bool)$id, 'id' => $id]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
