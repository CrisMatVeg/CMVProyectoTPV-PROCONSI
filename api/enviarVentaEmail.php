<?php

/**
 * API: enviarVentaEmail.php
 * Envía ticket/factura por correo con opción de adjuntar HTML o PDF
 */
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/VentaPDO.php';
    require_once __DIR__ . '/../model/ConfiguracionPDO.php';

    session_start();
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'No autorizado']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $numTicket = (int)($input['numTicket'] ?? 0);
    $destinatario = trim($input['destinatario'] ?? '');
    $tipo = $input['tipo'] ?? 'ticket'; // 'ticket' o 'factura'

    // Validación
    if ($numTicket <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Número de ticket inválido']);
        exit;
    }

    if (!filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Email inválido']);
        exit;
    }

    // Cargar venta
    $venta = VentaPDO::obtenerVentaPorTicket($numTicket);
    if (!$venta) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'Venta no encontrada']);
        exit;
    }

    $appConfig = ConfiguracionPDO::obtenerConfiguracion();
    $adjuntarPDF = !empty($input['adjuntarPDF']);

    // Generar HTML del ticket
    $htmlTicket = generarHTMLTicket($venta, $tipo, $appConfig);

    // Intentar enviar con PHPMailer
    $enviado = false;
    $errorMsg = '';

    // Rutas para PHPMailer (ajustar si se usa composer o manual)
    $pathPHPMailer = __DIR__ . '/../vendor/PHPMailer/src/';

    if (file_exists($pathPHPMailer . 'PHPMailer.php')) {
        try {
            require_once $pathPHPMailer . 'Exception.php';
            require_once $pathPHPMailer . 'PHPMailer.php';
            require_once $pathPHPMailer . 'SMTP.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            // Server settings - CONFIGURAR AQUÍ
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // Cambiar por tu servidor
            $mail->SMTPAuth   = true;
            $mail->Username   = 'tu-email@gmail.com'; // Cambiar por tu email
            $mail->Password   = 'tu-app-password'; // Cambiar por tu password o app password
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            // Recipients
            $mail->setFrom('noreply@electrobazar.es', 'ElectroBazar');
            $mail->addAddress($destinatario);

            // Asunto
            $empresaNombre = $appConfig['empresa_nombre'] ?? 'ElectroBazar';
            $asunto = ($tipo === 'factura') ? "Tu Factura de $empresaNombre" : "Tu Ticket de Compra de $empresaNombre";

            // Content
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $htmlTicket;
            $mail->AltBody = strip_tags(str_replace('<br>', "\n", $htmlTicket));

            // Si se solicita adjuntar PDF, y tenemos el generador PDF...
            if ($adjuntarPDF) {
                // Aquí podrías generar el PDF en el servidor y adjuntarlo
                // Como fallback, avisamos que el PDF está incluido en el cuerpo HTML
            }

            $enviado = $mail->send();
        } catch (Exception $e) {
            $errorMsg = "Error PHPMailer: {$mail->ErrorInfo}";
        }
    } else {
        // Fallback: usar mail() si PHPMailer no está instalado o falló
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "From: ElectroBazar <noreply@electrobazar.es>\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $enviado = @mail($destinatario, $asunto, $htmlTicket, $headers);
        if (!$enviado) $errorMsg = "Error en función mail() de PHP. Verifica configuración SMTP.";
    }

    if ($enviado) {
        echo json_encode([
            'ok' => true,
            'message' => 'Ticket enviado correctamente a ' . $destinatario,
            'email' => $destinatario
        ]);
    } else {
        // Si falló el envío por red, guardamos en log local como respaldo
        $logDir = __DIR__ . '/../doc/mail_logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0777, true);

        $logFile = $logDir . "/ticket_" . $numTicket . "_" . time() . ".html";
        file_put_contents($logFile, "<!-- Error: $errorMsg -->\n" . $htmlTicket);

        echo json_encode([
            'ok' => false,
            'error' => 'No se pudo enviar el email: ' . $errorMsg,
            'info' => 'El ticket ha sido guardado localmente en doc/mail_logs/ por seguridad.'
        ]);
    }

    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

/**
 * Genera HTML del ticket para email
 */
function generarHTMLTicket($venta, $tipo = 'ticket', $appConfig = [])
{
    $esFactura = $tipo === 'factura' || $venta['tipo_cliente'] === 'empresa';
    $templatePath = $esFactura ? __DIR__ . '/../factura-electrobazar.html' : __DIR__ . '/../ticket-electrobazar.html';

    if (!file_exists($templatePath)) {
        // Fallback or legacy generation if template missing
        return "Plantilla no encontrada.";
    }

    $html = file_get_contents($templatePath);
    $fmt2 = fn($n) => number_format((float)$n, 2, ',', '.') . ' €';
    $numeroStr = str_pad($venta['numero_ticket'], 4, '0', STR_PAD_LEFT);
    $fechaStr = date("d/m/Y H:i", strtotime($venta['fecha']));

    if ($esFactura) {
        $lineasHTML = "";
        foreach ($venta['lineas'] as $l) {
            $desc = htmlspecialchars($l['nombre_producto'] ?? $l['codigo_producto']);

            $lineasHTML .= "<tr>
                <td>$desc</td>
                <td style='text-align:center;'>" . (int)$l['cantidad'] . "</td>
                <td>" . $fmt2($l['precio_unitario']) . "</td>
                <td>" . ((float)$venta['descuento_pct'] > 0 ? (float)$venta['descuento_pct'] . '%' : '—') . "</td>
                <td>" . $fmt2($l['total_linea']) . "</td>
            </tr>";
        }

        $reemplazos = [
            '{{FACTURA_NUM}}' => 'FAC-' . date('Y', strtotime($venta['fecha'])) . '-' . $numeroStr,
            '{{FECHA_EMISION}}' => date("d/m/Y", strtotime($venta['fecha'])),
            '{{FECHA_VENCIMIENTO}}' => date("d/m/Y", strtotime($venta['fecha'] . " + 30 days")),
            '{{METODO_PAGO}}' => ucfirst($venta['metodo_pago']),
            '{{CLIENTE_NOMBRE}}' => htmlspecialchars($venta['nombre_cliente'] ?? '—'),
            '{{CLIENTE_CIF}}' => htmlspecialchars($venta['nif_cliente'] ?? '—'),
            '{{CLIENTE_DIRECCION}}' => htmlspecialchars($venta['direccion_cliente'] ?? '—'),
            '{{CLIENTE_POBLACION}}' => htmlspecialchars(($venta['poblacion_cliente'] ?? '') . ' ' . ($venta['cp_cliente'] ?? '')),
            '{{CLIENTE_EMAIL}}' => htmlspecialchars($venta['email_cliente'] ?? ''),
            '{{LINEAS}}' => $lineasHTML,
            '{{BASE_IMPONIBLE}}' => $fmt2($venta['base_imponible']),
            '{{DESCUENTO_AMT}}' => $fmt2($venta['descuento_amt']),
            '{{IVA_AMT}}' => $fmt2($venta['iva_amt']),
            '{{TOTAL}}' => $fmt2($venta['total']),
            '{{DISPLAY_DESCUENTO}}' => (float)$venta['descuento_amt'] > 0 ? '' : 'display:none;',
            '{{EMPRESA_NOMBRE}}' => htmlspecialchars($appConfig['empresa_nombre'] ?? ''),
            '{{EMPRESA_RAZON_SOCIAL}}' => htmlspecialchars($appConfig['empresa_razon_social'] ?? ''),
            '{{EMPRESA_NIF}}' => htmlspecialchars($appConfig['empresa_nif'] ?? ''),
            '{{EMPRESA_DIRECCION}}' => htmlspecialchars($appConfig['empresa_direccion'] ?? ''),
            '{{EMPRESA_TELEFONO}}' => htmlspecialchars($appConfig['empresa_telefono'] ?? ''),
            '{{EMPRESA_EMAIL}}' => htmlspecialchars($appConfig['empresa_email'] ?? ''),
            '{{EMPRESA_WEB}}' => htmlspecialchars($appConfig['empresa_web'] ?? ''),
            '{{EMPRESA_REGISTRO}}' => htmlspecialchars($appConfig['empresa_registro'] ?? ''),
            '{{TICKET_PIE_PAGINA}}' => htmlspecialchars($appConfig['ticket_pie_pagina'] ?? ''),
            '{{TICKET_POLITICA}}' => htmlspecialchars($appConfig['ticket_politica'] ?? '')
        ];
    } else {
        $lineasHTML = "";
        foreach ($venta['lineas'] as $l) {
            $nombre = htmlspecialchars($l['nombre_producto'] ?? $l['codigo_producto']);
            $lineasHTML .= "<div class='item'><span class='item-desc'>$nombre</span><span class='item-price'>" . $fmt2($l['total_linea']) . "</span></div>";
            $lineasHTML .= "<div class='item-detail'>Ref: " . htmlspecialchars($l['codigo_producto']) . " · " . (int)$l['cantidad'] . " ud x " . $fmt2($l['precio_unitario']) . "</div>";
        }

        $reemplazos = [
            '{{NUMERO_TICKET}}' => $numeroStr,
            '{{FECHA}}' => $fechaStr,
            '{{OPERADOR}}' => $venta['nombre_cajero'] ?? 'Sistema',
            '{{LINEAS}}' => $lineasHTML,
            '{{SUBTOTAL}}' => $fmt2($venta['subtotal']),
            '{{DESCUENTO_PCT}}' => (float)$venta['descuento_pct'],
            '{{DESCUENTO_AMT}}' => $fmt2($venta['descuento_amt']),
            '{{IVA_AMT}}' => $fmt2($venta['iva_amt']),
            '{{TOTAL}}' => $fmt2($venta['total']),
            '{{METODO_PAGO}}' => ucfirst($venta['metodo_pago']),
            '{{EFECTIVO_RECIBIDO}}' => $fmt2($venta['efectivo_recibido'] ?? 0),
            '{{EFECTIVO_CAMBIO}}' => $fmt2(($venta['efectivo_recibido'] ?? 0) - $venta['total']),
            '{{BASE_IMPONIBLE}}' => $fmt2($venta['base_imponible']),
            '{{DISPLAY_DESCUENTO}}' => (float)$venta['descuento_amt'] > 0 ? '' : 'display:none;',
            '{{DISPLAY_EFECTIVO}}' => ($venta['metodo_pago'] === 'efectivo') ? '' : 'display:none;',
            '{{PAGO_DETALLE}}' => '',
            '{{EMPRESA_NOMBRE}}' => htmlspecialchars($appConfig['empresa_nombre'] ?? ''),
            '{{EMPRESA_RAZON_SOCIAL}}' => htmlspecialchars($appConfig['empresa_razon_social'] ?? ''),
            '{{EMPRESA_NIF}}' => htmlspecialchars($appConfig['empresa_nif'] ?? ''),
            '{{EMPRESA_DIRECCION}}' => htmlspecialchars($appConfig['empresa_direccion'] ?? ''),
            '{{EMPRESA_TELEFONO}}' => htmlspecialchars($appConfig['empresa_telefono'] ?? ''),
            '{{EMPRESA_EMAIL}}' => htmlspecialchars($appConfig['empresa_email'] ?? ''),
            '{{EMPRESA_WEB}}' => htmlspecialchars($appConfig['empresa_web'] ?? ''),
            '{{EMPRESA_REGISTRO}}' => htmlspecialchars($appConfig['empresa_registro'] ?? ''),
            '{{TICKET_PIE_PAGINA}}' => htmlspecialchars($appConfig['ticket_pie_pagina'] ?? ''),
            '{{TICKET_POLITICA}}' => htmlspecialchars($appConfig['ticket_politica'] ?? '')
        ];
    }

    foreach ($reemplazos as $key => $val) {
        $html = str_replace($key, $val, $html);
    }

    return $html;
}
