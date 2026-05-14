<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: proveedores.php
 * Gestiona los proveedores (CRUD).
 */

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/ProveedorPDO.php';
    require_once __DIR__ . '/../model/Validador.php';

    // session_start(); // Handled by csrf_check.php

    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    $usuario = $_SESSION['usuarioActualTPV'];
    if (!$usuario->tienePermiso('gestionar_proveedores')) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'No tienes permiso para gestionar proveedores']);
        exit;
    }

    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $proveedor = ProveedorPDO::buscarPorId($_GET['id']);
                echo json_encode(['ok' => true, 'proveedor' => $proveedor ? [
                    'id' => $proveedor->getId(),
                    'cif_nif' => $proveedor->getCifNif(),
                    'nombre' => $proveedor->getNombre(),
                    'direccion' => $proveedor->getDireccion(),
                    'telefono' => $proveedor->getTelefono(),
                    'email' => $proveedor->getEmail(),
                    'aplica_re' => $proveedor->getAplicaRe(),
                    'notas' => $proveedor->getNotas(),
                    'activo' => $proveedor->getActivo(),
                    'vencimiento_dias' => $proveedor->getVencimientoDias()
                ] : null]);
            } else {
                $proveedores = ProveedorPDO::listarTodos(isset($_GET['soloActivos']) ? ($_GET['soloActivos'] === 'true') : true);
                $res = [];
                foreach ($proveedores as $p) {
                    $res[] = [
                        'id' => $p->getId(),
                        'cif_nif' => $p->getCifNif(),
                        'nombre' => $p->getNombre(),
                        'direccion' => $p->getDireccion(),
                        'telefono' => $p->getTelefono(),
                        'email' => $p->getEmail(),
                        'aplica_re' => $p->getAplicaRe(),
                        'activo' => $p->getActivo(),
                        'vencimiento_dias' => $p->getVencimientoDias()
                    ];
                }
                echo json_encode(['ok' => true, 'proveedores' => $res]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['nombre']) || !isset($data['cif_nif'])) {
                echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
                break;
            }

            if (!Validador::validarDocumento($data['cif_nif'])) {
                echo json_encode(['ok' => false, 'error' => 'El CIF/NIF proporcionado no tiene un formato válido.']);
                break;
            }

            if (!empty($data['telefono']) && !Validador::validarTelefono($data['telefono'])) {
                echo json_encode(['ok' => false, 'error' => 'El teléfono proporcionado no tiene un formato válido.']);
                break;
            }

            if (isset($data['id'])) {
                $success = ProveedorPDO::editarProveedor(
                    $data['id'], $data['cif_nif'], $data['nombre'],
                    $data['direccion'] ?? '', $data['telefono'] ?? '',
                    $data['email'] ?? '', $data['aplica_re'] ?? false,
                    $data['notas'] ?? '', $data['activo'] ?? true,
                    $data['vencimiento_dias'] ?? 0
                );
                echo json_encode(['ok' => true, 'success' => $success]);
            } else {
                $id = ProveedorPDO::agregarProveedor(
                    $data['cif_nif'], $data['nombre'], $data['direccion'] ?? '',
                    $data['telefono'] ?? '', $data['email'] ?? '',
                    $data['aplica_re'] ?? false, $data['notas'] ?? '',
                    $data['vencimiento_dias'] ?? 0
                );
                echo json_encode(['ok' => true, 'success' => (bool)$id, 'id' => $id]);
            }
            break;

        case 'DELETE':
            if (isset($_GET['id'])) {
                $success = ProveedorPDO::borrarProveedor($_GET['id']);
                echo json_encode(['ok' => true, 'success' => $success]);
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
