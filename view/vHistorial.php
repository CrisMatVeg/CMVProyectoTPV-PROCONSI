</header>
<div class="main-full p-24">

    <!-- CABECERA DE SECCIÓN -->
    <div class="section-header container-wider flex-wrap gap-16">
        <div class="section-title">
            <h1><?php echo L('history_title'); ?></h1>
            <p><?php echo L('history_subtitle'); ?></p>
        </div>

        <div class="d-flex ai-center gap-16 flex-wrap">
            <div class="cat-tabs m-0">
                <a href="index.php?verCierres=0" class="cat-tab <?php echo !$avHistorial['verCierres'] ? 'active' : ''; ?>" style="text-decoration: none;">
                    <i class="fa-solid fa-receipt"></i> <?php echo L('history_tab_sales'); ?>
                </a>
                <a href="index.php?verCierres=1" class="cat-tab <?php echo $avHistorial['verCierres'] ? 'active' : ''; ?>" style="text-decoration: none;">
                    <i class="fa-solid fa-file-invoice-dollar"></i> <?php echo L('history_tab_closings'); ?>
                </a>
            </div>

            <div class="vr" style="height: 30px; width: 1px; background: var(--border); opacity: 0.5;"></div>

            <form method="post" class="m-0">
                <button type="submit" name="volver" class="btn-back">
                    <?php echo L('history_btn_back'); ?>
                </button>
            </form>
        </div>
    </div>

    <!-- PANEL DE FILTROS COMÚN -->
    <div class="filters-panel container-wider">
        <form id="formFiltros" method="get" action="index.php" class="filters-form" novalidate>
            <input type="hidden" name="verCierres" value="<?php echo $avHistorial['verCierres'] ? '1' : '0'; ?>">
            
            <!-- Selector de Periodo -->
            <div class="filter-group">
                <label><?php echo L('history_filter_period'); ?></label>
                <select name="periodo" id="filterPeriodo" class="filter-input">
                    <option value="hoy" <?php echo $avHistorial['filtros']['periodo'] === 'hoy' ? 'selected' : ''; ?>><?php echo L('history_period_today'); ?></option>
                    <option value="semana" <?php echo $avHistorial['filtros']['periodo'] === 'semana' ? 'selected' : ''; ?>><?php echo L('history_period_week'); ?></option>
                    <option value="mes" <?php echo $avHistorial['filtros']['periodo'] === 'mes' ? 'selected' : ''; ?>><?php echo L('history_period_month'); ?></option>
                    <option value="todo" <?php echo $avHistorial['filtros']['periodo'] === 'todo' ? 'selected' : ''; ?>><?php echo L('history_period_all'); ?></option>
                    <option value="personalizado" <?php echo $avHistorial['filtros']['periodo'] === 'personalizado' ? 'selected' : ''; ?>><?php echo L('history_period_custom'); ?></option>
                </select>
            </div>

            <!-- Rango Personalizado (Oculto si no es personalizado) -->
            <div id="customDates" class="d-contents" style="<?php echo $avHistorial['filtros']['periodo'] !== 'personalizado' ? 'display:none' : ''; ?>">
                <div class="filter-group">
                    <label><?php echo L('history_filter_since'); ?></label>
                    <input type="date" name="fechaDesde" value="<?php echo $avHistorial['filtros']['desde']; ?>" class="filter-input">
                </div>
                <div class="filter-group">
                    <label><?php echo L('history_filter_until'); ?></label>
                    <input type="date" name="fechaHasta" value="<?php echo $avHistorial['filtros']['hasta']; ?>" class="filter-input">
                </div>
            </div>

            <?php if (!$avHistorial['verCierres']): ?>
                <div class="filter-group w-120">
                    <label><?php echo L('history_filter_ticket'); ?></label>
                    <input type="text" name="numeroTicket" id="searchTicket" value="<?php echo $avHistorial['filtros']['ticket']; ?>" class="filter-input" placeholder="Ej: 1002">
                </div>

                <div class="filter-group w-180">
                    <label><?php echo L('history_filter_cashier'); ?></label>
                    <select name="idCajero" class="filter-input">
                        <option value=""><?php echo L('history_filter_all_cashiers'); ?></option>
                        <?php foreach ($avHistorial['cajeros'] as $c): ?>
                            <option value="<?php echo $c->getId(); ?>" <?php echo $avHistorial['filtros']['cajero'] == $c->getId() ? 'selected' : ''; ?>>
                                <?php echo $c->getNombreCompleto(); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group w-140">
                    <label><?php echo L('history_filter_type'); ?></label>
                    <select name="tipoDocumento" class="filter-input">
                        <option value="todos" <?php echo $avHistorial['filtros']['tipoDocumento'] === 'todos' ? 'selected' : ''; ?>><?php echo L('history_type_all'); ?></option>
                        <option value="venta" <?php echo $avHistorial['filtros']['tipoDocumento'] === 'venta' ? 'selected' : ''; ?>><?php echo L('history_type_sales'); ?></option>
                        <option value="abono" <?php echo $avHistorial['filtros']['tipoDocumento'] === 'abono' ? 'selected' : ''; ?>><?php echo L('history_type_abonos'); ?></option>
                    </select>
                </div>
            <?php endif; ?>

            <!-- Ordenación -->
            <div class="filter-group">
                <label><?php echo L('history_filter_sort_by'); ?></label>
                <div class="d-flex gap-4">
                    <select name="ordenPor" class="filter-input">
                        <option value="fecha" <?php echo $avHistorial['filtros']['ordenPor'] === 'fecha' ? 'selected' : ''; ?>><?php echo L('history_sort_date'); ?></option>
                        <?php if (!$avHistorial['verCierres']): ?>
                            <option value="numero_ticket" <?php echo $avHistorial['filtros']['ordenPor'] === 'numero_ticket' ? 'selected' : ''; ?>><?php echo L('history_sort_ticket'); ?></option>
                            <option value="total" <?php echo $avHistorial['filtros']['ordenPor'] === 'total' ? 'selected' : ''; ?>><?php echo L('history_sort_amount'); ?></option>
                            <option value="nombre_cajero" <?php echo $avHistorial['filtros']['ordenPor'] === 'nombre_cajero' ? 'selected' : ''; ?>><?php echo L('history_sort_cashier'); ?></option>
                        <?php else: ?>
                            <option value="total" <?php echo $avHistorial['filtros']['ordenPor'] === 'total' ? 'selected' : ''; ?>><?php echo L('history_sort_amount'); ?></option>
                            <option value="id" <?php echo $avHistorial['filtros']['ordenPor'] === 'id' ? 'selected' : ''; ?>>ID #Z</option>
                        <?php endif; ?>
                    </select>
                    <select name="ordenDir" class="filter-input w-80">
                        <option value="DESC" <?php echo $avHistorial['filtros']['ordenDir'] === 'DESC' ? 'selected' : ''; ?>>DESC</option>
                        <option value="ASC" <?php echo $avHistorial['filtros']['ordenDir'] === 'ASC' ? 'selected' : ''; ?>>ASC</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn-filter no-print">
                <i class="fa-solid fa-sync"></i>
            </button>
        </form>
    </div>

    <?php if (!$avHistorial['verCierres']): ?>
        <!-- RESULTADOS -->
        <div class="table-container container-wider">
            <table class="data-table exclude-pagination">
                <thead>
                    <tr>
                        <th class="w-150"><?php echo L('history_th_ticket'); ?></th>
                        <th class="w-150"><?php echo L('history_th_datetime'); ?></th>
                        <th><?php echo L('history_th_cashier'); ?></th>
                        <th><?php echo L('history_th_payment'); ?></th>
                        <th class="text-right"><?php echo L('history_th_base'); ?></th>
                        <th class="text-right"><?php echo L('history_th_iva'); ?></th>
                        <th class="text-right"><?php echo L('history_th_total'); ?></th>
                        <th class="text-center"><?php echo L('history_th_status'); ?></th>
                        <th class="text-center w-80"><?php echo L('history_th_actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($avHistorial['ventas'])): ?>
                        <tr>
                            <td colspan="9" class="empty-state">
                                <i class="fa-solid fa-folder-open"></i>
                                <?php echo L('history_no_sales'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($avHistorial['ventas'] as $v): ?>
                        <?php $esAbono = ($v['tipo_documento'] ?? 'venta') === 'abono'; ?>
                        <tr <?php echo $esAbono ? 'style="background: rgba(192,57,43,0.03);"' : ''; ?>>
                            <td class="ticket-num">
                                <?php if ($esAbono): ?>
                                    <span style="display:inline-flex;align-items:center;gap:5px;">
                                        <span style="background:var(--red);color:#fff;font-size:9px;font-weight:800;padding:2px 6px;border-radius:4px;letter-spacing:.5px;">ABONO</span>
                                        <?php echo VentaPDO::formatTicketNumber($v['numero_ticket'], $v['fecha'], false, 'abono'); ?>
                                    </span>
                                <?php else: ?>
                                    <?php echo VentaPDO::formatTicketNumber($v['numero_ticket'], $v['fecha'], !empty($v['es_factura'])); ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted fs-13">
                                <?php echo date('d/m/Y H:i', strtotime($v['fecha'])); ?>
                            </td>
                            <td>
                                <span class="d-flex ai-center gap-8">
                                    <div class="avatar-sm">
                                        <?php echo strtoupper(substr($v['nombre_cajero'] ?? '?', 0, 1)); ?>
                                    </div>
                                    <?php echo htmlspecialchars($v['nombre_cajero'] ?? L('tpv_unknown', true)); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($v['metodo_pago'] === 'tarjeta'): ?>
                                    <span class="status-pill status-card">
                                        <i class="fa-solid fa-credit-card"></i> <?php echo L('tpv_method_card'); ?>
                                    </span>
                                <?php elseif ($v['metodo_pago'] === 'bizum'): ?>
                                    <span class="status-pill" style="background: var(--accent-light); color: var(--accent); border-color: var(--accent);">
                                        <i class="fa-solid fa-mobile-screen-button"></i> <?php echo L('tpv_method_bizum'); ?>
                                    </span>
                                <?php elseif ($v['metodo_pago'] === 'a_cuenta'): ?>
                                    <span class="status-pill" style="background: var(--surface2); color: var(--accent); border-color: var(--accent);">
                                        <i class="fa-solid fa-file-invoice-dollar"></i> <?php echo L('tpv_method_account'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="status-pill status-cash">
                                        <i class="fa-solid fa-money-bill-1-wave"></i> <?php echo L('tpv_method_cash'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right font-mono <?php echo $esAbono ? 'text-red' : ''; ?>">
                                <?php echo number_format($v['base_imponible'], 2, ',', '.'); ?> €
                            </td>
                            <td class="text-right font-mono text-muted <?php echo $esAbono ? 'text-red' : ''; ?>">
                                <?php echo number_format($v['iva_amt'], 2, ',', '.'); ?> €
                            </td>
                            <td class="text-right font-bold font-mono <?php echo $esAbono ? 'text-red' : ''; ?>">
                                <?php echo number_format($v['total'], 2, ',', '.'); ?> €
                            </td>
                            <td class="text-center" id="status-venta-<?php echo $v['numero_ticket']; ?>">
                                <?php if ($esAbono): ?>
                                    <span class="status-pill" style="background:var(--red-light);color:var(--red);border-color:var(--red);" title="Ticket de abono / devolución">
                                        <i class="fa-solid fa-rotate-left"></i> Abono
                                    </span>
                                <?php elseif ($v['estado'] === 'completada'): ?>
                                    <span class="status-pill status-active" title="<?php echo L('history_status_completed'); ?>">
                                        <i class="fa-solid fa-check"></i>
                                    </span>
                                <?php elseif ($v['estado'] === 'parcialmente_devuelta'): ?>
                                    <span class="status-pill" style="background:#fff3e0;color:#e65100;border-color:#e65100;" title="Devolución parcial registrada">
                                        <i class="fa-solid fa-rotate-left"></i> Parcial
                                    </span>
                                <?php elseif ($v['estado'] === 'pendiente_pago'): ?>
                                    <?php
                                    $vencida  = (!empty($v['fecha_limite_pago']) && strtotime($v['fecha_limite_pago']) < strtotime(date('Y-m-d')));
                                    $pendiente = (float)$v['total'] - (float)$v['pagado_a_cuenta'];
                                    ?>
                                    <span class="status-pill <?php echo $vencida ? 'status-overdue' : 'status-pending'; ?>"
                                        title="<?php echo $vencida ? L('history_status_overdue', true) : L('history_status_pending_of_payment', true); ?> (<?php echo L('history_status_debt'); ?>: <?php echo number_format($pendiente, 2, ',', '.'); ?>€)">
                                        <i class="fa-solid <?php echo $vencida ? 'fa-triangle-exclamation' : 'fa-clock'; ?>"></i>
                                        <?php echo $vencida ? L('history_status_overdue', true) : L('tpv_pending', true); ?>
                                    </span>
                                <?php elseif ($v['estado'] === 'devuelta'): ?>
                                    <span class="status-pill" style="background: var(--red-light); color: var(--red); border-color: var(--red);" title="<?php echo L('history_status_returned'); ?>">
                                        <i class="fa-solid fa-rotate-left"></i> <?php echo L('tpv_returned'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="status-pill" style="background: var(--surface2); color: var(--text-muted);" title="<?php echo L('history_status_canceled'); ?>">
                                        <i class="fa-solid fa-ban"></i> <?php echo L('tpv_canceled'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-8 jc-center">
                                    <button title="Ver detalle" class="btn-icon" onclick="verTicket('<?php echo $v['numero_ticket']; ?>')">
                                        <i class="fa-solid <?php echo $esAbono ? 'fa-file-circle-minus' : 'fa-receipt'; ?>"></i>
                                    </button>
                                    <button title="<?php echo !empty($v['es_factura']) ? L('history_btn_view_invoice', true) : L('history_btn_gen_invoice', true); ?>" class="btn-icon <?php echo !empty($v['es_factura']) ? 'text-accent' : ''; ?>" onclick="abrirModalFactura('<?php echo $v['id']; ?>', '<?php echo $v['numero_ticket']; ?>', '<?php echo addslashes(htmlspecialchars($v['nombre_cliente'] ?? '')); ?>', '<?php echo addslashes(htmlspecialchars($v['nif_cliente'] ?? '')); ?>', <?php echo !empty($v['es_factura']) ? 'true' : 'false'; ?>)">
                                        <i class="fa-solid fa-file-invoice"></i>
                                    </button>
                                    <?php if ($esAbono && !empty($v['numero_ticket_origen'])): ?>
                                        <button title="Ver venta origen: T-<?php echo $v['numero_ticket_origen']; ?>" class="btn-icon text-accent" onclick="verTicket('<?php echo $v['numero_ticket_origen']; ?>')">
                                            <i class="fa-solid fa-link"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación para Ventas -->
        <div class="pagination-footer mt-24 d-flex ai-center jc-between p-16 container-wider bg-surface border-top no-print" style="border-radius: 0 0 12px 12px;">
            <div class="pagination-info fs-13 fw-500 text-muted">
                <i class="fa-solid fa-list-ol mr-8 opacity-50"></i>
                <?php 
                    $from = $avHistorial['paginacion']['totalRegistros'] > 0 ? ($avHistorial['paginacion']['actual'] - 1) * $avHistorial['paginacion']['limit'] + 1 : 0;
                    $to = min($avHistorial['paginacion']['actual'] * $avHistorial['paginacion']['limit'], $avHistorial['paginacion']['totalRegistros']);
                    echo str_replace(['{from}', '{to}', '{total}'], (array)[$from, $to, $avHistorial['paginacion']['totalRegistros']], L('page_showing')); 
                ?>
            </div>
            
            <div class="pagination-controls d-flex ai-center gap-12">
                <?php
                // Construir URL base con TODOS los filtros actuales para persistencia
                $params = [
                    'fechaDesde'    => $avHistorial['filtros']['desde'],
                    'fechaHasta'    => $avHistorial['filtros']['hasta'],
                    'periodo'       => $_GET['periodo'] ?? 'hoy',
                    'ordenPor'      => $avHistorial['filtros']['ordenPor'] ?? 'fecha',
                    'ordenDir'      => $avHistorial['filtros']['ordenDir'] ?? 'DESC',
                    'numeroTicket'  => $avHistorial['filtros']['ticket'] ?? '',
                    'idCajero'      => $avHistorial['filtros']['cajero'] ?? '',
                    'tipoDocumento' => $avHistorial['filtros']['tipoDocumento'] ?? 'todos'
                ];
                $baseUrl = "index.php?" . http_build_query($params);
                $isFirst = $avHistorial['paginacion']['actual'] <= 1;
                $isLast  = $avHistorial['paginacion']['actual'] >= $avHistorial['paginacion']['total'];
                ?>
                
                <div class="d-flex gap-4">
                    <a class="btn-icon btn-sm <?php echo $isFirst ? 'disabled pointer-events-none opacity-30' : ''; ?>" 
                       href="<?php echo $baseUrl; ?>&p=1" title="<?php echo L('page_first'); ?>">
                        <i class="fa-solid fa-angles-left"></i>
                    </a>
                    <a class="btn-secondary btn-sm <?php echo $isFirst ? 'disabled pointer-events-none opacity-30' : ''; ?>" 
                       href="<?php echo $baseUrl; ?>&p=<?php echo $avHistorial['paginacion']['actual'] - 1; ?>" style="min-width: 100px; height: 36px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-chevron-left mr-8"></i> <?php echo L('page_prev'); ?>
                    </a>
                </div>

                <div class="pagination-current fw-700 fs-13 d-flex ai-center px-16 h-36 border shadow-sm" style="background: #fff; border-radius: 8px; color: var(--accent); min-width: 120px; justify-content: center; border-color: rgba(var(--accent-rgb), 0.2);">
                    <?php echo str_replace(['{current}', '{total}'], [$avHistorial['paginacion']['actual'], max(1, $avHistorial['paginacion']['total'])], L('page_info')); ?>
                </div>

                <div class="d-flex gap-4">
                    <a class="btn-secondary btn-sm <?php echo $isLast ? 'disabled pointer-events-none opacity-30' : ''; ?>" 
                       href="<?php echo $baseUrl; ?>&p=<?php echo $avHistorial['paginacion']['actual'] + 1; ?>" style="min-width: 100px; height: 36px; display: inline-flex; align-items: center; justify-content: center;">
                        <?php echo L('page_next'); ?> <i class="fa-solid fa-chevron-right ml-8"></i>
                    </a>
                    <a class="btn-icon btn-sm <?php echo $isLast ? 'disabled pointer-events-none opacity-30' : ''; ?>" 
                       href="<?php echo $baseUrl; ?>&p=<?php echo $avHistorial['paginacion']['total']; ?>" title="<?php echo L('page_last'); ?>">
                        <i class="fa-solid fa-angles-right"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- LISTADO DE CIERRES FISCALES -->
        <div class="table-container container-wider">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 85px;"><?php echo L('history_th_id_z'); ?></th>
                        <th style="width: 150px;"><?php echo L('history_th_date_z'); ?></th>
                        <th><?php echo L('history_th_cashier_z'); ?></th>
                        <th class="text-right" style="padding-left: 10px; padding-right: 10px;"><?php echo L('history_th_tickets_z'); ?></th>
                        <th class="text-right" style="padding-left: 10px; padding-right: 10px;"><?php echo L('history_th_cash_z'); ?></th>
                        <th class="text-right" style="padding-left: 10px; padding-right: 10px;"><?php echo L('history_th_card_z'); ?></th>
                        <th class="text-right" style="padding-left: 10px; padding-right: 10px;"><?php echo L('history_th_bizum_z'); ?></th>
                        <th class="text-right" style="padding-left: 10px; padding-right: 10px;"><?php echo L('history_th_total_z'); ?></th>
                        <th class="text-right" style="padding-left: 10px; padding-right: 10px;"><?php echo L('history_th_debt_z'); ?></th>
                        <th class="text-center" style="width: 80px;"><?php echo L('history_th_actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($avHistorial['cierres'])): ?>
                        <tr>
                            <td colspan="10" class="empty-state">
                                <i class="fa-solid fa-file-circle-xmark"></i>
                                <?php echo L('history_no_closings'); ?>
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
                                    <?php echo htmlspecialchars($c['nombre_usuario'] ?? L('system', true)); ?>
                                </span>
                            </td>
                            <td class="text-right font-mono" style="white-space: nowrap; padding-left: 10px; padding-right: 10px;">
                                <?php echo (int)($c['num_tickets'] ?? 0); ?>
                            </td>
                            <td class="text-right font-mono" style="white-space: nowrap; padding-left: 10px; padding-right: 10px;">
                                <?php echo number_format($c['total_efectivo'], 2, ',', '.'); ?> €
                            </td>
                            <td class="text-right font-mono" style="white-space: nowrap; padding-left: 10px; padding-right: 10px;">
                                <?php echo number_format($c['total_tarjeta'], 2, ',', '.'); ?> €
                            </td>
                            <td class="text-right font-mono" style="white-space: nowrap; padding-left: 10px; padding-right: 10px;">
                                <?php echo number_format($c['total_bizum'] ?? 0, 2, ',', '.'); ?> €
                            </td>
                            <td class="text-right font-bold font-mono" style="white-space: nowrap; padding-left: 10px; padding-right: 10px;">
                                <?php echo number_format($c['total_general'], 2, ',', '.'); ?> €
                            </td>
                            <td class="text-right font-mono <?php echo ($c['deuda_generada'] ?? 0) > 0 ? 'text-red' : 'text-muted'; ?>" style="white-space: nowrap; padding-left: 10px; padding-right: 10px;">
                                <?php echo number_format($c['deuda_generada'] ?? 0, 2, ',', '.'); ?> €
                            </td>
                            <td>
                                <div class="d-flex gap-8 jc-center">
                                    <button title="<?php echo L('history_btn_view_report_z'); ?>" class="btn-icon" onclick='verReporteZ(<?php echo json_encode($c); ?>)'>
                                        <i class="fa-solid fa-print"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación para Cierres -->
        <div class="pagination-footer mt-24 d-flex ai-center jc-between p-16 container-wider bg-surface border-top no-print" style="border-radius: 0 0 12px 12px;">
            <div class="pagination-info fs-13 fw-500 text-muted">
                <i class="fa-solid fa-list-ol mr-8 opacity-50"></i>
                <?php 
                    $from = $avHistorial['paginacion']['totalRegistros'] > 0 ? ($avHistorial['paginacion']['actual'] - 1) * $avHistorial['paginacion']['limit'] + 1 : 0;
                    $to = min($avHistorial['paginacion']['actual'] * $avHistorial['paginacion']['limit'], $avHistorial['paginacion']['totalRegistros']);
                    echo str_replace(['{from}', '{to}', '{total}'], (array)[$from, $to, $avHistorial['paginacion']['totalRegistros']], L('page_showing')); 
                ?>
            </div>
            
            <div class="pagination-controls d-flex ai-center gap-12">
                <?php
                // Construir URL base con TODOS los filtros actuales para persistencia
                $params = [
                    'verCierres'    => 1,
                    'fechaDesde'    => $avHistorial['filtros']['desde'],
                    'fechaHasta'    => $avHistorial['filtros']['hasta'],
                    'periodo'       => $_GET['periodo'] ?? 'hoy',
                    'ordenPor'      => $avHistorial['filtros']['ordenPor'] ?? 'fecha',
                    'ordenDir'      => $avHistorial['filtros']['ordenDir'] ?? 'DESC'
                ];
                $baseUrl = "index.php?" . http_build_query($params);
                $isFirst = $avHistorial['paginacion']['actual'] <= 1;
                $isLast  = $avHistorial['paginacion']['actual'] >= $avHistorial['paginacion']['total'];
                ?>
                
                <div class="d-flex gap-4">
                    <a class="btn-icon btn-sm <?php echo $isFirst ? 'disabled pointer-events-none opacity-30' : ''; ?>" 
                       href="<?php echo $baseUrl; ?>&p=1" title="<?php echo L('page_first'); ?>">
                        <i class="fa-solid fa-angles-left"></i>
                    </a>
                    <a class="btn-secondary btn-sm <?php echo $isFirst ? 'disabled pointer-events-none opacity-30' : ''; ?>" 
                       href="<?php echo $baseUrl; ?>&p=<?php echo $avHistorial['paginacion']['actual'] - 1; ?>" style="min-width: 100px; height: 36px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-chevron-left mr-8"></i> <?php echo L('page_prev'); ?>
                    </a>
                </div>

                <div class="pagination-current fw-700 fs-13 d-flex ai-center px-16 h-36 border shadow-sm" style="background: #fff; border-radius: 8px; color: var(--accent); min-width: 120px; justify-content: center; border-color: rgba(var(--accent-rgb), 0.2);">
                    <?php echo str_replace(['{current}', '{total}'], [$avHistorial['paginacion']['actual'], max(1, $avHistorial['paginacion']['total'])], L('page_info')); ?>
                </div>

                <div class="d-flex gap-4">
                    <a class="btn-secondary btn-sm <?php echo $isLast ? 'disabled pointer-events-none opacity-30' : ''; ?>" 
                       href="<?php echo $baseUrl; ?>&p=<?php echo $avHistorial['paginacion']['actual'] + 1; ?>" style="min-width: 100px; height: 36px; display: inline-flex; align-items: center; justify-content: center;">
                        <?php echo L('page_next'); ?> <i class="fa-solid fa-chevron-right ml-8"></i>
                    </a>
                    <a class="btn-icon btn-sm <?php echo $isLast ? 'disabled pointer-events-none opacity-30' : ''; ?>" 
                       href="<?php echo $baseUrl; ?>&p=<?php echo $avHistorial['paginacion']['total']; ?>" title="<?php echo L('page_last'); ?>">
                        <i class="fa-solid fa-angles-right"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- MODAL GENERAR FACTURA -->
<style>
    .badge-invoice {
        background: rgba(99, 102, 241, .12);
        color: #6366f1;
        border: 1px solid rgba(99, 102, 241, .3);
        border-radius: 4px;
        font-size: 9px;
        font-weight: 700;
        padding: 2px 5px;
        letter-spacing: .04em;
        vertical-align: middle;
        margin-left: 4px;
    }
</style>

<div id="modalGenerarFactura" class="modal-overlay-bg">
    <div class="modal-content" style="max-width: 600px; border-radius: 20px;">
        <div class="modal-header">
            <div>
                <h2 style="margin:0 0 2px 0"><?php echo L('history_modal_gen_invoice_title'); ?></h2>
                <span style="font-size:12px; color: var(--text-muted); font-weight:400;"><?php echo L('history_modal_gen_invoice_sub'); ?></span>
            </div>
            <button class="btn-close-modal" onclick="cerrarModalFactura()">&times;</button>
        </div>
        <div class="p-24">
            <input type="hidden" id="facTicketId" value="">
            <input type="hidden" id="facVentaId" value="">
            <div class="form-group mb-16">
                <label class="form-label fs-11 tt-uppercase"><?php echo L('history_modal_label_name'); ?></label>
                <input type="text" id="facNombreCliente" class="form-input" placeholder="Ej: Empresa S.L." maxlength="100">
            </div>
            <div class="form-group mb-0">
                <label class="form-label fs-11 tt-uppercase"><?php echo L('history_modal_label_nif'); ?></label>
                <input type="text" id="facNifCliente" class="form-input" placeholder="Ej: B12345678" maxlength="20">
            </div>
        </div>
        <div class="modal-footer pt-16 border-top p-24">
            <button class="btn-cancel" onclick="cerrarModalFactura()"><?php echo L('modal_cancel'); ?></button>
            <div class="flex-1"></div>
            <button id="btnConfirmarFactura" class="btn-save w-auto px-32" onclick="confirmarGenerarFactura()">
                <i class="fa-solid fa-file-invoice mr-8"></i> <?php echo L('history_modal_btn_gen'); ?>
            </button>
        </div>
    </div>
</div>

<div id="modalReporteZ" class="modal-overlay-bg" style="display: none; align-items: center; justify-content: center; padding: 20px; z-index: 10000;">
    <div class="modal-content shadow-2xl" style="max-width: 1000px; width: 100%; padding: 0; overflow: hidden; border-radius: 20px; border: none; display: flex; flex-direction: column; max-height: 90vh;">
        <div class="modal-header p-24 bg-surface2 border-bottom shadow-sm" style="border-radius: 20px 20px 0 0; background: linear-gradient(135deg, var(--surface2) 0%, #f1f5f9 100%); flex-shrink: 0; position: relative; z-index: 10;">
            <div class="d-flex ai-center gap-16">
                <div class="w-48 h-48 br-12 bg-accent text-white d-flex ai-center jc-center shadow-md">
                    <i class="fa-solid fa-receipt fs-24"></i>
                </div>
                <div>
                    <h2 class="m-0 fs-20 font-bold" style="letter-spacing: -0.5px; color: var(--text);"><?php echo L('history_report_z_title'); ?></h2>
                    <p class="m-0 fs-12 text-muted fw-500"><?php echo L('history_report_z_sub'); ?></p>
                </div>
            </div>
            <button onclick="cerrarModalZ()" class="btn-close-modal" style="top: 28px; right: 24px; background: rgba(0,0,0,0.05); width: 32px; height: 32px; border-radius: 50%; opacity: 0.6; transition: all 0.2s;">&times;</button>
        </div>

        <div class="p-32 bg-surface" id="printZ" style="flex: 1; overflow-y: auto;">

            <!-- ID + meta -->
            <div class="text-center pb-24 mb-24 border-bottom">
                <div class="fs-11 tt-uppercase text-muted font-bold mb-8" style="letter-spacing: 2px;"><?php echo L('history_report_z_cert'); ?></div>
                <h1 class="fs-32 font-bold font-mono m-0" id="z-id" style="letter-spacing: -1px;"></h1>
                <div class="d-flex ai-center jc-center gap-24 mt-12 fs-13 text-muted" id="z-header-info"></div>
            </div>

            <!-- KPIs fila 1 -->
            <div class="grid-3 gap-16 mb-20">
                <div class="bg-surface2 p-16 br-12 border">
                    <div class="fs-11 tt-uppercase text-muted font-bold mb-8" style="letter-spacing: 0.5px;"><?php echo L('history_report_z_activity'); ?></div>
                    <div class="fs-16 font-bold font-mono" id="z-rango"></div>
                </div>
                <div class="bg-surface2 p-16 br-12 border">
                    <div class="fs-11 tt-uppercase text-muted font-bold mb-8" style="letter-spacing: 0.5px;"><?php echo L('history_report_z_ops'); ?></div>
                    <div class="fs-24 font-bold font-mono" id="z-tickets"></div>
                </div>
                <div class="p-16 br-12 border" id="z-deuda-card" style="background: var(--red-light); border-color: rgba(192,57,43,0.2);">
                    <div class="fs-11 tt-uppercase font-bold mb-8 text-red" style="letter-spacing: 0.5px;"><?php echo L('history_report_z_imbalance'); ?></div>
                    <div class="fs-24 font-bold font-mono text-red" id="z-deuda"></div>
                </div>
            </div>

            <!-- Métodos de pago -->
            <div class="mb-20">
                <div class="fs-11 tt-uppercase text-muted font-bold mb-12" style="letter-spacing: 0.5px;"><?php echo L('history_report_z_breakdown'); ?></div>
                <div class="grid-3 gap-12">
                    <div class="bg-surface2 p-16 br-12 border">
                        <div class="fs-11 text-muted mb-4"><?php echo L('tpv_method_cash'); ?></div>
                        <div class="fs-18 font-bold font-mono text-green" id="z-efectivo"></div>
                    </div>
                    <div class="bg-surface2 p-16 br-12 border">
                        <div class="fs-11 text-muted mb-4"><?php echo L('tpv_method_card'); ?></div>
                        <div class="fs-18 font-bold font-mono text-blue" id="z-tarjeta"></div>
                    </div>
                    <div class="bg-surface2 p-16 br-12 border">
                        <div class="fs-11 text-muted mb-4"><?php echo L('tpv_method_bizum'); ?></div>
                        <div class="fs-18 font-bold font-mono text-accent" id="z-bizum"></div>
                    </div>
                </div>
            </div>

            <!-- Total arqueado -->
            <div class="bg-blue-light p-20 br-12 border-2 mb-24 d-flex jc-between ai-center gap-32" style="border-color: var(--accent);">
                <div style="flex: 1;">
                    <div class="fs-11 tt-uppercase font-bold text-accent mb-4" style="letter-spacing: 0.5px;"><?php echo L('history_report_z_total_arqueado'); ?></div>
                    <div class="fs-12 text-muted"><?php echo L('history_report_z_sum'); ?></div>
                </div>
                <div class="fs-32 font-bold font-mono text-accent" id="z-total" style="flex-shrink: 0;"></div>
            </div>

            <!-- Turnos y Retiros -->
                <div class="mb-24">
                    <div class="d-flex ai-center jc-between mb-12">
                        <div class="fs-11 tt-uppercase text-muted font-bold" style="letter-spacing: 0.5px;"><?php echo L('history_report_z_shifts_title'); ?></div>
                        <span class="fs-11 bg-surface2 text-accent px-8 py-2 br-8 border font-bold" id="z-num-turnos"></span>
                    </div>
                    <div class="table-container m-0 border br-12">
                        <table class="data-table fs-12">
                            <thead>
                                <tr>
                                    <th class="py-10 hlh-12 pl-12"><?php echo L('history_report_z_th_shift_time'); ?></th>
                                    <th class="py-10"><?php echo L('history_report_z_th_shift_resp'); ?></th>
                                    <th class="text-right py-10 pr-12"><?php echo L('history_th_actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="z-turnos-body"></tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <div class="fs-11 tt-uppercase text-muted font-bold mb-12" style="letter-spacing: 0.5px;"><?php echo L('history_report_z_movements_title'); ?></div>
                    <div class="table-container m-0 border br-12">
                        <table class="data-table fs-12">
                            <thead>
                                <tr>
                                    <th class="py-10 pl-12"><?php echo L('history_report_z_th_mov_time'); ?></th>
                                    <th class="py-10"><?php echo L('history_report_z_th_mov_user'); ?></th>
                                    <th class="py-10"><?php echo L('history_report_z_th_mov_concept'); ?></th>
                                    <th class="text-right py-10 pr-12"><?php echo L('history_report_z_th_mov_amount'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="z-retiros-body"></tbody>
                        </table>
                    </div>
                </div>

            <!-- Pie -->
            <div class="text-center pt-16 border-top">
                <span class="fs-11 tt-uppercase text-muted font-bold" style="letter-spacing: 2px;"><?php echo L('history_report_z_footer'); ?></span>
            </div>
        </div>

        <div class="modal-footer p-24 border-top no-print" style="border-radius: 0 0 20px 20px; background: var(--surface2); flex-shrink: 0; position: relative; z-index: 10;">
            <button onclick="window.open('api/imprimirCierreZ.php?id=' + window._currentZId, '_blank')"
                class="btn-cancel d-flex ai-center gap-8 h-48 px-24">
                <i class="fa-solid fa-print"></i> <?php echo L('history_report_z_btn_print'); ?>
            </button>
            <div class="flex-1"></div>
            <button onclick="cerrarModalZ()" class="btn-save h-48 px-40 font-bold"><?php echo L('history_report_z_btn_close'); ?></button>
        </div>
    </div>
</div>

<!-- Modal Detalle Turno (Segundo Nivel) -->
<div id="modalTurnoDetalle" class="modal-overlay-bg" style="display: none; z-index: 20000 !important; align-items: center; justify-content: center; padding: 20px; background: rgba(0,0,0,0.7);">
    <div class="modal-content br-20 shadow-2xl animate-scale-up" style="max-width: 650px; width: 100%; background: var(--surface); border: none; overflow: hidden; display: flex; flex-direction: column; max-height: 85vh;">
        <div class="modal-header p-24 bg-surface2 border-bottom d-flex ai-center jc-space-between" style="background: linear-gradient(135deg, var(--surface2) 0%, #fdfdfd 100%); flex-shrink: 0;">
            <div style="display: flex; align-items: center; gap: 20px;">
                <div style="width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--accent) 0%, #4338ca 100%); color: white; box-shadow: 0 4px 12px rgba(26, 47, 191, 0.25); border: 2px solid rgba(255,255,255,0.2);">
                    <i class="fa-solid fa-clock-rotate-left fs-26"></i>
                </div>
                <div>
                    <h3 class="fs-22 font-bold m-0" id="td-titulo" style="color: var(--text); letter-spacing: -0.8px;"><?php echo L('history_modal_shift_title'); ?></h3>
                    <div class="fs-13 text-muted fw-600" id="td-periodo" style="opacity: 0.9;"></div>
                </div>
            </div>
            <button onclick="cerrarTurnoDetalle()" style="width: 44px; height: 44px; border-radius: 50%; border: none; background: #fff; box-shadow: var(--shadow); color: var(--text-muted); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;">
                <i class="fa-solid fa-xmark fs-22"></i>
            </button>
        </div>
        
        <div class="modal-body p-32 custom-scroll" style="max-height: 75vh; overflow-y: auto;">
            <!-- KPIS Rápidos -->
            <div class="grid-2 gap-24 mb-32">
                <div class="p-24 br-20 border" style="border-color: rgba(var(--accent-rgb), 0.15); background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.08) 0%, rgba(var(--accent-rgb), 0.03) 100%); display: flex; align-items: center; gap: 24px; border-radius: 20px;">
                    <div style="width: 64px; height: 64px; border-radius: 18px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--accent) 0%, #4338ca 100%); color: white; box-shadow: 0 8px 16px rgba(26, 47, 191, 0.2);">
                        <i class="fa-solid fa-coins fs-28"></i>
                    </div>
                    <div class="flex-1">
                        <div class="fs-12 text-muted mb-4 tt-uppercase font-bold" style="letter-spacing: 1px; opacity: 0.8;"><?php echo L('history_modal_shift_sales'); ?></div>
                        <div class="fs-34 font-bold font-mono text-accent" id="td-total" style="letter-spacing: -1.5px;"></div>
                    </div>
                </div>
                <div class="p-24 br-20 border" style="border-color: var(--border); background: linear-gradient(135deg, var(--surface2) 0%, #f4f7f9 100%); display: flex; align-items: center; gap: 24px; border-radius: 20px;">
                     <div style="width: 64px; height: 64px; border-radius: 18px; background: #fff; border: 1.5px solid var(--border); display: flex; align-items: center; justify-content: center; color: #64748b; box-shadow: var(--shadow);">
                        <i class="fa-solid fa-receipt fs-28"></i>
                    </div>
                    <div class="flex-1">
                        <div class="fs-12 text-muted mb-4 tt-uppercase font-bold" style="letter-spacing: 1px; opacity: 0.8;"><?php echo L('history_modal_shift_ops'); ?></div>
                        <div class="fs-34 font-bold font-mono" id="td-tickets" style="letter-spacing: -1.5px; color: var(--text);"></div>
                    </div>
                </div>
            </div>

            <!-- Resumen Ventas -->
            <div class="mb-32">
                <div class="fs-11 tt-uppercase text-muted font-bold mb-12" style="letter-spacing: 1px;"><?php echo L('history_modal_shift_breakdown'); ?></div>
                <div class="grid-2 gap-12" id="td-metodos">
                    <!-- Dinámico -->
                </div>
            </div>

            <!-- Movimientos -->
            <div>
                <div class="fs-11 tt-uppercase text-muted font-bold mb-12" style="letter-spacing: 1px;"><?php echo L('history_modal_shift_movements'); ?></div>
                <div class="table-container m-0 border br-12 shadow-sm overflow-hidden">
                    <table class="data-table fs-12">
                        <thead>
                            <tr>
                                <th class="py-12 pl-16 bg-surface2"><?php echo L('history_report_z_th_mov_time'); ?></th>
                                <th class="py-12 bg-surface2"><?php echo L('history_report_z_th_mov_concept'); ?></th>
                                <th class="text-right py-12 pr-16 bg-surface2"><?php echo L('history_report_z_th_mov_amount'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="td-movimientos"></tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="modal-footer p-24 border-top d-flex jc-end bg-surface2" style="flex-shrink: 0;">
            <button onclick="cerrarTurnoDetalle()" class="btn-save h-48 px-40 font-bold shadow-md hover-scale"><?php echo L('history_modal_shift_btn_close'); ?></button>
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

    @media print {
        body * {
            visibility: hidden;
        }

        #printZ,
        #printZ * {
            visibility: visible !important;
        }

        #modalReporteZ {
            position: fixed !important;
            inset: 0 !important;
            display: block !important;
            visibility: visible !important;
            overflow: visible !important;
            padding: 0 !important;
            background: white !important;
        }

        #printZ {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            padding: 20mm !important;
            background: white !important;
        }

        .no-print {
            display: none !important;
            visibility: hidden !important;
        }
    }
</style>

<script>
    function verTicket(id) {
        if (!id) return;
        // Direct call to global function in main.js
        fetch('api/obtenerVenta.php?id=' + encodeURIComponent(id))
            .then(r => r.json())
            .then(data => {
                if (data.ok) mostrarTicket(data.venta, false);
            });
    }

    // ── Factura desde Historial ────────────────────────────────────────────────
    function abrirModalFactura(idVenta, numTicket, nombreExistente, nifExistente, yaEsFactura) {
        document.getElementById('facVentaId').value = idVenta;
        document.getElementById('facTicketId').value = numTicket;
        document.getElementById('facNombreCliente').value = nombreExistente || '';
        document.getElementById('facNifCliente').value = nifExistente || '';

        // Si ya tiene datos completos, podemos abrir directamente la factura
        if (yaEsFactura && nombreExistente && nifExistente) {
            window.open('api/imprimirTicketPro.php?id=' + encodeURIComponent(numTicket) + '&modo=factura', '_blank');
            return;
        }

        document.getElementById('modalGenerarFactura').style.display = 'flex';
        setTimeout(() => document.getElementById('facNombreCliente').focus(), 100);
    }

    function cerrarModalFactura() {
        document.getElementById('modalGenerarFactura').style.display = 'none';
    }

    async function confirmarGenerarFactura() {
        const idVenta = document.getElementById('facVentaId').value;
        const ticket = document.getElementById('facTicketId').value;
        const nombre = document.getElementById('facNombreCliente').value.trim();
        const nif = document.getElementById('facNifCliente').value.trim();

        if (!nombre || !nif) {
            showCustomAlert(<?php echo json_encode(L('history_modal_label_name')); ?>, <?php echo json_encode(L('history_js_inv_req')); ?>, 'warning');
            return;
        }

        if (!validarDocumento(nif)) {
            showCustomAlert(<?php echo json_encode(L('client_js_invalid_doc')); ?>, <?php echo json_encode(L('history_js_inv_format_error')); ?>, 'warning');
            return;
        }

        const btn = document.getElementById('btnConfirmarFactura');
        btn.disabled = true;

        try {
            const r = await fetch('api/generarFacturaVenta.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id_venta: idVenta,
                    nombre_cliente: nombre,
                    nif_cliente: nif
                })
            });
            const res = await r.json();
            if (!res.ok) {
                showCustomAlert(<?php echo json_encode(L('config_save_error')); ?>, (res.error || <?php echo json_encode(L('history_js_gen_error')); ?>), 'error');
                return;
            }
            cerrarModalFactura();
            window.open('api/imprimirTicketPro.php?id=' + encodeURIComponent(ticket) + '&modo=factura', '_blank');
            // Recargar la página para que el badge FAC aparezca en la fila
            setTimeout(() => location.reload(), 800);
        } catch (e) {
            showCustomAlert(<?php echo json_encode(L('config_save_error')); ?>, <?php echo json_encode(L('history_js_conn_error')); ?>, 'error');
        } finally {
            btn.disabled = false;
        }
    }

    function verReporteZ(data) {
        window._currentZId = data.id;
        document.getElementById('z-id').innerText = '#Cierre-' + String(data.id).padStart(3, '0');
        document.getElementById('z-header-info').innerHTML = `
            <span><i class="fa-solid fa-calendar-day opacity-50 mr-4"></i> ${data.fecha}</span>
            <span><i class="fa-solid fa-user-tie opacity-50 mr-4"></i> <?php echo L('history_report_z_resp'); ?> <b>${data.nombre_usuario || <?php echo json_encode(L('system')); ?>}</b></span>
        `;

        const fmt = (num) => new Intl.NumberFormat('de-DE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(num) + ' €';
        const rango = (data.primera_venta && data.ultima_venta) ?
            `${data.primera_venta.substring(11, 16)}h → ${data.ultima_venta.substring(11, 16)}h` : <?php echo json_encode(L('history_report_z_no_activity')); ?>;

        document.getElementById('z-rango').innerText = rango;
        document.getElementById('z-tickets').innerText = data.num_tickets || 0;
        const deuda = parseFloat(data.deuda_generada) || 0;
        const deudaCard = document.getElementById('z-deuda-card');
        document.getElementById('z-deuda').innerText = (deuda >= 0 ? '+' : '') + fmt(deuda);
        document.getElementById('z-efectivo').innerText = fmt(data.total_efectivo);
        document.getElementById('z-tarjeta').innerText = fmt(data.total_tarjeta);
        document.getElementById('z-bizum').innerText = fmt(data.total_bizum || 0);
        document.getElementById('z-total').innerText = fmt(data.total_general);

        if (deuda === 0) {
            deudaCard.style.background = 'var(--green-light)';
            deudaCard.style.borderColor = 'rgba(15,128,96,0.2)';
            document.getElementById('z-deuda').className = 'fs-24 font-bold font-mono text-green';
        } else {
            deudaCard.style.background = 'var(--red-light)';
            deudaCard.style.borderColor = 'rgba(192,57,43,0.2)';
            document.getElementById('z-deuda').className = 'fs-24 font-bold font-mono text-red';
        }

        fetch('api/cajaInfoCierre.php?id=' + data.id)
            .then(r => r.json())
            .then(info => {
                const bodyRet = document.getElementById('z-retiros-body');
                const bodyTur = document.getElementById('z-turnos-body');
                bodyRet.innerHTML = '';
                if (bodyTur) bodyTur.innerHTML = '';
                const fmtNum = (n) => new Intl.NumberFormat('de-DE', {
                    minimumFractionDigits: 2
                }).format(n) + ' €';

                if (info.ok && info.retiros && info.retiros.length) {
                    info.retiros.forEach(m => {
                        const tr = document.createElement('tr');
                        const hora = m.created_at ? m.created_at.substring(11, 16) : '-';
                        const colorClass = m.tipo === 'ingreso' ? 'text-green' : 'text-red';
                        const prefijo = m.tipo === 'ingreso' ? '+' : '-';
                        tr.innerHTML = `
                            <td class="pl-12 font-mono">${hora}h</td>
                            <td class="fw-600">${m.nombre_usuario || '-'}</td>
                            <td class="text-muted"><span class="badge ${m.tipo === 'ingreso' ? 'bg-green-light text-green' : 'bg-red-light text-red'} fs-9 mr-4">${m.tipo.toUpperCase()}</span> ${m.concepto || 'Operación'}</td>
                            <td class="text-right pr-12 font-mono font-bold ${colorClass}">${prefijo}${fmtNum(m.importe)}</td>
                        `;
                        bodyRet.appendChild(tr);
                    });
                } else {
                    bodyRet.innerHTML = '<tr><td colspan="4" class="text-center py-24 text-muted fs-12 italic">' + <?php echo json_encode(L('history_js_no_movs')); ?> + '</td></tr>';
                }

                if (info.ok && info.turnos && info.turnos.length) {
                    window._currentTurnosData = info.turnos;
                    document.getElementById('z-num-turnos').innerText = info.turnos.length + ' ' + (info.turnos.length === 1 ? <?php echo json_encode(L('history_js_shift')); ?> : <?php echo json_encode(L('history_js_shifts')); ?>);
                    info.turnos.forEach((t, index) => {
                        const tr = document.createElement('tr');
                        const h_ape = t.fecha_apertura ? t.fecha_apertura.substring(11, 16) : '-';
                        const h_cie = t.fecha_cierre ? t.fecha_cierre.substring(11, 16) : 'Abierto';
                        const resp = t.nombre_usuario_apertura || '?';
                        tr.innerHTML = `
                            <td class="pl-12">
                                <span class="badge bg-surface2 border text-muted mr-8 font-mono">T-${String(t.id).padStart(3, '0')}</span>
                                <span class="fw-600">${h_ape}h - ${h_cie}h</span>
                            </td>
                            <td class="tt-uppercase fs-11 fw-600">${resp}</td>
                            <td class="text-right pr-12">
                                <button onclick="verDetalleTurno(${index})" class="btn-cancel px-12 py-4 fs-10 hover-scale">
                                    <i class="fa-solid fa-eye mr-4"></i> <?php echo L('history_th_actions'); ?>
                                </button>
                            </td>
                        `;
                        bodyTur.appendChild(tr);
                    });
                } else if (bodyTur) {
                    document.getElementById('z-num-turnos').innerText = '0 ' + <?php echo json_encode(L('history_js_shifts')); ?>;
                    bodyTur.innerHTML = '<tr><td colspan="3" class="text-center py-24 text-muted fs-12 italic">' + <?php echo json_encode(L('history_js_no_shifts')); ?> + '</td></tr>';
                }

                // Recopilar datos de turnos y retiros para la ventana de impresión
                const turnosRows = bodyTur ? bodyTur.innerHTML : '';
                const retirosRows = bodyRet.innerHTML;

                // Guardar datos para el botón de imprimir
                window._currentZData = {
                    data,
                    info,
                    fmt,
                    fmtNum,
                    rango,
                    turnosRows,
                    retirosRows
                };
            })
            .catch(() => {
                document.getElementById('z-retiros-body').innerHTML = '<tr><td colspan="4" class="text-center text-red fs-12">' + <?php echo json_encode(L('history_js_load_movs_error')); ?> + '</td></tr>';
            })
            .finally(() => {
                document.getElementById('modalReporteZ').style.display = 'flex';
            });
    }

    function imprimirReporteZ() {
        const d = window._currentZData;
        if (!d) return;

        const { data, fmt, fmtNum, rango, turnosRows, retirosRows } = d;
        const deuda = parseFloat(data.deuda_generada) || 0;
        const deudaColor = deuda > 0 ? '#dc2626' : '#16a34a';

        // Escapar strings y pre-calcular traducciones
        const empresa = <?php echo json_encode($avConfig['empresa_nombre'] ?? 'ElectroBazar'); ?>;
        const langCode = <?php echo json_encode(($_SESSION['lang'] ?? 'es') === 'en' ? 'en-GB' : 'es-ES'); ?>;
        
        // Traducciones pre-escapadas
        const t = {
            sub: <?php echo json_encode(L('history_report_z_sub')); ?>,
            resp: <?php echo json_encode(L('history_report_z_th_shift_resp')); ?>,
            activity: <?php echo json_encode(L('history_report_z_activity')); ?>,
            system: <?php echo json_encode(L('system')); ?>,
            summary: <?php echo json_encode(L('history_report_z_ops_summary')); ?>,
            ops: <?php echo json_encode(L('history_report_z_ops')); ?>,
            tickets: <?php echo json_encode(L('history_th_tickets_z')); ?>,
            imbalance: <?php echo json_encode(L('history_report_z_imbalance')); ?>,
            breakdown: <?php echo json_encode(L('history_report_z_breakdown')); ?>,
            cash: <?php echo json_encode(L('tpv_method_cash')); ?>,
            card: <?php echo json_encode(L('tpv_method_card')); ?>,
            bizum: <?php echo json_encode(L('tpv_method_bizum')); ?>,
            totalArqueado: <?php echo json_encode(L('history_report_z_total_arqueado')); ?>,
            shiftsTitle: <?php echo json_encode(L('history_report_z_shifts_title')); ?>,
            shiftOpen: <?php echo json_encode(L('history_report_z_th_shift_open')); ?>,
            shiftClose: <?php echo json_encode(L('history_report_z_th_shift_close')); ?>,
            shiftReal: <?php echo json_encode(L('history_report_z_th_shift_real')); ?>,
            noShifts: <?php echo json_encode(L('history_js_no_shifts')); ?>,
            movsTitle: <?php echo json_encode(L('history_report_z_movements_title')); ?>,
            movTime: <?php echo json_encode(L('history_report_z_th_mov_time')); ?>,
            movConcept: <?php echo json_encode(L('history_report_z_th_mov_concept')); ?>,
            movAmount: <?php echo json_encode(L('history_report_z_th_mov_amount')); ?>,
            noMovs: <?php echo json_encode(L('history_js_no_movs')); ?>,
            footer: <?php echo json_encode(L('history_report_z_footer')); ?>
        };

        let html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">';
        html += '<title>Cierre Fiscal #' + String(data.id).padStart(3,"0") + '</title>';
        html += '<style>' +
                '* { box-sizing: border-box; margin: 0; padding: 0; }' +
                'body { font-family: "Courier New", monospace; background: #fff; color: #000; font-size: 12px; padding: 20mm 15mm; }' +
                '.header { text-align: center; border-bottom: 3px double #000; padding-bottom: 12px; margin-bottom: 16px; }' +
                '.header .empresa { font-size: 20px; font-weight: 900; letter-spacing: 3px; text-transform: uppercase; }' +
                '.header .subtitulo { font-size: 11px; letter-spacing: 1px; color: #555; margin-top: 2px; }' +
                '.header .num-cierre { font-size: 28px; font-weight: 900; letter-spacing: -1px; margin: 8px 0 4px; }' +
                '.header .meta { font-size: 11px; color: #444; display: flex; justify-content: center; gap: 24px; margin-top: 6px; }' +
                '.seccion { margin-bottom: 14px; }' +
                '.seccion-titulo { font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; border-bottom: 1px solid #000; padding-bottom: 4px; margin-bottom: 8px; }' +
                '.grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 14px; }' +
                '.kpi { border: 1px solid #ccc; padding: 10px 12px; }' +
                '.kpi .label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #666; margin-bottom: 4px; }' +
                '.kpi .valor { font-size: 16px; font-weight: 900; }' +
                '.total-box { border: 2px solid #000; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; background: #f0f0f0; }' +
                '.total-box .label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }' +
                '.total-box .valor { font-size: 24px; font-weight: 900; letter-spacing: -1px; }' +
                'table { width: 100%; border-collapse: collapse; font-size: 11px; }' +
                'thead tr { background: #000; color: #fff; }' +
                'thead th { padding: 6px 8px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }' +
                'tbody td { padding: 6px 8px; border-bottom: 1px solid #ddd; }' +
                '.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }' +
                '.pie { margin-top: 20px; padding-top: 12px; border-top: 3px double #000; text-align: center; font-size: 10px; color: #666; letter-spacing: 1px; }' +
                '.text-right { text-align: right; }' +
                '@media print { body { padding: 10mm; } @page { margin: 10mm; size: A4; } }' +
                '</style></head><body>';

        html += '<div class="header">' +
                '<div class="empresa">' + empresa + '</div>' +
                '<div class="subtitulo">' + t.sub + '</div>' +
                '<div class="num-cierre">#Cierre-' + String(data.id).padStart(3,"0") + '</div>' +
                '<div class="meta">' +
                '<span>' + data.fecha + '</span> ' +
                '<span>' + t.resp + ': <strong>' + (data.nombre_usuario || t.system) + '</strong></span> ' +
                '<span>' + t.activity + ': ' + rango + '</span>' +
                '</div></div>';

        html += '<div class="seccion">' +
                '<div class="seccion-titulo">' + t.summary + '</div>' +
                '<div class="grid-3">' +
                '<div class="kpi"><div class="label">' + t.ops + '</div><div class="valor">' + (data.num_tickets || 0) + ' ' + t.tickets + '</div></div>' +
                '<div class="kpi"><div class="label">' + t.activity + '</div><div class="valor">' + rango + '</div></div>' +
                '<div class="kpi" style="border-color: ' + deudaColor + ';">' +
                '<div class="label">' + t.imbalance + '</div>' +
                '<div class="valor" style="color: ' + deudaColor + ';">' + (deuda >= 0 ? '+' : '') + fmt(deuda) + '</div></div>' +
                '</div></div>';

        html += '<div class="seccion">' +
                '<div class="seccion-titulo">' + t.breakdown + '</div>' +
                '<div class="grid-3">' +
                '<div class="kpi"><div class="label">' + t.cash + '</div><div class="valor">' + fmt(data.total_efectivo) + '</div></div>' +
                '<div class="kpi"><div class="label">' + t.card + '</div><div class="valor">' + fmt(data.total_tarjeta) + '</div></div>' +
                '<div class="kpi"><div class="label">' + t.bizum + '</div><div class="valor">' + fmt(data.total_bizum || 0) + '</div></div>' +
                '</div></div>';

        html += '<div class="total-box"><div class="label">' + t.totalArqueado + '</div><div class="valor">' + fmt(data.total_general) + '</div></div>';

        html += '<div class="grid-2"><div class="seccion">' +
                '<div class="seccion-titulo">' + t.shiftsTitle + '</div>' +
                '<table><thead><tr><th>' + t.shiftOpen + '</th><th>' + t.shiftClose + '</th><th>' + t.resp + '</th><th class="text-right">' + t.shiftReal + '</th></tr></thead>' +
                '<tbody>' + (turnosRows || '<tr><td colspan="4" style="text-align:center; padding:12px; color:#999;">' + t.noShifts + '</td></tr>') + '</tbody>' +
                '</table></div>';

        html += '<div class="seccion">' +
                '<div class="seccion-titulo">' + t.movsTitle + '</div>' +
                '<table><thead><tr><th>' + t.movTime + '</th><th>' + t.movConcept + '</th><th class="text-right">' + t.movAmount + '</th></tr></thead>' +
                '<tbody>' + (retirosRows || '<tr><td colspan="3" style="text-align:center; padding:12px; color:#999;">' + t.noMovs + '</td></tr>') + '</tbody>' +
                '</table></div></div>';

        html += '<div class="pie">' + empresa + ' TPV &nbsp;·&nbsp; ' + t.footer + ' &nbsp;·&nbsp; ' + new Date().toLocaleString(langCode) + '</div>';
        html += '<' + 'script>window.onload = () => { window.print(); setTimeout(() => window.close(), 500); };<' + '/script>';
        html += '</body></html>';

        const ventana = window.open('', '_blank', 'width=900,height=700');
        if (ventana) {
            ventana.document.write(html);
            ventana.document.close();
        }
    }

    function cerrarModalZ() {
        document.getElementById('modalReporteZ').style.display = 'none';
    }

    // ── Detalle Turno (Modal 2º Nivel) ──────────────────────────────────────────
    function verDetalleTurno(index) {
        const t = window._currentTurnosData[index];
        if (!t) return;

        const fmt = (num) => new Intl.NumberFormat('de-DE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(num) + ' €';

        const h_ape = t.fecha_apertura ? t.fecha_apertura.substring(11, 16) : '-';
        const h_cie = t.fecha_cierre ? t.fecha_cierre.substring(11, 16) : 'Abierto';
        const res = t.resumen || {};

        document.getElementById('td-titulo').innerText = <?php echo json_encode(L('history_js_shift')); ?> + " T-" + String(t.id).padStart(3, '0');
        document.getElementById('td-periodo').innerText = <?php echo json_encode(L('history_modal_shift_horario')); ?> + ": " + h_ape + "h - " + h_cie + "h | " + <?php echo json_encode(L('history_modal_shift_resp')); ?> + ": " + (t.nombre_usuario_apertura || '?');
        document.getElementById('td-total').innerText = fmt(res.total || 0);
        document.getElementById('td-tickets').innerText = res.num_tickets || 0;

        // Limpiar y llenar métodos
        const containerMetodos = document.getElementById('td-metodos');
        containerMetodos.innerHTML = '';
        const metodos = [
            { label: <?php echo json_encode(L('tpv_method_cash')); ?>, val: res.efectivo, class: 'text-green', icon: 'fa-money-bill-1-wave', bg: 'rgba(34, 197, 94, 0.15)', iconColor: '#16a34a' },
            { label: <?php echo json_encode(L('tpv_method_card')); ?>, val: res.tarjeta, class: 'text-blue', icon: 'fa-credit-card', bg: 'rgba(59, 130, 246, 0.15)', iconColor: '#2563eb' },
            { label: <?php echo json_encode(L('tpv_method_bizum')); ?>, val: res.bizum, class: 'text-accent', icon: 'fa-mobile-screen-button', bg: 'rgba(79, 70, 229, 0.15)', iconColor: '#4f46e5' },
        ];
        metodos.forEach(m => {
            const div = document.createElement('div');
            div.className = 'bg-surface p-20 border transition-all hover-translate-y shadow-sm';
            div.style.display = 'flex';
            div.style.alignItems = 'center';
            div.style.gap = '16px';
            div.style.borderRadius = '16px';
            div.innerHTML = `
                <div style="width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; background: ${m.bg}; color: ${m.iconColor}; border: 1.5px solid rgba(0,0,0,0.03);">
                    <i class="fa-solid ${m.icon}"></i>
                </div>
                <div class="flex-1">
                    <div class="fs-11 text-muted mb-2 tt-uppercase font-bold" style="letter-spacing: 0.8px; opacity: 0.8;">${m.label}</div>
                    <div class="fs-20 font-bold font-mono ${m.class}">${fmt(m.val || 0)}</div>
                </div>
            `;
            containerMetodos.appendChild(div);
        });

        // Llenar movimientos
        const bodyMov = document.getElementById('td-movimientos');
        bodyMov.innerHTML = '';
        if (t.movimientos && t.movimientos.length) {
            t.movimientos.forEach(m => {
                const tr = document.createElement('tr');
                const hora = m.created_at ? m.created_at.substring(11, 16) : '-';
                const color = m.tipo === 'ingreso' ? 'text-green' : 'text-red';
                tr.innerHTML = `
                    <td class="pl-12 font-mono">${hora}h</td>
                    <td class="text-muted"><span class="badge ${m.tipo === 'ingreso'?'bg-green-light text-green':'bg-red-light text-red'} fs-8 mr-4">${m.tipo.toUpperCase()}</span> ${m.concepto || 'Operación'}</td>
                    <td class="text-right pr-12 font-mono font-bold ${color}">${m.tipo==='ingreso'?'+':'-'}${fmt(m.importe)}</td>
                `;
                bodyMov.appendChild(tr);
            });
        } else {
            bodyMov.innerHTML = '<tr><td colspan="3" class="text-center py-12 text-muted italic fs-10">' + <?php echo json_encode(L('history_modal_shift_no_movs')); ?> + '</td></tr>';
        }

        document.getElementById('modalTurnoDetalle').style.display = 'flex';
    }

    function cerrarTurnoDetalle() {
        document.getElementById('modalTurnoDetalle').style.display = 'none';
    }

    // ── Lógica de Filtros Avanzados ──────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('formFiltros');
        if (!form) return;

        const periodSelect = document.getElementById('filterPeriodo');
        const customDates = document.getElementById('customDates');
        const searchInput = document.getElementById('searchTicket');
        
        // Toggle de fechas personalizadas
        if (periodSelect) {
            periodSelect.addEventListener('change', () => {
                if (periodSelect.value === 'personalizado') {
                    customDates.style.display = 'contents';
                } else {
                    customDates.style.display = 'none';
                    form.submit();
                }
            });
        }

        // Auto-submit en cambios de select
        form.querySelectorAll('select').forEach(sel => {
            if (sel.id !== 'filterPeriodo') {
                sel.addEventListener('change', () => form.submit());
            }
        });

        // Auto-submit en cambios de fecha
        form.querySelectorAll('input[type="date"]').forEach(inp => {
            inp.addEventListener('change', () => form.submit());
        });

        // Debounce para búsqueda de ticket
        if (searchInput) {
            let timeout = null;
            searchInput.addEventListener('input', () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    form.submit();
                }, 500);
            });
        }
    });
</script>