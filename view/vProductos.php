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

                <div class="vr mx-4" style="height: 20px; width: 1px; background: var(--border); opacity: 0.5;"></div>

                <button onclick="abrirModalAjusteMasivo()" class="btn-filter" id="btnAjusteMasivo" style="border:none; background:transparent; color: #7c3aed !important; font-weight: 600;" title="<?php echo L('prod_tip_mass_adjustment'); ?>">
                    <?php echo L('prod_btn_mass_adjustment'); ?>
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
    <div class="filters-panel-new container-wider mb-24">
        <div class="flex-1">
            <div class="search-input-fancy">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="prodSearch" placeholder="<?php echo L('prod_search_placeholder'); ?>">
            </div>
        </div>

        <div class="filter-item">
            <span class="filter-label"><?php echo L('prod_label_price_filter'); ?></span>
            <div class="d-flex gap-8">
                <input type="number" id="filterPriceMin" placeholder="<?php echo L('prod_filter_price_min'); ?>" class="filter-control" style="width: 100px; min-width: 100px;" oninput="filtrarProductos()">
                <input type="number" id="filterPriceMax" placeholder="<?php echo L('prod_filter_price_max'); ?>" class="filter-control" style="width: 100px; min-width: 100px;" oninput="filtrarProductos()">
            </div>
        </div>

        <div class="filter-item">
            <span class="filter-label"><?php echo L('prod_th_category'); ?></span>
            <select id="filterCat" class="filter-control" onchange="applyFilters()" style="min-width: 180px;">
                <option value="all"><?php echo L('prod_filter_cat_all'); ?></option>
                <?php foreach ($avProductos['categorias'] as $c): ?>
                    <option value="<?php echo htmlspecialchars($c['codigo']); ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-item">
            <span class="filter-label"><?php echo L('prod_th_status'); ?></span>
            <select id="filterEstado" class="filter-control" onchange="applyFilters()" style="min-width: 150px;">
                <option value="all"><?php echo L('prod_filter_status_all'); ?></option>
                <option value="1"><?php echo L('prod_status_active'); ?></option>
                <option value="0"><?php echo L('prod_status_inactive'); ?></option>
                <option value="bajo_stock"><?php echo L('prod_status_low_stock'); ?></option>
            </select>
        </div>
    </div>

    <!-- TABLA DE PRODUCTOS (PESTAÑA 1) -->
    <div class="table-container container-wider tab-content active" id="tabContentProductos">
        <table class="data-table exclude-pagination">
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
            <tbody id="tbodyProductos">
                <?php foreach ($avProductos['productos'] as $p): ?>
                    <tr data-id="<?php echo $p['id']; ?>">
                        <td class="font-mono text-muted pl-20"><?php echo $p['id']; ?></td>
                        <td class="text-center" style="width: 80px;">
                            <?php 
                            $icono = $p['icono'];
                            if ($icono && strpos($icono, 'data:image') === 0): ?>
                                <img src="<?php echo $icono; ?>" class="prod-img-fixed" alt="Icono">
                            <?php else: ?>
                                <span class="fs-24"><?php echo $icono; ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="font-bold"><?php echo htmlspecialchars($p['nombre']); ?></td>
                        <td class="text-muted"><?php echo htmlspecialchars($p['referencia']); ?></td>
                        <td>
                            <span class="cat-pill">
                                <?php echo htmlspecialchars($p['categoria']); ?>
                            </span>
                             <?php
                             $atributosStr = $p['atributos'];
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
                            <?php echo number_format($p['precio_venta'], 2, ',', '.'); ?> €
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
                                <button onclick="toggleEstadoProducto(<?php echo $p['id']; ?>)" title="<?php echo $p['activo'] ? L('prod_tip_deactivate') : L('prod_tip_activate'); ?>" class="btn-icon <?php echo $p['activo'] ? 'text-red' : 'text-green'; ?>">
                                    <i class="fa-solid fa-<?php echo $p['activo'] ? 'arrow-down' : 'arrow-up'; ?>"></i>
                                </button>
                                <button onclick="eliminarProducto(<?php echo $p['id']; ?>, '<?php echo addslashes(htmlspecialchars($p['nombre'])); ?>')" title="<?php echo L('modal_delete'); ?>" class="btn-icon text-red" style="opacity:0.7;">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($avProductos['productos'])): ?>
                    <tr id="noResults">
                        <td colspan="9">
                            <div class="empty-state">
                                <i class="fa-solid fa-box-open"></i>
                                <?php echo L('prod_no_results'); ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Paginación -->
        <div class="pagination-footer mt-24 d-flex ai-center jc-between p-16 bg-surface border-top">
            <div class="pagination-info fs-12 text-muted">
                <?php 
                    $from = $avProductos['paginacion']['totalRegistros'] > 0 ? ($avProductos['paginacion']['actual'] - 1) * $avProductos['paginacion']['limit'] + 1 : 0;
                    $to = min($avProductos['paginacion']['actual'] * $avProductos['paginacion']['limit'], $avProductos['paginacion']['totalRegistros']);
                    echo str_replace(['{from}', '{to}', '{total}'], (array)[$from, $to, $avProductos['paginacion']['totalRegistros']], L('page_showing')); 
                ?>
            </div>
            <div class="pagination-controls d-flex gap-8">
                <button class="btn-icon" onclick="loadProducts(1)" <?php echo $avProductos['paginacion']['actual'] == 1 ? 'disabled' : ''; ?> title="<?php echo L('page_first'); ?>">
                    <i class="fa-solid fa-angles-left"></i>
                </button>
                <button class="btn-secondary" onclick="loadProducts(<?php echo $avProductos['paginacion']['actual'] - 1; ?>)" <?php echo $avProductos['paginacion']['actual'] == 1 ? 'disabled' : ''; ?>>
                    <i class="fa-solid fa-chevron-left"></i> <?php echo L('page_prev'); ?>
                </button>
                <span class="pagination-current fw-600 fs-13 d-flex ai-center px-12 br-8" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                    <?php echo str_replace(['{current}', '{total}'], [$avProductos['paginacion']['actual'], $avProductos['paginacion']['total']], L('page_info')); ?>
                </span>
                <button class="btn-secondary" onclick="loadProducts(<?php echo $avProductos['paginacion']['actual'] + 1; ?>)" <?php echo $avProductos['paginacion']['actual'] >= $avProductos['paginacion']['total'] ? 'disabled' : ''; ?>>
                    <?php echo L('page_next'); ?> <i class="fa-solid fa-chevron-right"></i>
                </button>
                <button class="btn-icon" onclick="loadProducts(<?php echo $avProductos['paginacion']['total']; ?>)" <?php echo $avProductos['paginacion']['actual'] >= $avProductos['paginacion']['total'] ? 'disabled' : ''; ?> title="<?php echo L('page_last'); ?>">
                    <i class="fa-solid fa-angles-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- TABLA DE PACKS (PESTAÑA 2) -->
    <div class="table-container container-wider tab-content d-none" id="tabContentPacks">
        <table class="data-table exclude-pagination">
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
                <?php foreach ($avProductos['productos'] as $p): if (!$p['es_pack']) continue; ?>
                    <tr class="product-row row-tipo-pack"
                        data-nombre="<?php echo strtolower(htmlspecialchars($p['nombre'])); ?>"
                        data-codigo="<?php echo strtolower(htmlspecialchars($p['referencia'])); ?>"
                        data-categoria="<?php echo $p['categoria']; ?>"
                        data-precio="<?php echo $p['precio_venta']; ?>"
                        data-activo="<?php echo $p['activo'] ? '1' : '0'; ?>">
                        <td class="font-mono text-muted pl-20"><?php echo $p['id']; ?></td>
                        <td class="text-center">
                            <?php 
                            $icono = $p['icono'];
                            if ($icono && strpos($icono, 'data:image') === 0): ?>
                                <img src="<?php echo $icono; ?>" class="prod-img-fixed" alt="Icono">
                            <?php else: ?>
                                <span class="fs-24"><?php echo $icono; ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="font-bold">
                            <?php echo htmlspecialchars($p['nombre']); ?>
                            <span class="ml-8 px-6 py-2 br-4 fs-10 bg-accent-soft text-accent border border-accent"><?php echo L('prod_pill_pack'); ?></span>
                        </td>
                        <td class="text-muted"><?php echo htmlspecialchars($p['referencia']); ?></td>
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
                            <?php echo number_format($p['precio_venta'], 2, ',', '.'); ?> €
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
                                <button onclick="toggleEstadoProducto(<?php echo $p['id']; ?>)" title="<?php echo $p['activo'] ? L('prod_tip_deactivate') : L('prod_tip_activate'); ?>" class="btn-icon <?php echo $p['activo'] ? 'text-red' : 'text-green'; ?>">
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


    /* --- Tabla de Tarifas --- */
    .tarifas-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: auto;
        margin: 0;
        background: transparent;
    }

    .tarifas-table th,
    .tarifas-table td {
        padding: 14px 16px;
        vertical-align: middle;
        font-size: 13px;
        border-bottom: 1px solid var(--surface2);
        color: var(--text);
    }

    .tarifas-table th {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-weight: 700;
        color: var(--text-muted);
        background: var(--surface2);
        white-space: normal; /* Permitir que el texto respire y no se solape */
        line-height: 1.4;
    }

    /* Distribución equilibrada */
    .tarifas-table th:nth-child(1), .tarifas-table td:nth-child(1) { width: 35%; min-width: 160px; text-align: left; }
    .tarifas-table th:nth-child(2), .tarifas-table td:nth-child(2) { width: 16%; min-width: 90px; text-align: center; }
    .tarifas-table th:nth-child(3), .tarifas-table td:nth-child(3) { width: 16%; min-width: 90px; text-align: center; }
    .tarifas-table th:nth-child(4), .tarifas-table td:nth-child(4) { width: 16%; min-width: 90px; text-align: center; }
    .tarifas-table th:nth-child(5), .tarifas-table td:nth-child(5) { width: 17%; min-width: 90px; text-align: center; }

    .tarifas-table .btn-icon {
        width: 32px;
        height: 32px;
        background: transparent;
        border: none;
        box-shadow: none;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--red);
        cursor: pointer;
        transition: all 0.2s;
        border-radius: 50%;
    }
    .tarifas-table .btn-icon:hover {
        background: var(--red-light);
        transform: scale(1.1);
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
                                <div class="d-flex flex-column ai-center gap-16 p-20 bg-surface2 border-2" style="border-radius: 10px;">
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

                    <div class="form-group mb-32" style="margin-top: 15px;">
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
                                <div class="d-flex ai-center mb-4" style="height: 16px;">
                                    <label class="form-label fs-10 tt-uppercase m-0" style="white-space: nowrap;">
                                        <?php echo L('prod_modal_label_cost_base'); ?> <span class="opacity-50">(<?php echo L('optional'); ?>)</span>
                                    </label>
                                </div>
                                <input type="text" id="prodPrecioProveedor" placeholder="<?php echo L('prod_placeholder_price'); ?>"
                                    class="form-input text-right font-mono"
                                    oninput="calcularTotalDesdeBase()"
                                    title="<?php echo L('prod_modal_tip_cost_base'); ?>">
                            </div>
                            <div class="form-group mb-0">
                                <div class="d-flex ai-center jc-between mb-4 w-100" style="height: 16px;">
                                    <label class="form-label fs-10 tt-uppercase m-0" style="white-space: nowrap;"><?php echo L('prod_modal_label_cost_total'); ?></label>
                                    <button type="button" id="btnHistorialCostes" onclick="abrirModalHistorialCostes()" class="btn-icon p-0 fs-10 text-accent d-inline-flex ai-center ml-auto" title="<?php echo L('prod_modal_tip_cost_history'); ?>" style="display:none; width: auto; height: auto;">
                                        <i class="fa-solid fa-clock-rotate-left mr-4"></i> <?php echo L('prod_modal_btn_history'); ?>
                                    </button>
                                </div>
                                <input type="text" id="prodPrecioCoste" placeholder="<?php echo L('prod_placeholder_price'); ?>"
                                    class="form-input text-right font-mono"
                                    oninput="calcularBaseDesdeTotal(); calcularPrecioDesdeMargen();"
                                    title="<?php echo L('prod_modal_tip_cost_auto'); ?>">
                                <span class="form-error" id="err-precio_coste"></span>
                            </div>
                        </div>
                        <div class="form-group mt-16">
                            <label class="form-label fs-11 tt-uppercase mb-4"><?php echo L('prod_modal_label_margin'); ?></label>
                            <input type="number" id="prodMargen" placeholder="<?php echo L('prod_placeholder_margin'); ?>" step="0.01"
                                class="form-input text-right font-mono"
                                oninput="calcularPrecioDesdeMargen()"
                                title="<?php echo L('prod_modal_tip_margin_calc'); ?>">
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
                                    <div class="d-flex jc-center ai-center gap-8 w-full" style="max-width: 220px;">
                                        <button type="button" id="btnRetirarStock" class="btn-icon text-red d-none" onclick="ajustarStockManual('salida')" title="<?php echo L('prod_modal_tip_stock_withdraw'); ?>">
                                            <i class="fa-solid fa-circle-minus fs-20"></i>
                                        </button>
                                        <input type="text" id="prodStock" placeholder="<?php echo L('prod_placeholder_stock'); ?>" class="form-input text-center font-bold flex-1 h-44 fs-16" style="border-radius: 12px; border-width: 2px;">
                                        <button type="button" id="btnAñadirStock" class="btn-icon text-green d-none" onclick="ajustarStockManual('entrada')" title="<?php echo L('prod_modal_tip_stock_add'); ?>">
                                            <i class="fa-solid fa-circle-plus fs-20"></i>
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
                                        <th class="text-center"><?php echo L('prod_modal_th_variation'); ?></th>
                                        <th class="text-center"><?php echo L('prod_modal_th_price_with_rate'); ?></th>
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

        <div class="modal-body" style="padding: 24px 32px; background: var(--surface); min-height: 400px;">
            <div class="table-container" style="max-height: 550px; overflow-y: auto; border-radius: 12px; border: 1px solid var(--border);">
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
        <div class="modal-body" style="padding: 24px 32px; min-height: 300px;">
            <div style="max-height: 500px; overflow-y: auto; border: 1px solid var(--border); border-radius: 12px; overflow: hidden;">
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
    let paginationData = <?php echo json_encode($avProductos['paginacion']); ?>;
    let currentFilters = {
        term: '',
        cat: '',
        minPrice: '',
        maxPrice: ''
    };

    function debounce(func, timeout = 300) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => { func.apply(this, args); }, timeout);
        };
    }

    async function loadProducts(page) {
        if (page < 1 || (paginationData.total > 0 && page > paginationData.total)) return;

        const limit = paginationData.limit;
        const offset = (page - 1) * limit;
        
        const tbody = document.getElementById('tbodyProductos');
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-40"><i class="fa-solid fa-circle-notch fa-spin fa-2x text-muted"></i></td></tr>';

        try {
            const resp = await fetch('api/gestionProducto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    accion: 'listar',
                    term: currentFilters.term,
                    cat: currentFilters.cat,
                    minPrice: currentFilters.minPrice,
                    maxPrice: currentFilters.maxPrice,
                    limit: limit,
                    offset: offset
                })
            });
            const data = await resp.json();
            
            if (!data.ok) throw new Error(data.error);

            renderProductsTable(data.productos);
            
            paginationData.actual = page;
            paginationData.total = data.totalPaginas || Math.ceil(data.total / limit);
            paginationData.totalRegistros = data.total;

            updateProductsPaginationUI();

        } catch (e) {
            console.error(e);
            tbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger py-40">${e.message || "<?php echo L('prod_js_error'); ?>"}</td></tr>`;
        }
    }

    function renderProductsTable(lista) {
        const tbody = document.getElementById('tbodyProductos');
        if (lista.length === 0) {
            tbody.innerHTML = '<tr id="noResults"><td colspan="9"><div class="empty-state"><i class="fa-solid fa-box-open"></i><?php echo L('prod_no_results'); ?></div></td></tr>';
            return;
        }

        let html = '';
        lista.forEach(p => {
            if (p.es_pack) return; // En esta pestaña no mostramos packs

            const stock = parseInt(p.stock || 0);
            const stockMin = parseInt(p.stock_minimo || 0);
            const esCritico = (stockMin > 0 && stock <= stockMin);
            const statusClass = p.activo ? 'status-active' : 'status-inactive';
            const statusIcon = p.activo ? 'fa-circle-check' : 'fa-circle-xmark';
            const statusText = p.activo ? "<?php echo L('prod_pill_active'); ?>" : "<?php echo L('prod_pill_inactive'); ?>";

            let iconHtml = '';
            if (p.icono && p.icono.startsWith('data:image')) {
                iconHtml = `<img src="${p.icono}" class="prod-img-fixed" alt="Icono">`;
            } else {
                iconHtml = `<span class="fs-24">${p.icono || '<i class="fa-solid fa-box"></i>'}</span>`;
            }

            html += `
                <tr data-id="${p.id}">
                    <td class="font-mono text-muted pl-20">${p.id}</td>
                    <td class="text-center" style="width: 80px;">${iconHtml}</td>
                    <td class="font-bold">${p.nombre}</td>
                    <td class="text-muted">${p.referencia || ''}</td>
                    <td>
                        <span class="cat-pill">${p.categoria}</span>
                    </td>
                    <td class="text-right font-bold font-mono">
                        <div class="d-flex flex-column ai-end">
                            <span class="${esCritico ? 'text-red bg-red-soft px-4 br-4' : ''}">${stock}</span>
                            <span class="fs-9 tt-uppercase ${esCritico ? 'text-red font-bold' : 'text-muted'}"><?php echo L('prod_stock_min_label'); ?> ${stockMin}</span>
                        </div>
                    </td>
                    <td class="text-right font-bold font-mono">${parseFloat(p.precio_venta).toLocaleString('es-ES', {minimumFractionDigits: 2})} €</td>
                    <td class="text-center">
                        <span class="status-pill ${statusClass}">
                            <i class="fa-solid ${statusIcon}"></i> ${statusText}
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="d-flex jc-center gap-8 pr-20">
                            <button onclick="abrirModalHistorial(${p.id}, '${p.nombre.replace(/'/g, "\\'")}')" class="btn-icon text-accent"><i class="fa-solid fa-clock-rotate-left"></i></button>
                            <button onclick="abrirModalHistorialPrecios(${p.id}, '${p.nombre.replace(/'/g, "\\'")}')" class="btn-icon" style="color: var(--yellow, #f59e0b);"><i class="fa-solid fa-tag"></i></button>
                            <button onclick='abrirModalProducto(${JSON.stringify(p)})' class="btn-icon"><i class="fa-solid fa-pen"></i></button>
                            <button onclick="toggleEstadoProducto(${p.id})" class="btn-icon ${p.activo ? 'text-red' : 'text-green'}"><i class="fa-solid ${p.activo ? 'fa-arrow-down' : 'fa-arrow-up'}"></i></button>
                            <button onclick="eliminarProducto(${p.id}, '${p.nombre.replace(/'/g, "\\'")}')" class="btn-icon text-red" style="opacity:0.7;"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    }

    function updateProductsPaginationUI() {
        const from = paginationData.totalRegistros > 0 ? (paginationData.actual - 1) * paginationData.limit + 1 : 0;
        const to = Math.min(paginationData.actual * paginationData.limit, paginationData.totalRegistros);
        
        let infoText = "<?php echo L('page_showing'); ?>";
        infoText = infoText.replace('{from}', from).replace('{to}', to).replace('{total}', paginationData.totalRegistros);
        
        document.querySelector('.pagination-info').textContent = infoText;
        document.querySelector('.pagination-current').textContent = `<?php echo L('page_info'); ?>`.replace('{current}', paginationData.actual).replace('{total}', paginationData.total);
        
        const controls = document.querySelector('.pagination-controls');
        const btns = controls.querySelectorAll('button');
        
        btns[0].onclick = () => loadProducts(1);
        btns[0].disabled = paginationData.actual === 1;
        
        btns[1].onclick = () => loadProducts(paginationData.actual - 1);
        btns[1].disabled = paginationData.actual === 1;
        
        btns[2].onclick = () => loadProducts(paginationData.actual + 1);
        btns[2].disabled = paginationData.actual >= paginationData.total;
        
        btns[3].onclick = () => loadProducts(paginationData.total);
        btns[3].disabled = paginationData.actual >= paginationData.total;
    }

    const filtrarProductos = debounce(() => {
        currentFilters.term = document.getElementById('prodSearch').value.toLowerCase().trim();
        currentFilters.cat  = document.getElementById('filterCat') ? document.getElementById('filterCat').value : '';
        currentFilters.minPrice = document.getElementById('filterPriceMin') ? document.getElementById('filterPriceMin').value : '';
        currentFilters.maxPrice = document.getElementById('filterPriceMax') ? document.getElementById('filterPriceMax').value : '';
        
        loadProducts(1);
    }, 400);

    // Adjuntar evento de búsqueda
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('prodSearch');
        if (searchInput) {
            searchInput.addEventListener('input', filtrarProductos);
        }
    });

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

    function getDatoIVA() {
        const selIva = document.getElementById('prodIvaTipo');
        const opt = selIva.options[selIva.selectedIndex];
        return {
            iva: parseFloat(opt.dataset.porcentaje || 21),
            re: parseFloat(opt.dataset.re || 0)
        };
    }

    function getAplicaRE() {
        const selProv = document.getElementById('prodProveedor');
        if (!selProv.value) return false;
        const opt = selProv.options[selProv.selectedIndex];
        return opt.dataset.re == "1";
    }

    function calcularTotalDesdeBase() {
        let base = document.getElementById('prodPrecioProveedor').value.replace(',', '.');
        base = parseFloat(base) || 0;
        
        const infoIVA = getDatoIVA();
        const aplicaRE = getAplicaRE();
        const pctRE = aplicaRE ? infoIVA.re : 0;

        const total = base * (1 + (infoIVA.iva / 100) + (pctRE / 100));
        document.getElementById('prodPrecioCoste').value = total.toFixed(4);
        
        // Al cambiar el coste, recalculamos PVP si hay margen
        calcularPrecioDesdeMargen();
    }

    function calcularBaseDesdeTotal() {
        let total = document.getElementById('prodPrecioCoste').value.replace(',', '.');
        total = parseFloat(total) || 0;

        const infoIVA = getDatoIVA();
        const aplicaRE = getAplicaRE();
        const pctRE = aplicaRE ? infoIVA.re : 0;

        const base = total / (1 + (infoIVA.iva / 100) + (pctRE / 100));
        document.getElementById('prodPrecioProveedor').value = base.toFixed(4);
    }

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
            document.getElementById('prodPrecioProveedor').value = parseFloat(producto.precio_proveedor || 0).toFixed(4);

            if (parseFloat(producto.margen || 0) <= 0) calcularMargenDesdePrecio();

            // Bloquear edición manual de stock y coste para productos existentes (solo permitimos si el usuario quiere forzar)
            inputStock.readOnly = true;
            inputStock.style.opacity = '0.7';
            inputStock.title = <?php echo json_encode(L('prod_modal_tip_stock_auto', true)); ?>;
            
            // El coste total se puede editar pero avisamos que es CMP
            inputCoste.classList.add('input-readonly-cmp'); 
            inputCoste.title = <?php echo json_encode(L('prod_modal_tip_cost_auto', true)); ?>;

            const btnAñadir = document.getElementById('btnAñadirStock');
            if (btnAñadir) btnAñadir.classList.remove('d-none');
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
            document.getElementById('prodPrecioProveedor').value = '0.00';

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
        if (query === '') {
            document.getElementById('listaExcepciones').innerHTML = '<div class="text-center p-20 text-muted fs-12">' + <?php echo json_encode(L('prod_modal_mass_margin_ex_empty', true)); ?> + '</div>';
            return;
        }

        const url = 'api/gestionProducto.php?accion=listar'; 
        try {
            const resp = await fetch(url);
            const r = await resp.json();
            if (r.ok) {
                let html = '';
                const prods = r.productos.filter(p => !p.es_pack && (p.nombre.toLowerCase().includes(query.toLowerCase()) || (p.referencia && p.referencia.toLowerCase().includes(query.toLowerCase()))));
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

    /* --- LÓGICA AJUSTE DE PRECIO MASIVO --- */
    let excepcionesAjuste = [];
    let timerBusquedaExcepcionesAjuste = null;

    function abrirModalAjusteMasivo() {
        const modal = document.getElementById('modalAjusteMasivo');
        modal.classList.add('visible');
        document.getElementById('ajusteValorInput').value = '';
        document.getElementById('ajustePreviewBox').style.display = 'none';
        document.getElementById('btnAplicarAjuste').disabled = true;
        
        excepcionesAjuste = [];
        actualizarUIExcepcionesAjuste();
        document.getElementById('buscadorExcepcionesAjuste').value = '';
        lanzarBusquedaExcepcionesAjuste('');
    }

    function buscarExcepcionesAjuste() {
        if (timerBusquedaExcepcionesAjuste) clearTimeout(timerBusquedaExcepcionesAjuste);
        timerBusquedaExcepcionesAjuste = setTimeout(() => {
            const query = document.getElementById('buscadorExcepcionesAjuste').value.trim();
            lanzarBusquedaExcepcionesAjuste(query);
        }, 300);
    }

    async function lanzarBusquedaExcepcionesAjuste(query) {
        if (!query) {
            document.getElementById('listaExcepcionesAjuste').innerHTML = '';
            return;
        }

        const url = 'api/gestionProducto.php?accion=listar'; 
        try {
            const resp = await fetch(url);
            const r = await resp.json();
            if (r.ok) {
                let html = '';
                const prods = r.productos.filter(p => !p.es_pack && (p.nombre.toLowerCase().includes(query.toLowerCase()) || (p.referencia && p.referencia.toLowerCase().includes(query.toLowerCase()))));
                const topProds = prods.slice(0, 30);
                
                if (topProds.length === 0) {
                    html = `<div class="text-center p-20 text-muted fs-12">${<?php echo json_encode(L('prod_js_mass_adj_no_results')) ?>}</div>`;
                } else {
                    topProds.forEach(p => {
                        const isSelected = excepcionesAjuste.some(ex => ex.id == p.id);
                        html += `
                            <div class="d-flex ai-center jc-between p-12 br-8 bg-surface border" style="width: 100%; box-sizing: border-box;">
                                <div class="d-flex ai-center gap-12">
                                    <div class="fs-18 text-muted"><i class="${p.icono ? p.icono : 'fa-solid fa-box'}"></i></div>
                                    <div>
                                        <div class="font-bold fs-13 text-ellipsis" style="max-width:260px;">${p.nombre}</div>
                                        <div class="fs-10 text-muted">REF: ${p.referencia || 'S/N'}</div>
                                    </div>
                                </div>
                                <button type="button" class="btn ${isSelected ? 'btn-red' : 'btn-outline'} px-12 py-6 fs-11" onclick="toggleExcepcionAjuste(${p.id}, '${p.nombre.replace(/'/g, "\\'")}', '${(p.referencia||'').replace(/'/g, "\\'")}')">
                                    <i class="fa-solid ${isSelected ? 'fa-minus' : 'fa-plus'}"></i> ${isSelected ? 'Quitar' : 'Añadir'}
                                </button>
                            </div>
                        `;
                    });
                }
                document.getElementById('listaExcepcionesAjuste').innerHTML = html;
            }
        } catch(e) {}
    }

    function toggleExcepcionAjuste(id, nombre, ref) {
        const idx = excepcionesAjuste.findIndex(ex => ex.id == id);
        if (idx > -1) {
            excepcionesAjuste.splice(idx, 1);
        } else {
            excepcionesAjuste.push({id, nombre, referencia: ref});
        }
        actualizarUIExcepcionesAjuste();
        const query = document.getElementById('buscadorExcepcionesAjuste').value.trim();
        lanzarBusquedaExcepcionesAjuste(query); 
        previewAjusteMasivo();
    }

    function actualizarUIExcepcionesAjuste() {
        document.getElementById('contadorExcepcionesAjuste').innerText = excepcionesAjuste.length;
    }

    function cerrarModalAjusteMasivo() {
        document.getElementById('modalAjusteMasivo').classList.remove('visible');
    }

    let timerPreviewAjuste = null;
    function previewAjusteMasivo() {
        const valor = parseFloat(document.getElementById('ajusteValorInput').value);
        const btn = document.getElementById('btnAplicarAjuste');

        if (isNaN(valor) || valor === 0) {
            btn.disabled = true;
            document.getElementById('ajustePreviewBox').style.display = 'none';
            return;
        }

        btn.disabled = false;

        if (timerPreviewAjuste) clearTimeout(timerPreviewAjuste);
        timerPreviewAjuste = setTimeout(async () => {
            const categoria = document.getElementById('ajusteCategoriaSelect').value;
            const tipo = document.getElementById('ajusteTipoSelect').value;
            try {
                const resp = await fetch('api/ajustePrecioMasivo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        valor, tipo, categoria,
                        excepciones: excepcionesAjuste.map(e => e.id),
                        preview: true
                    })
                });
                const r = await resp.json();
                if (r.ok) {
                    document.getElementById('previewCountAjuste').innerText = r.total;
                    document.getElementById('ajustePreviewBox').style.display = 'block';
                }
            } catch (e) {}
        }, 500);
    }

    function confirmarAplicarAjuste() {
        const valor = document.getElementById('ajusteValorInput').value;
        const tipo = document.getElementById('ajusteTipoSelect').value;
        const catLabel = document.getElementById('ajusteCategoriaSelect').options[document.getElementById('ajusteCategoriaSelect').selectedIndex].text;

        const bodyTexto = `<?php echo L('prod_js_mass_adj_confirm_body') ?>`.replace('{count}', document.getElementById('previewCountAjuste').innerText);
        
        showCustomConfirm(
            '<?php echo L('prod_js_mass_adj_confirm_title') ?>',
            bodyTexto,
            async () => {
                const btn = document.getElementById('btnAplicarAjuste');
                const oldHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ...';

                const categoria = document.getElementById('ajusteCategoriaSelect').value;
                try {
                    const resp = await fetch('api/ajustePrecioMasivo.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            valor, tipo, categoria,
                            excepciones: excepcionesAjuste.map(e => e.id)
                        })
                    });
                    const r = await resp.json();
                    if (r.ok) {
                        showCustomAlert('<?php echo L('label_exito') ?>', `<?php echo L('prod_js_mass_adj_success_body') ?>`.replace('{count}', r.total), 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showCustomAlert('<?php echo L('error') ?>', r.mensaje || r.error || '...', 'error');
                    }
                } catch (e) {
                    showCustomAlert('<?php echo L('error') ?>', "Error: " + e.message, 'error');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = oldHtml;
                }
            },
            '<?php echo L('prod_js_mass_adj_confirm_btn') ?>',
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
        tbody.innerHTML = '<tr><td colspan="4" class="text-center p-20"><i class="fa-solid fa-spinner fa-spin"></i> ' + <?php echo json_encode(L('loading', true)); ?> + '</td></tr>';

        try {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const resp = await fetch('api/gestionExclusiones.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'x-csrf-token': token 
                },
                body: JSON.stringify({
                    accion: 'listar_aplicables',
                    id_producto: idProducto
                })
            });
            const data = await resp.json().catch(err => {
                console.error("Malformed JSON response:", err);
                throw new Error("Respuesta del servidor no válida (posible error PHP fatal). Revise la consola.");
            });

            console.log("Diagnostic Data - Reglas:", data);

            if (!data.ok) {
                console.error("API Error Reported:", data.error);
                throw new Error(data.error || "Error desconocido en el servidor");
            }

            tbody.innerHTML = '';
            if (!data.reglas || data.reglas.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center p-20 text-muted">' + 
                    (data.reglas ? <?php echo json_encode(L('prod_modal_tab_tarifas_none', true)); ?> : '<span class="text-red">Error: La respuesta no contiene la lista de reglas.</span>') + 
                    '</td></tr>';
                return;
            }

            const basePrice = parseFloat(document.getElementById('prodPrecioVenta').value.replace(',', '.')) || 0;

            data.reglas.forEach(r => {
                let finalPrice = basePrice;
                if (r.tipo === 'percent') {
                   finalPrice = basePrice * (1 + (r.valor / 100));
                } else {
                   finalPrice = basePrice + r.valor;
                }

                const isPermanent = r.is_permanent === true;
                const tr = document.createElement('tr');
                if (isPermanent) {
                    tr.style.opacity = '0.6';
                    tr.style.background = 'var(--surface2)';
                    tr.classList.add('applied-rule');
                }

                tr.innerHTML = `
                    <td class="font-bold">${r.nombre || 'Sin nombre'} ${isPermanent ? '<i class="fa-solid fa-lock fs-10 opacity-50 ml-4" title="Tarifa aplicada permanentemente"></i>' : ''}</td>
                    <td class="text-center font-mono ${r.valor > 0 ? 'text-green' : 'text-red'}">${r.valor > 0 ? '+' : ''}${r.valor}${r.tipo === 'percent' ? '%' : '€'}</td>
                    <td class="text-center font-bold text-accent">${finalPrice.toFixed(2)}€</td>
                    <td class="text-center">
                        ${isPermanent ? 
                            '<span class="text-muted fs-11 italic"><i class="fa-solid fa-check-double"></i> ' + <?php echo json_encode(L('rates_status_applied', true)); ?> + '</span>' :
                            `<button type="button" onclick="excluirDeRegla(${idProducto}, ${r.id}, '${r.tipo_regla}')" class="btn-icon text-red" title="Excluir producto de esta regla">
                                <i class="fa-solid fa-ban"></i>
                            </button>`
                        }
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } catch (e) {
            console.error("Frontend Exception:", e);
            tbody.innerHTML = `<tr><td colspan="5" class="text-center p-20 text-red" style="background: #fff5f5; border: 1px dashed #feb2b2; border-radius: 8px;">
                <div class="font-bold mb-4"><i class="fa-solid fa-circle-exclamation mr-8"></i> ERROR DE DIAGNÓSTICO</div>
                <div class="fs-12">${e.message}</div>
            </td></tr>`;
        }
    }

    async function excluirDeRegla(idProducto, idRegla, tipo) {
        if (!confirm(<?php echo json_encode(L('prod_js_confirm_exclude_title', true)); ?>.replace('{tipo}', (tipo === 'tarifa' ? <?php echo json_encode(L('prod_modal_pill_tariff', true)); ?>.toLowerCase() : <?php echo json_encode(L('prod_modal_pill_promo', true)); ?>.toLowerCase())))) return;

        try {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const resp = await fetch('api/gestionExclusiones.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'x-csrf-token': token 
                },
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
            id_proveedor: document.getElementById('prodProveedor').value || null,
            aplica_re: 0,
            es_pack: 0,
            componentes_pack: null
        };

        // Enviamos siempre el precio de coste y stock actual para permitir ajustes manuales tanto al añadir como al editar
        // Sanitizamos comas por puntos para evitar errores de persistencia en PHP
        datos.precio_proveedor = (document.getElementById('prodPrecioProveedor').value || '0').replace(',', '.');
        datos.precio_coste = (document.getElementById('prodPrecioCoste').value || '0').replace(',', '.');
        datos.stock_actual = (document.getElementById('prodStock').value || '0').replace(',', '.');
        datos.precio_venta = (document.getElementById('prodPrecioVenta').value || '0').replace(',', '.');
        datos.margen = (document.getElementById('prodMargen').value || '0').replace(',', '.');

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

    // Eventos globales para recálculo de precios
    document.addEventListener('DOMContentLoaded', () => {
        const selIva = document.getElementById('prodIvaTipo');
        const selProv = document.getElementById('prodProveedor');
        if (selIva) selIva.addEventListener('change', calcularTotalDesdeBase);
        if (selProv) selProv.addEventListener('change', calcularTotalDesdeBase);
    });

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

    // Redefinimos applyFilters para que use la carga AJAX
    window.applyFilters = function() {
        if (typeof filtrarProductos === 'function') {
            filtrarProductos();
        }
    };

    function ajustarStockManual(tipo) {
        const id = document.getElementById('prodId').value;
        const nombre = document.getElementById('prodNombre').value;
        const stockActual = parseInt(document.getElementById('prodStock').value) || 0;

        if (!id) return;

        const isAdd = (tipo === 'entrada');
        
        // Configurar Modal
        document.getElementById('ajusteProdId').value = id;
        document.getElementById('ajusteTipo').value = tipo;
        document.getElementById('modalAjusteProdNombre').innerText = nombre;
        document.getElementById('modalAjusteStockActual').innerText = stockActual;
        document.getElementById('ajusteCantidad').value = '';
        document.getElementById('ajusteMotivo').value = '';
        
        const titleEl = document.getElementById('modalAjusteTitle');
        const iconEl = document.getElementById('modalAjusteIcon');
        
        if (isAdd) {
            titleEl.innerText = <?php echo json_encode(L('prod_modal_stock_adj_title_add', true)); ?>;
            iconEl.innerHTML = '<i class="fa-solid fa-circle-plus text-green"></i>';
            iconEl.className = 'fs-24 text-green';
        } else {
            titleEl.innerText = <?php echo json_encode(L('prod_modal_stock_adj_title_remove', true)); ?>;
            iconEl.innerHTML = '<i class="fa-solid fa-circle-minus text-red"></i>';
            iconEl.className = 'fs-24 text-red';
        }

        document.getElementById('modalAjusteStock').style.display = 'flex';
        setTimeout(() => document.getElementById('ajusteCantidad').focus(), 300);
    }

    function cerrarModalAjusteStock() {
        document.getElementById('modalAjusteStock').style.display = 'none';
    }

    async function guardarAjusteStockManual() {
        const id = document.getElementById('ajusteProdId').value;
        const tipo = document.getElementById('ajusteTipo').value;
        const cantidadStr = document.getElementById('ajusteCantidad').value;
        const motivo = document.getElementById('ajusteMotivo').value.trim();
        const stockActual = parseInt(document.getElementById('modalAjusteStockActual').innerText) || 0;

        const cant = parseInt(cantidadStr);
        if (isNaN(cant) || cant <= 0) {
            return showCustomAlert(<?php echo json_encode(L('prod_js_error', true)); ?>, <?php echo json_encode(L('prod_js_qty_error', true)); ?>, 'error');
        }

        if (!motivo) {
            return showCustomAlert(<?php echo json_encode(L('prod_js_error', true)); ?>, <?php echo json_encode(L('prod_js_reason_req', true)); ?>, 'error');
        }

        const isAdd = (tipo === 'entrada');
        const finalCant = isAdd ? cant : -cant;

        // Si es salida y deja stock negativo, avisar
        if (!isAdd && cant > stockActual) {
            showCustomConfirm(
                <?php echo json_encode(L('prod_js_stock_neg_title', true)); ?>,
                <?php echo json_encode(L('prod_js_stock_neg_body', true)); ?>.replace('{cant}', cant).replace('{stock}', stockActual).replace('{res}', stockActual - cant),
                () => ejecutarAjusteStockAPI(id, finalCant, motivo),
                <?php echo json_encode(L('prod_js_stock_neg_btn', true)); ?>,
                'warning'
            );
        } else {
            ejecutarAjusteStockAPI(id, finalCant, motivo);
        }
    }

    async function ejecutarAjusteStockAPI(id, cant, motivo) {
        try {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const resp = await fetch('api/gestionProducto.php', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'x-csrf-token': token 
                },
                body: JSON.stringify({
                    accion: 'ajuste_stock',
                    id: id,
                    cantidad: cant,
                    motivo: motivo
                })
            });
            const r = await resp.json();
            if (r.ok) {
                cerrarModalAjusteStock();
                showCustomAlert(<?php echo json_encode(L('prod_js_success', true)); ?>, <?php echo json_encode(L('prod_toast_stock_adj_success', true)); ?>, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showCustomAlert('Error', r.error || 'Err', 'error');
            }
        } catch (e) {
            showCustomAlert(<?php echo json_encode(L('prod_js_error', true)); ?>, <?php echo json_encode(L('prod_js_connection', true)); ?>, 'error');
        }
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
                    <div id="listaExcepciones" style="display: flex; flex-direction: column !important; gap: 8px; max-height: 180px; overflow-y: auto;">
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
            <button type="button" id="btnAplicarMargen" onclick="confirmarAplicarMargen()" class="btn-save px-24" style="background: var(--accent); color: white;" disabled>
                <i class="fa-solid fa-check"></i> <?php echo L('prod_modal_mass_margin_btn_apply'); ?>
            </button>
        </div>
    </div>
</div>

<!-- MODAL AJUSTE DE PRECIOS MASIVO -->
<div id="modalAjusteMasivo" class="modal-overlay" style="transition: all 0.2s ease;">
    <div class="modal-content" style="max-width: 550px; border-radius: 20px;">
        <div class="modal-header">
            <h2><?php echo L('prod_modal_mass_adj_title'); ?></h2>
            <button class="btn-close-modal" onclick="cerrarModalAjusteMasivo()">&times;</button>
        </div>
        
        <div class="modal-body p-24">
            <div class="p-16 bg-surface2 br-12 border mb-20 d-flex ai-center gap-12">
                <div class="fs-24 text-accent"><i class="fa-solid fa-circle-exclamation"></i></div>
                <p class="fs-12 m-0 text-muted">
                    <?php echo L('prod_modal_mass_adj_important'); ?>
                </p>
            </div>

            <div class="form-group mb-20">
                <label class="form-label font-bold mb-8"><?php echo L('prod_modal_mass_adj_step1'); ?></label>
                <select id="ajusteCategoriaSelect" class="form-input h-44" onchange="previewAjusteMasivo()">
                    <option value="all"><?php echo L('tpv_all'); ?></option>
                    <?php foreach ($aCategorias as $cat) { ?>
                        <option value="<?php echo $cat->getId(); ?>"><?php echo $cat->getNombre(); ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="d-grid grid-2 gap-16 mb-20">
                <div class="form-group">
                    <label class="form-label font-bold mb-8"><?php echo L('prod_modal_mass_adj_step2'); ?></label>
                    <select id="ajusteTipoSelect" class="form-input h-44" onchange="previewAjusteMasivo()">
                        <option value="percent"><?php echo L('prod_modal_mass_adj_type_percent'); ?></option>
                        <option value="amount"><?php echo L('prod_modal_mass_adj_type_amount'); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label font-bold mb-8"><?php echo L('prod_modal_mass_adj_step3'); ?></label>
                    <input type="number" id="ajusteValorInput" class="form-input h-44 font-mono text-center fs-18" placeholder="0.00" step="0.01" oninput="previewAjusteMasivo()">
                </div>
            </div>

            <div class="form-group mb-20">
                <label class="form-label font-bold mb-8 d-flex jc-between ai-center">
                    <span><?php echo L('prod_modal_mass_adj_step4'); ?></span>
                    <span class="badge bg-surface2 text-muted fw-normal px-8 py-2 br-4" id="contadorExcepcionesAjuste">0</span>
                </label>
                <div class="p-16 border br-12 bg-surface2">
                    <div class="search-box mb-12">
                        <i class="fa-solid fa-search"></i>
                        <input type="text" id="buscadorExcepcionesAjuste" placeholder="<?php echo L('prod_modal_mass_adj_search_placeholder'); ?>" oninput="buscarExcepcionesAjuste()" class="form-input">
                    </div>
                    <div id="listaExcepcionesAjuste" style="display: flex; flex-direction: column !important; gap: 8px; max-height: 200px; overflow-y: auto;">
                    </div>
                </div>
            </div>

            <div id="ajustePreviewBox" class="p-16 br-12 border bg-surface mb-20 text-center" style="display:none; border-style: dashed; border-color: var(--accent);">
                <span class="fs-13 text-muted"><?php echo L('prod_modal_mass_adj_preview'); ?></span>
                <div class="fs-24 font-bold text-accent" id="previewCountAjuste">0</div>
            </div>
        </div>
        <div class="modal-footer px-24 pb-24 border-none">
            <button type="button" onclick="cerrarModalAjusteMasivo()" class="btn-cancel"><?php echo L('modal_cancel'); ?></button>
            <button type="button" id="btnAplicarAjuste" onclick="confirmarAplicarAjuste()" class="btn-save px-24" style="background: var(--accent); color: white;" disabled>
                <i class="fa-solid fa-check mr-8"></i> <?php echo L('prod_modal_mass_adj_btn_apply'); ?>
            </button>
        </div>
    </div>
</div>

<!-- MODAL GESTIÓN CATEGORÍAS -->
<div id="modalCategorias" class="modal-overlay-bg">
    <div class="modal-content" style="max-width: 1100px; width: 95%; padding: 0; overflow: hidden; border-radius: 20px; box-shadow: 0 40px 80px rgba(0,0,0,0.45); border: 1px solid var(--border);">
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

            <div class="table-container border br-20 overflow-hidden" style="max-height: 500px; overflow-y: auto; background: var(--surface); box-shadow: var(--shadow-sm);">
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
                                    <div class="d-flex ai-center gap-20">
                                        <div class="p-12 br-12 bg-surface2 text-muted border" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fa-solid fa-folder-open fs-18"></i>
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

<!-- MODAL: AJUSTE MANUAL DE STOCK (COMBINADO) -->
<div id="modalAjusteStock" class="modal-overlay-bg">
    <div class="modal-content" style="max-width: 450px; border-radius: 20px; overflow: hidden;">
        <div class="modal-header">
            <h2 id="modalAjusteTitle">Ajustar Stock</h2>
            <button onclick="cerrarModalAjusteStock()" class="btn-close-modal">&times;</button>
        </div>
        <div class="modal-body p-24">
            <div class="d-flex flex-column gap-24">
                <div id="modalAjusteInfo" class="p-16 br-12 bg-surface2 border d-flex ai-center gap-12">
                    <div id="modalAjusteIcon" class="fs-24"></div>
                    <div>
                        <div class="fs-14 fw-700" id="modalAjusteProdNombre">--</div>
                        <div class="fs-12 text-muted"><?php echo L('prod_modal_label_stock_actual'); ?>: <span id="modalAjusteStockActual" class="fw-700">0</span></div>
                    </div>
                </div>

                <input type="hidden" id="ajusteProdId">
                <input type="hidden" id="ajusteTipo">

                <div class="form-group mb-0">
                    <label class="form-label mb-8"><?php echo L('prod_modal_stock_adj_label_qty'); ?></label>
                    <input type="number" id="ajusteCantidad" class="form-input text-center fs-20 fw-900 font-mono h-56" style="border-radius: 12px; border-width: 2px;" placeholder="0" min="1" step="1">
                </div>

                <div class="form-group mb-0">
                    <label class="form-label mb-8"><?php echo L('prod_modal_stock_adj_label_reason'); ?></label>
                    <textarea id="ajusteMotivo" class="form-input p-16 fs-13" rows="3" placeholder="<?php echo L('prod_modal_stock_adj_placeholder_reason'); ?>" style="border-radius: 12px;"></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer p-20 bg-surface2 d-flex gap-12">
            <button onclick="cerrarModalAjusteStock()" class="btn-cancel flex-1"><?php echo L('modal_cancel'); ?></button>
            <button onclick="guardarAjusteStockManual()" class="btn-save flex-1 bg-accent shadow-sm">
                <i class="fa-solid fa-check"></i> <?php echo L('prod_modal_stock_adj_btn_save'); ?>
            </button>
        </div>
    </div>
</div>

<style>
    #modalAjusteStock .modal-content {
        animation: modalFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    @keyframes modalFadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    #modalAjusteIcon.text-green { color: var(--green); }
    #modalAjusteIcon.text-red { color: var(--red); }
</style>