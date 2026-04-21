<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

class VeriFactuQrService
{

    /**
     * Genera la URL de verificación de la AEAT según las especificaciones de VeriFactu.
     * 
     * @param array $venta Datos de la venta/factura
     * @param string $nifEmisor NIF de la empresa emisora
     * @return string URL formateada
     */
    public static function generarUrlAEAT(array $venta, string $nifEmisor): string
    {
        // El formato de fecha debe ser DD-MM-AAAA
        $fecha = date('d-m-Y', strtotime($venta['fecha']));
        
        // El importe debe tener 2 decimales y "." como separador
        $importe = number_format((float)$venta['total'], 2, '.', '');
        
        // Separamos el formato T-DDMMYYYY-NNNN en serie (T-DDMMYYYY) y número (NNNN)
        $fullNum = $venta['numero_ticket_formato'] ?? $venta['numero_ticket'] ?? '';
        $parts = explode('-', $fullNum);
        
        if (count($parts) >= 3) {
            $serie = $parts[0] . '-' . $parts[1]; // T-DDMMYYYY
            $numero = $parts[2];                 // NNNN
        } else {
            $serie = "";
            $numero = $fullNum;
        }

        // URL CORRECTA del motor de cotejo de la AEAT (Nueva para VeriFactu)
        $baseUrl = defined('VERIFACTU_URL_QR_PRUEBAS') 
            ? VERIFACTU_URL_QR_PRUEBAS 
            : "https://prewww2.aeat.es/wlpl/TIKE-CONT/ValidarQR";
        
        $params = [
            'nif'      => $nifEmisor,
            'numserie' => $fullNum,
            'fecha'    => $fecha,
            'importe'  => $importe,
            'hash'     => strtoupper(substr($venta['hash_actual'] ?? '', 0, 8))
        ];

        // RFC3986 es vital para que los caracteres como "/" o "-" se codifiquen bien
        return $baseUrl . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Genera el código QR en formato Data URI (Base64) para incrustar en HTML.
     * 
     * @param string $url URL a codificar
     * @return string Data URI del código QR
     */
    public static function generarQrBase64(string $url): string
    {
        $qrCode = new \Endroid\QrCode\QrCode(
            data: $url,
            encoding: new \Endroid\QrCode\Encoding\Encoding('UTF-8'),
            errorCorrectionLevel: \Endroid\QrCode\ErrorCorrectionLevel::Medium,
            size: 300,
            margin: 10,
            roundBlockSizeMode: \Endroid\QrCode\RoundBlockSizeMode::Margin
        );

        $writer = new \Endroid\QrCode\Writer\PngWriter();
        $result = $writer->write($qrCode);

        return $result->getDataUri();
    }
}
