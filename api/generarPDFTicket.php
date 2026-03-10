<?php

/**
 * API: generarPDFTicket.php
 * Genera PDF del ticket descargable
 * Usa html2pdf si está disponible, sino genera el HTML para impresión
 */
header('Content-Type: application/pdf; charset=utf-8');

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
    if ($numTicket <= 0) {
        throw new Exception('Número de ticket inválido');
    }

    $venta = VentaPDO::obtenerVentaPorTicket($numTicket);
    if (!$venta) {
        throw new Exception('Venta no encontrada');
    }

    require_once __DIR__ . '/../model/ConfiguracionPDO.php';
    $appConfig = ConfiguracionPDO::obtenerConfiguracion();

    // Información de la empresa
    $empresa = [
        'nombre' => $appConfig['empresa_nombre'] ?? 'ElectroBazar',
        'direccion' => $appConfig['empresa_direccion'] ?? 'C/ Tecnología 24, 28001 Madrid',
        'nif' => $appConfig['empresa_nif'] ?? 'B87654321',
        'telefono' => $appConfig['empresa_telefono'] ?? '+34 91 123 45 67',
        'email' => $appConfig['empresa_email'] ?? 'info@electrobazar.es'
    ];

    $numeroStr = str_pad($venta['numero_ticket'], 4, '0', STR_PAD_LEFT);
    $fechaStr = date("d/m/Y H:i", strtotime($venta['fecha']));
    $nombreCajero = $venta['nombre_cajero'] ?? 'Sistema';
    $hasFinanciacion = !empty($venta['meses']) && (int)$venta['meses'] > 0;

    // Generar HTML del ticket pasando $appConfig
    $html = generarHTMLTicketPDF($venta, $empresa, $numeroStr, $fechaStr, $nombreCajero, $hasFinanciacion, $appConfig);

    // Nombre de archivo sugerido para el cliente
    $filename = "Ticket_" . $numeroStr . "_" . date('Y-m-d_His') . ".pdf";

    // Servimos el HTML para que el cliente lo procese
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    exit;
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>Error</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
}

function generarHTMLTicketPDF($venta, $empresa, $numeroStr, $fechaStr, $nombreCajero, $hasFinanciacion, $appConfig = [])
{
    $html = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 20px; font-size: 11px; line-height: 1.4; }
        .container { max-width: 210mm; margin: 0 auto; background: white; padding: 15px; }
        
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 15px; }
        .header h1 { font-size: 18px; margin-bottom: 5px; }
        .header p { font-size: 10px; color: #666; margin: 2px 0; }
        
        .info-section { margin-bottom: 12px; }
        .info-row { display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px dotted #ccc; }
        .info-label { font-weight: bold; color: #333; }
        .info-value { text-align: right; }
        
        .products-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .products-table th { background: #f5f5f5; padding: 6px 4px; text-align: left; font-weight: bold; border-bottom: 1px solid #333; font-size: 10px; }
        .products-table td { padding: 6px 4px; border-bottom: 1px dotted #ddd; }
        .qty-cell { text-align: center; }
        .price-cell { text-align: right; }
        
        .totals { border-top: 2px solid #333; padding-top: 10px; margin-top: 10px; }
        .total-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 11px; }
        .total-row.main { font-weight: bold; font-size: 12px; border-top: 1px solid #333; padding-top: 6px; margin-top: 6px; }
        .total-row.main .value { color: #000; font-size: 13px; }
        
        .financing-section { background: #fffacd; border: 1px solid #daa520; padding: 8px; margin: 10px 0; border-radius: 3px; font-size: 10px; }
        .financing-section h4 { margin-bottom: 4px; color: #8b6914; }
        
        .footer { text-align: center; margin-top: 15px; padding-top: 10px; border-top: 1px dashed #ccc; font-size: 9px; color: #666; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>" . htmlspecialchars($empresa['nombre']) . "</h1>
            <p>" . htmlspecialchars($empresa['direccion']) . "</p>
            <p>NIF: " . htmlspecialchars($empresa['nif']) . " | Tel: " . htmlspecialchars($empresa['telefono']) . "</p>";

    if ($venta['estado'] === 'devuelta') {
        $html .= "<div style='margin-top: 10px; padding: 8px; background: #ffebee; color: #d32f2f; border: 2px dashed #d32f2f; font-weight: bold; font-size: 14px; text-align: center;'>TICKET ANULADO</div>";
    }

    $html .= "</div>
        <div class='info-section'>
            <div class='info-row'>
                <span class='info-label'>" . ($venta['tipo_cliente'] === 'empresa' || (isset($venta['es_factura']) && $venta['es_factura'] == 1) ? 'FACTURA #:' : 'TICKET #:') . "</span>
                <span class='info-value'>" . $numeroStr . "</span>
            </div>
            <div class='info-row'>
                <span class='info-label'>FECHA:</span>
                <span class='info-value'>" . $fechaStr . "</span>
            </div>
            <div class='info-row'>
                <span class='info-label'>CAJERO:</span>
                <span class='info-value'>" . htmlspecialchars($nombreCajero) . "</span>
            </div>
            <div class='info-row'>
                <span class='info-label'>PAGO:</span>
                <span class='info-value'>" . ucfirst(htmlspecialchars($venta['metodo_pago'])) . "</span>
            </div>";

    if (!empty($venta['nombre_cliente'])) {
        $html .= "
            <div class='info-row'>
                <span class='info-label'>CLIENTE:</span>
                <span class='info-value'>" . htmlspecialchars($venta['nombre_cliente']) . "</span>
            </div>";
    }

    if (!empty($venta['nif_cliente'])) {
        $html .= "
            <div class='info-row'>
                <span class='info-label'>NIF/DNI:</span>
                <span class='info-value'>" . htmlspecialchars($venta['nif_cliente']) . "</span>
            </div>";
    }

    $html .= "
        </div>

        <table class='products-table'>
            <thead>
                <tr>
                    <th>DESCRIPCIÓN</th>
                    <th class='qty-cell'>CANT.</th>
                    <th class='price-cell'>PRECIO</th>
                    <th class='price-cell'>TOTAL</th>
                </tr>
            </thead>
            <tbody>";

    foreach ($venta['lineas'] as $linea) {
        $html .= "
                <tr>
                    <td>";
        if (!empty($linea['devuelta'])) {
            $html .= "
                        <strong style='text-decoration: line-through; color: #999;'>" . htmlspecialchars($linea['nombre_producto'] ?? $linea['codigo_producto']) . "</strong>";
        } else {
            $html .= "
                        <strong>" . htmlspecialchars($linea['nombre_producto'] ?? $linea['codigo_producto']) . "</strong>";
        }

        if (!empty($linea['codigo_producto'])) {
            $html .= "<br/><small style='color: #999;'>Cód: " . htmlspecialchars($linea['codigo_producto']) . "</small>";
        }

        if (!empty($linea['devuelta'])) {
            $html .= "<br/><span style='color: white; background: #d32f2f; padding: 2px 4px; font-size: 8px; border-radius: 2px; font-weight: bold; display: inline-block; margin-top: 2px;'>DEVUELTO</span>";
        }

        $html .= "
                    </td>
                    <td class='qty-cell'>" . (int)$linea['cantidad'] . "</td>
                    <td class='price-cell'>" . number_format($linea['precio_unitario'], 2, ',', '.') . "</td>
                    <td class='price-cell'>" . number_format($linea['total_linea'], 2, ',', '.') . "</td>
                </tr>";
    }

    $html .= "
            </tbody>
        </table>

        <div class='totals'>
            <div class='total-row'>
                <span>Subtotal:</span>
                <span class='value'>" . number_format($venta['subtotal'], 2, ',', '.') . " €</span>
            </div>";

    if ((float)$venta['descuento_amt'] > 0) {
        $html .= "
            <div class='total-row'>
                <span>Descuento:</span>
                <span class='value'>-" . number_format($venta['descuento_amt'], 2, ',', '.') . " €</span>
            </div>";
    }

    $html .= "
            <div class='total-row'>
                <span>Base Imponible:</span>
                <span class='value'>" . number_format($venta['base_imponible'], 2, ',', '.') . " €</span>
            </div>
            <div class='total-row'>
                <span>IVA (21%):</span>
                <span class='value'>" . number_format($venta['iva_amt'], 2, ',', '.') . " €</span>
            </div>
            <div class='total-row main'>
                <span>TOTAL:</span>
                <span class='value'>" . number_format($venta['total'], 2, ',', '.') . " €</span>
            </div>
        </div>";

    if ($hasFinanciacion) {
        $html .= "
        <div class='financing-section'>
            <h4>💳 FINANCIACIÓN ACTIVA</h4>
            <div style='display: flex; justify-content: space-between; margin: 3px 0;'>
                <span>Entidad:</span>
                <strong>" . htmlspecialchars($venta['nombre_financiera']) . "</strong>
            </div>
            <div style='display: flex; justify-content: space-between; margin: 3px 0;'>
                <span>Plazo:</span>
                <strong>" . (int)$venta['meses'] . " meses</strong>
            </div>
            <div style='display: flex; justify-content: space-between; margin: 3px 0;'>
                <span>Cuota:</span>
                <strong>" . number_format($venta['cuota_mensual'], 2, ',', '.') . " €</strong>
            </div>
        </div>";
    }

    $html .= "
        <div class='footer'>
            <p>" . htmlspecialchars($appConfig['ticket_pie_pagina'] ?? 'Gracias por su compra') . "</p>
            <p>" . htmlspecialchars($appConfig['ticket_politica'] ?? 'Conserve este ticket para devoluciones') . "</p>
            <p style='margin-top: 8px; font-size: 8px;'>Generado: " . date('d/m/Y H:i:s') . "</p>
        </div>
    </div>
</body>
</html>";

    return $html;
}
