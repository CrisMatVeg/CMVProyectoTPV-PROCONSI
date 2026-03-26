<?php

/**
 * PDFServiceV2.php
 */
require_once __DIR__ . '/fpdf.php';

class PDFServiceV2
{

    private static function decode($txt)
    {
        if ($txt === null) return '';
        // Si ya es válido como windows-1252 puro (no UTF-8), no convertir
        if (mb_detect_encoding($txt, 'UTF-8', true) === false) {
            return $txt; // Ya está en windows-1252
        }
        return mb_convert_encoding($txt, 'windows-1252', 'UTF-8');
    }

    private static function formatEuros($amt)
    {
        return number_format((float)$amt, 2, ',', '.') . " " . chr(128);
    }

    private static function getVatBreakdown($venta)
    {
        $breakdown = [];

        if (empty($venta['lineas'])) return [];

        foreach ($venta['lineas'] as $l) {
            $rate       = (float)($l['iva_aplicado'] ?? 21);
            $totalLinea = (float)$l['total_linea'];
            $base       = $totalLinea / (1 + $rate / 100);
            $tax        = $totalLinea - $base;

            $sRate = (string)$rate;
            if (!isset($breakdown[$sRate])) {
                $breakdown[$sRate] = ['base' => 0.0, 'tax' => 0.0];
            }
            $breakdown[$sRate]['base'] += $base;
            $breakdown[$sRate]['tax']  += $tax;
        }

        return $breakdown;
    }

    public static function generarTicketPDF($venta, $appConfig)
    {
        error_log("PDF TICKET - total: " . $venta['total'] . " | subtotal: " . $venta['subtotal'] . " | ticket: " . $venta['numero_ticket']);
        $pdf = new FPDF('P', 'mm', array(80, 297));
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->SetMargins(10, 10, 10);

        $pdf->SetFont('Courier', 'B', 14);
        $pdf->Cell(0, 7, self::decode($appConfig['empresa_nombre'] ?? 'ELECTROBAZAR'), 0, 1, 'C');
        $pdf->SetFont('Courier', '', 8);
        $pdf->Cell(0, 4, self::decode("Electrónica - Tecnología - Accesorios"), 0, 1, 'C');
        $pdf->Ln(2);

        $pdf->SetFont('Courier', '', 8);
        $pdf->MultiCell(0, 3, self::decode($appConfig['empresa_direccion'] ?? ''), 0, 'C');
        $pdf->Cell(0, 4, self::decode("Tel: " . ($appConfig['empresa_telefono'] ?? '') . " · CIF: " . ($appConfig['empresa_nif'] ?? '')), 0, 1, 'C');

        $pdf->Ln(2);
        $pdf->Cell(0, 0, '', 'T');
        $pdf->Ln(2);

        $pdf->Cell(30, 4, self::decode("Ticket n.º:"), 0, 0);
        $pdf->Cell(0, 4, $venta['numero_ticket'], 0, 1, 'R');

        $pdf->Cell(30, 4, self::decode("Fecha:"), 0, 0);
        $pdf->Cell(0, 4, date("d/m/Y H:i", strtotime($venta['fecha'])), 0, 1, 'R');

        $pdf->Cell(30, 4, self::decode("Caja:"), 0, 0);
        $pdf->Cell(0, 4, "01", 0, 1, 'R');

        $pdf->Cell(30, 4, self::decode("Operador:"), 0, 0);
        $pdf->Cell(0, 4, self::decode($venta['nombre_cajero'] ?? 'Sistema'), 0, 1, 'R');

        $pdf->Ln(2);
        $pdf->Cell(0, 0, '', 'T');
        $pdf->Ln(2);

        $pdf->SetFont('Courier', 'B', 9);
        $pdf->Cell(40, 5, self::decode("DESCRIPCIÓN"), 0, 0);
        $pdf->Cell(20, 5, self::decode("IMPORTE"), 0, 1, 'R');
        $pdf->Ln(1);

        $pdf->SetFont('Courier', '', 8);
        foreach ($venta['lineas'] as $l) {
            $nombre = self::decode($l['nombre_producto']);
            $pdf->MultiCell(0, 4, $nombre, 0, 'L');

            $subTexto = (int)$l['cantidad'] . " x " . number_format($l['precio_unitario'], 2, ',', '.') . " " . chr(128);
            $pdf->Cell(40, 4, self::decode($subTexto), 0, 0);
            $pdf->Cell(20, 4, self::formatEuros($l['total_linea']), 0, 1, 'R');

            if (!empty($l['meses_garantia']) && $l['meses_garantia'] > 0) {
                $pdf->SetFont('Courier', 'I', 7);
                $pdf->Cell(0, 3, self::decode("  Garantía: " . $l['meses_garantia'] . " meses"), 0, 1);
                $pdf->SetFont('Courier', '', 8);
            }
            $pdf->Ln(1);
        }

        $pdf->Ln(1);
        $pdf->Cell(0, 0, '', 'T');
        $pdf->Ln(2);

        $pdf->Cell(40, 4, self::decode("Subtotal:"), 0, 0);
        $pdf->Cell(20, 4, self::formatEuros($venta['subtotal']), 0, 1, 'R');

        if (!empty($venta['descuento_amt']) && (float)$venta['descuento_amt'] > 0) {
            $pdf->Cell(40, 4, self::decode("Descuento (" . round($venta['descuento_pct']) . "%):"), 0, 0);
            $pdf->Cell(20, 4, "- " . self::formatEuros($venta['descuento_amt']), 0, 1, 'R');
        }

        $breakdown = self::getVatBreakdown($venta);
        if (empty($breakdown)) {
            $pdf->Cell(40, 4, self::decode("IVA incluido (21%):"), 0, 0);
            $pdf->Cell(20, 4, self::formatEuros($venta['iva_amt']), 0, 1, 'R');
        } else {
            foreach ($breakdown as $rate => $vals) {
                $pdf->Cell(40, 4, self::decode("IVA incluido ({$rate}%):"), 0, 0);
                $pdf->Cell(20, 4, self::formatEuros($vals['tax']), 0, 1, 'R');
            }
        }

        $valesAmt = 0;
        if (!empty($venta['pagos'])) {
            foreach ($venta['pagos'] as $pago) {
                if ($pago['metodo_pago'] === 'vale') $valesAmt += (float)$pago['importe'];
            }
        }
        if ($valesAmt > 0) {
            $pdf->Cell(40, 4, self::decode("Abonado con vales:"), 0, 0);
            $pdf->Cell(20, 4, "- " . self::formatEuros($valesAmt), 0, 1, 'R');
        }

        $pdf->Ln(2);
        $pdf->SetFont('Courier', 'B', 12);
        $pdf->Cell(30, 8, self::decode("TOTAL"), 0, 0);
        $pdf->Cell(30, 8, self::formatEuros($venta['total']), 0, 1, 'R');
        $pdf->Ln(2);

        $pdf->SetFont('Courier', '', 8);
        $pdf->Cell(0, 0, '', 'T');
        $pdf->Ln(2);

        $pdf->Cell(40, 4, self::decode("Forma de pago:"), 0, 0);
        $pdf->Cell(20, 4, strtoupper($venta['metodo_pago']), 0, 1, 'R');

        if ($venta['metodo_pago'] === 'efectivo') {
            $pdf->Cell(40, 4, self::decode("Entregado:"), 0, 0);
            $pdf->Cell(20, 4, self::formatEuros($venta['efectivo_recibido'] ?? $venta['total']), 0, 1, 'R');
            $recibido = (float)($venta['efectivo_recibido'] ?? $venta['total']);
            $cambio = $recibido - (float)$venta['total'] + $valesAmt;
            $pdf->Cell(40, 4, self::decode("Cambio:"), 0, 0);
            $pdf->Cell(20, 4, self::formatEuros(max(0, $cambio)), 0, 1, 'R');
        }


        $pdf->Ln(2);
        $pdf->Cell(0, 0, '', 'T');
        $pdf->Ln(2);

        if (!empty($venta['comentarios'])) {
            $pdf->SetFont('Courier', 'BI', 8);
            $pdf->Cell(0, 4, self::decode("OBSERVACIONES:"), 0, 1);
            $pdf->SetFont('Courier', 'I', 8);
            $pdf->MultiCell(0, 3, self::decode($venta['comentarios']), 0, 'L');
            $pdf->Ln(2);
            $pdf->Cell(0, 0, '', 'T');
            $pdf->Ln(2);
        }

        $pdf->SetFont('Courier', 'B', 8);
        $pdf->Cell(0, 4, self::decode("DESGLOSE IVA"), 0, 1, 'C');
        $pdf->SetFont('Courier', '', 8);

        if (empty($breakdown)) {
            $pdf->Cell(40, 4, self::decode("Base imp. (21%):"), 0, 0);
            $pdf->Cell(20, 4, self::formatEuros($venta['base_imponible']), 0, 1, 'R');
            $pdf->Cell(40, 4, self::decode("Cuota IVA (21%):"), 0, 0);
            $pdf->Cell(20, 4, self::formatEuros($venta['iva_amt']), 0, 1, 'R');
        } else {
            foreach ($breakdown as $rate => $vals) {
                $pdf->Cell(40, 4, self::decode("Base imp. ({$rate}%):"), 0, 0);
                $pdf->Cell(20, 4, self::formatEuros($vals['base']), 0, 1, 'R');
                $pdf->Cell(40, 4, self::decode("Cuota IVA ({$rate}%):"), 0, 0);
                $pdf->Cell(20, 4, self::formatEuros($vals['tax']), 0, 1, 'R');
            }
        }

        $pdf->Ln(4);
        $pdf->SetFont('Courier', '', 7);
        $pdf->MultiCell(0, 3, self::decode($appConfig['ticket_politica'] ?? ''), 0, 'C');
        $pdf->Ln(2);
        $pdf->SetFont('Courier', 'B', 7);
        $pdf->Cell(0, 4, self::decode($appConfig['ticket_pie_pagina'] ?? ''), 0, 1, 'C');

        return $pdf->Output('S');
    }

    public static function generarFacturaPDF($venta, $appConfig)
    {
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();

        $pdf->SetFont('Arial', 'B', 24);
        $pdf->Cell(120, 12, self::decode($appConfig['empresa_nombre'] ?? 'ELECTROBAZAR'), 0, 0);
        $pdf->SetTextColor(150);
        $pdf->SetFont('Arial', 'B', 32);
        $pdf->Cell(70, 15, 'FACTURA', 0, 1, 'R');
        $pdf->SetTextColor(0);

        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(120, 5, self::decode("Electrónica · Tecnología · Accesorios"), 0, 0);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(70, 5, self::decode("N.º: " . $venta['numero_ticket']), 0, 1, 'R');

        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(120, 5, self::decode("CIF: " . ($appConfig['empresa_nif'] ?? '')), 0, 1);
        $pdf->Cell(120, 5, self::decode($appConfig['empresa_direccion'] ?? ''), 0, 1);
        $pdf->Cell(120, 5, self::decode("Tel: " . ($appConfig['empresa_telefono'] ?? '')), 0, 1);
        $pdf->Cell(120, 5, self::decode($appConfig['empresa_email'] ?? ''), 0, 1);

        $pdf->Line(10, $pdf->GetY() + 5, 200, $pdf->GetY() + 5);
        $pdf->Ln(15);

        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(45, 5, self::decode("Fecha de emisión:"), 0, 0);
        $pdf->Cell(45, 5, self::decode("Vencimiento:"), 0, 0);
        $pdf->Cell(45, 5, self::decode("Método de pago:"), 0, 0);
        $pdf->Cell(45, 5, self::decode("Divisa:"), 0, 1);

        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(45, 5, date("d/m/Y", strtotime($venta['fecha'])), 0, 0);
        $pdf->Cell(45, 5, date("d/m/Y", strtotime($venta['fecha'])), 0, 0);
        $pdf->Cell(45, 5, strtoupper($venta['metodo_pago']), 0, 0);
        $pdf->Cell(45, 5, "EUR - Euro", 0, 1);

        $pdf->Ln(10);

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(95, 5, self::decode("DATOS DEL EMISOR"), 'B', 0);
        $pdf->Cell(10, 5, '', 0, 0);
        $pdf->Cell(85, 5, self::decode("DATOS DEL CLIENTE"), 'B', 1);

        $pdf->SetFont('Arial', '', 10);
        $pdf->Ln(2);
        $yData = $pdf->GetY();

        $pdf->SetXY(10, $yData);
        $pdf->MultiCell(90, 5, self::decode(($appConfig['empresa_razon_social'] ?? $appConfig['empresa_nombre']) . "\nCIF: " . $appConfig['empresa_nif'] . "\n" . $appConfig['empresa_direccion'] . "\n" . $appConfig['empresa_email']), 0, 'L');

        $pdf->SetXY(115, $yData);
        $pdf->MultiCell(85, 5, self::decode(($venta['nombre_cliente'] ?? '') . "\nCIF: " . ($venta['nif_cliente'] ?? 'N/A') . "\n" . ($venta['direccion_cliente'] ?? '') . "\n" . ($venta['poblacion_cliente'] ?? '') . "\n" . ($venta['email_cliente'] ?? '')), 0, 'L');

        $pdf->SetY($pdf->GetY() + 5);
        $pdf->Ln(10);

        $pdf->SetFillColor(0, 0, 0);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(85, 9, self::decode("Descripción"), 1, 0, 'L', true);
        $pdf->Cell(20, 9, self::decode("Cant."), 1, 0, 'C', true);
        $pdf->Cell(30, 9, self::decode("Precio U."), 1, 0, 'R', true);
        $pdf->Cell(25, 9, self::decode("Desc."), 1, 0, 'R', true);
        $pdf->Cell(30, 9, self::decode("Subtotal"), 1, 1, 'R', true);

        $pdf->SetTextColor(0);
        $pdf->SetFont('Arial', '', 10);
        $fill = false;
        foreach ($venta['lineas'] as $l) {
            $pdf->SetFillColor(245, 245, 245);
            $pdf->Cell(85, 8, self::decode($l['nombre_producto']), 'B', 0, 'L', $fill);
            $pdf->Cell(20, 8, (int)$l['cantidad'], 'B', 0, 'C', $fill);
            $pdf->Cell(30, 8, self::formatEuros($l['precio_unitario']), 'B', 0, 'R', $fill);
            $pdf->Cell(25, 8, "0,00", 'B', 0, 'R', $fill);
            $pdf->Cell(30, 8, self::formatEuros($l['total_linea']), 'B', 1, 'R', $fill);
            $fill = !$fill;
        }

        $pdf->Ln(5);

        $breakdown = self::getVatBreakdown($venta);
        if (empty($breakdown)) {
            $pdf->SetX(130);
            $pdf->Cell(40, 6, "Base imp. (21%):", 0, 0, 'R');
            $pdf->Cell(30, 6, self::formatEuros($venta['base_imponible']), 0, 1, 'R');
            $pdf->SetX(130);
            $pdf->Cell(40, 6, "IVA (21%):", 0, 0, 'R');
            $pdf->Cell(30, 6, self::formatEuros($venta['iva_amt']), 0, 1, 'R');
        } else {
            foreach ($breakdown as $rate => $vals) {
                $pdf->SetX(130);
                $pdf->Cell(40, 6, "Base imp. ({$rate}%):", 0, 0, 'R');
                $pdf->Cell(30, 6, self::formatEuros($vals['base']), 0, 1, 'R');
                $pdf->SetX(130);
                $pdf->Cell(40, 6, "IVA ({$rate}%):", 0, 0, 'R');
                $pdf->Cell(30, 6, self::formatEuros($vals['tax']), 0, 1, 'R');
            }
        }

        if (!empty($venta['descuento_amt']) && (float)$venta['descuento_amt'] > 0) {
            $pdf->SetX(130);
            $pdf->Cell(40, 6, "Descuento:", 0, 0, 'R');
            $pdf->Cell(30, 6, "- " . self::formatEuros($venta['descuento_amt']), 0, 1, 'R');
        }

        $valesAmt = 0;
        if (!empty($venta['pagos'])) {
            foreach ($venta['pagos'] as $pago) {
                if ($pago['metodo_pago'] === 'vale') $valesAmt += (float)$pago['importe'];
            }
        }
        if ($valesAmt > 0) {
            $pdf->SetX(130);
            $pdf->Cell(40, 6, "Abonado con vales:", 0, 0, 'R');
            $pdf->Cell(30, 6, "- " . self::formatEuros($valesAmt), 0, 1, 'R');
        }

        $pdf->Ln(2);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetX(130);
        $pdf->Cell(40, 10, "TOTAL A PAGAR:", 'T', 0, 'R');
        $pdf->Cell(30, 10, self::formatEuros($venta['total']), 'T', 1, 'R');

        $pdf->Ln(10);

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(95, 5, self::decode("DATOS BANCARIOS"), 'B', 0);
        $pdf->Cell(10, 5, '', 0, 0);
        $pdf->Cell(85, 5, self::decode("NOTAS Y CONDICIONES"), 'B', 1);

        $pdf->SetFont('Arial', '', 9);
        $pdf->Ln(2);
        $yBot = $pdf->GetY();

        $pdf->SetXY(10, $yBot);
        $bancoInfo = "Banco Sabadell\nIBAN: ES76 0081 0248 6000 0011 2233\nRef: " . $venta['numero_ticket'];
        $pdf->MultiCell(95, 5, self::decode($bancoInfo), 0, 'L');

        $pdf->SetXY(115, $yBot);
        $condiciones = "Pago en un plazo máximo de 30 días. En caso de retraso se aplicará un interés de demora del 1,5% mensual. Factura emitida conforme al RD 1619/2012.";
        if (!empty($venta['comentarios'])) {
            $condiciones .= "\n\nOBSERVACIONES:\n" . $venta['comentarios'];
        }
        $pdf->MultiCell(85, 5, self::decode($condiciones), 0, 'L');

        $pdf->SetY(275);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(100);
        $pdf->Cell(0, 5, self::decode(($appConfig['empresa_razon_social'] ?? $appConfig['empresa_nombre']) . " · CIF: " . $appConfig['empresa_nif'] . " · " . $appConfig['empresa_direccion']), 0, 1, 'C');

        return $pdf->Output('S');
    }
}
