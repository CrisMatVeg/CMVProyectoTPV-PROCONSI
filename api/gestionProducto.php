<?php
/**
 * API: gestionProducto.php
 * Gestiona las operaciones CRUD de productos (solo admins).
 * Acciones: añadir | editar | eliminar | baja
 * Devuelve JSON con el resultado.
 */
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/Producto.php';
    require_once __DIR__ . '/../model/ProductoPDO.php';

    session_start();

    // Solo POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
        exit;
    }

    // Solo usuarios autenticados
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autenticado']);
        exit;
    }

    // Solo administradores
    if ($_SESSION['usuarioActualTPV']->getRol() !== 'admin') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Acceso restringido a administradores']);
        exit;
    }

    $datos = json_decode(file_get_contents('php://input'), true);
    $accion = $datos['accion'] ?? '';

    switch ($accion) {

        case 'añadir':
            $nuevo = ProductoPDO::añadirProducto($datos);
            echo json_encode([
                'ok'       => true,
                'producto' => [
                    'id'       => (int)$nuevo['id'],
                    'name'     => $nuevo['nombre'],
                    'codigo'   => $nuevo['codigo'],
                    'price'    => (float)$nuevo['precio'],
                    'icono'    => $nuevo['icono'],
                    'cat'      => $nuevo['categoria'],
                    'inactive' => false,
                ]
            ]);
            break;

        case 'editar':
            $id = (int)($datos['id'] ?? 0);
            if (!$id) throw new InvalidArgumentException('ID de producto inválido');
            ProductoPDO::editarProducto($id, $datos);
            echo json_encode(['ok' => true]);
            break;

        case 'eliminar':
            $id = (int)($datos['id'] ?? 0);
            if (!$id) throw new InvalidArgumentException('ID de producto inválido');
            ProductoPDO::eliminarProducto($id);
            echo json_encode(['ok' => true]);
            break;

        case 'baja':
            $id = (int)($datos['id'] ?? 0);
            if (!$id) throw new InvalidArgumentException('ID de producto inválido');
            $activo = ProductoPDO::toggleBaja($id);
            echo json_encode(['ok' => true, 'activo' => $activo]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => "Acción desconocida: $accion"]);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error del servidor: ' . $e->getMessage()]);
}
