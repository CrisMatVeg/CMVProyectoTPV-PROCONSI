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
                <h1><?php echo L('dashboard_btn_providers'); ?></h1>
                <p><?php echo L('dashboard_btn_providers_sub'); ?></p>
            </div>
        </div>
        <div class="d-flex gap-12 ai-center">
            <button onclick="abrirModalProveedor()" class="btn-add">
                <i class="fa-solid fa-truck-field"></i> <?php echo L('prov_btn_new'); ?>
            </button>
        </div>
    </div>

    <!-- Barra de Búsqueda -->
    <div class="filters-bar-new container-wider mb-24 br-20 shadow-sm" style="background: var(--surface2); padding: 16px; border: 1px solid var(--border);">
        <div class="filter-item flex-1">
            <div class="search-input-fancy" style="border-radius: 12px; background: var(--surface); padding-left: 15px;">
                <i class="fa-solid fa-magnifying-glass opacity-50"></i>
                <input type="text" id="provSearch" placeholder="<?php echo L('prod_search_placeholder'); ?>" onkeyup="filtrarProveedores()" style="height: 48px; border: none; background: transparent; width: 100%; padding-left: 10px;">
            </div>
        </div>
    </div>

    <!-- TABLA DE PROVEEDORES -->
    <div class="table-container container-wider">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-60 pl-20"><?php echo L('id'); ?></th>
                    <th><?php echo L('prov_label_name'); ?> / <?php echo L('client_label_nif'); ?></th>
                    <th><?php echo L('prov_th_contact'); ?></th>
                    <th class="text-center"><?php echo L('prod_th_status'); ?></th>
                    <th class="text-center pr-20"><?php echo L('prod_th_actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($avInicioPrivado['proveedores'])): ?>
                    <tr class="empty-row">
                        <td colspan="5" class="p-40 text-center">
                            <div class="empty-state">
                                <i class="fa-solid fa-truck-ramp-box opacity-20 fs-48 mb-16"></i>
                                <p class="text-muted"><?php echo L('prov_no_providers'); ?></p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($avInicioPrivado['proveedores'] as $p): ?>
                        <tr>
                            <td class="font-mono text-muted pl-20"><?php echo $p['id']; ?></td>
                            <td>
                                <div class="font-bold"><?php echo htmlspecialchars($p['nombre']); ?></div>
                                <div class="fs-12 text-muted font-mono"><?php echo htmlspecialchars($p['cif_nif']); ?></div>
                            </td>
                            <td>
                                <div class="fs-13 d-flex ai-center gap-8">
                                    <i class="fa-solid fa-phone text-muted fs-11 w-12"></i>
                                    <?php echo htmlspecialchars($p['telefono'] ?: '-'); ?>
                                </div>
                                <div class="fs-12 d-flex ai-center gap-8 opacity-70">
                                    <i class="fa-solid fa-envelope text-muted fs-11 w-12"></i>
                                    <?php echo htmlspecialchars($p['email'] ?: '-'); ?>
                                </div>
                             </td>
                            <td class="text-center">
                                <?php if ($p['activo']): ?>
                                    <span class="status-pill status-active">
                                        <i class="fa-solid fa-circle-check"></i> <?php echo L('prod_status_active'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="status-pill status-inactive">
                                        <i class="fa-solid fa-circle-xmark"></i> <?php echo L('prod_status_inactive'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex jc-center gap-8 pr-20">
                                    <button class="btn-icon" onclick='verHistorialProveedor(<?php echo json_encode($p); ?>)' title="<?php echo L('prov_btn_history'); ?>" style="color: var(--accent);">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                    </button>
                                    <button class="btn-icon" onclick='editarProveedor(<?php echo json_encode($p); ?>)' title="<?php echo L('user_tip_edit'); ?>">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div><!-- MODAL PROVEEDOR (Integrado con estilos de la app) -->
<div id="modalProveedor" class="modal-overlay-bg">
    <div class="modal-content w-modal-lg" style="max-width: 900px; border-radius: 20px; overflow: hidden; display: flex; flex-direction: column; max-height: 90vh; border: 1px solid var(--border); box-shadow: var(--shadow-lg);">
        <div class="modal-header">
            <h2 id="modalProveedorTitle"><?php echo L('prov_modal_new_title'); ?></h2>
            <button class="btn-close-modal" onclick="cerrarModalProveedor()">&times;</button>
        </div>

        <!-- TABS NAV -->
        <div class="d-flex px-24 border-bottom bg-surface1">
            <button class="tab-btn active" id="tabBtnGeneral" onclick="cambiarTab('general')">
                <i class="fa-solid fa-address-card"></i> <?php echo L('prod_modal_tab_general'); ?>
            </button>
            <button class="tab-btn" id="tabBtnProductos" onclick="cambiarTab('productos')" style="display:none;">
                <i class="fa-solid fa-boxes-stacked"></i> <?php echo L('prov_tab_products'); ?>
            </button>
        </div>

        <div class="flex-1 overflow-auto p-24 bg-surface2" style="min-height: 500px;">
            <!-- TAB GENERAL -->
            <div id="tabGeneral" class="tab-pane">
                <form id="formProveedor" onsubmit="guardarProveedor(event)">
                    <input type="hidden" id="provId">

                    <div class="form-group">
                        <label class="form-label"><?php echo L('prov_label_name'); ?> *</label>
                        <input type="text" id="provNombre" class="form-input" required placeholder="<?php echo L('prov_placeholder_name'); ?>">
                    </div>
 
                    <div class="d-grid grid-2 gap-16">
                        <div class="form-group">
                            <label class="form-label"><?php echo L('client_label_nif'); ?> *</label>
                            <input type="text" id="provCif" class="form-input font-mono" required placeholder="<?php echo L('prov_placeholder_cif'); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?php echo L('client_label_phone'); ?></label>
                            <input type="text" id="provTel" class="form-input" placeholder="<?php echo L('prov_placeholder_tel'); ?>">
                        </div>
                    </div>
 
                    <div class="form-group">
                        <label class="form-label"><?php echo L('prov_label_email'); ?></label>
                        <input type="email" id="provEmail" class="form-input" placeholder="<?php echo L('prov_placeholder_email'); ?>">
                    </div>

                    <div class="d-grid grid-3 gap-16">
                        <div class="form-group">
                            <label class="form-label"><?php echo L('prov_label_pay_cond'); ?></label>
                            <input type="text" id="provCondicionesPago" class="form-input" placeholder="<?php echo L('prov_placeholder_pay_cond'); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?php echo L('prov_label_delivery_term'); ?></label>
                            <input type="text" id="provPlazoEntrega" class="form-input" placeholder="<?php echo L('prov_placeholder_delivery_term'); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?php echo L('prov_label_due_days'); ?></label>
                            <input type="number" id="provVencimientoDias" class="form-input" min="0" placeholder="<?php echo L('prov_placeholder_due_days'); ?>">
                        </div>
                    </div>
 
                    <div class="form-group">
                        <label class="form-label"><?php echo L('prov_label_address'); ?></label>
                        <input type="text" id="provDireccion" class="form-input" placeholder="<?php echo L('prov_placeholder_address'); ?>">
                    </div>
 
                    <div class="form-group d-flex ai-center gap-8" id="divProvActivo" style="display:none;">
                        <input type="checkbox" id="provActivo" checked style="width: 18px; height: 18px;">
                        <label class="form-label mb-0 fs-14"><?php echo L('prov_label_active'); ?></label>
                    </div>

                    <div class="modal-footer pt-16 border-top">
                        <button type="button" class="btn-cancel" onclick="cerrarModalProveedor()"><?php echo L('modal_cancel'); ?></button>
                        <div class="flex-1"></div>
                        <button type="submit" class="btn-save w-auto px-32"><?php echo L('prov_btn_save'); ?></button>
                    </div>
                </form>
            </div>

            <!-- TAB PRODUCTOS -->
            <div id="tabProductos" class="tab-pane d-none">
                <div class="d-grid grid-2 gap-24 ai-start">
                    <!-- PANEL IZQUIERDO: SELECCIÓN -->
                    <div class="panel-selection">
                        <h3 class="fs-14 font-bold mb-12 d-flex ai-center gap-8 text-accent">
                            <i class="fa-solid fa-list-check"></i> <?php echo L('prov_label_catalog'); ?>
                        </h3>
                        <div class="pos-rel mb-12">
                            <i class="fa-solid fa-magnifying-glass pos-abs left-12 top-12 text-muted"></i>
                            <input type="text" class="form-input pl-36" id="searchProductosLibres" placeholder="<?php echo L('prod_search_placeholder'); ?>" onkeyup="buscarProductosAlEscribir(event)">
                        </div>

                        <div id="checklistCont">
                            <!-- Checklist de productos cargados aquí -->
                            <div class="p-40 text-center text-muted fs-13"><?php echo L('loading'); ?></div>
                        </div>

                        <div class="d-flex ai-center jc-between mt-auto">
                            <span class="fs-12 text-muted"><?php echo L('selected'); ?>: <strong id="countSelectedSearch" class="text-accent">0</strong></span>
                            <button class="btn-vincular" onclick="vincularSeleccionados()">
                                <i class="fa-solid fa-plus"></i> <?php echo L('prov_btn_link'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- PANEL DERECHO: VINCULADOS -->
                    <div class="panel-associated">
                        <h3 class="fs-14 font-bold mb-12 d-flex ai-center gap-8">
                            <i class="fa-solid fa-link"></i> <?php echo L('prov_label_linked'); ?>
                        </h3>
                        <div class="table-container m-0 border br-12 overflow-auto bg-white flex-1">
                            <table class="data-table">
                                <thead class="pos-sticky top-0 z-10 bg-surface1">
                                    <tr>
                                        <th><?php echo L('prov_th_ref_prod'); ?></th>
                                        <th class="text-right"><?php echo L('prod_th_stock'); ?></th>
                                        <th class="text-center"><?php echo L('prod_th_actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyProvProductos">
                                    <!-- Productos vinculados aquí -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL HISTORIAL DE PEDIDOS -->
<div id="modalHistorial" class="modal-overlay-bg">
    <div class="modal-content w-modal-lg" style="max-width: 1000px; border-radius: 20px; overflow: hidden; display: flex; flex-direction: column; max-height: 90vh; border: 1px solid var(--border); box-shadow: var(--shadow-lg);">
        <div class="modal-header">
            <h2 id="modalHistorialTitle"><?php echo L('prov_modal_history_title'); ?></h2>
            <button class="btn-close-modal" onclick="cerrarModalHistorial()">&times;</button>
        </div>
        
        <div class="flex-1 overflow-auto p-24 bg-surface2">
            <div class="table-container m-0 border br-12 overflow-hidden bg-white">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="pl-20"><?php echo L('purchase_th_albaran'); ?></th>
                            <th><?php echo L('purchase_th_invoice_date'); ?></th>
                            <th class="text-right"><?php echo L('purchase_total_albaran'); ?></th>
                            <th class="text-center"><?php echo L('prod_th_status'); ?></th>
                            <th class="text-center pr-20"><?php echo L('prod_th_actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="tbodyHistorial">
                        <!-- Historial cargado aquí -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="modal-footer p-16 bg-surface1 border-top">
            <button type="button" class="btn-cancel" onclick="cerrarModalHistorial()"><?php echo L('modal_cancel'); ?></button>
        </div>
    </div>
</div>

<script>
    function filtrarProveedores() {
        const term = document.getElementById('provSearch').value.toLowerCase().trim();
        const rows = document.querySelectorAll('.data-table tbody tr:not(.empty-row)');
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    }

    let selectedForLinking = [];

    function abrirModalProveedor() {
        document.getElementById('formProveedor').reset();
        document.getElementById('provId').value = '';
        document.getElementById('provCondicionesPago').value = '';
        document.getElementById('provPlazoEntrega').value = '';
        document.getElementById('provVencimientoDias').value = '0';
        document.getElementById('modalProveedorTitle').innerText = "<?php echo L('prov_modal_new_title'); ?>";
        document.getElementById('divProvActivo').style.display = 'none';
        document.getElementById('tabBtnProductos').style.display = 'none';
        cambiarTab('general');
        document.getElementById('modalProveedor').style.display = 'flex';
    }

    function cerrarModalProveedor() {
        document.getElementById('modalProveedor').style.display = 'none';
        selectedForLinking = [];
        document.getElementById('countSelectedSearch').innerText = '0';
        document.getElementById('searchProductosLibres').value = '';
    }

    function editarProveedor(p) {
        document.getElementById('provId').value = p.id;
        document.getElementById('provNombre').value = p.nombre;
        document.getElementById('provCif').value = p.cif_nif;
        document.getElementById('provTel').value = p.telefono;
        document.getElementById('provEmail').value = p.email;
        document.getElementById('provDireccion').value = p.direccion;
        document.getElementById('provCondicionesPago').value = p.condiciones_pago || '';
        document.getElementById('provPlazoEntrega').value = p.plazo_entrega || '';
        document.getElementById('provVencimientoDias').value = p.vencimiento_dias || 0;
        document.getElementById('provActivo').checked = p.activo == 1;
        document.getElementById('divProvActivo').style.display = 'flex';
        document.getElementById('modalProveedorTitle').innerText = "<?php echo L('prov_modal_edit_title'); ?>: " + p.nombre;
        document.getElementById('tabBtnProductos').style.display = 'flex';
        cambiarTab('general');
        document.getElementById('modalProveedor').style.display = 'flex';
    }

    function cambiarTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(p => {
            p.classList.add('d-none');
            p.style.display = 'none'; // Ensure hidden completely
        });

        if (tab === 'general') {
            document.getElementById('tabBtnGeneral').classList.add('active');
            const pane = document.getElementById('tabGeneral');
            pane.classList.remove('d-none');
            pane.style.display = 'block';
        } else {
            document.getElementById('tabBtnProductos').classList.add('active');
            const pane = document.getElementById('tabProductos');
            pane.classList.remove('d-none');
            pane.style.display = 'block'; // Or 'grid' if d-grid works fine
            selectedForLinking = [];
            document.getElementById('countSelectedSearch').innerText = '0';
            lanzarBusquedaProductos(''); // Cargar todos al inicio
            cargarProductosProveedor();
        }
    }

    async function cargarProductosProveedor() {
        const idProv = document.getElementById('provId').value;
        const tbody = document.getElementById('tbodyProvProductos');
        tbody.innerHTML = '<tr><td colspan="3" class="text-center p-20"><?php echo L('loading'); ?></td></tr>';

        try {
            const res = await fetch(`api/productos_proveedor.php?id_proveedor=${idProv}`);
            const data = await res.json();
            if (data.success) {
                if (data.productos.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center p-40 text-muted"><?php echo L('prov_js_no_linked'); ?></td></tr>';
                    return;
                }
                tbody.innerHTML = '';
                data.productos.forEach(p => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>
                            <div class="font-bold fs-13">${p.nombre}</div>
                            <div class="fs-11 text-muted font-mono">${p.referencia}</div>
                        </td>
                        <td class="text-right font-mono">${p.stock_actual}</td>
                        <td class="text-center">
                            <button class="btn-icon text-red" onclick="desvincularProducto(${p.id})" title="<?php echo L('prov_js_unlink'); ?>">
                                <i class="fa-solid fa-link-slash"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        } catch (err) {
            console.error(err);
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-red p-20"><?php echo L('prod_js_error'); ?></td></tr>';
        }
    }

    let searchTimer;

    function buscarProductosAlEscribir(e) {
        clearTimeout(searchTimer);
        const term = e.target.value.trim();
        searchTimer = setTimeout(() => lanzarBusquedaProductos(term), 300);
    }

    async function lanzarBusquedaProductos(term) {
        const idProv = document.getElementById('provId').value;
        const cont = document.getElementById('checklistCont');

        try {
            const res = await fetch(`api/productos_proveedor.php?accion=buscar_libres&term=${term}&id_proveedor=${idProv}`);
            const data = await res.json();
            if (data.success) {
                if (data.productos.length === 0) {
                    cont.innerHTML = '<div class="p-40 text-center text-muted fs-13"><?php echo L('prod_no_results_short'); ?></div>';
                    return;
                }
                cont.innerHTML = '';
                data.productos.forEach(p => {
                    const isSelected = selectedForLinking.includes(p.id);
                    const div = document.createElement('div');
                    div.className = 'item-check' + (isSelected ? ' selected' : '');
                    div.innerHTML = `
                        <input type="checkbox" ${isSelected ? 'checked' : ''}>
                        <div class="item-info">
                            <div class="font-bold fs-14">${p.nombre}</div>
                            <div class="fs-11 text-muted font-mono">${p.referencia} | Stock: ${p.stock_actual}</div>
                        </div>
                    `;
                    div.onclick = (e) => {
                        const checkbox = div.querySelector('input');
                        if (e.target !== checkbox) checkbox.checked = !checkbox.checked;
                        if (checkbox.checked) {
                            if (!selectedForLinking.includes(p.id)) selectedForLinking.push(p.id);
                            div.classList.add('selected');
                        } else {
                            selectedForLinking = selectedForLinking.filter(id => id !== p.id);
                            div.classList.remove('selected');
                        }
                        document.getElementById('countSelectedSearch').innerText = selectedForLinking.length;
                    };
                    cont.appendChild(div);
                });
            }
        } catch (err) {
            cont.innerHTML = '<div class="p-40 text-center text-red"><?php echo L('prod_js_error'); ?></div>';
        }
    }

    async function vincularSeleccionados() {
        const ids = selectedForLinking;
        if (ids.length === 0) return showCustomAlert("<?php echo L('prod_th_status'); ?>", "<?php echo L('prov_js_select_vinc'); ?>", 'warning');

        const idProv = document.getElementById('provId').value;
        try {
            const res = await fetch('api/productos_proveedor.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    accion: 'vincular',
                    id_proveedor: idProv,
                    ids: ids
                })
            });
            const data = await res.json();
            if (data.success) {
                selectedForLinking = [];
                document.getElementById('countSelectedSearch').innerText = '0';
                lanzarBusquedaProductos(document.getElementById('searchProductosLibres').value);
                cargarProductosProveedor();
            }
        } catch (err) {
            showCustomAlert("<?php echo L('prod_js_error'); ?>", "<?php echo L('prod_js_error'); ?>", 'error');
        }
    }

    async function desvincularProducto(id) {
        showCustomConfirm(
            "<?php echo L('prov_js_unlink'); ?>",
            "<?php echo L('prov_js_unlink_confirm'); ?>",
            async () => {
                try {
                    const res = await fetch('api/productos_proveedor.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            accion: 'desvincular',
                            ids: [id]
                        })
                    });
                    const data = await res.json();
                    if (data.success) {
                        cargarProductosProveedor();
                        lanzarBusquedaProductos(document.getElementById('searchProductosLibres').value);
                    }
                } catch (err) {
                    showCustomAlert("<?php echo L('prod_js_error'); ?>", "<?php echo L('prod_js_error'); ?>", "error");
                }
            },
            "<?php echo L('prov_js_unlink'); ?>",
            'danger'
        );
    }

    async function guardarProveedor(e) {
        e.preventDefault();
        const data = {
            id: document.getElementById('provId').value || undefined,
            nombre: document.getElementById('provNombre').value,
            cif_nif: document.getElementById('provCif').value,
            telefono: document.getElementById('provTel').value,
            email: document.getElementById('provEmail').value,
            direccion: document.getElementById('provDireccion').value,
            condiciones_pago: document.getElementById('provCondicionesPago').value,
            plazo_entrega: document.getElementById('provPlazoEntrega').value,
            vencimiento_dias: parseInt(document.getElementById('provVencimientoDias').value) || 0,
            aplica_re: 1, // Siempre aplicado
            activo: document.getElementById('provActivo').checked ? 1 : 0
        };

        if (!validarDocumento(data.cif_nif)) {
            showCustomAlert("<?php echo L('client_js_invalid_doc'); ?>", "<?php echo L('client_js_invalid_doc_body'); ?>", 'warning');
            return;
        }

        if (!validarTelefono(data.telefono)) {
            showCustomAlert("<?php echo L('client_js_invalid_phone'); ?>", "<?php echo L('client_js_invalid_phone_body'); ?>", 'warning');
            return;
        }

        try {
            const res = await fetch('api/proveedores.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) window.location.reload();
            else showCustomAlert("<?php echo L('prod_js_error'); ?>", result.error || "<?php echo L('prod_js_error'); ?>", 'error');
        } catch (err) {
            showCustomAlert("<?php echo L('prod_js_error'); ?>", "<?php echo L('prod_js_error'); ?>", 'error');
        }
    }

    async function verHistorialProveedor(p) {
        document.getElementById('modalHistorialTitle').innerText = "<?php echo L('prov_modal_history_title'); ?> " + p.nombre;
        const tbody = document.getElementById('tbodyHistorial');
        tbody.innerHTML = '<tr><td colspan="5" class="text-center p-20"><?php echo L('loading'); ?></td></tr>';
        document.getElementById('modalHistorial').style.display = 'flex';

        try {
            const res = await fetch(`api/compras.php?type=albaranes&proveedor_id=${p.id}`);
            const data = await res.json();
            
            if (data && data.length > 0) {
                tbody.innerHTML = '';
                data.forEach(a => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="pl-20 font-bold">${a.numero_albaran}</td>
                        <td>${a.fecha}</td>
                        <td class="text-right font-mono">${parseFloat(a.total || 0).toFixed(2)}€</td>
                        <td class="text-center">
                            <span class="status-pill status-${a.estado === 'facturado' ? 'active' : 'pending'}">
                                ${a.estado.charAt(0).toUpperCase() + a.estado.slice(1)}
                            </span>
                        </td>
                        <td class="text-center pr-20">
                            <button class="btn-icon" onclick="window.location.href='index.php?irCompras=1&id_albaran=${a.id}'" title="<?php echo L('purchase_modal_details_title'); ?>">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center p-40 text-muted"><?php echo L('purchase_no_albaranes'); ?></td></tr>';
            }
        } catch (err) {
            console.error(err);
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-red p-20"><?php echo L('prod_js_error'); ?></td></tr>';
        }
    }

    function cerrarModalHistorial() {
        document.getElementById('modalHistorial').style.display = 'none';
    }
</script>
