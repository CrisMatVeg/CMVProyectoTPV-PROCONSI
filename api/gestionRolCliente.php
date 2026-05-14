<?php
require_once __DIR__ . '/csrf_check.php';

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

    // session_start(); // Handled by csrf_check.php

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
        if (!$_SESSION['usuarioActualTPV']->tienePermiso('gestionar_clientes')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'No tienes permiso para crear roles de cliente']);
            exit;
        }

        $nombre = trim($input['nombre'] ?? '');
        if ($nombre === '') {
            throw new Exception("El nombre del rol no puede estar vacío");
        }

        $roles = RolClientePDO::listarRoles();
        foreach ($roles as $r) {
            if (strtolower($r['nombre']) === strtolower($nombre)) {
                throw new Exception("El rol '$nombre' ya existe");
            }
        }

        $id = RolClientePDO::agregarRol($nombre);
        echo json_encode(['ok' => true, 'id' => $id, 'nombre' => strtolower($nombre)]);
        exit;
    }

    if ($accion === 'eliminar') {
        if (!$_SESSION['usuarioActualTPV']->tienePermiso('gestionar_clientes')) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'No tienes permiso para eliminar roles de cliente']);
            exit;
        }

        $nombre = strtolower(trim($input['nombre'] ?? ''));
        if ($nombre === '' || $nombre === 'general') {
            throw new Exception("El rol '$nombre' no puede eliminarse");
        }

        RolClientePDO::eliminarRol($nombre);
        echo json_encode(['ok' => true]);
        exit;
    }

    throw new Exception("Acción no reconocida");
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
