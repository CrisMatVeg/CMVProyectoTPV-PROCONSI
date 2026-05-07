<?php
require_once __DIR__ . '/csrf_check.php';
/**
 * API: productos_proveedor.php
 * Gestiona el listado y vinculación de productos a un proveedor específico.
 */
require_once __DIR__ . '/../model/Usuario.php';
require_once __DIR__ . '/../model/DBPDO.php';
require_once __DIR__ . '/../model/ProductoPDO.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar sesión y permisos
if (!isset($_SESSION['usuarioActualTPV'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sesión no iniciada']);
    exit;
}

if (!$_SESSION['usuarioActualTPV']->tienePermiso('gestionar_proveedores') && !$_SESSION['usuarioActualTPV']->tienePermiso('gestionar_productos')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No tienes permiso para gestionar la vinculación de productos']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $idProveedor = isset($_GET['id_proveedor']) ? (int)$_GET['id_proveedor'] : 0;
        $accion = isset($_GET['accion']) ? $_GET['accion'] : 'listar';
        
        if ($accion === 'buscar_libres') {
            $termino = isset($_GET['term']) ? trim($_GET['term']) : '';
            $productos = ProductoPDO::buscarParaVincular($termino, $idProveedor);
            echo json_encode(['success' => true, 'productos' => $productos]);
        } else {
            // Listar vinculados
            if ($idProveedor <= 0) {
                echo json_encode(['success' => false, 'error' => 'ID de proveedor inválido']);
                exit;
            }
            $productos = ProductoPDO::listarPorProveedor($idProveedor);
            echo json_encode(['success' => true, 'productos' => $productos]);
        }
    } 
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $accion = $input['accion'] ?? '';
        
        if ($accion === 'vincular') {
            $idProv = (int)($input['id_proveedor'] ?? 0);
            $ids = $input['ids'] ?? [];
            if ($idProv <= 0 || empty($ids)) {
                echo json_encode(['success' => false, 'error' => 'Datos insuficientes']);
                exit;
            }
            $res = ProductoPDO::vincularAProveedor($idProv, $ids);
            echo json_encode(['success' => $res]);
        } 
        elseif ($accion === 'desvincular') {
            $ids = $input['ids'] ?? [];
            if (empty($ids)) {
                echo json_encode(['success' => false, 'error' => 'No hay productos seleccionados']);
                exit;
            }
            $res = ProductoPDO::desvincularDeProveedor($ids);
            echo json_encode(['success' => $res]);
        }
        else {
            echo json_encode(['success' => false, 'error' => 'Acción no reconocida']);
        }
    }
} catch (Throwable $e) {
    error_log("API productos_proveedor: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
