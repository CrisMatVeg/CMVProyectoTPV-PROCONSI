<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: imprimirTicketPro.php
 * Renderiza el ticket o factura en HTML profesional para impresión desde el navegador.
 */

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: text/html; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/VentaPDO.php';
    require_once __DIR__ . '/../model/ConfiguracionPDO.php';
    require_once __DIR__ . '/../model/VeriFactuQrService.php';

    // session_start(); // Handled by csrf_check.php
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        die('No autorizado');
    }

    $numTicket = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($numTicket <= 0) throw new Exception('Número de ticket inválido');

    $venta = VentaPDO::obtenerVentaPorTicket($numTicket);
    if (!$venta) throw new Exception('Venta no encontrada');

    $appConfig = ConfiguracionPDO::obtenerConfiguracion();
    $numeroStr = (string)$venta['numero_ticket'];
    $fechaStr = date("d/m/Y H:i", strtotime($venta['fecha']));

    $esAbono = ($venta['tipo_documento'] ?? 'venta') === 'abono';
    $esFactura = !$esAbono && ($venta['tipo_cliente'] === 'empresa' || (!empty($venta['es_factura'])) || (isset($_GET['modo']) && $_GET['modo'] === 'factura'));

    // [VERIFACTU] Generación de QR y URL de Verificación
    $nifEmisor = $appConfig['empresa_nif'] ?? '';
    
    // Intentar recuperar la serie original enviada a la AEAT para evitar desincronización si cambia el formato
    $stmtSerie = DBPDO::ejecutarConsulta("SELECT numero_serie FROM verifactu_logs WHERE id_venta = ? LIMIT 1", [$venta['id']]);
    $serieGuardada = $stmtSerie->fetchColumn();
    
    if ($serieGuardada) {
        $numFormated = $serieGuardada;
    } else {
        $numFormated = VentaPDO::formatTicketNumber($venta['numero_ticket'], $venta['fecha'], $venta['es_factura'] ?? ($esAbono ? false : $esFactura), ($venta['tipo_documento'] ?? 'venta'));
    }
    
    $venta['numero_ticket_formato'] = $numFormated;
    
    $qrUrl = VeriFactuQrService::generarUrlAEAT($venta, $nifEmisor);
    $qrBase64 = VeriFactuQrService::generarQrBase64($qrUrl);
    $verifactuLabel = ($appConfig['verifactu_remision_voluntaria'] ?? '0') === '1' ? 'VERI*FACTU' : 'No Veri*Factu';

    // Para abonos, usar la plantilla correcta según si la venta original era factura
    if ($esAbono) {
        $templatePath = ($venta['es_factura'] ?? 0) ? __DIR__ . '/../factura-electrobazar.html' : __DIR__ . '/../ticket-electrobazar.html';
    } else {
        $templatePath = $esFactura ? __DIR__ . '/../factura-electrobazar.html' : __DIR__ . '/../ticket-electrobazar.html';
    }
    if (!file_exists($templatePath)) throw new Exception("Plantilla no encontrada");

    $html = file_get_contents($templatePath);
    $fmt2 = fn($n) => number_format((float)$n, 2, ',', '.') . ' €';

    $subtotalReal = array_sum(array_map(fn($l) => (float)$l['total_linea'], $venta['lineas']));
    $totalVenta   = (float)$venta['total'];
    $diff         = $subtotalReal - $totalVenta;

    $ivaGruposPro = [];
    foreach ($venta['lineas'] as $l) {
        if (!empty($l['devuelta'])) continue;
        $rate = (float)($l['iva_aplicado'] ?? 21);
        $totalLinea = (float)$l['total_linea'];
        $base = $totalLinea / (1 + $rate / 100); $tax = $totalLinea - $base;
        if (!isset($ivaGruposPro[$rate])) $ivaGruposPro[$rate] = ['base' => 0, 'tax' => 0];
        $ivaGruposPro[$rate]['base'] += $base; $ivaGruposPro[$rate]['tax'] += $tax;
    }
    ksort($ivaGruposPro);

    $ivaDesgloseTicketHtml = ''; $ivaDesgloseFacturaHtml = '';
    foreach ($ivaGruposPro as $rate => $data) {
        $ivaDesgloseTicketHtml .= "<div class=\"total-row small\"><span>Base imp. ({$rate}%):</span><span>" . $fmt2($data['base']) . "</span></div><div class=\"total-row small\"><span>IVA {$rate}%:</span><span>" . $fmt2($data['tax']) . "</span></div>";
        $ivaDesgloseFacturaHtml .= "<tr><td colspan=\"4\" style=\"text-align: right\">Base imponible ({$rate}%):</td><td>" . $fmt2($data['base']) . "</td></tr><tr><td colspan=\"4\" style=\"text-align: right\">IVA ({$rate}%):</td><td>" . $fmt2($data['tax']) . "</td></tr>";
    }

    // Pagos efectivo y Mixto
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

    $efectivoCambio = 0;
    if ($tieneEfectivo) {
        if ($venta['metodo_pago'] === 'mixto') {
             $efectivoCambio = max(0, (float)$venta['efectivo_recibido'] - $importeEfectivoMixto);
        } else {
             $efectivoCambio = max(0, (float)$venta['efectivo_recibido'] - $totalVenta);
        }
    }

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
            $detallesHtml[] = "$label: " . $fmt2($pago['importe']);
        }
        $detallesPago = implode('<br>', $detallesHtml);
        if ($venta['estado'] === 'pendiente_pago') {
            $pendiente = max(0, $totalVenta - $totalPagos);
            $detallesPago .= '<br><b>Pendiente: ' . $fmt2($pendiente) . '</b>';
        }
    } elseif ($venta['estado'] === 'pendiente_pago' && $venta['metodo_pago'] === 'a_cuenta') {
        $pendiente = max(0, $totalVenta - (float)($venta['pagado_a_cuenta'] ?? 0));
        $detallesPago .= '<b>Pendiente: ' . $fmt2($pendiente) . '</b>';
    }

    $valesAmt = 0;
    if (!empty($venta['pagos'])) {
        foreach ($venta['pagos'] as $pago) {
            if ($pago['metodo_pago'] === 'vale') $valesAmt += (float)$pago['importe'];
        }
    }

    $comentariosHtml = '';
    if (!empty($venta['comentarios'])) {
        $comentariosHtml = $esFactura
            ? '<div style="margin-top:20px;padding:10px;border:1px dashed #000;font-style:italic;"><strong>Observaciones:</strong><br>' . nl2br(htmlspecialchars($venta['comentarios'])) . '</div>'
            : '<div class="small" style="margin-top:5px;font-style:italic;border-top:1px dashed #ccc;padding-top:5px;"><strong>Observaciones:</strong> ' . nl2br(htmlspecialchars($venta['comentarios'])) . '</div>';
    }

    if ($esFactura) {
        $lineasHTML = "";
        foreach ($venta['lineas'] as $l) {
            $desc = htmlspecialchars($l['nombre_producto']);
            $detalle = !empty($l['numeros_serie']) ? "SN: " . htmlspecialchars($l['numeros_serie']) : "";
            $lineSpecificDescSum = 0; $lineSpecificDetails = "";
            if (!empty($l['descuentos'])) {
                foreach ($l['descuentos'] as $d) {
                    $nombreDesc = $d['nombre_descuento'] ?: $d['nombre'] ?: ucfirst($d['tipo_descuento']);
                    if (($d['tipo_descuento'] ?? '') === 'cupon') continue;
                    if (!empty($venta['descuento_label']) && $nombreDesc === $venta['descuento_label']) continue;
                    
                    $vDescontado = (float)($d['valor_descontado'] ?? 0); 
                    $signo = ($vDescontado >= 0) ? '+' : '−'; 
                    $lineSpecificDescSum += abs($vDescontado);
                    $lineSpecificDetails .= "<div style='font-size:8px; color:#666; margin-top:2px;'>- $nombreDesc ($signo" . $fmt2(abs($vDescontado)) . ")</div>";
                }
            }
            $lineasHTML .= "<tr><td>$desc" . ($detalle ? "<span class='small'>$detalle</span>" : "") . "$lineSpecificDetails</td><td style='text-align:center;'>" . (int)$l['cantidad'] . "</td><td>" . $fmt2($l['precio_unitario']) . "</td><td>" . ($lineSpecificDescSum > 0 ? $fmt2($lineSpecificDescSum) : "0,00") . "</td><td>" . $fmt2($l['total_linea']) . "</td></tr>";
        }
        // Para abonos: mostrar número A-... y referencia al documento original
        $esTipoAbono = ($venta['tipo_documento'] ?? 'venta') === 'abono';
        $numDocumento = $esTipoAbono ? $numFormated : ('FAC-' . date('Y', strtotime($venta['fecha'])) . '-' . $numeroStr);
        $tipoDocLabel = $esTipoAbono ? ($venta['es_factura'] ? 'FACTURA RECTIFICATIVA' : 'ABONO / DEVOLUCIÓN') : 'FACTURA';
        $refOriginal  = $esTipoAbono && !empty($venta['numero_ticket_origen'])
            ? 'Rectifica: ' . VentaPDO::formatTicketNumber($venta['numero_ticket_origen'], $venta['fecha'], $venta['es_factura'], 'venta')
            : '';
        $reemplazos = [
            '{{FACTURA_NUM}}' => $numDocumento,
            '{{FECHA_EMISION}}' => date("d/m/Y", strtotime($venta['fecha'])),
            '{{FECHA_VENCIMIENTO}}' => date("d/m/Y", strtotime($venta['fecha'] . " + 30 days")),
            '{{METODO_PAGO}}' => ucfirst($venta['metodo_pago']),
            '{{PAGO_DETALLE}}' => $detallesPago,
            '{{CLIENTE_NOMBRE}}' => htmlspecialchars($venta['nombre_cliente'] ?? '—'),
            '{{CLIENTE_CIF}}' => htmlspecialchars($venta['nif_cliente'] ?? '—'),
            '{{LINEAS}}' => $lineasHTML,
            '{{IVA_DESGLOSE}}' => $ivaDesgloseFacturaHtml,
            '{{DESCUENTO_AMT}}' => $fmt2($venta['descuento_amt']),
            '{{TOTAL}}' => $fmt2($totalVenta),
            '{{DISPLAY_DESCUENTO}}' => (float)$venta['descuento_amt'] > 0 ? '' : 'display:none;',
            '{{DISPLAY_EFECTIVO}}' => $tieneEfectivo ? '' : 'display:none;',
            '{{EFECTIVO_RECIBIDO}}' => $fmt2($venta['efectivo_recibido'] ?? 0),
            '{{EFECTIVO_CAMBIO}}' => $fmt2($efectivoCambio),
            '{{EMPRESA_NOMBRE}}' => htmlspecialchars($appConfig['empresa_nombre'] ?? ''),
            '{{EMPRESA_RAZON_SOCIAL}}' => htmlspecialchars($appConfig['empresa_razon_social'] ?? ''),
            '{{EMPRESA_NIF}}' => htmlspecialchars($appConfig['empresa_nif'] ?? ''),
            '{{EMPRESA_DIRECCION}}' => htmlspecialchars($appConfig['empresa_direccion'] ?? ''),
            '{{EMPRESA_TELEFONO}}' => htmlspecialchars($appConfig['empresa_telefono'] ?? ''),
            '{{EMPRESA_EMAIL}}' => htmlspecialchars($appConfig['empresa_email'] ?? ''),
            '{{EMPRESA_WEB}}' => htmlspecialchars($appConfig['empresa_web'] ?? ''),
            '{{EMPRESA_REGISTRO}}' => htmlspecialchars($appConfig['empresa_registro'] ?? ''),
            '{{TICKET_PIE_PAGINA}}' => htmlspecialchars($appConfig['ticket_pie_pagina'] ?? ''),
            '{{TICKET_POLITICA}}' => htmlspecialchars($appConfig['ticket_politica'] ?? ''),
            '{{QR_CODE_IMAGE}}' => $qrBase64,
            '{{VERIFACTU_TEXT}}' => $verifactuLabel,
            '{{TIPO_DOC_LABEL}}' => $tipoDocLabel,
            '{{REF_ORIGINAL}}' => $refOriginal,
            '{{DISPLAY_VALES}}' => $valesAmt > 0 ? '' : 'display:none;',
            '{{VALES_AMT}}' => $fmt2($valesAmt),
            '{{COMENTARIOS}}' => $comentariosHtml,
            '{{CLIENTE_DIRECCION}}' => htmlspecialchars($venta['direccion_cliente'] ?? ''),
            '{{CLIENTE_POBLACION}}' => htmlspecialchars($venta['poblacion_cliente'] ?? ''),
            '{{CLIENTE_EMAIL}}' => htmlspecialchars($venta['email_cliente'] ?? ''),
        ];
    } else {
        $lineasHTML = "";
        foreach ($venta['lineas'] as $l) {
            $nombre = htmlspecialchars($l['nombre_producto']); 
            $lineSpecificDetails = "";
            if (!empty($l['descuentos'])) {
                foreach ($l['descuentos'] as $d) {
                    $nombreDesc = $d['nombre_descuento'] ?: $d['nombre'] ?: ucfirst($d['tipo_descuento']);
                    if (($d['tipo_descuento'] ?? '') === 'cupon') continue;
                    if (!empty($venta['descuento_label']) && $nombreDesc === $venta['descuento_label']) continue;
                    
                    $vDescontado = (float)($d['valor_descontado'] ?? 0); 
                    $signo = ($vDescontado >= 0) ? '+' : '−';
                    $lineSpecificDetails .= "<div style='font-size:9px; color:#666; margin-left:14px;'>└─ $nombreDesc ($signo" . $fmt2(abs($vDescontado)) . ")</div>";
                }
            }
            $lineasHTML .= "<div class='item'><span class='item-desc'>$nombre$lineSpecificDetails</span><span class='item-price'>" . $fmt2($l['total_linea']) . "</span></div>";
            $lineasHTML .= "<div class='item-detail'>Ref: " . htmlspecialchars($l['codigo_producto']) . " · " . (int)$l['cantidad'] . " ud x " . $fmt2($l['precio_unitario']) . "</div>";
            if (!empty($l['numeros_serie'])) $lineasHTML .= "<div class='item-detail' style='margin-bottom:4px;'>S/N: " . htmlspecialchars($l['numeros_serie']) . "</div>";
        }
        // Para abonos en ticket: usar serie A-...
        $esTipoAbono = ($venta['tipo_documento'] ?? 'venta') === 'abono';
        $refOriginalTicket = $esTipoAbono && !empty($venta['numero_ticket_origen'])
            ? 'ABONO s/ ref: ' . VentaPDO::formatTicketNumber($venta['numero_ticket_origen'], $venta['fecha'], false, 'venta')
            : '';
        
        $descLabelText = !empty($venta['descuento_label']) ? $venta['descuento_label'] : ((float)$venta['descuento_pct'] > 0 ? $venta['descuento_pct'] . '%' : 'Descuento');

        $reemplazos = [
            '{{NUMERO_TICKET}}' => $numFormated,
            '{{FECHA}}' => $fechaStr,
            '{{OPERADOR}}' => htmlspecialchars($venta['nombre_cajero'] ?? 'Sistema'),
            '{{LINEAS}}' => $lineasHTML,
            '{{SUBTOTAL}}' => $fmt2($subtotalReal),
            '{{DESCUENTO_PCT}}' => $descLabelText,
            '{{DESCUENTO_AMT}}' => '− ' . $fmt2($diff),
            '{{IVA_DESGLOSE}}' => $ivaDesgloseTicketHtml,
            '{{IVA_DESGLOSE_BOTTOM}}' => $ivaDesgloseTicketHtml,
            '{{TOTAL}}' => $fmt2($totalVenta),
            '{{METODO_PAGO}}' => ucfirst($venta['metodo_pago']),
            '{{PAGO_DETALLE}}' => $detallesPago,
            '{{EFECTIVO_RECIBIDO}}' => $fmt2($venta['efectivo_recibido'] ?? 0),
            '{{EFECTIVO_CAMBIO}}' => $fmt2($efectivoCambio),
            '{{DISPLAY_DESCUENTO}}' => $diff > 0.01 ? '' : 'display:none;',
            '{{DISPLAY_EFECTIVO}}' => $tieneEfectivo ? '' : 'display:none;',
            '{{EMPRESA_NOMBRE}}' => htmlspecialchars($appConfig['empresa_nombre'] ?? ''),
            '{{EMPRESA_RAZON_SOCIAL}}' => htmlspecialchars($appConfig['empresa_razon_social'] ?? ''),
            '{{EMPRESA_NIF}}' => htmlspecialchars($appConfig['empresa_nif'] ?? ''),
            '{{EMPRESA_DIRECCION}}' => htmlspecialchars($appConfig['empresa_direccion'] ?? ''),
            '{{EMPRESA_TELEFONO}}' => htmlspecialchars($appConfig['empresa_telefono'] ?? ''),
            '{{EMPRESA_EMAIL}}' => htmlspecialchars($appConfig['empresa_email'] ?? ''),
            '{{EMPRESA_WEB}}' => htmlspecialchars($appConfig['empresa_web'] ?? ''),
            '{{EMPRESA_REGISTRO}}' => htmlspecialchars($appConfig['empresa_registro'] ?? ''),
            '{{TICKET_PIE_PAGINA}}' => htmlspecialchars($appConfig['ticket_pie_pagina'] ?? ''),
            '{{TICKET_POLITICA}}' => htmlspecialchars($appConfig['ticket_politica'] ?? ''),
            '{{QR_CODE_IMAGE}}' => $qrBase64,
            '{{VERIFACTU_TEXT}}' => $verifactuLabel,
            '{{TIPO_DOC_LABEL}}' => $esTipoAbono ? 'ABONO / DEVOLUCIÓN' : '',
            '{{REF_ORIGINAL}}' => $refOriginalTicket,
            '{{DISPLAY_VALES}}' => $valesAmt > 0 ? '' : 'display:none;',
            '{{VALES_AMT}}' => $fmt2($valesAmt),
            '{{COMENTARIOS}}' => $comentariosHtml,
        ];
    }
    foreach ($reemplazos as $key => $val) { $html = str_replace($key, $val, $html); }
    $html .= "<script>window.onload = () => { window.print(); setTimeout(() => { window.close(); }, 500); }</script>";
    echo $html;
} catch (Throwable $e) { die($e->getMessage()); }