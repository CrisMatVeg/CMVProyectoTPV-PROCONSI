<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: generarPDFTicket.php
 * Genera PDF usando PDFServiceV2 para descarga.
 */

ini_set('display_errors', 0);
error_reporting(0);

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/VentaPDO.php';
    require_once __DIR__ . '/../model/ConfiguracionPDO.php';
    require_once __DIR__ . '/../core/PDFServiceV2.php';

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
    $tipo = $_GET['tipo'] ?? 'ticket';
    $esFactura = ($tipo === 'factura' || $venta['tipo_cliente'] === 'empresa' || (isset($venta['es_factura']) && $venta['es_factura'] == 1));

    $formattedNum = VentaPDO::formatTicketNumber($venta['numero_ticket'], $venta['fecha'], $esFactura);
    $venta['numero_ticket_formato'] = $formattedNum;

    if ($esFactura) {
        $pdfBinary = PDFServiceV2::generarFacturaPDF($venta, $appConfig);
        $filename = "Factura_" . $formattedNum . ".pdf";
    } else {
        $pdfBinary = PDFServiceV2::generarTicketPDF($venta, $appConfig);
        $filename = "Ticket_" . $formattedNum . ".pdf";
    }

    // Limpiar cualquier salida previa
    if (ob_get_length()) ob_clean();

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . strlen($pdfBinary));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    echo $pdfBinary;
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Error crítico: " . $e->getMessage();
}