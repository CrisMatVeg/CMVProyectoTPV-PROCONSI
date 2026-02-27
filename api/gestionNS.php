<?php
/**
 * API: gestionNS.php
 * Gestiona operaciones sobre números de serie de productos.
 */
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/NumeroSeriePDO.php';

    session_start();

    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autenticado']);
        exit;
    }

    $datos = json_decode(file_get_contents('php://input'), true);
    $accion = $datos['accion'] ?? '';

    switch ($accion) {
        case 'listar':
            $id = (int)$datos['id_producto'];
            $lista = NumeroSeriePDO::listarPorProducto($id);
            echo json_encode(['ok' => true, 'lista' => $lista]);
            break;

        case 'añadir':
            $id = (int)$datos['id_producto'];
            $ns = trim($datos['numero_serie']);
            if (empty($ns)) throw new Exception('El número de serie no puede estar vacío');
            $res = NumeroSeriePDO::añadirNumeroSerie($id, $ns);
            echo json_encode(['ok' => $res]);
            break;

        case 'eliminar':
            $id = (int)$datos['id'];
            $res = NumeroSeriePDO::eliminar($id);
            echo json_encode(['ok' => $res]);
            break;

        default:
            echo json_encode(['ok' => false, 'error' => 'Acción no válida']);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
