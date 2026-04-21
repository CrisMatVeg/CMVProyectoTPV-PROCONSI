<?php
require_once __DIR__ . '/csrf_check.php';

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: text/html; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/VentaPDO.php';
    require_once __DIR__ . '/../model/ConfiguracionPDO.php';
    require_once __DIR__ . '/../model/VeriFactuQrService.php';

    // session_start(); // Handled by csrf_check.php
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo "No autorizado";
        exit;
    }

    $numTicket = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($numTicket <= 0) throw new Exception('Número de ticket faltante');

    $venta = VentaPDO::obtenerVentaPorTicket($numTicket);
    if (!$venta) throw new Exception('Venta no encontrada');

    $appConfig = ConfiguracionPDO::obtenerConfiguracion();

    // [VERIFACTU] Preparación de datos fiscales para QR
    $nifEmisor = $appConfig['empresa_nif'] ?? '';
    $esFactura = ($venta['tipo_cliente'] === 'empresa' || (isset($venta['es_factura']) && $venta['es_factura'] == 1));
    $numFormated = VentaPDO::formatTicketNumber($venta['numero_ticket'], $venta['fecha'], $esFactura, ($venta['tipo_documento'] ?? 'venta'));
    $venta['numero_ticket_formato'] = $numFormated;
    
    $qrUrl = VeriFactuQrService::generarUrlAEAT($venta, $nifEmisor);
    $qrBase64 = VeriFactuQrService::generarQrBase64($qrUrl);
    $verifactuLabel = ($appConfig['verifactu_remision_voluntaria'] ?? '0') === '1' ? 'VERI*FACTU' : 'No Veri*Factu';

    $templatePath = $esFactura
        ? __DIR__ . '/../factura-electrobazar.html'
        : __DIR__ . '/../ticket-electrobazar.html';

    if (!file_exists($templatePath)) throw new Exception("Plantilla no encontrada: $templatePath");

    $html = file_get_contents($templatePath);

    $fmt       = fn($num) => number_format((float)$num, 2, ',', '.') . ' €';
    $fmtSimple = fn($num) => number_format((float)$num, 2, ',', '.');

    $operador = $venta['nombre_cajero'] ?? '—';

    if ($venta['estado'] === 'devuelta') {
        if ($esFactura) {
            $html = str_replace('<div class="factura-title">FACTURA</div>', '<div class="factura-title">FACTURA</div><div style="color:red; font-weight:bold; font-size:18px;">ANULADA</div>', $html);
        } else {
            $html = str_replace('<div class="logo">ElectroBazar</div>', '<div class="logo">ElectroBazar</div><div style="color:red; font-weight:bold; font-size:14px; margin:4px 0; border:1px dashed red;">TICKET ANULADO</div>', $html);
        }
    }

    // Datos empresa
    $html = str_replace('{{EMPRESA_NOMBRE}}',        htmlspecialchars($appConfig['empresa_nombre'] ?? 'ElectroBazar'), $html);
    $html = str_replace('{{EMPRESA_RAZON_SOCIAL}}',  htmlspecialchars($appConfig['empresa_razon_social'] ?? $appConfig['empresa_nombre'] ?? 'ElectroBazar S.L.'), $html);
    $html = str_replace('{{EMPRESA_DIRECCION}}',     htmlspecialchars($appConfig['empresa_direccion'] ?? ''), $html);
    $html = str_replace('{{EMPRESA_NIF}}',           htmlspecialchars($appConfig['empresa_nif'] ?? ''), $html);
    $html = str_replace('{{EMPRESA_TELEFONO}}',      htmlspecialchars($appConfig['empresa_telefono'] ?? ''), $html);
    $html = str_replace('{{EMPRESA_EMAIL}}',         htmlspecialchars($appConfig['empresa_email'] ?? ''), $html);
    $html = str_replace('{{EMPRESA_WEB}}',           htmlspecialchars($appConfig['empresa_web'] ?? ''), $html);
    $html = str_replace('{{EMPRESA_REGISTRO}}',      htmlspecialchars($appConfig['empresa_registro_mercantil'] ?? ''), $html);
    $html = str_replace('{{TICKET_POLITICA}}',       htmlspecialchars($appConfig['ticket_politica'] ?? ''), $html);
    $html = str_replace('{{TICKET_PIE_PAGINA}}',     htmlspecialchars($appConfig['ticket_pie_pagina'] ?? 'Gracias por su compra'), $html);

    // [VERIFACTU] Reemplazos visuales
    $html = str_replace('{{QR_CODE_IMAGE}}', $qrBase64, $html);
    $html = str_replace('{{VERIFACTU_TEXT}}', $verifactuLabel, $html);

    // Datos comunes
    $formattedNum = VentaPDO::formatTicketNumber($venta['numero_ticket'], $venta['fecha'], $esFactura);
    $html = str_replace('{{NUMERO_TICKET}}',     $formattedNum, $html);
    $html = str_replace('{{FACTURA_NUM}}',       $formattedNum, $html);
    $html = str_replace('{{FECHA}}',             date('d/m/Y H:i', strtotime($venta['fecha'])), $html);
    $html = str_replace('{{FECHA_EMISION}}',     date('d/m/Y', strtotime($venta['fecha'])), $html);
    $html = str_replace('{{FECHA_VENCIMIENTO}}', date('d/m/Y', strtotime($venta['fecha'])), $html);
    $html = str_replace('{{OPERADOR}}',          htmlspecialchars($operador), $html);
    $html = str_replace('{{METODO_PAGO}}',       ucfirst($venta['metodo_pago']), $html);

    // Total y desglose IVA calculados desde líneas
    $totalReal = array_sum(array_map(fn($l) => (float)$l['total_linea'], $venta['lineas']));

    $html = str_replace('{{SUBTOTAL}}', $fmt($totalReal), $html);
    $html = str_replace('{{TOTAL}}',    $fmt($totalReal), $html);

    $ivaGrupos = [];
    foreach ($venta['lineas'] as $l) {
        if (!empty($l['devuelta'])) continue;
        $rate       = (float)($l['iva_aplicado'] ?? 21);
        $totalLinea = (float)$l['total_linea'];
        $base       = $totalLinea / (1 + $rate / 100);
        $tax        = $totalLinea - $base;
        if (!isset($ivaGrupos[$rate])) $ivaGrupos[$rate] = ['base' => 0, 'tax' => 0];
        $ivaGrupos[$rate]['base'] += $base;
        $ivaGrupos[$rate]['tax']  += $tax;
    }
    ksort($ivaGrupos);

    $ivaDesgloseHtml       = '';
    $ivaDesgloseBottomHtml = '';
    foreach ($ivaGrupos as $rate => $data) {
        $ivaDesgloseHtml .= "<div class=\"total-row small\">"
            . "<span>Base imp. ({$rate}%):</span><span>" . $fmt($data['base']) . "</span>"
            . "</div>"
            . "<div class=\"total-row small\">"
            . "<span>IVA {$rate}%:</span><span>" . $fmt($data['tax']) . "</span>"
            . "</div>";

        $ivaDesgloseBottomHtml .= "<div class=\"total-row\">"
            . "<span>Base imp. ({$rate}%):</span><span>" . $fmt($data['base']) . "</span>"
            . "</div>"
            . "<div class=\"total-row\">"
            . "<span>Cuota IVA {$rate}%:</span><span>" . $fmt($data['tax']) . "</span>"
            . "</div>";
    }
    $html = str_replace('{{IVA_DESGLOSE}}',        $ivaDesgloseHtml, $html);
    $html = str_replace('{{IVA_DESGLOSE_BOTTOM}}', $ivaDesgloseBottomHtml, $html);
    $html = str_replace('{{BASE_IMPONIBLE}}',      $fmt($venta['base_imponible']), $html);
    $html = str_replace('{{IVA_AMT}}',             $fmt($venta['iva_amt']), $html);

    // Descuentos
    $descAmt = (float)($venta['descuento_amt'] ?? 0);
    $descPct = (float)($venta['descuento_pct'] ?? 0);
    if ($descAmt > 0) {
        $html = str_replace('{{DISPLAY_DESCUENTO}}', '', $html);
        $html = str_replace('{{DESCUENTO_AMT}}',     $fmt($descAmt), $html);
        $html = str_replace('{{DESCUENTO_PCT}}',     $descPct > 0 ? $descPct : 'Global', $html);
    } else {
        $html = str_replace('{{DISPLAY_DESCUENTO}}', 'display: none;', $html);
        $html = str_replace('{{DESCUENTO_AMT}}',     '0,00 €', $html);
        $html = str_replace('{{DESCUENTO_PCT}}',     '0', $html);
    }

    // Vales
    $valesAmt = 0;
    if (!empty($venta['pagos'])) {
        foreach ($venta['pagos'] as $pago) {
            if ($pago['metodo_pago'] === 'vale') $valesAmt += (float)$pago['importe'];
        }
    }
    if ($valesAmt > 0) {
        $html = str_replace('{{DISPLAY_VALES}}', '', $html);
        $html = str_replace('{{VALES_AMT}}',     $fmt($valesAmt), $html);
    } else {
        $html = str_replace('{{DISPLAY_VALES}}', 'display: none;', $html);
        $html = str_replace('{{VALES_AMT}}',     '0,00 €', $html);
    }

    // Pagos efectivo
    $tieneEfectivo = ($venta['metodo_pago'] === 'efectivo');
    $importeEfectivoMixto = 0;
    if ($venta['metodo_pago'] === 'mixto' && !empty($venta['pagos'])) {
        foreach ($venta['pagos'] as $pago) {
            if ($pago['metodo_pago'] === 'efectivo' && (float)$pago['importe'] > 0) {
                $tieneEfectivo = true;
                $importeEfectivoMixto += (float)$pago['importe'];
            }
        }
    }

    if ($tieneEfectivo) {
        $html = str_replace('{{DISPLAY_EFECTIVO}}',  '', $html);
        $html = str_replace('{{EFECTIVO_RECIBIDO}}', $fmt($venta['efectivo_recibido']), $html);
        if ($venta['metodo_pago'] === 'mixto') {
             $cambio = max(0, (float)$venta['efectivo_recibido'] - $importeEfectivoMixto);
        } else {
             $cambio = max(0, (float)$venta['efectivo_recibido'] - $totalReal);
        }
        $html = str_replace('{{EFECTIVO_CAMBIO}}',   $fmt($cambio), $html);
    } else {
        $html = str_replace('{{DISPLAY_EFECTIVO}}',  'display: none;', $html);
        $html = str_replace('{{EFECTIVO_RECIBIDO}}', '', $html);
        $html = str_replace('{{EFECTIVO_CAMBIO}}',   '', $html);
    }

    // Detalle pago — omitir efectivo porque ya se muestra arriba
    $detallesPago = '';
    if (!empty($venta['pagos']) && $venta['metodo_pago'] === 'mixto') {
        $detallesHtml = [];
        $totalPagos = 0;
        foreach ($venta['pagos'] as $pago) {
            if (in_array($pago['metodo_pago'], ['efectivo', 'tarjeta', 'bizum', 'vale'])) {
                 $totalPagos += (float)$pago['importe'];
            }
            if ($pago['metodo_pago'] === 'efectivo') continue;
            $label = ucfirst($pago['metodo_pago']);
            if ($pago['metodo_pago'] === 'vale' && !empty($pago['notas'])) {
                $label = 'Vale (' . $pago['notas'] . ')';
            }
            $detallesHtml[] = "$label: " . $fmt($pago['importe']);
        }
        $detallesPago = implode('<br>', $detallesHtml);
        if ($venta['estado'] === 'pendiente_pago') {
            $pendiente = max(0, $totalReal - $totalPagos);
            $detallesPago .= '<br><b>Pendiente: ' . $fmt($pendiente) . '</b>';
        }
    } elseif ($venta['estado'] === 'pendiente_pago' && $venta['metodo_pago'] === 'a_cuenta') {
        $pendiente = max(0, $totalReal - (float)($venta['pagado_a_cuenta'] ?? 0));
        $detallesPago .= '<b>Pendiente: ' . $fmt($pendiente) . '</b>';
    }
    $html = str_replace('{{PAGO_DETALLE}}', $detallesPago, $html);

    // Comentarios
    $comentariosHtml = '';
    if (!empty($venta['comentarios'])) {
        if ($esFactura) {
            $comentariosHtml = '<div style="margin-top: 20px; padding: 10px; border: 1px dashed #000; font-style: italic;">'
                . '<strong>Observaciones:</strong><br>' . nl2br(htmlspecialchars($venta['comentarios']))
                . '</div>';
        } else {
            $comentariosHtml = '<div class="small" style="margin-top: 5px; font-style: italic; border-top: 1px dashed #ccc; padding-top: 5px;">'
                . '<strong>Observaciones:</strong> ' . nl2br(htmlspecialchars($venta['comentarios']))
                . '</div>';
        }
    }
    $html = str_replace('{{COMENTARIOS}}', $comentariosHtml, $html);

    // Datos cliente factura
    if ($esFactura) {
        $html = str_replace('{{CLIENTE_NOMBRE}}',    htmlspecialchars($venta['nombre_cliente'] ?? '—'), $html);
        $html = str_replace('{{CLIENTE_CIF}}',       htmlspecialchars($venta['nif_cliente'] ?? '—'), $html);
        $html = str_replace('{{CLIENTE_DIRECCION}}', 'Dirección del cliente', $html);
        $html = str_replace('{{CLIENTE_POBLACION}}', 'Población del cliente', $html);
        $html = str_replace('{{CLIENTE_EMAIL}}',     htmlspecialchars($venta['email_cliente'] ?? ''), $html);
    }

    // Líneas
    $htmlLineas = '';
    foreach ($venta['lineas'] as $l) {
        $nombre = $l['nombre_producto'] ?: $l['codigo_producto'] ?: ('ID ' . $l['id_producto']);
        if (!empty($l['devuelta'])) {
            $nombre = "<span style='text-decoration:line-through'>$nombre</span> <span style='color:red; font-size:80%; font-weight:bold;'>[DEVUELTO]</span>";
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
              <td style='text-align:center'>" . (int)$l['cantidad'] . "</td>
              <td style='text-align:right'>" . $fmtSimple($l['precio_unitario']) . "</td>
              <td style='text-align:right'>0,00</td>
              <td style='text-align:right'>" . $fmtSimple($l['total_linea']) . "</td>
            </tr>";
        } else {
            $descLineasHTML = '';
            if (!empty($l['descuentos'])) {
                foreach ($l['descuentos'] as $d) {
                    if (($d['tipo_descuento'] ?? '') === 'tarifa') continue;
                    $nombreDesc  = $d['nombre_descuento'] ?: ucfirst($d['tipo_descuento']);
                    $vDescontado = (float)$d['valor_descontado'];
                    $signo       = ($vDescontado >= 0) ? '+' : '-';
                    $valor       = $fmt(abs($vDescontado));
                    $descLineasHTML .= "<div style='font-size:9px; color:#666; margin-left:10px;'>└─ [$nombreDesc] $signo$valor</div>";
                }
            }
            $htmlLineas .= "<div class='item'>
              <span class='item-desc'>$nombre$descLineasHTML</span>
              <span class='item-price'>" . (int)$l['cantidad'] . "x " . $fmtSimple($l['precio_unitario']) . " = " . $fmtSimple($l['total_linea']) . "</span>
            </div>";
        }
    }

    if (!$esFactura && empty($htmlLineas)) {
        $htmlLineas = "<div class='item'>- Sin productos -</div>";
    }

    if ($esFactura) {
        $html = str_replace('{{LINEAS}}', $htmlLineas, $html);
    } else {
        $html = str_replace('<div id="items-container">{{LINEAS}}</div>', '<div id="items-container">' . $htmlLineas . '</div>', $html);
    }

    $script = "<script>window.onload = function() { window.print(); }; window.onafterprint = function() { window.close(); };</script>";
    $html   = str_replace('</body>', $script . '</body>', $html);

    echo $html;

} catch (Throwable $e) {
    echo "<h2>Error al generar impresión</h2><p>" . htmlspecialchars($e->getMessage()) . "</p>";
}