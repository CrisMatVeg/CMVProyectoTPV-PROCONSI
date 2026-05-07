<?php
require_once __DIR__ . '/csrf_check.php';

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: text/html; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';
    require_once __DIR__ . '/../model/ConfiguracionPDO.php';
    require_once __DIR__ . '/../model/CierreFiscalPDO.php';

    // session_start(); // Handled by csrf_check.php
    if (!isset($_SESSION['usuarioActualTPV'])) {
        http_response_code(401);
        echo "No autorizado";
        exit;
    }

    $idCierre = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($idCierre <= 0) throw new Exception('ID de cierre inválido');

    $cierre = CierreFiscalPDO::obtenerCierrePorId($idCierre);
    if (!$cierre) throw new Exception('Cierre no encontrado');

    $qTurnos = DBPDO::ejecutarConsulta(
        "SELECT ct.*, u1.nombre as nombre_usuario_apertura, u2.nombre as nombre_usuario_cierre
         FROM caja_turnos ct LEFT JOIN usuarios u1 ON ct.id_usuario_apertura = u1.id LEFT JOIN usuarios u2 ON ct.id_usuario_cierre = u2.id
         WHERE ct.num_z = :id ORDER BY ct.fecha_apertura ASC", [':id' => $idCierre]
    );
    $turnos = $qTurnos->fetchAll(PDO::FETCH_ASSOC);

    $qMovs = DBPDO::ejecutarConsulta(
        "SELECT cm.*, u.nombre as nombre_usuario 
         FROM caja_movimientos cm LEFT JOIN usuarios u ON cm.id_usuario = u.id
         WHERE cm.id_turno IN (SELECT id FROM caja_turnos WHERE num_z = :id) ORDER BY cm.created_at ASC", [':id' => $idCierre]
    );
    $movimientos = $qMovs->fetchAll(PDO::FETCH_ASSOC);

    $appConfig = ConfiguracionPDO::obtenerConfiguracion();
    $templatePath = __DIR__ . '/../cierres-electrobazar.html';
    if (!file_exists($templatePath)) throw new Exception("Plantilla no encontrada");
    $html = file_get_contents($templatePath);
    $fmt = fn($n) => number_format((float)$n, 2, ',', '.') . ' €';

    $rango = 'Sin actividad';
    if (!empty($cierre['primera_venta']) && !empty($cierre['ultima_venta'])) {
        $rango = date('H:i', strtotime($cierre['primera_venta'])) . 'h → ' . date('H:i', strtotime($cierre['ultima_venta'])) . 'h';
    }

    $deuda = (float)($cierre['deuda_generada'] ?? 0);
    $deudaStr = ($deuda >= 0 ? '+' : '') . $fmt($deuda);
    $deudaColor = $deuda > 0 ? '#dc2626' : '#16a34a';

    $turnosRows = '';
    foreach ($turnos as $t) {
        $hApe = !empty($t['fecha_apertura']) ? date('H:i', strtotime($t['fecha_apertura'])) . 'h' : '-';
        $hCie = !empty($t['fecha_cierre']) ? date('H:i', strtotime($t['fecha_cierre'])) . 'h' : 'Abierto';
        $resp = htmlspecialchars($t['nombre_usuario_apertura'] ?? '?');
        $respCie = (!empty($t['nombre_usuario_cierre']) && $t['nombre_usuario_cierre'] !== $t['nombre_usuario_apertura']) ? ' / ' . htmlspecialchars($t['nombre_usuario_cierre']) : '';
        $efectivo = $fmt($t['efectivo_real'] ?? 0);
        $turnosRows .= "<tr><td>{$hApe}</td><td>{$hCie}</td><td>{$resp}{$respCie}</td><td class='text-right font-bold'>{$efectivo}</td></tr>";
    }
    if (empty($turnosRows)) $turnosRows = "<tr><td colspan='4' style='text-align:center; padding:12px; color:#999;'>Sin turnos registrados</td></tr>";

    $movsRows = '';
    foreach ($movimientos as $m) {
        $hora = !empty($m['created_at']) ? date('H:i', strtotime($m['created_at'])) . 'h' : '-';
        $tipo = ($m['tipo'] === 'retiro') ? '<span style="color:#dc2626;">[R]</span>' : '<span style="color:#16a34a;">[I]</span>';
        $concepto = htmlspecialchars($m['concepto'] ?? 'Operación de caja');
        $importe = $fmt($m['importe'] ?? 0);
        $movsRows .= "<tr><td class='font-mono'>{$hora}</td><td>{$tipo} {$concepto}</td><td class='text-right font-bold'>{$importe}</td></tr>";
    }
    if (empty($movsRows)) $movsRows = "<tr><td colspan='3' style='text-align:center; padding:12px; color:#999;'>Sin movimientos adicionales</td></tr>";

    $reemplazos = [
        '{{EMPRESA_NOMBRE}}' => htmlspecialchars($appConfig['empresa_nombre'] ?? 'ElectroBazar'),
        '{{CIERRE_NUM}}' => '#Cierre-' . str_pad($cierre['id'], 3, '0', STR_PAD_LEFT),
        '{{FECHA}}' => date('d/m/Y H:i', strtotime($cierre['fecha'])),
        '{{RESPONSABLE}}' => htmlspecialchars($cierre['nombre_usuario'] ?? 'Sistema'),
        '{{RANGO}}' => htmlspecialchars($rango),
        '{{NUM_TICKETS}}' => (int)($cierre['num_tickets'] ?? 0),
        '{{DEUDA}}' => $deudaStr,
        '{{DEUDA_COLOR}}' => $deudaColor,
        '{{TOTAL_EFECTIVO}}' => $fmt($cierre['total_efectivo']),
        '{{TOTAL_TARJETA}}' => $fmt($cierre['total_tarjeta']),
        '{{TOTAL_BIZUM}}' => $fmt($cierre['total_bizum'] ?? 0),
        '{{TOTAL_FINANCIADO}}' => $fmt($cierre['total_a_cuenta'] ?? 0),
        '{{TOTAL_GENERAL}}' => $fmt($cierre['total_general']),
        '{{TURNOS_ROWS}}' => $turnosRows,
        '{{RETIROS_ROWS}}' => $movsRows,
        '{{FECHA_GENERACION}}' => date('d/m/Y H:i'),
    ];
    foreach ($reemplazos as $key => $val) { $html = str_replace($key, $val, $html); }
    $html .= "<script>window.onload = function() { window.print(); }; window.onafterprint = function() { window.close(); };</script>";
    echo $html;
} catch (Throwable $e) { echo "<h2>Error</h2><p>" . htmlspecialchars($e->getMessage()) . "</p>"; }