<?php
require_once __DIR__ . '/csrf_check.php';

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/UsuarioPDO.php';

    $datos = json_decode(file_get_contents('php://input'), true) ?? [];
    $accion = $datos['accion'] ?? '';

    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autenticado']);
        exit;
    }

    if (!$_SESSION['usuarioActualTPV']->tienePermiso('gestionar_usuarios')) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Sin permisos']);
        exit;
    }

    switch ($accion) {
        case 'toggle_estado':
            $id = (int)($datos['id'] ?? 0);
            if (!$id) throw new InvalidArgumentException('ID de usuario inválido');
            if ($id === $_SESSION['usuarioActualTPV']->getId()) {
                throw new InvalidArgumentException('No puedes cambiar tu propio estado');
            }
            $activo = UsuarioPDO::toggleEstatus($id);
            echo json_encode(['ok' => true, 'activo' => $activo]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Acción desconocida']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
