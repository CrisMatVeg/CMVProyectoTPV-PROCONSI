<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: generarFacturaVenta.php
 * Convierte un ticket de venta existente en factura actualizando los datos del cliente.
 */
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/VentaPDO.php';
    require_once __DIR__ . '/../model/Validador.php';

    // session_start(); // Handled by csrf_check.php
    if (!isset($_SESSION['usuarioActualTPV'])) {
        throw new Exception('No autorizado', 401);
    }

    $body = json_decode(file_get_contents('php://input'), true);

    $idVenta       = isset($body['id_venta'])       ? (int)$body['id_venta']          : 0;
    $nombreCliente = isset($body['nombre_cliente'])  ? trim($body['nombre_cliente'])   : '';
    $nifCliente    = isset($body['nif_cliente'])     ? trim($body['nif_cliente'])      : '';

    if ($idVenta <= 0) {
        throw new Exception('ID de venta inválido');
    }
    if ($nombreCliente === '' || $nifCliente === '') {
        throw new Exception('Nombre y NIF/CIF son obligatorios');
    }

    if (!Validador::validarDocumento($nifCliente)) {
        throw new Exception('El NIF/CIF proporcionado no tiene un formato válido.');
    }

    $ok = VentaPDO::convertirAFactura($idVenta, $nombreCliente, $nifCliente);

    if (!$ok) {
        throw new Exception('No se pudo actualizar la venta');
    }

    echo json_encode(['ok' => true]);

} catch (Throwable $e) {
    $code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
