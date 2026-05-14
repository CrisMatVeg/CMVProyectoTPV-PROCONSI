<div class="main-full p-24">

    <!-- CABECERA DE SECCIÓN -->
    <div class="section-header container-wider">
        <div class="d-flex ai-center gap-16">
            <a href="index.php?irDashboard=1" class="btn-prominent-back compact" title="<?php echo L('login_back'); ?>">
                <i class="fa-solid fa-chevron-left"></i>
                <span><?php echo L('login_back'); ?></span>
            </a>
            <div class="vr" style="height: 32px; width: 1px; background: var(--border); opacity: 0.5;"></div>
            <div class="section-title">
                <h1><?php echo L('dashboard_btn_purchases'); ?></h1>
                <p><?php echo L('dashboard_btn_purchases_sub'); ?></p>
            </div>
        </div>
        <div class="d-flex gap-12 ai-center">
            <button onclick="abrirModalNuevoAlbaran()" class="btn-add">
                <i class="fa-solid fa-truck-ramp-box"></i> <?php echo L('purchase_btn_new_albaran'); ?>
            </button>
            <button onclick="abrirModalNuevaFactura()" class="btn-save" style="background: var(--accent);">
                <i class="fa-solid fa-file-invoice-dollar"></i> <?php echo L('purchase_btn_new_invoice'); ?>
            </button>
        </div>
    </div>

    <!-- TABS -->
    <div class="container-wider mb-24">
        <div class="d-flex gap-24 border-bottom pb-8">
            <button class="tab-btn active" onclick="switchTab('albaranes', this)">
                <i class="fa-solid fa-boxes-stacked mr-8"></i> <?php echo L('purchase_tab_albaranes'); ?>
            </button>
            <button class="tab-btn" onclick="switchTab('facturas', this)">
                <i class="fa-solid fa-file-invoice mr-8"></i> <?php echo L('purchase_tab_facturas'); ?>
            </button>
        </div>
    </div>

    <!-- SECCIÓN ALBARANES -->
    <div id="tab-albaranes" class="purchase-tab-content">
        <div class="table-container container-wider br-20">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="w-180 pl-20"><?php echo L('prod_th_date'); ?></th>
                        <th><?php echo L('purchase_th_albaran'); ?></th>
                        <th><?php echo L('prod_modal_label_provider'); ?></th>
                        <th class="text-right"><?php echo L('tpv_total'); ?></th>
                        <th class="text-center"><?php echo L('prod_th_status'); ?></th>
                        <th class="text-center pr-20"><?php echo L('prod_th_actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($avInicioPrivado['historico_albaranes'])): ?>
                        <tr>
                            <td colspan="6" class="p-40 text-center text-muted"><?php echo L('purchase_no_albaranes'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($avInicioPrivado['historico_albaranes'] as $a): ?>
                            <tr>
                                <td class="pl-20 font-mono text-muted"><?php echo date('d/m/Y', strtotime($a['fecha'])); ?></td>
                                <td class="font-bold"><?php echo htmlspecialchars($a['numero_albaran']); ?></td>
                                <td><?php echo htmlspecialchars($a['proveedor_nombre']); ?></td>
                                <td class="text-right font-mono"><?php echo number_format($a['total'], 2, ',', '.'); ?> €</td>
                                <td class="text-center">
                                    <?php 
                                        $badgeClass = 'badge-info';
                                        if ($a['estado'] === 'recibido') $badgeClass = 'badge-warning';
                                        elseif ($a['estado'] === 'validado') $badgeClass = 'badge-info';
                                        elseif ($a['estado'] === 'facturado') $badgeClass = 'badge-success';
                                        elseif ($a['estado'] === 'anulado') $badgeClass = 'badge-danger';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo L('purchase_status_' . strtolower($a['estado'])); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex jc-center gap-8">
                                        <?php if ($a['estado'] === 'recibido'): ?>
                                            <button class="btn-icon text-success" onclick="validarAlbaran(<?php echo $a['id']; ?>)" title="<?php echo L('purchase_btn_validate'); ?>">
                                                <i class="fa-solid fa-circle-check"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn-icon" onclick="verDetalleAlbaran(<?php echo $a['id']; ?>)" title="<?php echo L('purchase_modal_details_title'); ?>">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SECCIÓN FACTURAS -->
    <div id="tab-facturas" class="purchase-tab-content d-none">
        <div class="table-container container-wider br-20">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="w-180 pl-20"><?php echo L('purchase_th_invoice_date'); ?></th>
                        <th><?php echo L('purchase_th_invoice_num'); ?> / <?php echo L('purchase_th_due_date'); ?></th>
                        <th><?php echo L('prod_modal_label_provider'); ?></th>
                        <th class="text-right"><?php echo L('tpv_total'); ?></th>
                        <th class="text-center"><?php echo L('prod_th_status'); ?></th>
                        <th class="text-center pr-20"><?php echo L('prod_th_actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($avInicioPrivado['historico_facturas'])): ?>
                        <tr>
                            <td colspan="6" class="p-40 text-center text-muted"><?php echo L('purchase_no_facturas'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($avInicioPrivado['historico_facturas'] as $f): ?>
                            <tr>
                                <td class="pl-20 font-mono text-muted"><?php echo date('d/m/Y', strtotime($f['fecha_factura'])); ?></td>
                                <td>
                                    <div class="font-bold"><?php echo htmlspecialchars($f['numero_factura']); ?></div>
                                    <div class="fs-12 text-muted"><?php echo L('purchase_th_due_date'); ?>: <?php echo date('d/m/Y', strtotime($f['fecha_vencimiento'])); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($f['proveedor_nombre']); ?></td>
                                <td class="text-right font-bold font-mono text-accent">
                                    <?php echo number_format($f['total'], 2, ',', '.'); ?> €
                                </td>
                                <td class="text-center">
                                    <?php if ($f['pagado']): ?>
                                        <span class="badge badge-success">
                                            <i class="fa-solid <?php echo $f['metodo_pago'] === 'caja' ? 'fa-cash-register' : 'fa-building-columns'; ?> mr-4"></i>
                                            <?php echo L('purchase_status_facturado'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">
                                            <i class="fa-solid fa-clock mr-4"></i>
                                            <?php echo L('purchase_status_recibido'); ?> (Pendiente)
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex jc-center gap-8">
                                        <?php if (!$f['pagado']): ?>
                                            <button class="btn-icon text-success" onclick="pagarFactura(<?php echo $f['id']; ?>)" title="<?php echo L('purchase_btn_pay'); ?>">
                                                <i class="fa-solid fa-money-bill-transfer"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn-icon" onclick="verDetalleFactura(<?php echo $f['id']; ?>)">
                                            <i class="fa-solid fa-file-lines"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL NUEVO ALBARÁN (Stock Entry) -->
<style>
    /* === ALBARAN MODAL === */
    #modalNuevoAlbaran .modal-header h2 {
        font-size: 18px;
    }

    #modalNuevoAlbaran .modal-header {
        padding-bottom: 16px;
    }

    #albLineas tr td {
        padding: 6px 8px;
        vertical-align: middle;
    }

    #albLineas input[type="number"] {
        font-family: monospace;
        font-size: 13px;
    }

    #albLineas .price-cell {
        background: var(--surface2);
        border-radius: 6px;
        padding: 4px 8px;
        text-align: right;
        font-weight: 700;
        font-family: monospace;
        min-width: 100px;
    }

    .alb-info-box {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(99, 102, 241, 0.06));
        border: 1px solid rgba(59, 130, 246, 0.2);
        border-radius: 12px;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
        font-size: 12px;
        line-height: 1.5;
        color: var(--text-muted);
    }

    .alb-info-box strong {
        color: var(--text);
    }

    .alb-total-box {
        background: var(--surface);
        border: 2px solid var(--accent);
        border-radius: 12px;
        padding: 16px 24px;
        text-align: right;
        width: 100%;
        margin-bottom: 20px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    .alb-total-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-muted);
        margin-bottom: 4px;
    }

    .alb-total-amount {
        font-size: 28px;
        font-weight: 800;
        font-family: monospace;
        color: var(--accent);
        line-height: 1;
    }

    .empty-lines-state {
        text-align: center;
        padding: 32px;
        color: var(--text-muted);
        font-size: 13px;
    }

    .empty-lines-state i {
        font-size: 28px;
        margin-bottom: 8px;
        opacity: 0.3;
        display: block;
    }
</style>

<div id="modalNuevoAlbaran" class="modal-overlay-bg">
    <div class="modal-content" style="max-width: 1000px; width: 95%; height: auto; max-height: 90vh; border-radius: 20px; overflow: hidden; display: flex; flex-direction: column;">
        <div class="modal-header">
            <div>
                <h2 style="margin:0 0 2px 0"><?php echo L('purchase_modal_albaran_title'); ?></h2>
                <span style="font-size:12px; color: var(--text-muted); font-weight:400;"><?php echo L('purchase_modal_albaran_sub'); ?></span>
            </div>
            <button class="btn-close-modal" onclick="cerrarModalNuevoAlbaran()">&times;</button>
        </div>

        <div class="p-24 overflow-y-auto" style="flex: 1; min-height: 0;">
            <!-- Cabecera del albarán -->
            <div class="d-grid grid-3 gap-16 mb-20 p-16 bg-surface2 br-12 border-2">
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase"><?php echo L('prod_modal_label_provider'); ?> *</label>
                    <select id="albProveedor" class="form-input" onchange="actualizarREAlbaran()">
                        <option value="">-- <?php echo L('purchase_select_prov_hint'); ?> --</option>
                        <?php foreach ($avInicioPrivado['proveedores'] as $p): ?>
                            <option value="<?php echo $p['id']; ?>" data-re="<?php echo $p['aplica_re'] ? '1' : '0'; ?>">
                                <?php echo htmlspecialchars($p['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase"><?php echo L('purchase_label_albaran_ref'); ?> *</label>
                    <input type="text" id="albNum" class="form-input font-mono" placeholder="<?php echo L('purchase_placeholder_alb_ref'); ?>">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase"><?php echo L('purchase_label_entry_date'); ?></label>
                    <input type="date" id="albFecha" class="form-input" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>

            <!-- Búsqueda de productos -->
            <div class="search-bar mb-12">
                <div class="search-input-wrap flex-1">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="albBusqueda" class="search-input" placeholder="<?php echo L('purchase_search_placeholder'); ?>" onkeyup="buscarProductoAlbaran(this.value)">
                    <div id="albResultados" class="search-results-dropdown d-none"></div>
                </div>
            </div>

            <!-- Tabla de líneas -->
            <div class="table-container mb-16">
                <div style="max-height: 320px; overflow-y: auto; overflow-x: auto;">
                    <table class="data-table mb-0" style="min-width: 700px;">
                        <thead style="position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th class="pl-20"><?php echo L('prod_th_name'); ?></th>
                                <th class="w-100 text-center"><?php echo L('prod_th_qty'); ?></th>
                                <th class="w-180 text-right"><?php echo L('purchase_th_cost_net'); ?></th>
                                <th class="w-70 text-center"><?php echo L('modal_label_iva'); ?></th>
                                <th class="w-70 text-center th-re">RE</th>
                                <th class="w-140 text-right"><?php echo L('purchase_th_line_total'); ?></th>
                                <th class="w-50"></th>
                            </tr>
                        </thead>
                        <tbody id="albLineas">
                            <tr id="albEmptyRow">
                                <td colspan="7">
                                    <div class="empty-lines-state">
                                        <i class="fa-solid fa-box-open"></i>
                                        <?php echo L('purchase_empty_lines'); ?>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Info de stock -->
            <div class="alb-info-box">
                <i class="fa-solid fa-circle-info text-accent fs-20"></i>
                <p class="m-0">
                    <?php echo L('purchase_info_stock_cmp'); ?>
                    <?php echo L('purchase_info_pending'); ?>
                </p>
            </div>
        </div>

        <div class="modal-footer border-top p-32 bg-surface2" style="flex-shrink: 0; z-index: 5; flex-direction: column; display: flex;">
            <div class="alb-total-box">
                <div class="alb-total-label"><?php echo L('purchase_total_albaran'); ?></div>
                <div class="alb-total-amount"><span id="albTotal">0,00</span> <span style="font-size:18px">€</span></div>
            </div>
            
            <div class="d-flex w-100 ai-center" style="margin-top: 4px;">
                <button class="btn-cancel" onclick="cerrarModalNuevoAlbaran()"><?php echo L('modal_cancel'); ?></button>
                <div class="flex-1"></div>
                <button id="btnGuardarAlbaran" class="btn-save w-auto px-32" onclick="guardarAlbaran()">
                    <i class="fa-solid fa-check-circle mr-8"></i> <?php echo L('purchase_btn_process'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL REGISTRAR FACTURA (Billing and Payment) -->
<div id="modalNuevaFactura" class="modal-overlay-bg">
    <div class="modal-content w-700" style="max-width: 700px; border-radius: 20px; overflow: hidden;">
        <div class="modal-header">
            <h2><?php echo L('purchase_modal_fac_title'); ?></h2>
            <button class="btn-close-modal" onclick="cerrarModalNuevaFactura()">&times;</button>
        </div>

        <div class="p-24">
            <div class="d-grid grid-2 gap-16 mb-24">
                <div class="form-group">
                    <label class="form-label fs-11 tt-uppercase"><?php echo L('prod_modal_label_provider'); ?></label>
                    <select id="facProveedor" class="form-input" onchange="cargarAlbaranesPendientes()">
                        <option value="">-- <?php echo L('purchase_select_prov_hint'); ?> --</option>
                        <?php foreach ($avInicioPrivado['proveedores'] as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label fs-11 tt-uppercase"><?php echo L('purchase_label_fac_num'); ?> *</label>
                    <input type="text" id="facNum" class="form-input font-mono" placeholder="<?php echo L('purchase_placeholder_fac_num'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label fs-11 tt-uppercase"><?php echo L('purchase_label_fac_date'); ?></label>
                    <input type="date" id="facFecha" class="form-input" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label fs-11 tt-uppercase"><?php echo L('tpv_payment_method'); ?></label>
                    <select id="facPago" class="form-input">
                        <option value="banco"><?php echo L('purchase_method_bank'); ?></option>
                        <option value="caja"><?php echo L('purchase_method_cash'); ?></option>
                        <option value="otro"><?php echo L('purchase_method_other'); ?></option>
                    </select>
                </div>
                <div class="form-group d-flex ai-center gap-8 mt-24">
                    <input type="checkbox" id="facPagada" checked style="width: 20px; height: 20px;">
                    <label for="facPagada" class="form-label mb-0 fs-13 fw-700"><?php echo L('purchase_label_mark_paid'); ?></label>
                </div>
            </div>

            <div class="mb-8 fs-12 fw-700 tt-uppercase opacity-50"><?php echo L('purchase_label_pending_alb'); ?></div>
            <div id="listaAlbaranesPendientes" class="border-2 br-12 p-8 overflow-y-auto mb-24" style="max-height: 200px; background: var(--surface2);">
                <div class="p-20 text-center opacity-50 fs-13"><?php echo L('purchase_select_prov_hint'); ?></div>
            </div>

            <div class="bg-surface p-20 br-16 border-2 text-right">
                <div class="d-flex jc-space-between ai-center">
                    <span class="font-bold text-accent"><?php echo L('purchase_total_to_pay'); ?></span>
                    <span class="fs-24 font-bold text-accent font-mono"><span id="facTotal">0,00</span> €</span>
                </div>
            </div>
        </div>

        <div class="modal-footer pt-16 border-top p-24">
            <button class="btn-cancel" onclick="cerrarModalNuevaFactura()"><?php echo L('modal_cancel'); ?></button>
            <div class="flex-1"></div>
            <button id="btnGuardarFactura" class="btn-save w-auto px-32" onclick="guardarFactura()" disabled>
                <i class="fa-solid fa-file-invoice-dollar mr-8"></i> <?php echo L('purchase_btn_generate_fac'); ?>
            </button>
        </div>
    </div>
</div>

<!-- MODAL PAGO FACTURA PENDIENTE -->
<div id="modalPagarFactura" class="modal-overlay-bg">
    <div class="modal-content" style="max-width: 440px; border-radius: 20px; overflow: hidden;">
        <div class="modal-header">
            <h2><?php echo L('purchase_btn_pay'); ?></h2>
            <button class="btn-close-modal" onclick="cerrarModalPagarFactura()">&times;</button>
        </div>
        <div class="p-24">
            <div class="mb-20">
                <div class="fs-12 tt-uppercase opacity-60 mb-4"><?php echo L('purchase_total_to_pay'); ?></div>
                <div class="fs-28 fw-700 font-mono text-accent" id="pagoFacTotal">0,00 €</div>
            </div>
            <div class="form-group mb-16">
                <label class="form-label fs-11 tt-uppercase"><?php echo L('tpv_payment_method'); ?></label>
                <select id="pagoFacMetodo" class="form-input" onchange="onCambioMetodoPagoFactura()">
                    <option value="banco"><?php echo L('purchase_method_bank'); ?></option>
                    <option value="caja"><?php echo L('purchase_method_cash'); ?></option>
                    <option value="otro"><?php echo L('purchase_method_other'); ?></option>
                </select>
            </div>
            <div id="pagoFacCajaInfo" class="bg-surface p-12 br-8 border-2 d-none">
                <div class="d-flex jc-space-between ai-center">
                    <span class="fs-12 opacity-60"><?php echo L('tpv_cash_in_drawer'); ?>:</span>
                    <span class="fw-700 font-mono" id="pagoFacEfectivoDisp">...</span>
                </div>
                <div id="pagoFacAlerta" class="d-none mt-8 fs-12 fw-700" style="color: var(--danger);">
                    &#9888; <?php echo L('purchase_js_insufficient_cash'); ?>
                </div>
            </div>
        </div>
        <div class="modal-footer pt-16 border-top p-24">
            <button class="btn-cancel" onclick="cerrarModalPagarFactura()"><?php echo L('modal_cancel'); ?></button>
            <div class="flex-1"></div>
            <button id="btnConfirmarPagoFactura" class="btn-save w-auto px-32" onclick="confirmarPagoFactura()">
                <i class="fa-solid fa-money-bill-transfer mr-8"></i> <?php echo L('purchase_btn_pay'); ?>
            </button>
        </div>
    </div>
</div>

<!-- MODAL DETALLES -->
<div id="modalDetalle" class="modal-overlay-bg">
    <div class="modal-content w-600" style="max-width: 600px; border-radius: 20px; overflow: hidden;">
        <div class="modal-header">
            <h2 id="detalleTitulo"><?php echo L('purchase_modal_details_title'); ?></h2>
            <button class="btn-close-modal" onclick="cerrarModalDetalle()">&times;</button>
        </div>
        <div id="detalleContent" class="p-24 overflow-y-auto" style="max-height: 70vh;"></div>
        <div class="modal-footer pt-16 border-top p-24">
            <button class="btn-cancel" onclick="cerrarModalDetalle()"><?php echo L('modal_close'); ?></button>
        </div>
    </div>
</div>

<style>
    .tab-btn {
        background: none;
        border: none;
        padding: 12px 24px;
        cursor: pointer;
        opacity: 0.5;
        transition: 0.3s;
        font-weight: 700;
        border-bottom: 3px solid transparent;
    }

    .tab-btn.active {
        opacity: 1;
        border-bottom-color: var(--accent);
        color: var(--accent);
    }

    .badge {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .badge-warning {
        background: rgba(255, 152, 0, 0.15);
        color: #f57c00;
    }

    .badge-success {
        background: rgba(76, 175, 80, 0.15);
        color: #388e3c;
    }

    .badge-info {
        background: rgba(33, 150, 243, 0.15);
        color: #1976d2;
    }

    .badge-danger {
        background: rgba(244, 43, 85, 0.15);
        color: #d32f2f;
    }

    .alb-item {
        cursor: pointer;
        padding: 10px 16px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--surface);
        margin-bottom: 6px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: 0.2s;
    }

    .alb-item:hover {
        border-color: var(--accent);
        background: var(--blue-light);
    }

    .alb-item.selected {
        background: var(--accent);
        color: white;
        border-color: var(--accent);
    }

    .search-results-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: var(--surface);
        border: 2px solid var(--border);
        border-radius: 12px;
        box-shadow: var(--shadow-lg);
        z-index: 1000;
        max-height: 300px;
        overflow-y: auto;
        margin-top: 4px;
    }

    .search-result-item {
        padding: 12px 16px;
        cursor: pointer;
        border-bottom: 1px solid var(--border);
        transition: background 0.2s;
    }

    .search-result-item:hover {
        background: var(--blue-light);
    }

    .th-re {
        display: none;
    }

    .has-re .th-re {
        display: table-cell;
    }
</style>

<script>
    let productosDB = <?php echo json_encode($avInicioPrivado['productos']); ?>;
    let albLineas = [];
    let albAplicaRE = false;
    let albaranesPendientes = [];
    let albaranesSeleccionados = [];

    function switchTab(tab, btn) {
        document.querySelectorAll('.purchase-tab-content').forEach(el => el.classList.add('d-none'));
        document.getElementById('tab-' + tab).classList.remove('d-none');
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        btn.classList.add('active');
    }

    // --- LÓGICA ALBARANES ---
    function abrirModalNuevoAlbaran() {
        // Limpiar banner de pedidos automáticos si existe
        const oldBanner = document.getElementById('autoPedidoBanner');
        if (oldBanner) oldBanner.remove();

        albLineas = [];
        albAplicaRE = false;
        document.getElementById('albProveedor').value = '';
        document.getElementById('albNum').value = '';
        document.getElementById('albLineas').innerHTML = '';
        document.getElementById('modalNuevoAlbaran').classList.remove('has-re');
        calcularTotalAlbaran();
        document.getElementById('modalNuevoAlbaran').style.display = 'flex';
    }

    function cerrarModalNuevoAlbaran() {
        document.getElementById('modalNuevoAlbaran').style.display = 'none';
    }

    function actualizarREAlbaran() {
        const sel = document.getElementById('albProveedor');
        if (sel.selectedIndex <= 0) return;

        // Si ya hay productos y cambiamos de proveedor, advertir
        if (albLineas.length > 0) {
            showCustomConfirm(
                <?php echo json_encode(L('purchase_js_change_prov_title')); ?>,
                <?php echo json_encode(L('purchase_js_change_prov_body')); ?>,
                () => {
                    albLineas = [];
                    renderLineasAlbaran();
                    aplicarCambioProveedor(sel);
                },
                <?php echo json_encode(L('purchase_js_change_prov_btn')); ?>,
                'danger'
            );
            // Revertir temporalmente la selección hasta que el usuario confirme
            // (Esto es un poco complejo sin guardar el valor anterior, pero podemos forzar el cambio si acepta)
        } else {
            aplicarCambioProveedor(sel);
        }
    }

    function aplicarCambioProveedor(sel) {
        albAplicaRE = sel.options[sel.selectedIndex].dataset.re === '1';
        document.getElementById('modalNuevoAlbaran').classList.toggle('has-re', albAplicaRE);
        document.getElementById('albBusqueda').value = '';
        document.getElementById('albResultados').classList.add('d-none');
        calcularTotalAlbaran();
    }

    function buscarProductoAlbaran(q) {
        const dd = document.getElementById('albResultados');
        const idProv = document.getElementById('albProveedor').value;

        if (!idProv) {
            showCustomAlert(<?php echo json_encode(L('prod_th_status')); ?>, <?php echo json_encode(L('purchase_js_select_prov_first')); ?>, "warning");
            return dd.classList.add('d-none');
        }

        if (q.length < 2) return dd.classList.add('d-none');

        // Filtrar por nombre/referencia Y por el proveedor seleccionado
        const res = productosDB.filter(p => {
            const matchesQuery = p.nombre.toLowerCase().includes(q.toLowerCase()) ||
                p.referencia.toLowerCase().includes(q.toLowerCase());
            const matchesProv = parseInt(p.id_proveedor) === parseInt(idProv);
            return matchesQuery && matchesProv;
        }).slice(0, 10);

        if (res.length > 0) {
            dd.innerHTML = res.map(p => `
                <div class="search-result-item" onclick="añadirLineaAlbaran(${p.id})">
                    <b>${p.nombre}</b> <small>(${p.referencia})</small>
                </div>
            `).join('');
            dd.classList.remove('d-none');
        } else {
            dd.innerHTML = '<div class="p-12 text-center text-muted fs-12"><?php echo L('purchase_js_no_products_found'); ?></div>';
            dd.classList.remove('d-none');
        }
    }

    async function añadirLineaAlbaran(id) {
        const p = productosDB.find(x => x.id === id);
        insertarLineaProcesada(p);
    }

    function insertarLineaProcesada(p) {
        const nombreMostrar = `${p.nombre} (${p.referencia})`;

        if (albLineas.find(l => l.producto_id === p.id)) {
            return showCustomAlert(<?php echo json_encode(L('prod_th_status')); ?>, <?php echo json_encode(L('purchase_js_product_added')); ?>, 'warning');
        }

        let rePct = 0;
        if (p.iva >= 21) rePct = 5.2;
        else if (p.iva >= 10) rePct = 1.4;
        else if (p.iva >= 4) rePct = 0.5;

        albLineas.push({
            producto_id: p.id,
            nombre: nombreMostrar,
            cantidad: 1,
            precio_coste_neto: 0,
            iva_pct: parseFloat(p.iva),
            re_pct: rePct
        });
        renderLineasAlbaran();
        document.getElementById('albBusqueda').value = '';
        document.getElementById('albResultados').classList.add('d-none');
    }

    function renderLineasAlbaran() {
        const tbody = document.getElementById('albLineas');
        const emptyRow = document.getElementById('albEmptyRow');

        // Limpiamos el contenido previo (excepto el emptyRow si queremos reusarlo, pero es más limpio reconstruir)
        tbody.innerHTML = '';

        if (albLineas.length === 0) {
            // Reinsertamos el emptyRow si la lista está vacía
            tbody.innerHTML = `
                <tr id="albEmptyRow">
                    <td colspan="7">
                        <div class="empty-lines-state">
                            <i class="fa-solid fa-box-open"></i>
                            <?php echo L('purchase_empty_lines'); ?>
                        </div>
                    </td>
                </tr>
            `;
            calcularTotalAlbaran();
            return;
        }

        const rows = albLineas.map((l, i) => {
            const isZero = parseFloat(l.precio_coste_neto) <= 0;
            const priceStyle = isZero ? 'border: 2px solid #ef4444; background: #fef2f2; color:#991b1b;' : '';
            const totalLinea = l.cantidad * l.precio_coste_neto * (1 + (l.iva_pct / 100) + (albAplicaRE ? l.re_pct / 100 : 0));

            return `
                <tr>
                    <td class="font-bold pl-20" style="max-width:220px;">
                        ${l.nombre}
                    </td>
                    <td style="width:90px;"><input type="number" class="form-input text-center" style="width:80px;padding:6px;" value="${l.cantidad}" min="1" onchange="albLineas[${i}].cantidad=Math.max(1,parseFloat(this.value)||1);renderLineasAlbaran()"></td>
                    <td style="width:160px;"><input type="number" class="form-input text-right font-mono" style="width:140px;padding:6px;${priceStyle}" value="${parseFloat(l.precio_coste_neto).toFixed(2)}" step="0.01" placeholder="0.00" onchange="albLineas[${i}].precio_coste_neto=parseFloat(this.value)||0;renderLineasAlbaran()"></td>
                    <td class="text-center fs-12 text-muted" style="width:60px;">${l.iva_pct}%</td>
                    <td class="text-center fs-12 text-muted th-re" style="width:60px;">${l.re_pct}%</td>
                    <td class="text-right font-bold font-mono" style="width:120px;">${totalLinea.toFixed(2)} €</td>
                    <td style="width:40px;"><button class="btn-icon text-red" title="<?php echo L('purchase_js_del_line'); ?>" onclick="albLineas.splice(${i},1);renderLineasAlbaran()"><i class="fa-solid fa-trash-can"></i></button></td>
                </tr>
            `;
        }).join('');

        tbody.innerHTML = rows;
        calcularTotalAlbaran();
    }

    function calcularTotalAlbaran() {
        let total = 0;
        albLineas.forEach(l => total += l.cantidad * l.precio_coste_neto * (1 + (l.iva_pct / 100) + (albAplicaRE ? l.re_pct / 100 : 0)));
        document.getElementById('albTotal').innerText = total.toLocaleString("<?php echo L('locale'); ?>", {
            minimumFractionDigits: 2
        });
    }

    async function guardarAlbaran() {
        const prov = document.getElementById('albProveedor').value;
        const num = document.getElementById('albNum').value;

        if (!prov || !num) {
            return showCustomAlert(<?php echo json_encode(L('prod_th_status')); ?>, <?php echo json_encode(L('purchase_js_incomplete_fields')); ?>, 'warning');
        }

        if (albLineas.length === 0) {
            return showCustomAlert(<?php echo json_encode(L('prod_th_status')); ?>, <?php echo json_encode(L('purchase_js_empty_albaran')); ?>, 'warning');
        }

        // Validar que no haya precios a 0
        const preciosCero = albLineas.filter(l => parseFloat(l.precio_coste_neto) <= 0);
        if (preciosCero.length > 0) {
            const nombres = preciosCero.map(l => l.nombre).join(', ');
            showCustomConfirm(
                <?php echo json_encode(L('purchase_js_zero_cost_title')); ?>,
                <?php echo json_encode(L('purchase_js_zero_cost_body', true)); ?>.replace('{productos}', nombres),
                () => ejecutarGuardarAlbaran(prov, num),
                <?php echo json_encode(L('purchase_js_zero_cost_btn')); ?>,
                'danger'
            );
            return;
        }

        ejecutarGuardarAlbaran(prov, num);
    }

    async function validarAlbaran(id) {
        showCustomConfirm(
            <?php echo json_encode(L('purchase_btn_validate')); ?>,
            <?php echo json_encode(L('purchase_js_validate_confirm')); ?>,
            async () => {
                try {
                    const r = await fetch('api/compras.php', {
                        method: 'POST',
                        body: JSON.stringify({
                            action: 'validar_albaran',
                            id: id
                        })
                    });
                    const res = await r.json();
                    if (res.success) {
                        sessionStorage.setItem('tpv_refresh_stock', Date.now());
                        window.location.reload();
                    } else {
                        showCustomAlert(<?php echo json_encode(L('prod_js_error')); ?>, res.error || <?php echo json_encode(L('prod_js_error')); ?>, 'error');
                    }
                } catch (e) {
                    showCustomAlert(<?php echo json_encode(L('prod_js_error')); ?>, <?php echo json_encode(L('prod_js_error')); ?>, 'error');
                }
            },
            <?php echo json_encode(L('purchase_btn_validate')); ?>,
            'success'
        );
    }

    async function ejecutarGuardarAlbaran(prov_id, numero) {
        if (!prov_id || !numero || albLineas.length === 0) {
            console.error("Faltan datos para guardar albarán", { prov_id, numero, lineas: albLineas.length });
            return showCustomAlert(<?php echo json_encode(L('warning')); ?>, <?php echo json_encode(L('purchase_js_incomplete_fields')); ?>, 'warning');
        }

        const btn = document.getElementById('btnGuardarAlbaran');
        btn.disabled = true;
        
        try {
            const r = await fetch('api/compras.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'registrar_albaran',
                    proveedor_id: prov_id,
                    numero_albaran: numero,
                    fecha: document.getElementById('albFecha').value,
                    lineas: albLineas
                })
            });

            if (!r.ok) throw new Error(`HTTP Error: ${r.status}`);

            const res = await r.json();
            if (res.success) {
                // Notificar al TPV que el stock ha cambiado
                sessionStorage.setItem('tpv_refresh_stock', Date.now());

                showNotification(<?php echo json_encode(L('purchase_js_saved_ok')); ?>, 'success');
                
                // Si venía de pedido automático, ver si hay más en la cola
                const queueData = sessionStorage.getItem('tpv_pedido_auto_data');
                let hasMore = false;
                try {
                    const q = JSON.parse(queueData);
                    if (q && q.length > 0) hasMore = true;
                } catch(e){}

                if (hasMore) {
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    window.location.reload();
                }
            } else {
                console.error("Error API registrar_albaran:", res.error);
                showCustomAlert(<?php echo json_encode(L('prod_js_error')); ?>, res.error || "Error desconocido al guardar", 'error');
            }
        } catch (e) {
            console.error("Exception en ejecutarGuardarAlbaran:", e);
            showCustomAlert(<?php echo json_encode(L('prod_js_error')); ?>, <?php echo json_encode(L('tpv_js_conn_error')); ?>, 'error');
        } finally {
            btn.disabled = false;
        }
    }

    // --- LÓGICA FACTURAS ---
    function abrirModalNuevaFactura() {
        albaranesSeleccionados = [];
        document.getElementById('facProveedor').value = '';
        document.getElementById('facNum').value = '';
        document.getElementById('facPagada').checked = true;
        document.getElementById('facTotal').innerText = '0,00';
        document.getElementById('listaAlbaranesPendientes').innerHTML = '<div class="p-20 text-center opacity-50 fs-13"><?php echo L('purchase_select_prov_hint'); ?></div>';
        document.getElementById('modalNuevaFactura').style.display = 'flex';
    }

    function cerrarModalNuevaFactura() {
        document.getElementById('modalNuevaFactura').style.display = 'none';
    }

    async function cargarAlbaranesPendientes() {
        const provId = document.getElementById('facProveedor').value;
        if (!provId) return;
        try {
            const r = await fetch('api/compras.php?type=albaranes_pendientes');
            const data = await r.json();
            // La API ya devuelve solo los 'validado' porque hemos actualizado listarAlbaranes(true)
            albaranesPendientes = data.filter(a => a.proveedor_id == provId);
            const container = document.getElementById('listaAlbaranesPendientes');
            if (albaranesPendientes.length === 0) container.innerHTML = '<div class="p-20 text-center opacity-50 fs-13"><?php echo L('purchase_no_pending_alb'); ?></div>';
            else {
                container.innerHTML = albaranesPendientes.map(a => `
                    <div class="alb-item" id="alb-row-${a.id}" onclick="toggleSeleccionAlbaran(${a.id}, ${a.total})">
                        <div>
                            <div class="fw-700">${a.numero_albaran}</div>
                            <div class="fs-11 text-muted">${new Date(a.fecha).toLocaleDateString("<?php echo L('locale'); ?>")}</div>
                        </div>
                        <div class="fw-700 font-mono text-accent">${parseFloat(a.total).toFixed(2)} €</div>
                    </div>
                `).join('');
            }
            albaranesSeleccionados = [];
            actualizarTotalFactura();
        } catch (e) {
            console.error(e);
        }
    }

    function toggleSeleccionAlbaran(id, total) {
        const idx = albaranesSeleccionados.indexOf(id);
        const el = document.getElementById('alb-row-' + id);
        if (idx === -1) {
            albaranesSeleccionados.push(id);
            el.classList.add('selected');
        } else {
            albaranesSeleccionados.splice(idx, 1);
            el.classList.remove('selected');
        }
        actualizarTotalFactura();
    }

    function actualizarTotalFactura() {
        let total = 0;
        albaranesSeleccionados.forEach(id => {
            const a = albaranesPendientes.find(x => x.id === id);
            if (a) total += parseFloat(a.total);
        });
        document.getElementById('facTotal').innerText = total.toLocaleString("<?php echo L('locale'); ?>", {
            minimumFractionDigits: 2
        });
        document.getElementById('btnGuardarFactura').disabled = (albaranesSeleccionados.length === 0);
    }

    async function guardarFactura() {
        const num = document.getElementById('facNum').value;
        if (!num) return showCustomAlert(<?php echo json_encode(L('prod_th_status')); ?>, <?php echo json_encode(L('purchase_js_incomplete_fields')); ?>, 'warning');
        const btn = document.getElementById('btnGuardarFactura');
        const metodo = document.getElementById('facPago').value;
        const pagado = document.getElementById('facPagada').checked;
        btn.disabled = true;

        if (metodo === 'caja' && pagado) {
            try {
                let totalFac = 0;
                albaranesSeleccionados.forEach(id => {
                    const a = albaranesPendientes.find(x => x.id === id);
                    if (a) totalFac += parseFloat(a.total);
                });
                const rc = await fetch('api/cajaEstadoActual.php');
                const resc = await rc.json();
                const efectivo = resc.ok ? parseFloat(resc.efectivoActual) : 0;
                if (efectivo < totalFac) {
                    showCustomAlert('Error', <?php echo json_encode(L('purchase_js_insufficient_cash')); ?>, 'error');
                    btn.disabled = false;
                    return;
                }
            } catch (e) {
                showCustomAlert('Error', <?php echo json_encode(L('tpv_js_conn_error')); ?>, 'error');
                btn.disabled = false;
                return;
            }
        }

        try {
            const r = await fetch('api/compras.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'registrar_factura',
                    proveedor_id: document.getElementById('facProveedor').value,
                    numero_factura: num,
                    fecha: document.getElementById('facFecha').value,
                    metodo_pago: metodo,
                    pagado: pagado,
                    ids_albaranes: albaranesSeleccionados
                })
            });
            const res = await r.json();
            if (res.success) window.location.reload();
            else showCustomAlert('Error', res.error, 'error');
        } catch (e) {
            showCustomAlert(<?php echo json_encode(L('warning')); ?>, <?php echo json_encode(L('tpv_js_conn_error')); ?>, 'error');
        } finally {
            btn.disabled = false;
        }
    }

    let _pagoFacturaId = null;
    let _pagoFacturaTotal = 0;

    async function pagarFactura(id) {
        const r = await fetch('api/compras.php?type=factura&id=' + id);
        const f = await r.json();
        _pagoFacturaId = id;
        _pagoFacturaTotal = parseFloat(f.total);

        document.getElementById('pagoFacTotal').innerText =
            _pagoFacturaTotal.toLocaleString(<?php echo json_encode(L('locale')); ?>, {minimumFractionDigits: 2}) + ' €';
        document.getElementById('pagoFacMetodo').value = 'banco';
        document.getElementById('pagoFacCajaInfo').classList.add('d-none');
        document.getElementById('pagoFacAlerta').classList.add('d-none');
        document.getElementById('btnConfirmarPagoFactura').disabled = false;
        document.getElementById('modalPagarFactura').style.display = 'flex';
    }

    function cerrarModalPagarFactura() {
        document.getElementById('modalPagarFactura').style.display = 'none';
        _pagoFacturaId = null;
        _pagoFacturaTotal = 0;
    }

    async function onCambioMetodoPagoFactura() {
        const metodo = document.getElementById('pagoFacMetodo').value;
        const cajaInfo = document.getElementById('pagoFacCajaInfo');
        const alerta = document.getElementById('pagoFacAlerta');
        const btn = document.getElementById('btnConfirmarPagoFactura');

        if (metodo === 'caja') {
            cajaInfo.classList.remove('d-none');
            document.getElementById('pagoFacEfectivoDisp').innerText = '...';
            try {
                const rc = await fetch('api/cajaEstadoActual.php');
                const resc = await rc.json();
                const efectivo = resc.ok ? parseFloat(resc.efectivoActual) : 0;
                document.getElementById('pagoFacEfectivoDisp').innerText =
                    efectivo.toLocaleString(<?php echo json_encode(L('locale')); ?>, {minimumFractionDigits: 2}) + ' €';
                if (efectivo < _pagoFacturaTotal) {
                    alerta.classList.remove('d-none');
                    btn.disabled = true;
                } else {
                    alerta.classList.add('d-none');
                    btn.disabled = false;
                }
            } catch (e) {
                alerta.classList.remove('d-none');
                btn.disabled = true;
            }
        } else {
            cajaInfo.classList.add('d-none');
            alerta.classList.add('d-none');
            btn.disabled = false;
        }
    }

    async function confirmarPagoFactura() {
        const metodo = document.getElementById('pagoFacMetodo').value;
        const btn = document.getElementById('btnConfirmarPagoFactura');
        btn.disabled = true;

        if (metodo === 'caja') {
            try {
                const rc = await fetch('api/cajaEstadoActual.php');
                const resc = await rc.json();
                const efectivo = resc.ok ? parseFloat(resc.efectivoActual) : 0;
                if (efectivo < _pagoFacturaTotal) {
                    showCustomAlert('Error', <?php echo json_encode(L('purchase_js_insufficient_cash')); ?>, 'error');
                    btn.disabled = false;
                    return;
                }
            } catch (e) {
                showCustomAlert('Error', <?php echo json_encode(L('tpv_js_conn_error')); ?>, 'error');
                btn.disabled = false;
                return;
            }
        }

        await ejecutarPagoFactura(_pagoFacturaId, metodo);
        cerrarModalPagarFactura();
    }

    async function ejecutarPagoFactura(id, metodo) {
        try {
            const r = await fetch('api/compras.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'pagar_factura',
                    id: id,
                    metodo_pago: metodo
                })
            });
            const res = await r.json();
            if (res.success) window.location.reload();
            else showCustomAlert('Error', res.error, 'error');
        } catch (e) {
            showCustomAlert('Error', 'Error de conexión', 'error');
        }
    }

    // --- DETALLES ---
    async function verDetalleAlbaran(id) {
        const r = await fetch('api/compras.php?type=albaran&id=' + id);
        const a = await r.json();
        document.getElementById('detalleTitulo').innerText = <?php echo json_encode(L('purchase_th_albaran')); ?> + " " + a.numero_albaran;
        document.getElementById('detalleContent').innerHTML = `
            <div class="mb-16"><b><?php echo L('purchase_modal_details_vendor'); ?>:</b> ${a.proveedor_nombre} <br> <b><?php echo L('purchase_modal_details_date'); ?>:</b> ${new Date(a.fecha).toLocaleDateString()}</div>
            <table class="data-table">
                <thead><tr><th><?php echo L('prod_th_name'); ?></th><th class="text-right"><?php echo L('prod_th_qty'); ?></th><th class="text-right"><?php echo L('tpv_total'); ?></th></tr></thead>
                <tbody>${a.lineas.map(l => `<tr><td>${l.producto_nombre}</td><td class="text-right">${l.cantidad}</td><td class="text-right font-mono">${(l.cantidad * l.precio_coste_neto * (1+(l.iva_pct/100)+(l.re_pct/100))).toFixed(2)} €</td></tr>`).join('')}</tbody>
            </table>
            <div class="text-right mt-16 fs-20 font-bold text-accent"><?php echo L('tpv_total'); ?>: ${parseFloat(a.total).toFixed(2)} €</div>
        `;
        document.getElementById('modalDetalle').style.display = 'flex';
    }

    async function verDetalleFactura(id) {
        const r = await fetch('api/compras.php?type=factura&id=' + id);
        const f = await r.json();
        document.getElementById('detalleTitulo').innerText = <?php echo json_encode(L('purchase_th_invoice_num')); ?> + " " + f.numero_factura;
        document.getElementById('detalleContent').innerHTML = `
            <div class="mb-16">
                <b><?php echo L('purchase_modal_details_vendor'); ?>:</b> ${f.proveedor_nombre} <br> 
                <b><?php echo L('purchase_modal_details_date'); ?>:</b> ${new Date(f.fecha_factura).toLocaleDateString()} <br>
                <b><?php echo L('purchase_modal_details_payment'); ?>:</b> <span class="badge badge-info">${f.metodo_pago}</span>
            </div>
            <div class="fw-700 mb-8 tt-uppercase fs-11 opacity-50"><?php echo L('purchase_modal_details_included_alb'); ?>:</div>
            ${f.albaranes.map(a => `
                <div class="p-12 br-8 border-2 mb-4 d-flex jc-space-between ai-center">
                    <span>${a.numero_albaran} (${new Date(a.fecha).toLocaleDateString()})</span>
                    <span class="font-mono">${parseFloat(a.total).toFixed(2)} €</span>
                </div>
            `).join('')}
            <div class="text-right mt-16 fs-24 font-bold text-accent"><?php echo L('purchase_modal_details_total_fac'); ?>: ${parseFloat(f.total).toFixed(2)} €</div>
        `;
        document.getElementById('modalDetalle').style.display = 'flex';
    }

    function cerrarModalDetalle() {
        document.getElementById('modalDetalle').style.display = 'none';
    }

    // --- MANEJO DE PEDIDO AUTOMÁTICO (Viene de vProductos) ---
    window.addEventListener('DOMContentLoaded', () => {
        const dataRaw = sessionStorage.getItem('tpv_pedido_auto_data');
        if (!dataRaw) return;

        let data;
        try {
            data = JSON.parse(dataRaw);
        } catch (e) {
            sessionStorage.removeItem('tpv_pedido_auto_data');
            return;
        }

        if (!data || data.length === 0) {
            sessionStorage.removeItem('tpv_pedido_auto_data');
            return;
        }

        const pedido = data[0];

        // Abrir modal albarán
        abrirModalNuevoAlbaran();

        // Seleccionar proveedor
        const provSelect = document.getElementById('albProveedor');
        if (provSelect) {
            provSelect.value = pedido.proveedor_id;
            actualizarREAlbaran();
        }

        // Generar número de albarán sugerido
        document.getElementById('albNum').value = 'AUTO-' + new Date().toISOString().slice(0, 10).replace(/-/g, '') + '-' + Math.random().toString(36).substr(2, 4).toUpperCase();

        // Cargar líneas del pedido automático
        albLineas = pedido.productos.map(p => ({
            producto_id: p.id,
            nombre: p.nombre,
            cantidad: p.cantidad,
            precio_coste_neto: p.precio_coste_neto,
            iva_pct: p.iva_pct,
            re_pct: p.re_pct
        }));

        renderLineasAlbaran();

        const tieneCero = albLineas.some(l => parseFloat(l.precio_coste_neto) <= 0);
        let bannerMsg = tieneCero ?
            <?php echo json_encode(L('purchase_auto_banner_zero')); ?>.replace('{prov}', pedido.proveedor_nombre) :
            <?php echo json_encode(L('purchase_auto_banner_ok')); ?>.replace('{prov}', pedido.proveedor_nombre);

        if (data.length > 1) {
            bannerMsg += <?php echo json_encode(L('purchase_auto_banner_multi')); ?>.replace('{count}', data.length);
        }

        // Limpiar banner previo si sigue ahí
        const oldBanner = document.getElementById('autoPedidoBanner');
        if (oldBanner) oldBanner.remove();

        // Insertar banner al inicio del modal
        const bannerEl = document.createElement('div');
        bannerEl.id = 'autoPedidoBanner';
        const isMulti = data.length > 1;
        const bannerColor = isMulti ? '#fff7ed' : '#fef9c3';
        const borderColor = isMulti ? '#ea580c' : '#f59e0b';
        const textColor = isMulti ? '#9a3412' : '#92400e';
        
        bannerEl.style.cssText = `background:${bannerColor};border:2px solid ${borderColor};color:${textColor};padding:14px 18px;font-size:13px;margin-bottom:16px;border-radius:12px;display:flex;align-items:center;gap:12px;box-shadow:0 4px 6px -1px rgb(0 0 0 / 0.1);`;
        bannerEl.innerHTML = `<i class="fa-solid ${isMulti ? 'fa-layer-group' : 'fa-box-open'} fs-20"></i> <div>${bannerMsg}</div>`;
        const lineasContainer = document.getElementById('albLineas');
        if (lineasContainer) {
            const tableContainer = lineasContainer.closest('.table-container');
            if (tableContainer && tableContainer.parentNode) {
                tableContainer.parentNode.insertBefore(bannerEl, tableContainer);
            }
        }

        // Manejo secuencial: Si hay más proveedores, actualizar sessionStorage para el siguiente
        if (data.length > 1) {
            const restantes = data.slice(1);
            sessionStorage.setItem('tpv_pedido_auto_data', JSON.stringify(restantes));
        } else {
            sessionStorage.removeItem('tpv_pedido_auto_data');
        }
    });
</script>