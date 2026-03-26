<?php
/**
 * API: enviarVentaEmail.php
 * Envía ticket/factura por correo adjuntando un PDF profesional generado con FPDF.
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

ob_start();
try {
    require_once __DIR__ . '/csrf_check.php';
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/VentaPDO.php';
    require_once __DIR__ . '/../model/ConfiguracionPDO.php';
    require_once __DIR__ . '/../core/PHPMailerMinimal.php';
    require_once __DIR__ . '/../core/PDFServiceV2.php';

    /*
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    */
    
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $numTicket = (int)($input['numTicket'] ?? 0);
    $destinatario = trim($input['destinatario'] ?? '');
    $tipo = $input['tipo'] ?? 'ticket';

    if ($numTicket <= 0 || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
        exit;
    }

    $venta = VentaPDO::obtenerVentaPorTicket($numTicket);
    $appConfig = ConfiguracionPDO::obtenerConfiguracion();
    if (!$venta) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Venta no encontrada']);
        exit;
    }

    $empresaNombre = $appConfig['empresa_nombre'] ?? 'ElectroBazar';
    $esFactura = ($tipo === 'factura' || 
                  $venta['tipo_cliente'] === 'empresa' || 
                  (isset($venta['es_factura']) && $venta['es_factura'] == 1));
    $asunto = $esFactura ? "Factura de Compra - $empresaNombre" : "Ticket de Compra - $empresaNombre";
    
    // GENERAR PDF
    $pdfContent = $esFactura ? PDFServiceV2::generarFacturaPDF($venta, $appConfig) : PDFServiceV2::generarTicketPDF($venta, $appConfig);
    $tempFile = sys_get_temp_dir() . '/tpv_doc_' . $numTicket . '_' . time() . '.pdf';
    file_put_contents($tempFile, $pdfContent);

    // CONFIGURACIÓN SMTP
    $mail = new PHPMailer();
    $mail->isSMTP();
    $mail->Host       = $appConfig['smtp_host'] ?? 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $appConfig['smtp_user'] ?? '';
    $mail->Password   = $appConfig['smtp_pass'] ?? '';
    $mail->SMTPSecure = $appConfig['smtp_secure'] ?? 'tls';
    $mail->Port       = (int)($appConfig['smtp_port'] ?? 587);
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom($appConfig['empresa_email'] ?? 'noreply@example.com', $empresaNombre);
    $mail->addAddress($destinatario);
    $mail->isHTML(true);
    $mail->Subject = $asunto;
    
    $docName = $esFactura ? "Factura_{$numTicket}.pdf" : "Ticket_{$numTicket}.pdf";
    $mail->Body = "Hola,<br><br>Adjuntamos el " . ($esFactura ? "archivo de tu factura" : "ticket de tu compra") . " realizado en <b>$empresaNombre</b>.<br><br>Gracias por tu confianza.";
    
    $mail->addAttachment($tempFile, $docName);

    ob_clean();
    if ($mail->send()) {
        echo json_encode(['ok' => true, 'message' => 'Documento enviado correctamente a ' . $destinatario]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Error de envío: ' . $mail->getErrorInfo()]);
    }
    
    // Limpieza
    if (file_exists($tempFile)) @unlink($tempFile);

} catch (Throwable $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
