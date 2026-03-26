<?php
require_once __DIR__ . '/csrf_check.php';

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
    require_once __DIR__ . '/../model/LogPDO.php';

    // session_start(); // Handled by csrf_check.php
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $accion = $input['accion'] ?? '';
    $motivo = $input['motivo'] ?? null;
    $metodo = $input['metodoReembolso'] ?? 'efectivo';

    $validarPlazos = function (string $fechaVenta, int $mesesGarantia, string $metodo) {
        $fechaVentaTs = strtotime($fechaVenta);
        $ahoraTs = time();
        $diasDiferencia = ($ahoraTs - $fechaVentaTs) / (60 * 60 * 24);
        if ($metodo === 'efectivo' || $metodo === 'vale') {
            if ($diasDiferencia > 30) throw new Exception("El plazo para reembolsos (30 días) ha expirado. Solo se permite el cambio por garantía si el producto tiene fallos.");
        }
        if ($metodo === 'reemplazo') {
            $mesesDiferencia = ($ahoraTs - $fechaVentaTs) / (60 * 60 * 24 * 30.4375);
            if ($mesesDiferencia > $mesesGarantia) throw new Exception("El plazo de garantía legal ($mesesGarantia meses) ha expirado para este producto.");
        }
    };

    $efectivoDisponible = CajaTurnoPDO::obtenerEfectivoDisponible();

    if ($accion === 'devolverLinea') {
        $idLinea = (int)($input['idLinea'] ?? 0);
        $cantidadADevolver = isset($input['cantidad']) ? (int)$input['cantidad'] : null;
        $nuevo_id_cliente = isset($input['nuevo_id_cliente']) ? (int)$input['nuevo_id_cliente'] : null;

        if ($idLinea <= 0) throw new Exception('Línea de venta inválida');

        $q = DBPDO::ejecutarConsulta(
            "SELECT l.total_linea, l.cantidad, l.precio_unitario, l.devuelta, v.estado, v.fecha as fecha_venta, l.meses_garantia, v.id_cliente, v.id as id_venta
             FROM lineas_venta l JOIN ventas v ON l.id_venta = v.id WHERE l.id = :id",
            [':id' => $idLinea]
        );
        $l = $q->fetch(PDO::FETCH_ASSOC);
        if (!$l) throw new Exception('Línea de venta no encontrada');

        if ($metodo === 'vale' && empty($l['id_cliente']) && $nuevo_id_cliente > 0) {
            DBPDO::ejecutarConsulta("UPDATE ventas SET id_cliente = :id_cliente WHERE id = :id_venta", [':id_cliente' => $nuevo_id_cliente, ':id_venta' => $l['id_venta']]);
        }

        if ($l['estado'] !== 'completada') throw new Exception('No se pueden devolver productos de una venta pendiente de pago.');
        if (!empty($l['devuelta'])) throw new Exception('Esta línea ya ha sido devuelta');

        $cantidadOriginal = (int)$l['cantidad'];
        if ($cantidadADevolver !== null) {
            if ($cantidadADevolver <= 0 || $cantidadADevolver > $cantidadOriginal) throw new Exception("Cantidad inválida para devolver (Máximo: $cantidadOriginal)");
        } else {
            $cantidadADevolver = $cantidadOriginal;
        }

        $validarPlazos($l['fecha_venta'], (int)$l['meses_garantia'], $metodo);
        $importeDevolucion = ($cantidadADevolver === $cantidadOriginal) ? (float)$l['total_linea'] : round($cantidadADevolver * (float)$l['precio_unitario'], 2);

        if ($metodo === 'efectivo' && $importeDevolucion > $efectivoDisponible + 0.009) {
            throw new Exception("No hay suficiente efectivo en caja para realizar esta devolución.");
        }

        $res = VentaPDO::devolverLinea($idLinea, $motivo, $metodo, $cantidadADevolver);
        if ($res) LogPDO::addLog('ANULACION_LINEA', "Devolución de $cantidadADevolver unidades (ID LÍNEA: $idLinea) por importe de " . number_format($importeDevolucion, 2, ',', '.') . "€ - Motivo: $motivo");
        echo json_encode(['ok' => $res]);

    } else if ($accion === 'devolverTicket') {
        $numTicket = (int)($input['numTicket'] ?? 0);
        $nuevo_id_cliente = isset($input['nuevo_id_cliente']) ? (int)$input['nuevo_id_cliente'] : null;
        if ($numTicket <= 0) throw new Exception('Número de ticket inválido');

        $venta = VentaPDO::obtenerVentaPorTicket($numTicket);
        if (!$venta) throw new Exception('Ticket no encontrado');

        if ($metodo === 'vale' && empty($venta['id_cliente']) && $nuevo_id_cliente > 0) {
            DBPDO::ejecutarConsulta("UPDATE ventas SET id_cliente = :id_cliente WHERE id = :id_venta", [':id_cliente' => $nuevo_id_cliente, ':id_venta' => $venta['id']]);
            $venta['id_cliente'] = $nuevo_id_cliente;
        }

        if ($venta['estado'] !== 'completada') throw new Exception('El ticket no se puede devolver porque no está completado.');
        if ($metodo !== 'reemplazo') $validarPlazos($venta['fecha'], 36, $metodo);
        if ($metodo === 'saldo_cliente' && empty($venta['id_cliente'])) throw new Exception('Venta anónima. No se puede reembolsar al saldo.');

        $importeTotalDevolver = 0.0;
        foreach ($venta['lineas'] as $linea) { if (empty($linea['devuelta'])) $importeTotalDevolver += (float)$linea['total_linea']; }
        if ($importeTotalDevolver <= 0) throw new Exception('No hay líneas pendientes de devolver');
        if ($metodo === 'efectivo' && $importeTotalDevolver > $efectivoDisponible + 0.009) throw new Exception('No hay suficiente efectivo en caja para anular este ticket.');

        $success = true;
        foreach ($venta['lineas'] as $linea) {
            if (empty($linea['devuelta'])) {
                if (!VentaPDO::devolverLinea((int)$linea['id'], $motivo, $metodo)) $success = false;
            }
        }
        if ($success) LogPDO::addLog('ANULACION_TICKET', "Anulación completa del ticket #$numTicket por importe de " . number_format($importeTotalDevolver, 2, ',', '.') . "€ - Motivo: $motivo");
        echo json_encode(['ok' => $success]);
    } else {
        throw new Exception('Acción no válida');
    }
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
