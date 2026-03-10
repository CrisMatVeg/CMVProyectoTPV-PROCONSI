<?php
// api/imprimirTicket.php
// Genera el HTML de un ticket o factura basándose en las plantillas y abre el diálogo de impresión.

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: text/html; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/VentaPDO.php';
    require_once __DIR__ . '/../model/ConfiguracionPDO.php';

    session_start();
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo "No autorizado";
        exit;
    }

    $numTicket = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($numTicket <= 0) {
        throw new Exception('Número de ticket faltante');
    }

    $venta = VentaPDO::obtenerVentaPorTicket($numTicket);
    if (!$venta) {
        throw new Exception('Venta no encontrada');
    }

    $appConfig = ConfiguracionPDO::obtenerConfiguracion();

    $esFactura = ($venta['tipo_cliente'] === 'empresa' || (isset($venta['es_factura']) && $venta['es_factura'] == 1));

    if ($esFactura) {
        $templatePath = __DIR__ . '/../factura-electrobazar.html';
    } else {
        $templatePath = __DIR__ . '/../ticket-electrobazar.html';
    }

    if (!file_exists($templatePath)) {
        throw new Exception("Plantilla no encontrada: $templatePath");
    }

    $html = file_get_contents($templatePath);

    // Formatear números
    $fmt = function ($num) {
        return number_format((float)$num, 2, ',', '.') . ' €';
    };
    $fmtSimple = function ($num) {
        return number_format((float)$num, 2, ',', '.');
    };

    $operador = $venta['nombre_cajero'] ?? '—';
    if ($venta['estado'] === 'devuelta') {
        // Añadir aviso de anulado
        if ($esFactura) {
            $html = str_replace('<div class="factura-title">FACTURA</div>', '<div class="factura-title">FACTURA</div><div style="color:red; font-weight:bold; font-size:18px;">ANULADA</div>', $html);
        } else {
            $html = str_replace('<div class="logo">ElectroBazar</div>', '<div class="logo">ElectroBazar</div><div style="color:red; font-weight:bold; font-size:14px; margin:4px 0; border:1px dashed red;">TICKET ANULADO</div>', $html);
        }
    }

    // Datos empresa
    $html = str_replace('{{EMPRESA_NOMBRE}}', htmlspecialchars($appConfig['empresa_nombre'] ?? 'ElectroBazar'), $html);
    $html = str_replace('{{EMPRESA_RAZON_SOCIAL}}', htmlspecialchars($appConfig['empresa_razon_social'] ?? $appConfig['empresa_nombre'] ?? 'ElectroBazar S.L.'), $html);
    $html = str_replace('{{EMPRESA_DIRECCION}}', htmlspecialchars($appConfig['empresa_direccion'] ?? ''), $html);
    $html = str_replace('{{EMPRESA_NIF}}', htmlspecialchars($appConfig['empresa_nif'] ?? ''), $html);
    $html = str_replace('{{EMPRESA_TELEFONO}}', htmlspecialchars($appConfig['empresa_telefono'] ?? ''), $html);
    $html = str_replace('{{EMPRESA_EMAIL}}', htmlspecialchars($appConfig['empresa_email'] ?? ''), $html);
    $html = str_replace('{{EMPRESA_WEB}}', htmlspecialchars($appConfig['empresa_web'] ?? ''), $html);
    $html = str_replace('{{EMPRESA_REGISTRO}}', htmlspecialchars($appConfig['empresa_registro_mercantil'] ?? ''), $html);
    $html = str_replace('{{TICKET_POLITICA}}', htmlspecialchars($appConfig['ticket_politica'] ?? ''), $html);
    $html = str_replace('{{TICKET_PIE_PAGINA}}', htmlspecialchars($appConfig['ticket_pie_pagina'] ?? 'Gracias por su compra'), $html);

    // Datos comunes
    $html = str_replace('{{NUMERO_TICKET}}', str_pad($venta['numero_ticket'], 4, '0', STR_PAD_LEFT), $html);
    $html = str_replace('{{FACTURA_NUM}}', str_pad($venta['numero_ticket'], 4, '0', STR_PAD_LEFT), $html);
    $html = str_replace('{{FECHA}}', date('d/m/Y H:i', strtotime($venta['fecha'])), $html);
    $html = str_replace('{{FECHA_EMISION}}', date('d/m/Y', strtotime($venta['fecha'])), $html);
    $html = str_replace('{{FECHA_VENCIMIENTO}}', date('d/m/Y', strtotime($venta['fecha'])), $html);
    $html = str_replace('{{OPERADOR}}', htmlspecialchars($operador), $html);
    $html = str_replace('{{METODO_PAGO}}', ucfirst($venta['metodo_pago']), $html);

    // Totales comunes
    $html = str_replace('{{SUBTOTAL}}', $fmt($venta['subtotal']), $html);
    $html = str_replace('{{BASE_IMPONIBLE}}', $fmt($venta['base_imponible']), $html);
    $html = str_replace('{{IVA_AMT}}', $fmt($venta['iva_amt']), $html);
    $html = str_replace('{{TOTAL}}', $fmt($venta['total']), $html);

    // Descuentos
    $descAmt = (float)($venta['descuento_amt'] ?? 0);
    $descPct = (float)($venta['descuento_pct'] ?? 0);
    if ($descAmt > 0) {
        $html = str_replace('{{DISPLAY_DESCUENTO}}', '', $html);
        $html = str_replace('{{DESCUENTO_AMT}}', $fmt($descAmt), $html);
        $html = str_replace('{{DESCUENTO_PCT}}', $descPct > 0 ? $descPct : 'Global', $html);
    } else {
        $html = str_replace('{{DISPLAY_DESCUENTO}}', 'display: none;', $html);
        $html = str_replace('{{DESCUENTO_AMT}}', '0,00 €', $html);
        $html = str_replace('{{DESCUENTO_PCT}}', '0', $html);
    }

    // Pagos
    if ($venta['metodo_pago'] === 'efectivo') {
        $html = str_replace('{{DISPLAY_EFECTIVO}}', '', $html);
        $html = str_replace('{{EFECTIVO_RECIBIDO}}', $fmt($venta['efectivo_recibido']), $html);
        $html = str_replace('{{EFECTIVO_CAMBIO}}', $fmt(max(0, $venta['efectivo_recibido'] - $venta['total'])), $html);
    } else {
        $html = str_replace('{{DISPLAY_EFECTIVO}}', 'display: none;', $html);
    }

    $detallesPago = '';
    if ($venta['metodo_pago'] === 'financiado') {
        $detallesPago = 'Financiera: ' . ($venta['nombre_financiera'] ?? '') . ' | Plazo: ' . ($venta['meses'] ?? '') . ' meses';
    } else if (!empty($venta['pagos']) && $venta['pagado_a_cuenta'] > 0) {
        $detallesPago = 'Abonado a cuenta: ' . $fmt($venta['pagado_a_cuenta']) . '<br>Pendiente: ' . $fmt(max(0, $venta['total'] - $venta['pagado_a_cuenta']));
    }
    $html = str_replace('{{PAGO_DETALLE}}', $detallesPago, $html);

    // Datos cliente factura
    if ($esFactura) {
        $html = str_replace('{{CLIENTE_NOMBRE}}', htmlspecialchars($venta['nombre_cliente'] ?? '—'), $html);
        $html = str_replace('{{CLIENTE_CIF}}', htmlspecialchars($venta['nif_cliente'] ?? '—'), $html);
        // Fallbacks since VentaPDO doesn't fetch all these directly unless joined
        $html = str_replace('{{CLIENTE_DIRECCION}}', 'Dirección del cliente', $html);
        $html = str_replace('{{CLIENTE_POBLACION}}', 'Población del cliente', $html);
        $html = str_replace('{{CLIENTE_EMAIL}}', htmlspecialchars($venta['email_cliente'] ?? ''), $html);
    }

    // Líneas
    $htmlLineas = '';
    foreach ($venta['lineas'] as $l) {
        $nombre = $l['nombre_producto'] ?: $l['codigo_producto'] ?: ('ID ' . $l['id_producto']);
        if (!empty($l['devuelta'])) {
            $nombre = "<span style='text-decoration:line-through'>$nombre</span> <span style='color:red; font-size: 80%; font-weight:bold;'>[DEVUELTO]</span>";
        }
        if (!empty($l['codigo_producto'])) {
            $nombre .= "<br><small style='color:#666;'>Cód: " . htmlspecialchars($l['codigo_producto']) . "</small>";
        }
        if (!empty($l['numeros_serie'])) {
            $nombre .= "<br><small style='color:#666;'>S/N: " . htmlspecialchars($l['numeros_serie']) . "</small>";
        }

        if ($esFactura) {
            $htmlLineas .= "<tr>
              <td>$nombre</td>
              <td style='text-align: center'>" . (int)$l['cantidad'] . "</td>
              <td style='text-align: right'>" . $fmtSimple($l['precio_unitario']) . "</td>
              <td style='text-align: right'>0,00</td>
              <td style='text-align: right'>" . $fmtSimple($l['total_linea']) . "</td>
            </tr>";
        } else {
            $htmlLineas .= "<div class='item'>
              <span class='item-desc'>$nombre</span>
              <span class='item-price'>" . (int)$l['cantidad'] . "x " . $fmtSimple($l['precio_unitario']) . " = " . $fmtSimple($l['total_linea']) . "</span>
            </div>";
        }
    }

    if (!$esFactura && empty($htmlLineas)) {
        // Fallback for empty lines
        $htmlLineas = "<div class='item'>- Sin productos -</div>";
    }

    if ($esFactura) {
        $html = str_replace('{{LINEAS}}', $htmlLineas, $html);
    } else {
        $html = str_replace('<div id="items-container">{{LINEAS}}</div>', '<div id="items-container">' . $htmlLineas . '</div>', $html);
    }

    // Añadir el script de auto impresión
    $script = "<script>window.onload = function() { window.print(); }; window.onafterprint = function() { window.close(); };</script>";
    $html = str_replace('</body>', $script . '</body>', $html);

    echo $html;
} catch (Throwable $e) {
    echo "<h2>Error al generar impresión</h2><p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
