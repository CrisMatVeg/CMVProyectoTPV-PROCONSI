<?php
/**
 * VeriFactu Visual Log Viewer
 * Dashboard administrativo para monitorear los envíos a la AEAT.
 */

require_once("./config/confDBPDO.php");
require_once("./model/DBPDO.php");
require_once("./model/Usuario.php");
require_once("./model/AeatQueueService.php");

session_start();
if (!isset($_SESSION['usuarioActualTPV']) || $_SESSION['usuarioActualTPV']->getRol() !== 'admin') {
    die("Acceso denegado. Se requiere perfil administrador.");
}

$queueService = new AeatQueueService();
$logs = $queueService->listarUltimosMovimientos(50);
$stats = $queueService->obtenerEstadisticas();

/**
 * Parsea el XML de respuesta de la AEAT para extraer un resumen legible.
 */
function parseAeatResponse($xmlString) {
    if (empty($xmlString) || strpos($xmlString, '<?xml') === false) return null;
    
    try {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlString);
        if (!$xml) return null;

        $namespaces = $xml->getNamespaces(true);
        $xml->registerXPathNamespace('r', 'https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/RespuestaSuministro.xsd');
        $xml->registerXPathNamespace('env', 'http://schemas.xmlsoap.org/soap/envelope/');

        // 1. Verificar si es un SOAP Fault
        $faults = $xml->xpath('//env:Fault');
        if (!empty($faults)) {
            $msg = (string)$xml->xpath('//faultstring')[0] ?? 'Fallo SOAP';
            return ['status' => 'Error Crítico (SOAP)', 'message' => $msg, 'is_error' => true];
        }

        // 2. Extraer estado y descripción del registro
        $estado = (string)$xml->xpath('//r:EstadoRegistro')[0] ?? 'Desconocido';
        $errorDesc = (string)$xml->xpath('//r:DescripcionErrorRegistro')[0] ?? '';
        $errorCode = (string)$xml->xpath('//r:CodigoErrorRegistro')[0] ?? '';

        return [
            'status' => $estado,
            'message' => $errorDesc ? "($errorCode) $errorDesc" : "Operación finalizada.",
            'is_error' => ($estado !== 'Correcto'),
            'code' => $errorCode
        ];
    } catch (Exception $e) {
        return null;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría VeriFactu · AEAT Logs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f8fafc;
            --surface: #ffffff;
            --primary: #2563eb;
            --primary-light: #eff6ff;
            --text: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --green: #10b981;
            --red: #ef4444;
            --yellow: #f59e0b;
        }

        * { box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg); 
            color: var(--text); 
            margin: 0; 
            padding: 20px; 
            line-height: 1.5;
        }

        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
        }

        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
        }

        .header h1 { 
            margin: 0; 
            font-size: 24px; 
            font-weight: 700; 
            display: flex; 
            align-items: center; 
            gap: 12px;
        }

        .header h1 i { color: var(--primary); }

        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(3, 1fr); 
            gap: 20px; 
            margin-bottom: 30px; 
        }

        .stat-card { 
            background: var(--surface); 
            padding: 20px; 
            border-radius: 12px; 
            border: 1px solid var(--border); 
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .stat-card .label { 
            font-size: 13px; 
            color: var(--text-light); 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            margin-bottom: 8px; 
            display: block;
        }

        .stat-card .value { 
            font-size: 28px; 
            font-weight: 700; 
            font-family: 'JetBrains Mono', monospace;
        }

        .card { 
            background: var(--surface); 
            border-radius: 12px; 
            border: 1px solid var(--border); 
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); 
            overflow: hidden; 
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 14px; 
        }

        th { 
            text-align: left; 
            padding: 16px; 
            background: #f1f5f9; 
            color: var(--text-light); 
            font-weight: 600; 
            border-bottom: 1px solid var(--border);
        }

        td { 
            padding: 16px; 
            border-bottom: 1px solid var(--border); 
        }

        tr:hover { background: #fafafa; }

        .badge { 
            padding: 4px 10px; 
            border-radius: 20px; 
            font-size: 11px; 
            font-weight: 700; 
            text-transform: uppercase;
        }

        .badge-enviado { background: #d1fae5; color: #065f46; }
        .badge-pendiente { background: #fef3c7; color: #92400e; }
        .badge-error_critico { background: #fee2e2; color: #991b1b; }

        tr.row-error { background-color: #fff1f2; }
        tr.row-warning { background-color: #fffbeb; }

        .btn-view { 
            background: var(--primary-light); 
            color: var(--primary); 
            border: none; 
            padding: 6px 12px; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 12px; 
            font-weight: 600; 
            transition: all 0.2s;
        }

        .btn-view:hover { 
            background: var(--primary); 
            color: white; 
        }

        .modal { 
            display: none; 
            position: fixed; 
            top: 0; left: 0; 
            width: 100%; height: 100%; 
            background: rgba(0,0,0,0.5); 
            z-index: 1000; 
            align-items: center; 
            justify-content: center; 
        }

        .modal-content { 
            background: white; 
            width: 95%; 
            max-width: 900px; 
            max-height: 90vh; 
            border-radius: 12px; 
            display: flex; 
            flex-direction: column; 
            overflow: hidden;
        }

        .modal-header { 
            padding: 20px; 
            border-bottom: 1px solid var(--border); 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }

        .modal-body { 
            padding: 0; 
            overflow-y: auto; 
            background: #f8fafc;
            display: flex;
            flex-direction: column;
        }

        .modal-summary {
            padding: 20px;
            background: #ffffff;
            border-bottom: 1px solid var(--border);
        }

        .code-block {
            padding: 20px;
            background: #1e1e1e; 
            color: #d4d4d4; 
            font-family: 'JetBrains Mono', monospace; 
            font-size: 13px; 
            white-space: pre-wrap; 
            margin: 0;
            flex-grow: 1;
        }

        .alert-mini {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 12px;
            font-size: 13px;
        }
        .alert-mini-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-mini-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }

        .close { 
            cursor: pointer; 
            font-size: 24px; 
            color: var(--text-light); 
        }

        .text-mono { font-family: 'JetBrains Mono', monospace; }
        .small-text { font-size: 12px; color: var(--text-light); margin-top: 4px; }
    </style>
    <script>
        function showModal(title, content, parsed = null) {
            document.getElementById('modalTitle').innerText = title;
            document.getElementById('modalContent').innerText = content;
            
            const summaryDiv = document.getElementById('modalSummary');
            if (parsed) {
                summaryDiv.style.display = 'block';
                const alertClass = parsed.is_error ? 'alert-mini-error' : 'alert-mini-success';
                const icon = parsed.is_error ? 'fa-triangle-exclamation' : 'fa-circle-check';
                
                summaryDiv.innerHTML = `
                    <div class="alert-mini ${alertClass}">
                        <div style="font-weight: 700; margin-bottom: 4px;">
                            <i class="fa-solid ${icon}"></i> Estado AEAT: ${parsed.status}
                        </div>
                        <div>${parsed.message}</div>
                    </div>
                    <div class="small-text">Abajo se muestra el XML completo de la respuesta recibida:</div>
                `;
            } else {
                summaryDiv.style.display = 'none';
            }
            
            document.getElementById('modal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('modal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('modal')) closeModal();
        }
    </script>
</head>
<body>

<div class="container">
    <div class="header">
        <h1><i class="fa-solid fa-microchip"></i> Auditoría VeriFactu · AEAT</h1>
        <div style="display: flex; gap: 10px;">
            <button onclick="location.reload()" class="btn-view" style="background: white; border: 1px solid var(--border);"><i class="fa-solid fa-sync"></i> Actualizar</button>
            <button onclick="window.close()" class="btn-view" style="background: var(--text); color: white;"><i class="fa-solid fa-xmark"></i> Cerrar</button>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <span class="label">Tickets Enviados</span>
            <span class="value" style="color: var(--green);"><?= number_format($stats['enviados'] ?? 0) ?></span>
        </div>
        <div class="stat-card">
            <span class="label">En Cola (Pendientes)</span>
            <span class="value" style="color: var(--yellow);"><?= number_format($stats['pendientes'] ?? 0) ?></span>
        </div>
        <div class="stat-card">
            <span class="label">Errores Críticos</span>
            <span class="value" style="color: var(--red);"><?= number_format($stats['errores'] ?? 0) ?></span>
        </div>
    </div>

    <div class="card">
        <table>
            <thead>
            <tr>
                <th>Ticket / ID</th>
                <th>Estado</th>
                <th>Nº Intentos</th>
                <th>Última Transmisión</th>
                <th>Último Mensaje</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($logs)): ?>
                <tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--text-light);">No hay registros en la cola de envíos.</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $row): 
                    $xmlReq = file_exists($row['xml_path']) ? file_get_contents($row['xml_path']) : 'No disponible';
                    $xmlResp = $row['respuesta_aeat'] ?: 'No hay respuesta registrada.';
                    $parsed = parseAeatResponse($row['respuesta_aeat']);
                    
                    $rowClass = '';
                    if ($row['estado'] === 'error_critico' || ($parsed && $parsed['is_error'])) $rowClass = 'row-error';
                    elseif ($row['estado'] === 'pendiente' && $row['intentos'] > 0) $rowClass = 'row-warning';
                ?>
                    <tr class="<?= $rowClass ?>">
                        <td class="text-mono">
                            <strong>#<?= htmlspecialchars($row['numero_ticket']) ?></strong>
                            <div class="small-text">ID Venta: <?= $row['id_venta'] ?></div>
                        </td>
                        <td><span class="badge badge-<?= $row['estado'] ?>"><?= $row['estado'] ?></span></td>
                        <td style="text-align: center;"><?= $row['intentos'] ?></td>
                        <td>
                            <?= $row['fecha_envio'] ? date('d/m H:i:s', strtotime($row['fecha_envio'])) : '—' ?>
                            <?php if ($row['estado'] === 'pendiente' && $row['fecha_proximo_intento']): ?>
                                <div class="small-text" style="color: var(--yellow);">Retry: <?= date('H:i', strtotime($row['fecha_proximo_intento'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="max-width: 300px; font-size: 13px;" title="<?= htmlspecialchars($row['ultimo_error'] ?? '') ?>">
                            <?php if ($parsed): ?>
                                <strong style="color: <?= $parsed['is_error'] ? 'var(--red)' : 'var(--green)' ?>"><?= $parsed['status'] ?></strong>
                                <div class="small-text"><?= htmlspecialchars($row['ultimo_error'] ?? '') ?></div>
                            <?php else: ?>
                                <?= htmlspecialchars($row['ultimo_error'] ?? '—') ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <button class="btn-view" onclick='showModal("XML Solicitud", <?= json_encode($xmlReq) ?>)'>XML</button>
                                <button class="btn-view" onclick='showModal("Respuesta AEAT", <?= json_encode($xmlResp) ?>, <?= json_encode($parsed) ?>)'>RESP</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle" style="margin: 0; font-size: 16px;">Contenido</h3>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body" id="modalBody">
            <div id="modalSummary" class="modal-summary" style="display: none;"></div>
            <pre id="modalContent" class="code-block"></pre>
        </div>
    </div>
</div>

</body>
</html>
