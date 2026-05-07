<?php
/**
 * API: verifactuSubsanar.php
 * Procesa la subsanación de un registro VeriFactu erróneo.
 */

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
    $idVenta = (int)$data['idVenta'];
    $nombre = trim($data['nombre']);
    $nif = trim($data['nif']);
    $rechazo = ($data['rechazoPrevio'] ?? 'N') === 'S';

    // subsanarVenta encola el registro y desbloquea los posteriores internamente
    VentaPDO::subsanarVenta($idVenta, $nombre, $nif, $rechazo);

    // Disparar procesamiento inmediato de la cola (procesamos un lote pequeño para no demorar la respuesta)
    require_once __DIR__ . '/../model/AeatQueueService.php';
    $qService = new AeatQueueService();
    $qService->procesarCola(true, 5);

    echo json_encode(['ok' => true, 'msg' => 'Subsanación procesada y enviada a la AEAT.']);

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'msg' => 'Error técnico: ' . $e->getMessage()]);
}
