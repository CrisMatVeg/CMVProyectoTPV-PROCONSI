<?php
require_once __DIR__ . '/csrf_check.php';

/**
 * API: descargarDeclaracion.php
 * Genera la Declaración Responsable de VeriFactu en PDF para descarga.
 */

ini_set('display_errors', 0);
error_reporting(0);

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/ConfiguracionPDO.php';
    require_once __DIR__ . '/../core/PDFServiceV2.php';

    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        die('No autorizado');
    }

    $appConfig = ConfiguracionPDO::obtenerConfiguracion();
    
    $pdfBinary = PDFServiceV2::generarDeclaracionResponsablePDF($appConfig);
    $softwareName = $appConfig['verifactu_nombre_sistema'] ?? 'ElectroBazar_TPV';
    $filename = "Declaracion_Responsable_VeriFactu_" . str_replace([' ', '/', '\\'], '_', $softwareName) . ".pdf";

    // Limpiar cualquier salida previa para evitar PDFs corruptos
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
