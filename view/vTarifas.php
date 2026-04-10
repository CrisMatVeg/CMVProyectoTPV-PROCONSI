<?php // Vista de Tarifas de Precios 
?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<div class="main-full p-24">
    <div class="section-header container-wider">
        <div class="section-title">
            <h1><?php echo L('rates_title'); ?></h1>
            <p><?php echo L('rates_subtitle'); ?></p>
        </div>
        <div class="d-flex gap-12">
            <button onclick="abrirModalTarifa()" class="btn-add">
                <i class="fa-solid fa-plus"></i> <?php echo L('rates_btn_add'); ?>
            </button>
            <a href="index.php?irDashboard=1" class="btn-back">
                <?php echo L('rates_btn_back'); ?>
            </a>
        </div>
    </div>

    <!-- TABS PRINCIPALES -->
    <div class="modal-tabs container-wider mb-24" style="background:none; border-bottom: 2px solid var(--border); padding:0;">
        <button type="button" class="tab-btn active" id="btnTabReglas" onclick="switchMainTab('reglas')">
            <i class="fa-solid fa-gears"></i> <?php echo L('rates_tab_rules'); ?>
        </button>
        <button type="button" class="tab-btn" id="btnTabTarifario" onclick="switchMainTab('tarifario')">
            <i class="fa-solid fa-table-list"></i> <?php echo L('rates_tab_global'); ?>
        </button>
    </div>

    <!-- CONTENIDO: REGLAS -->
    <div id="view-reglas" class="main-tab-content">
        <div class="table-container container-wider">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="pl-20" style="width: 40px;"></th>
                    <th><?php echo L('rates_th_name'); ?></th>
                    <th><?php echo L('rates_th_type'); ?></th>
                    <th class="text-right"><?php echo L('rates_th_value'); ?></th>
                    <th><?php echo L('rates_th_scope'); ?></th>
                    <th class="text-center"><?php echo L('rates_th_status'); ?></th>
                    <th class="text-center pr-20"><?php echo L('rates_th_actions'); ?></th>
                </tr>
            </thead>
            <tbody id="tarifasTableBody">
                <?php foreach ($avTarifas['lista'] as $t): ?>
                    <tr data-id="<?php echo $t['id']; ?>">
                        <td class="pl-20">
                            <i class="fa-solid fa-grip-vertical drag-handle"></i>
                        </td>
                        <td><?php echo htmlspecialchars($t['nombre']); ?></td>
                        <td>
                            <span class="status-pill <?php echo $t['tipo'] === 'percent' ? 'status-card' : 'status-cash'; ?>">
                                <?php echo $t['tipo'] === 'percent' ? L('rates_type_percent', true) : L('rates_type_amount', true); ?>
                            </span>
                        </td>
                        <td class="text-right font-mono font-bold">
                            <?php echo $t['tipo'] === 'percent'
                                ? number_format($t['valor'], 2, ',', '.') . ' %'
                                : number_format($t['valor'], 2, ',', '.') . ' €'; ?>
                        </td>
                        <td class="text-center font-mono fs-12">
                            <div class="d-flex flex-column gap-2">
                                <span>
                                    <?php echo date('d/m/Y', strtotime($t['fecha_aplicacion'])); ?>
                                    <?php if (!empty($t['fecha_fin'])): ?>
                                        - <?php echo date('d/m/Y', strtotime($t['fecha_fin'])); ?>
                                    <?php endif; ?>
                                </span>
                                <?php if (!empty($t['dias_semana'])): ?>
                                    <span class="fs-10 text-accent font-bold">
                                        <?php
                                        $mapDias = [1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S', 0 => 'D'];
                                        $dArray = explode(',', $t['dias_semana']);
                                        $labels = array_map(fn($d) => $mapDias[$d], $dArray);
                                        echo L('tpv_days', true) . ': ' . implode(',', $labels);
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-center fs-12">
                            <?php
                            $scopeLabel = L('rates_opt_all', true);
                            if (($t['scope'] ?? 'todos') === 'categoria') {
                                $scopeLabel = L('tpv_category', true) . ': ' . htmlspecialchars($t['categoria'] ?? '-');
                            } elseif (($t['scope'] ?? 'todos') === 'productos') {
                                $scopeLabel = L('rates_opt_manual', true);
                            }
                            echo $scopeLabel;
                            ?>
                        </td>
                        <td class="text-center">
                            <span onclick="toggleTarifa(<?php echo $t['id']; ?>)"
                                class="status-pill cursor-pointer <?php echo $t['activo'] ? 'status-paid' : 'text-muted border-2'; ?>"
                                style="padding: 2px 8px; font-size: 10px; width: fit-content;"
                                title="<?php echo L('modal_change_status', true); ?>">
                                <i class="fa-solid <?php echo $t['activo'] ? 'fa-play' : 'fa-pause'; ?>"></i>
                                <?php echo $t['activo'] ? L('rates_status_active', true) : L('rates_status_paused', true); ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex jc-center gap-8 pr-20">
                                <button onclick='abrirModalTarifa(<?php echo json_encode($t, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' title="<?php echo L('rates_edit_rule'); ?>" class="btn-icon">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button onclick="eliminarTarifa(<?php echo $t['id']; ?>)" title="<?php echo L('modal_delete'); ?>" class="btn-icon text-red">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($avTarifas['lista'])): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="fa-solid fa-scale-balanced"></i>
                                <?php echo L('rates_no_rates'); ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    </div>

    <!-- CONTENIDO: TARIFARIO -->
    <div id="view-tarifario" class="main-tab-content d-none">
        <div class="container-wider">
            <div class="bg-surface p-20 br-12 border shadow-sm mb-20 d-flex ai-center jc-between gap-16">
                <div class="flex-1">
                    <h2 class="fs-18 mb-4"><?php echo L('rates_global_title'); ?></h2>
                    <p class="fs-13 text-muted"><?php echo L('rates_global_subtitle'); ?></p>
                </div>
                <div class="search-input-fancy" style="max-width: 400px;">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="tarifarioSearch" onkeyup="renderTarifario()" placeholder="<?php echo L('rates_search_placeholder'); ?>">
                </div>
            </div>

            <div class="table-container shadow-sm no-border" style="border-radius: 20px;">
                <table class="data-table table-matrix" id="tarifarioTable">
                    <thead>
                        <tr id="tarifarioHeader">
                            <!-- JS Dinámico -->
                        </tr>
                    </thead>
                    <tbody id="tarifarioBody">
                        <!-- JS Dinámico -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<!-- MODAL NUEVA TARIFA -->
<div class="modal-overlay" id="tarifaModal">
    <div class="modal modal-content gap-16 ai-stretch w-modal-lg" style="max-width: 1100px; border-radius: 20px; overflow: hidden; height: auto; max-height: 95vh;">
        <div class="modal-header d-flex flex-column mb-0 p-0">
            <div class="d-flex ai-center jc-center w-100 p-24-32" style="position: relative;">
                <h2 class="m-0 fs-20 fw-700 text-main text-center"><?php echo L('rates_modal_title'); ?></h2>
                <button onclick="cerrarModalTarifa()" class="btn-close-modal" style="position: absolute; right: 24px; top: 50%; transform: translateY(-50%); font-size: 24px; background: transparent; border: none; cursor: pointer;">&times;</button>
            </div>

            <div class="modal-tabs">
                <button type="button" class="tab-btn active" data-tab="general" onclick="switchTabTarifa(this, 'tab-general')">
                    <i class="fa-solid fa-sliders"></i> <?php echo L('rates_tab_general'); ?>
                </button>
                <button type="button" class="tab-btn" data-tab="programacion" onclick="switchTabTarifa(this, 'tab-programacion')">
                    <i class="fa-solid fa-calendar-days"></i> <?php echo L('rates_tab_prog'); ?>
                </button>
                <button type="button" class="tab-btn" data-tab="clientes" onclick="switchTabTarifa(this, 'tab-clientes')">
                    <i class="fa-solid fa-users"></i> <?php echo L('rates_tab_clients'); ?>
                </button>
                <button type="button" class="tab-btn" data-tab="productos" onclick="switchTabTarifa(this, 'tab-productos')">
                    <i class="fa-solid fa-box-open"></i> <?php echo L('rates_tab_products'); ?>
                </button>
            </div>
        </div>
        <form id="tarifaForm" class="modal-body p-24" style="overflow-y: auto; max-height: 70vh;">
            <input type="hidden" id="tarifaId">

            <div class="alert-premium premium-warning mb-24" style="border-left: 4px solid var(--accent); background: rgba(var(--accent-rgb), 0.05);">
                <div class="alert-icon-wrap"><i class="fa-solid fa-circle-question fs-20"></i></div>
                <div class="alert-content">
                    <span class="alert-title font-bold"><?php echo L('rates_info_title'); ?></span>
                    <p class="alert-desc fs-12 m-0 mt-4">
                        <?php echo L('rates_info_replace'); ?><br>
                        <?php echo L('rates_info_coexist'); ?>
                    </p>
                </div>
            </div>

            <!-- TAB: GENERAL -->
            <div id="tab-general" class="tab-pane active">
                <div class="form-group">
                    <label class="form-label fw-600"><?php echo L('rates_label_name'); ?></label>
                    <input type="text" id="tarifaNombre" class="form-input" placeholder="<?php echo L('rates_name_placeholder'); ?>">
                    <span class="form-error" id="err-nombre"></span>
                </div>
 
                <div class="d-grid grid-3 gap-16">
                    <div class="form-group">
                        <label class="form-label fs-11 tt-uppercase fw-600"><?php echo L('rates_label_type'); ?></label>
                        <select id="tarifaTipo" class="form-input">
                            <option value="percent"><?php echo L('rates_type_percent'); ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label fs-11 tt-uppercase fw-600"><?php echo L('rates_label_value'); ?></label>
                        <input type="number" id="tarifaValor" class="form-input text-right font-mono" step="0.01">
                        <span class="form-error" id="err-valor"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label fs-11 tt-uppercase fw-600"><?php echo L('rates_label_priority'); ?></label>
                        <input type="number" id="tarifaPrioridad" class="form-input text-right" value="0">
                    </div>
                </div>

                <div class="alert-premium premium-info mt-24">
                    <div class="alert-icon-wrap">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>
                    <div class="alert-content">
                        <span class="alert-title text-uppercase"><?php echo L('rates_info_title'); ?></span>
                        <span class="alert-desc"><?php echo L('rates_info_help'); ?></span>
                    </div>
                </div>
            </div>

            <!-- TAB: PROGRAMACIÓN -->
            <div id="tab-programacion" class="tab-pane">
                <div class="bg-surface2 p-20 br-12 border mb-20">
                    <label class="form-label fs-12 tt-uppercase fw-700 mb-16 d-flex ai-center gap-8">
                        <i class="fa-solid fa-calendar text-accent"></i> <?php echo L('rates_label_date_range'); ?>
                    </label>
                    <div class="d-grid grid-2 gap-16">
                        <div class="form-group">
                            <label class="form-label fs-11"><?php echo L('rates_label_start_date'); ?></label>
                            <input type="date" id="tarifaFecha" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label fs-11"><?php echo L('rates_label_end_date'); ?></label>
                            <input type="date" id="tarifaFechaFin" class="form-input">
                        </div>
                    </div>
                </div>

                <div class="bg-surface2 p-20 br-12 border mb-20">
                    <label class="form-label fs-12 tt-uppercase fw-700 mb-16 d-flex ai-center gap-8">
                        <i class="fa-solid fa-calendar-day text-accent"></i> <?php echo L('rates_label_weekdays'); ?>
                    </label>
                    <div class="d-flex flex-wrap gap-12">
                        <?php
                        $dias = [
                            1 => L('day_mon', true),
                            2 => L('day_tue', true),
                            3 => L('day_wed', true),
                            4 => L('day_thu', true),
                            5 => L('day_fri', true),
                            6 => L('day_sat', true),
                            0 => L('day_sun', true)
                        ];
                        foreach ($dias as $val => $label): ?>
                            <label class="method-option border br-8 px-12 py-8 d-flex ai-center gap-8 cp transition" style="min-width: 100px;">
                                <input type="checkbox" class="tarifa-dia-checkbox" value="<?php echo $val; ?>" style="width: 16px; height: 16px;">
                                <span class="fs-12"><?php echo $label; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="fs-11 text-muted mt-12"><?php echo L('rates_weekdays_help'); ?></p>
                </div>

                <div class="bg-surface2 p-20 br-12 border">
                    <label class="form-label fs-12 tt-uppercase fw-700 mb-16 d-flex ai-center gap-8">
                        <i class="fa-solid fa-clock text-accent"></i> <?php echo L('rates_label_time_range'); ?>
                    </label>
                    <div class="d-grid grid-2 gap-16">
                        <div class="form-group">
                            <label class="form-label fs-11"><?php echo L('rates_label_start_time'); ?></label>
                            <input type="time" id="tarifaHoraInicio" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label fs-11"><?php echo L('rates_label_end_time'); ?></label>
                            <input type="time" id="tarifaHoraFin" class="form-input">
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: CLIENTES -->
            <div id="tab-clientes" class="tab-pane">
                <div class="alert-premium premium-info mb-24">
                    <div class="alert-icon-wrap">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>
                    <div class="alert-content">
                        <span class="alert-desc"><?php echo L('rates_clients_help'); ?></span>
                    </div>
                </div>

                <div class="d-grid grid-2 gap-24">
                    <!-- Columna Izquierda: Segmentos/Roles -->
                    <div class="flex-column gap-12">
                        <label class="form-label fw-600 mb-0"><?php echo L('rates_label_segments'); ?></label>

                        <div class="roles-selection-container border br-12 overflow-hidden bg-white">
                            <div class="checkbox-list p-4" style="max-height: 250px; overflow-y: auto;" id="listadoRolesTarifa">
                                <?php
                                // Roles predefinidos sugeridos si no están en BD o para asegurar "General"
                                $rolesSugeridos = ['general', 'particular', 'socio', 'empresa', 'mayorista'];
                                $rolesBD = array_map(fn($r) => strtolower($r['nombre']), $avTarifas['roles']);
                                $rolesFinales = array_unique(array_merge($rolesSugeridos, $rolesBD));

                                foreach ($rolesFinales as $rol): ?>
                                    <label class="checkbox-item d-flex ai-center gap-12 p-8-16 cp hover-bg-surface2 br-8 transition role-row"
                                        data-name="<?php echo htmlspecialchars($rol); ?>">
                                        <input type="checkbox" class="tarifa-rol-checkbox" value="<?php echo htmlspecialchars($rol); ?>">
                                        <div class="fs-13 text-capitalize"><?php echo htmlspecialchars($rol); ?></div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Cliente Individual -->
                    <div class="flex-column gap-12">
                        <label class="form-label fw-600 mb-0"><?php echo L('rates_label_individual'); ?></label>
                        <div class="p-16 br-12 border bg-surface2 flex-column gap-12">
                            <div class="search-box-container mb-4">
                                <div class="search-input-fancy">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <input type="text" id="tarifaBusquedaIndiv" placeholder="<?php echo L('rates_search_clients'); ?>" onkeyup="filtrarClientesTarifa()">
                                </div>
                            </div>

                            <div class="clients-selection-container border br-12 overflow-hidden bg-white mt-4">
                                <div class="d-flex ai-center jc-between p-12-24 border-bottom bg-surface1">
                                    <span class="fs-12 tt-uppercase fw-800 text-accent ls-1" id="countSelectedClients">0 <?php echo L('rates_selected'); ?></span>
                                    <button type="button" class="btn-clean-tarifa px-16 py-8 fs-13 fw-600 transition" onclick="if(window.unselectAllClientsTarifa) unselectAllClientsTarifa(); else alert('Error: JS not loaded');">
                                        <i class="fa-solid fa-eraser mr-4"></i> <?php echo L('rates_btn_clean'); ?>
                                    </button>
                                </div>
                                <div class="checkbox-list p-4" style="max-height: 250px; overflow-y: auto;" id="listadoClientesTarifa">
                                    <?php foreach ($avTarifas['clientes'] as $c): ?>
                                        <label class="checkbox-item d-flex ai-center gap-12 p-10-16 cp hover-bg-surface2 br-8 transition client-row"
                                            data-name="<?php echo htmlspecialchars(strtolower(($c['nombre'] ?? '') . ' ' . ($c['apellidos'] ?? ''))); ?>"
                                            data-id="<?php echo $c['id']; ?>">
                                            <input type="checkbox" class="tarifa-cliente-checkbox" value="<?php echo $c['id']; ?>" onchange="updateClientCount()">
                                            <div class="flex-1">
                                                <div class="fs-13 fw-600"><?php echo htmlspecialchars(($c['nombre'] ?? '') . ' ' . ($c['apellidos'] ?? '')); ?></div>
                                                <div class="fs-11 text-muted"><?php echo htmlspecialchars($c['dni'] ?? $c['cif'] ?? '-'); ?></div>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <p class="fs-11 text-muted mt-4 italic"><?php echo L('rates_individual_help'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: PRODUCTOS -->
            <div id="tab-productos" class="tab-pane">
                <div class="form-group mb-20">
                    <label class="form-label fw-600 mb-8"><?php echo L('rates_label_scope'); ?></label>
                    <select id="tarifaScope" class="form-input" onchange="onScopeChangeTarifa()">
                        <option value="todos"><?php echo L('rates_opt_all'); ?></option>
                        <option value="categoria"><?php echo L('rates_opt_cat'); ?></option>
                        <option value="productos"><?php echo L('rates_opt_manual'); ?></option>
                    </select>
                </div>

                <div class="form-group mb-0" id="tarifaCategoriaWrapper" style="display:none;">
                    <label class="form-label fw-600 mb-8"><?php echo L('rates_label_select_cat'); ?></label>
                    <select id="tarifaCategoria" class="form-input">
                        <option value=""><?php echo L('rates_opt_select_cat'); ?></option>
                        <?php foreach ($avTarifas['categorias'] as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['codigo']); ?>">
                                <?php echo htmlspecialchars($cat['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="tarifaProductosWrapper" style="display:none;" class="flex-column gap-16">
                    <div class="search-box-container mb-16">
                        <div class="search-input-fancy">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="tarifaBusquedaProd" placeholder="<?php echo L('rates_search_prod'); ?>" onkeyup="filtrarProductosTarifa()">
                        </div>
                    </div>

                    <div class="products-selection-container border br-12 overflow-hidden bg-white">
                        <div class="d-flex ai-center jc-between p-16-24 border-bottom bg-surface1 gap-12">
                            <span class="fs-12 tt-uppercase fw-800 text-accent ls-1" id="countSelectedProd">0 <?php echo L('rates_selected'); ?></span>
                            <button type="button" class="btn-clean-tarifa px-16 py-8 fs-13 fw-600 transition" onclick="if(window.unselectAllProductsTarifa) unselectAllProductsTarifa(); else alert('Error: JS not loaded');">
                                <i class="fa-solid fa-eraser mr-4"></i> <?php echo L('rates_btn_clean'); ?>
                            </button>
                        </div>
                        <div class="checkbox-list p-4" style="max-height: 250px; overflow-y: auto;" id="listadoProductosTarifa">
                            <?php foreach ($avTarifas['productos'] as $p): ?>
                                <label class="checkbox-item d-flex ai-center gap-12 p-10-16 cp hover-bg-surface2 br-8 transition product-row"
                                    data-name="<?php echo htmlspecialchars(strtolower($p->getNombre())); ?>">
                                    <input type="checkbox" class="tarifa-prod-checkbox" value="<?php echo $p->getId(); ?>" onchange="updateProdCount()">
                                    <div class="flex-1">
                                        <div class="fs-13 fw-600"><?php echo htmlspecialchars($p->getNombre()); ?></div>
                                    </div>
                                    <div class="fs-12 font-mono text-muted"><?php echo number_format($p->getPrecioVenta(), 2, ',', '.'); ?> €</div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <div class="modal-footer full-width">
            <button onclick="cerrarModalTarifa()" class="btn-cancel"><?php echo L('modal_cancel'); ?></button>
            <button onclick="guardarTarifa()" class="btn-save"><?php echo L('rates_btn_save'); ?></button>
        </div>
    </div>
</div>

<script>
    function limpiarErroresTarifa() {
        document.querySelectorAll('#tarifaModal .form-error').forEach(el => el.innerText = '');
    }

    function onScopeChangeTarifa() {
        const scope = document.getElementById('tarifaScope').value;
        const catWrapper = document.getElementById('tarifaCategoriaWrapper');
        const prodWrapper = document.getElementById('tarifaProductosWrapper');
        catWrapper.style.display = scope === 'categoria' ? 'block' : 'none';
        prodWrapper.style.display = scope === 'productos' ? 'flex' : 'none';
        if (scope === 'productos') updateProdCount();
    }

    function switchTabTarifa(btn, tabId) {
        // Desactivar todos los botones de pestañas
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        // Activar el botón clicado
        btn.classList.add('active');

        // Ocultar todas las pestañas
        document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        // Mostrar la pestaña seleccionada
        document.getElementById(tabId).classList.add('active');
    }

    function filtrarProductosTarifa() {
        const busqueda = document.getElementById('tarifaBusquedaProd').value.toLowerCase();
        const rows = document.querySelectorAll('#listadoProductosTarifa .product-row');

        rows.forEach(row => {
            const nombre = row.dataset.name;
            if (nombre.includes(busqueda)) {
                row.style.display = 'flex';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function filtrarClientesTarifa() {
        const busqueda = document.getElementById('tarifaBusquedaIndiv').value.toLowerCase();
        const rows = document.querySelectorAll('#listadoClientesTarifa .client-row');

        rows.forEach(row => {
            const nombre = row.dataset.name;
            row.style.display = nombre.includes(busqueda) ? 'flex' : 'none';
        });
    }

    function updateClientCount() {
        const count = document.querySelectorAll('.tarifa-cliente-checkbox:checked').length;
        const el = document.getElementById('countSelectedClients');
        if (el) el.innerText = count + ' ' + (count === 1 ? "<?php echo L('selected'); ?>" : "<?php echo L('selected'); ?>");
    }

    window.unselectAllClientsTarifa = function() {
        console.log("Limpiar clientes triggered");
        const cbs = document.querySelectorAll('.tarifa-cliente-checkbox');
        cbs.forEach(cb => {
            if (cb.checked) {
                cb.checked = false;
                if (typeof Event === 'function') cb.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }
        });
        updateClientCount();

        // Limpiar también la búsqueda
        const inputBusqueda = document.getElementById('tarifaBusquedaIndiv');
        if (inputBusqueda) {
            inputBusqueda.value = '';
            filtrarClientesTarifa();
        }
    };

    window.unselectAllProductsTarifa = function() {
        console.log("Limpiar productos triggered");
        const cbs = document.querySelectorAll('.tarifa-prod-checkbox');
        cbs.forEach(cb => {
            if (cb.checked) {
                cb.checked = false;
                if (typeof Event === 'function') cb.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }
        });
        updateProdCount();

        // Limpiar también la búsqueda
        const inputBusquedaProd = document.getElementById('tarifaBusquedaProd');
        if (inputBusquedaProd) {
            inputBusquedaProd.value = '';
            filtrarProductosTarifa();
        }
    };

    function filtrarRolesTarifa() {
        const input = document.getElementById('tarifaBusquedaRoles');
        if (!input) return;
        const busqueda = input.value.toLowerCase();
        const rows = document.querySelectorAll('#listadoRolesTarifa .role-row');

        rows.forEach(row => {
            const nombre = row.dataset.name;
            if (nombre.includes(busqueda)) {
                row.style.display = 'flex';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function updateProdCount() {
        const count = document.querySelectorAll('.tarifa-prod-checkbox:checked').length;
        const el = document.getElementById('countSelectedProd');
        if (el) el.innerText = count + ' ' + (count === 1 ? "<?php echo L('selected'); ?>" : "<?php echo L('selected'); ?>");
    }

    // Eliminamos la función duplicada o la mantenemos para compatibilidad si se llama desde otro sitio
    /*
    function unselectAllProductsTarifa() {
        document.querySelectorAll('.tarifa-prod-checkbox').forEach(cb => {
            cb.checked = false;
        });
        updateProdCount();
    }
    */

    function abrirModalTarifa(t = null) {
        limpiarErroresTarifa();
        if (t) {
            document.getElementById('tarifaId').value = t.id;
            document.getElementById('tarifaNombre').value = t.nombre;
            document.getElementById('tarifaTipo').value = t.tipo;
            document.getElementById('tarifaValor').value = t.valor;
            document.getElementById('tarifaPrioridad').value = t.prioridad || 0;
            document.getElementById('tarifaFecha').value = t.fecha_aplicacion;
            document.getElementById('tarifaFechaFin').value = t.fecha_fin || '';
            document.getElementById('tarifaHoraInicio').value = t.hora_inicio || '';
            document.getElementById('tarifaHoraFin').value = t.hora_fin || '';

            // Días de la semana
            const diasRule = t.dias_semana ? t.dias_semana.split(',').map(Number) : [];
            document.querySelectorAll('.tarifa-dia-checkbox').forEach(cb => {
                cb.checked = diasRule.includes(parseInt(cb.value));
            });
            document.querySelectorAll('.tarifa-cliente-checkbox').forEach(cb => {
                cb.checked = false;
                if (t.cliente_ids) {
                    try {
                        const ids = JSON.parse(t.cliente_ids);
                        if (Array.isArray(ids) && ids.includes(parseInt(cb.value))) {
                            cb.checked = true;
                        }
                    } catch (e) {}
                }
            });

            // Roles Segmento
            const roles = t.roles_segmento ? t.roles_segmento.split(',') : [];
            document.querySelectorAll('.tarifa-rol-checkbox').forEach(cb => {
                cb.checked = roles.includes(cb.value);
            });

            document.getElementById('tarifaScope').value = t.scope || 'todos';
            document.getElementById('tarifaCategoria').value = t.categoria || '';

            // Checkboxes de productos
            document.querySelectorAll('.tarifa-prod-checkbox').forEach(cb => {
                cb.checked = false;
                if (t.producto_ids) {
                    try {
                        const ids = JSON.parse(t.producto_ids);
                        if (Array.isArray(ids) && ids.includes(parseInt(cb.value))) {
                            cb.checked = true;
                        }
                    } catch (e) {}
                }
            });
            document.querySelector('.modal-header h2').innerText = "<?php echo L('rates_edit_rule'); ?>";
        } else {
            document.getElementById('tarifaId').value = '';
            document.getElementById('tarifaNombre').value = '';
            document.getElementById('tarifaTipo').value = 'percent';
            document.getElementById('tarifaValor').value = '';
            document.getElementById('tarifaPrioridad').value = '0';
            document.getElementById('tarifaFecha').value = '';
            document.getElementById('tarifaFechaFin').value = '';
            document.getElementById('tarifaHoraInicio').value = '';
            document.getElementById('tarifaHoraFin').value = '';
            document.querySelectorAll('.tarifa-dia-checkbox').forEach(cb => cb.checked = false);
            document.querySelectorAll('.tarifa-cliente-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('tarifaScope').value = 'todos';
            document.getElementById('tarifaCategoria').value = '';
            document.querySelectorAll('.tarifa-rol-checkbox').forEach(cb => cb.checked = false);
            document.querySelectorAll('.tarifa-prod-checkbox').forEach(cb => cb.checked = false);
            document.querySelector('.modal-header h2').innerText = "<?php echo L('rates_new_rule'); ?>";
        }

        onScopeChangeTarifa();
        updateProdCount();
        updateClientCount();

        // Resetear a la primera pestaña
        const firstTabBtn = document.querySelector('.tab-btn[data-tab="general"]');
        if (firstTabBtn) switchTabTarifa(firstTabBtn, 'tab-general');

        document.getElementById('tarifaModal').classList.add('visible');
    }

    function cerrarModalTarifa() {
        document.getElementById('tarifaModal').classList.remove('visible');
        limpiarErroresTarifa();
    }

    async function guardarTarifa() {
        limpiarErroresTarifa();
        const nombre = document.getElementById('tarifaNombre').value.trim();
        const valor = parseFloat(document.getElementById('tarifaValor').value);
        const tipo = document.getElementById('tarifaTipo').value;
        const id = document.getElementById('tarifaId').value;
        const prioridad = document.getElementById('tarifaPrioridad').value;
        
        // RESTRICCIÓN: Al menos un filtro
        const fechaIni = document.getElementById('tarifaFecha').value;
        const fechaFin = document.getElementById('tarifaFechaFin').value;
        const horaIni = document.getElementById('tarifaHoraInicio').value;
        const horaFin = document.getElementById('tarifaHoraFin').value;
        const roles = Array.from(document.querySelectorAll('.tarifa-rol-checkbox:checked')).map(cb => cb.value);
        const clientes = Array.from(document.querySelectorAll('.tarifa-cliente-checkbox:checked')).map(cb => cb.value);
        const dias = Array.from(document.querySelectorAll('.tarifa-dia-checkbox:checked')).map(cb => cb.value);

        const hasFilter = (fechaIni || fechaFin || horaIni || horaFin || roles.length > 0 || clientes.length > 0 || dias.length > 0);

        if (!nombre) {
            document.getElementById('err-nombre').innerText = 'El nombre es obligatorio';
            return;
        }
        if (isNaN(valor) || valor === 0) {
            document.getElementById('err-valor').innerText = 'El valor no puede ser 0';
            return;
        }

        if (!hasFilter) {
            showCustomAlert('<?php echo L('modal_alert_title') ?>', '<?php echo L('rates_js_insufficient_criteria_body') ?>', 'warning');
            return;
        }

        const scope = document.getElementById('tarifaScope').value;
        const categoria = document.getElementById('tarifaCategoria').value;
        const selectedProds = Array.from(document.querySelectorAll('.tarifa-prod-checkbox:checked')).map(cb => cb.value);

        const payload = {
            accion: id ? 'editar' : 'añadir',
            id,
            nombre, valor, tipo, prioridad,
            scope,
            categoria,
            producto_ids: selectedProds,
            tipo_cliente: roles.join(','),
            roles_segmento: clientes.join(','),
            dias_semana: dias.join(','),
            hora_inicio: horaIni,
            hora_fin: horaFin,
            fecha_aplicacion: fechaIni,
            fecha_fin: fechaFin
        };

        try {
            const resp = await fetch('api/gestionTarifa.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const r = await resp.json();
            if (r.ok) {
                location.reload();
            } else {
                showCustomAlert('Error', r.error || 'No se pudo guardar la tarifa', 'error');
            }
        } catch (e) {
            showCustomAlert('Error', 'Error de conexión con el servidor', 'error');
        }
    }

    function aplicarTarifa(id) {
        showCustomAlert('<?php echo L('modal_alert_title') ?>', '<?php echo L('rates_js_mass_apply_disabled_body') ?>', 'info');
    }
    async function toggleTarifa(id) {
        if (!id) return;
        try {
            const resp = await fetch('api/gestionTarifa.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    accion: 'toggle',
                    id
                }),
            });
            const r = await resp.json();
            if (r.ok) location.reload();
            else alert("<?php echo L('error'); ?>: " + (r.error || "<?php echo L('modal_error_change_status'); ?>"));
        } catch (e) {
            console.error(e);
            alert("<?php echo L('error_server_connection'); ?>");
        }
    }

    async function eliminarTarifa(id) {
        if (!id) return;
        showCustomConfirm(
            "<?php echo L('rates_confirm_del_title'); ?>",
            "<?php echo L('rates_confirm_del_msg'); ?>",
            async () => {
                    try {
                        const resp = await fetch('api/gestionTarifa.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                accion: 'eliminar',
                                id
                            }),
                        });
                        const r = await resp.json();
                        if (r.ok) location.reload();
                        else showCustomAlert("<?php echo L('error'); ?>", r.error || "<?php echo L('modal_error_delete'); ?>", 'error');
                    } catch (e) {
                        console.error(e);
                        showCustomAlert("<?php echo L('error'); ?>", "<?php echo L('error_server_connection'); ?>", 'error');
                    }
                },
                "<?php echo L('modal_delete'); ?>",
                'danger'
        );
    }

    // --- LÓGICA TARIFARIO GLOBAL ---
    const PRODUCTOS_DATA = <?php echo json_encode($avTarifas['productos']); ?>;
    const TARIFAS_DATA = <?php echo json_encode($avTarifas['lista']); ?>;

    function switchMainTab(tab) {
        document.querySelectorAll('.main-tab-content').forEach(el => el.classList.add('d-none'));
        document.querySelectorAll('.modal-tabs .tab-btn').forEach(el => el.classList.remove('active'));
        
        if (tab === 'reglas') {
            document.getElementById('view-reglas').classList.remove('d-none');
            document.getElementById('btnTabReglas').classList.add('active');
        } else {
            document.getElementById('view-tarifario').classList.remove('d-none');
            document.getElementById('btnTabTarifario').classList.add('active');
            renderTarifario();
        }
    }

    function renderTarifario() {
        const query = document.getElementById('tarifarioSearch').value.toLowerCase();
        const header = document.getElementById('tarifarioHeader');
        const tbody = document.getElementById('tarifarioBody');
        
        // 1. Identificamos tarifas activas para las columnas
        const activeRates = TARIFAS_DATA.filter(t => t.activo);
        
        // 2. Construimos la cabecera dinámica
        let headerHtml = `
            <th class="pl-24" style="width: 300px;">${<?php echo json_encode(L('rates_th_product', true)); ?>}</th>
            <th class="text-center matrix-col-base" style="width: 120px; background: rgba(var(--accent-rgb), 0.02);">General</th>
        `;
        
        activeRates.forEach((t, i) => {
            const colClass = `matrix-col-${(i % 5) + 1}`;
            headerHtml += `<th class="text-center ${colClass}" style="width: 140px;">${t.nombre}</th>`;
        });
        header.innerHTML = headerHtml;

        // 3. Renderizamos las filas
        tbody.innerHTML = '';
        const now = new Date();
        const today = now.toISOString().split('T')[0];
        const dayOfWeek = now.getDay();
        const currentTime = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');

        PRODUCTOS_DATA.forEach(p => {
            // Filtro de búsqueda
            if (query && !p.nombre.toLowerCase().includes(query) && !p.categoria.toLowerCase().includes(query)) {
                return;
            }

            const basePrice = parseFloat(p.precio_venta || 0);
            
            let rowHtml = `
                <td class="pl-24">
                    <div class="matrix-product-info">
                        <span class="fw-700 fs-14 text-main">${p.nombre}</span>
                        <span class="fs-11 text-muted tt-uppercase ls-1">${p.categoria || 'S/C'}</span>
                    </div>
                </td>
                <td class="text-center bg-light-col">
                    <span class="matrix-price matrix-col-base">${basePrice.toFixed(2)}€</span>
                </td>
            `;

            // Celdas para cada tarifa
            activeRates.forEach((t, i) => {
                const colClass = `matrix-col-${(i % 5) + 1}`;
                let appliedPrice = basePrice;
                let isApplicable = true;

                // --- Lógica de Aplicabilidad (Scope) ---
                if (t.scope === 'categoria' && p.categoria !== t.categoria) isApplicable = false;
                if (t.scope === 'productos') {
                    try {
                        const ids = JSON.parse(t.producto_ids || '[]');
                        if (!ids.includes(p.id)) isApplicable = false;
                    } catch(e) { isApplicable = false; }
                }

                // Excluidos
                if (isApplicable && t.excluidos) {
                    try {
                        const excl = JSON.parse(t.excluidos);
                        if (excl.includes(p.id)) isApplicable = false;
                    } catch(e) {}
                }

                // --- Renderizado de Celda ---
                if (isApplicable) {
                    const val = parseFloat(t.valor);
                    if (t.tipo === 'percent') appliedPrice *= (1 + val/100);
                    else appliedPrice += val;
                    
                    rowHtml += `
                        <td class="text-center">
                            <span class="matrix-price ${colClass}">${appliedPrice.toFixed(2)}€</span>
                        </td>
                    `;
                } else {
                    rowHtml += `
                        <td class="text-center">
                            <span class="matrix-dash">—</span>
                        </td>
                    `;
                }
            });

            const tr = document.createElement('tr');
            tr.innerHTML = rowHtml;
            tbody.appendChild(tr);
        });

        if (tbody.innerHTML === '') {
            tbody.innerHTML = `<tr><td colspan="${activeRates.length + 2}" class="text-center p-40 text-muted fs-14 italic">${<?php echo json_encode(L('rates_no_products_found', true)); ?>}</td></tr>`;
        }
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const el = document.getElementById('tarifasTableBody');
        if (!el) return;

        Sortable.create(el, {
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'sortable-ghost',
            onEnd: async function() {
                const ids = Array.from(el.querySelectorAll('tr[data-id]')).map(tr => tr.dataset.id);
                try {
                    const resp = await fetch('api/gestionTarifa.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            accion: 'reordenar',
                            ids
                        }),
                    });
                    const r = await resp.json();
                    if (!r.ok) {
                        alert("<?php echo L('rates_error_save_order'); ?>: " + (r.error || "<?php echo L('rates_unknown_error'); ?>"));
                        location.reload();
                    }
                } catch (e) {
                    console.error(e);
                    alert("<?php echo L('rates_error_reorder_connection'); ?>");
                    location.reload();
                }
            }
        });
    });
</script>

<style>
    .drag-handle {
        cursor: grab;
        color: var(--text-muted);
        padding-right: 12px;
    }

    .drag-handle:active {
        cursor: grabbing;
    }

    .sortable-ghost {
        opacity: 0.4;
        background: var(--surface2) !important;
    }

    /* Utilitarios */
    .text-green { color: #10b981 !important; }
    .text-red { color: #ef4444 !important; }
    .d-none { display: none !important; }
    .fw-600 { font-weight: 600; }
    .fw-700 { font-weight: 700; }
    .italic { font-style: italic; }

    /* Estilos Premium para Segmentación */
    select[multiple] {
        padding: 8px;
        background: var(--surface1);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        color: var(--text-main);
        transition: border-color 0.2s;
    }

    select[multiple]:focus {
        border-color: var(--accent-primary);
        outline: none;
    }

    select[multiple] option {
        padding: 10px 14px;
        margin-bottom: 4px;
        border-radius: 6px;
        cursor: pointer;
    }

    select[multiple] option:checked {
        background: var(--accent-primary) !important;
        color: white;
    }

    .alert {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border-radius: 12px;
        font-size: 13px;
        line-height: 1.5;
    }

    .alert-info {
        background: rgba(var(--accent-primary-rgb, 59, 130, 246), 0.1);
        border: 1px solid rgba(var(--accent-primary-rgb, 59, 130, 246), 0.2);
        color: var(--accent-primary);
    }

    .alert-warning {
        background: rgba(251, 191, 36, 0.1);
        border: 1px solid rgba(251, 191, 36, 0.2);
        color: #b45309;
    }

    /* Modal Tabs Styles */
    .modal-tabs {
        display: flex;
        background: var(--surface2);
        padding: 0 20px;
        border-bottom: 1px solid var(--border);
        gap: 8px;
    }

    .tab-btn {
        background: transparent;
        border: none;
        padding: 14px 20px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-muted);
        cursor: pointer;
        position: relative;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        border-bottom: 3px solid transparent;
    }

    .tab-btn i {
        font-size: 14px;
        opacity: 0.7;
    }

    .tab-btn:hover {
        color: var(--text);
        background: rgba(0, 0, 0, 0.02);
    }

    .tab-btn.active {
        color: var(--accent);
        border-bottom-color: var(--accent);
    }

    .tab-btn.active i {
        opacity: 1;
    }

    .tab-pane {
        display: none;
        animation: fadeIn 0.3s ease;
    }

    .tab-pane.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(5px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Reusing method-option style for checkboxes */
    .method-option {
        background: var(--surface1);
        transition: all 0.2s ease;
    }

    .method-option:hover {
        border-color: var(--accent) !important;
        background: rgba(var(--accent-rgb), 0.05);
    }

    .method-option:has(input:checked) {
        border-color: var(--accent) !important;
        background: var(--accent-light) !important;
        color: var(--accent);
        font-weight: 600;
    }

    .product-row:hover {
        background: var(--surface2);
    }

    .product-row:has(input:checked),
    .client-row:has(input:checked) {
        background: var(--accent-light);
        border-color: var(--accent);
    }

    /* Roles Selection Styles */
    .roles-selection-container {
        border: 1px solid var(--border);
        background: var(--white);
    }

    .role-row:hover {
        background: var(--surface2);
    }

    .role-row:has(input:checked) {
        background: var(--accent-light);
        border-color: var(--accent);
        font-weight: 600;
        color: var(--accent);
    }

    /* --- TARIFARIO MATRIX STYLES --- */
    #view-tarifario .table-container {
        overflow-x: auto;
        width: 100%;
    }

    .table-matrix {
        min-width: 800px; /* Asegura un ancho mínimo para forzar scroll si hay pocas tarifas pero pantalla pequeña */
    }

    .table-matrix thead th {
        background: var(--surface);
        padding: 20px 16px;
        font-weight: 700;
        font-size: 14px;
        border-bottom: 2px solid var(--border);
        white-space: nowrap;
    }
    
    .table-matrix tbody td {
        padding: 16px;
        vertical-align: middle;
        border-bottom: 1.5px solid var(--border);
        height: 70px;
    }

    .matrix-price {
        font-family: var(--font-main);
        font-weight: 700;
        font-size: 15px;
        transition: transform 0.2s;
    }
    
    .matrix-price:hover {
        transform: scale(1.05);
    }

    .matrix-dash {
        color: var(--text-muted);
        opacity: 0.3;
        font-weight: 400;
    }

    .matrix-product-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .matrix-col-base { color: var(--accent) !important; }
    .matrix-col-1 { color: #10b981 !important; } /* Green */
    .matrix-col-2 { color: #f59e0b !important; } /* Orange */
    .matrix-col-3 { color: #8b5cf6 !important; } /* Purple */
    .matrix-col-4 { color: #db2777 !important; } /* Pink */
    .matrix-col-5 { color: #06b6d4 !important; } /* Cyan */
    
    .bg-light-col { background: rgba(0,0,0,0.015); }

    /* CSS v2 Styling updates */
    .hover-border-accent:hover {
        border-color: var(--accent) !important;
    }

    .focus-outline-none:focus {
        outline: none !important;
    }

    .shadow-sm {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .text-main {
        color: var(--text-main, #222);
    }

    .btn-clean-tarifa {
        background: rgba(var(--accent-rgb, 59, 130, 246), 0.05);
        color: var(--accent);
        border: 1px solid rgba(var(--accent-rgb, 59, 130, 246), 0.15);
        border-radius: 8px;
        cursor: pointer;
        padding: 8px 16px;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .btn-clean-tarifa:hover {
        background: var(--accent);
        color: white;
        border-color: var(--accent);
    }

    .search-input-group {
        padding: 12px 20px;
        height: 52px;
        box-sizing: border-box;
    }

    .search-input-group input {
        height: 100%;
        font-size: 15px;
        flex: 1;
        min-width: 0;
        border: none !important;
        outline: none !important;
        background: transparent !important;
    }

    .checkbox-item {
        padding: 12px 16px !important;
        margin-bottom: 2px;
    }

    .ls-1 {
        letter-spacing: 0.5px;
    }

    /* Modal spacing and layout */
    .w-modal-lg {
        width: 90vw !important;
        max-width: 1000px !important;
    }

    #tarifaModal h2 {
        white-space: nowrap;
        margin-right: 0px;
    }

    @media (max-width: 768px) {
        #tarifaModal h2 {
            white-space: normal;
            font-size: 16px;
        }
    }
</style>