<div class="main">
    <!-- CATALOG PANEL -->
    <div class="catalog-panel">
        <div class="search-bar">
            <div class="search-input-wrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input
                    class="search-input"
                    type="text"
                    id="searchInput"
                    oninput="handleSearch(this.value)"
                    placeholder="<?php echo L('tpv_search_placeholder'); ?>" />
            </div>
            <button onclick="toggleAdvancedFilters()" class="btn-filter-toggle p-10 br-10 bg-surface border-2 cursor-pointer transition-all" title="<?php echo L('tpv_filters_advanced'); ?>">
                <i class="fa-solid fa-filter"></i>
            </button>
            <button onclick="abrirModalComodin()" class="btn-filter-toggle p-10 br-10 bg-accent text-white border-2 border-accent cursor-pointer transition-all" title="<?php echo L('tpv_add_custom_product'); ?>" style="margin-left: 8px;">
                <i class="fa-solid fa-plus"></i> <i class="fa-solid fa-box-open"></i>
            </button>
        </div>

        <!-- ADVANCED FILTERS PANEL -->
        <div id="advancedFilters" class="advanced-filters-panel d-none bg-surface p-16 br-12 border-2 mb-10 shadow-sm">
            <?php
            // Extraer todos los atributos únicos usados en los productos activos para generar los filtros
            $atributosDisponibles = [];
            if (isset($avInicioPrivado) && is_array($avInicioPrivado) && isset($avInicioPrivado['productos']) && is_array($avInicioPrivado['productos'])) {
                foreach ($avInicioPrivado['productos'] as $prod) {
                    if ($prod['activo'] && !empty($prod['atributos'])) {
                        $attrArr = json_decode($prod['atributos'], true);
                        if (is_array($attrArr)) {
                            foreach ($attrArr as $attr) {
                                if (!in_array($attr, $atributosDisponibles)) {
                                    $atributosDisponibles[] = $attr;
                                }
                            }
                        }
                    }
                }
                sort($atributosDisponibles);
            }
            ?>
            <div class="grid-4 gap-12 ai-end">
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase opacity-70"><?php echo L('tpv_price_min'); ?></label>
                    <input type="number" id="filterPriceMin" class="form-input fs-13" placeholder="0.00" oninput="applyAdvancedFilters()">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase opacity-70"><?php echo L('tpv_price_max'); ?></label>
                    <input type="number" id="filterPriceMax" class="form-input fs-13" placeholder="999.99" oninput="applyAdvancedFilters()">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase opacity-70"><?php echo L('tpv_stock'); ?></label>
                    <select id="filterStock" class="form-input fs-13" onchange="applyAdvancedFilters()">
                        <option value="all"><?php echo L('tpv_all'); ?></option>
                        <option value="in-stock"><?php echo L('tpv_in_stock'); ?></option>
                        <option value="low-stock"><?php echo L('tpv_low_stock'); ?></option>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase opacity-70"><?php echo L('tpv_sort_by'); ?></label>
                    <select id="filterSort" class="form-input fs-13" onchange="applyAdvancedFilters()">
                        <option value="name-asc"><?php echo L('tpv_sort_name_asc'); ?></option>
                        <option value="name-desc"><?php echo L('tpv_sort_name_desc'); ?></option>
                        <option value="price-asc"><?php echo L('tpv_sort_price_asc'); ?></option>
                        <option value="price-desc"><?php echo L('tpv_sort_price_desc'); ?></option>
                        <option value="stock-asc"><?php echo L('tpv_sort_stock_asc'); ?></option>
                        <option value="stock-desc"><?php echo L('tpv_sort_stock_desc'); ?></option>
                    </select>
                </div>

                <?php if (!empty($atributosDisponibles)): ?>
                    <div style="grid-column: span 4;">
                        <label class="form-label fs-11 tt-uppercase opacity-70 mb-8"><i class="fa-solid fa-tags"></i> <?php echo L('tpv_additional_tags'); ?></label>
                        <div class="attr-tabs" id="attrTabs" style="display:flex; flex-wrap:wrap; gap:8px;">
                            <?php foreach ($atributosDisponibles as $attr): ?>
                                <button class="attr-tab cat-tab d-inline-flex ai-center gap-6"
                                    data-attr="<?php echo htmlspecialchars($attr); ?>"
                                    style="font-size: 11px; padding: 6px 14px;">
                                    <i class="fa-solid fa-tag" style="opacity: 0.5;"></i> <?php echo htmlspecialchars($attr); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>


        <div class="cat-tabs" id="catTabs">
            <button class="cat-tab active" data-cat="all"><?php echo L('tpv_cat_all'); ?></button>
            <?php if (isset($avInicioPrivado) && is_array($avInicioPrivado) && isset($avInicioPrivado['categorias']) && is_array($avInicioPrivado['categorias'])): ?>
                <?php foreach ($avInicioPrivado['categorias'] as $c): ?>
                    <button class="cat-tab" data-cat="<?php echo htmlspecialchars($c['codigo']); ?>">
                        <?php echo htmlspecialchars($c['nombre']); ?>
                    </button>
                <?php endforeach; ?>
            <?php endif; ?>
            <button class="cat-tab d-inline-flex ai-center gap-6 text-red border-red-light bg-red-light" data-cat="baja">
                <i class="fa-solid fa-arrow-trend-down"></i> <?php echo L('tpv_cat_discontinued'); ?>
            </button>
        </div>





        <div class="products-grid" id="productsGrid"></div>
    </div>

    <script>
        const DB_TARIFAS = <?php echo json_encode(TarifaPrecioPDO::listarActivas()); ?>;
    </script>

    <!-- ORDER PANEL -->
    <div class="order-panel">
        <div class="order-header">
            <div class="d-flex ai-center gap-10">
                <span class="order-title"><?php echo L('tpv_order'); ?></span>
                <span class="order-count" id="orderCount">0</span>
            </div>
            <div class="d-flex ai-center gap-8">
                <button id="btnResumeSale" class="btn-save px-10 py-4 fs-11 bg-accent border-0 br-6 shadow-sm ai-center gap-6" style="display: none;" onclick="resumeSale()" title="<?php echo L('tpv_resume'); ?>"><i class="fa-solid fa-play"></i> <?php echo L('tpv_resume'); ?></button>
                <button class="btn-cancel px-10 py-4 fs-11 border-1 br-6 ai-center gap-6 bg-surface" onclick="postponeSale()" title="<?php echo L('tpv_postpone'); ?>"><i class="fa-solid fa-pause text-accent"></i> <?php echo L('tpv_postpone'); ?></button>
                <button class="btn-clear" onclick="clearCart()"><?php echo L('tpv_clear'); ?></button>
            </div>
        </div>

        <div class="order-items" id="orderItems">
            <div class="empty-cart" id="emptyCart">
                <div class="empty-cart-icon">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
                <div><?php echo L('tpv_empty_cart'); ?><br /><?php echo L('tpv_empty_cart_sub'); ?></div>
            </div>
        </div>

        <div class="order-totals">
            <div class="total-row" id="rowSubtotal">
                <span><?php echo L('tpv_subtotal'); ?></span>
                <span id="subtotal">0,00 €</span>
            </div>
            <div
                class="total-row d-none text-green"
                id="discountRow">
                <span><?php echo L('tpv_discount'); ?></span>
                <span id="discountAmt">-0,00 €</span>
            </div>
            <div id="ivaBreakdown" class="iva-breakdown">
                <!-- Dinámico -->
            </div>
            <div class="total-row main">
                <span><?php echo L('tpv_total'); ?></span>
                <span id="totalAmt">0,00 €</span>
            </div>
        </div>



        <div class="payment-section">
            <div class="payment-label"><?php echo L('tpv_payment_method'); ?></div>
            <div class="payment-methods">
                <button
                    class="pay-btn selected"
                    data-method="efectivo"
                    onclick="selectSidebarPayment(this)">
                    <i class="fa-solid fa-money-bill-1-wave"></i>
                    <?php echo L('tpv_method_cash'); ?>
                </button>
                <button
                    class="pay-btn"
                    data-method="tarjeta"
                    onclick="selectSidebarPayment(this)">
                    <i class="fa-solid fa-credit-card"></i>
                    <?php echo L('tpv_method_card'); ?>
                </button>
                <button
                    class="pay-btn"
                    data-method="bizum"
                    onclick="selectSidebarPayment(this)">
                    <i class="fa-solid fa-mobile-screen-button"></i>
                    <?php echo L('tpv_method_bizum'); ?>
                </button>
                <!-- Botón Vale eliminado y movido dentro del modal de cliente -->
                <button
                    class="pay-btn"
                    data-method="a_cuenta"
                    id="btnACuenta"
                    onclick="selectSidebarPayment(this)">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <?php echo L('tpv_method_account'); ?>
                </button>
                <button
                    class="pay-btn"
                    data-method="mixto"
                    id="btnMixtoSidebar"
                    onclick="selectSidebarPayment(this)">
                    <i class="fa-solid fa-layer-group"></i>
                    <?php echo L('tpv_method_mixed'); ?>
                </button>
            </div>

            <div class="discount-row">
                <input
                    class="discount-input"
                    type="text"
                    id="discountCode"
                    placeholder="<?php echo L('tpv_discount_placeholder'); ?>" />
                <button class="discount-apply" onclick="applyDiscount()">
                    <?php echo L('tpv_apply'); ?>
                </button>
            </div>
            <span id="err-discount" class="form-error"></span>

            <div class="toggle-row">
                <div class="toggle-label text-accent">
                    <i class="fa-solid fa-file-invoice"></i> <?php echo L('tpv_require_invoice'); ?>
                </div>
                <label class="switch">
                    <input type="checkbox" id="facturaToggle">
                    <span class="slider"></span>
                </label>
            </div>

            <button
                class="charge-btn"
                id="chargeBtn"
                onclick="processPayment()"
                disabled>
                <?php echo L('tpv_charge'); ?> <span id="chargeTotal">0,00 €</span>
            </button>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal">
    <div class="modal modal-content gap-14 ai-stretch w-800" style="max-width: 800px; border-radius: 20px; overflow: hidden;">
        <div class="modal-header mb-0">
            <div class="modal-title fs-16"><?php echo L('modal_edit_title'); ?></div>
            <button onclick="document.getElementById('editModal').classList.remove('visible')" class="btn-close-modal">×</button>
        </div>
        <input type="hidden" id="editId" />
        <input type="hidden" id="editCost" />
        <div class="form-grid">
                <div class="form-group">
                <label class="form-label"><?php echo L('modal_label_img'); ?></label>
                <div class="d-flex ai-center gap-12">
                    <div id="editImgPreview" class="prod-img-preview border-none bg-surface2" style="width: 80px; height: 80px; flex-shrink: 0;">
                        <i class="fa-solid fa-image"></i>
                    </div>
                    <div class="flex-1">
                        <input type="file" id="editFile" accept="image/*" class="d-none" onchange="previewImageTPV(this, 'edit')">
                        <button type="button" onclick="document.getElementById('editFile').click()" class="btn-icon w-auto h-auto p-12-20 fs-13 gap-8 full-width">
                            <i class="fa-solid fa-upload"></i> <?php echo L('modal_btn_change_img'); ?>
                        </button>
                        <input type="hidden" id="editEmoji" />
                    </div>
                </div>
            </div>
            <div class="form-group flex-1">
                <label class="form-label"><?php echo L('modal_label_name'); ?></label>
                <input id="editName" class="form-input" />
                <span class="form-error" id="err-editNombre"></span>
            </div>
            <div class="form-group-wrap grid-2">
                <div class="form-group">
                    <label class="form-label"><?php echo L('modal_label_ref'); ?></label>
                    <input id="editSku" class="form-input font-mono" />
                </div>
                <div class="form-group grid-col-span-2">
                    <div class="grid-2 gap-12">
                        <div class="form-group mb-0">
                            <label class="form-label"><?php echo L('modal_label_price'); ?></label>
                            <input id="editPrice" class="form-input font-mono text-right fs-16" type="text" oninput="updateEditMargin()" placeholder="0.00" />
                            <span class="form-error" id="err-editPrecio"></span>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label"><?php echo L('modal_label_margin'); ?></label>
                            <div class="form-input d-flex ai-center jc-space-between font-mono font-bold bg-surface2 border-none" style="height: 44px; color: var(--green);">
                                <span id="editMarginPercent" class="fs-12 opacity-80 pl-8">0%</span>
                                <span id="editMarginDisplay" class="pr-8">0,00 €</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo L('modal_label_warranty'); ?></label>
                    <input id="editMesesGarantia" class="form-input font-mono text-right" type="number" min="0" max="120" step="1" placeholder="24" />
                </div>
            </div>

            <button class="btn-save mt-4 full-width" onclick="saveEdit()">
                <?php echo L('modal_save'); ?>
            </button>
        </div>
    </div>

    <!-- ADD MODAL -->
    <div class="modal-overlay" id="addModal">
        <div class="modal modal-content gap-14 ai-stretch w-800" style="max-width: 800px; border-radius: 20px; overflow: hidden;">
            <div class="modal-header mb-0">
                <div class="modal-title fs-16"><?php echo L('modal_new_title'); ?></div>
                <button onclick="document.getElementById('addModal').classList.remove('visible')" class="btn-close-modal">×</button>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label"><?php echo L('modal_label_img'); ?></label>
                    <div class="d-flex ai-center gap-12">
                        <div id="addImgPreview" class="prod-img-preview border-none bg-surface2" style="width: 80px; height: 80px; flex-shrink: 0;">
                            <i class="fa-solid fa-image"></i>
                        </div>
                        <div class="flex-1">
                            <input type="file" id="addFile" accept="image/*" class="d-none" onchange="previewImageTPV(this, 'add')">
                            <button type="button" onclick="document.getElementById('addFile').click()" class="btn-icon w-auto h-auto p-12-20 fs-13 gap-8 full-width">
                                <i class="fa-solid fa-upload"></i> <?php echo L('modal_btn_upload_img'); ?>
                            </button>
                            <input type="hidden" id="addEmoji" />
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo L('modal_label_name'); ?></label>
                    <input id="addName" class="form-input" placeholder="<?php echo L('client_placeholder_name'); ?>" />
                    <span class="form-error" id="err-addNombre"></span>
                </div>
                <div class="form-group-wrap grid-3">
                    <div class="form-group">
                        <label class="form-label"><?php echo L('modal_label_ref'); ?></label>
                        <input id="addSku" class="form-input font-mono" placeholder="PRO-001" />
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php echo L('modal_label_price'); ?></label>
                        <input id="addPrice" class="form-input font-mono text-right" type="text" placeholder="0.00" />
                        <span class="form-error" id="err-addPrecio"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php echo L('modal_label_iva'); ?></label>
                        <input id="addIva" class="form-input font-mono text-right" type="text" value="21" />
                    </div>
                </div>

                <label class="form-label"><?php echo L('modal_label_cat'); ?></label>
                <select id="addCat" class="form-input">
                    <?php if (isset($avInicioPrivado['categorias']) && is_array($avInicioPrivado['categorias'])): ?>
                        <?php foreach ($avInicioPrivado['categorias'] as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['codigo']); ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

        </div>
        <button class="btn-save mt-4 full-width" onclick="guardarNuevoProducto()">
            <?php echo L('modal_create'); ?>
        </button>
    </div>
</div>

<!-- DELETE CONFIRM MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal modal-content gap-16" style="border-radius: 20px; overflow: hidden;">
        <div class="modal-title"><?php echo L('modal_delete_title'); ?></div>
        <div class="modal-sub">
            <?php echo L('modal_delete_confirm'); ?> <strong id="delName"></strong><?php echo L('modal_delete_warning'); ?>
        </div>
        <div class="modal-footer full-width">
            <button onclick="document.getElementById('deleteModal').classList.remove('visible')" class="btn-cancel"><?php echo L('modal_cancel'); ?></button>
            <button id="delConfirmBtn" class="btn-save bg-red"><?php echo L('modal_delete_btn'); ?></button>
        </div>
    </div>
</div>


<!-- MODAL APERTURA DE CAJA (antes de poder vender) -->
<div class="modal-overlay" id="aperturaCajaModal">
    <div class="modal modal-content gap-16 ai-stretch w-360" style="border-radius: 20px; overflow: hidden;">
        <div class="modal-title text-center"><?php echo L('cash_open_title'); ?></div>
        <p class="text-muted fs-13 text-center">
            <?php echo L('cash_open_sub'); ?>
        </p>
        <form method="post" action="index.php" class="d-flex flex-column gap-12">
            <?php 
            $pendientes = CajaTurnoPDO::obtenerTurnosPendientesArqueo();
            if (!empty($pendientes)): 
            ?>
                <div class="bg-red-light text-red p-20 br-16 border-2 d-flex flex-column ai-center text-center gap-12" style="border-color: rgba(231, 76, 60, 0.3); background: rgba(231, 76, 60, 0.05);">
                    <div class="w-48 h-48 br-50 bg-red text-white d-flex ai-center jc-center shadow-md">
                        <i class="fa-solid fa-lock fs-20"></i>
                    </div>
                    <div>
                        <div class="fs-15 font-bold"><?php echo L('cash_open_locked'); ?></div>
                        <p class="fs-12 opacity-80 mt-4"><?php echo L('cash_open_locked_msg'); ?></p>
                    </div>
                    <a href="index.php?irCierreCaja" class="btn-save bg-red border-0 px-20 py-10 br-10 fs-12 fw-700 shadow-sm" style="text-decoration: none;">
                        <i class="fa-solid fa-vault mr-6"></i> <?php echo L('cash_open_btn_resolve'); ?>
                    </a>
                </div>
            <?php else: ?>
                <?php if (isset($_SESSION['mensajeErrorCaja'])): ?>
                    <div class="bg-red-light text-red p-12 br-8 fs-12 mb-10 border font-bold">
                        <?= $_SESSION['mensajeErrorCaja']; unset($_SESSION['mensajeErrorCaja']); ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label fs-13"><?php echo L('cash_open_label_initial'); ?></label>
                    <input type="number" step="0.01" min="0" name="fondoInicial" class="form-input font-mono fs-16 text-right" placeholder="0.00" value="<?= number_format($avInicioPrivado['fondoSugerido'], 2, '.', '') ?>" required autofocus>
                </div>
                <div class="modal-footer full-width mt-10">
                    <button type="submit" name="abrirCaja" class="btn-save w-full h-48 fs-15 font-bold br-12 shadow-md">
                        <i class="fa-solid fa-unlock-keyhole mr-8"></i> <?php echo L('cash_open_btn_open'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- MODAL 1: TIPO DE CLIENTE (aparece al pulsar Cobrar) -->
<div class="modal-overlay" id="clienteModal">
    <div class="modal modal-content ai-stretch" style="max-width: 960px; width: 96vw; border-radius: 20px; overflow: hidden; padding: 0; gap: 0;">
        <!-- Header -->
        <div class="d-flex jc-space-between ai-center" style="padding: 18px 28px; border-bottom: 1px solid var(--border);">
            <div class="modal-title fs-18 font-bold m-0"><?php echo L('client_type_title'); ?></div>
            <button onclick="cerrarModalCliente()" class="btn-close-modal" style="background:none; border:none; font-size:24px; cursor:pointer; color:var(--text-muted);">&times;</button>
        </div>

        <!-- 2-column body -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0; max-height:78vh; min-height:0;">

            <!-- ── LEFT: Cliente ── -->
            <div style="padding: 24px 28px; border-right: 1px solid var(--border); overflow-y: auto; display: flex; flex-direction: column; gap: 14px; background: var(--surface2);"><label style="font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); margin-bottom:-6px;"><?php echo L('client_type_title'); ?></label>

                <!-- Tipo de cliente -->
                <div class="grid-3 gap-8">
                    <button id="btnParticular" onclick="seleccionarTipoCliente('particular')"
                        class="p-12-8 br-12 border-2 bg-surface2 cursor-pointer fs-12 d-flex flex-column ai-center gap-8 active-scale h-auto">
                        <i class="fa-solid fa-user fs-22"></i>
                        <span class="font-bold"><?php echo L('client_particular'); ?></span>
                    </button>
                    <button id="btnSocio" onclick="seleccionarTipoCliente('socio')"
                        class="p-12-8 br-12 border-2 bg-surface2 cursor-pointer fs-12 d-flex flex-column ai-center gap-8 active-scale h-auto">
                        <i class="fa-solid fa-id-card fs-22 text-accent"></i>
                        <span class="font-bold"><?php echo L('client_socio'); ?></span>
                    </button>
                    <button id="btnEmpresa" onclick="seleccionarTipoCliente('empresa')"
                        class="p-12-8 br-12 border-2 bg-surface2 cursor-pointer fs-12 d-flex flex-column ai-center gap-8 active-scale h-auto">
                        <i class="fa-solid fa-building fs-22"></i>
                        <span class="font-bold"><?php echo L('client_empresa'); ?></span>
                    </button>
                </div>

                <!-- Buscador genérico -->
                <div id="clienteBusquedaGenerica" class="d-none flex-column gap-8 p-12 bg-surface2 br-8 border-2">
                    <div class="d-flex gap-8">
                        <input id="clienteSearch" class="form-input fs-13 flex-1" placeholder="<?php echo L('client_search_placeholder'); ?>" />
                        <button type="button" onclick="buscarClienteGuardado()" class="btn-save w-auto p-4-12">
                            <i class="fa-solid fa-search"></i>
                        </button>
                    </div>
                    <div id="clienteResultados" class="fs-12 mt-4 text-accent font-bold"></div>
                    <button id="btnAddCliente" onclick="mostrarRegistroCliente()" class="cat-tab p-4-8 fs-11 d-none"><?php echo L('client_new_btn'); ?></button>
                </div>

                <!-- Registro de Cliente -->
                <div id="clienteRegistro" class="d-none flex-column gap-8 p-12 bg-surface2 br-8 border-2">
                    <div id="clienteRegistroTitulo" class="form-label fs-12 font-bold"><?php echo L('client_new_title'); ?></div>
                    <input id="newClienteNombre" class="form-input fs-12" placeholder="<?php echo L('client_placeholder_name'); ?>" />
                    <input id="newClienteNif" class="form-input fs-12 font-mono" placeholder="NIF / CIF" />
                    <div class="d-flex gap-8">
                        <button onclick="cancelarRegistroCliente()" class="btn-cancel fs-11 p-4"><?php echo L('modal_cancel'); ?></button>
                        <button onclick="guardarNuevoCliente()" class="btn-save fs-11 p-4"><?php echo L('client_btn_save_use'); ?></button>
                    </div>
                </div>

                <!-- Buscador de Socio -->
                <div id="socioBusqueda" class="d-none flex-column gap-8 p-12 bg-surface2 br-8 border-2">
                    <div class="d-flex gap-8">
                        <input id="socioSearch" class="form-input fs-13 flex-1" placeholder="<?php echo L('client_search_socio_placeholder'); ?>" />
                        <button onclick="buscarSocio()" class="btn-save w-auto p-4-12"><i class="fa-solid fa-search"></i></button>
                    </div>
                    <div id="socioInfo" class="fs-12 mt-4 text-accent font-bold"></div>
                    <button id="btnAddSocio" onclick="mostrarRegistroSocio()" class="cat-tab p-4-8 fs-11 d-none"><?php echo L('client_new_socio_btn'); ?></button>
                </div>

                <!-- Registro de Socio -->
                <div id="socioRegistro" class="d-none flex-column gap-8 p-12 bg-surface2 br-8 border-2">
                    <div class="form-label fs-12 font-bold"><?php echo L('client_new_socio_title'); ?></div>
                    <input id="newSocioNombre" class="form-input fs-12" placeholder="Nombre completo" />
                    <input id="newSocioNif" class="form-input fs-12 font-mono" placeholder="DNI / NIE" />
                    <div class="d-flex gap-8">
                        <button onclick="cancelarRegistroSocio()" class="btn-cancel fs-11 p-4"><?php echo L('modal_cancel'); ?></button>
                        <button onclick="guardarNuevoSocio()" class="btn-save fs-11 p-4"><?php echo L('client_btn_save_use'); ?></button>
                    </div>
                </div>

                <!-- Datos de empresa -->
                <div id="empresaDatos" class="d-none flex-column gap-8">
                    <div class="form-group">
                        <label class="form-label" id="labelEmpresaNombre"><?php echo L('client_label_razon'); ?></label>
                        <input id="empresaNombre" class="form-input" placeholder="<?php echo L('client_label_razon'); ?>" />
                        <span class="form-error" id="err-empresaNombre"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" id="labelEmpresaNif"><?php echo L('client_label_nif'); ?></label>
                        <input id="empresaNif" class="form-input font-mono" placeholder="B12345678" />
                        <span class="form-error" id="err-empresaNif"></span>
                    </div>
                </div>

                <!-- Vales del cliente -->
                <div id="clienteValesContainer" class="d-none flex-column gap-8 p-12 bg-surface2 br-8 border-2">
                    <div class="form-label fs-13 font-bold d-flex ai-center gap-8">
                        <i class="fa-solid fa-ticket text-accent"></i> <?php echo L('tpv_available_vouchers'); ?>
                    </div>
                    <div id="listadoValesCliente" class="d-flex flex-column gap-4"></div>
                    <div id="valeAplicadoResumen" class="d-none mt-4 p-8 br-6 fs-12 bg-accent-light text-accent border-2">
                        <div class="d-flex jc-space-between ai-center">
                            <span><?php echo L('tpv_voucher_applied'); ?>:</span>
                            <span id="valeAplicadoTotal" class="font-bold">0,00 €</span>
                        </div>
                    </div>
                </div>

                <!-- Puntos de fidelidad -->
                <div id="clientePuntosContainer" class="d-none flex-column gap-8 p-12 bg-surface2 br-8 border-2" style="border-color: var(--accent);">
                    <div class="form-label fs-13 font-bold d-flex ai-center jc-space-between gap-8">
                        <div class="d-flex ai-center gap-8">
                            <i class="fa-solid fa-star text-accent"></i> <?php echo L('tpv_loyalty_points'); ?>
                        </div>
                        <span class="fs-14 font-mono font-bold text-accent"><span id="labelPuntosDisponibles">0</span> pts</span>
                    </div>
                    <div id="puntosCanjeArea" class="d-flex flex-column gap-8 mt-4 p-8 br-6 bg-surface border-1">
                        <div class="fs-11 text-muted" id="puntosCanjeMsg">Cargando puntos...</div>
                        <div class="d-flex ai-center gap-8 d-none" id="controlesCanjePuntos">
                            <select id="puntosAcanjearSelect" class="form-input fs-11 p-2-4 flex-1" style="height: auto;" onchange="updatePuntosDiscountPreview()"></select>
                            <button type="button" id="btnCanjearPuntos" onclick="canjearPuntos()" class="btn-save fs-10 p-4-12 w-auto" style="background: var(--accent); white-space: nowrap;"><?php echo L('tpv_redeem_points'); ?></button>
                        </div>
                    </div>
                    <div id="puntosAplicadosResumen" class="d-none mt-4 p-8 br-6 fs-12 bg-accent text-white d-flex jc-space-between ai-center">
                        <div class="d-flex ai-center gap-6">
                            <i class="fa-solid fa-check"></i>
                            <span><?php echo L('tpv_points_discount'); ?>: <strong id="puntosDiscountVal">-0,00 €</strong> (<span id="puntosRedeemedVal">0</span> pts)</span>
                        </div>
                        <button onclick="quitarPuntosCanjeados()" class="btn-close-modal text-white" style="font-size:16px;">×</button>
                    </div>
                </div>

            </div><!-- /left col -->

            <!-- ── RIGHT: Pago ── -->
            <div style="padding: 24px 28px; overflow-y: auto; display: flex; flex-direction: column; gap: 16px;">

                <!-- Total -->
                <div id="pagosMixResumen" class="flex-column gap-8 br-12 border-2" style="border-color: var(--accent); padding: 16px 20px;">
                    <div class="d-flex jc-space-between ai-center">
                        <span class="fs-13 font-bold"><?php echo L('tpv_total_sale'); ?></span>
                        <span id="mixTotalVenta" class="font-bold font-mono fs-20 text-accent">0,00 €</span>
                    </div>
                    <div id="mixListaPagos" class="d-flex flex-column gap-4 mt-8 pb-8 border-bottom border-dashed">
                        <div class="text-center opacity-50 fs-11 italic"><?php echo L('tpv_no_payments'); ?></div>
                    </div>
                    <div class="d-flex jc-space-between ai-center pt-4">
                        <span class="fs-13 font-bold"><?php echo L('tpv_pending'); ?></span>
                        <span id="mixTotalPendiente" class="font-bold font-mono fs-18 text-red">0,00 €</span>
                    </div>
                    <div id="mixTotalPagadoRow" class="d-none jc-space-between ai-center fs-11 opacity-70">
                        <span><?php echo L('tpv_total_paid'); ?>:</span>
                        <span id="mixTotalPagado">0,00 €</span>
                    </div>
                </div>

                <!-- Gestión de pago -->
                <div id="cobroMixtoGestion" class="flex-column gap-8 br-8 border-2" style="padding: 16px 20px; background: var(--surface2);">
                    <div class="form-label font-bold text-accent mb-4"><?php echo L('tpv_add_payment'); ?></div>

                    <div class="d-flex gap-8 fw-wrap mb-8" id="selectorMetodoPago">
                        <button id="btnEfectivo" onclick="selectModalPayment(this)" class="btn-tpv-method flex-1 py-10 px-8 br-8 border-2 transition d-flex flex-column ai-center gap-4 bg-surface" style="min-width:70px;">
                            <i class="fa-solid fa-money-bill-1 fs-16"></i>
                            <span class="fs-11 font-bold"><?php echo L('tpv_method_cash'); ?></span>
                        </button>
                        <button id="btnTarjeta" onclick="selectModalPayment(this)" class="btn-tpv-method flex-1 py-10 px-8 br-8 border-2 transition d-flex flex-column ai-center gap-4 bg-surface" style="min-width:70px;">
                            <i class="fa-solid fa-credit-card fs-16"></i>
                            <span class="fs-11 font-bold"><?php echo L('tpv_method_card'); ?></span>
                        </button>
                        <button id="btnBizum" onclick="selectModalPayment(this)" class="btn-tpv-method flex-1 py-10 px-8 br-8 border-2 transition d-flex flex-column ai-center gap-4 bg-surface" style="min-width:70px;">
                            <i class="fa-solid fa-mobile-screen fs-16"></i>
                            <span class="fs-11 font-bold"><?php echo L('tpv_method_bizum'); ?></span>
                        </button>
                        <button id="btnAcuenta" onclick="selectModalPayment(this)" class="btn-tpv-method flex-1 py-10 px-8 br-8 border-2 transition d-flex flex-column ai-center gap-4 bg-surface" style="min-width:70px;">
                            <i class="fa-solid fa-file-invoice-dollar fs-16"></i>
                            <span class="fs-11 font-bold"><?php echo L('tpv_method_account'); ?></span>
                        </button>
                    </div>

                    <div id="pagoMontoArea" class="d-none animate-fade-in">
                        <div class="form-group mb-8">
                            <label class="fs-13 font-bold mb-4 opacity-80" id="labelMontoPago"><?php echo L('tpv_amount_to_add'); ?> (€)</label>
                            <div class="d-flex gap-8 ai-center">
                                <input id="mixPagoMonto" type="number" step="0.01" class="form-input font-mono fs-20 text-right flex-1 bg-surface1 border-0 br-8" placeholder="0,00" oninput="calcularCambioMix()" />
                                <button id="mixBtnAddPago" onclick="addPagoMixto()" class="btn-save p-12-24 br-8 font-bold" style="height:unset; font-size:14px;"><?php echo L('tpv_add'); ?></button>
                            </div>
                        </div>
                        <!-- Cambio efectivo -->
                        <div id="extraEfectivo" class="d-none mt-2 text-right">
                            <span class="fs-13 font-bold text-accent"><?php echo L('tpv_change_to_return'); ?>: <span id="efectivoCambio" class="font-mono fs-16">0,00 €</span></span>
                        </div>
                        <!-- A cuenta fecha -->
                        <div id="extraAcuenta" class="d-none mt-8 p-8 br-8 bg-surface1 border-1 border-dashed mb-8">
                            <div class="form-group">
                                <label class="fs-11 opacity-70 mb-2 d-block"><?php echo L('tpv_payment_deadline'); ?></label>
                                <input id="aCuentaFechaLimite" type="date" class="form-input fs-12 p-4-8 bg-transparent" style="border:0; border-bottom:1px solid var(--border-color);" />
                            </div>
                            <div id="aCuentaAlertaCliente" class="d-none mt-4 p-4 bg-red-light br-4 text-red font-bold fs-10 text-center">
                                <?php echo L('tpv_select_client_to_owe'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Comentarios -->
                <div class="form-group br-8 border-2" style="padding: 14px 18px; background: var(--surface2);">
                    <label class="form-label fs-13 font-bold d-flex ai-center gap-8">
                        <i class="fa-solid fa-comment-dots text-accent"></i> <?php echo L('client_comments_label'); ?>
                    </label>
                    <textarea id="ticketComentarios" class="form-input fs-12" placeholder="<?php echo L('client_comments_placeholder'); ?>" rows="2" style="resize:none;"></textarea>
                </div>

                <!-- Spacer push footer to bottom -->
                <div style="flex:1;"></div>

            </div><!-- /right col -->

        </div><!-- /grid -->

        <!-- Footer -->
        <div class="modal-footer" style="border-top: 1px solid var(--border); padding: 16px 28px;">
            <button onclick="cerrarModalCliente()" class="btn-cancel"><?php echo L('modal_cancel'); ?></button>
            <button id="confirmarClienteBtn" onclick="ejecutarCobroFinal()" class="btn-save"><?php echo L('tpv_charge'); ?></button>
        </div>
    </div>
</div>
</div>


<!-- MODAL PRODUCTO COMODÍN -->
<?php
require_once __DIR__ . '/../model/TipoIVAPDO.php';
$ivasVigentes = TipoIVAPDO::listarVigentesActuales();
?>
<div id="modalComodin" class="modal-overlay">
    <div class="modal modal-content ai-stretch w-450 p-32">
        <div class="d-flex ai-center gap-16 pb-16 border-bottom">
            <div class="modal-icon text-accent bg-accent-light m-0">
                <i class="fa-solid fa-box-open"></i>
            </div>
            <div>
                <div class="fs-18 fw-700"><?php echo L('tpv_custom_product_title'); ?></div>
                <div class="fs-12 text-muted"><?php echo L('tpv_add_custom_product'); ?></div>
            </div>
            <button onclick="cerrarModalComodin()" class="btn-close-modal" style="margin-left: auto;">&times;</button>
        </div>

        <div class="modal-body p-0 mt-24 grid gap-32">
            <div class="form-group mb-0">
                <label class="form-label fw-600 mb-4 fs-13 text-muted tt-uppercase ls-1"><?php echo L('tpv_custom_desc'); ?></label>
                <input id="comodinDesc" type="text" class="form-input" placeholder="<?php echo L('tpv_custom_desc_placeholder'); ?>" autocomplete="off" />
            </div>
            
            <div class="grid-2 gap-24">
                <div class="form-group mb-0">
                    <label class="form-label fw-600 mb-4 fs-13 text-muted tt-uppercase ls-1"><?php echo L('tpv_custom_price'); ?></label>
                    <input id="comodinPrice" type="number" step="0.01" min="0" class="form-input font-mono text-right" placeholder="0.00" />
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fw-600 mb-4 fs-13 text-muted tt-uppercase ls-1"><?php echo L('modal_label_iva'); ?></label>
                    <select id="comodinIva" class="form-input font-mono text-right">
                        <?php foreach ($ivasVigentes as $iv): ?>
                            <option value="<?php echo $iv['porcentaje']; ?>"><?php echo $iv['nombre']; ?> (<?php echo (float)$iv['porcentaje']; ?>%)</option>
                        <?php endforeach; ?>
                        <?php if (empty($ivasVigentes)): ?>
                            <option value="21">21%</option>
                            <option value="10">10%</option>
                            <option value="4">4%</option>
                            <option value="0">0%</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
            
            <div id="comodinError" class="d-none text-red fs-12 font-bold text-center"></div>
        </div>

        <div class="modal-footer full-width jc-end mt-24 pt-16 border-top">
            <button onclick="cerrarModalComodin()" class="btn-cancel"><?php echo L('modal_cancel'); ?></button>
            <button onclick="agregarComodin()" class="btn-save d-flex ai-center gap-8">
                <i class="fa-solid fa-plus"></i> <?php echo L('modal_add_item'); ?>
            </button>
        </div>
    </div>
</div>


<style>
    .qty-input {
        width: 40px;
        background: none;
        border: none;
        text-align: center;
        font-weight: 700;
        font-family: inherit;
        font-size: 14px;
        color: var(--text);
        padding: 0;
        margin: 0;
    }
    /* Quitar flechas en Chrome/Safari/Edge */
    .qty-input::-webkit-outer-spin-button,
    .qty-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    /* Quitar flechas en Firefox */
    .qty-input[type=number] {
        -moz-appearance: textfield;
        appearance: textfield;
    }
    .qty-input:focus {
        outline: none;
        background: var(--surface2);
        border-radius: 4px;
    }
</style>