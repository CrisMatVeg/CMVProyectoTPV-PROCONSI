<?php // Vista de gestión de clientes y socios 
?>
<style>
    .border-bottom-active {
        border-bottom: 2px solid var(--primary) !important;
        color: var(--primary) !important;
    }

    .transition {
        transition: all 0.2s ease;
    }

    .tab-btn {
        background: transparent;
        border: none;
        border-bottom: 2px solid transparent;
        outline: none;
        border-radius: 0;
        cursor: pointer;
    }

    .tab-btn:hover {
        background: rgba(0, 0, 0, 0.05);
    }
</style>
<div class="main-full p-24">
    <div class="section-header container-wider">
        <div class="section-title">
            <h1><?php echo L('dashboard_btn_clients'); ?></h1>
            <p><?php echo L('dashboard_btn_clients_sub'); ?></p>
        </div>
        <div class="d-flex gap-12">
            <button onclick="abrirModalCliente()" class="btn-add">
                <i class="fa-solid fa-user-plus"></i> <?php echo L('client_btn_new'); ?>
            </button>
            <a href="index.php?irDashboard=1" class="btn-back">
                <?php echo L('login_back'); ?>
            </a>
        </div>
    </div>

    <!-- BUSCADOR Y FILTROS -->
    <div class="filters-panel-new container-wider mb-24 br-20 shadow-sm" style="background: var(--surface2); padding: 20px; border: 1px solid var(--border);">
        <div class="flex-1">
            <div class="search-input-fancy" style="border-radius: 12px; background: var(--surface); padding-left: 15px;">
                <i class="fa-solid fa-magnifying-glass opacity-50"></i>
                <input type="text" id="filtroNombre" placeholder="<?php echo L('client_search_placeholder'); ?>" oninput="filtrarClientes()" style="height: 48px; border: none; background: transparent; width: 100%; padding-left: 10px;">
            </div>
        </div>

        <div class="filter-item">
            <span class="filter-label"><?php echo L('client_label_type'); ?></span>
            <select id="filtroTipo" class="filter-control" onchange="filtrarClientes()" style="min-width: 150px;">
                <option value=""><?php echo L('prod_filter_cat_all'); ?></option>
                <option value="particular"><?php echo L('client_type_particular'); ?></option>
                <option value="empresa"><?php echo L('client_type_empresa'); ?></option>
            </select>
        </div>

        <div class="filter-item">
            <span class="filter-label"><?php echo L('user_th_rol'); ?></span>
            <select id="filtroRol" class="filter-control" onchange="filtrarClientes()" style="min-width: 180px;">
                <option value=""><?php echo L('prod_filter_cat_all'); ?></option>
                <option value="socio"><?php echo L('client_rol_socio'); ?></option>
                <option value="mayorista"><?php echo L('client_rol_mayorista'); ?></option>
                <option value="general"><?php echo L('client_rol_general'); ?></option>
                <?php foreach ($avClientes['roles'] as $r): ?>
                    <?php if (!in_array(strtolower($r['nombre']), ['socio', 'mayorista', 'general'])): ?>
                        <option value="<?php echo htmlspecialchars(strtolower($r['nombre'])); ?>">
                            <?php echo htmlspecialchars(ucfirst($r['nombre'])); ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>

        <button onclick="limpiarFiltrosClientes()" class="btn-cancel">
            <i class="fa-solid fa-rotate-left"></i> 
            <span><?php echo L('prod_filter_btn_clear'); ?></span>
        </button>
    </div>

    <div class="d-flex ai-center jc-between mb-24 container-wider pl-40">
        <button onclick="document.getElementById('formNuevoCliente').style.display='flex'" class="btn-save h-44 px-20 shadow-sm" style="border-radius: 12px; font-weight: 600;">
            <i class="fa-solid fa-plus mr-8"></i> <?php echo L('client_btn_new'); ?>
        </button>
    </div>

    <div class="table-container container-wider br-20">
        <table class="data-table exclude-pagination" id="tablaClientes">
            <thead>
                <tr>
                    <th class="w-60 pl-20"><?php echo L('prod_th_id'); ?></th>
                    <th><?php echo L('prod_th_name'); ?></th>
                    <th><?php echo L('client_label_type'); ?></th>
                    <th><?php echo L('client_th_nif'); ?></th>
                    <th><?php echo L('user_th_email'); ?></th>
                    <th><?php echo L('client_label_phone'); ?></th>
                    <th class="text-center"><?php echo L('client_rol_socio'); ?></th>
                    <th class="text-center"><?php echo L('client_th_signup'); ?></th>
                    <th class="text-center pr-20"><?php echo L('prod_th_actions'); ?></th>
                </tr>
            </thead>
            <tbody id="tbodyClientes">
                <?php foreach ($avClientes['lista'] as $c): ?>
                    <tr data-id="<?php echo $c['id']; ?>"
                        data-nombre="<?php echo htmlspecialchars(strtolower(trim($c['nombre'] . ' ' . ($c['apellidos'] ?? '')))); ?>"
                        data-nif="<?php echo htmlspecialchars(strtolower($c['nif'] ?? '')); ?>"
                        data-email="<?php echo htmlspecialchars(strtolower($c['email'] ?? '')); ?>"
                        data-tipo="<?php echo htmlspecialchars(strtolower($c['tipo'] ?? '')); ?>"
                        data-rol="<?php echo htmlspecialchars(strtolower($c['rol'] ?? '')); ?>">
                        <td class="font-mono text-muted pl-20"><?php echo $c['id']; ?></td>
                        <td class="">
                            <?php echo htmlspecialchars(trim($c['nombre'] . ' ' . ($c['apellidos'] ?? ''))); ?>
                        </td>
                        <td>
                            <span class="status-pill">
                                <i class="fa-solid <?php echo $c['tipo'] === 'empresa' ? 'fa-building' : 'fa-user'; ?>"></i>
                                <?php echo L('client_type_' . $c['tipo'], true); ?>
                            </span>
                        </td>
                        <td class="font-mono"><?php echo htmlspecialchars($c['nif'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($c['email'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($c['telefono'] ?? ''); ?></td>
                        <td class="text-center">
                            <div class="d-flex flex-column gap-4 ai-center">
                                <?php if ($c['rol'] === 'socio'): ?>
                                    <span class="status-pill status-active" style="padding: 2px 8px; font-size: 10px;">
                                        <i class="fa-solid fa-id-card"></i> <?php echo mb_strtoupper(L('client_rol_socio', true)); ?>
                                    </span>
                                <?php elseif ($c['rol'] === 'mayorista'): ?>
                                    <span class="status-pill status-paid" style="padding: 2px 8px; font-size: 10px; background: rgba(156, 39, 176, 0.1); color: #9c27b0; border-color: rgba(156, 39, 176, 0.2);">
                                        <i class="fa-solid fa-truck-fast"></i> <?php echo mb_strtoupper(L('client_rol_mayorista', true)); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted fs-11" style="text-transform: uppercase;"><?php echo htmlspecialchars(L('client_rol_' . strtolower($c['rol'] ?? 'general'), true)); ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-center font-mono fs-12">
                            <?php 
                            $fechaAlta = $c['fecha_alta'] ?? null;
                            echo ($fechaAlta && strtotime($fechaAlta)) ? date('d/m/Y', strtotime($fechaAlta)) : '-'; 
                            ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex jc-center gap-8 pr-20">
                                <button class="btn-icon text-primary" title="<?php echo L('client_tip_history'); ?>" onclick="abrirHistorialCliente(<?php echo $c['id']; ?>)">
                                    <i class="fa-solid fa-file-invoice-dollar"></i>
                                </button>
                                <button class="btn-icon" title="<?php echo L('user_tip_edit'); ?>" onclick='editarCliente(<?php echo json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button class="btn-icon text-danger" title="<?php echo L('modal_delete'); ?>" onclick="eliminarCliente(<?php echo $c['id']; ?>)">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($avClientes['lista'])): ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i class="fa-solid fa-user-group"></i>
                                <?php echo L('client_no_clients'); ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Paginación -->
        <div class="pagination-footer mt-20 d-flex ai-center jc-between">
            <div class="pagination-info fs-12 text-muted">
                <?php 
                    $from = $avClientes['paginacion']['totalRegistros'] > 0 ? ($avClientes['paginacion']['actual'] - 1) * $avClientes['paginacion']['limit'] + 1 : 0;
                    $to = min($avClientes['paginacion']['actual'] * $avClientes['paginacion']['limit'], $avClientes['paginacion']['totalRegistros']);
                    echo str_replace(['{from}', '{to}', '{total}'], (array)[$from, $to, $avClientes['paginacion']['totalRegistros']], L('page_showing')); 
                ?>
            </div>
            <div class="pagination-controls d-flex gap-8">
                <button class="btn-icon" onclick="loadClients(1)" <?php echo $avClientes['paginacion']['actual'] == 1 ? 'disabled' : ''; ?> title="<?php echo L('page_first'); ?>">
                    <i class="fa-solid fa-angles-left"></i>
                </button>
                <button class="btn-secondary" onclick="loadClients(<?php echo $avClientes['paginacion']['actual'] - 1; ?>)" <?php echo $avClientes['paginacion']['actual'] == 1 ? 'disabled' : ''; ?>>
                    <i class="fa-solid fa-chevron-left"></i> <?php echo L('page_prev'); ?>
                </button>
                <span class="pagination-current fw-600 fs-13 d-flex ai-center px-12 br-8" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                    <?php echo str_replace(['{current}', '{total}'], [$avClientes['paginacion']['actual'], $avClientes['paginacion']['total']], L('page_info')); ?>
                </span>
                <button class="btn-secondary" onclick="loadClients(<?php echo $avClientes['paginacion']['actual'] + 1; ?>)" <?php echo $avClientes['paginacion']['actual'] >= $avClientes['paginacion']['total'] ? 'disabled' : ''; ?>>
                    <?php echo L('page_next'); ?> <i class="fa-solid fa-chevron-right"></i>
                </button>
                <button class="btn-icon" onclick="loadClients(<?php echo $avClientes['paginacion']['total']; ?>)" <?php echo $avClientes['paginacion']['actual'] >= $avClientes['paginacion']['total'] ? 'disabled' : ''; ?> title="<?php echo L('page_last'); ?>">
                    <i class="fa-solid fa-angles-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL ALTA/EDICIÓN CLIENTE -->
<div class="modal-overlay" id="clienteAdminModal">
    <div class="modal modal-content gap-16 ai-stretch w-modal-md" style="max-width: 800px; border-radius: 20px; overflow: hidden; border: 1px solid var(--border); box-shadow: var(--shadow-lg);">
        <div class="modal-header mb-0">
            <h2 class="m-0 fs-18" id="client_modal_new_title"><?php echo L('client_modal_new_title'); ?></h2>
            <button onclick="cerrarAdminModalCliente()" class="btn-close-modal">&times;</button>
        </div>
        <form id="clienteForm" class="modal-body p-20">
            <input type="hidden" id="clienteId">
            <div class="form-group">
                <label class="form-label"><?php echo L('client_label_type'); ?></label>
                <select id="clienteTipo" class="form-input">
                    <option value="particular"><?php echo L('client_type_particular'); ?></option>
                    <option value="empresa"><?php echo L('client_type_empresa'); ?></option>
                </select>
            </div>
            <div class="form-group mb-0 mt-10">
                <label class="form-label"><?php echo L('prod_modal_label_name'); ?></label>
                <input type="text" id="clienteNombre" class="form-input" required>
            </div>
            <div class="form-group mb-0 mt-10">
                <label class="form-label"><?php echo L('client_label_lastname'); ?></label>
                <input type="text" id="clienteApellidos" class="form-input">
            </div>
            <div class="form-group mb-0 mt-10">
                <label class="form-label"><?php echo L('client_th_nif'); ?></label>
                <input type="text" id="clienteNif" class="form-input font-mono">
            </div>
            <div class="form-group mb-0 mt-10">
                <label class="form-label"><?php echo L('user_label_email'); ?></label>
                <input type="email" id="clienteEmail" class="form-input">
            </div>
            <div class="form-group mb-0 mt-10">
                <label class="form-label"><?php echo L('client_label_phone'); ?></label>
                <input type="text" id="clienteTelefono" class="form-input">
            </div>
            <div class="form-group mb-0 mt-10">
                <label class="form-label"><?php echo L('client_label_notes'); ?></label>
                <textarea id="clienteNotas" class="form-input" rows="2"></textarea>
            </div>
            <div class="form-group mb-0 mt-10">
                    <span><?php echo L('client_label_rol'); ?></span>
                    <button type="button" class="btn-text fs-12 text-blue p-0" style="background:none; border:none; cursor:pointer;" onclick="crearNuevoRol()"><?php echo L('client_btn_new_rol'); ?></button>
                </label>
                <select id="clienteRol" class="form-input">
                    <?php if (!empty($avClientes['roles'])): ?>
                        <?php foreach ($avClientes['roles'] as $r): ?>
                            <option value="<?php echo htmlspecialchars(strtolower($r['nombre'])); ?>">
                                <?php echo htmlspecialchars(ucfirst($r['nombre'])); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="general"><?php echo L('client_rol_general'); ?></option>
                    <?php endif; ?>
                </select>
            </div>
        </form>
        <div class="modal-footer full-width">
            <button onclick="cerrarAdminModalCliente()" class="btn-cancel"><?php echo L('modal_cancel'); ?></button>
            <button onclick="guardarCliente()" class="btn-save"><?php echo L('modal_save'); ?></button>
        </div>
    </div>
</div>

<!-- MODAL HISTORIAL CLIENTE -->
<div class="modal-overlay" id="historialClienteModal">
    <div class="modal modal-content gap-16 ai-stretch w-900" style="max-width: 900px; border-radius: 20px; overflow: hidden; border: 1px solid var(--border); box-shadow: var(--shadow-lg);">
        <div class="modal-header mb-0">
            <h2 class="m-0 fs-18" id="historialTitle"><?php echo L('client_modal_history_title'); ?></h2>
            <button onclick="cerrarAdminHistorialModal()" class="btn-close-modal">&times;</button>
        </div>
        <div class="modal-body p-0" style="max-height: 70vh; overflow-y: auto;">
            <!-- Tabs -->
            <div class="d-flex border-bottom bg-surface2">
                <button onclick="switchHistorialTab('ventas')" id="tabVentas" class="tab-btn flex-1 p-12 fs-13 fw-600 transition border-bottom-active">
                    <i class="fa-solid fa-file-invoice-dollar mr-8"></i><?php echo L('client_tab_sales'); ?>
                </button>
                <button onclick="switchHistorialTab('vales')" id="tabVales" class="tab-btn flex-1 p-12 fs-13 fw-600 transition">
                    <i class="fa-solid fa-ticket mr-8"></i><?php echo L('client_tab_vouchers'); ?>
                </button>
                <button onclick="switchHistorialTab('puntos')" id="tabPuntos" class="tab-btn flex-1 p-12 fs-13 fw-600 transition">
                    <i class="fa-solid fa-star mr-8"></i><?php echo L('tpv_loyalty_points'); ?>
                </button>
            </div>

            <!-- Contenido Ventas -->
            <div id="contentVentas" class="p-20">
                <table class="data-table">
                    <thead class="bg-surface2">
                        <tr>
                            <th><?php echo L('prod_th_date'); ?></th>
                            <th><?php echo L('hist_th_ticket'); ?></th>
                            <th><?php echo L('prod_th_status'); ?></th>
                            <th class="text-right"><?php echo L('tpv_total'); ?></th>
                            <th class="text-right"><?php echo L('client_th_paid'); ?></th>
                            <th class="text-right"><?php echo L('client_th_pending'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="historialBody">
                        <tr>
                            <td colspan="6" class="text-center text-muted"><?php echo L('loading'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Contenido Vales -->
            <div id="contentVales" class="p-20 d-none">
                <table class="data-table">
                    <thead class="bg-surface2">
                        <tr>
                            <th><?php echo L('prod_th_date'); ?></th>
                            <th><?php echo L('prod_th_code'); ?></th>
                            <th class="text-right"><?php echo L('client_th_amount'); ?></th>
                            <th class="text-right"><?php echo L('client_th_remaining'); ?></th>
                            <th class="text-center"><?php echo L('prod_th_status'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="valesBody">
                        <tr>
                            <td colspan="5" class="text-center text-muted"><?php echo L('client_no_vouchers'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Contenido Puntos -->
            <div id="contentPuntos" class="p-20 d-none">
                <div class="d-flex ai-center jc-space-between p-16 br-20 bg-surface1 border-2 mb-16">
                    <div class="d-flex ai-center gap-12">
                        <div class="br-12 bg-accent-light p-12 text-accent">
                            <i class="fa-solid fa-star fs-24"></i>
                        </div>
                        <div>
                            <div class="fs-12 text-muted fw-600 tt-uppercase"><?php echo L('client_points_balance_label'); ?></div>
                            <div class="fs-28 font-mono font-bold text-accent" id="puntosBalanceValue">0</div>
                        </div>
                    </div>
                </div>
                <div id="puntosHistoryContainer">
                    <!-- Future point history could go here -->
                </div>
            </div>
        </div>
        <div class="modal-footer full-width jc-end">
            <button onclick="cerrarAdminHistorialModal()" class="btn-cancel"><?php echo L('modal_cancel'); ?></button>
        </div>
    </div>
</div>

<script>
    async function guardarCliente() {
        const payload = {
            id: document.getElementById('clienteId').value || null,
            tipo: document.getElementById('clienteTipo').value,
            nombre: document.getElementById('clienteNombre').value.trim(),
            apellidos: document.getElementById('clienteApellidos').value.trim(),
            nif: document.getElementById('clienteNif').value.trim(),
            email: document.getElementById('clienteEmail').value.trim(),
            telefono: document.getElementById('clienteTelefono').value.trim(),
            notas: document.getElementById('clienteNotas').value.trim(),
            rol: document.getElementById('clienteRol').value,
        };

        if (!validarDocumento(payload.nif)) {
            showCustomAlert("<?php echo L('client_js_invalid_doc'); ?>", "<?php echo L('client_js_invalid_doc_body'); ?>", 'warning');
            return;
        }

        if (!validarTelefono(payload.telefono)) {
            showCustomAlert("<?php echo L('client_js_invalid_phone'); ?>", "<?php echo L('client_js_invalid_phone_body'); ?>", 'warning');
            return;
        }

        try {
            const resp = await fetch('api/gestionCliente.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload),
            });
            const data = await resp.json();
            if (!data.ok) {
                showCustomAlert("<?php echo L('prod_js_error'); ?>", data.error || "<?php echo L('prod_js_error'); ?>", 'error');
                return;
            }
            location.reload();
        } catch (e) {
            console.error(e);
            showCustomAlert("<?php echo L('prod_js_error'); ?>", "<?php echo L('prod_js_error'); ?>", 'error');
        }
    }

    function abrirModalCliente() {
        document.getElementById('clienteId').value = '';
        document.getElementById('clienteTipo').value = 'particular';
        document.getElementById('clienteNombre').value = '';
        document.getElementById('clienteApellidos').value = '';
        document.getElementById('clienteNif').value = '';
        document.getElementById('clienteEmail').value = '';
        document.getElementById('clienteTelefono').value = '';
        document.getElementById('clienteNotas').value = '';
        document.getElementById('clienteRol').value = 'general';
        document.getElementById('client_modal_new_title').innerText = "<?php echo L('client_modal_new_title'); ?>";
        document.getElementById('clienteAdminModal').classList.add('visible');
    }

    function editarCliente(c) {
        document.getElementById('clienteId').value = c.id;
        document.getElementById('clienteTipo').value = c.tipo;
        document.getElementById('clienteNombre').value = c.nombre;
        document.getElementById('clienteApellidos').value = c.apellidos || '';
        document.getElementById('clienteNif').value = c.nif || '';
        document.getElementById('clienteEmail').value = c.email || '';
        document.getElementById('clienteTelefono').value = c.telefono || '';
        document.getElementById('clienteNotas').value = c.notas || '';
        document.getElementById('clienteRol').value = c.rol || 'general';
        document.getElementById('client_modal_new_title').innerText = "<?php echo L('client_modal_edit_title'); ?>";

        document.getElementById('clienteAdminModal').classList.add('visible');
    }

    function cerrarAdminModalCliente() {
        document.getElementById('clienteAdminModal').classList.remove('visible');
    }

    async function eliminarCliente(id) {
        if (!id) return;
        showCustomConfirm(
            "<?php echo L('client_js_delete_title'); ?>",
            "<?php echo L('client_js_delete_body'); ?>",
            async () => {
                    try {
                        const resp = await fetch('api/gestionCliente.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                accion: 'eliminar',
                                id: id
                            }),
                        });
                        const data = await resp.json();
                        if (!data.ok) {
                            showCustomAlert("<?php echo L('prod_js_error'); ?>", data.error || "<?php echo L('prod_js_error'); ?>", 'error');
                            return;
                        }
                        location.reload();
                    } catch (e) {
                        console.error(e);
                        showCustomAlert("<?php echo L('error'); ?>", "<?php echo L('prod_js_error'); ?>", 'error');
                    }
                },
                "<?php echo L('client_js_delete_btn'); ?>",
                'danger'
        );
    }

    function switchHistorialTab(tab) {
        document.getElementById('contentVentas').classList.toggle('d-none', tab !== 'ventas');
        document.getElementById('contentVales').classList.toggle('d-none', tab !== 'vales');
        document.getElementById('contentPuntos').classList.toggle('d-none', tab !== 'puntos');

        document.getElementById('tabVentas').classList.toggle('border-bottom-active', tab === 'ventas');
        document.getElementById('tabVales').classList.toggle('border-bottom-active', tab === 'vales');
        document.getElementById('tabPuntos').classList.toggle('border-bottom-active', tab === 'puntos');
    }

    async function abrirHistorialCliente(id) {
        document.getElementById('historialClienteModal').classList.add('visible');
        switchHistorialTab('ventas'); // Reset tab to first one

        const tbodyVentas = document.getElementById('historialBody');
        const tbodyVales = document.getElementById('valesBody');

        tbodyVentas.innerHTML = '<tr><td colspan="6" class="text-center text-muted"><?php echo L('loading'); ?></td></tr>';
        tbodyVales.innerHTML = '<tr><td colspan="5" class="text-center text-muted"><?php echo L('loading'); ?></td></tr>';

        try {
            const resp = await fetch('api/gestionCliente.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    accion: 'historial',
                    id: id
                }),
            });
            const data = await resp.json();
            if (!data.ok) {
                tbodyVentas.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Error: ${data.error}</td></tr>`;
                return;
            }

            // --- RENDER VENTAS ---
            if (data.ventas.length === 0) {
                tbodyVentas.innerHTML = '<tr><td colspan="6" class="text-center text-muted"><?php echo L('client_no_sales'); ?></td></tr>';
            } else {
                let htmlV = '';
                for (const v of data.ventas) {
                    const total = parseFloat(v.total) || 0;
                    const pagado = parseFloat(v.pagado_a_cuenta) || 0;
                    let pendiente = (v.estado === 'pendiente_pago') ? (total - pagado) : 0;
                    const fechaStr = new Date(v.fecha).toLocaleString("<?php echo L('locale'); ?>", {
                        dateStyle: 'short',
                        timeStyle: 'short'
                    });

                    let badgeClass = 'status-active';
                    if (v.estado === 'pendiente_pago') badgeClass = 'status-pending';
                    else if (['devuelta', 'anulada'].includes(v.estado)) badgeClass = 'status-cancelled';

                    htmlV += `<tr>
                        <td>${fechaStr}</td>
                        <td class="font-mono">#${v.numero_ticket}</td>
                        <td><span class="status-pill ${badgeClass}" style="padding: 2px 8px; font-size: 10px; text-transform: uppercase;">${v.estado.replace('_', ' ')}</span></td>
                        <td class="text-right font-mono">${total.toFixed(2)}€</td>
                        <td class="text-right font-mono">${v.estado === 'pendiente_pago' ? pagado.toFixed(2) + '€' : '-'}</td>
                        <td class="text-right font-mono fw-bold ${pendiente > 0 ? 'text-danger' : 'text-success'}">${pendiente.toFixed(2)}€</td>
                    </tr>`;
                }
                tbodyVentas.innerHTML = htmlV;
            }

            // --- RENDER VALES ---
            if (!data.vales || data.vales.length === 0) {
                tbodyVales.innerHTML = '<tr><td colspan="5" class="text-center text-muted"><?php echo L('client_no_vouchers'); ?></td></tr>';
            } else {
                let htmlVales = '';
                for (const val of data.vales) {
                    const fechaVal = new Date(val.fecha_creacion).toLocaleDateString("<?php echo L('locale'); ?>");
                    const isActivo = val.estado === 'activo';
                    htmlVales += `<tr>
                        <td>${fechaVal}</td>
                        <td class="font-mono fw-bold">${val.codigo}</td>
                        <td class="text-right font-mono">${parseFloat(val.importe).toFixed(2)}€</td>
                        <td class="text-right font-mono fw-bold ${isActivo ? 'text-primary' : 'text-muted'}">${parseFloat(val.importe_restante).toFixed(2)}€</td>
                        <td class="text-center">
                            <span class="status-pill ${isActivo ? 'status-active' : 'status-cancelled'}" style="padding: 2px 8px; font-size: 10px; text-transform: uppercase;">
                                ${val.estado}
                            </span>
                        </td>
                    </tr>`;
                }
                tbodyVales.innerHTML = htmlVales;
            }

            // --- RENDER PUNTOS ---
            document.getElementById('puntosBalanceValue').textContent = data.cliente ? (data.cliente.puntos || 0) : 0;

        } catch (e) {
            console.error(e);
            tbodyVentas.innerHTML = '<tr><td colspan="6" class="text-center text-danger"><?php echo L('prod_js_error'); ?></td></tr>';
        }
    }

    function cerrarAdminHistorialModal() {
        document.getElementById('historialClienteModal').classList.remove('visible');
    }

    async function crearNuevoRol() {
        showCustomPrompt(
            "<?php echo L('client_js_new_rol_title'); ?>",
            "<?php echo L('client_js_new_rol_prompt'); ?>",
            async (nombre) => {
                    if (!nombre || nombre.trim() === '') return;

                    try {
                        const resp = await fetch('api/gestionRolCliente.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                accion: 'crear',
                                nombre: nombre
                            }),
                        });
                        const data = await resp.json();
                        if (!data.ok) {
                            showCustomAlert("<?php echo L('prod_js_error'); ?>", data.error || "<?php echo L('prod_js_error'); ?>", 'error');
                            return;
                        }

                        // Notificar éxito y recargar para que aparezca en el PHP
                        showNotification('<i class="fa-solid fa-circle-check"></i> <?php echo L('client_js_rol_created'); ?>', 'success');
                        setTimeout(() => location.reload(), 800);
                    } catch (e) {
                        console.error(e);
                        showCustomAlert("<?php echo L('prod_js_error'); ?>", "<?php echo L('prod_js_error'); ?>", 'error');
                    }
                },
                '',
                'Ej: VIP, VIP+, etc.'
        );
    }

    let paginationData = <?php echo json_encode($avClientes['paginacion']); ?>;
    let currentFilters = {
        term: '',
        tipo: '',
        rol: ''
    };

    function debounce(func, timeout = 300) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => { func.apply(this, args); }, timeout);
        };
    }

    const filtrarClientes = debounce(async () => {
        currentFilters.term = document.getElementById('filtroNombre').value.toLowerCase().trim();
        currentFilters.tipo = document.getElementById('filtroTipo').value;
        currentFilters.rol = document.getElementById('filtroRol').value;
        
        await loadClients(1);
    }, 400);

    async function loadClients(page) {
        if (page < 1 || (paginationData.total > 0 && page > paginationData.total)) return;

        const limit = paginationData.limit;
        const offset = (page - 1) * limit;
        
        // Mostrar Loading
        const tbody = document.getElementById('tbodyClientes');
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-40"><i class="fa-solid fa-circle-notch fa-spin fa-2x text-muted"></i></td></tr>';

        try {
            const resp = await fetch('api/gestionCliente.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    accion: 'buscarTexto',
                    term: currentFilters.term,
                    tipo: currentFilters.tipo,
                    rol: currentFilters.rol,
                    limit: limit,
                    offset: offset,
                    force: true // Para que permita búsqueda vacía
                })
            });
            const data = await resp.json();
            
            if (!data.ok) throw new Error(data.error);

            // Re-calcular total si ha cambiado por el filtro
            // Nota: En la API buscarTexto no devolvemos el total real de la búsqueda, 
            // idealmente deberíamos modificar la API para devolverlo.
            // Por ahora, si hay filtro, estimamos o simplemente mostramos.
            // Para ser rigurosos, usaré el 'total' que devuelve la API si lo implementamos.
            
            // Actualizar tabla
            renderTable(data.lista);
            
            // Actualizar metadata de paginación
            // Simulamos total para que la UI no se rompa si la API no lo da aún
            // (En el próximo paso me aseguro de que la API lo dé)
            const totalRecords = data.total !== undefined ? data.total : (currentFilters.term ? data.lista.length : paginationData.totalRegistros);
            const totalPaginas = Math.max(1, Math.ceil(totalRecords / limit));
            
            paginationData.actual = page;
            paginationData.total = totalPaginas;
            paginationData.totalRegistros = totalRecords;

            updatePaginationUI();

        } catch (e) {
            console.error(e);
            tbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger py-40">${e.message || "<?php echo L('prod_js_error'); ?>"}</td></tr>`;
        }
    }

    function renderTable(lista) {
        const tbody = document.getElementById('tbodyClientes');
        if (lista.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center py-40 text-muted"><?php echo L('user_no_results'); ?></td></tr>';
            return;
        }

        let html = '';
        lista.forEach(c => {
            const nombreCompleto = (c.nombre || '') + ' ' + (c.apellidos || '');
            const rolPill = getRolPill(c.rol);
            const tipoIcon = c.tipo === 'empresa' ? 'fa-building' : 'fa-user';
            const fechaAlta = new Date(c.fecha_alta).toLocaleDateString();

            html += `
                <tr data-id="${c.id}" 
                    data-nombre="${nombreCompleto.toLowerCase()}" 
                    data-nif="${(c.nif || '').toLowerCase()}" 
                    data-email="${(c.email || '').toLowerCase()}"
                    data-tipo="${(c.tipo || '').toLowerCase()}"
                    data-rol="${(c.rol || '').toLowerCase()}">
                    <td class="font-mono text-muted pl-20">${c.id}</td>
                    <td class="">${nombreCompleto}</td>
                    <td><span class="status-pill"><i class="fa-solid ${tipoIcon}"></i> ${capitalizeFirst(c.tipo)}</span></td>
                    <td class="font-mono">${c.nif || ''}</td>
                    <td>${c.email || ''}</td>
                    <td>${c.telefono || ''}</td>
                    <td class="text-center">${rolPill}</td>
                    <td class="text-center font-mono fs-12">${fechaAlta}</td>
                    <td class="text-center">
                        <div class="d-flex jc-center gap-8 pr-20">
                            <button class="btn-icon text-primary" onclick="abrirHistorialCliente(${c.id})"><i class="fa-solid fa-file-invoice-dollar"></i></button>
                            <button class="btn-icon" onclick='editarCliente(${JSON.stringify(c)})'><i class="fa-solid fa-pen"></i></button>
                            <button class="btn-icon text-danger" onclick="eliminarCliente(${c.id})"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    }

    function getRolPill(rol) {
        rol = (rol || 'general').toLowerCase();
        if (rol === 'socio') return '<span class="status-pill status-active" style="padding: 2px 8px; font-size: 10px;"><i class="fa-solid fa-id-card"></i> SOCIO</span>';
        if (rol === 'mayorista') return '<span class="status-pill status-paid" style="padding: 2px 8px; font-size: 10px; background: rgba(156, 39, 176, 0.1); color: #9c27b0; border-color: rgba(156, 39, 176, 0.2);"><i class="fa-solid fa-truck-fast"></i> MAYORISTA</span>';
        return `<span class="text-muted fs-11" style="text-transform: uppercase;">${rol}</span>`;
    }

    function capitalizeFirst(s) {
        return s.charAt(0).toUpperCase() + s.slice(1);
    }

    function updatePaginationUI() {
        const from = paginationData.totalRegistros > 0 ? (paginationData.actual - 1) * paginationData.limit + 1 : 0;
        const to = Math.min(paginationData.actual * paginationData.limit, paginationData.totalRegistros);
        
        let infoText = "<?php echo L('page_showing'); ?>";
        infoText = infoText.replace('{from}', from).replace('{to}', to).replace('{total}', paginationData.totalRegistros);
        
        document.querySelector('.pagination-info').textContent = infoText;
        document.querySelector('.pagination-current').textContent = `<?php echo L('page_info'); ?>`.replace('{current}', paginationData.actual).replace('{total}', paginationData.total);
        
        const controls = document.querySelector('.pagination-controls');
        const btns = controls.querySelectorAll('button');
        
        btns[0].onclick = () => loadClients(1);
        btns[0].disabled = paginationData.actual === 1;
        
        btns[1].onclick = () => loadClients(paginationData.actual - 1);
        btns[1].disabled = paginationData.actual === 1;
        
        btns[2].onclick = () => loadClients(paginationData.actual + 1);
        btns[2].disabled = paginationData.actual >= paginationData.total;
        
        btns[3].onclick = () => loadClients(paginationData.total);
        btns[3].disabled = paginationData.actual >= paginationData.total;
    }

    function limpiarFiltrosClientes() {
        document.getElementById('filtroNombre').value = '';
        document.getElementById('filtroTipo').value = '';
        document.getElementById('filtroRol').value = '';
        currentFilters = { term: '', tipo: '', rol: '' };
        loadClients(1);
    }

    // Inicializar contador al cargar
    document.addEventListener('DOMContentLoaded', () => {
        // El primer render ya viene del PHP, pero configuramos los eventos
        const inputBusqueda = document.getElementById('filtroNombre');
        inputBusqueda.addEventListener('input', filtrarClientes);
    });
</script>