<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: gestionDevolucion.php
 * Genera un ticket de ABONO (devolución) independiente vinculado a la venta original.
 * No modifica el ticket de origen.
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

    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    $idUsuario = (int)$_SESSION['usuarioActualTPV']->getId();
    $input  = json_decode(file_get_contents('php://input'), true);
    $accion = $input['accion'] ?? '';
    $motivo = trim($input['motivo'] ?? 'Devolución estándar');
    $metodo = $input['metodoReembolso'] ?? 'efectivo';

    if (!in_array($metodo, ['efectivo', 'vale', 'reemplazo'])) {
        $metodo = 'efectivo';
    }

    // ── Validación de plazos ──────────────────────────────────────────────────
    $validarPlazos = function (string $fechaVenta, int $mesesGarantia, string $metodo) {
        $fechaVentaTs   = strtotime($fechaVenta);
        $diasDiferencia = (time() - $fechaVentaTs) / (60 * 60 * 24);

        if ($metodo === 'efectivo' || $metodo === 'vale') {
            if ($diasDiferencia > 30) {
                throw new Exception("El plazo para reembolsos (30 días) ha expirado. Solo se permite el cambio por garantía si el producto tiene fallos.");
            }
        }
        if ($metodo === 'reemplazo') {
            $mesesDiferencia = (time() - $fechaVentaTs) / (60 * 60 * 24 * 30.4375);
            if ($mesesDiferencia > $mesesGarantia) {
                throw new Exception("El plazo de garantía legal ($mesesGarantia meses) ha expirado para este producto.");
            }
        }
    };

    $efectivoDisponible = CajaTurnoPDO::obtenerEfectivoDisponible();

    // ── Acción: devolver una línea específica ──────────────────────────────────
    if ($accion === 'devolverLinea') {
        $idLinea  = (int)($input['idLinea'] ?? 0);
        $cantidad = isset($input['cantidad']) ? (int)$input['cantidad'] : null;
        $nuevo_id_cliente = isset($input['nuevo_id_cliente']) ? (int)$input['nuevo_id_cliente'] : null;

        if ($idLinea <= 0) throw new Exception('Línea de venta inválida');

        // Obtener datos de la línea + venta
        $q = DBPDO::ejecutarConsulta(
            "SELECT l.total_linea, l.cantidad, l.precio_unitario, l.devuelta,
                    v.estado, v.fecha AS fecha_venta, l.meses_garantia,
                    v.id_cliente, v.id AS id_venta, v.tipo_documento
             FROM lineas_venta l
             JOIN ventas v ON l.id_venta = v.id
             WHERE l.id = :id",
            [':id' => $idLinea]
        );
        $l = $q->fetch(PDO::FETCH_ASSOC);
        if (!$l) throw new Exception('Línea de venta no encontrada');

        // Solo se pueden devolver líneas de ventas normales (no de abonos)
        if (($l['tipo_documento'] ?? 'venta') === 'abono') {
            throw new Exception('No se puede devolver un línea de un ticket de abono.');
        }

        // Asignar cliente si se indica para vales
        if ($metodo === 'vale' && empty($l['id_cliente']) && $nuevo_id_cliente > 0) {
            DBPDO::ejecutarConsulta(
                "UPDATE ventas SET id_cliente = :idc WHERE id = :idv",
                [':idc' => $nuevo_id_cliente, ':idv' => $l['id_venta']]
            );
        }

        if (!in_array($l['estado'], ['completada', 'parcialmente_devuelta'])) {
            throw new Exception('No se pueden devolver productos de una venta en estado: ' . $l['estado']);
        }

        $cantidadOriginal = (int)$l['cantidad'];
        if ($cantidad === null || $cantidad <= 0) $cantidad = $cantidadOriginal;
        if ($cantidad > $cantidadOriginal) throw new Exception("Cantidad inválida para devolver (Máximo: $cantidadOriginal)");

        $validarPlazos($l['fecha_venta'], (int)$l['meses_garantia'], $metodo);

        $importeDevolucion = round($cantidad * (float)$l['precio_unitario'], 2);
        if ($metodo === 'efectivo' && $importeDevolucion > $efectivoDisponible + 0.009) {
            throw new Exception("No hay suficiente efectivo en caja para realizar esta devolución.");
        }

        // Crear el ticket de abono
        $numAbono = VentaPDO::crearAbono(
            $l['id_venta'],
            [['id_linea' => $idLinea, 'cantidad' => $cantidad]],
            $motivo,
            $metodo,
            $idUsuario
        );

        LogPDO::addLog('ABONO_GENERADO', "Abono #$numAbono generado: $cantidad uds de línea #$idLinea ($importeDevolucion€) - Motivo: $motivo");
        echo json_encode(['ok' => true, 'numTicketAbono' => $numAbono]);

    // ── Acción: devolver un ticket completo ────────────────────────────────────
    } elseif ($accion === 'devolverTicket') {
        $numTicket = (int)($input['numTicket'] ?? 0);
        $nuevo_id_cliente = isset($input['nuevo_id_cliente']) ? (int)$input['nuevo_id_cliente'] : null;

        if ($numTicket <= 0) throw new Exception('Número de ticket inválido');

        $venta = VentaPDO::obtenerVentaPorTicket($numTicket);
        if (!$venta) throw new Exception('Ticket no encontrado');

        if (($venta['tipo_documento'] ?? 'venta') === 'abono') {
            throw new Exception('No se puede abonar un ticket que ya es un abono.');
        }

        if (!in_array($venta['estado'], ['completada', 'parcialmente_devuelta'])) {
            throw new Exception('El ticket no se puede devolver porque está en estado: ' . $venta['estado']);
        }

        // Asignar cliente si se indica para vales
        if ($metodo === 'vale' && empty($venta['id_cliente']) && $nuevo_id_cliente > 0) {
            DBPDO::ejecutarConsulta(
                "UPDATE ventas SET id_cliente = :idc WHERE id = :idv",
                [':idc' => $nuevo_id_cliente, ':idv' => $venta['id']]
            );
            $venta['id_cliente'] = $nuevo_id_cliente;
        }

        if ($metodo !== 'reemplazo') $validarPlazos($venta['fecha'], 36, $metodo);
        if ($metodo === 'saldo_cliente' && empty($venta['id_cliente'])) {
            throw new Exception('Venta anónima. No se puede reembolsar al saldo.');
        }

        // Construir lista de líneas a devolver (todas las que no sean ya de un abono)
        $lineasADevolver = [];
        $importeTotalDevolver = 0.0;
        foreach ($venta['lineas'] as $linea) {
            // Las líneas de la venta original pueden tener devuelta=0 o devuelta=1 (legacy)
            // En el nuevo sistema tomamos todas las líneas de la venta original
            if (empty($linea['devuelta'])) {
                $lineasADevolver[] = ['id_linea' => (int)$linea['id'], 'cantidad' => (int)$linea['cantidad']];
                $importeTotalDevolver += (float)$linea['total_linea'];
            }
        }

        // Si todas las líneas ya estaban devueltas (legacy), usarlas igualmente
        if (empty($lineasADevolver)) {
            foreach ($venta['lineas'] as $linea) {
                $lineasADevolver[] = ['id_linea' => (int)$linea['id'], 'cantidad' => abs((int)$linea['cantidad'])];
                $importeTotalDevolver += abs((float)$linea['total_linea']);
            }
        }

        if ($importeTotalDevolver <= 0) throw new Exception('No hay importe pendiente de devolver.');
        if ($metodo === 'efectivo' && $importeTotalDevolver > $efectivoDisponible + 0.009) {
            throw new Exception('No hay suficiente efectivo en caja para anular este ticket.');
        }

        $numAbono = VentaPDO::crearAbono(
            (int)$venta['id'],
            $lineasADevolver,
            $motivo,
            $metodo,
            $idUsuario
        );

        LogPDO::addLog('ABONO_GENERADO', "Abono #$numAbono (anulación completa ticket #$numTicket, $importeTotalDevolver€) - Motivo: $motivo");
        echo json_encode(['ok' => true, 'numTicketAbono' => $numAbono]);

    } else {
        throw new Exception('Acción no válida');
    }

} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
