<?php

/**
 * api/gestionVariante.php
 * Endpoint para listar y editar variantes físicas.
 */
session_start();
require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/../model/DBPDO.php';
require_once __DIR__ . '/../model/VariantePDO.php';

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['usuarioActualTPV'])) {
        throw new Exception('No autenticado');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $accion = $input['accion'] ?? $_GET['accion'] ?? '';

    switch ($accion) {
        case 'listar':
            $id = (int)($input['id_producto'] ?? $_GET['id_producto'] ?? 0);
            if (!$id) throw new Exception("Falta ID de producto");
            $lista = VariantePDO::listarPorProducto($id);
            echo json_encode(['ok' => true, 'lista' => $lista]);
            break;

        case 'editar':
            $id = (int)($input['id'] ?? 0);
            if (!$id) throw new Exception("Falta ID de variante");
            VariantePDO::editar($id, $input);
            echo json_encode(['ok' => true]);
            break;

        default:
            throw new Exception("Acción no válida: $accion");
    }
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
