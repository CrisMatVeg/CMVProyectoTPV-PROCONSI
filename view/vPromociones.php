<?php // Vista de Promociones 
?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    // Helpers para evitar TypeErrors si algún elemento no existe
    const setVal = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.value = val;
    };
    const setChecked = (id, bool) => {
        const el = document.getElementById(id);
        if (el) el.checked = !!bool;
    };
    const setText = (id, txt) => {
        const el = document.getElementById(id);
        if (el) el.innerText = txt;
    };

    window.limpiarErroresPromo = function() {
        const errors = document.querySelectorAll('#promoModal .form-error');
        if (errors) errors.forEach(el => el.innerText = '');
    };

    window.togglePromoFields = function() {
        const tipoEl = document.getElementById('promoTipo');
        if (!tipoEl) return;
        const tipo = tipoEl.value;
        const fieldsBundle = document.getElementById('fieldsBundle');
        const fieldsGeneral = document.getElementById('fieldsGeneral');
        const blockPayQty = document.getElementById('blockPayQty');
        const labelValor = document.getElementById('labelValor');

        if (tipo === 'bundle' || tipo === 'fixed_bundle') {
            if (fieldsBundle) fieldsBundle.classList.remove('d-none');
            if (blockPayQty) blockPayQty.classList.toggle('d-none', tipo === 'fixed_bundle');
            if (fieldsGeneral) fieldsGeneral.classList.toggle('d-none', tipo === 'bundle');
            if (tipo === 'fixed_bundle' && labelValor) labelValor.innerText = '<?php echo L('promos_label_fixed_price'); ?>';
        } else {
            if (fieldsBundle) fieldsBundle.classList.add('d-none');
            if (fieldsGeneral) fieldsGeneral.classList.remove('d-none');
            if (labelValor) labelValor.innerText = '<?php echo L('promos_label_value'); ?>';
        }
    };

    window.abrirModalPromo = function(promo = null) {
        limpiarErroresPromo();
        const modal = document.getElementById('promoModal');
        if (!modal) return;

        // Reset selección de productos
        _promoSelectedIds = new Set();
        if(window.updateProdCountPromo) updateProdCountPromo();

        if (promo) {
            setText('promoModalTitle', '<?php echo L('promos_edit_title'); ?>');
            setVal('promoId', promo.id);
            setVal('promoCodigo', promo.codigo || '');
            setVal('promoDescripcion', promo.descripcion || '');
            setVal('promoTipo', promo.tipo || 'percent');
            setVal('promoValor', promo.valor || 0);
            setVal('promoMin', promo.min_subtotal || 0);
            setVal('promoBuyQty', promo.bundle_buy_qty || '');
            setVal('promoPayQty', promo.bundle_pay_qty || '');
            
            // Cargar IDs de productos seleccionados
            let pids = [];
            if (promo.producto_ids) {
                try {
                    pids = JSON.parse(promo.producto_ids);
                } catch(e) {
                    pids = promo.producto_ids.toString().split(',').map(Number);
                }
            } else if (promo.id_producto) {
                pids = [parseInt(promo.id_producto)];
            }
            _promoSelectedIds = new Set(pids.map(Number));
            if(window.updateProdCountPromo) updateProdCountPromo();

            const cats = promo.categoria_code ? promo.categoria_code.split(',') : [];
            document.querySelectorAll('.promo-cat-checkbox').forEach(cb => {
                cb.checked = cats.includes(cb.value);
            });
            setVal('promoFechaInicio', promo.fecha_inicio ? promo.fecha_inicio.replace(' ', 'T') : '');
            setVal('promoFechaFin', promo.fecha_fin ? promo.fecha_fin.replace(' ', 'T') : '');
            const roles = promo.roles_segmento ? promo.roles_segmento.split(',') : [];
            const selRoles = document.getElementById('promoRoles');
            if (selRoles) Array.from(selRoles.options).forEach(opt => opt.selected = roles.includes(opt.value));

            const dias = promo.dias_semana ? promo.dias_semana.split(',').map(Number) : [];
            document.querySelectorAll('.promo-dia-checkbox').forEach(cb => cb.checked = dias.includes(parseInt(cb.value)));

            setVal('promoHoraInicio', promo.hora_inicio || '');
            setVal('promoHoraFin', promo.hora_fin || '');
            setChecked('promoActiva', parseInt(promo.activo));
        } else {
            setText('promoModalTitle', '<?php echo L('promos_new_title'); ?>');
            setVal('promoId', '');
            setVal('promoCodigo', '');
            setVal('promoDescripcion', '');
            setVal('promoTipo', 'percent');
            setVal('promoValor', '');
            setVal('promoMin', '');
            setVal('promoBuyQty', '');
            setVal('promoPayQty', '');
            document.querySelectorAll('.promo-cat-checkbox').forEach(cb => cb.checked = false);
            setVal('promoFechaInicio', '');
            setVal('promoFechaFin', '');
            const selRoles = document.getElementById('promoRoles');
            if (selRoles) selRoles.selectedIndex = -1;
            document.querySelectorAll('.promo-dia-checkbox').forEach(cb => cb.checked = false);
            setVal('promoHoraInicio', '');
            setVal('promoHoraFin', '');
            setChecked('promoActiva', true);
        }

        togglePromoFields();
        _cachedPromoCatRows = [];
        const prodSearch = document.getElementById('promoBusquedaProd');
        if (prodSearch) prodSearch.value = '';
        const prodList = document.getElementById('listadoProductosPromo');
        if (prodList) prodList.innerHTML = '';

        const firstTab = document.querySelector('#promoModal .tab-btn[data-tab="general"]');
        if (firstTab) switchTabPromo(firstTab, 'tab-promo-general');

        modal.classList.add('visible');
    };

    let _promoSelectedIds = new Set();
    let _cachedPromoCatRows = [];

    window.buildPromoFiltersCache = function() {
        _cachedPromoCatRows = Array.from(document.querySelectorAll('.promo-category-row'));
    };

    let _searchPromoTimer;
    window.filtrarProductosPromo = function() {
        clearTimeout(_searchPromoTimer);
        _searchPromoTimer = setTimeout(async () => {
            const input = document.getElementById('promoBusquedaProd');
            const term = (input?.value || '').trim();
            const container = document.getElementById('listadoProductosPromo');
            if (!container) return;

            container.innerHTML = '<div class="p-12 text-center text-muted fs-12"><i class="fa-solid fa-spinner fa-spin"></i></div>';

            try {
                const resp = await fetch('api/gestionPromocion.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ accion: 'buscarProductos', term }),
                });
                const r = await resp.json();
                if (!r.ok) { container.innerHTML = ''; return; }

                container.innerHTML = '';
                if (r.productos.length === 0) {
                    container.innerHTML = '<div class="p-12 text-center text-muted fs-12">Sin resultados</div>';
                    return;
                }

                const frag = document.createDocumentFragment();
                r.productos.forEach(p => {
                    const label = document.createElement('label');
                    label.className = 'checkbox-item d-flex ai-center gap-12 p-8-12 cp hover-bg-surface2 br-12 transition promo-product-row';

                    const cb = document.createElement('input');
                    cb.type = 'checkbox';
                    cb.className = 'promo-prod-checkbox';
                    cb.value = p.id;
                    cb.checked = _promoSelectedIds.has(p.id);
                    cb.addEventListener('change', () => {
                        if (cb.checked) _promoSelectedIds.add(p.id);
                        else _promoSelectedIds.delete(p.id);
                        updateProdCountPromo();
                    });

                    const info = document.createElement('div');
                    info.className = 'flex-1';
                    const nombre = document.createElement('div');
                    nombre.className = 'fs-13 fw-600 text-main';
                    nombre.textContent = p.nombre;
                    const refDiv = document.createElement('div');
                    refDiv.className = 'fs-11 text-muted';
                    refDiv.textContent = p.referencia;
                    info.appendChild(nombre);
                    info.appendChild(refDiv);

                    const precioDiv = document.createElement('div');
                    precioDiv.className = 'fs-12 font-mono fw-700 text-accent';
                    precioDiv.textContent = parseFloat(p.precio).toFixed(2).replace('.', ',') + ' \u20ac';

                    label.appendChild(cb);
                    label.appendChild(info);
                    label.appendChild(precioDiv);
                    frag.appendChild(label);
                });
                container.appendChild(frag);
            } catch(e) {
                container.innerHTML = '<div class="p-12 text-center text-muted fs-12">Error al cargar</div>';
            }
        }, 250);
    };

    let _searchCatPromoTimer;
    window.filtrarCategoriasPromo = function() {
        clearTimeout(_searchCatPromoTimer);
        _searchCatPromoTimer = setTimeout(() => {
            const input = document.getElementById('promoBusquedaCat');
            if (!input) return;
            const val = input.value.toLowerCase().trim().normalize('NFD').replace(/[\u0300-\u036f]/g, "");
            
            if (_cachedPromoCatRows.length === 0) buildPromoFiltersCache();

            const len = _cachedPromoCatRows.length;
            for (let i = 0; i < len; i++) {
                const row = _cachedPromoCatRows[i];
                const name = (row.getAttribute('data-name') || '').normalize('NFD').replace(/[\u0300-\u036f]/g, "");
                const code = (row.getAttribute('data-code') || '').normalize('NFD').replace(/[\u0300-\u036f]/g, "");
                row.classList.toggle('hidden-filter-row', !!val && !name.includes(val) && !code.includes(val));
            }
        }, 100);
    };

    window.updateProdCountPromo = function() {
        setText('promoCountSelectedProd', _promoSelectedIds.size + ' <?php echo L('promos_selected'); ?>');
    };

    window.unselectAllProductsPromo = function() {
        _promoSelectedIds.clear();
        document.querySelectorAll('.promo-prod-checkbox').forEach(cb => cb.checked = false);
        updateProdCountPromo();
    };

    window.unselectAllCatsPromo = function() {
        document.querySelectorAll('.promo-cat-checkbox').forEach(cb => cb.checked = false);
    };

    window.switchTabPromo = function(btn, tabId) {
        const modal = document.getElementById('promoModal');
        if (!modal) return;

        modal.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        requestAnimationFrame(() => {
            modal.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
            const target = document.getElementById(tabId);
            if (target) target.classList.add('active');
            if (tabId === 'tab-promo-filtros') {
                filtrarProductosPromo();
                if (_cachedPromoCatRows.length === 0) buildPromoFiltersCache();
            }
        });
    };

    window.cerrarModalPromo = function() {
        const modal = document.getElementById('promoModal');
        if (modal) modal.classList.remove('visible');
        limpiarErroresPromo();
    };

    window.guardarPromo = async function() {
        limpiarErroresPromo();
        const id = document.getElementById('promoId')?.value;
        
        const payload = {
            accion: id ? 'editar' : 'añadir',
            id: id || null,
            codigo: document.getElementById('promoCodigo')?.value.trim() || '',
            descripcion: document.getElementById('promoDescripcion')?.value.trim() || '',
            tipo: document.getElementById('promoTipo')?.value || 'percent',
            valor: document.getElementById('promoValor')?.value || 0,
            min_subtotal: document.getElementById('promoMin')?.value || 0,
            bundle_buy_qty: document.getElementById('promoBuyQty')?.value || '',
            bundle_pay_qty: document.getElementById('promoPayQty')?.value || '',
            id_producto: null,
            producto_ids: _promoSelectedIds.size > 0 ? Array.from(_promoSelectedIds) : null,
            categoria_code: Array.from(document.querySelectorAll('.promo-cat-checkbox:checked')).map(cb => cb.value).join(','),
            fecha_inicio: document.getElementById('promoFechaInicio')?.value || null,
            fecha_fin: document.getElementById('promoFechaFin')?.value || null,
            roles_segmento: Array.from(document.getElementById('promoRoles')?.selectedOptions || []).map(o => o.value),
            dias_semana: Array.from(document.querySelectorAll('.promo-dia-checkbox:checked')).map(cb => cb.value),
            hora_inicio: document.getElementById('promoHoraInicio')?.value || null,
            hora_fin: document.getElementById('promoHoraFin')?.value || null,
            activo: document.getElementById('promoActiva')?.checked ? 1 : 0,
        };

        try {
            const resp = await fetch('api/gestionPromocion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload),
            });
            const r = await resp.json();
            if (r.ok) {
                location.reload();
            } else if (r.aErrores) {
                // Mapa campo → id del tab que lo contiene
                const campoTab = {
                    codigo: 'tab-promo-general',
                    descripcion: 'tab-promo-general',
                    tipo: 'tab-promo-config',
                    valor: 'tab-promo-config',
                    bundle_buy_qty: 'tab-promo-config',
                    bundle_pay_qty: 'tab-promo-config',
                };
                let primerTab = null;
                for (const [field, msg] of Object.entries(r.aErrores)) {
                    setText('err-' + field, msg);
                    if (!primerTab && campoTab[field]) primerTab = campoTab[field];
                }
                // Saltar al tab que contiene el primer error para que sea visible
                if (primerTab) {
                    const dataTabMap = {
                        'tab-promo-general': 'general',
                        'tab-promo-config': 'config',
                    };
                    const dataTabVal = dataTabMap[primerTab];
                    if (dataTabVal) {
                        const targetBtn = document.querySelector(`#promoModal .tab-btn[data-tab="${dataTabVal}"]`);
                        if (targetBtn) switchTabPromo(targetBtn, primerTab);
                    }
                }
            } else {
                showCustomAlert('Error', r.error || 'No se pudo guardar la promoción', 'error');
            }
        } catch (e) {
            console.error(e);
            showCustomAlert('Error', 'Error de conexión con el servidor', 'error');
        }
    };

    window.togglePromo = async function(id) {
        if (!id) return;
        try {
            const resp = await fetch('api/gestionPromocion.php', {
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
            if (r.ok) {
                location.reload();
            } else {
                showCustomAlert('Error', r.error || 'No se pudo cambiar el estado', 'error');
            }
        } catch (e) {
            console.error(e);
            showCustomAlert('Error', 'Error de conexión', 'error');
        }
    };

    window.eliminarPromo = async function(id) {
        if (!id) return;
        showCustomConfirm(
            '<?php echo L('promos_confirm_del_title'); ?>',
            '<?php echo L('promos_confirm_del_msg'); ?>',
            async () => {
                    try {
                        const resp = await fetch('api/gestionPromocion.php', {
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
                        if (r.ok) {
                            location.reload();
                        } else {
                            showCustomAlert('Error', r.error || 'No se pudo eliminar la promoción', 'error');
                        }
                    } catch (e) {
                        console.error(e);
                        showCustomAlert('Error', 'Error de conexión', 'error');
                    }
                },
                '<?php echo L('modal_delete'); ?>',
                'danger'
        );
    };

    document.addEventListener('DOMContentLoaded', () => {
        const el = document.getElementById('promosTableBody');
        if (!el) return;

        Sortable.create(el, {
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'sortable-ghost',
            onEnd: async function() {
                const ids = Array.from(el.querySelectorAll('tr[data-id]')).map(tr => tr.dataset.id);
                try {
                    const resp = await fetch('api/gestionPromocion.php', {
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
                        showCustomAlert('Error', 'Error al guardar el nuevo orden: ' + (r.error || 'Desconocido'), 'error');
                        location.reload();
                    }
                } catch (e) {
                    console.error(e);
                    showCustomAlert('Error', 'Error de conexión al reordenar', 'error');
                    location.reload();
                }
            }
        });
    });
</script>
<style>
    /* Internal Styles for Promotions Modal */
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

    /* Form and Tab Scoped Scrolling */
    #promoForm {
        overflow: hidden !important;
        display: flex;
        flex-direction: column;
        height: 680px; /* Increased height to accommodate all elements without squashing */
    }

    .tab-pane {
        display: none;
        overflow-y: auto;
        flex: 1;
        padding-bottom: 20px;
    }

    .tab-pane.active {
        display: block;
        animation: fadeIn 0.2s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(4px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Special rule for Filters Tab: internally scrollable lists, not the container */
    #tab-promo-filtros {
        display: none;
        overflow: hidden !important;
        flex-direction: column;
        gap: 16px; /* Uniform gap for the tab components */
    }
    #tab-promo-filtros.active {
        display: flex;
    }

    /* List and Selection Styles */
    .method-option {
        background: var(--surface);
        border: 1.5px solid var(--border);
        transition: all 0.2s ease;
    }

    .method-option:hover {
        border-color: var(--accent) !important;
        background: rgba(var(--accent-rgb), 0.03);
    }

    .method-option:has(input:checked) {
        border-color: var(--accent) !important;
        background: var(--blue-light) !important;
        color: var(--accent);
        font-weight: 600;
    }

    .promo-product-row:hover {
        background: var(--surface2);
    }

    .promo-product-row:has(input:checked) {
        background: var(--blue-light);
        border-color: var(--accent);
    }

    .promo-product-row, .promo-category-row, .checkbox-item {
        user-select: none;
        -webkit-user-select: none;
    }

    #listadoProductosPromo {
        min-height: 0;
    }

    .hidden-filter-row {
        display: none !important;
    }

    /* Modal Spacing & Components */
    .w-modal-lg {
        width: 90vw !important;
        max-width: 1100px !important;
    }

    .search-box-container .search-input-wrap {
        height: 48px;
    }

    .checkbox-list {
        background: var(--surface1);
        border: 1.5px solid var(--border);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.03);
        padding: 12px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .hidden-filter-row {
        display: none !important;
    }

    .ls-1 {
        letter-spacing: 0.5px;
    }

    .tt-uppercase {
        text-transform: uppercase;
    }

    /* Fix for modal header title overlap */
    #promoModalTitle {
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        padding-right: 60px;
    }

    /* Multi-select styling */
    select[multiple] {
        padding: 8px;
        background: var(--surface1);
        border: 1.5px solid var(--border);
        border-radius: 12px;
        color: var(--text-main);
        transition: border-color 0.2s;
    }

    select[multiple]:focus {
        border-color: var(--accent);
        outline: none;
    }

    select[multiple] option {
        padding: 10px 14px;
        margin-bottom: 4px;
        border-radius: 8px;
        cursor: pointer;
    }

    select[multiple] option:checked {
        background: var(--accent) !important;
        color: white;
    }

</style>


<div class="main-full p-24">
    <div class="section-header container-wider">
        <div class="d-flex ai-center gap-16">
            <a href="index.php?irDashboard=1" class="btn-prominent-back compact" title="<?php echo L('login_back'); ?>">
                <i class="fa-solid fa-chevron-left"></i>
                <span><?php echo L('login_back'); ?></span>
            </a>
            <div class="vr" style="height: 32px; width: 1px; background: var(--border); opacity: 0.5;"></div>
            <div class="section-title">
                <h1><?php echo L('promos_title'); ?></h1>
                <p><?php echo L('promos_subtitle'); ?></p>
            </div>
        </div>
        <div class="d-flex gap-12">
            <button onclick="window.abrirModalPromo()" class="btn-add">
                <i class="fa-solid fa-plus"></i> <?php echo L('promos_btn_add'); ?>
            </button>
        </div>
    </div>

    <div class="table-container container-wider">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="pl-20" style="width: 40px;"></th>
                    <th><?php echo L('promos_th_code'); ?></th>
                    <th><?php echo L('promos_th_desc'); ?></th>
                    <th class="text-center"><?php echo L('promos_th_type'); ?></th>
                    <th><?php echo L('promos_th_config'); ?></th>
                    <th class="text-right"><?php echo L('promos_th_min'); ?></th>
                    <th class="text-center"><?php echo L('promos_th_members'); ?></th>
                    <th class="text-center"><?php echo L('promos_th_status'); ?></th>
                    <th class="text-center pr-20"><?php echo L('promos_th_actions'); ?></th>
                </tr>
            </thead>
            <tbody id="promosTableBody">
                <?php foreach ($avPromos['lista'] as $p): ?>
                    <tr data-id="<?php echo $p['id']; ?>">
                        <td class="pl-20">
                            <i class="fa-solid fa-grip-vertical drag-handle"></i>
                        </td>
                        <td class="font-mono"><?php echo htmlspecialchars($p['codigo'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($p['descripcion'] ?? ''); ?></td>
                        <td class="text-center">
                            <?php
                            $badge = 'status-card';
                            $label = L('promos_type_discount', true);
                            if ($p['tipo'] === 'percent') {
                                $badge = 'status-card';
                                $label = L('promos_type_percent', true);
                            }
                            if ($p['tipo'] === 'amount') {
                                $badge = 'status-cash';
                                $label = L('promos_type_amount', true);
                            }
                            if ($p['tipo'] === 'bundle') {
                                $badge = 'status-active';
                                $label = L('promos_type_bundle', true);
                            }
                            if ($p['tipo'] === 'fixed_bundle') {
                                $badge = 'status-active';
                                $label = L('promos_type_fixed', true);
                            }
                            ?>
                            <span class="status-pill <?php echo $badge; ?>">
                                <?php echo $label; ?>
                            </span>
                        </td>
                        <td class="fs-12">
                            <?php if ($p['tipo'] === 'bundle'): ?>
                                <strong><?php echo $p['bundle_buy_qty']; ?>x<?php echo $p['bundle_pay_qty']; ?></strong>
                            <?php elseif ($p['tipo'] === 'fixed_bundle'): ?>
                                <strong><?php echo $p['bundle_buy_qty']; ?> <?php echo L('tpv_for'); ?> <?php echo number_format($p['valor'], 2, ',', '.'); ?> €</strong>
                            <?php else: ?>
                                <strong><?php echo $p['valor']; ?><?php echo $p['tipo'] === 'percent' ? '%' : '€'; ?></strong>
                            <?php endif; ?>

                            <?php 
                            $pids = [];
                            if (!empty($p['producto_ids'])) {
                                $pids = json_decode($p['producto_ids'], true) ?: [];
                            }
                            if (count($pids) > 1): ?>
                                <div class="text-muted fs-11 mt-4"><i class="fa-solid fa-boxes-stacked"></i> <?php echo count($pids); ?> <?php echo L('tpv_products'); ?></div>
                            <?php elseif (count($pids) === 1 || $p['id_producto']): ?>
                                <div class="text-muted fs-11 mt-4"><i class="fa-solid fa-box"></i> <?php echo L('tpv_product'); ?> ID: <?php echo count($pids) === 1 ? $pids[0] : $p['id_producto']; ?></div>
                            <?php elseif ($p['categoria_code']): ?>
                                <div class="text-muted fs-11 mt-4"><i class="fa-solid fa-tags"></i> Cat: <?php echo htmlspecialchars($p['categoria_code'] ?? ''); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-right font-mono">
                            <?php echo number_format($p['min_subtotal'], 2, ',', '.'); ?> €
                        </td>
                        <td class="text-center">
                            <?php if ($p['solo_socios']): ?>
                                <span class="status-pill status-card"><i class="fa-solid fa-id-card"></i> <?php echo L('tpv_yes'); ?></span>
                            <?php else: ?>
                                <span class="status-pill text-muted"><?php echo L('tpv_no'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($p['activo']): ?>
                                <span class="status-pill status-active">
                                    <i class="fa-solid fa-circle-check"></i> <?php echo L('promos_status_active'); ?>
                                </span>
                            <?php else: ?>
                                <span class="status-pill status-inactive">
                                    <i class="fa-solid fa-circle-xmark"></i> <?php echo L('promos_status_inactive'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center pr-20">
                            <div class="d-flex jc-center gap-8">
                                <button onclick="window.abrirModalPromo(<?php echo htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8'); ?>)" title="<?php echo L('modal_edit'); ?>" class="btn-icon">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button onclick="window.togglePromo(<?php echo $p['id']; ?>)" title="<?php echo $p['activo'] ? L('modal_deactivate', true) : L('modal_activate', true); ?>" class="btn-icon <?php echo $p['activo'] ? 'text-red' : 'text-green'; ?>">
                                    <i class="fa-solid fa-<?php echo $p['activo'] ? 'pause' : 'play'; ?>"></i>
                                </button>
                                <button onclick="window.eliminarPromo(<?php echo $p['id']; ?>)" title="<?php echo L('modal_delete'); ?>" class="btn-icon btn-icon-danger">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($avPromos['lista'])): ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i class="fa-solid fa-ticket"></i>
                                <?php echo L('promos_no_promos'); ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL NUEVA/EDITAR PROMO -->
<div class="modal-overlay" id="promoModal">
    <div class="modal modal-content gap-16 ai-stretch w-modal-lg" style="max-width: 1100px; border-radius: 20px; overflow: hidden; height: auto; max-height: 95vh;">
        <div class="modal-header d-flex flex-column mb-0 p-0">
            <div class="d-flex ai-center jc-center w-100 p-24-32" style="position: relative; padding-left: 80px; padding-right: 80px;">
                <h2 id="promoModalTitle" class="m-0 fs-20 fw-800 text-main text-center tt-uppercase ls-1"><?php echo L('promos_modal_title'); ?></h2>
                <button type="button" onclick="cerrarModalPromo()" class="btn-close-modal fs-32" style="position: absolute; right: 24px; top: 50%; transform: translateY(-50%); background: transparent; border: none; cursor: pointer;">&times;</button>
            </div>

            <div class="modal-tabs">
                <button type="button" class="tab-btn active" data-tab="general" onclick="switchTabPromo(this, 'tab-promo-general')">
                    <i class="fa-solid fa-sliders"></i> <?php echo L('promos_tab_general'); ?>
                </button>
                <button type="button" class="tab-btn" data-tab="config" onclick="switchTabPromo(this, 'tab-promo-config')">
                    <i class="fa-solid fa-gear"></i> <?php echo L('promos_tab_config'); ?>
                </button>
                <button type="button" class="tab-btn" data-tab="filtros" onclick="switchTabPromo(this, 'tab-promo-filtros')">
                    <i class="fa-solid fa-filter"></i> <?php echo L('promos_tab_filters'); ?>
                </button>
                <button type="button" class="tab-btn" data-tab="programacion" onclick="switchTabPromo(this, 'tab-promo-prog')">
                    <i class="fa-solid fa-calendar-days"></i> <?php echo L('promos_tab_prog'); ?>
                </button>
                <button type="button" class="tab-btn" data-tab="segmentacion" onclick="switchTabPromo(this, 'tab-promo-seg')">
                    <i class="fa-solid fa-users"></i> <?php echo L('promos_tab_seg'); ?>
                </button>
            </div>
        </div>

        <form id="promoForm" class="modal-body p-24">
            <input type="hidden" id="promoId">

            <!-- TAB: GENERAL -->
            <div id="tab-promo-general" class="tab-pane active">
                <div class="d-grid grid-2 gap-24 mb-20">
                    <div class="form-group">
                        <label class="form-label fw-600"><?php echo L('promos_label_code'); ?></label>
                        <div class="search-input-wrap">
                            <i class="fa-solid fa-ticket"></i>
                            <input type="text" id="promoCodigo" class="search-input font-mono fw-700" 
                                style="text-transform: uppercase; letter-spacing: 1px;"
                                placeholder="<?php echo L('promos_code_placeholder'); ?>">
                        </div>
                        <span class="form-error" id="err-codigo"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label fw-600"><?php echo L('promos_label_status'); ?></label>
                        <label class="d-flex ai-center gap-12 cursor-pointer p-12 bg-surface2 br-12 border hover-border-accent transition" style="height: 52px; border-radius: 10px;">
                            <input type="checkbox" id="promoActiva" checked style="width: 20px; height: 20px;">
                            <span class="fs-14 fw-600"><?php echo L('promos_status_help'); ?></span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label fw-600"><?php echo L('promos_label_desc'); ?></label>
                    <input type="text" id="promoDescripcion" class="form-input" placeholder="<?php echo L('promos_desc_placeholder'); ?>">
                    <span class="form-error" id="err-descripcion"></span>
                    <p class="fs-11 text-muted mt-8"><?php echo L('promos_desc_help'); ?></p>
                </div>
            </div>

            <!-- TAB: CONFIGURACIÓN -->
            <div id="tab-promo-config" class="tab-pane">
                <div class="bg-surface2 p-20 br-12 border mb-20">
                    <label class="form-label fs-12 tt-uppercase fw-700 mb-16 d-flex ai-center gap-8">
                        <i class="fa-solid fa-gears text-accent"></i> <?php echo L('promos_config_rule'); ?>
                    </label>
                    <div class="form-group mb-0">
                        <label class="form-label fs-11"><?php echo L('promos_config_type'); ?></label>
                        <select id="promoTipo" class="form-input" onchange="togglePromoFields()">
                            <option value="percent"><?php echo L('promos_opt_percent'); ?></option>
                            <option value="amount"><?php echo L('promos_opt_amount'); ?></option>
                            <option value="bundle"><?php echo L('promos_opt_bundle'); ?></option>
                            <option value="fixed_bundle"><?php echo L('promos_opt_fixed'); ?></option>
                        </select>
                    </div>
                </div>

                <div id="fieldsGeneral" class="d-grid grid-2 gap-16 mb-20">
                    <div class="form-group mb-0">
                        <label class="form-label fs-11" id="labelValor"><?php echo L('promos_label_value'); ?></label>
                        <input type="number" id="promoValor" class="form-input text-right font-mono" step="0.01" min="0">
                        <span class="form-error" id="err-valor"></span>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label fs-11"><?php echo L('promos_label_min'); ?></label>
                        <input type="number" id="promoMin" class="form-input text-right font-mono" step="0.01" min="0">
                    </div>
                </div>

                <div id="fieldsBundle" class="d-none bg-surface1 p-20 br-12 border mb-20">
                    <div class="d-grid grid-2 gap-24">
                        <div class="form-group mb-0">
                            <label class="form-label fs-11 d-flex ai-center gap-4">
                                <i class="fa-solid fa-cart-shopping"></i> <?php echo L('promos_label_buy_qty'); ?>
                            </label>
                            <input type="number" id="promoBuyQty" class="form-input text-center fs-20 fw-700" placeholder="0">
                        </div>
                        <div id="blockPayQty" class="form-group mb-0">
                            <label class="form-label fs-11 d-flex ai-center gap-4">
                                <i class="fa-solid fa-receipt"></i> <?php echo L('promos_label_pay_qty'); ?>
                            </label>
                            <input type="number" id="promoPayQty" class="form-input text-center fs-20 fw-700" placeholder="0">
                        </div>
                    </div>
                    <p class="fs-11 text-muted mt-12 italic text-center"><?php echo L('promos_bundle_help'); ?></p>
                </div>
            </div>

            <!-- TAB: FILTROS -->
            <div id="tab-promo-filtros" class="tab-pane">
                <div class="alert-premium premium-info mb-24">
                    <div class="alert-icon-wrap">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>
                    <div class="alert-content">
                        <span class="alert-title text-uppercase"><?php echo L('promos_filter_scope'); ?></span>
                        <span class="alert-desc"><?php echo L('promos_filter_scope_help'); ?></span>
                    </div>
                </div>

                <div class="d-grid grid-2 gap-32 flex-1 min-h-0">
                    <!-- Column 1: Products -->
                    <div class="filter-column d-flex flex-column gap-12 min-h-0">
                        <label class="form-label fw-800 d-flex ai-center gap-8 mb-4 flex-shrink-0">
                            <i class="fa-solid fa-box-open text-accent"></i> <?php echo L('promos_filter_products'); ?>
                        </label>
                        
                        <div class="search-box-container flex-shrink-0">
                            <div class="search-input-wrap shadow-sm">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="text" id="promoBusquedaProd" class="search-input"
                                    placeholder="<?php echo L('promos_search_placeholder'); ?>" oninput="filtrarProductosPromo()">
                            </div>
                        </div>

                        <div class="products-selection-container border br-16 overflow-hidden bg-white shadow-sm d-flex flex-column flex-1 min-h-0">
                            <div class="d-flex ai-center jc-between p-12-20 border-bottom bg-surface1 flex-shrink-0">
                                <span class="fs-11 tt-uppercase fw-800 text-accent ls-1" id="promoCountSelectedProd">0 <?php echo L('promos_selected'); ?></span>
                                <button type="button" class="btn-clean px-12 py-6 fs-11 fw-700 transition br-8 border-0 bg-transparent text-muted hover-text-accent cp" onclick="unselectAllProductsPromo()">
                                    <i class="fa-solid fa-eraser mr-4"></i> <?php echo L('promos_btn_clean'); ?>
                                </button>
                            </div>
                            <div class="checkbox-list-container p-8" style="max-height: 320px; overflow-y: auto;">
                                <div class="checkbox-list border-0" id="listadoProductosPromo">
                                    <!-- Cargado vía AJAX al abrir la pestaña -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Column 2: Categories -->
                    <div class="filter-column d-flex flex-column gap-12 min-h-0">
                        <label class="form-label fw-800 d-flex ai-center gap-8 mb-4 flex-shrink-0">
                            <i class="fa-solid fa-tags text-accent"></i> <?php echo L('promos_filter_cats'); ?>
                        </label>

                        <div class="search-box-container flex-shrink-0">
                            <div class="search-input-wrap shadow-sm">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="text" id="promoBusquedaCat" class="search-input"
                                    placeholder="<?php echo L('promos_search_placeholder_cat') ?: 'Buscar categoría...'; ?>" oninput="filtrarCategoriasPromo()">
                            </div>
                        </div>

                        <div class="categories-selection-container border br-16 overflow-hidden bg-white shadow-sm d-flex flex-column flex-1 min-h-0">
                            <div class="d-flex ai-center jc-between p-12-20 border-bottom bg-surface1 flex-shrink-0">
                                <span class="fs-11 tt-uppercase fw-800 text-accent ls-1"><?php echo L('tpv_all'); ?></span>
                                <button type="button" class="btn-clean px-12 py-6 fs-11 fw-700 transition br-8 border-0 bg-transparent text-muted hover-text-accent cp" onclick="unselectAllCatsPromo()">
                                    <i class="fa-solid fa-eraser mr-4"></i> <?php echo L('promos_btn_clean'); ?>
                                </button>
                            </div>
                            <div class="checkbox-list-container p-8" style="max-height: 320px; overflow-y: auto;">
                                <div class="checkbox-list border-0">
                                    <?php foreach ($avPromos['categorias'] as $cat): ?>
                                        <label class="checkbox-item d-flex ai-center gap-12 p-8-12 cp hover-bg-surface2 br-12 transition promo-category-row"
                                            data-name="<?php echo htmlspecialchars(strtolower($cat['nombre'])); ?>"
                                            data-code="<?php echo htmlspecialchars(strtolower($cat['codigo'])); ?>">
                                            <input type="checkbox" class="promo-cat-checkbox" value="<?php echo htmlspecialchars($cat['codigo']); ?>">
                                            <div class="flex-1">
                                                <div class="fs-13 fw-600 text-main"><?php echo htmlspecialchars($cat['nombre']); ?></div>
                                                <div class="fs-11 text-muted"><?php echo htmlspecialchars($cat['codigo']); ?></div>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: PROGRAMACIÓN -->
            <div id="tab-promo-prog" class="tab-pane">
                <div class="bg-surface2 p-20 br-12 border mb-20">
                    <label class="form-label fs-12 tt-uppercase fw-700 mb-16 d-flex ai-center gap-8">
                        <i class="fa-solid fa-calendar text-accent"></i> <?php echo L('promos_prog_validity'); ?>
                    </label>
                    <div class="d-grid grid-2 gap-16">
                        <div class="form-group mb-0">
                            <label class="form-label fs-11"><?php echo L('promos_prog_start'); ?></label>
                            <input type="datetime-local" id="promoFechaInicio" class="form-input font-mono">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label fs-11"><?php echo L('promos_prog_end'); ?></label>
                            <input type="datetime-local" id="promoFechaFin" class="form-input font-mono">
                        </div>
                    </div>
                </div>

                <div class="bg-surface2 p-20 br-12 border mb-20">
                    <label class="form-label fs-12 tt-uppercase fw-700 mb-16 d-flex ai-center gap-8">
                        <i class="fa-solid fa-clock text-accent"></i> <?php echo L('promos_prog_days_time'); ?>
                    </label>
                    <div class="d-flex flex-wrap gap-12 mb-20">
                        <?php
                        $dias = [1 => L('tpv_day_monday', true), 2 => L('tpv_day_tuesday', true), 3 => L('tpv_day_wednesday', true), 4 => L('tpv_day_thursday', true), 5 => L('tpv_day_friday', true), 6 => L('tpv_day_saturday', true), 0 => L('tpv_day_sunday', true)];
                        foreach ($dias as $val => $label): ?>
                            <label class="method-option border br-8 px-12 py-8 d-flex ai-center gap-8 cp transition" style="min-width: 100px;">
                                <input type="checkbox" class="promo-dia-checkbox" value="<?php echo $val; ?>" style="width: 16px; height: 16px;">
                                <span class="fs-12"><?php echo $label; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-grid grid-2 gap-16 mt-16 pt-16 border-top">
                        <div class="form-group mb-0">
                            <label class="form-label fs-11"><?php echo L('promos_prog_h_start'); ?></label>
                            <input type="time" id="promoHoraInicio" class="form-input">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label fs-11"><?php echo L('promos_prog_h_end'); ?></label>
                            <input type="time" id="promoHoraFin" class="form-input">
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: SEGMENTACIÓN -->
            <div id="tab-promo-seg" class="tab-pane">
                <div class="p-20 bg-surface2 br-12 border">
                    <label class="form-label fs-12 tt-uppercase fw-700 mb-16 d-flex ai-center gap-8">
                        <i class="fa-solid fa-user-tag text-accent"></i> <?php echo L('promos_seg_groups'); ?>
                    </label>
                    <div class="form-group">
                        <label class="form-label fs-12 fw-600"><?php echo L('promos_seg_roles'); ?></label>
                        <select id="promoRoles" class="form-input" multiple style="height: 250px;">
                            <option value="general"><?php echo L('promos_role_general'); ?></option>
                            <option value="socio"><?php echo L('promos_role_member'); ?></option>
                            <option value="gamer afilidado"><?php echo L('promos_role_gamer'); ?></option>
                            <option value="empresa"><?php echo L('promos_role_b2b'); ?></option>
                            <option value="mayorista"><?php echo L('promos_role_wholesaler'); ?></option>
                        </select>
                        <p class="fs-11 text-muted mt-12"><?php echo L('promos_seg_help'); ?></p>
                    </div>
                </div>

                <div class="alert-premium premium-warning mt-24">
                    <div class="alert-icon-wrap">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div class="alert-content">
                        <span class="alert-title text-uppercase"><?php echo L('promos_priority_title'); ?></span>
                        <span class="alert-desc"><?php echo L('promos_priority_help'); ?></span>
                    </div>
                </div>
            </div>
        </form>

        <div class="modal-footer full-width p-24 bg-surface1 border-top">
            <button onclick="window.cerrarModalPromo()" class="btn-cancel px-24 py-12 fs-14 fw-600"><?php echo L('modal_cancel'); ?></button>
            <button onclick="window.guardarPromo()" class="btn-save px-32 py-12 fs-14 fw-700 background-accent text-white br-12 shadow-sm transition">
                <i class="fa-solid fa-floppy-disk mr-8"></i> <?php echo L('promos_btn_save'); ?>
            </button>
        </div>
    </div>
</div>
