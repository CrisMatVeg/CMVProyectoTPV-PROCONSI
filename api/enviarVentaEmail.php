<?php
// Suprimir errores PHP para que nunca contaminen el JSON
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

try {
    // Bootstrap: rutas absolutas
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/Venta.php';
    require_once __DIR__ . '/../model/VentaPDO.php';

    // Iniciar sesión para seguridad
    session_start();
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Sesión no válida o expirada']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $numTicket = $input['numTicket'] ?? null;
    $email     = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);

    if (!$numTicket || !$email) {
        throw new Exception('Datos insuficientes o email inválido');
    }

    // Obtener los datos de la venta
    $v = VentaPDO::obtenerVentaPorTicket($numTicket);
    if (!$v) {
        throw new Exception('No se encontró la venta con el ticket especificado');
    }

$esFactura = $v['tipo_cliente'] === 'empresa';
$tipoDoc = $esFactura ? 'Factura' : 'Ticket de Venta';
$numeroStr = '#' . str_pad($v['numero_ticket'], 4, '0', STR_PAD_LEFT);
$fechaStr = date("d/m/Y H:i", strtotime($v['creado_en']));

// Generar el cuerpo del mensaje HTML (Diseño profesional similar al ticket)
$html = "
<div style='font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; color: #333;'>
    <div style='text-align: center; border-bottom: 2px solid #1a2fbf; padding-bottom: 20px; margin-bottom: 20px;'>
        <h1 style='color: #1a2fbf; margin: 0;'>ElectroBazar</h1>
        <p style='font-size: 12px; color: #666; margin: 5px 0;'>C/ Tecnología 24, 28001 Madrid · NIF: B87654321</p>
    </div>
    
    <div style='margin-bottom: 20px;'>
        <h2 style='font-size: 18px; margin: 0;'>$tipoDoc $numeroStr</h2>
        <p style='font-size: 13px; color: #666; margin: 5px 0;'>Fecha: $fechaStr</p>
        <p style='font-size: 13px; color: #666; margin: 5px 0;'>Método de pago: " . ucfirst($v['metodo_pago']) . "</p>
    </div>";

if ($esFactura) {
    $html .= "
    <div style='background: #f9f9f9; padding: 10px; margin-bottom: 20px; border-radius: 5px;'>
        <p style='font-size: 12px; font-weight: bold; margin: 0 0 5px;'>Datos del Cliente:</p>
        <p style='font-size: 13px; margin: 0;'>" . htmlspecialchars($v['nombre_cliente']) . "</p>
        <p style='font-size: 13px; margin: 0;'>NIF: " . htmlspecialchars($v['nif_cliente']) . "</p>
    </div>";
}

$html .= "
    <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
        <thead>
            <tr style='border-bottom: 1px solid #ddd; text-align: left; font-size: 13px;'>
                <th style='padding: 8px 0;'>Producto</th>
                <th style='padding: 8px 0; text-align: right;'>Total</th>
            </tr>
        </thead>
        <tbody>";

foreach ($v['lineas'] as $l) {
    $html .= "
            <tr style='border-bottom: 1px solid #eee; font-size: 13px;'>
                <td style='padding: 10px 0;'>
                    <strong>" . htmlspecialchars($l['nombre_producto']) . "</strong><br>
                    <small style='color: #666;'>" . $l['cantidad'] . " x " . number_format($l['precio_unitario'], 2, ',', '.') . " €</small>
                </td>
                <td style='padding: 10px 0; text-align: right;'>" . number_format($l['total_linea'], 2, ',', '.') . " €</td>
            </tr>";
}

$html .= "
        </tbody>
    </table>

    <div style='border-top: 2px solid #ddd; padding-top: 10px;'>
        <div style='display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 14px;'>
            <span>Subtotal:</span>
            <span>" . number_format($v['subtotal'], 2, ',', '.') . " €</span>
        </div>";

if ($v['descuento_pct'] > 0) {
    $html .= "
        <div style='display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 14px; color: #c0392b;'>
            <span>Descuento (" . $v['descuento_pct'] . "%):</span>
            <span>-" . number_format($v['descuento_amt'], 2, ',', '.') . " €</span>
        </div>";
}

$html .= "
        <div style='display: flex; justify-content: space-between; margin-top: 10px; font-size: 18px; font-weight: bold; color: #1a2fbf;'>
            <span>TOTAL:</span>
            <span>" . number_format($v['total'], 2, ',', '.') . " €</span>
        </div>
    </div>

    <div style='margin-top: 30px; text-align: center; font-size: 11px; color: #999;'>
        <p>Gracias por su compra en ElectroBazar.</p>
        <p>Este es un documento oficial emitido por nuestro TPV.</p>
    </div>
</div>";

// Cabeceras para correo HTML
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: ElectroBazar <noreply@electrobazar.com>" . "\r\n";

    // Intentar enviar el correo
    ob_start();
    $success = mail($email, "$tipoDoc No. $numeroStr - ElectroBazar", $html, $headers);
    $errorCapture = ob_get_clean();

    // Sistema de Log como Fallback (útil para desarrollo en local sin SMTP)
    if (!$success) {
        $logDir = __DIR__ . '/../doc/mail_logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . "/ticket_" . $v['numero_ticket'] . "_" . time() . ".html";
        file_put_contents($logFile, $html);
        
        echo json_encode([
            'ok' => true, 
            'message' => 'El servidor no tiene configurado correo, pero se ha guardado una copia en local.',
            'log_path' => 'doc/mail_logs/' . basename($logFile)
        ]);
    } else {
        echo json_encode(['ok' => true, 'message' => 'Email enviado correctamente']);
    }

} catch (Throwable $e) {
    echo json_encode([
        'ok'    => false,
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
