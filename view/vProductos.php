<?php // Vista de Productos 
?>
<div class="main-full p-24">

    <div class="section-header container-wider">
        <div class="section-title">
            <h1><?php echo L('prod_title'); ?></h1>
            <p><?php echo L('prod_subtitle'); ?></p>
        </div>
        <div class="d-flex gap-12 ai-center flex-wrap">
            <a href="index.php?irDashboard=1" class="btn-back">
                <?php echo L('login_back'); ?>
            </a>

            <div class="tabs-nav mr-12">
                <button class="tab-btn active" onclick="switchTab('productos')" id="btnTabProductos">
                    <?php echo L('prod_tab_standard'); ?>
                </button>
                <button class="tab-btn" onclick="switchTab('packs')" id="btnTabPacks">
                    <?php echo L('prod_tab_packs'); ?>
                </button>
            </div>

            <div class="d-flex gap-8 ai-center bg-surface2 p-4 br-12 border">
                <!-- Botón Exportar -->
                <div class="ie-dropdown-wrap" id="exportDropdownWrap">
                    <button class="btn-filter" onclick="toggleExportDropdown()" style="border:none; background:transparent; color: var(--text-main) !important;">
                        <?php echo L('prod_btn_export'); ?>
                    </button>
                    <div class="ie-dropdown" id="exportDropdown">
                        <a href="api/exportarProductos.php?format=csv" class="ie-dropdown-item">
                            <?php echo L('prod_export_csv'); ?>
                        </a>
                        <a href="api/exportarProductos.php?format=json" class="ie-dropdown-item">
                            <?php echo L('prod_export_json'); ?>
                        </a>
                    </div>
                </div>

                <div class="vr mx-4" style="height: 20px; width: 1px; background: var(--border); opacity: 0.5;"></div>

                <button class="btn-filter" onclick="document.getElementById('importFileInput').click()" style="border:none; background:transparent; color: var(--text-main) !important;">
                    <?php echo L('prod_btn_import'); ?>
                </button>
                <input type="file" id="importFileInput" accept=".csv,.json" class="d-none" onchange="importarProductos(this)">

                <div class="vr mx-4" style="height: 20px; width: 1px; background: var(--border); opacity: 0.5;"></div>

                <button onclick="abrirModalGestionCategorias()" class="btn-filter" style="border:none; background:transparent; color: var(--text-main) !important;">
                    <?php echo L('prod_btn_categories'); ?>
                </button>

                <div class="vr mx-4" style="height: 20px; width: 1px; background: var(--border); opacity: 0.5;"></div>

                <button onclick="generarPedidoAutomatico()" class="btn-filter" id="btnPedidoAuto" style="border:none; background:transparent; color: var(--accent-primary) !important; font-weight: 600;">
                    <?php echo L('prod_btn_auto_order'); ?>
                </button>

                <div class="vr mx-4" style="height: 20px; width: 1px; background: var(--border); opacity: 0.5;"></div>

                <button onclick="abrirModalMargenMasivo()" class="btn-filter" id="btnMargenMasivo" style="border:none; background:transparent; color: #7c3aed !important; font-weight: 600;" title="<?php echo L('prod_tip_mass_margin'); ?>">
                    <?php echo L('prod_btn_mass_margin'); ?>
                </button>
            </div>

            <div class="flex-1"></div>

            <button onclick="abrirModalProducto()" class="btn-save h-44 px-20 shadow-sm" id="btnNuevoProducto">
                <i class="fa-solid fa-plus"></i> <?php echo L('prod_btn_new_product'); ?>
            </button>
            <button onclick="abrirModalPack()" class="btn-save h-44 px-20 shadow-sm d-none" id="btnNuevoPack">
                <i class="fa-solid fa-plus"></i> <?php echo L('prod_btn_new_pack'); ?>
            </button>
        </div>
    </div>

    <!-- PANEL DE FILTROS -->
    <div class="filters-panel container-wider">
        <div class="search-bar-wrap flex-1">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" id="prodSearch" placeholder="<?php echo L('prod_search_placeholder'); ?>" class="search-input">
        </div>
        <div class="d-flex gap-8" id="filtersPrecios">
            <input type="number" id="filterPriceMin" placeholder="<?php echo L('prod_filter_price_min'); ?>" class="form-input fs-12" style="width: 125px;">
            <input type="number" id="filterPriceMax" placeholder="<?php echo L('prod_filter_price_max'); ?>" class="form-input fs-12" style="width: 125px;">
        </div>
        <select id="filterCat" class="filter-input p-10 br-8" onchange="applyFilters()">
            <option value="all"><?php echo L('prod_filter_cat_all'); ?></option>
            <?php foreach ($avProductos['categorias'] as $c): ?>
                <option value="<?php echo htmlspecialchars($c['codigo']); ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
            <?php endforeach; ?>
        </select>
        <select id="filterEstado" class="filter-input p-10 br-8" onchange="applyFilters()">
            <option value="all"><?php echo L('prod_filter_status_all'); ?></option>
            <option value="1"><?php echo L('prod_status_active'); ?></option>
            <option value="0"><?php echo L('prod_status_inactive'); ?></option>
            <option value="bajo_stock"><?php echo L('prod_status_low_stock'); ?></option>
        </select>
    </div>

    <!-- TABLA DE PRODUCTOS (PESTAÑA 1) -->
    <div class="table-container container-wider tab-content active" id="tabContentProductos">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-60 pl-20"><?php echo L('prod_th_id'); ?></th>
                    <th class="w-80 text-center"><?php echo L('prod_th_icon'); ?></th>
                    <th><?php echo L('prod_th_name'); ?></th>
                    <th><?php echo L('prod_th_code'); ?></th>
                    <th><?php echo L('prod_th_category'); ?></th>
                    <th class="text-right"><?php echo L('prod_th_stock'); ?></th>
                    <th class="text-right"><?php echo L('prod_th_price'); ?></th>
                    <th class="text-center"><?php echo L('prod_th_status'); ?></th>
                    <th class="text-center pr-20"><?php echo L('prod_th_actions'); ?></th>
                </tr>
            </thead>
            <tbody id="productsTableBody">
                <?php foreach ($avProductos['productos'] as $p): if (!empty($p['es_pack'])) continue; ?>
                    <tr class="product-row row-tipo-producto"
                        data-nombre="<?php echo strtolower(htmlspecialchars($p['nombre'])); ?>"
                        data-codigo="<?php echo strtolower(htmlspecialchars($p['codigo'])); ?>"
                        data-categoria="<?php echo $p['categoria']; ?>"
                        data-precio="<?php echo $p['precio']; ?>"
                        data-stock="<?php echo $p['stock']; ?>"
                        data-stock-minimo="<?php echo $p['stock_minimo']; ?>"
                        data-activo="<?php echo $p['activo'] ? '1' : '0'; ?>">
                        <td class="font-mono text-muted"><?php echo $p['id']; ?></td>
                        <td class="text-center">
                            <?php if (strpos($p['icono'], 'data:image') === 0): ?>
                                <img src="<?php echo $p['icono']; ?>" class="prod-img-fixed" alt="Icono">
                            <?php else: ?>
                                <span class="fs-24"><?php echo $p['icono']; ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="font-bold"><?php echo htmlspecialchars($p['nombre']); ?></td>
                        <td class="text-muted"><?php echo htmlspecialchars($p['codigo']); ?></td>
                        <td>
                            <span class="cat-pill">
                                <?php echo htmlspecialchars($p['categoria']); ?>
                            </span>
                            <?php
                            $atributosStr = $p['atributos'] ?? null;
                            if ($atributosStr) {
                                $atributosArr = json_decode($atributosStr, true);
                                if (is_array($atributosArr) && count($atributosArr) > 0) {
                                    echo '<div class="mt-4 d-flex flex-wrap gap-4">';
                                    foreach ($atributosArr as $attr) {
                                        echo '<span class="px-8 py-2 br-4 fs-10 bg-accent-soft text-accent border border-accent">' . htmlspecialchars($attr) . '</span>';
                                    }
                                    echo '</div>';
                                }
                            }
                            ?>
                        </td>
                        <td class="text-right font-bold font-mono">
                            <?php
                            $stock = (int)$p['stock'];
                            $stockMin = (int)$p['stock_minimo'];
                            $esCritico = ($stockMin > 0 && $stock <= $stockMin);
                            ?>
                            <div class="d-flex flex-column ai-end">
                                <span class="<?php echo $esCritico ? 'text-red bg-red-soft px-4 br-4' : ''; ?>">
                                    <?php echo $stock; ?>
                                </span>
                                <?php if ($esCritico): ?>
                                    <span class="fs-9 tt-uppercase text-red font-bold"><?php echo L('prod_stock_min_label'); ?> <?php echo $stockMin; ?></span>
                                <?php else: ?>
                                    <span class="fs-9 tt-uppercase text-muted"><?php echo L('prod_stock_min_label'); ?> <?php echo $stockMin; ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-right font-bold font-mono">
                            <?php echo number_format($p['precio'], 2, ',', '.'); ?> €
                        </td>
                        <td class="text-center">
                            <?php if ($p['activo']): ?>
                                <span class="status-pill status-active">
                                    <i class="fa-solid fa-circle-check"></i> <?php echo L('prod_pill_active'); ?>
                                </span>
                            <?php else: ?>
                                <span class="status-pill status-inactive">
                                    <i class="fa-solid fa-circle-xmark"></i> <?php echo L('prod_pill_inactive'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex jc-center gap-8 pr-20">
                                <button onclick="abrirModalHistorial(<?php echo $p['id']; ?>, '<?php echo addslashes(htmlspecialchars($p['nombre'])); ?>')" title="<?php echo L('prod_tip_history_stock'); ?>" class="btn-icon text-accent">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                </button>
                                <button onclick="abrirModalHistorialPrecios(<?php echo $p['id']; ?>, '<?php echo addslashes(htmlspecialchars($p['nombre'])); ?>')" title="<?php echo L('prod_tip_history_prices'); ?>" class="btn-icon" style="color: var(--yellow, #f59e0b);">
                                    <i class="fa-solid fa-tag"></i>
                                </button>
                                <button onclick='abrirModalProducto(<?php echo json_encode($p); ?>)' title="<?php echo L('user_tip_edit'); ?>" class="btn-icon">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button onclick="toggleEstadoProducto(<?php echo $p['id']; ?>)" title="<?php $p['activo'] ? L('prod_tip_deactivate') : L('prod_tip_activate'); ?>" class="btn-icon <?php echo $p['activo'] ? 'text-red' : 'text-green'; ?>">
                                    <i class="fa-solid fa-<?php echo $p['activo'] ? 'arrow-down' : 'arrow-up'; ?>"></i>
                                </button>
                                <button onclick="eliminarProducto(<?php echo $p['id']; ?>, '<?php echo addslashes(htmlspecialchars($p['nombre'])); ?>')" title="<?php echo L('modal_delete'); ?>" class="btn-icon text-red" style="opacity:0.7;">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr id="noResults" class="d-none">
                    <td colspan="9">
                        <div class="empty-state">
                            <i class="fa-solid fa-box-open"></i>
                            <?php echo L('prod_no_results'); ?>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- TABLA DE PACKS (PESTAÑA 2) -->
    <div class="table-container container-wider tab-content d-none" id="tabContentPacks">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-60 pl-20"><?php echo L('prod_th_id'); ?></th>
                    <th class="w-80 text-center"><?php echo L('prod_th_icon'); ?></th>
                    <th><?php echo L('prod_th_pack_name'); ?></th>
                    <th><?php echo L('prod_th_pack_ref'); ?></th>
                    <th><?php echo L('prod_th_pack_components'); ?></th>
                    <th class="text-right"><?php echo L('prod_th_pack_price'); ?></th>
                    <th class="text-center"><?php echo L('prod_th_status'); ?></th>
                    <th class="text-center pr-20"><?php echo L('prod_th_actions'); ?></th>
                </tr>
            </thead>
            <tbody id="packsTableBody">
                <?php foreach ($avProductos['productos'] as $p): if (empty($p['es_pack'])) continue; ?>
                    <tr class="product-row row-tipo-pack"
                        data-nombre="<?php echo strtolower(htmlspecialchars($p['nombre'])); ?>"
                        data-codigo="<?php echo strtolower(htmlspecialchars($p['codigo'])); ?>"
                        data-categoria="<?php echo $p['categoria']; ?>"
                        data-precio="<?php echo $p['precio']; ?>"
                        data-activo="<?php echo $p['activo'] ? '1' : '0'; ?>">
                        <td class="font-mono text-muted"><?php echo $p['id']; ?></td>
                        <td class="text-center">
                            <?php if (strpos($p['icono'], 'data:image') === 0): ?>
                                <img src="<?php echo $p['icono']; ?>" class="prod-img-fixed" alt="Icono">
                            <?php else: ?>
                                <span class="fs-24"><?php echo $p['icono']; ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="font-bold">
                            <?php echo htmlspecialchars($p['nombre']); ?>
                            <span class="ml-8 px-6 py-2 br-4 fs-10 bg-accent-soft text-accent border border-accent"><?php echo L('prod_pill_pack'); ?></span>
                        </td>
                        <td class="text-muted"><?php echo htmlspecialchars($p['codigo']); ?></td>
                        <td class="fs-12 text-muted">
                            <?php
                            if (!empty($p['componentes_pack']) && is_array($p['componentes_pack'])) {
                                $comps = [];
                                foreach ($p['componentes_pack'] as $c) {
                                    $comps[] = "{$c['cantidad']}x " . htmlspecialchars($c['nombre']);
                                }
                                echo implode('<br>', $comps);
                            } else {
                                echo '<em>' . L('prod_no_components', true) . '</em>';
                            }
                            ?>
                        </td>
                        <td class="text-right font-bold font-mono text-accent">
                            <?php echo number_format($p['precio'], 2, ',', '.'); ?> €
                        </td>
                        <td class="text-center">
                            <?php if ($p['activo']): ?>
                                <span class="status-pill status-active">
                                    <i class="fa-solid fa-circle-check"></i> <?php echo L('prod_pill_active'); ?>
                                </span>
                            <?php else: ?>
                                <span class="status-pill status-inactive">
                                    <i class="fa-solid fa-circle-xmark"></i> <?php echo L('prod_pill_inactive'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex jc-center gap-8 pr-20">
                                <button onclick='abrirModalPack(<?php echo json_encode($p); ?>)' title="<?php echo L('user_tip_edit'); ?>" class="btn-icon">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button onclick="toggleEstadoProducto(<?php echo $p['id']; ?>)" title="<?php $p['activo'] ? L('prod_tip_deactivate') : L('prod_tip_activate'); ?>" class="btn-icon <?php echo $p['activo'] ? 'text-red' : 'text-green'; ?>">
                                    <i class="fa-solid fa-<?php echo $p['activo'] ? 'arrow-down' : 'arrow-up'; ?>"></i>
                                </button>
                                <button onclick="eliminarProducto(<?php echo $p['id']; ?>, '<?php echo addslashes(htmlspecialchars($p['nombre'])); ?>')" title="<?php echo L('modal_delete'); ?>" class="btn-icon text-red" style="opacity:0.7;">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr id="noResultsPacks" class="d-none">
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="fa-solid fa-boxes-stacked"></i>
                            <?php echo L('prod_no_results_packs'); ?>
                        </div>
                    </td>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>



<style>
    /* ===== ESTILOS MEJORADOS MODAL DE PRODUCTO ===== */

    /* --- Layout general del modal --- */
    #modalProducto .modal-content {
        display: flex;
        flex-direction: column;
        max-height: 92vh;
    }

    #modalProducto #formProducto {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        min-height: 0;
    }

    #modalProducto .modal-footer {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 20px 32px;
        border-top: 2px solid var(--surface2);
        background: var(--surface);
    }

    .modal-footer .btn-save {
        margin-left: 12px;
    }

    .modal-footer .btn-cancel {
        margin-right: 12px;
    }

    /* --- Tabs --- */
    .tabs-nav {
        display: flex;
        gap: 8px;
        padding: 12px 24px 0;
        border-bottom: 2px solid var(--border, var(--surface2));
        background: var(--surface2);
        flex-shrink: 0;
    }

    .tab-btn {
        position: relative;
        background: none;
        border: none;
        padding: 12px 24px;
        cursor: pointer;
        opacity: 0.55;
        transition: all 0.2s;
        font-weight: 700;
        font-size: 13px;
        border-radius: 8px 8px 0 0;
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
        border-bottom: 3px solid transparent;
        color: var(--text);
    }

    .tab-btn:hover {
        opacity: 0.85;
        background: rgba(0, 0, 0, 0.06);
    }

    .tab-btn.active {
        opacity: 1;
        border-bottom-color: var(--accent);
        color: var(--accent);
        background: var(--bg-body, var(--surface));
    }

    /* --- Campo CMP bloqueado --- */
    .input-readonly-cmp {
        background: var(--surface2) !important;
        border-color: transparent !important;
        cursor: not-allowed !important;
        color: var(--text-muted) !important;
        font-style: italic;
    }

    .cmp-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--accent);
        background: rgba(59, 130, 246, 0.12);
        border: 1px solid var(--accent);
        border-radius: 20px;
        padding: 2px 8px;
    }

    /* --- Secciones dentro de pestaña --- */
    .tab-section {
        background: var(--bg-body, var(--surface));
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        border: 1.5px solid var(--border, var(--surface2));
    }

    .tab-section-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--text-muted);
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .tab-section .form-group+.form-group {
        margin-top: 16px;
    }

    /* --- Tabla de Tarifas --- */
    #listaTarifasProductoBody tr td,
    #listaTarifasProductoBody tr+tr th {
        padding: 10px 12px;
        vertical-align: middle;
    }

    .tarifas-table th {
        padding: 10px 12px;
        white-space: nowrap;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-weight: 700;
        color: var(--text-muted);
        background: var(--surface2);
    }

    .tarifas-table th:first-child {
        min-width: 180px;
    }

    .tarifas-table th:nth-child(2) {
        min-width: 90px;
    }

    .tarifas-table th:nth-child(3) {
        min-width: 90px;
    }

    .tarifas-table th:nth-child(4) {
        min-width: 80px;
    }

    .tarifas-table th:last-child {
        min-width: 70px;
    }

    .tarifas-table td {
        padding: 10px 12px;
        vertical-align: middle;
        font-size: 13px;
    }
</style>


<!-- MODAL AÑADIR/EDITAR PRODUCTO -->
<div id="modalProducto" class="modal-overlay-bg">
    <div class="modal-content" style="max-width: 900px; padding: 0; overflow: hidden; border-radius: 20px;">
        <div class="modal-header">
            <h2 id="modalTitle"><?php echo L('prod_modal_title_new'); ?></h2>
            <button onclick="cerrarModalProducto()" class="btn-close-modal">&times;</button>
        </div>
        <form id="formProducto" class="modal-body p-0">
            <!-- TABS NAVIGATION -->
            <div class="tabs-nav border-bottom px-20 pt-10 bg-surface1">
                <button type="button" class="tab-btn active" onclick="switchModalTab('general', this)">
                    <?php echo L('prod_modal_tab_general'); ?>
                </button>
                <button type="button" class="tab-btn" onclick="switchModalTab('precios', this)">
                    <?php echo L('prod_modal_tab_precios'); ?>
                </button>
                <button type="button" class="tab-btn" onclick="switchModalTab('tarifas', this)" id="btnTabModalTarifas">
                    <?php echo L('prod_modal_tab_tarifas'); ?>
                </button>
            </div>

            <div class="p-24">
                <input type="hidden" id="prodId">

                <!-- TAB GENERAL -->
                <div id="modalTabGeneral" class="modal-tab-content">
                    <div class="d-grid grid-1-3 gap-32 mb-32">
                        <!-- Columna Izquierda: Imagen -->
                        <div class="d-flex flex-column gap-20">
                            <div class="form-group mb-0">
                                <label class="form-label mb-8"><?php echo L('prod_modal_label_image'); ?></label>
                                <div class="d-flex flex-column ai-center gap-16 p-20 bg-surface2 br-16 border-2">
                                    <div id="imgPreview" class="prod-img-preview m-0" style="width: 140px; height: 140px;">
                                        <i class="fa-solid fa-image fs-40 opacity-20"></i>
                                    </div>
                                    <input type="file" id="prodFile" accept="image/*" class="d-none" onchange="previewImage(this)">
                                    <button type="button" onclick="document.getElementById('prodFile').click()" class="btn-filter w-auto h-auto p-12-20 fs-13 gap-8">
                                        <i class="fa-solid fa-upload"></i> <?php echo L('prod_modal_btn_upload'); ?>
                                    </button>
                                    <input type="hidden" id="prodIcono">
                                </div>
                            </div>
                        </div>

                        <!-- Columna Derecha: Datos Principales -->
                        <div class="d-flex flex-column gap-32">
                            <div class="form-group mb-0">
                                <label class="form-label mb-4"><?php echo L('prod_modal_label_name'); ?></label>
                                <input type="text" id="prodNombre" placeholder="<?php echo L('prod_placeholder_name'); ?>" class="form-input p-12 fs-15">
                                <span class="form-error mt-4" id="err-nombre"></span>
                            </div>
                            <div class="d-grid grid-2 gap-32">
                                <div class="form-group mb-0">
                                    <label class="form-label mb-4"><?php echo L('prod_modal_label_sku'); ?></label>
                                    <input type="text" id="prodCodigo" placeholder="<?php echo L('prod_placeholder_sku'); ?>" class="form-input font-mono p-12">
                                    <span class="form-error mt-4" id="err-referencia"></span>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label mb-4"><?php echo L('prod_th_category'); ?></label>
                                    <select id="prodCat" class="form-input p-12">
                                        <?php foreach ($avProductos['categorias'] as $c): ?>
                                            <option value="<?php echo htmlspecialchars($c['codigo']); ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-32">
                        <label class="form-label mb-4"><?php echo L('prod_modal_label_desc'); ?></label>
                        <textarea id="prodDesc" placeholder="<?php echo L('prod_placeholder_desc'); ?>" class="form-input" rows="2"></textarea>
                    </div>

                    <div class="d-grid gap-32 mb-16 p-16 bg-surface2 br-12 border-2" style="grid-template-columns: 1fr 1fr;">
                        <div class="form-group mb-0">
                            <label class="form-label fs-11 tt-uppercase mb-4"><?php echo L('prod_modal_label_provider'); ?></label>
                            <select id="prodProveedor" class="form-input">
                                <option value=""><?php echo L('prod_modal_label_provider_none'); ?></option>
                                <?php foreach ($avProductos['proveedores'] as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" data-re="<?php echo $p['aplica_re']; ?>">
                                        <?php echo htmlspecialchars($p['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label fs-11 tt-uppercase mb-4"><?php echo L('prod_modal_label_iva'); ?></label>
                            <select id="prodIvaTipo" class="form-input">
                                <?php foreach ($avProductos['tipos_iva'] as $t): ?>
                                    <option value="<?php echo htmlspecialchars($t['codigo']); ?>"
                                        data-porcentaje="<?php echo $t['porcentaje']; ?>"
                                        data-re="<?php echo $t['recargo_equivalencia'] ?? 0; ?>">
                                        <?php echo htmlspecialchars($t['nombre'] . ' (' . (float)$t['porcentaje'] . '%)'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <!-- /TAB GENERAL -->

                <!-- TAB PRECIOS Y STOCK -->
                <div id="modalTabPrecios" class="modal-tab-content d-none">

                    <!-- Coste y Margen -->
                    <div class="tab-section" style="position:relative;">
                        <div class="tab-section-title">
                            <i class="fa-solid fa-lock-open"></i> <?php echo L('prod_modal_label_cost_cmp'); ?>
                        </div>
                        <div class="d-flex ai-center gap-8 mb-4">
                            <span class="cmp-badge"><i class="fa-solid fa-robot"></i> <?php echo L('prod_modal_badge_auto'); ?></span>
                            <span class="fs-11 text-muted"><?php echo L('prod_modal_badge_auto_desc'); ?></span>
                        </div>
                        <div class="d-grid gap-16 mt-12" style="grid-template-columns: 1fr 1fr;">
                            <div class="form-group mb-0">
                                <label class="form-label fs-11 tt-uppercase d-flex ai-center jc-between mb-4">
                                    <span><?php echo L('prod_modal_label_cost'); ?> <span class="opacity-50">(<?php echo L('optional'); ?>)</span></span>
                                    <button type="button" id="btnHistorialCostes" onclick="abrirModalHistorialCostes()" class="btn-icon p-0 fs-11 text-accent" title="<?php echo L('prod_modal_tip_cost_history'); ?>" style="display:none;">
                                        <?php echo L('prod_modal_btn_history'); ?>
                                    </button>
                                </label>
                                <input type="text" id="prodPrecioCoste" placeholder="<?php echo L('prod_placeholder_price'); ?>"
                                    class="form-input text-right font-mono"
                                    readonly
                                    tabindex="-1"
                                    title="<?php echo L('prod_modal_tip_cost_auto'); ?>">
                                <span class="form-error" id="err-precio_coste"></span>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label fs-11 tt-uppercase mb-4"><?php echo L('prod_modal_label_margin'); ?></label>
                                <input type="number" id="prodMargen" placeholder="<?php echo L('prod_placeholder_margin'); ?>" step="0.01"
                                    class="form-input text-right font-mono"
                                    oninput="calcularPrecioDesdeMargen()"
                                    title="<?php echo L('prod_modal_tip_margin_calc'); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- PVP Venta -->
                    <div class="tab-section">
                        <div class="tab-section-title">
                            <?php echo L('prod_th_price'); ?>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label fs-11 tt-uppercase mb-4"><?php echo L('prod_th_price'); ?> (€) <span class="opacity-50">(<?php echo L('optional'); ?>)</span></label>
                            <input type="text" id="prodPrecioVenta" placeholder="<?php echo L('prod_placeholder_price'); ?>"
                                class="form-input text-right font-bold font-mono text-accent fs-18"
                                oninput="calcularMargenDesdePrecio()"
                                title="<?php echo L('prod_modal_tip_price_calc'); ?>">
                            <span class="form-error" id="err-precio_venta"></span>
                        </div>
                    </div>

                    <!-- Stock -->
                    <div class="tab-section">
                        <div class="tab-section-title">
                            <?php echo L('prod_th_stock'); ?>
                        </div>
                        <div class="d-grid grid-3 gap-32">
                            <div class="form-group mb-0 text-center d-flex flex-column ai-center">
                                <label class="form-label fs-11 tt-uppercase d-flex ai-center gap-4 jc-center mb-4">
                                    <?php echo L('prod_modal_label_stock_actual'); ?> <span class="opacity-50">(<?php echo L('optional'); ?>)</span> <i class="fa-solid fa-circle-info text-muted" title="<?php echo L('prod_modal_tip_stock_manual'); ?>"></i>
                                </label>
                                <div class="d-flex jc-center w-full" style="max-width: 140px; position: relative;">
                                    <input type="text" id="prodStock" placeholder="<?php echo L('prod_placeholder_stock'); ?>" class="form-input text-center font-bold flex-1 h-44 fs-16" style="border-radius: 12px; border-width: 2px;">
                                    <button type="button" id="btnRetirarStock" class="btn-icon text-red d-none" onclick="retirarStockManual()" title="<?php echo L('prod_modal_tip_stock_withdraw'); ?>" style="position: absolute; right: -36px; top: 50%; transform: translateY(-50%);">
                                        <i class="fa-solid fa-minus-circle fs-20"></i>
                                    </button>
                                </div>
                                <span class="form-error" id="err-stock_actual"></span>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label fs-11 tt-uppercase mb-4"><?php echo L('prod_modal_label_stock_min'); ?></label>
                                <input type="text" id="prodStockMin" placeholder="<?php echo L('prod_placeholder_stock_min'); ?>" class="form-input text-center">
                                <span class="form-error" id="err-stock_minimo"></span>
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label fs-11 tt-uppercase mb-4"><?php echo L('prod_modal_label_warranty'); ?></label>
                                <input type="text" id="prodGarantia" placeholder="<?php echo L('prod_placeholder_warranty'); ?>" class="form-input text-center">
                                <span class="form-error" id="err-meses_garantia"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /TAB PRECIOS Y STOCK -->


                <!-- TAB TARIFAS -->
                <div id="modalTabTarifas" class="modal-tab-content d-none">
                    <div class="p-16 border-2 br-12 mb-24 bg-surface2">
                        <label class="form-label mb-8 d-flex ai-center gap-8">
                            <i class="fa-solid fa-percent text-accent"></i> <?php echo L('prod_modal_tab_tarifas_title'); ?>
                        </label>
                        <div class="fs-12 text-muted mb-16" style="line-height:1.6;">
                            <?php echo L('prod_modal_tab_tarifas_desc'); ?>
                            <?php echo L('prod_modal_tab_tarifas_desc2'); ?>
                        </div>
                        <div class="table-container" style="background: var(--bg-body); border-radius: 8px; overflow: hidden;">
                            <table class="w-100 tarifas-table" style="border-collapse: collapse;">
                                <thead>
                                    <tr>
                                        <th class="text-left"><?php echo L('prod_modal_th_rule'); ?></th>
                                        <th class="text-center"><?php echo L('prod_modal_th_type'); ?></th>
                                        <th class="text-right"><?php echo L('prod_modal_th_variation'); ?></th>
                                        <th class="text-center"><?php echo L('prod_modal_th_priority'); ?></th>
                                        <th class="text-center"><?php echo L('prod_modal_th_action'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="listaTarifasProductoBody">
                                    <!-- JS filler -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- /TAB TARIFAS -->

            </div><!-- /p-24 -->
        </form><!-- /formProducto -->

        <div class="modal-footer">
            <button type="button" onclick="cerrarModalProducto()" class="btn-cancel"><?php echo L('modal_cancel'); ?></button>
            <div class="flex-1"></div>
            <button type="button" id="btnGuardarProd" class="btn-save w-auto px-32"><?php echo L('prod_modal_btn_save'); ?></button>
        </div>
    </div><!-- /modal-content -->
</div><!-- /modalProducto -->

<!-- MODAL AÑADIR/EDITAR PACK -->
<div id="modalPack" class="modal-overlay-bg d-none">
    <div class="modal-content" style="max-width: 1000px; padding: 0; overflow: hidden; border-radius: 20px;">
        <div class="modal-header">
            <h2 id="modalPackTitle"><?php echo L('prod_modal_pack_title_new'); ?></h2>
            <button type="button" onclick="cerrarModalPack()" class="btn-close-modal">&times;</button>
        </div>
        <form id="formPack" class="modal-body">
            <input type="hidden" id="packId">

            <div class="d-grid grid-1-3 gap-24 mb-24">
                <!-- Columna Izquierda: Imagen y Meta -->
                <div class="d-flex flex-column gap-16">
                    <div class="form-group mb-0">
                        <label class="form-label"><?php echo L('prod_modal_pack_label_image'); ?></label>
                        <div class="d-flex flex-column ai-center gap-12 p-16 bg-surface2 br-12 border-2">
                            <div id="imgPreviewPack" class="prod-img-preview m-0" style="width: 120px; height: 120px;">
                                <i class="fa-solid fa-boxes-stacked fs-32 opacity-20"></i>
                            </div>
                            <input type="file" id="packFile" accept="image/*" class="d-none" onchange="previewImagePack(this)">
                            <button type="button" onclick="document.getElementById('packFile').click()" class="btn-filter w-auto h-auto p-10-16 fs-12 gap-8">
                                <i class="fa-solid fa-upload"></i> <?php echo L('prod_modal_btn_upload'); ?>
                            </button>
                            <input type="hidden" id="packIcono">
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Datos Principales -->
                <div class="d-flex flex-column gap-16">
                    <div class="form-group mb-0">
                        <label class="form-label"><?php echo L('prod_modal_pack_label_name'); ?></label>
                        <input type="text" id="packNombre" placeholder="<?php echo L('prod_placeholder_pack_name'); ?>" class="form-input" required>
                    </div>

                    <div class="d-grid grid-2 gap-16">
                        <div class="form-group mb-0">
                            <label class="form-label"><?php echo L('prod_modal_pack_label_sku'); ?></label>
                            <input type="text" id="packCodigo" placeholder="<?php echo L('prod_placeholder_pack_sku'); ?>" class="form-input font-mono">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label"><?php echo L('prod_th_category'); ?></label>
                            <select id="packCat" class="form-input">
                                <?php foreach ($avProductos['categorias'] as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c['codigo']); ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="d-grid grid-2 gap-16">
                        <div class="form-group mb-0">
                            <label class="form-label fs-11 tt-uppercase"><?php echo L('prod_modal_pack_label_price'); ?> (€)</label>
                            <input type="text" id="packPrecioVenta" placeholder="<?php echo L('prod_placeholder_price'); ?>" class="form-input text-right font-bold font-mono text-accent" required>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label fs-11 tt-uppercase"><?php echo L('prod_modal_pack_label_iva'); ?> (%)</label>
                            <select id="packIvaTipo" class="form-input">
                                <?php foreach ($avProductos['tipos_iva'] as $t): ?>
                                    <option value="<?php echo htmlspecialchars($t['codigo']); ?>">
                                        <?php echo htmlspecialchars($t['codigo'] . ' - ' . $t['porcentaje'] . '%'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Componentes del Pack -->
            <div class="p-20 border-2 border-accent br-12 mb-24 bg-surface1 col-span-1-3 pack-components-section" style="width: 100%; box-sizing: border-box; grid-column: 1 / -1;">

                <label class="form-label mb-12 d-flex ai-center gap-8 text-accent font-bold">
                    <i class="fa-solid fa-boxes-packing"></i> <?php echo L('prod_modal_pack_label_components'); ?>
                </label>
                <div class="fs-12 text-muted mb-24">
                    <?php echo L('prod_modal_pack_components_desc'); ?>
                </div>

                <div class="d-flex mb-24" style="width: 100%;">
                    <div class="pack-search-container" style="width: 100%; position: relative;">
                        <i class="fa-solid fa-magnifying-glass pack-search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); z-index: 10;"></i>
                        <input type="text" id="packSearchInput" class="form-input border-accent pl-40" style="width: 100%; padding-left: 40px !important;" placeholder="<?php echo L('prod_modal_pack_search_placeholder'); ?>" autocomplete="off" oninput="buscarProductoParaPack(this.value)">
                        <div id="packSearchResults" class="search-results-dropdown" style="display:none; position: absolute; width: 100%; left: 0; top: 100%; z-index: 1000; background: var(--surface); border: 1px solid var(--border); box-shadow: var(--shadow-lg); border-radius: 8px; max-height: 300px; overflow-y: auto;"></div>
                    </div>
                </div>


                <div class="table-container mt-12" style="max-height: 250px; overflow-y: auto; background: var(--bg-body);">
                    <table class="full-width fs-12 pack-components-table">
                        <thead>
                            <tr style="background: var(--surface2);">
                                <th class="p-8 text-left"><?php echo L('prod_th_sku'); ?></th>
                                <th class="p-8 text-left"><?php echo L('prod_th_name'); ?></th>
                                <th class="p-8 text-center" style="width: 80px;"><?php echo L('prod_th_qty'); ?></th>
                                <th class="p-8 text-center" style="width: 40px;"></th>
                            </tr>
                        </thead>
                        <tbody id="listaComponentesBody">
                            <!-- JS filler -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="cerrarModalPack()" class="btn-cancel"><?php echo L('modal_cancel'); ?></button>
                <div class="flex-1"></div>
                <button type="submit" id="btnGuardarPack" class="btn-save w-auto px-32" style="background: var(--accent); color: white;"><?php echo L('prod_modal_btn_save_pack'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL HISTORIAL STOCK -->
<style>
    #modalHistorialStock .modal-content {
        max-width: 900px !important;
        width: 90% !important;
        background: var(--surface) !important;
        color: var(--text);
    }

    #modalHistorialStock .historial-table-wrapper {
        width: 100%;
        overflow-x: auto;
    }

    #modalHistorialStock .stock-log-table {
        width: 100% !important;
        border-collapse: collapse !important;
        table-layout: fixed !important;
        display: table !important;
        margin: 0 !important;
    }

    #modalHistorialStock .stock-log-table thead {
        display: table-header-group !important;
    }

    #modalHistorialStock .stock-log-table tbody {
        display: table-row-group !important;
    }

    #modalHistorialStock .stock-log-table tr {
        display: table-row !important;
    }

    #modalHistorialStock .stock-log-table th,
    #modalHistorialStock .stock-log-table td {
        display: table-cell !important;
        padding: 16px 20px !important;
        border-bottom: 1px solid var(--border) !important;
        text-align: left;
        vertical-align: middle;
    }

    #modalHistorialStock .stock-log-table thead th {
        background: var(--surface2) !important;
        color: var(--text-muted) !important;
        font-size: 11px !important;
        text-transform: uppercase !important;
        font-weight: bold !important;
        position: sticky;
        top: 0;
        z-index: 20;
    }

    #modalHistorialStock .empty-log-container {
        padding: 80px 40px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        width: 100%;
        color: var(--text-muted);
    }

    #modalHistorialStock .empty-log-icon {
        font-size: 56px;
        margin-bottom: 24px;
        opacity: 0.15;
    }
</style>
<div id="modalHistorialStock" class="modal-overlay-bg d-none">
    <div class="modal-content printable-area" style="padding: 0; overflow: hidden; border: none; border-radius: 20px; box-shadow: 0 30px 60px rgba(0,0,0,0.4); background: var(--surface);">
        <div class="modal-header" style="background: linear-gradient(135deg, var(--surface) 0%, var(--surface2) 100%); padding: 32px; border-bottom: 1px solid var(--border); position: relative;">
            <div class="d-flex ai-center gap-20">
                <div class="bg-accent-soft p-16 br-16 text-accent" style="box-shadow: 0 8px 16px rgba(var(--accent-rgb), 0.1);">
                    <i class="fa-solid fa-clock-rotate-left fs-28"></i>
                </div>
                <div>
                    <h2 id="historialTitle" class="m-0 fs-24 font-bold mb-4"><?php echo L('prod_modal_stock_history_title'); ?></h2>
                    <div id="historialSubtitle" class="fs-14 opacity-70"></div>
                </div>
            </div>
            <button type="button" onclick="cerrarModalHistorial()" class="btn-close-modal" style="position: absolute; top: 32px; right: 32px; background: var(--surface2); width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border); transition: all 0.2s;">&times;</button>
        </div>

        <div class="modal-body" style="padding: 0; background: var(--surface); min-height: 400px;">
            <div class="table-container" style="max-height: 550px; overflow-y: auto; border-radius: 0;">
                <table class="stock-log-table">
                    <thead>
                        <tr>
                            <th style="width: 180px;"><?php echo L('prod_modal_th_datetime'); ?></th>
                            <th style="width: 160px;"><?php echo L('prod_modal_th_operation'); ?></th>
                            <th style="width: 110px;" class="text-center"><?php echo L('prod_modal_th_variation'); ?></th>
                            <th style="width: 150px;"><?php echo L('prod_modal_th_user'); ?></th>
                            <th style="width: auto;"><?php echo L('prod_modal_th_obs'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="historialTableBody" class="fs-13">
                        <!-- JS filler -->
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal-footer" style="padding: 24px 32px; background: var(--surface); border-top: 1px solid var(--border); display: flex; align-items: center;">
            <button type="button" onclick="cerrarModalHistorial()" class="btn-cancel" style="border: 1px solid var(--border); padding: 12px 24px; border-radius: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-xmark"></i> <?php echo L('modal_close'); ?>
            </button>
        </div>
    </div>
</div>

<!-- MODAL HISTORIAL DE PRECIOS -->
<div id="modalHistorialPrecios" class="modal-overlay-bg d-none">
    <div class="modal-content" style="max-width: 860px; padding: 0; overflow: hidden; border-radius: 20px; box-shadow: 0 30px 60px rgba(0,0,0,0.4); background: var(--surface);">
        <div class="modal-header" style="background: linear-gradient(135deg, var(--surface) 0%, var(--surface2) 100%); padding: 28px 32px; border-bottom: 1px solid var(--border); position: relative;">
            <div class="d-flex ai-center gap-16">
                <div style="background: rgba(245,158,11,0.12); color: #f59e0b; padding: 14px; border-radius: 14px; box-shadow: 0 4px 12px rgba(245,158,11,0.18);">
                    <i class="fa-solid fa-tag fs-22"></i>
                </div>
                <div>
                    <h2 id="historialPreciosTitle" class="m-0 fs-20 font-bold mb-4"><?php echo L('prod_modal_price_history_title'); ?></h2>
                    <div id="historialPreciosSubtitle" class="fs-13 opacity-60"></div>
                </div>
            </div>
            <button type="button" onclick="cerrarModalHistorialPrecios()" class="btn-close-modal" style="position: absolute; top: 28px; right: 28px; background: var(--surface2); width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border);">&times;</button>
        </div>
        <div class="modal-body" style="padding: 0; min-height: 300px;">
            <div style="max-height: 500px; overflow-y: auto;">
                <table class="data-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="padding: 14px 20px;"><?php echo L('prod_modal_th_date'); ?></th>
                            <th style="padding: 14px 20px; text-align: right;"><?php echo L('prod_modal_th_price_old'); ?></th>
                            <th style="padding: 14px 20px; text-align: right;"><?php echo L('prod_modal_th_price_new'); ?></th>
                            <th style="padding: 14px 20px; text-align: right;"><?php echo L('prod_modal_th_variation'); ?></th>
                            <th style="padding: 14px 20px;"><?php echo L('prod_modal_th_reason'); ?></th>
                            <th style="padding: 14px 20px;"><?php echo L('prod_modal_th_user'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="historialPreciosBody">
                        <tr>
                            <td colspan="6" class="text-center p-24 opacity-50"><?php echo L('loading'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer" style="padding: 20px 28px; border-top: 1px solid var(--border);">
            <button type="button" onclick="cerrarModalHistorialPrecios()" class="btn-cancel">
                <i class="fa-solid fa-xmark"></i> <?php echo L('modal_close'); ?>
            </button>
        </div>
    </div>
</div>

<script>
    async function abrirModalHistorialPrecios(id, nombre) {
        const modal = document.getElementById('modalHistorialPrecios');
        const title = document.getElementById('historialPreciosTitle');
        const subtitle = document.getElementById('historialPreciosSubtitle');
        const body = document.getElementById('historialPreciosBody');

        title.innerText = <?php echo json_encode(L('prod_js_history_for', true)); ?> + nombre;
        subtitle.innerText = <?php echo json_encode(L('prod_js_fetching_prices', true)); ?>;
        body.innerHTML = '<tr><td colspan="6" class="text-center p-24 opacity-50"><i class="fa-solid fa-spinner fa-spin mr-8"></i>' + <?php echo json_encode(L('prod_js_searching', true)); ?> + '</td></tr>';

        modal.classList.remove('d-none');
        modal.style.display = 'flex';

        try {
            const resp = await fetch(`api/obtenerHistorialPrecios.php?id=${id}`);
            const data = await resp.json();

            if (data.ok) {
                subtitle.innerText = data.historial.length > 0 ?
                    `${data.historial.length} ` + <?php echo json_encode(L('prod_hist_changes_registered', true)); ?> :
                    <?php echo json_encode(L('prod_hist_no_changes', true)); ?>;

                if (data.historial.length === 0) {
                    body.innerHTML = `
                        <tr><td colspan="6">
                            <div style="padding: 60px 40px; text-align: center; color: var(--text-muted);">
                                <div style="font-size: 48px; opacity: 0.15; margin-bottom: 20px;"><i class="fa-solid fa-tag"></i></div>
                                <div style="font-size: 16px; font-weight: bold; margin-bottom: 8px;"><?php echo L('prod_hist_empty_title'); ?></div>
                                <div style="font-size: 13px; opacity: 0.6;"><?php echo L('prod_hist_empty_sub'); ?></div>
                            </div>
                        </td></tr>`;
                } else {
                    body.innerHTML = data.historial.map(h => {
                        const anterior = parseFloat(h.precio_old);
                        const nuevo = parseFloat(h.precio_new);
                        const diff = nuevo - anterior;
                        const diffStr = (diff >= 0 ? '+' : '') + diff.toFixed(2);
                        const diffColor = diff < 0 ? 'color:#ef4444;' : diff > 0 ? 'color:#22c55e;' : 'color:var(--text-muted);';
                        const fecha = new Date(h.fecha);
                        return `
                        <tr>
                            <td style="padding: 14px 20px; font-size: 12px; color: var(--text-muted);">
                                ${fecha.toLocaleDateString()}<br>
                                <strong>${fecha.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</strong>
                            </td>
                            <td style="padding: 14px 20px; text-align: right; font-family: var(--font-main); text-decoration: line-through; opacity: 0.5;">${anterior.toFixed(2)} €</td>
                            <td style="padding: 14px 20px; text-align: right; font-family: var(--font-main); font-weight: bold;">${nuevo.toFixed(2)} €</td>
                            <td style="padding: 14px 20px; text-align: right; font-family: var(--font-main); font-weight: bold; ${diffColor}">${diffStr} €</td>
                            <td style="padding: 14px 20px; font-size: 13px; font-style: italic; opacity: 0.75;">${h.motivo || '<span style="opacity:0.3">' + <?php echo json_encode(L('prod_hist_no_reason', true)); ?> + '</span>'}</td>
                            <td style="padding: 14px 20px; font-size: 12px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="width:26px; height:26px; border-radius:50%; background:var(--surface2); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:bold; flex-shrink:0;">
                                        ${(h.nombre_usuario || 'S').charAt(0).toUpperCase()}
                                    </div>
                                    ${h.nombre_usuario || <?php echo json_encode(L('prod_hist_system', true)); ?>}
                                </div>
                            </td>
                        </tr>`;
                    }).join('');
                }
            } else {
                body.innerHTML = `<tr><td colspan="6" class="text-center p-32" style="color:var(--red);">Error: ${data.error}</td></tr>`;
            }
        } catch (e) {
            body.innerHTML = '<tr><td colspan="6" class="text-center p-32 text-red">' + <?php echo json_encode(L('prod_js_error_fetching_history', true)); ?> + '</td></tr>';
        }
    }

    function cerrarModalHistorialPrecios() {
        document.getElementById('modalHistorialPrecios').style.display = 'none';
    }
</script>

<script>
    // IVA general vigente, calculado en el backend (para que se actualice automáticamente si cambia en tipos_iva)
    const IVA_GENERAL_ACTUAL = <?php echo json_encode($avProductos['ivaGeneral'] ?? 21.00); ?>;
    const TIPOS_IVA = <?php echo json_encode($avProductos['tipos_iva'] ?? []); ?>;
    const TODOS_LOS_PRODUCTOS = <?php echo json_encode($avProductos['productos'] ?? []); ?>;
    let componentesPackActivos = [];

    /** 
     * Lógica de gestión de productos (JS integrado por ahora)
     */

    // --- Lógica de cálculo de precios ---
    // El coste ahora es manual o CMP automático de albaranes, no depende de un precio fijo de proveedor.

    function calcularPrecioDesdeMargen() {
        const coste = parseFloat(document.getElementById('prodPrecioCoste').value) || 0;
        const margen = parseFloat(document.getElementById('prodMargen').value) || 0;
        const inputVenta = document.getElementById('prodPrecioVenta');

        if (coste >= 0) {
            const precioVenta = coste * (1 + (margen / 100));
            inputVenta.value = precioVenta.toFixed(2);
        }
    }

    function calcularMargenDesdePrecio() {
        const coste = parseFloat(document.getElementById('prodPrecioCoste').value) || 0;
        const venta = parseFloat(document.getElementById('prodPrecioVenta').value.replace(',', '.')) || 0;
        const inputMargen = document.getElementById('prodMargen');

        if (coste > 0) {
            const margen = ((venta / coste) - 1) * 100;
            inputMargen.value = margen.toFixed(2);
        } else {
            inputMargen.value = '0.00';
        }
    }


    // --- FUNCIONES GLOBALES DE MODALES ---

    function abrirModalProducto(producto = null) {
        const modal = document.getElementById('modalProducto');
        const title = document.getElementById('modalTitle');
        const productForm = document.getElementById('formProducto');

        // Cerrar cualquier otro modal-overlay antes de abrir este
        document.querySelectorAll('.modal-overlay-bg').forEach(m => {
            m.style.display = 'none';
        });

        // Limpiar errores previos
        document.querySelectorAll('.form-error').forEach(el => el.innerText = '');

        const inputCoste = document.getElementById('prodPrecioCoste');
        const inputStock = document.getElementById('prodStock');
        const btnRetirada = document.getElementById('btnRetirarStock');
        const btnTabTarifas = document.getElementById('btnTabModalTarifas');
        const btnHistCoste = document.getElementById('btnHistorialCostes');

        if (producto) {
            title.innerText = <?php echo json_encode(L('prod_modal_title_edit', true)); ?>;
            document.getElementById('prodId').value = producto.id;
            document.getElementById('prodIcono').value = producto.icono || '';
            document.getElementById('prodCodigo').value = producto.codigo || '';
            document.getElementById('prodNombre').value = producto.nombre || '';
            document.getElementById('prodDesc').value = producto.descripcion || '';
            document.getElementById('prodCat').value = producto.categoria || '';
            document.getElementById('prodProveedor').value = producto.id_proveedor || '';
            document.getElementById('prodGarantia').value = producto.meses_garantia || '24';

            const selIva = document.getElementById('prodIvaTipo');
            if (selIva) selIva.value = producto.codigo_iva || 'GENERAL';

            inputCoste.value = parseFloat(producto.precio_coste || 0).toFixed(2);
            document.getElementById('prodPrecioVenta').value = producto.price || producto.precio || '0.00';
            inputStock.value = producto.stock !== undefined ? producto.stock : (producto.stock_actual || '0');
            document.getElementById('prodStockMin').value = producto.stock_minimo || '0';
            document.getElementById('prodMargen').value = producto.margen || '0.00';

            if (parseFloat(producto.margen || 0) <= 0) calcularMargenDesdePrecio();

            // Bloquear edición manual de stock y coste para productos existentes
            inputCoste.readOnly = true;
            inputStock.readOnly = true;
            inputCoste.style.opacity = '0.7';
            inputStock.style.opacity = '0.7';
            inputCoste.classList.add('input-readonly-cmp');
            inputCoste.title = <?php echo json_encode(L('prod_modal_tip_cost_auto', true)); ?>;
            inputStock.title = <?php echo json_encode(L('prod_modal_tip_stock_auto', true)); ?>;

            if (btnRetirada) btnRetirada.classList.remove('d-none');
            if (btnTabTarifas) btnTabTarifas.style.display = 'block';
            if (btnHistCoste) btnHistCoste.style.display = 'inline-block';

            // Preview imagen
            const preview = document.getElementById('imgPreview');
            if (preview) {
                if (producto.icono && producto.icono.startsWith('data:image')) {
                    preview.innerHTML = `<img src="${producto.icono}" style="width:100%; height:100%; object-fit:contain; border-radius:8px;">`;
                } else {
                    preview.innerHTML = `<span class="fs-24">${producto.icono || '<i class="fa-solid fa-image"></i>'}</span>`;
                }
            }

            // Cargar tarifas
            cargarTarifasProducto(producto.id);

        } else {
            title.innerText = <?php echo json_encode(L('prod_modal_title_new', true)); ?>;
            if (productForm && typeof productForm.reset === 'function') {
                productForm.reset();
            }
            document.getElementById('prodId').value = '';
            document.getElementById('prodIcono').value = '';
            document.getElementById('prodIvaTipo').value = 'GENERAL';
            document.getElementById('imgPreview').innerHTML = '<i class="fa-solid fa-image"></i>';

            inputCoste.readOnly = false;
            inputStock.readOnly = false;
            inputCoste.style.opacity = '1';
            inputStock.style.opacity = '1';
            inputCoste.classList.remove('input-readonly-cmp');
            inputCoste.title = <?php echo json_encode(L('prod_modal_tip_margin_calc', true)); ?>;
            inputStock.title = <?php echo json_encode(L('prod_modal_label_stock', true)); ?>;

            if (btnRetirada) btnRetirada.classList.add('d-none');
            if (btnTabTarifas) btnTabTarifas.style.display = 'none';
            if (btnHistCoste) btnHistCoste.style.display = 'none';
        }

        switchModalTab('general', document.querySelector('#modalProducto .tab-btn'));
        modal.style.display = 'flex';
    }

    /* --- LÓGICA MARGEN MASIVO --- */
    let excepcionesMasivas = [];
    let timerBusquedaExcepciones = null;

    function abrirModalMargenMasivo() {
        const modal = document.getElementById('modalMargenMasivo');
        modal.classList.remove('d-none');
        modal.style.display = 'flex';
        document.getElementById('massMargenInput').value = '';
        document.getElementById('margenPreviewBox').style.display = 'none';
        document.getElementById('btnAplicarMargen').disabled = true;
        
        excepcionesMasivas = [];
        actualizarUIExcepciones();
        document.getElementById('buscadorExcepciones').value = '';
        lanzarBusquedaExcepciones('');
    }

    function buscarExcepcionesAlEscribir() {
        if (timerBusquedaExcepciones) clearTimeout(timerBusquedaExcepciones);
        timerBusquedaExcepciones = setTimeout(() => {
            const query = document.getElementById('buscadorExcepciones').value.trim();
            lanzarBusquedaExcepciones(query);
        }, 300);
    }

    async function lanzarBusquedaExcepciones(query) {
        const url = 'api/gestionProducto.php?accion=listar'; 
        try {
            const resp = await fetch(url);
            const r = await resp.json();
            if (r.ok) {
                let html = '';
                const prods = r.productos.filter(p => !p.es_pack && (query === '' || p.nombre.toLowerCase().includes(query.toLowerCase()) || (p.referencia && p.referencia.toLowerCase().includes(query.toLowerCase()))));
                const topProds = prods.slice(0, 30);
                
                if (topProds.length === 0) {
                    html = '<div class="text-center p-20 text-muted fs-12">' + <?php echo json_encode(L('prod_js_no_results', true)); ?> + '</div>';
                } else {
                    topProds.forEach(p => {
                        const isSelected = excepcionesMasivas.some(ex => ex.id == p.id);
                        html += `
                            <div class="d-flex ai-center jc-between p-12 br-8 bg-surface border">
                                <div class="d-flex ai-center gap-12">
                                    <div class="fs-18 text-muted"><i class="${p.icono ? p.icono : 'fa-solid fa-box'}"></i></div>
                                    <div>
                                        <div class="font-bold fs-13 text-ellipsis" style="max-width:280px;">${p.nombre}</div>
                                        <div class="fs-10 text-muted">REF: ${p.referencia || 'S/N'}</div>
                                    </div>
                                </div>
                                <button type="button" class="btn ${isSelected ? 'btn-red' : 'btn-outline'} px-12 py-6 fs-11" onclick="toggleExcepcionMasiva(${p.id}, '${p.nombre.replace(/'/g, "\\'")}', '${(p.referencia||'').replace(/'/g, "\\'")}')">
                                    <i class="fa-solid ${isSelected ? 'fa-minus' : 'fa-plus'}"></i> ${isSelected ? <?php echo json_encode(L('prod_js_remove', true)); ?> : <?php echo json_encode(L('prod_js_add', true)); ?>}
                                </button>
                            </div>
                        `;
                    });
                }
                document.getElementById('listaExcepciones').innerHTML = html;
            }
        } catch(e) {}
    }

    function toggleExcepcionMasiva(id, nombre, ref) {
        const idx = excepcionesMasivas.findIndex(ex => ex.id == id);
        if (idx > -1) {
            excepcionesMasivas.splice(idx, 1);
        } else {
            excepcionesMasivas.push({id, nombre, referencia: ref});
        }
        actualizarUIExcepciones();
        const query = document.getElementById('buscadorExcepciones').value.trim();
        lanzarBusquedaExcepciones(query); 
        previewMargenMasivo();
    }

    function actualizarUIExcepciones() {
        document.getElementById('contadorExcepciones').innerText = `${excepcionesMasivas.length} ` + <?php echo json_encode(L('selected', true)); ?>;
    }

    function cerrarModalMargenMasivo() {
        document.getElementById('modalMargenMasivo').style.display = 'none';
    }

    let timerPreviewMargen = null;

    function previewMargenMasivo() {
        const margen = parseFloat(document.getElementById('massMargenInput').value);
        const btn = document.getElementById('btnAplicarMargen');

        if (isNaN(margen) || margen < 0) {
            btn.disabled = true;
            document.getElementById('margenPreviewBox').style.display = 'none';
            return;
        }

        btn.disabled = false;

        // Debounce para no saturar la API
        if (timerPreviewMargen) clearTimeout(timerPreviewMargen);
        timerPreviewMargen = setTimeout(async () => {
            const categoria = document.getElementById('massCategoriaSelect').value;
            try {
                const resp = await fetch('api/aplicarMargenMasivo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        margen,
                        categoria,
                        excepciones: excepcionesMasivas.map(e => e.id),
                        preview: true
                    })
                });
                const r = await resp.json();
                if (r.ok) {
                    document.getElementById('previewCountOk').innerText = r.actualizados;
                    document.getElementById('previewCountOmit').innerText = r.omitidos;
                    document.getElementById('margenPreviewBox').style.display = 'block';
                }
            } catch (e) {}
        }, 500);
    }

    function confirmarAplicarMargen() {
        const margen = document.getElementById('massMargenInput').value;
        const catLabel = document.getElementById('massCategoriaSelect').options[document.getElementById('massCategoriaSelect').selectedIndex].text;

        showCustomConfirm(
            <?php echo json_encode(L('prod_js_apply_margin_confirm_title', true)); ?>,
            <?php echo json_encode(L('prod_js_apply_margin_confirm_body', true)); ?>.replace('{margen}', margen).replace('{categoria}', catLabel),
            async () => {
                    const btn = document.getElementById('btnAplicarMargen');
                    const oldHtml = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + <?php echo json_encode(L('prod_js_applying', true)); ?>;

                    const categoria = document.getElementById('massCategoriaSelect').value;
                    try {
                        const resp = await fetch('api/aplicarMargenMasivo.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                margen,
                                categoria,
                                excepciones: excepcionesMasivas.map(e => e.id)
                            })
                        });
                        const r = await resp.json();
                        if (r.ok) {
                            showCustomAlert(<?php echo json_encode(L('prod_js_success', true)); ?>, r.mensaje || <?php echo json_encode(L('prod_toast_stock_adj_success', true)); ?>, 'success');
                            location.reload();
                        } else {
                            showCustomAlert(<?php echo json_encode(L('prod_js_error', true)); ?>, r.mensaje || r.error || <?php echo json_encode(L('prod_js_error', true)); ?>, 'error');
                        }
                    } catch (e) {
                        showCustomAlert(<?php echo json_encode(L('prod_js_error', true)); ?>, <?php echo json_encode(L('prod_js_error', true)); ?> + ": " + e.message, 'error');
                    } finally {
                        btn.disabled = false;
                        btn.innerHTML = oldHtml;
                    }
                },
                <?php echo json_encode(L('prod_modal_mass_margin_btn_apply', true)); ?>,
                'danger'
        );
    }

    async function abrirModalHistorialCostes() {
        const id = document.getElementById('prodId').value;
        const nombre = document.getElementById('prodNombre').value;
        if (!id) return;

        const modal = document.getElementById('modalHistorialCostes');
        const title = document.getElementById('costesTitle');
        const body = document.getElementById('costesTableBody');

        title.innerText = <?php echo json_encode(L('prod_js_history_cmp_for', true)); ?> + nombre;
        body.innerHTML = '<tr><td colspan="5" class="text-center p-24 opacity-50"><i class="fa-solid fa-spinner fa-spin"></i> ' + <?php echo json_encode(L('loading', true)); ?> + '</td></tr>';

        modal.classList.remove('d-none');
        modal.style.display = 'flex';

        try {
            const resp = await fetch(`api/obtenerHistorialCostes.php?id=${id}`);
            const data = await resp.json();

            if (data.ok) {
                if (data.historial.length === 0) {
                    body.innerHTML = `<tr><td colspan="5" class="text-center p-48 text-muted">${<?php echo json_encode(L('prod_modal_tab_tarifas_none', true)); ?>}</td></tr>`;
                } else {
                    body.innerHTML = data.historial.map(h => {
                        const fecha = new Date(h.fecha);
                        return `
                            <tr>
                                <td class="pl-20 fs-12">
                                    ${fecha.toLocaleDateString()}<br>
                                    <span class="opacity-50">${fecha.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}</span>
                                </td>
                                <td class="text-center font-bold">${h.cantidad}</td>
                                <td class="text-right font-mono">${parseFloat(h.precio_coste).toFixed(2)} €</td>
                                <td class="text-right font-mono text-muted">${parseFloat(h.cmp_anterior).toFixed(2)} €</td>
                                <td class="text-right pr-20 font-mono font-bold text-accent">${parseFloat(h.cmp_resultante).toFixed(2)} €</td>
                            </tr>
                        `;
                    }).join('');
                }
            } else {
                body.innerHTML = `<tr><td colspan="5" class="text-center p-32 text-red">Error: ${data.error}</td></tr>`;
            }
        } catch (e) {
            body.innerHTML = '<tr><td colspan="5" class="text-center p-32 text-red">Error de red.</td></tr>';
        }
    }

    function cerrarModalHistorialCostes() {
        document.getElementById('modalHistorialCostes').style.display = 'none';
    }

    function resetFormProducto() {
        document.getElementById('prodNombre').value = '';
        document.getElementById('prodCodigo').value = '';
        document.getElementById('prodDesc').value = '';
        document.getElementById('prodCat').selectedIndex = 0;
        document.getElementById('prodProveedor').value = '';
        document.getElementById('prodIvaTipo').value = 'GENERAL';
        document.getElementById('prodPrecioCoste').value = '0.00';
        document.getElementById('prodPrecioVenta').value = '0.00';
        document.getElementById('prodStock').value = '0';
        document.getElementById('prodStockMin').value = '0';
        document.getElementById('prodGarantia').value = '24';
        document.getElementById('prodMargen').value = '0.00';
    }

    function switchModalTab(tabId, btn) {
        // Toggle tabs
        document.querySelectorAll('#modalProducto .modal-tab-content').forEach(c => c.classList.add('d-none'));
        document.getElementById('modalTab' + tabId.charAt(0).toUpperCase() + tabId.slice(1)).classList.remove('d-none');

        // Toggle buttons
        document.querySelectorAll('#modalProducto .tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }

    async function cargarTarifasProducto(idProducto) {
        const tbody = document.getElementById('listaTarifasProductoBody');
        tbody.innerHTML = '<tr><td colspan="5" class="text-center p-20"><i class="fa-solid fa-spinner fa-spin"></i> ' + <?php echo json_encode(L('loading', true)); ?> + '</td></tr>';

        try {
            const resp = await fetch('api/gestionExclusiones.php', {
                method: 'POST',
                body: JSON.stringify({
                    accion: 'listar_aplicables',
                    id_producto: idProducto
                })
            });
            const data = await resp.json();

            if (!data.ok) throw new Error(data.error);

            tbody.innerHTML = '';
            if (data.reglas.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center p-20 text-muted">' + <?php echo json_encode(L('prod_modal_tab_tarifas_none', true)); ?> + '</td></tr>';
                return;
            }

            data.reglas.forEach(r => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="p-8 font-bold">${r.nombre}</td>
                    <td class="p-8 text-center"><span class="px-6 py-2 br-4 fs-10 bg-surface1 text-muted border">${r.tipo_regla === 'tarifa' ? <?php echo json_encode(L('prod_modal_pill_tariff', true)); ?> : <?php echo json_encode(L('prod_modal_pill_promo', true)); ?>}</span></td>
                    <td class="p-8 text-right font-mono ${r.valor > 0 ? 'text-green' : 'text-red'}">${r.valor > 0 ? '+' : ''}${r.valor}${r.tipo === 'percent' ? '%' : '€'}</td>
                    <td class="p-8 text-center text-muted">${r.prioridad}</td>
                    <td class="p-8 text-right">
                        <button type="button" onclick="excluirDeRegla(${idProducto}, ${r.id}, '${r.tipo_regla}')" class="btn-icon text-red" title="Excluir producto de esta regla">
                            <i class="fa-solid fa-ban"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } catch (e) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center p-20 text-red">Error al cargar tarifas: ${e.message}</td></tr>`;
        }
    }

    async function excluirDeRegla(idProducto, idRegla, tipo) {
        if (!confirm(<?php echo json_encode(L('prod_js_confirm_exclude_title', true)); ?>.replace('{tipo}', (tipo === 'tarifa' ? <?php echo json_encode(L('prod_modal_pill_tariff', true)); ?>.toLowerCase() : <?php echo json_encode(L('prod_modal_pill_promo', true)); ?>.toLowerCase())))) return;

        try {
            const resp = await fetch('api/gestionExclusiones.php', {
                method: 'POST',
                body: JSON.stringify({
                    accion: 'excluir',
                    id_producto: idProducto,
                    id_regla: idRegla,
                    tipo: tipo
                })
            });
            const data = await resp.json();
            if (data.ok) {
                if (typeof showToast === 'function') showToast(<?php echo json_encode(L('prod_js_excluded_success', true)); ?>);
                cargarTarifasProducto(idProducto);
            } else {
                showCustomAlert(<?php echo json_encode(L('prod_js_error', true)); ?>, data.error, 'error');
            }
        } catch (e) {
            showCustomAlert(<?php echo json_encode(L('prod_js_error', true)); ?>, <?php echo json_encode(L('prod_js_connection', true)); ?> + ": " + e.message, 'error');
        }
    }

    function cerrarModalProducto() {
        document.getElementById('modalProducto').style.display = 'none';
        document.querySelectorAll('.form-error').forEach(el => el.innerText = '');
    }

    function abrirModalPack(pack = null) {
        const modal = document.getElementById('modalPack');
        const title = document.getElementById('modalPackTitle');
        const packForm = document.getElementById('formPack');

        if (!modal || !title || !packForm) return;

        // Cerrar cualquier otro modal-overlay antes de abrir este
        document.querySelectorAll('.modal-overlay-bg').forEach(m => {
            m.style.display = 'none';
        });

        if (packForm && typeof packForm.reset === 'function') {
            packForm.reset();
        }
        document.querySelectorAll('.form-error').forEach(e => e.textContent = '');
        componentesPackActivos = [];

        if (pack) {
            title.textContent = 'Editar Pack: ' + pack.nombre;
            document.getElementById('packId').value = pack.id;
            document.getElementById('packNombre').value = pack.nombre;
            document.getElementById('packCodigo').value = pack.codigo || '';
            document.getElementById('packPrecioVenta').value = pack.precio;
            document.getElementById('packCat').value = pack.categoria;
            document.getElementById('packIcono').value = pack.icono || '';

            // Cargar componentes guardados
            componentesPackActivos = pack.componentes_pack ? [...pack.componentes_pack] : [];

            // Buscar IVA actual del producto
            let codigoIva = 'GENERAL';
            for (const [key, t] of Object.entries(TIPOS_IVA)) {
                if (parseFloat(t.porcentaje) === parseFloat(pack.iva_aplicado)) {
                    codigoIva = key;
                    break;
                }
            }
            if (document.getElementById('packIvaTipo')) {
                document.getElementById('packIvaTipo').value = codigoIva;
            }

            // Preview imagen
            const preview = document.getElementById('imgPreviewPack');
            if (pack.icono && pack.icono.startsWith('data:image')) {
                preview.innerHTML = `<img src="${pack.icono}" style="width:100%; height:100%; object-fit:contain; border-radius:8px;">`;
            } else {
                preview.innerHTML = `<span class="fs-24">${pack.icono || '<i class="fa-solid fa-boxes-stacked"></i>'}</span>`;
            }
        } else {
            title.textContent = 'Nuevo Pack de Venta';
            document.getElementById('packId').value = '';
            document.getElementById('packIcono').value = '';
            document.getElementById('imgPreviewPack').innerHTML = `<i class="fa-solid fa-boxes-stacked fs-32 opacity-20"></i>`;

            const selIva = document.getElementById('packIvaTipo');
            if (selIva) selIva.value = 'GENERAL';
        }

        renderComponentesPack();
        modal.classList.remove('d-none');
        modal.style.display = 'flex';
    }

    function cerrarModalPack() {
        const modal = document.getElementById('modalPack');
        if (modal) {
            modal.classList.add('d-none');
            modal.style.display = 'none';
        }
    }

    function abrirModalGestionCategorias() {
        const modal = document.getElementById('modalCategorias');
        if (!modal) return;

        // Cerrar cualquier otro modal-overlay antes de abrir este
        document.querySelectorAll('.modal-overlay-bg').forEach(m => {
            m.style.display = 'none';
        });

        modal.classList.remove('d-none');
        modal.style.display = 'flex';
    }

    function cerrarModalCategorias() {
        const modal = document.getElementById('modalCategorias');
        if (modal) modal.style.display = 'none';
        location.reload();
    }

    async function aplicarIvaCategoria(idCategoria) {
        const select = document.getElementById('iva-cat-' + idCategoria);
        const idTipoIva = select.value;

        if (!idTipoIva) {
            showCustomAlert(<?php echo json_encode(L('warning', true)); ?>, <?php echo json_encode(L('prod_modal_cat_option_select', true)); ?>, 'warning');
            return;
        }

        showCustomConfirm(
            <?php echo json_encode(L('prod_js_cat_iva_confirm_title', true)); ?>,
            <?php echo json_encode(L('prod_js_cat_iva_confirm_body', true)); ?>,
            async () => {
                    try {
                        const resp = await fetch('api/gestionCategoria.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                accion: 'asignarIVA',
                                idCategoria: idCategoria,
                                idTipoIva: idTipoIva
                            })
                        });

                        const r = await resp.json();
                        if (r.ok) {
                            showCustomAlert(<?php echo json_encode(L('prod_js_success', true)); ?>, <?php echo json_encode(L('prod_js_cat_iva_success', true)); ?>, 'success');
                        } else {
                            showCustomAlert(<?php echo json_encode(L('prod_js_error', true)); ?>, r.error || <?php echo json_encode(L('prod_js_cat_iva_error', true)); ?>, 'error');
                        }
                    } catch (e) {
                        console.error(e);
                        showCustomAlert(<?php echo json_encode(L('prod_js_error', true)); ?>, <?php echo json_encode(L('prod_js_connection', true)); ?>, 'error');
                    }
                },
                <?php echo json_encode(L('prod_js_cat_iva_apply_btn', true)); ?>,
                'danger'
        );
    }






    /* Lógica de atributos deshabilitada
    function añadirAtributoUI(value = null) {
        ...
    }

    function syncAtributos() {
        ...
    }
    */





    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('prodIcono').value = e.target.result;
                document.getElementById('imgPreview').innerHTML = `<img src="${e.target.result}" style="width:100%; height:100%; object-fit:contain; border-radius:8px;">`;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }



    // Modificamos el guardado para que use el botón directamente ya que no hay un <form> envolviendo todo el modal-body ahora
    document.getElementById('btnGuardarProd').onclick = async (e) => {
        const id = document.getElementById('prodId').value;
        const btn = document.getElementById('btnGuardarProd');
        btn.disabled = true;
        const oldHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + <?php echo json_encode(L('prod_js_saving', true)); ?>;

        // Resolver tipo de IVA seleccionado
        const selIva = document.getElementById('prodIvaTipo');
        const codigoIva = selIva ? selIva.value : 'GENERAL';
        const tipoIva = TIPOS_IVA.find(t => t.codigo === codigoIva);
        const ivaValor = tipoIva ? tipoIva.porcentaje : IVA_GENERAL_ACTUAL;

        const datos = {
            accion: id ? 'editar' : 'añadir',
            id: id,
            icono: document.getElementById('prodIcono').value,
            referencia: document.getElementById('prodCodigo').value,
            nombre: document.getElementById('prodNombre').value,
            descripcion: document.getElementById('prodDesc').value,
            categoria: document.getElementById('prodCat').value,
            iva: ivaValor,
            meses_garantia: document.getElementById('prodGarantia').value,
            precio_venta: document.getElementById('prodPrecioVenta').value,
            stock_minimo: document.getElementById('prodStockMin').value,
            requiere_serial: 0,
            atributos: (document.getElementById('prodAtributos') || {
                value: null
            }).value || null,
            codigo_iva: codigoIva,
            precio_proveedor: 0,
            id_proveedor: document.getElementById('prodProveedor').value || null,
            aplica_re: 0,
            es_pack: 0,
            componentes_pack: null,
            margen: document.getElementById('prodMargen').value || 0
        };

        // El stock y el precio de coste (CMP) solo se envían al añadir un producto nuevo.
        // Al editar, se gestionan mediante Albaranes/Compras para evitar sobrescribir con datos obsoletos del DOM.
        if (!id) {
            datos.precio_coste = document.getElementById('prodPrecioCoste').value;
            datos.stock_actual = document.getElementById('prodStock').value;
        }

        try {
            const resp = await fetch('api/gestionProducto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(datos)
            });
            const r = await resp.json();

            if (r.ok) {
                location.reload();
            } else {
                document.querySelectorAll('.form-error').forEach(el => el.innerText = '');
                if (r.aErrores) {
                    const map = {
                        'referencia': 'err-referencia',
                        'nombre': 'err-nombre',
                        'precio_coste': 'err-precio_coste',
                        'precio_venta': 'err-precio_venta',
                        'stock_actual': 'err-stock_actual',
                        'stock_minimo': 'err-stock_minimo',
                        'meses_garantia': 'err-meses_garantia'
                    };
                    for (const [key, msg] of Object.entries(r.aErrores)) {
                        const errId = map[key] || ('err-' + key);
                        const errEl = document.getElementById(errId);
                        if (errEl && msg) errEl.innerText = msg;
                    }
                    // Volver a la pestaña de errores (precios o general)
                    if (r.aErrores.nombre || r.aErrores.referencia) switchModalTab('general', document.querySelector('#modalProducto .tab-btn'));
                    else switchModalTab('precios', document.querySelectorAll('#modalProducto .tab-btn')[1]);
                } else {
                    showCustomAlert(<?php echo json_encode(L('prod_js_error_save', true)); ?>, r.error, 'error');
                }
            }
        } catch (err) {
            showCustomAlert(<?php echo json_encode(L('prod_js_error', true)); ?>, <?php echo json_encode(L('prod_js_connection', true)); ?>, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = oldHtml;
        }
    };

    async function toggleEstadoProducto(id) {
        try {
            const resp = await fetch('api/gestionProducto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    accion: 'baja',
                    id: id
                })
            });
            const r = await resp.json();
            if (r.ok) {
                const activo = r.activo; // true = activado, false = dado de baja

                // Encontrar la fila por el botón
                const btn = document.querySelector(`button[onclick="toggleEstadoProducto(${id})"]`);
                if (!btn) return;
                const row = btn.closest('tr');

                // Actualizar botón
                btn.className = `btn-icon ${activo ? 'text-red' : 'text-green'}`;
                btn.title = activo ? <?php echo json_encode(L('prod_tip_deactivate', true)); ?> : <?php echo json_encode(L('prod_tip_activate', true)); ?>;
                btn.querySelector('i').className = `fa-solid fa-${activo ? 'arrow-down' : 'arrow-up'}`;

                // Actualizar data-activo (para los filtros)
                row.dataset.activo = activo ? '1' : '0';

                // Actualizar pastilla de estado
                const pill = row.querySelector('.status-pill');
                if (pill) {
                    pill.className = activo ? 'status-pill status-active' : 'status-pill status-inactive';
                    pill.innerHTML = activo ?
                        '<i class="fa-solid fa-circle-check"></i> ' + <?php echo json_encode(L('prod_pill_active', true)); ?> :
                        '<i class="fa-solid fa-circle-xmark"></i> ' + <?php echo json_encode(L('prod_pill_inactive', true)); ?>;
                }

                showNotification(
                    <?php echo json_encode(L('prod_js_status_change_success', true)); ?>.replace('{status}', activo ? <?php echo json_encode(L('prod_js_status_active', true)); ?> : <?php echo json_encode(L('prod_js_status_inactive', true)); ?>),
                    activo ? 'success' : 'info'
                );
            } else {
                showNotification("<i class='fa-solid fa-circle-xmark'></i> " + (r.error || <?php echo json_encode(L('prod_js_status_change_error', true)); ?>), 'error');
            }
        } catch (err) {
            console.error(err);
            showNotification("<i class='fa-solid fa-circle-xmark'></i> " + <?php echo json_encode(L('prod_js_status_change_error', true)); ?>, 'error');
        }
    }

    function eliminarProducto(id, nombre) {
        if (!id) return;
        showCustomConfirm(
            <?php echo json_encode(L('prod_js_delete_confirm_title', true)); ?>,
            <?php echo json_encode(L('prod_js_delete_confirm_body', true)); ?>.replace('{nombre}', nombre),
            async () => {
                    try {
                        const resp = await fetch('api/gestionProducto.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                accion: 'eliminar',
                                id: id
                            }),
                        });
                        const r = await resp.json();
                        if (r.ok) {
                            const targetRow = document.querySelector(`.product-row[data-id="${id}"]`) ||
                                Array.from(document.querySelectorAll('.product-row')).find(row => row.innerHTML.includes(`eliminarProducto(${id},`));

                            if (targetRow) {
                                targetRow.style.transition = 'opacity 0.3s, transform 0.3s';
                                targetRow.style.opacity = '0';
                                targetRow.style.transform = 'translateX(12px)';
                                setTimeout(() => targetRow.remove(), 300);
                            }
                            showNotification('<i class="fa-solid fa-circle-check"></i> ' + <?php echo json_encode(L('prod_js_delete_success', true)); ?>, 'success');
                        } else {
                            showNotification('<i class="fa-solid fa-circle-xmark"></i> ' + <?php echo json_encode(L('prod_js_error', true)); ?> + ': ' + (r.error || <?php echo json_encode(L('prod_js_delete_error', true)); ?>), 'error');
                        }
                    } catch (e) {
                        console.error(e);
                        showNotification('<i class="fa-solid fa-circle-xmark"></i> ' + <?php echo json_encode(L('prod_js_connection', true)); ?>, 'error');
                    }
                },
                <?php echo json_encode(L('modal_delete', true)); ?>,
                'danger'
        );
    }

    // Lógica de filtrado y ordenación
    function applyFilters() {
        const term = document.getElementById('prodSearch').value.toLowerCase().trim();
        const cat = document.getElementById('filterCat').value;
        const estado = document.getElementById('filterEstado').value;
        const priceMin = parseFloat(document.getElementById('filterPriceMin').value) || 0;
        const priceMax = parseFloat(document.getElementById('filterPriceMax').value) || 999999;

        const rows = document.querySelectorAll('.product-row');
        let hasResults = false;

        rows.forEach(row => {
            const nombre = row.dataset.nombre;
            const codigo = row.dataset.codigo;
            const categoria = row.dataset.categoria;
            const activo = row.dataset.activo;
            const precio = parseFloat(row.dataset.precio);

            const matchesTerm = !term || nombre.includes(term) || codigo.includes(term);
            const matchesCat = cat === 'all' || categoria === cat;
            const matchesEstado = estado === 'all' || activo === estado;
            const matchesPrice = precio >= priceMin && precio <= priceMax;

            if (matchesTerm && matchesCat && matchesEstado && matchesPrice) {
                row.style.display = '';
                hasResults = true;
            } else {
                row.style.display = 'none';
            }
        });

        document.getElementById('noResults').classList.toggle('d-none', hasResults);
    }

    document.getElementById('filterPriceMin').addEventListener('input', applyFilters);
    document.getElementById('filterPriceMax').addEventListener('input', applyFilters);

    function applySort() {
        const order = document.getElementById('sortOrder').value;
        if (order === 'none') return;

        const tbody = document.getElementById('productsTableBody');
        const rows = Array.from(tbody.querySelectorAll('.product-row'));

        rows.sort((a, b) => {
            let valA, valB;
            if (order.startsWith('price')) {
                valA = parseFloat(a.dataset.precio);
                valB = parseFloat(b.dataset.precio);
            } else if (order.startsWith('stock')) {
                valA = parseInt(a.dataset.stock);
                valB = parseInt(b.dataset.stock);
            }

            return order.endsWith('asc') ? valA - valB : valB - valA;
        });

        // Devolvemos el noResults al final
        const noRes = document.getElementById('noResults');
        rows.forEach(row => tbody.appendChild(row));
        tbody.appendChild(noRes);
    }





    async function cargarNS(idProd) {
        const listDiv = document.getElementById('nsList');
        const tableBody = document.getElementById('listaNSTablaBody');
        listDiv.innerHTML = <?php echo json_encode('<i class="fa-solid fa-spinner fa-spin"></i> ' . L('loading', true)); ?>;
        tableBody.innerHTML = '';

        try {
            // ── CORRECCIÓN: añadir Content-Type ──
            const resp = await fetch('api/gestionNS.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    accion: 'listar',
                    id_producto: idProd
                })
            });
            const r = await resp.json();
            if (r.ok) {
                if (r.lista.length === 0) {
                    listDiv.innerText = <?php echo json_encode(L('prod_js_ns_empty', true)); ?>;
                    tableBody.innerHTML = '<tr><td colspan="3" class="text-center p-16 text-muted">' + <?php echo json_encode(L('prod_js_ns_empty', true)); ?> + '</td></tr>';
                } else {
                    listDiv.innerText = <?php echo json_encode(L('prod_js_ns_count', true)); ?>.replace('{count}', r.lista.length);
                    r.lista.forEach(item => {
                        const row = document.createElement('tr');
                        const color = item.estado === 'disponible' ? 'text-success' : 'text-danger';
                        row.innerHTML = `
                        <td class="p-8">${item.numero_serie}</td>
                        <td class="text-center p-8"><span class="${color} font-bold">${item.estado}</span></td>
                        <td class="text-center p-8">
                            <button onclick="eliminarNS(${item.id})" class="btn-icon p-4" title="Eliminar"><i class="fa-solid fa-trash text-danger"></i></button>
                        </td>
                    `;
                        tableBody.appendChild(row);
                    });
                }
            }
        } catch (err) {
            console.error(err);
        }
    }

    async function añadirNS() {
        const idProd = document.getElementById('prodId').value;
        const ns = document.getElementById('nuevoNS').value;
        if (!ns) return;

        try {
            // ── CORRECCIÓN: añadir Content-Type ──
            const resp = await fetch('api/gestionNS.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    accion: 'añadir',
                    id_producto: idProd,
                    numero_serie: ns
                })
            });
            const r = await resp.json();
            if (r.ok) {
                document.getElementById('nuevoNS').value = '';
                cargarNS(idProd);
            } else {
                alert(<?php echo json_encode(L('prod_js_error', true)); ?> + ": " + (r.error || <?php echo json_encode(L('error_unknown', true)); ?>));
            }
        } catch (err) {
            console.error(err);
        }
    }

    async function eliminarNS(id) {
        if (!id) return;
        showCustomConfirm(
            <?php echo json_encode(L('prod_js_cat_delete_confirm_title', true)); ?>,
            <?php echo json_encode(L('prod_js_cat_delete_confirm_body', true)); ?>.replace('{nombre}', ''),
            async () => {
                    try {
                        const resp = await fetch('api/gestionNS.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                accion: 'eliminar',
                                id: id
                            })
                        });
                        const r = await resp.json();
                        if (r.ok) {
                            cargarNS(document.getElementById('prodId').value);
                        } else {
                            showCustomAlert(<?php echo json_encode(L('prod_js_error', true)); ?>, r.error || <?php echo json_encode(L('prod_js_could_not_delete', true)); ?>, 'error');
                        }
                    } catch (err) {
                        console.error(err);
                        showCustomAlert(<?php echo json_encode(L('prod_js_error', true)); ?>, <?php echo json_encode(L('prod_js_connection', true)); ?>, 'error');
                    }
                },
                <?php echo json_encode(L('modal_delete', true)); ?>,
                'danger'
        );
    }

    // --- TABS LOGIC ---
    function switchTab(tabId) {
        // Pestañas
        const btnProd = document.getElementById('btnTabProductos');
        const btnPacks = document.getElementById('btnTabPacks');
        const contentProd = document.getElementById('tabContentProductos');
        const contentPacks = document.getElementById('tabContentPacks');

        // Botones de acción y filtros
        const btnNuevoProd = document.getElementById('btnNuevoProducto');
        const btnNuevoPack = document.getElementById('btnNuevoPack');
        const filtersPrecios = document.getElementById('filtersPrecios');

        if (tabId === 'productos') {
            btnProd.classList.add('active');
            btnPacks.classList.remove('active');

            contentProd.classList.add('active');
            contentProd.classList.remove('d-none');
            contentPacks.classList.add('d-none');
            contentPacks.classList.remove('active');

            btnNuevoProd.classList.remove('d-none');
            btnNuevoPack.classList.add('d-none');
            filtersPrecios.classList.remove('d-none');
        } else {
            btnPacks.classList.add('active');
            btnProd.classList.remove('active');

            contentPacks.classList.add('active');
            contentPacks.classList.remove('d-none');
            contentProd.classList.add('d-none');
            contentProd.classList.remove('active');

            btnNuevoPack.classList.remove('d-none');
            btnNuevoProd.classList.add('d-none');
            filtersPrecios.classList.add('d-none');
        }
    }

    // --- PACKS LOGIC ---




    function previewImagePack(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('packIcono').value = e.target.result;
                document.getElementById('imgPreviewPack').innerHTML = `<img src="${e.target.result}" style="width:100%; height:100%; object-fit:contain; border-radius:8px;">`;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }


    function buscarProductoParaPack(query) {
        const resCont = document.getElementById('packSearchResults');
        query = query.trim().toLowerCase();
        if (query.length < 2) {
            resCont.style.display = 'none';
            return;
        }

        const idEnEdicion = parseInt(document.getElementById('packId').value) || 0;
        const yaAñadidos = componentesPackActivos.map(c => parseInt(c.id_producto || c.id));

        const filtrados = TODOS_LOS_PRODUCTOS.filter(p => {
            if (p.id === idEnEdicion) return false; // Not itself
            if (p.es_pack) return false; // Pack of packs not supported yet
            if (yaAñadidos.includes(p.id)) return false; // Already added

            return p.nombre.toLowerCase().includes(query) || (p.codigo && p.codigo.toLowerCase().includes(query));
        }).slice(0, 8); // top 8

        resCont.innerHTML = '';
        if (filtrados.length === 0) {
            resCont.innerHTML = '<div class="p-16 text-muted fs-12 text-center"><i class="fa-solid fa-circle-info mr-8"></i> ' + <?php echo json_encode(L('prod_js_no_results', true)); ?> + '</div>';
        } else {
            filtrados.forEach(p => {
                const div = document.createElement('div');
                div.className = 'search-result-item';
                div.innerHTML = `
                    <div class="d-flex ai-center gap-12 full-width">
                        <div class="result-icon"><i class="fa-solid fa-box fs-14"></i></div>
                        <div class="flex-1 overflow-hidden">
                            <div class="result-title text-ellipsis">${p.nombre}</div>
                            <div class="result-meta font-mono">${p.codigo || 'S/R'}</div>
                        </div>
                        <div class="result-price">${(parseFloat(p.precio)).toFixed(2)}€</div>
                    </div>
                `;
                div.onclick = () => {
                    añadirComponente(p);
                    document.getElementById('packSearchInput').value = '';
                    resCont.style.display = 'none';
                };
                resCont.appendChild(div);
            });
        }
        resCont.style.display = 'block';
    }

    // Hide search results when click outside
    document.addEventListener('click', (e) => {
        if (e.target.id !== 'packSearchInput') {
            const sr = document.getElementById('packSearchResults');
            if (sr) sr.style.display = 'none';
        }
    });

    function añadirComponente(prod) {
        componentesPackActivos.push({
            id_producto: prod.id,
            id: prod.id, // For compatibility
            nombre: prod.nombre,
            referencia: prod.codigo,
            cantidad: 1
        });
        renderComponentesPack();
    }

    function removerComponentePack(id) {
        componentesPackActivos = componentesPackActivos.filter(c => c.id_producto != id && c.id != id);
        renderComponentesPack();
    }

    function actualizarCantidadComponente(id, input) {
        let val = parseInt(input.value) || 1;
        if (val < 1) val = 1;
        input.value = val;

        const c = componentesPackActivos.find(c => c.id_producto == id || c.id == id);
        if (c) c.cantidad = val;
    }

    function renderComponentesPack() {
        const body = document.getElementById('listaComponentesBody');
        body.innerHTML = '';

        if (componentesPackActivos.length === 0) {
            body.innerHTML = `
                <tr>
                    <td colspan="4" class="p-32 text-center text-muted">
                        <div class="opacity-40 mb-12"><i class="fa-solid fa-boxes-stacked fs-32"></i></div>
                        <div class="fs-13 font-bold">${<?php echo json_encode(L('prod_no_components', true)); ?>}</div>
                        <div class="fs-11">${<?php echo json_encode(L('prod_modal_pack_components_desc', true)); ?>}</div>
                    </td>
                </tr>
            `;
            return;
        }

        componentesPackActivos.forEach(c => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="p-8 text-muted font-mono">${c.referencia || ''}</td>
                <td class="p-8 font-bold">${c.nombre || ''}</td>
                <td class="p-8 text-center" style="width: 80px;">
                    <input type="number" min="1" class="form-input text-center p-4 fs-12 full-width input-qty-mini" value="${c.cantidad}" onchange="actualizarCantidadComponente(${c.id_producto || c.id}, this)">
                </td>
                <td class="p-8 text-center">
                    <button type="button" class="btn-icon text-red" onclick="removerComponentePack(${c.id_producto || c.id})">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </td>
            `;
            body.appendChild(tr);
        });
    }

    document.getElementById('prodSearch').addEventListener('input', applyFilters);
    document.getElementById('filterCat').addEventListener('change', applyFilters);
    document.getElementById('filterEstado').addEventListener('change', applyFilters);

    // Guardar Pack Form Submit Listener
    document.getElementById('formPack').addEventListener('submit', async (e) => {
        e.preventDefault();

        if (componentesPackActivos.length === 0) {
            alert(<?php echo json_encode(L('prod_js_pack_min_components', true)); ?>);
            return;
        }

        const btn = document.getElementById('btnGuardarPack');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + <?php echo json_encode(L('prod_js_saving', true)); ?>;

        const id = document.getElementById('packId').value || null;
        const codIva = document.getElementById('packIvaTipo').value;
        const listaIvas = Array.isArray(TIPOS_IVA) ? TIPOS_IVA : Object.values(TIPOS_IVA);
        const tipoIva = listaIvas.find(t => t.codigo === codIva);
        const ivaPerc = tipoIva ? tipoIva.porcentaje : 21.00;

        const payload = {
            accion: id ? 'editar' : 'añadir',
            id: id,
            nombre: document.getElementById('packNombre').value,
            referencia: document.getElementById('packCodigo').value.trim() || null,
            icono: document.getElementById('packIcono').value || '',
            categoria: document.getElementById('packCat').value,
            precio_coste: 0,
            precio_venta: document.getElementById('packPrecioVenta').value,
            iva: ivaPerc,
            codigo_iva: codIva,
            stock_actual: 0,
            stock_minimo: 0,
            meses_garantia: 24,
            descripcion: <?php echo json_encode(L('prod_tab_standard', true)); ?>,
            es_pack: 1,
            componentes_pack: componentesPackActivos
        };

        try {
            const resp = await fetch('api/gestionProducto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const data = await resp.json();

            if (data.ok) {
                cerrarModalPack();
                location.reload();
            } else {
                let msg = data.error || <?php echo json_encode(L('error_unknown', true)); ?>;
                if (data.aErrores) {
                    const errorList = Object.entries(data.aErrores)
                        .filter(([k, v]) => v !== null && v !== '')
                        .map(([k, v]) => `- ${k}: ${v}`)
                        .join('\n');
                    if (errorList) msg += '\n\nDetalles:\n' + errorList;
                }
                alert(<?php echo json_encode(L('prod_js_error_save', true)); ?> + ":\n" + msg);
            }
        } catch (err) {
            alert(<?php echo json_encode(L('prod_js_connection', true)); ?>);
        } finally {
            btn.disabled = false;
            btn.innerHTML = <?php echo json_encode(L('prod_modal_btn_save_pack', true)); ?>;
        }
    });

    // ── IMPORT / EXPORT ────────────────────────────────────────────────────────
    function toggleExportDropdown() {
        document.getElementById('exportDropdown').classList.toggle('open');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const wrap = document.getElementById('exportDropdownWrap');
        if (wrap && !wrap.contains(e.target)) {
            document.getElementById('exportDropdown').classList.remove('open');
        }
    });

    async function importarProductos(input) {
        if (!input.files || !input.files[0]) return;
        const file = input.files[0];
        const ext = file.name.split('.').pop().toLowerCase();

        if (!['csv', 'json'].includes(ext)) {
            showNotification('<i class="fa-solid fa-circle-xmark"></i> ' + <?php echo json_encode(L('prod_js_import_unsupported', true)); ?>, 'error');
            input.value = '';
            return;
        }

        const formData = new FormData();
        formData.append('file', file);

        showNotification('<i class="fa-solid fa-spinner fa-spin"></i> ' + <?php echo json_encode(L('prod_js_importing', true)); ?>, 'info');

        try {
            const resp = await fetch('api/importarProductos.php', {
                method: 'POST',
                body: formData
            });
            const r = await resp.json();
            if (r.ok) {
                const {
                    creados,
                    actualizados,
                    errores
                } = r.stats;
                showNotification(
                    <?php echo json_encode(L('prod_js_import_success', true)); ?>.replace('{creados}', creados).replace('{actualizados}', actualizados) + (errores > 0 ? `, <span class="text-red">${errores} errores</span>` : ''),
                    'success'
                );
                setTimeout(() => location.reload(), 2500);
            } else {
                showNotification('<i class="fa-solid fa-circle-xmark"></i> ' + <?php echo json_encode(L('prod_js_error', true)); ?> + ': ' + (r.error || <?php echo json_encode(L('error_unknown', true)); ?>), 'error');
            }
        } catch (err) {
            showNotification('<i class="fa-solid fa-circle-xmark"></i> ' + <?php echo json_encode(L('prod_js_connection', true)); ?>, 'error');
        }

        input.value = '';
    }

    // Mini-toast local para esta página (puede no tener el global de main.js)
    function showNotification(html, type = 'info') {
        let notif = document.getElementById('ie-notif');
        if (!notif) {
            notif = document.createElement('div');
            notif.id = 'ie-notif';
            document.body.appendChild(notif);
        }
        const colors = {
            info: '#1a2fbf',
            success: '#0f8060',
            error: '#c0392b'
        };
        notif.style.cssText = `position:fixed;bottom:24px;right:24px;background:${colors[type]};color:white;padding:14px 20px;border-radius:12px;font-size:13px;font-weight:600;z-index:99999;box-shadow:0 4px 20px rgba(0,0,0,0.2);transition:opacity 0.3s;max-width:400px;line-height:1.4;`;
        notif.innerHTML = html;
        notif.style.opacity = '1';
        clearTimeout(notif._timeout);
        notif._timeout = setTimeout(() => {
            notif.style.opacity = '0';
        }, 3500);
    }

    // --- GESTIÓN DE CATEGORÍAS ---




    async function añadirCategoria() {
        const nombre = document.getElementById('newCatNombre').value.trim();
        const codigo = document.getElementById('newCatCodigo').value.trim();

        if (!nombre || !codigo) {
            showCustomAlert(<?php echo json_encode(L('prod_js_cat_add_error', true)); ?>, <?php echo json_encode(L('prod_js_cat_add_error_body', true)); ?>, 'warning');
            return;
        }

        try {
            const resp = await fetch('api/gestionCategoria.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    accion: 'añadir',
                    nombre,
                    codigo
                })
            });
            const r = await resp.json();
            if (r.ok) {
                document.getElementById('newCatNombre').value = '';
                document.getElementById('newCatCodigo').value = '';
                location.reload();
            } else {
                showCustomAlert(<?php echo json_encode(L('prod_js_error', true)); ?>, r.error || <?php echo json_encode(L('prod_js_delete_error', true)); ?>, 'error');
            }
        } catch (e) {
            console.error(e);
        }
    }

    async function eliminarCategoria(id, nombre) {
        showCustomConfirm(
            <?php echo json_encode(L('prod_js_cat_delete_confirm_title', true)); ?>,
            <?php echo json_encode(L('prod_js_cat_delete_confirm_body', true)); ?>.replace('{nombre}', nombre),
            async () => {
                    try {
                        const resp = await fetch('api/gestionCategoria.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                accion: 'eliminar',
                                id
                            })
                        });
                        const r = await resp.json();
                        if (r.ok) {
                            location.reload();
                        } else {
                            showCustomAlert(<?php echo json_encode(L('prod_js_error', true)); ?>, r.error || <?php echo json_encode(L('prod_js_delete_error', true)); ?>, 'error');
                        }
                    } catch (e) {
                        console.error(e);
                        showCustomAlert(<?php echo json_encode(L('prod_js_error', true)); ?>, <?php echo json_encode(L('prod_js_connection', true)); ?>, 'error');
                    }
                },
                <?php echo json_encode(L('prod_js_cat_delete_btn', true)); ?>,
                'danger'
        );
    }

    // --- HISTORIAL DE STOCK ---
    async function abrirModalHistorial(id, nombre) {
        const modal = document.getElementById('modalHistorialStock');
        const title = document.getElementById('historialTitle');
        const subtitle = document.getElementById('historialSubtitle');
        const body = document.getElementById('historialTableBody');

        title.innerText = <?php echo json_encode(L('prod_js_history_for', true)); ?> + nombre;
         subtitle.innerText = <?php echo json_encode(L('prod_js_history_loading', true)); ?>;
        body.innerHTML = '<tr><td colspan="5" class="text-center p-24 opacity-50">' + <?php echo json_encode(L('prod_js_searching', true)); ?> + '</td></tr>';

        modal.classList.remove('d-none');
        modal.style.display = 'flex';

        try {
            const resp = await fetch(`api/obtenerHistorialStock.php?id=${id}`);
            const data = await resp.json();

            if (data.ok) {
                subtitle.innerText = `${data.historial.length} productos`;
                if (data.historial.length === 0) {
                    body.innerHTML = `
                        <tr>
                            <td colspan="5">
                                <div class="empty-log-container">
                                    <div class="empty-log-icon"><i class="fa-solid fa-ghost"></i></div>
                                    <div class="fs-18 font-bold mb-8" style="color: var(--text);">${<?php echo json_encode(L('prod_hist_no_changes', true)); ?>}</div>
                                    <div class="fs-14 opacity-60">${<?php echo json_encode(L('prod_hist_empty_sub', true)); ?>}</div>
                                </div>
                            </td>
                        </tr>
                    `;
                } else {
                    body.innerHTML = data.historial.map(m => {
                        const translationMap = {
                            'inicial': <?php echo json_encode(L('prod_js_status_inicial', true)); ?>,
                            'compra': <?php echo json_encode(L('prod_js_status_compra', true)); ?>,
                            'venta': <?php echo json_encode(L('prod_js_status_venta', true)); ?>,
                            'devolucion': <?php echo json_encode(L('prod_js_status_devolucion', true)); ?>,
                            'ajuste': <?php echo json_encode(L('prod_js_status_ajuste', true)); ?>,
                            'retirada': <?php echo json_encode(L('prod_js_status_ajuste', true)); ?>
                        };
                        const translatedTipo = translationMap[m.tipo_movimiento.toLowerCase()] || m.tipo_movimiento;
                        
                        return `
                        <tr class="hover-bg-surface2 transition">
                            <td class="font-mono fs-12">
                                <span class="text-muted">${new Date(m.fecha).toLocaleDateString()}</span><br>
                                <span class="font-bold">${new Date(m.fecha).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                            </td>
                            <td>
                                <div class="d-flex ai-center gap-10">
                                    <div class="p-8 br-8 ${getTipoMovClase(m.tipo_movimiento)} d-flex ai-center jc-center" style="width:32px; height:32px; flex-shrink:0;">
                                        <i class="fa-solid ${getTipoMovIcono(m.tipo_movimiento)} fs-13"></i>
                                    </div>
                                    <span class="tt-uppercase font-bold fs-10" style="letter-spacing: 0.05em;">${translatedTipo}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="p-6-10 br-10 font-mono font-bold fs-14 ${m.cantidad > 0 ? 'bg-green-soft text-green' : 'text-red bg-red-soft'}">
                                    ${m.cantidad > 0 ? '+' : ''}${m.cantidad}
                                </div>
                            </td>
                            <td>
                                <div class="d-flex ai-center gap-10">
                                    <div class="bg-surface2 br-circle d-flex ai-center jc-center fs-10 font-bold" style="width:28px; height:28px; border:1px solid var(--border); flex-shrink:0;">
                                        ${(m.nombre_usuario || 'S').charAt(0).toUpperCase()}
                                    </div>
                                    <span class="opacity-90 fs-12">${m.nombre_usuario || <?php echo json_encode(L('prod_hist_system', true)); ?>}</span>
                                </div>
                            </td>
                            <td class="fs-12 italic opacity-70">${m.notas || '<span class="opacity-30"><?php echo json_encode(L('prod_hist_no_reason', true)); ?></span>'}</td>
                        </tr>
                    `;
                    }).join('');
                }
            } else {
                body.innerHTML = `<tr><td colspan="5" class="text-center p-48 text-red font-bold">Error: ${data.error}</td></tr>`;
            }
        } catch (e) {
            body.innerHTML = '<tr><td colspan="5" class="text-center p-24 text-red">' + <?php echo json_encode(L('prod_js_connection', true)); ?> + '</td></tr>';
        }
    }

    function getTipoMovClase(tipo) {
        switch (tipo) {
            case 'inicial':
                return 'bg-accent-soft text-accent';
            case 'compra':
                return 'bg-green-soft text-green';
            case 'venta':
                return 'bg-red-soft text-red';
            case 'devolucion':
                return 'bg-yellow-soft text-yellow';
            case 'ajuste':
                return 'bg-surface2 text-muted';
            default:
                return 'bg-surface2 text-muted';
        }
    }

    function getTipoMovIcono(tipo) {
        switch (tipo) {
            case 'inicial':
                return 'fa-star';
            case 'compra':
                return 'fa-truck-ramp-box';
            case 'venta':
                return 'fa-hand-holding-dollar';
            case 'devolucion':
                return 'fa-rotate-left';
            case 'ajuste':
                return 'fa-wrench';
            default:
                return 'fa-circle-info';
        }
    }

    function cerrarModalHistorial() {
        document.getElementById('modalHistorialStock').style.display = 'none';
    }

    function generarPropuestaPedido() {
        showCustomConfirm(
            <?php echo json_encode(L('prod_js_order_prop_title', true)); ?>,
            <?php echo json_encode(L('prod_js_order_prop_body', true)); ?>,
            () => {
                const btn = document.querySelector('button[onclick="generarPropuestaPedido()"]');
                const old = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + <?php echo json_encode(L('prod_js_applying', true)); ?>;

                location.href = 'index.php?irPropuestaPedido';
            },
            <?php echo json_encode(L('prod_js_order_prop_btn', true)); ?>,
            'info'
        );
    }

    // --- PEDIDO AUTOMÁTICO ---
    async function generarPedidoAutomatico() {

        showNotification("<i class='fa-solid fa-spinner fa-spin'></i> Analizando stock...", 'info');

        try {
            const resp = await fetch('api/generarPedidoAuto.php');
            const data = await resp.json();

            if (data.ok) {
                if (data.datos_pedido && data.datos_pedido.length > 0) {
                    // Guardar datos en sessionStorage para recuperarlos en vCompras
                    sessionStorage.setItem('tpv_pedido_auto_data', JSON.stringify(data.datos_pedido));

                    let tienePrecioCero = data.datos_pedido.some(prov => prov.productos.some(p => p.precio_coste_neto <= 0));
                    let msg = `<i class='fa-solid fa-circle-check'></i> ${<?php echo json_encode(L('prod_toast_order_found_prefix', true)); ?>} ${data.datos_pedido.length} ${<?php echo json_encode(L('prod_toast_order_found_suffix', true)); ?>}`;

                    if (tienePrecioCero) {
                        msg += `<br><small style='color:#fbbf24'><i class='fa-solid fa-triangle-exclamation'></i> ${<?php echo json_encode(L('prod_toast_warn_zero_cost', true)); ?>}</small>`;
                    }

                    showNotification(msg, 'success');

                    setTimeout(() => {
                        location.href = 'index.php?irCompras=1&pedido_auto=1';
                    }, 2000);
                } else {
                    let msg = "<i class='fa-solid fa-circle-info'></i> " + <?php echo json_encode(L('prod_toast_no_low_stock', true)); ?>;
                    showNotification(msg, 'info');
                }
            } else {
                showNotification("<i class='fa-solid fa-circle-xmark'></i> " + <?php echo json_encode(L('prod_js_error', true)); ?> + ": " + data.error, 'error');
            }
        } catch (e) {
            showNotification("<i class='fa-solid fa-circle-xmark'></i> " + <?php echo json_encode(L('prod_js_connection', true)); ?>, 'error');
        }
    }

    // Actualizar applyFilters para el filtro Bajo Stock
    const applyFiltersOriginal = window.applyFilters;
    window.applyFilters = function() {
        const query = document.getElementById('prodSearch').value.toLowerCase();
        const cat = document.getElementById('filterCat').value;
        const estado = document.getElementById('filterEstado').value;
        const pMin = parseFloat(document.getElementById('filterPriceMin').value) || 0;
        const pMax = parseFloat(document.getElementById('filterPriceMax').value) || 9999999;

        const rows = document.querySelectorAll('.product-row');
        let visibles = 0;

        rows.forEach(row => {
            const nombre = row.dataset.nombre || '';
            const codigo = row.dataset.codigo || '';
            const categoria = row.dataset.categoria || '';
            const precio = parseFloat(row.dataset.price || row.dataset.precio) || 0;
            const activo = row.dataset.activo || '1';
            const stock = parseInt(row.dataset.stock) || 0;
            const stockMin = parseInt(row.dataset.stockMinimo) || 0;

            let match = true;

            if (query && !nombre.includes(query) && !codigo.includes(query)) match = false;
            if (cat !== 'all' && categoria !== cat) match = false;

            if (estado === 'bajo_stock') {
                if (stock > stockMin || activo !== '1') match = false;
            } else if (estado !== 'all' && activo !== estado) match = false;

            if (precio < pMin || precio > pMax) match = false;

            row.style.display = match ? 'table-row' : 'none';
            if (match) visibles++;
        });

        // Toggle empty states
        const noRes = document.getElementById('noResults');
        if (noRes) noRes.classList.toggle('d-none', visibles > 0 || document.getElementById('btnTabPacks').classList.contains('active'));
    };

    async function retirarStockManual() {
        const id = document.getElementById('prodId').value;
        const nombre = document.getElementById('prodNombre').value;
        const stockActual = parseInt(document.getElementById('prodStock').value) || 0;

        if (!id) return;

        showCustomPrompt(
            <?php echo json_encode(L('prod_js_retira_title', true)); ?>.replace('{nombre}', nombre),
            <?php echo json_encode(L('prod_js_retira_body', true)); ?>.replace('{stock}', stockActual),
            async (cantidad) => {
                    const cant = parseInt(cantidad);
                    if (isNaN(cant) || cant <= 0) {
                        return showCustomAlert(<?php echo json_encode(L('prod_js_error', true)); ?>, <?php echo json_encode(L('prod_js_qty_error', true)); ?>, 'error');
                    }

                    if (cant > stockActual) {
                        showCustomConfirm(
                            <?php echo json_encode(L('prod_js_stock_neg_title', true)); ?>,
                            <?php echo json_encode(L('prod_js_stock_neg_body', true)); ?>.replace('{cant}', cant).replace('{stock}', stockActual).replace('{res}', stockActual - cant),
                            () => solicitarMotivoRetirada(id, cant),
                            <?php echo json_encode(L('prod_js_stock_neg_btn', true)); ?>,
                            'warning'
                        );
                    } else {
                        solicitarMotivoRetirada(id, cant);
                    }
                },
                '',
                'Cantidad (ej: 2)'
        );
    }

    function solicitarMotivoRetirada(id, cant) {
        showCustomPrompt(
            <?php echo json_encode(L('prod_js_reason_title', true)); ?>,
            <?php echo json_encode(L('prod_js_reason_body', true)); ?>,
            async (motivo) => {
                    if (!motivo || motivo.trim() === '') {
                        return showCustomAlert(<?php echo json_encode(L('prod_js_error', true)); ?>, <?php echo json_encode(L('prod_js_reason_error', true)); ?>, 'error');
                    }

                    try {
                        const resp = await fetch('api/gestionProducto.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                accion: 'retirada_stock',
                                id: id,
                                cantidad: cant,
                                motivo: motivo
                            })
                        });
                        const r = await resp.json();
                        if (r.ok) {
                            showCustomAlert(<?php echo json_encode(L('prod_js_success', true)); ?>, <?php echo json_encode(L('prod_toast_stock_adj_success', true)); ?>, 'success');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showCustomAlert('Error', r.error || 'No se pudo registrar el ajuste', 'error');
                        }
                    } catch (e) {
                        showCustomAlert(<?php echo json_encode(L('prod_js_error', true)); ?>, <?php echo json_encode(L('prod_js_connection', true)); ?>, 'error');
                    }
                },
                '',
                'Motivo'
        );
    }
</script>

<!-- MODAL MARGEN MASIVO -->
<div id="modalMargenMasivo" class="modal-overlay-bg" style="display:none;">
    <div class="modal-content" style="max-width: 550px; border-radius: 20px;">
        <div class="modal-header">
            <h2><?php echo L('prod_modal_mass_margin_title'); ?></h2>
            <button onclick="cerrarModalMargenMasivo()" class="btn-close-modal">&times;</button>
        </div>
        <div class="modal-body p-24">
            <div class="p-16 bg-surface2 br-12 border mb-20 d-flex ai-center gap-12">
                <div class="fs-24 text-accent"><i class="fa-solid fa-lightbulb"></i></div>
                <p class="fs-12 m-0 text-muted">
                    <?php echo L('prod_modal_mass_margin_info'); ?>
                </p>
            </div>

            <div class="form-group mb-20">
                <label class="form-label font-bold mb-8"><?php echo L('prod_modal_mass_margin_step1'); ?></label>
                <select id="massCategoriaSelect" class="form-input h-44" onchange="previewMargenMasivo()">
                    <option value="all"><?php echo L('prod_modal_mass_margin_cat_all'); ?></option>
                    <?php foreach ($avProductos['categorias'] as $c): ?>
                        <option value="<?php echo htmlspecialchars($c['codigo']); ?>">
                            <?php echo htmlspecialchars($c['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mb-20">
                <label class="form-label font-bold mb-8"><?php echo L('prod_modal_mass_margin_step2'); ?></label>
                <div class="d-flex ai-center gap-12">
                    <input type="number" id="massMargenInput" class="form-input h-44 font-mono text-center fs-18" placeholder="0.00" step="0.01" oninput="previewMargenMasivo()">
                    <div class="fs-20 font-bold opacity-30">%</div>
                </div>
            </div>

            <div class="form-group mb-20">
                <label class="form-label font-bold mb-8 d-flex jc-between ai-center">
                    <span><?php echo L('prod_modal_mass_margin_step3'); ?></span>
                    <span class="badge bg-surface2 text-muted fw-normal px-8 py-2 br-4" id="contadorExcepciones">0 <?php echo L('selected'); ?></span>
                </label>
                <div class="p-16 border br-12 bg-surface2">
                    <div class="search-box mb-12">
                        <i class="fa-solid fa-search"></i>
                        <input type="text" id="buscadorExcepciones" class="form-input" placeholder="<?php echo L('prod_modal_mass_margin_ex_placeholder'); ?>" oninput="buscarExcepcionesAlEscribir()">
                    </div>
                    <div id="listaExcepciones" class="d-flex fd-column gap-8" style="max-height: 180px; overflow-y: auto;">
                        <div class="text-center p-20 text-muted fs-12"><i class="fa-solid fa-info-circle mb-8 fs-16 d-block"></i> <?php echo L('prod_modal_mass_margin_ex_empty'); ?></div>
                    </div>
                </div>
            </div>

            <div id="margenPreviewBox" class="p-16 br-12 border-2" style="display:none; background: rgba(var(--accent-rgb), 0.05); border-style: dashed; border-color: var(--accent);">
                <div class="fs-11 tt-uppercase font-bold text-accent mb-8" style="letter-spacing: 0.05em;"><?php echo L('prod_modal_mass_margin_preview_title'); ?></div>
                <div class="d-flex jc-between ai-center mb-4">
                    <span class="fs-13"><?php echo L('prod_modal_mass_margin_updating'); ?></span>
                    <span id="previewCountOk" class="font-bold text-green fs-14">0</span>
                </div>
                <div class="d-flex jc-between ai-center">
                    <span class="fs-13 text-muted"><?php echo L('prod_modal_mass_margin_omitted'); ?></span>
                    <span id="previewCountOmit" class="font-bold text-red fs-14">0</span>
                </div>
            </div>
        </div>
        <div class="modal-footer px-24 pb-24 border-none">
            <button type="button" onclick="cerrarModalMargenMasivo()" class="btn-cancel"><?php echo L('modal_cancel'); ?></button>
            <button type="button" id="btnAplicarMargen" onclick="confirmarAplicarMargen()" class="btn-save px-24" style="background: #7c3aed; color: white;" disabled>
                <i class="fa-solid fa-check"></i> <?php echo L('prod_modal_mass_margin_btn_apply'); ?>
            </button>
        </div>
    </div>
</div>

<!-- MODAL GESTIÓN CATEGORÍAS -->
<div id="modalCategorias" class="modal-overlay-bg">
    <div class="modal-content" style="max-width: 1100px; width: 95%; padding: 0; overflow: hidden; border-radius: 24px; box-shadow: 0 40px 80px rgba(0,0,0,0.45); border: 1px solid var(--border);">
        <div class="modal-header p-24 bg-surface2 border-bottom" style="background: linear-gradient(135deg, var(--surface) 0%, var(--surface2) 100%);">
            <div class="d-flex ai-center gap-15">
                <div class="p-12 br-12 bg-accent-soft text-accent" style="box-shadow: 0 8px 20px rgba(var(--accent-rgb), 0.15); font-size: 24px;">
                    <i class="fa-solid fa-tags"></i>
                </div>
                <div>
                    <h2 class="m-0 fs-24 font-bold" style="letter-spacing: -0.02em;"><?php echo L('prod_modal_cat_title'); ?></h2>
                    <p class="m-0 fs-13 text-muted opacity-80"><?php echo L('prod_modal_cat_subtitle'); ?></p>
                </div>
            </div>
            <button onclick="cerrarModalCategorias()" class="btn-close-modal" style="background: var(--surface2); width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border);">&times;</button>
        </div>
        <div class="p-32 bg-surface">
            <div class="p-24 bg-surface2 br-20 border-2 mb-32" style="border-style: dashed; background: var(--surface2);">
                <div class="fs-12 font-bold mb-16 text-accent tt-uppercase d-flex ai-center gap-8" style="letter-spacing: 0.12em;">
                    <i class="fa-solid fa-circle-plus"></i> <?php echo L('prod_modal_cat_new_title'); ?>
                </div>
                <div class="d-grid gap-20" style="grid-template-columns: 2fr 1fr 1fr auto;">
                    <div class="form-group mb-0">
                        <label class="form-label fs-10 font-bold opacity-60"><?php echo L('prod_modal_cat_label_name'); ?></label>
                        <input type="text" id="newCatNombre" placeholder="Ej: Audio, Sonido y Multimedia" class="form-input h-48 fs-14" style="border-radius: 12px;">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label fs-10 font-bold opacity-60"><?php echo L('prod_modal_cat_label_slug'); ?></label>
                        <input type="text" id="newCatCodigo" placeholder="ej: audio-multimedia" class="form-input h-48 font-mono fs-13" style="border-radius: 12px;">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label fs-10 font-bold opacity-60"><?php echo L('prod_modal_cat_label_iva'); ?></label>
                        <select id="newCatIva" class="form-input h-48 fs-13" style="border-radius: 12px;">
                            <?php foreach ($avProductos['tipos_iva'] as $t): ?>
                                <option value="<?php echo $t['id']; ?>" <?php echo ($t['porcentaje'] == 21) ? 'selected' : ''; ?>>
                                    <?php echo $t['codigo']; ?> (<?php echo (float)$t['porcentaje']; ?>%)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-flex ai-flex-end pb-4">
                        <button onclick="añadirCategoria()" class="btn-save h-48 px-32 fs-14 font-bold shadow-sm" style="border-radius: 12px; background: var(--accent); color: white;">
                            <?php echo L('prod_modal_cat_btn_add'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-container border br-16 overflow-hidden" style="max-height: 500px; overflow-y: auto; background: var(--surface); box-shadow: var(--shadow-sm);">
                <table class="fs-13" style="width: 100%; border-collapse: collapse;">
                    <thead style="position: sticky; top: 0; z-index: 10;">
                        <tr class="text-left bg-surface2 border-bottom">
                            <th class="p-20 text-muted tt-uppercase fs-10 font-bold" style="letter-spacing: 0.05em;"><?php echo L('prod_modal_cat_th_info'); ?></th>
                            <th class="p-20 text-muted tt-uppercase fs-10 font-bold" style="letter-spacing: 0.05em; width: 400px;"><?php echo L('prod_modal_cat_th_iva'); ?></th>
                            <th class="p-20 text-muted tt-uppercase fs-10 font-bold text-center" style="letter-spacing: 0.05em; width: 120px;"><?php echo L('prod_th_actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="background: var(--surface);">
                        <?php foreach ($avProductos['categorias'] as $c): ?>
                            <tr class="hover-bg-surface2 transition-all">
                                <td class="p-20">
                                    <div class="d-flex ai-center gap-15">
                                        <div class="p-10 br-10 bg-surface2 text-muted border">
                                            <i class="fa-solid fa-folder-open"></i>
                                        </div>
                                        <div>
                                            <div class="font-bold fs-15" style="color: var(--text);"><?php echo htmlspecialchars($c['nombre']); ?></div>
                                            <div class="fs-11 text-muted font-mono opacity-60"><?php echo htmlspecialchars($c['codigo']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-20">
                                    <div class="d-flex ai-center gap-12 bg-surface2 p-8 br-12 border">
                                        <select class="form-input h-38 fs-12 px-12 flex-1 border-none bg-none" id="iva-cat-<?php echo $c['id']; ?>">
                                            <option value=""><?php echo L('prod_modal_cat_option_select'); ?></option>
                                            <?php foreach ($avProductos['tipos_iva'] as $t): ?>
                                                <option value="<?php echo $t['id']; ?>" <?php echo ($c['id_tipo_iva'] == $t['id']) ? 'selected' : ''; ?>>
                                                    <?php echo $t['codigo']; ?> (<?php echo (float)$t['porcentaje']; ?>%)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button onclick="aplicarIvaCategoria(<?php echo $c['id']; ?>)" class="btn-save bg-accent text-white h-38 px-16 border-none shadow-sm hover-scale" title="Aplicar este IVA a todos los productos de esta categoría" style="border-radius: 8px;">
                                            <i class="fa-solid fa-bolt mr-8"></i> <?php echo L('prod_modal_cat_btn_apply'); ?>
                                        </button>
                                    </div>
                                </td>
                                <td class="p-20 text-center">
                                    <button onclick="eliminarCategoria(<?php echo $c['id']; ?>, '<?php echo addslashes(htmlspecialchars($c['nombre'])); ?>')" class="btn-icon text-red bg-red-soft p-12 br-12 transition-all hover-scale" title="<?php echo L('prod_modal_cat_tip_delete'); ?>" style="width: 42px; height: 42px;">
                                        <i class="fa-solid fa-trash-can fs-16"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL HISTORIAL COSTES (CMP) -->
<div id="modalHistorialCostes" class="modal-overlay-bg">
    <div class="modal-content" style="max-width: 900px; padding: 0; overflow: hidden; border-radius: 20px;">
        <div class="modal-header p-20 bg-surface2 border-bottom d-flex ai-center jc-between">
            <div class="d-flex ai-center gap-12">
                <div class="p-10 br-12 bg-accent-soft text-accent">
                    <i class="fa-solid fa-clock-rotate-left fs-18"></i>
                </div>
                <div>
                    <h2 class="m-0 fs-18" id="costesTitle"><?php echo L('prod_modal_cost_history_title'); ?></h2>
                    <p class="m-0 fs-12 text-muted"><?php echo L('prod_modal_cost_history_subtitle'); ?></p>
                </div>
            </div>
            <button onclick="cerrarModalHistorialCostes()" class="btn-close-modal">&times;</button>
        </div>
        <div class="p-24">
            <div class="table-container" style="max-height: 480px; overflow-y: auto;">
                <table class="data-table fs-13">
                    <thead style="position: sticky; top: 0; z-index: 10; background: var(--surface2);">
                        <tr>
                            <th class="text-left pl-20" style="white-space: nowrap;"><?php echo L('prod_modal_th_date'); ?></th>
                            <th class="text-center" style="white-space: nowrap;"><?php echo L('prod_th_qty'); ?></th>
                            <th class="text-right" style="white-space: nowrap;"><?php echo L('prod_modal_th_buy_price'); ?></th>
                            <th class="text-right" style="white-space: nowrap;"><?php echo L('prod_modal_th_cmp_prev'); ?></th>
                            <th class="text-right pr-20" style="white-space: nowrap;"><?php echo L('prod_modal_th_cmp_res'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="costesTableBody">
                        <!-- Se llena vía JS -->
                    </tbody>
                </table>
            </div>
            <div class="mt-20 p-16 bg-surface2 br-12 border d-flex ai-center gap-12">
                <div class="fs-18 text-accent"><i class="fa-solid fa-circle-info"></i></div>
                <p class="fs-12 m-0 text-muted">
                    <?php echo L('prod_modal_cost_history_info'); ?>
                </p>
            </div>
        </div>
    </div>
</div>