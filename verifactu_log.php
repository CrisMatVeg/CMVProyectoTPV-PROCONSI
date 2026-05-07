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

$tab = $_GET['tab'] ?? 'logs';
$queueService = new AeatQueueService();

if ($tab === 'queue') {
    $logs = $queueService->listarColaPendiente();
} else {
    $logs = $queueService->listarUltimosMovimientos(50);
}

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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            --orange: #f97316;
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
            margin-bottom: 20px; 
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

        /* Estilos de pestañas */
        .tabs-container {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 0;
        }

        .tab-btn {
            padding: 12px 20px;
            border: none;
            background: none;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-light);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tab-btn:hover {
            color: var(--primary);
            background: var(--primary-light);
        }

        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .tab-count {
            background: var(--border);
            color: var(--text);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
        }

        .tab-btn.active .tab-count {
            background: var(--primary);
            color: white;
        }

        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(4, 1fr); 
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
        .badge-bloqueado { background: #e2e8f0; color: #475569; }

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

        .empty-state {
            padding: 60px 40px;
            text-align: center;
            color: var(--text-light);
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.3;
        }
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

        // ─── LIVE UPDATE POLLING ───────────────────────────────────────────────────
        const currentTab = '<?= $tab ?>';
        let liveDataStore = { queue: [], logs: [] };

        function fmtBadge(estado) {
            const map = {
                'enviado':       '<span class="badge badge-enviado">enviado</span>',
                'pendiente':     '<span class="badge badge-pendiente">pendiente</span>',
                'error_critico': '<span class="badge badge-error_critico">error_critico</span>',
                'bloqueado':     '<span class="badge badge-bloqueado">bloqueado</span>',
            };
            return map[estado] || `<span class="badge">${estado}</span>`;
        }

        function fmtTime(ts) {
            if (!ts) return '—';
            const d = new Date(ts.replace(' ', 'T'));
            if (isNaN(d.getTime())) return ts;
            return d.toLocaleTimeString('es-ES', {hour:'2-digit', minute:'2-digit', second:'2-digit'}) + 
                   '<div class="small-text">' + d.toLocaleDateString('es-ES') + '</div>';
        }

        function renderStatsCards(stats) {
            const map = {
                'stats-enviados': stats.enviados,
                'stats-pendientes': stats.pendientes,
                'stats-errores': stats.errores,
                'stats-bloqueados': stats.bloqueados,
                'tab-queue-count': (+stats.pendientes||0) + (+stats.bloqueados||0) + (+stats.errores||0)
            };
            for (let id in map) {
                const el = document.getElementById(id);
                if (el) el.textContent = (map[id] || 0).toLocaleString();
            }
        }

        function renderQueueBanner(stats) {
            const banner = document.getElementById('queue-status-banner');
            if (!banner) return;
            const isStopped = stats.errores > 0;
            banner.className = 'alert-mini ' + (isStopped ? 'alert-mini-error' : 'alert-mini-success');
            const icon = isStopped ? 'fa-circle-pause' : 'fa-circle-play';
            const text = isStopped
                ? 'COLA DETENIDA: Requiere subsanación manual del primer error.'
                : 'COLA ACTIVA: Los registros se enviarán automáticamente según lo programado.';
            banner.innerHTML = `
                <i class="fa-solid ${icon} fs-24"></i>
                <div>
                    <div style="font-weight:800;text-transform:uppercase;font-size:12px;letter-spacing:0.5px;">${text}</div>
                    <div class="small-text" style="margin-top:2px;color:inherit;opacity:.8;">
                        ${stats.pendientes||0} listos para enviar, ${stats.bloqueados||0} bloqueados en espera, ${stats.errores||0} con error crítico.
                    </div>
                </div>`;
        }

        function renderTableRows(rows, isQueue) {
            const tbody = document.getElementById('main-tbody');
            if (!tbody) return;

            if (!rows || rows.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6">
                    <div class="empty-state">
                        <i class="fa-solid ${isQueue ? 'fa-circle-check' : 'fa-folder-open'}"></i>
                        <p>${isQueue ? 'No hay envíos pendientes en la cola automática.' : 'No hay registros en el historial.'}</p>
                    </div></td></tr>`;
                return;
            }

            tbody.innerHTML = rows.map((row, index) => {
                const rowClass = row.estado === 'error_critico' ? 'row-error' : (row.estado === 'pendiente' && row.intentos > 0 ? 'row-warning' : '');
                const timeCol = isQueue
                    ? (row.fecha_proximo_intento ? fmtTime(row.fecha_proximo_intento) : '<span style="color:var(--primary);font-weight:600;">Inmediato</span>')
                    : (row.fecha_envio ? fmtTime(row.fecha_envio) : '—');
                
                const errorText = row.ultimo_error || '—';
                const msgCol = errorText.length > 80 ? `<span title="${errorText.replace(/"/g, '&quot;')}">${errorText.substring(0,80)}…</span>` : errorText;
                
                const storeKey = isQueue ? 'queue' : 'logs';
                
                const subsanarBtn = (row.estado === 'error_critico' && isQueue)
                    ? `<button class="btn-view" style="background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;"
                           onclick="handleSubsanarFromStore('${storeKey}', ${index})">Subsanar</button>`
                    : '';

                return `<tr class="${rowClass}">
                    <td class="text-mono"><strong>#${row.numero_ticket || row.id_venta}</strong><div class="small-text">ID Venta: ${row.id_venta}</div></td>
                    <td>${fmtBadge(row.estado)}</td>
                    <td style="text-align:center;">${row.intentos || 0}</td>
                    <td>${timeCol}</td>
                    <td style="max-width:300px;font-size:13px;">${msgCol}</td>
                    <td><div style="display:flex;gap:5px;">
                        <button class="btn-view" onclick="viewFromStore('${storeKey}', ${index}, 'req')">XML</button>
                        <button class="btn-view" onclick="viewFromStore('${storeKey}', ${index}, 'resp')">RESP</button>
                        ${subsanarBtn}
                    </div></td>
                </tr>`;
            }).join('');
        }

        function handleSubsanarFromStore(key, index) {
            const item = liveDataStore[key][index];
            if (!item) return;
            
            // Si es un error crítico local (archivo no encontrado), mejor regenerar
            const isLocalError = (item.ultimo_error || '').includes('Archivo XML no encontrado');
            const isFactura = !!item.es_factura;

            if (item.estado === 'error_critico' && !isFactura && !isLocalError) {
                // Para tickets con error crítico, solemos regenerar/reintentar
                confirmarRegeneracion(item.id_venta);
            } else {
                // Abrir modal de subsanación
                abrirModalSubsanacion(
                    item.id_venta, 
                    item.numero_ticket || item.id_venta, 
                    item.nombre_cliente || '', 
                    item.nif_cliente || '', 
                    (item.estado === 'error_critico' ? 'S' : 'N'), 
                    '', // sugerencia aeat
                    isFactura
                );
            }
        }

        function viewFromStore(key, index, type) {
            const item = liveDataStore[key][index];
            if (!item) return;
            if (type === 'req') {
                showModal('XML Solicitud', item.xml_content || 'No disponible');
            } else {
                showModal('Respuesta AEAT', item.respuesta_aeat || 'No disponible');
            }
        }

        // Polling activo solo cuando hay ítems en cola; 3 s entre actualizaciones
        let _pollTimer = null;
        let _queueActive = (<?= (int)(($stats['pendientes'] ?? 0) + ($stats['errores'] ?? 0) + ($stats['bloqueados'] ?? 0)) ?> > 0);

        function schedulePoll() {
            clearTimeout(_pollTimer);
            if (_queueActive) {
                _pollTimer = setTimeout(pollData, 3000);
            } else {
                const ind = document.getElementById('live-indicator');
                if (ind) ind.textContent = '○ Sin actividad';
            }
        }

        function pollData() {
            fetch('api/verifactu_stats.php?accion=queue_data&_t=' + Date.now())
                .then(r => r.json())
                .then(data => {
                    if (!data.ok) return;
                    liveDataStore.queue = data.queue || [];
                    liveDataStore.logs = data.logs || [];

                    const s = data.stats || {};
                    _queueActive = ((+s.pendientes || 0) + (+s.errores || 0) + (+s.bloqueados || 0)) > 0;

                    renderStatsCards(s);
                    if (currentTab === 'queue') {
                        renderQueueBanner(s);
                        renderTableRows(liveDataStore.queue, true);
                    } else {
                        renderTableRows(liveDataStore.logs, false);
                    }

                    const ind = document.getElementById('live-indicator');
                    if (ind) ind.textContent = '● Actualizado ' + new Date().toLocaleTimeString('es-ES');
                })
                .catch(e => console.error('Poll error:', e))
                .finally(() => schedulePoll());
        }

        // Primer fetch inmediato, luego el bucle adaptativo lo releva
        pollData();
    </script>
</head>
<body>

<div class="container">
    <div class="header">
        <h1><i class="fa-solid fa-microchip"></i> Auditoría VeriFactu · AEAT</h1>
        <div style="display: flex; gap: 10px; align-items: center;">
            <span id="live-indicator" style="font-size:11px;color:var(--green);font-weight:600;">● En vivo</span>
            <button onclick="pollData()" class="btn-view" style="background: white; border: 1px solid var(--border);"><i class="fa-solid fa-sync"></i> Actualizar</button>
            <button onclick="window.close()" class="btn-view" style="background: var(--text); color: white;"><i class="fa-solid fa-xmark"></i> Cerrar</button>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <span class="label">Tickets Enviados</span>
            <span class="value" id="stats-enviados" style="color: var(--green);"><?= number_format($stats['enviados'] ?? 0) ?></span>
        </div>
        <div class="stat-card">
            <span class="label">Pendientes / Reenvío</span>
            <span class="value" id="stats-pendientes" style="color: var(--yellow);"><?= number_format($stats['pendientes'] ?? 0) ?></span>
        </div>
        <div class="stat-card">
            <span class="label">Errores Críticos</span>
            <span class="value" id="stats-errores" style="color: var(--red);"><?= number_format($stats['errores'] ?? 0) ?></span>
        </div>
        <div class="stat-card">
            <span class="label">Bloqueados</span>
            <span class="value" id="stats-bloqueados" style="color: var(--text-light);"><?= number_format($stats['bloqueados'] ?? 0) ?></span>
        </div>
    </div>

    <div class="tabs-container">
        <button onclick="location.href='?tab=logs'" class="tab-btn <?= $tab === 'logs' ? 'active' : '' ?>">
            <i class="fa-solid fa-list-ul"></i> Historial de Movimientos
        </button>
        <button onclick="location.href='?tab=queue'" class="tab-btn <?= $tab === 'queue' ? 'active' : '' ?>">
            <i class="fa-solid fa-clock"></i> Cola de Reenvío Automático
            <span class="tab-count" id="tab-queue-count"><?= ($stats['pendientes'] + $stats['bloqueados'] + $stats['errores']) ?></span>
        </button>
    </div>

    <?php if ($tab === 'queue'): ?>
        <?php 
            $isStopped = ($stats['errores'] > 0); 
            $statusClass = $isStopped ? 'alert-mini-error' : 'alert-mini-success';
            $statusIcon = $isStopped ? 'fa-circle-pause' : 'fa-circle-play';
            $statusText = $isStopped ? 'COLA DETENIDA: Requiere subsanación manual del primer error.' : 'COLA ACTIVA: Los registros se enviarán automáticamente según lo programado.';
        ?>
        <div id="queue-status-banner" class="alert-mini <?= $statusClass ?>" style="margin-bottom: 20px; display: flex; align-items: center; gap: 12px; padding: 16px;">
            <i class="fa-solid <?= $statusIcon ?> fs-24"></i>
            <div>
                <div style="font-weight: 800; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;"><?= $statusText ?></div>
                <div class="small-text" style="margin-top: 2px; color: inherit; opacity: 0.8;">
                    <?= $stats['pendientes'] ?> listos para enviar, <?= $stats['bloqueados'] ?> bloqueados en espera, <?= $stats['errores'] ?> con error crítico.
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <table>
            <thead>
            <tr>
                <th>Ticket / ID</th>
                <th>Estado</th>
                <th>Nº Intentos</th>
                <th><?= $tab === 'queue' ? 'Programado para' : 'Última Transmisión' ?></th>
                <th><?= $tab === 'queue' ? 'Motivo espera / Error' : 'Último Mensaje' ?></th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody id="main-tbody">
            <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="fa-solid <?= $tab === 'queue' ? 'fa-circle-check text-green' : 'fa-folder-open' ?>"></i>
                            <p><?= $tab === 'queue' ? 'No hay envíos pendientes en la cola automática.' : 'No hay registros en el historial.' ?></p>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($logs as $row): 
                    $xmlReq = file_exists($row['xml_path'] ?? '') ? file_get_contents($row['xml_path']) : 'No disponible';
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
                            <?php if ($tab === 'queue'): ?>
                                <span style="color: var(--primary); font-weight: 600;">
                                    <?= $row['fecha_proximo_intento'] ? date('H:i:s', strtotime($row['fecha_proximo_intento'])) : 'Inmediato' ?>
                                </span>
                                <div class="small-text"><?= $row['fecha_proximo_intento'] ? date('d/m/Y', strtotime($row['fecha_proximo_intento'])) : 'Sig. ejecución' ?></div>
                            <?php else: ?>
                                <?= $row['fecha_envio'] ? date('d/m H:i:s', strtotime($row['fecha_envio'])) : '—' ?>
                                <?php if ($row['estado'] === 'pendiente' && $row['fecha_proximo_intento']): ?>
                                    <div class="small-text" style="color: var(--yellow);">Retry: <?= date('H:i', strtotime($row['fecha_proximo_intento'])) ?></div>
                                <?php endif; ?>
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

<!-- MODAL SUBSANACIÓN VERIFACTU -->
<div id="modalSubsanacion" class="modal-overlay-bg" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div class="modal-content" style="background:white; padding:0; border-radius:12px; width:100%; max-width:500px; overflow:hidden; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <div style="padding:20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; background:#f8fafc;">
            <h3 style="margin:0; font-size:18px;">Subsanar Registro <span id="subTicketBadge" style="background:#fef3c7; color:#92400e; padding:2px 8px; border-radius:6px; font-size:14px; margin-left:10px;"></span></h3>
            <button onclick="cerrarModalSubsanacion()" style="border:none; background:none; font-size:24px; cursor:pointer; color:#64748b;">&times;</button>
        </div>
        <div style="padding:24px;">
            <input type="hidden" id="subVentaId" value="">
            <input type="hidden" id="subTicketId" value="">
            <input type="hidden" id="subRechazoPrevio" value="N">
            
            <div id="subGroupNombre" style="margin-bottom:16px;">
                <label style="display:block; font-size:12px; font-weight:700; color:#64748b; margin-bottom:6px; text-transform:uppercase;">Nombre/Razón Social Cliente</label>
                <input type="text" id="subNombreCliente" style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px;" placeholder="Ej: Empresa S.L.">
            </div>
            
            <div id="subGroupNif" style="margin-bottom:16px;">
                <label style="display:block; font-size:12px; font-weight:700; color:#64748b; margin-bottom:6px; text-transform:uppercase;">NIF Cliente</label>
                <input type="text" id="subNifCliente" style="width:100%; padding:10px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px;" placeholder="Ej: B12345678">
            </div>
            
            <p style="font-size:13px; color:#64748b; font-style:italic; margin:0;">
                <i class="fa-solid fa-circle-info" style="margin-right:5px;"></i> Se enviará un nuevo registro de Alta indicando Subsanación.
            </p>
        </div>
        <div style="padding:20px; border-top:1px solid #eee; display:flex; justify-content:flex-end; gap:12px; background:#f8fafc;">
            <button onclick="cerrarModalSubsanacion()" style="padding:10px 20px; border:1px solid #e2e8f0; border-radius:8px; background:white; cursor:pointer; font-weight:600;">Cancelar</button>
            <button onclick="confirmarSubsanacion()" style="padding:10px 20px; border:none; border-radius:8px; background:var(--orange); color:white; cursor:pointer; font-weight:600;">Subsanar Registro</button>
        </div>
    </div>
</div>

<script>
    let currentSubsanacionId = null;
    let _isFacturaSub = false;

    function abrirModalSubsanacion(idVenta, numTicket, nombre, nif, rechazoPrevio = 'N', aeatNombreSugerido = '', isFactura = false) {
        currentSubsanacionId = idVenta;
        _isFacturaSub = isFactura;
        
        document.getElementById('subVentaId').value = idVenta;
        document.getElementById('subTicketId').value = numTicket;
        document.getElementById('subTicketBadge').innerText = 'T-' + numTicket;
        document.getElementById('subNombreCliente').value = nombre || '';
        document.getElementById('subNifCliente').value = nif || '';
        document.getElementById('subRechazoPrevio').value = rechazoPrevio;

        document.getElementById('subGroupNombre').style.display = isFactura ? 'block' : 'none';
        document.getElementById('subGroupNif').style.display    = isFactura ? 'block' : 'none';

        document.getElementById('modalSubsanacion').style.display = 'flex';
    }

    function cerrarModalSubsanacion() {
        document.getElementById('modalSubsanacion').style.display = 'none';
    }

    async function confirmarSubsanacion() {
        const idVenta = currentSubsanacionId;
        const nombre = document.getElementById('subNombreCliente').value.trim();
        const nif = document.getElementById('subNifCliente').value.trim();
        const rechazoPrevio = document.getElementById('subRechazoPrevio').value;

        if (_isFacturaSub && (!nombre || !nif)) {
            Swal.fire('Atención', 'Para facturas completas el nombre y NIF son obligatorios', 'warning');
            return;
        }

        Swal.fire({
            title: 'Procesando...',
            didOpen: () => Swal.showLoading()
        });

        try {
            const resp = await fetch('api/verifactuSubsanar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ idVenta, nombre, nif, rechazoPrevio })
            });
            const data = await resp.json();
            if (data.ok) {
                Swal.fire('Éxito', data.msg, 'success').then(() => {
                    cerrarModalSubsanacion();
                    loadLiveData();
                });
            } else {
                Swal.fire('Error', data.msg, 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Error de conexión', 'error');
        }
    }

    async function confirmarRegeneracion(idVenta) {
        const res = await Swal.fire({
            title: '¿Regenerar registro?',
            text: 'Se intentará regenerar el archivo XML y enviarlo de nuevo a la AEAT.',
            icon: 'question',
            showCancelButton: true
        });
        if (!res.isConfirmed) return;

        Swal.fire({ title: 'Procesando...', didOpen: () => Swal.showLoading() });

        try {
            const resp = await fetch('api/verifactuSubsanar.php', { // Reutilizamos la misma API pero sin cambios de nombre/nif
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ idVenta, nombre: '', nif: '', rechazoPrevio: 'N' })
            });
            const data = await resp.json();
            if (data.ok) {
                Swal.fire('Éxito', 'Registro regenerado y encolado', 'success').then(loadLiveData);
            } else {
                Swal.fire('Error', data.msg, 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Error de conexión', 'error');
        }
    }
</script>
</body>
</html>
