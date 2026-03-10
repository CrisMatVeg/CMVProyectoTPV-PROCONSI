</header>
<div class="main-full p-24">

    <!-- CABECERA DE SECCIÓN -->
    <div class="section-header container-wider flex-wrap gap-16">
        <div class="section-title">
            <h1>Historial TPV</h1>
            <p>Auditoría de ventas y reportes de cierre de caja.</p>
        </div>

        <div class="d-flex ai-center gap-16 flex-wrap">
            <div class="cat-tabs m-0">
                <a href="index.php?verCierres=0" class="cat-tab <?php echo !$avHistorial['verCierres'] ? 'active' : ''; ?>" style="text-decoration: none;">
                    <i class="fa-solid fa-receipt"></i> Ventas
                </a>
                <a href="index.php?verCierres=1" class="cat-tab <?php echo $avHistorial['verCierres'] ? 'active' : ''; ?>" style="text-decoration: none;">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Cierres (Z)
                </a>
            </div>

            <div class="vr" style="height: 30px; width: 1px; background: var(--border); opacity: 0.5;"></div>

            <form method="post" class="m-0">
                <button type="submit" name="volver" class="btn-icon w-auto h-40 gap-8 fs-14 px-16 shadow-sm" style="background: var(--surface); border: 1px solid var(--border);">
                    <i class="fa-solid fa-arrow-left"></i> Volver
                </button>
            </form>
        </div>
    </div>

    <?php if (!$avHistorial['verCierres']): ?>

        <!-- PANEL DE FILTROS -->
        <div class="filters-panel container-wider">
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
                <div class="filter-group w-120">
                    <label>Nº Ticket</label>
                    <input type="number" name="numeroTicket" value="<?php echo $avHistorial['filtros']['ticket']; ?>" class="filter-input" placeholder="Ej: 1001">
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
                                <?php elseif ($v['metodo_pago'] === 'a_cuenta'): ?>
                                    <span class="status-pill" style="background: var(--surface2); color: var(--accent); border-color: var(--accent);">
                                        <i class="fa-solid fa-file-invoice-dollar"></i> A cuenta
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
                            <td class="text-center" id="status-venta-<?php echo $v['numero_ticket']; ?>">
                                <?php if ($v['estado'] === 'completada'): ?>
                                    <span class="status-pill status-active" title="Venta finalizada">
                                        <i class="fa-solid fa-check"></i>
                                    </span>
                                <?php elseif ($v['estado'] === 'pendiente_pago'): ?>
                                    <?php
                                    $vencida = (!empty($v['fecha_limite_pago']) && strtotime($v['fecha_limite_pago']) < strtotime(date('Y-m-d')));
                                    $pendiente = (float)$v['total'] - (float)$v['pagado_a_cuenta'];
                                    ?>
                                    <span class="status-pill <?php echo $vencida ? 'status-overdue' : 'status-pending'; ?>"
                                        title="<?php echo $vencida ? 'PAGO VENCIDO' : 'Pendiente de cobro'; ?> (Deuda: <?php echo number_format($pendiente, 2, ',', '.'); ?>€)">
                                        <i class="fa-solid <?php echo $vencida ? 'fa-triangle-exclamation' : 'fa-clock'; ?>"></i>
                                        <?php echo $vencida ? 'VENCIDA' : 'PENDIENTE'; ?>
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
                                    <button title="Ver Informe de Cierre Completo" class="btn-icon" onclick='verReporteZ(<?php echo json_encode($c); ?>)'>
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

<!-- MODAL INFORME DE CIERRE -->
<div id="modalReporteZ" class="modal-overlay-bg">
    <div class="modal-content shadow-2xl" style="max-width: 500px; padding: 0; overflow: visible; border-radius: 20px;">
        <div class="modal-header p-24 bg-surface2 border-bottom" style="border-radius: 20px 20px 0 0;">
            <div class="d-flex ai-center gap-12">
                <div class="w-40 h-40 br-10 bg-surface2 text-accent d-flex ai-center jc-center shadow-sm">
                    <i class="fa-solid fa-file-invoice-dollar fs-20"></i>
                </div>
                <div>
                    <h2 class="m-0 fs-18 font-bold">Resumen de Cierre de Caja</h2>
                    <p class="m-0 fs-12 text-muted">Auditoría de arqueo</p>
                </div>
            </div>
            <button onclick="cerrarModalZ()" class="btn-close-modal" style="top: 24px; right: 24px;">&times;</button>
        </div>
        <div class="p-32 bg-white printable-area" id="printZ">
            <div class="text-center mb-32">
                <div class="fs-12 tt-uppercase letter-spacing-2 text-accent font-bold mb-4">Certificado de Cierre de Caja</div>
                <div class="fs-32 font-bold" id="z-id" style="color: var(--text);"></div>
                <div class="text-muted fs-11 mt-8" id="z-header-info"></div>
            </div>

            <div class="bg-surface2 br-16 border-2 p-24 mb-24 d-flex flex-column gap-16">
                <div class="d-flex jc-between ai-center border-bottom pb-12">
                    <span class="text-muted fs-13">Período de Ventas:</span>
                    <span class="font-mono fs-12 font-bold" id="z-rango"></span>
                </div>
                <div class="d-flex jc-between ai-center border-bottom pb-12">
                    <span class="text-muted fs-13">Operaciones (Tickets):</span>
                    <span class="font-mono font-bold" id="z-tickets"></span>
                </div>
                <div class="d-flex jc-between ai-center border-bottom pb-12">
                    <span class="text-muted fs-13 text-red">Deuda en Caja (Diferencia):</span>
                    <span class="font-mono font-bold text-red" id="z-deuda"></span>
                </div>

                <div class="grid-2 gap-16 mt-8">
                    <div class="p-12 br-12 border bg-white shadow-sm">
                        <div class="fs-10 text-muted tt-uppercase font-bold mb-4">Efectivo</div>
                        <div class="font-mono font-bold text-green fs-16" id="z-efectivo"></div>
                    </div>
                    <div class="p-12 br-12 border bg-white shadow-sm">
                        <div class="fs-10 text-muted tt-uppercase font-bold mb-4">Tarjeta</div>
                        <div class="font-mono font-bold text-blue fs-16" id="z-tarjeta"></div>
                    </div>
                    <div class="p-12 br-12 border bg-white shadow-sm">
                        <div class="fs-10 text-muted tt-uppercase font-bold mb-4">Bizum</div>
                        <div class="font-mono font-bold text-accent fs-16" id="z-bizum"></div>
                    </div>
                    <div class="p-12 br-12 border bg-white shadow-sm">
                        <div class="fs-10 text-muted tt-uppercase font-bold mb-4">Financiado</div>
                        <div class="font-mono font-bold fs-16" style="color: #6c5ce7;" id="z-financiacion"></div>
                    </div>
                </div>

                <div class="d-flex jc-between ai-center pt-16 border-top mt-8">
                    <span class="fs-20 font-bold">Total Arqueo:</span>
                    <span class="fs-24 font-bold font-mono text-accent" id="z-total"></span>
                </div>
            </div>

            <div class="mt-24">
                <h4 class="fs-12 tt-uppercase text-muted font-bold mb-12 border-bottom pb-8">Movimientos de Efectivo</h4>
                <div class="table-container p-0 border-0">
                    <table class="data-table mb-12 fs-11">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Usuario</th>
                                <th class="text-right">Importe</th>
                                <th>Concepto</th>
                            </tr>
                        </thead>
                        <tbody id="z-retiros-body"></tbody>
                    </table>
                </div>
            </div>

            <div class="text-center mt-40 pt-20 border-top">
                <div class="fs-11 text-muted">Cierre Fiscal Autorizado - Copia de Auditoría</div>
                <div class="fs-10 text-muted mt-4">ElectroBazar TPV - Cloud v2.1</div>
            </div>
        </div>
        <div class="modal-footer p-24 border-top bg-surface2 no-print" style="border-radius: 0 0 20px 20px;">
            <div class="flex-1"></div>
            <button onclick="cerrarModalZ()" class="btn-cancel px-32 h-44 shadow-sm">Cerrar Detalle</button>
        </div>
    </div>
</div>

</div>

<style>
    .status-pending {
        background: #fff8e1;
        color: #ffa000;
        border-color: #ffa000;
    }

    .status-overdue {
        background: #ffebee;
        color: #d32f2f;
        border-color: #d32f2f;
        animation: pulse-red 2s infinite;
    }

    @keyframes pulse-red {
        0% {
            box-shadow: 0 0 0 0 rgba(211, 47, 47, 0.4);
        }

        70% {
            box-shadow: 0 0 0 10px rgba(211, 47, 47, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(211, 47, 47, 0);
        }
    }
</style>

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
        document.getElementById('z-id').innerText = '#Cierre-' + String(data.id).padStart(3, '0');
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