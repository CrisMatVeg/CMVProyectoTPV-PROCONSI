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
    $metodo = $input['metodoReembolso'] ?? 'efectivo';

    // Función interna para validar plazos
    $validarPlazos = function (string $fechaVenta, int $mesesGarantia, string $metodo) {
        $fechaVentaTs = strtotime($fechaVenta);
        $ahoraTs = time();
        $diasDiferencia = ($ahoraTs - $fechaVentaTs) / (60 * 60 * 24);

        if ($metodo === 'efectivo' || $metodo === 'vale') {
            if ($diasDiferencia > 30) {
                throw new Exception("El plazo para reembolsos (30 días) ha expirado. Solo se permite el cambio por garantía si el producto tiene fallos.");
            }
        }

        if ($metodo === 'reemplazo') {
            $mesesDiferencia = ($ahoraTs - $fechaVentaTs) / (60 * 60 * 24 * 30.4375);
            if ($mesesDiferencia > $mesesGarantia) {
                throw new Exception("El plazo de garantía legal ($mesesGarantia meses) ha expirado para este producto.");
            }
        }
    };

    // El efectivo disponible en caja limita solo las devoluciones en efectivo
    $efectivoDisponible = CajaTurnoPDO::obtenerEfectivoDisponible();

    if ($accion === 'devolverLinea') {
        $idLinea = (int)($input['idLinea'] ?? 0);
        if ($idLinea <= 0) {
            throw new Exception('Línea de venta inválida');
        }

        // Obtener datos para validación
        $q = DBPDO::ejecutarConsulta(
            "SELECT l.total_linea, l.devuelta, v.estado, v.fecha as fecha_venta, l.meses_garantia, v.id_cliente
             FROM lineas_venta l 
             JOIN ventas v ON l.id_venta = v.id 
             WHERE l.id = :id",
            [':id' => $idLinea]
        );
        $l = $q->fetch(PDO::FETCH_ASSOC);
        if (!$l) {
            throw new Exception('Línea de venta no encontrada');
        }

        if ($l['estado'] !== 'completada') {
            throw new Exception('No se pueden devolver productos de una venta pendiente de pago.');
        }
        if (!empty($l['devuelta'])) {
            throw new Exception('Esta línea ya ha sido devuelta');
        }

        // Validar plazos
        $validarPlazos($l['fecha_venta'], (int)$l['meses_garantia'], $metodo);

        // Ya no se valida si hay cliente para 'vale' porque ahora es un Vale/Abono genérico

        // Validar efectivo si se pide cash
        $importeDevolucion = (float)$l['total_linea'];
        if ($metodo === 'efectivo' && $importeDevolucion > $efectivoDisponible + 0.009) {
            throw new Exception('No hay suficiente efectivo en caja para realizar esta devolución.');
        }

        $res = VentaPDO::devolverLinea($idLinea, $motivo, $metodo);
        echo json_encode(['ok' => $res]);
    } else if ($accion === 'devolverTicket') {
        $numTicket = (int)($input['numTicket'] ?? 0);
        if ($numTicket <= 0) {
            throw new Exception('Número de ticket inválido');
        }

        $venta = VentaPDO::obtenerVentaPorTicket($numTicket);
        if (!$venta) {
            throw new Exception('Ticket no encontrado');
        }
        if ($venta['estado'] !== 'completada') {
            throw new Exception('El ticket no se puede devolver porque no está completado.');
        }

        // Validar plazos básicos del ticket (basado en la fecha de la venta para cualquier reembolso monetario)
        if ($metodo !== 'reemplazo') {
            $validarPlazos($venta['fecha'], 36, $metodo);
        }

        if ($metodo === 'saldo_cliente' && empty($venta['id_cliente'])) {
            throw new Exception('Venta anónima. No se puede reembolsar al saldo.');
        }

        $importeTotalDevolver = 0.0;
        foreach ($venta['lineas'] as $linea) {
            if (empty($linea['devuelta'])) {
                $importeTotalDevolver += (float)$linea['total_linea'];
            }
        }

        if ($importeTotalDevolver <= 0) {
            throw new Exception('No hay líneas pendientes de devolver');
        }

        if ($metodo === 'efectivo' && $importeTotalDevolver > $efectivoDisponible + 0.009) {
            throw new Exception('No hay suficiente efectivo en caja para anular este ticket.');
        }

        // Iterar y devolver cada línea con el método elegido
        $success = true;
        foreach ($venta['lineas'] as $linea) {
            if (empty($linea['devuelta'])) {
                if (!VentaPDO::devolverLinea((int)$linea['id'], $motivo, $metodo)) {
                    $success = false;
                }
            }
        }

        echo json_encode(['ok' => $success]);
    } else {
        throw new Exception('Acción no válida');
    }
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
