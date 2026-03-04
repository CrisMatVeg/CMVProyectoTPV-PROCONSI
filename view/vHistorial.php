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
                                <?php if ($v['metodo_pago'] === 'tarjeta'): ?>
                                    <span class="status-pill status-card">
                                        <i class="fa-solid fa-credit-card"></i> Tarjeta
                                    </span>
                                <?php elseif ($v['metodo_pago'] === 'bizum'): ?>
                                    <span class="status-pill" style="background: var(--accent-light); color: var(--accent); border-color: var(--accent);">
                                        <i class="fa-solid fa-mobile-screen-button"></i> Bizum
                                    </span>
                                <?php elseif ($v['metodo_pago'] === 'financiado'): ?>
                                    <span class="status-pill" style="background: #efecff; color: #6c5ce7; border-color: #6c5ce7;">
                                        <i class="fa-solid fa-calendar-check"></i> Financiado
                                    </span>
                                <?php else: ?>
                                    <span class="status-pill status-cash">
                                        <i class="fa-solid fa-money-bill-1-wave"></i> Efectivo
                                    </span>
                                <?php endif; ?>
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
                        <th class="text-right">Tickets</th>
                        <th class="text-right">Efectivo</th>
                        <th class="text-right">Tarjeta</th>
                        <th class="text-right">Bizum</th>
                        <th class="text-right">Financiado</th>
                        <th class="text-right">Total Z</th>
                        <th class="text-right">Deuda generada</th>
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
                                <?php echo (int)($c['num_tickets'] ?? 0); ?>
                            </td>
                            <td class="text-right font-mono">
                                <?php echo number_format($c['total_efectivo'], 2, ',', '.'); ?> €
                            </td>
                            <td class="text-right font-mono">
                                <?php echo number_format($c['total_tarjeta'], 2, ',', '.'); ?> €
                            </td>
                            <td class="text-right font-mono">
                                <?php echo number_format($c['total_bizum'] ?? 0, 2, ',', '.'); ?> €
                            </td>
                            <td class="text-right font-mono">
                                <?php echo number_format($c['total_financiado'] ?? 0, 2, ',', '.'); ?> €
                            </td>
                            <td class="text-right font-bold font-mono">
                                <?php echo number_format($c['total_general'], 2, ',', '.'); ?> €
                            </td>
                            <td class="text-right font-mono <?php echo ($c['deuda_generada'] ?? 0) > 0 ? 'text-red' : 'text-muted'; ?>">
                                <?php echo number_format($c['deuda_generada'] ?? 0, 2, ',', '.'); ?> €
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
                    <span class="text-muted">Rango de ventas:</span>
                    <span class="font-mono fs-12" id="z-rango"></span>
                </div>
                <div class="d-flex jc-between border-bottom pb-8">
                    <span class="text-muted">Tickets incluidos:</span>
                    <span class="font-mono" id="z-tickets"></span>
                </div>
                <div class="d-flex jc-between border-bottom pb-8">
                    <span class="text-muted">Deuda generada:</span>
                    <span class="font-mono text-red" id="z-deuda"></span>
                </div>
                <div class="d-flex jc-between border-bottom pb-8">
                    <span class="text-muted">Efectivo:</span>
                    <span class="font-mono font-bold text-green" id="z-efectivo"></span>
                </div>
                <div class="d-flex jc-between border-bottom pb-8">
                    <span class="text-muted">Tarjeta:</span>
                    <span class="font-mono font-bold text-blue" id="z-tarjeta"></span>
                </div>
                <div class="d-flex jc-between border-bottom pb-8">
                    <span class="text-muted">Bizum:</span>
                    <span class="font-mono font-bold text-accent" id="z-bizum"></span>
                </div>
                <div class="d-flex jc-between border-bottom pb-8">
                    <span class="text-muted">Financiación:</span>
                    <span class="font-mono font-bold" style="color: #6c5ce7;" id="z-financiacion"></span>
                </div>
                <div class="d-flex jc-between pt-8">
                    <span class="fs-18 font-bold">Total Arqueo:</span>
                    <span class="fs-18 font-bold font-mono text-accent" id="z-total"></span>
                </div>
            </div>

            <div class="mt-20">
                <div class="summary-label mb-8">Retiradas de efectivo del turno</div>
                <table class="data-table mb-12 fs-12">
                    <thead>
                        <tr>
                            <th>Fecha/Hora</th>
                            <th>Usuario</th>
                            <th class="text-right">Importe</th>
                            <th>Concepto</th>
                        </tr>
                    </thead>
                    <tbody id="z-retiros-body">
                        <tr>
                            <td colspan="4" class="text-muted fs-12">Sin retiradas registradas.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-12">
                <div class="summary-label mb-8">Deudas de caja asociadas</div>
                <table class="data-table fs-12">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th class="text-right">Importe</th>
                            <th>Concepto</th>
                        </tr>
                    </thead>
                    <tbody id="z-deudas-body">
                        <tr>
                            <td colspan="4" class="text-muted fs-12">Sin deudas registradas.</td>
                        </tr>
                    </tbody>
                </table>
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
    async function verTicket(id) {
        try {
            const resp = await fetch('api/obtenerVenta.php?id=' + id);
            const data = await resp.json();
            if (data.ok) {
                // mostrarTicket está definido en main.js y usará las nuevas plantillas
                mostrarTicket(data.venta, false); // false = modo historial (sin acciones de nueva venta)
            } else {
                alert("Error al cargar la venta: " + data.error);
            }
        } catch (e) {
            console.error(e);
            alert("Error de conexión");
        }
    }

    function verReporteZ(data) {
        document.getElementById('z-id').innerText = '#Z-' + String(data.id).padStart(3, '0');
        document.getElementById('z-header-info').innerHTML = `Fecha: ${data.fecha}<br>Responsable: ${data.nombre_usuario || 'Sistema'}`;

        const fmt = (num) => new Intl.NumberFormat('de-DE', {
            minimumFractionDigits: 2
        }).format(num) + ' €';

        const rango = (data.primera_venta && data.ultima_venta) ?
            `${data.primera_venta} → ${data.ultima_venta}` :
            'Sin datos de ventas';
        document.getElementById('z-rango').innerText = rango;
        document.getElementById('z-tickets').innerText = data.num_tickets || 0;
        const deuda = data.deuda_generada || 0;
        document.getElementById('z-deuda').innerText =
            new Intl.NumberFormat('de-DE', {
                minimumFractionDigits: 2
            }).format(deuda) + ' €';

        document.getElementById('z-efectivo').innerText = fmt(data.total_efectivo);
        document.getElementById('z-tarjeta').innerText = fmt(data.total_tarjeta);
        document.getElementById('z-bizum').innerText = fmt(data.total_bizum || 0);
        document.getElementById('z-financiacion').innerText = fmt(data.total_financiado || 0);
        document.getElementById('z-total').innerText = fmt(data.total_general);

        // Cargar detalles de caja (retiros y deudas) vía API
        fetch('api/cajaInfoCierre.php?id=' + data.id)
            .then(r => r.json())
            .then(info => {
                const bodyRet = document.getElementById('z-retiros-body');
                const bodyDeu = document.getElementById('z-deudas-body');
                bodyRet.innerHTML = '';
                bodyDeu.innerHTML = '';

                const fmtNum = (n) => new Intl.NumberFormat('de-DE', {
                    minimumFractionDigits: 2
                }).format(n) + ' €';

                if (info.ok && info.retiros && info.retiros.length) {
                    info.retiros.forEach(m => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                        <td class="font-mono">${m.created_at}</td>
                        <td>${m.nombre_usuario || '-'}</td>
                        <td class="text-right font-mono">${fmtNum(m.importe)}</td>
                        <td>${m.concepto || ''}</td>
                    `;
                        bodyRet.appendChild(tr);
                    });
                } else {
                    bodyRet.innerHTML = '<tr><td colspan="4" class="text-muted fs-12">Sin retiradas registradas.</td></tr>';
                }

                if (info.ok && info.deudas && info.deudas.length) {
                    info.deudas.forEach(d => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                        <td class="font-mono">${d.fecha_creacion}</td>
                        <td>${d.nombre_usuario || '-'}</td>
                        <td class="text-right font-mono text-red">${fmtNum(d.importe)}</td>
                        <td>${d.concepto || ''}</td>
                    `;
                        bodyDeu.appendChild(tr);
                    });
                } else {
                    bodyDeu.innerHTML = '<tr><td colspan="4" class="text-muted fs-12">Sin deudas registradas.</td></tr>';
                }
            })
            .catch(() => {
                document.getElementById('z-retiros-body').innerHTML =
                    '<tr><td colspan="4" class="text-muted fs-12">No se pudieron cargar las retiradas.</td></tr>';
                document.getElementById('z-deudas-body').innerHTML =
                    '<tr><td colspan="4" class="text-muted fs-12">No se pudieron cargar las deudas.</td></tr>';
            })
            .finally(() => {
                document.getElementById('modalReporteZ').style.display = 'flex';
            });
    }

    function cerrarModalZ() {
        document.getElementById('modalReporteZ').style.display = 'none';
    }
</script>