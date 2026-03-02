<?php
/**
 * API: gestionDevolucion.php
 * Permite devolver una línea específica o un ticket completo.
 */

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/VentaPDO.php';
    require_once __DIR__ . '/../model/CajaTurnoPDO.php';
    require_once __DIR__ . '/../model/ProductoPDO.php';

    session_start();
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $accion = $input['accion'] ?? '';
    $motivo = $input['motivo'] ?? null;

    // El efectivo disponible en caja limita cualquier devolución (todas se pagan en efectivo)
    $efectivoDisponible = CajaTurnoPDO::obtenerEfectivoDisponible();

    if ($accion === 'devolverLinea') {
        $idLinea = (int)($input['idLinea'] ?? 0);
        if ($idLinea <= 0) {
            throw new Exception('Línea de venta inválida');
        }

        // Importe de la línea a devolver
        $q = DBPDO::ejecutarConsulta(
            "SELECT total_linea, devuelta FROM lineas_venta WHERE id = :id",
            [':id' => $idLinea]
        );
        $l = $q->fetch(PDO::FETCH_ASSOC);
        if (!$l) {
            throw new Exception('Línea de venta no encontrada');
        }
        if (!empty($l['devuelta'])) {
            throw new Exception('Esta línea ya ha sido devuelta');
        }
        $importeDevolucion = (float)$l['total_linea'];

        if ($importeDevolucion > $efectivoDisponible + 0.009) {
            throw new Exception('No hay suficiente efectivo en caja para realizar esta devolución.');
        }

        $res = VentaPDO::devolverLinea($idLinea, $motivo);
        echo json_encode(['ok' => $res]);
    } else if ($accion === 'devolverTicket') {
        $numTicket = (int)($input['numTicket'] ?? 0);
        if ($numTicket <= 0) {
            throw new Exception('Número de ticket inválido');
        }

        // Importe pendiente de devolver de este ticket (solo líneas no devueltas)
        $venta = VentaPDO::obtenerVentaPorTicket($numTicket);
        if (!$venta || $venta['estado'] !== 'completada') {
            throw new Exception('El ticket no está disponible para devolución completa');
        }
        $importeDevolucion = 0.0;
        foreach ($venta['lineas'] as $l) {
            if (empty($l['devuelta'])) {
                $importeDevolucion += (float)$l['total_linea'];
            }
        }

        if ($importeDevolucion <= 0) {
            throw new Exception('No hay líneas pendientes de devolver en este ticket');
        }

        if ($importeDevolucion > $efectivoDisponible + 0.009) {
            throw new Exception('No hay suficiente efectivo en caja para anular completamente este ticket.');
        }

        $res = VentaPDO::devolverVenta($numTicket, $motivo);
        echo json_encode(['ok' => $res]);
    } else {
        throw new Exception('Acción no válida');
    }

} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
