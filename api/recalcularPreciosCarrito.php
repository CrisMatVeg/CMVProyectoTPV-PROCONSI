<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: recalcularPreciosCarrito.php
 * Recalcula el precio de cada línea del carrito usando PriceEngine (servidor).
 * Se llama justo antes de abrir el modal de cobro para obtener precios autoritativos.
 */

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/PriceEngine.php';

    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autenticado']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['lineas']) || !is_array($input['lineas'])) {
        echo json_encode(['ok' => true, 'lineas' => []]);
        exit;
    }

    $idCliente   = isset($input['id_cliente']) ? (int)$input['id_cliente'] : null;
    $codigoCupon = $input['codigoCupon'] ?? null;
    if (!$idCliente) $idCliente = null;

    $lineasResult = [];

    foreach ($input['lineas'] as $linea) {
        $idProducto = isset($linea['id']) ? (int)$linea['id'] : 0;
        $qty        = max(1, (int)($linea['qty'] ?? 1));

        // Comodines y productos sin ID: precio del frontend sin modificar
        if ($idProducto <= 0) {
            $lineasResult[] = [
                'id'             => $idProducto,
                'precio_base'    => (float)($linea['price'] ?? 0),
                'precio_final'   => (float)($linea['price'] ?? 0),
                'descuentos'     => [],
                'appliedTariffs' => [],
                'comodin'        => true,
            ];
            continue;
        }

        try {
            $breakdown = PriceEngine::calculate($idProducto, $idCliente, $qty, $codigoCupon);
            $lineasResult[] = [
                'id'             => $idProducto,
                'precio_base'    => $breakdown['precio_base'],
                'precio_final'   => $breakdown['precio_unitario_final'],
                'descuentos'     => $breakdown['descuentos'],
                'appliedTariffs' => $breakdown['descuentos'],
                'comodin'        => false,
            ];
        } catch (Throwable $e) {
            // Si PriceEngine falla para un producto, pasar precio del frontend
            $lineasResult[] = [
                'id'             => $idProducto,
                'precio_base'    => (float)($linea['price'] ?? 0),
                'precio_final'   => (float)($linea['price'] ?? 0),
                'descuentos'     => [],
                'appliedTariffs' => [],
                'comodin'        => false,
            ];
        }
    }

    echo json_encode(['ok' => true, 'lineas' => $lineasResult]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error del servidor']);
}
