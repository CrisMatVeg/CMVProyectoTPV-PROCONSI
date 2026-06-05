<?php
/**
 * API: verifactuSubsanar.php
 * Procesa la subsanación de un registro VeriFactu erróneo.
 */

require_once __DIR__ . '/csrf_check.php';
require_once __DIR__ . '/../model/Usuario.php';

if (!isset($_SESSION['usuarioActualTPV'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');
require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/../model/DBPDO.php';
require_once __DIR__ . '/../model/VentaPDO.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$idVenta = (int)$data['idVenta'];
$nombre  = trim($data['nombre']);
$nif     = trim($data['nif']);

if ($idVenta <= 0) {
    echo json_encode(['ok' => false, 'msg' => 'ID de venta inválido']);
    exit;
}

// Verificar si es factura completa para exigir nombre/nif
$esFactura = (bool)DBPDO::ejecutarConsulta("SELECT es_factura FROM ventas WHERE id = :id", [':id' => $idVenta])->fetchColumn();

if ($esFactura && ($nombre === '' || $nif === '')) {
    echo json_encode(['ok' => false, 'msg' => 'El nombre y NIF son obligatorios para subsanar una Factura Completa']);
    exit;
}

try {
    $rechazo = ($data['rechazoPrevio'] ?? 'N') === 'S';

    // subsanarVenta encola el registro y desbloquea los posteriores internamente
    VentaPDO::subsanarVenta($idVenta, $nombre, $nif, $rechazo);

    // Disparar procesamiento inmediato de la cola (procesamos un lote pequeño para no demorar la respuesta)
    require_once __DIR__ . '/../model/AeatQueueService.php';
    $qService = new AeatQueueService();
    $qService->procesarCola(true, 5);

    echo json_encode(['ok' => true, 'mensaje' => 'Subsanación procesada y enviada a la AEAT.']);

} catch (Exception $e) {
    error_log('verifactuSubsanar: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Error al procesar la subsanación.']);
}
