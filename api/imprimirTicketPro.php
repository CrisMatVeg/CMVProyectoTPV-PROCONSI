<?php

/**
 * API: imprimirTicketPro.php
 * Renderiza el ticket o factura en HTML profesional para impresión desde el navegador.
 */

require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/../model/DBPDO.php';
require_once __DIR__ . '/../model/VentaPDO.php';

session_start();
if (!isset($_SESSION['usuarioActualTPV'])) {
    http_response_code(401);
    die('No autorizado');
}

try {
    $numTicket = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($numTicket <= 0) throw new Exception('Número de ticket inválido');

    $venta = VentaPDO::obtenerVentaPorTicket($numTicket);
    if (!$venta) throw new Exception('Venta no encontrada');

    require_once __DIR__ . '/../model/ConfiguracionPDO.php';
    $appConfig = ConfiguracionPDO::obtenerConfiguracion();

    $numeroStr = str_pad($venta['numero_ticket'], 4, '0', STR_PAD_LEFT);
    $fechaStr = date("d/m/Y H:i", strtotime($venta['fecha']));
    $nombreCajero = $venta['nombre_cajero'] ?? 'Sistema';

    $esFactura = $venta['tipo_cliente'] === 'empresa';
    $templatePath = $esFactura ? __DIR__ . '/../factura-electrobazar.html' : __DIR__ . '/../ticket-electrobazar.html';

    if (!file_exists($templatePath)) throw new Exception("Plantilla no encontrada");

    $html = file_get_contents($templatePath);
    $fmt2 = fn($n) => number_format((float)$n, 2, ',', '.') . ' €';

    if ($esFactura) {
        $lineasHTML = "";
        foreach ($venta['lineas'] as $l) {
            $desc = htmlspecialchars($l['nombre_producto']);
            $detalle = !empty($l['numeros_serie']) ? "SN: " . htmlspecialchars($l['numeros_serie']) : "";

            $lineasHTML .= "<tr>
                <td>$desc" . ($detalle ? "<span class='small'>$detalle</span>" : "") . "</td>
                <td style='text-align:center;'>" . (int)$l['cantidad'] . "</td>
                <td>" . $fmt2($l['precio_unitario']) . "</td>
                <td>" . ((float)$venta['descuento_pct'] > 0 ? (float)$venta['descuento_pct'] . '%' : '—') . "</td>
                <td>" . $fmt2($l['total_linea']) . "</td>
            </tr>";
        }

        $reemplazos = [
            '{{FACTURA_NUM}}' => 'FAC-' . date('Y', strtotime($venta['fecha'])) . '-' . $numeroStr,
            '{{FECHA_EMISION}}' => date("d/m/Y", strtotime($venta['fecha'])),
            '{{FECHA_VENCIMIENTO}}' => date("d/m/Y", strtotime($venta['fecha'] . " + 30 days")),
            '{{METODO_PAGO}}' => ucfirst($venta['metodo_pago']),
            '{{CLIENTE_NOMBRE}}' => htmlspecialchars($venta['nombre_cliente'] ?? '—'),
            '{{CLIENTE_CIF}}' => htmlspecialchars($venta['nif_cliente'] ?? '—'),
            '{{LINEAS}}' => $lineasHTML,
            '{{BASE_IMPONIBLE}}' => $fmt2($venta['base_imponible']),
            '{{DESCUENTO_AMT}}' => $fmt2($venta['descuento_amt']),
            '{{IVA_AMT}}' => $fmt2($venta['iva_amt']),
            '{{TOTAL}}' => $fmt2($venta['total']),
            '{{DISPLAY_DESCUENTO}}' => (float)$venta['descuento_amt'] > 0 ? '' : 'display:none;',
            '{{EMPRESA_NOMBRE}}' => htmlspecialchars($appConfig['empresa_nombre'] ?? ''),
            '{{EMPRESA_RAZON_SOCIAL}}' => htmlspecialchars($appConfig['empresa_razon_social'] ?? ''),
            '{{EMPRESA_NIF}}' => htmlspecialchars($appConfig['empresa_nif'] ?? ''),
            '{{EMPRESA_DIRECCION}}' => htmlspecialchars($appConfig['empresa_direccion'] ?? ''),
            '{{EMPRESA_TELEFONO}}' => htmlspecialchars($appConfig['empresa_telefono'] ?? ''),
            '{{EMPRESA_EMAIL}}' => htmlspecialchars($appConfig['empresa_email'] ?? ''),
            '{{EMPRESA_WEB}}' => htmlspecialchars($appConfig['empresa_web'] ?? ''),
            '{{EMPRESA_REGISTRO}}' => htmlspecialchars($appConfig['empresa_registro'] ?? ''),
            '{{TICKET_PIE_PAGINA}}' => htmlspecialchars($appConfig['ticket_pie_pagina'] ?? ''),
            '{{TICKET_POLITICA}}' => htmlspecialchars($appConfig['ticket_politica'] ?? '')
        ];
    } else {
        $lineasHTML = "";
        foreach ($venta['lineas'] as $l) {
            $nombre = htmlspecialchars($l['nombre_producto']);
            $lineasHTML .= "<div class='item'><span class='item-desc'>$nombre</span><span class='item-price'>" . $fmt2($l['total_linea']) . "</span></div>";
            $lineasHTML .= "<div class='item-detail'>Ref: " . htmlspecialchars($l['codigo_producto']) . " · " . (int)$l['cantidad'] . " ud x " . $fmt2($l['precio_unitario']) . "</div>";
            if (!empty($l['numeros_serie'])) {
                $lineasHTML .= "<div class='item-detail' style='margin-bottom:4px;'>S/N: " . htmlspecialchars($l['numeros_serie']) . "</div>";
            }
        }

        $reemplazos = [
            '{{NUMERO_TICKET}}' => $numeroStr,
            '{{FECHA}}' => $fechaStr,
            '{{OPERADOR}}' => htmlspecialchars($nombreCajero),
            '{{LINEAS}}' => $lineasHTML,
            '{{SUBTOTAL}}' => $fmt2($venta['subtotal']),
            '{{DESCUENTO_PCT}}' => (float)$venta['descuento_pct'],
            '{{DESCUENTO_AMT}}' => $fmt2($venta['descuento_amt']),
            '{{IVA_AMT}}' => $fmt2($venta['iva_amt']),
            '{{TOTAL}}' => $fmt2($venta['total']),
            '{{METODO_PAGO}}' => ucfirst($venta['metodo_pago']),
            '{{EFECTIVO_RECIBIDO}}' => $fmt2($venta['efectivo_recibido'] ?? 0),
            '{{EFECTIVO_CAMBIO}}' => $fmt2(max(0, ($venta['efectivo_recibido'] ?? 0) - $venta['total'])),
            '{{BASE_IMPONIBLE}}' => $fmt2($venta['base_imponible']),
            '{{DISPLAY_DESCUENTO}}' => (float)$venta['descuento_amt'] > 0 ? '' : 'display:none;',
            '{{DISPLAY_EFECTIVO}}' => ($venta['metodo_pago'] === 'efectivo') ? '' : 'display:none;',
            '{{PAGO_DETALLE}}' => ($venta['metodo_pago'] === 'financiado' && !empty($venta['financiacion']))
                ? "FINANCIACIÓN: " . $venta['financiacion']['nombre_financiera'] . " (" . $venta['financiacion']['meses'] . " cuotas de " . $fmt2($venta['financiacion']['cuota_mensual']) . ")"
                : "",
            '{{EMPRESA_NOMBRE}}' => htmlspecialchars($appConfig['empresa_nombre'] ?? ''),
            '{{EMPRESA_RAZON_SOCIAL}}' => htmlspecialchars($appConfig['empresa_razon_social'] ?? ''),
            '{{EMPRESA_NIF}}' => htmlspecialchars($appConfig['empresa_nif'] ?? ''),
            '{{EMPRESA_DIRECCION}}' => htmlspecialchars($appConfig['empresa_direccion'] ?? ''),
            '{{EMPRESA_TELEFONO}}' => htmlspecialchars($appConfig['empresa_telefono'] ?? ''),
            '{{EMPRESA_EMAIL}}' => htmlspecialchars($appConfig['empresa_email'] ?? ''),
            '{{EMPRESA_WEB}}' => htmlspecialchars($appConfig['empresa_web'] ?? ''),
            '{{EMPRESA_REGISTRO}}' => htmlspecialchars($appConfig['empresa_registro'] ?? ''),
            '{{TICKET_PIE_PAGINA}}' => htmlspecialchars($appConfig['ticket_pie_pagina'] ?? ''),
            '{{TICKET_POLITICA}}' => htmlspecialchars($appConfig['ticket_politica'] ?? '')
        ];
    }

    foreach ($reemplazos as $key => $val) {
        $html = str_replace($key, $val, $html);
    }

    // Inyectar script de auto-impresión
    $html .= "<script>window.onload = () => { window.print(); setTimeout(() => { window.close(); }, 500); }</script>";

    header('Content-Type: text/html');
    echo $html;
} catch (Exception $e) {
    die($e->getMessage());
}
