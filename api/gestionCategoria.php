<?php

/**
 * API: gestionCategoria.php
 * Maneja altas y bajas de categorías vía AJAX.
 */
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/CategoriaPDO.php';

    session_start();

    // Solo administradores
    if (!isset($_SESSION['usuarioActualTPV']) || $_SESSION['usuarioActualTPV']->getRol() !== 'admin') {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $accion = $input['accion'] ?? '';

    switch ($accion) {
        case 'añadir':
            $codigo = $input['codigo'] ?? '';
            $nombre = $input['nombre'] ?? '';
            if (empty($codigo) || empty($nombre)) {
                echo json_encode(['ok' => false, 'error' => 'Código y nombre son obligatorios']);
                break;
            }
            $res = CategoriaPDO::añadir($codigo, $nombre);
            echo json_encode(['ok' => $res]);
            break;

        case 'eliminar':
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['ok' => false, 'error' => 'ID inválido']);
                break;
            }
            $res = CategoriaPDO::eliminar($id);
            echo json_encode(['ok' => $res]);
            break;

        default:
            echo json_encode(['ok' => false, 'error' => 'Acción desconocida']);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error del servidor: ' . $e->getMessage()]);
}
