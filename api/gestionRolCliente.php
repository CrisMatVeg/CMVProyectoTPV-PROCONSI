<?php

/**
 * API: gestionRolCliente.php
 * Endpoint para crear un nuevo rol de cliente dinámicamente.
 */
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/RolClientePDO.php';

    session_start();

    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['accion'])) {
        $input = $_POST;
    }

    $accion = $input['accion'] ?? '';

    if ($accion === 'crear') {
        if ($_SESSION['usuarioActualTPV']->getRol() !== 'admin') {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Solo administradores pueden crear roles']);
            exit;
        }

        $nombre = trim($input['nombre'] ?? '');
        if ($nombre === '') {
            throw new Exception("El nombre del rol no puede estar vacío");
        }

        // Verificar si existe usando listarRoles
        $roles = RolClientePDO::listarRoles();
        foreach ($roles as $r) {
            if (strtolower($r['nombre']) === strtolower($nombre)) {
                throw new Exception("El rol '$nombre' ya existe");
            }
        }

        $id = RolClientePDO::añadirRol($nombre);
        echo json_encode(['ok' => true, 'id' => $id, 'nombre' => strtolower($nombre)]);
        exit;
    }

    throw new Exception("Acción no reconocida");
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
