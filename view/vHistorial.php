</header>
<div class="main-full p-24">
    
    <!-- CABECERA DE SECCIÓN -->
    <div class="section-header container">
        <div class="section-title">
            <h1>Historial TPV</h1>
            <p>Auditoría de ventas y reportes de cierre de caja.</p>
        </div>
        <div class="cat-tabs mt-10">
            <a href="index.php?verCierres=0" class="cat-tab <?php echo !$avHistorial['verCierres'] ? 'active' : ''; ?>" style="text-decoration: none;">
                <i class="fa-solid fa-receipt"></i> Ventas
            </a>
            <a href="index.php?verCierres=1" class="cat-tab <?php echo $avHistorial['verCierres'] ? 'active' : ''; ?>" style="text-decoration: none;">
                <i class="fa-solid fa-file-invoice-dollar"></i> Cierres (Reporte Z)
            </a>
        </div>
        <form method="post">
            <button type="submit" name="volver" class="btn-icon w-auto h-auto gap-8 fs-14 p-10-20">
                <i class="fa-solid fa-house"></i> Dashboard
            </button>
        </form>
    </div>

    <?php if (!$avHistorial['verCierres']): ?>

    <!-- PANEL DE FILTROS -->
    <div class="filters-panel container">
        <form method="get" action="index.php" class="filters-form" novalidate>
            <div class="filter-group">
                <label>Desde</label>
                <input type="text" name="fechaDesde" value="<?php echo $avHistorial['filtros']['desde']; ?>" class="filter-input" placeholder="YYYY-MM-DD">
                <?php if (isset($avHistorial['aErrores']['fechaDesde']) && $avHistorial['aErrores']['fechaDesde'] != null) { ?>
                    <span class="form-error"><?php echo $avHistorial['aErrores']['fechaDesde']; ?></span>
                <?php } ?>
            </div>
            <div class="filter-group">
                <label>Hasta</label>
                <input type="text" name="fechaHasta" value="<?php echo $avHistorial['filtros']['hasta']; ?>" class="filter-input" placeholder="YYYY-MM-DD">
                <?php if (isset($avHistorial['aErrores']['fechaHasta']) && $avHistorial['aErrores']['fechaHasta'] != null) { ?>
                    <span class="form-error"><?php echo $avHistorial['aErrores']['fechaHasta']; ?></span>
                <?php } ?>
            </div>
            <div class="filter-group w-200">
                <label>Cajero</label>
                <select name="idCajero" class="filter-input">
                    <option value="">Todos los cajeros</option>
                    <?php foreach ($avHistorial['cajeros'] as $c): ?>
                        <option value="<?php echo $c->getId(); ?>" <?php echo $avHistorial['filtros']['cajero'] == $c->getId() ? 'selected' : ''; ?>>
                            <?php echo $c->getNombreCompleto(); ?> (<?php echo $c->getUsername(); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-filter">
                <i class="fa-solid fa-magnifying-glass"></i> Filtrar
            </button>
        </form>
    </div>

    <!-- RESULTADOS -->
    <div class="table-container container">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-100">Ticket</th>
                    <th class="w-180">Fecha y Hora</th>
                    <th>Cajero</th>
                    <th>Pago</th>
                    <th class="text-right">Base</th>
                    <th class="text-right">IVA</th>
                    <th class="text-right">TOTAL</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($avHistorial['ventas'])): ?>
                <tr>
                    <td colspan="8" class="empty-state">
                        <i class="fa-solid fa-folder-open"></i>
                        No se han encontrado ventas para los filtros seleccionados.
                    </td>
                </tr>
                <?php endif; ?>
                
                <?php foreach ($avHistorial['ventas'] as $v): ?>
                <tr>
                    <td class="ticket-num">#<?php echo str_pad($v['numero_ticket'], 4, '0', STR_PAD_LEFT); ?></td>
                    <td class="text-muted fs-13">
                        <?php echo date('d/m/Y H:i', strtotime($v['fecha'])); ?>
                    </td>
                    <td>
                        <span class="d-flex ai-center gap-8">
                            <div class="avatar-sm">
                                <?php echo strtoupper(substr($v['nombre_cajero'] ?? '?', 0, 1)); ?>
                            </div>
                            <?php echo htmlspecialchars($v['nombre_cajero'] ?? 'Desconocido'); ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-pill <?php echo $v['metodo_pago'] === 'tarjeta' ? 'status-card' : 'status-cash'; ?>">
                            <i class="fa-solid fa-<?php echo $v['metodo_pago'] === 'tarjeta' ? 'credit-card' : 'money-bill-1-wave'; ?>"></i>
                            <?php echo $v['metodo_pago']; ?>
                        </span>
                    </td>
                    <td class="text-right font-mono">
                        <?php echo number_format($v['base_imponible'], 2, ',', '.'); ?> €
                    </td>
                    <td class="text-right font-mono text-muted">
                        <?php echo number_format($v['iva_amt'], 2, ',', '.'); ?> €
                    </td>
                    <td class="text-right font-bold font-mono">
                        <?php echo number_format($v['total'], 2, ',', '.'); ?> €
                    </td>
                    <td class="text-center">
                        <?php if ($v['estado'] === 'completada'): ?>
                            <span class="status-pill status-active" title="Venta finalizada">
                                <i class="fa-solid fa-check"></i>
                            </span>
                        <?php elseif ($v['estado'] === 'devuelta'): ?>
                            <span class="status-pill" style="background: var(--red-light); color: var(--red); border-color: var(--red);" title="Venta devuelta">
                                <i class="fa-solid fa-rotate-left"></i> Devuelta
                            </span>
                        <?php else: ?>
                            <span class="status-pill" style="background: var(--surface2); color: var(--text-muted);" title="Venta anulada">
                                <i class="fa-solid fa-ban"></i> Anulada
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="d-flex gap-8 jc-center">
                            <button title="Ver ticket/factura" class="btn-icon" onclick="verTicket(<?php echo $v['numero_ticket']; ?>)">
                                <i class="fa-solid fa-receipt"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <!-- LISTADO DE CIERRES FISCALES -->
    <div class="table-container container">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-100">ID Cierre</th>
                    <th class="w-180">Fecha de Cierre</th>
                    <th>Cajero Responsable</th>
                    <th class="text-right">Efectivo</th>
                    <th class="text-right">Tarjeta</th>
                    <th class="text-right">Total Z</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($avHistorial['cierres'])): ?>
                <tr>
                    <td colspan="7" class="empty-state">
                        <i class="fa-solid fa-file-circle-xmark"></i>
                        No hay cierres registrados.
                    </td>
                </tr>
                <?php endif; ?>

                <?php foreach ($avHistorial['cierres'] as $c): ?>
                <tr>
                    <td class="font-mono font-bold text-accent">#Z-<?php echo str_pad($c['id'], 3, '0', STR_PAD_LEFT); ?></td>
                    <td class="text-muted fs-13">
                        <?php echo date('d/m/Y H:i', strtotime($c['fecha'])); ?>
                    </td>
                    <td>
                        <span class="d-flex ai-center gap-8">
                            <div class="avatar-sm" style="background: var(--blue-light); color: var(--blue);">
                                <?php echo strtoupper(substr($c['nombre_usuario'] ?? '?', 0, 1)); ?>
                            </div>
                            <?php echo htmlspecialchars($c['nombre_usuario'] ?? 'Sistema'); ?>
                        </span>
                    </td>
                    <td class="text-right font-mono">
                        <?php echo number_format($c['total_efectivo'], 2, ',', '.'); ?> €
                    </td>
                    <td class="text-right font-mono">
                        <?php echo number_format($c['total_tarjeta'], 2, ',', '.'); ?> €
                    </td>
                    <td class="text-right font-bold font-mono">
                        <?php echo number_format($c['total_general'], 2, ',', '.'); ?> €
                    </td>
                    <td>
                        <div class="d-flex gap-8 jc-center">
                            <button title="Ver Reporte Z Completo" class="btn-icon" onclick='verReporteZ(<?php echo json_encode($c); ?>)'>
                                <i class="fa-solid fa-print"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>

<!-- MODAL REPORTE Z -->
<div id="modalReporteZ" class="modal-overlay-bg">
    <div class="modal-content" style="max-width: 450px; padding: 0; overflow: hidden;">
        <div class="modal-header p-20 bg-surface2 border-bottom">
            <h2 class="m-0 fs-18">Detalle Reporte Z</h2>
            <button onclick="cerrarModalZ()" class="btn-close-modal">&times;</button>
        </div>
        <div class="p-24 bg-white" id="printZ">
            <div class="text-center mb-24">
                <div class="fs-24 font-bold tt-uppercase letter-spacing-2">Reporte Z</div>
                <div class="text-muted fs-12 mt-4" id="z-header-info"></div>
            </div>
            
            <div class="d-flex flex-column gap-16 border-2 br-12 p-20 mb-20">
                <div class="d-flex jc-between border-bottom pb-8">
                    <span class="text-muted">ID Cierre:</span>
                    <span class="font-mono font-bold" id="z-id"></span>
                </div>
                <div class="d-flex jc-between border-bottom pb-8">
                    <span class="text-muted">Efectivo:</span>
                    <span class="font-mono font-bold text-green" id="z-efectivo"></span>
                </div>
                <div class="d-flex jc-between border-bottom pb-8">
                    <span class="text-muted">Tarjeta:</span>
                    <span class="font-mono font-bold text-blue" id="z-tarjeta"></span>
                </div>
                <div class="d-flex jc-between pt-8">
                    <span class="fs-18 font-bold">Total Arqueo:</span>
                    <span class="fs-18 font-bold font-mono text-accent" id="z-total"></span>
                </div>
            </div>

            <div class="text-center mt-32">
                <div class="fs-11 text-muted">Cierre Fiscal Autorizado</div>
                <div class="fs-10 text-muted mt-2">ElectroBazar TPV - Sistema de Gestión v2.0</div>
            </div>
        </div>
        <div class="modal-footer p-20 border-top bg-surface2">
            <button onclick="window.print()" class="btn-filter w-auto gap-8 px-24">
                <i class="fa-solid fa-print"></i> Imprimir Reporte
            </button>
            <div class="flex-1"></div>
            <button onclick="cerrarModalZ()" class="btn-cancel">Cerrar</button>
        </div>
    </div>
</div>

<script>
function verTicket(id) {
    window.location.href = 'api/imprimirTicket.php?id=' + id;
}

function verReporteZ(data) {
    document.getElementById('z-id').innerText = '#Z-' + String(data.id).padStart(3, '0');
    document.getElementById('z-header-info').innerHTML = `Fecha: ${data.fecha}<br>Responsable: ${data.nombre_usuario || 'Sistema'}`;
    
    const fmt = (num) => new Intl.NumberFormat('de-DE', { minimumFractionDigits: 2 }).format(num) + ' €';
    
    document.getElementById('z-efectivo').innerText = fmt(data.total_efectivo);
    document.getElementById('z-tarjeta').innerText = fmt(data.total_tarjeta);
    document.getElementById('z-total').innerText = fmt(data.total_general);
    
    document.getElementById('modalReporteZ').style.display = 'flex';
}

function cerrarModalZ() {
    document.getElementById('modalReporteZ').style.display = 'none';
}
</script>
