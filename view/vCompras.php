<div class="main-full p-24">

    <!-- CABECERA DE SECCIÓN -->
    <div class="section-header container-wider">
        <div class="section-title">
            <h1>Gestión de Compras y Existencias</h1>
            <p>Controla las entradas de mercadería (Albaranes) y su posterior facturación y pago.</p>
        </div>
        <div class="d-flex gap-12 ai-center">
            <a href="index.php?irDashboard=1" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>
            <button onclick="abrirModalNuevoAlbaran()" class="btn-add">
                <i class="fa-solid fa-truck-ramp-box"></i> Nuevo Albarán
            </button>
            <button onclick="abrirModalNuevaFactura()" class="btn-save" style="background: var(--accent);">
                <i class="fa-solid fa-file-invoice-dollar"></i> Registrar Factura
            </button>
        </div>
    </div>

    <!-- TABS -->
    <div class="container-wider mb-24">
        <div class="d-flex gap-24 border-bottom pb-8">
            <button class="tab-btn active" onclick="switchTab('albaranes', this)">
                <i class="fa-solid fa-boxes-stacked mr-8"></i> Albaranes / Entradas
            </button>
            <button class="tab-btn" onclick="switchTab('facturas', this)">
                <i class="fa-solid fa-file-invoice mr-8"></i> Facturas Recibidas
            </button>
        </div>
    </div>

    <!-- SECCIÓN ALBARANES -->
    <div id="tab-albaranes" class="purchase-tab-content">
        <div class="table-container container-wider">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="w-180 pl-20">Fecha</th>
                        <th>Nº Albarán</th>
                        <th>Proveedor</th>
                        <th class="text-right">Importe</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center pr-20">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($avInicioPrivado['historico_albaranes'])): ?>
                        <tr>
                            <td colspan="6" class="p-40 text-center text-muted">No hay albaranes registrados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($avInicioPrivado['historico_albaranes'] as $a): ?>
                            <tr>
                                <td class="pl-20 font-mono text-muted"><?php echo date('d/m/Y', strtotime($a['fecha'])); ?></td>
                                <td class="font-bold"><?php echo htmlspecialchars($a['numero_albaran']); ?></td>
                                <td><?php echo htmlspecialchars($a['proveedor_nombre']); ?></td>
                                <td class="text-right font-mono"><?php echo number_format($a['total'], 2, ',', '.'); ?> €</td>
                                <td class="text-center">
                                    <span class="badge <?php echo $a['estado'] === 'pendiente' ? 'badge-warning' : 'badge-success'; ?>">
                                        <?php echo ucfirst($a['estado']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn-icon" onclick="verDetalleAlbaran(<?php echo $a['id']; ?>)">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
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
        <div class="table-container container-wider">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="w-180 pl-20">Fecha Factura</th>
                        <th>Nº Factura</th>
                        <th>Proveedor</th>
                        <th class="text-right">Total</th>
                        <th class="text-center">Pago</th>
                        <th class="text-center pr-20">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($avInicioPrivado['historico_facturas'])): ?>
                        <tr>
                            <td colspan="6" class="p-40 text-center text-muted">No hay facturas registradas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($avInicioPrivado['historico_facturas'] as $f): ?>
                            <tr>
                                <td class="pl-20 font-mono text-muted"><?php echo date('d/m/Y', strtotime($f['fecha_factura'])); ?></td>
                                <td class="font-bold"><?php echo htmlspecialchars($f['numero_factura']); ?></td>
                                <td><?php echo htmlspecialchars($f['proveedor_nombre']); ?></td>
                                <td class="text-right font-bold font-mono text-accent">
                                    <?php echo number_format($f['total'], 2, ',', '.'); ?> €
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-info">
                                        <i class="fa-solid <?php echo $f['metodo_pago'] === 'caja' ? 'fa-cash-register' : 'fa-building-columns'; ?> mr-4"></i>
                                        <?php echo ucfirst($f['metodo_pago']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn-icon" onclick="verDetalleFactura(<?php echo $f['id']; ?>)">
                                        <i class="fa-solid fa-file-lines"></i>
                                    </button>
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
<div id="modalNuevoAlbaran" class="modal-overlay-bg">
    <div class="modal-content" style="max-width: 1000px; width: 95%;">
        <div class="modal-header">
            <h2>Registrar Entrada de Mercadería (Albarán)</h2>
            <button class="btn-close-modal" onclick="cerrarModalNuevoAlbaran()">&times;</button>
        </div>

        <div class="p-24 overflow-y-auto" style="max-height: 80vh;">
            <div class="d-grid grid-3 gap-16 mb-24 p-16 bg-surface2 br-12 border-2">
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase">Proveedor *</label>
                    <select id="albProveedor" class="form-input" onchange="actualizarREAlbaran()">
                        <option value="">-- Seleccionar Proveedor --</option>
                        <?php foreach ($avInicioPrivado['proveedores'] as $p): ?>
                            <option value="<?php echo $p['id']; ?>" data-re="<?php echo $p['aplica_re'] ? '1' : '0'; ?>">
                                <?php echo htmlspecialchars($p['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase">Nº Albarán / Referencia *</label>
                    <input type="text" id="albNum" class="form-input font-mono" placeholder="Ej: ALB-2024-001">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase">Fecha Entrada</label>
                    <input type="date" id="albFecha" class="form-input" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>

            <div class="search-bar mb-16">
                <div class="search-input-wrap flex-1">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="albBusqueda" class="search-input" placeholder="Buscar producto para añadir al albarán..." onkeyup="buscarProductoAlbaran(this.value)">
                    <div id="albResultados" class="search-results-dropdown d-none"></div>
                </div>
            </div>

            <div class="table-container mb-24" style="max-height: 400px; overflow-y: auto;">
                <table class="data-table mb-0">
                    <thead style="position: sticky; top: 0; z-index: 10;">
                        <tr>
                            <th>Producto</th>
                            <th class="w-100 text-center">Cantidad</th>
                            <th class="w-180 text-right">Coste Neto (€)</th>
                            <th class="w-80 text-center">IVA</th>
                            <th class="w-80 text-center th-re">RE</th>
                            <th class="w-180 text-right">Total Línea</th>
                            <th class="w-60"></th>
                        </tr>
                    </thead>
                    <tbody id="albLineas"></tbody>
                </table>
            </div>

            <div class="d-grid grid-2-1 gap-24 ai-start mb-16">
                <div class="p-16 br-12 border-2 bg-blue-light border-blue d-flex ai-center gap-12">
                    <i class="fa-solid fa-circle-info text-accent fs-24"></i>
                    <p class="fs-12 m-0">
                        El albarán incrementará el <strong>stock</strong> y actualizará el <strong>precio de coste (CMP)</strong>.
                        Quedará marcado como "Pendiente de Facturar" hasta que registres la factura correspondiente.
                    </p>
                </div>
                <div class="bg-surface p-20 br-16 border-2 shadow-sm text-right">
                    <div class="d-flex jc-space-between mb-12 ai-center">
                        <span class="font-bold text-accent">TOTAL ALBARÁN</span>
                        <span class="fs-24 font-bold text-accent font-mono"><span id="albTotal">0,00</span> €</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer pt-16 border-top p-24">
            <button class="btn-cancel" onclick="cerrarModalNuevoAlbaran()">Cancelar</button>
            <div class="flex-1"></div>
            <button id="btnGuardarAlbaran" class="btn-save w-auto px-32" onclick="guardarAlbaran()">
                <i class="fa-solid fa-save mr-8"></i> Procesar Albarán
            </button>
        </div>
    </div>
</div>

<!-- MODAL REGISTRAR FACTURA (Billing and Payment) -->
<div id="modalNuevaFactura" class="modal-overlay-bg">
    <div class="modal-content w-700">
        <div class="modal-header">
            <h2>Registrar Factura de Proveedor</h2>
            <button class="btn-close-modal" onclick="cerrarModalNuevaFactura()">&times;</button>
        </div>

        <div class="p-24">
            <div class="d-grid grid-2 gap-16 mb-24">
                <div class="form-group">
                    <label class="form-label fs-11 tt-uppercase">Proveedor</label>
                    <select id="facProveedor" class="form-input" onchange="cargarAlbaranesPendientes()">
                        <option value="">-- Seleccionar Proveedor --</option>
                        <?php foreach ($avInicioPrivado['proveedores'] as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label fs-11 tt-uppercase">Nº Factura Oficial *</label>
                    <input type="text" id="facNum" class="form-input font-mono" placeholder="Ej: 2024/FACT-001">
                </div>
                <div class="form-group">
                    <label class="form-label fs-11 tt-uppercase">Fecha Factura</label>
                    <input type="date" id="facFecha" class="form-input" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label fs-11 tt-uppercase">Medio de Pago</label>
                    <select id="facPago" class="form-input">
                        <option value="banco">Transferencia / Banco</option>
                        <option value="caja">Efectivo (Caja TPV)</option>
                        <option value="otro">Otros</option>
                    </select>
                </div>
            </div>

            <div class="mb-8 fs-12 fw-700 tt-uppercase opacity-50">Albaranes pendientes de facturar</div>
            <div id="listaAlbaranesPendientes" class="border-2 br-12 p-8 overflow-y-auto mb-24" style="max-height: 200px; background: var(--surface2);">
                <div class="p-20 text-center opacity-50 fs-13">Selecciona un proveedor para ver sus albaranes</div>
            </div>

            <div class="bg-surface p-20 br-16 border-2 text-right">
                <div class="d-flex jc-space-between ai-center">
                    <span class="font-bold text-accent">TOTAL A PAGAR</span>
                    <span class="fs-24 font-bold text-accent font-mono"><span id="facTotal">0,00</span> €</span>
                </div>
            </div>
        </div>

        <div class="modal-footer pt-16 border-top p-24">
            <button class="btn-cancel" onclick="cerrarModalNuevaFactura()">Cancelar</button>
            <div class="flex-1"></div>
            <button id="btnGuardarFactura" class="btn-save w-auto px-32" onclick="guardarFactura()" disabled>
                <i class="fa-solid fa-file-invoice-dollar mr-8"></i> Generar Factura y Pago
            </button>
        </div>
    </div>
</div>

<!-- MODAL DETALLES -->
<div id="modalDetalle" class="modal-overlay-bg">
    <div class="modal-content w-600">
        <div class="modal-header">
            <h2 id="detalleTitulo">Detalles</h2>
            <button class="btn-close-modal" onclick="cerrarModalDetalle()">&times;</button>
        </div>
        <div id="detalleContent" class="p-24 overflow-y-auto" style="max-height: 70vh;"></div>
        <div class="modal-footer pt-16 border-top p-24">
            <button class="btn-cancel" onclick="cerrarModalDetalle()">Cerrar</button>
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
        albAplicaRE = sel.options[sel.selectedIndex].dataset.re === '1';
        document.getElementById('modalNuevoAlbaran').classList.toggle('has-re', albAplicaRE);
        calcularTotalAlbaran();
    }

    function buscarProductoAlbaran(q) {
        const dd = document.getElementById('albResultados');
        if (q.length < 2) return dd.classList.add('d-none');
        const res = productosDB.filter(p => p.nombre.toLowerCase().includes(q.toLowerCase()) || p.referencia.toLowerCase().includes(q.toLowerCase())).slice(0, 10);
        if (res.length > 0) {
            dd.innerHTML = res.map(p => `<div class="search-result-item" onclick="añadirLineaAlbaran(${p.id})"><b>${p.nombre}</b> <small>(${p.referencia})</small></div>`).join('');
            dd.classList.remove('d-none');
        } else dd.classList.add('d-none');
    }

    function añadirLineaAlbaran(id) {
        const p = productosDB.find(x => x.id === id);
        if (albLineas.find(l => l.producto_id === id)) return alert('Ya añadido');
        let rePct = 0;
        if (p.iva >= 21) rePct = 5.2;
        else if (p.iva >= 10) rePct = 1.4;
        else if (p.iva >= 4) rePct = 0.5;
        albLineas.push({
            producto_id: p.id,
            nombre: p.nombre,
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
        document.getElementById('albLineas').innerHTML = albLineas.map((l, i) => `
            <tr>
                <td class="font-bold pl-20">${l.nombre}</td>
                <td><input type="number" class="form-input text-center w-80" value="${l.cantidad}" onchange="albLineas[${i}].cantidad=parseFloat(this.value);renderLineasAlbaran()"></td>
                <td><input type="number" class="form-input text-right w-120" value="${l.precio_coste_neto}" step="0.01" onchange="albLineas[${i}].precio_coste_neto=parseFloat(this.value);renderLineasAlbaran()"></td>
                <td class="text-center opacity-70">${l.iva_pct}%</td>
                <td class="text-center opacity-70 th-re">${l.re_pct}%</td>
                <td class="text-right font-bold">${(l.cantidad * l.precio_coste_neto * (1 + (l.iva_pct / 100) + (albAplicaRE ? l.re_pct / 100 : 0))).toFixed(2)} €</td>
                <td><button class="btn-icon text-red" onclick="albLineas.splice(${i},1);renderLineasAlbaran()"><i class="fa-solid fa-trash"></i></button></td>
            </tr>
        `).join('');
        calcularTotalAlbaran();
    }

    function calcularTotalAlbaran() {
        let total = 0;
        albLineas.forEach(l => total += l.cantidad * l.precio_coste_neto * (1 + (l.iva_pct / 100) + (albAplicaRE ? l.re_pct / 100 : 0)));
        document.getElementById('albTotal').innerText = total.toLocaleString('es-ES', {
            minimumFractionDigits: 2
        });
    }

    async function guardarAlbaran() {
        const prov = document.getElementById('albProveedor').value;
        const num = document.getElementById('albNum').value;
        if (!prov || !num || albLineas.length === 0) return alert('Datos incompletos');
        const btn = document.getElementById('btnGuardarAlbaran');
        btn.disabled = true;
        try {
            const r = await fetch('api/compras.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'registrar_albaran',
                    proveedor_id: prov,
                    numero_albaran: num,
                    fecha: document.getElementById('albFecha').value,
                    lineas: albLineas
                })
            });
            const res = await r.json();
            if (res.success) window.location.reload();
            else alert('Error: ' + res.error);
        } catch (e) {
            alert('Error de conexión');
        } finally {
            btn.disabled = false;
        }
    }

    // --- LÓGICA FACTURAS ---
    function abrirModalNuevaFactura() {
        albaranesSeleccionados = [];
        document.getElementById('facProveedor').value = '';
        document.getElementById('facNum').value = '';
        document.getElementById('facTotal').innerText = '0,00';
        document.getElementById('listaAlbaranesPendientes').innerHTML = '<div class="p-20 text-center opacity-50 fs-13">Selecciona un proveedor</div>';
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
            albaranesPendientes = data.filter(a => a.proveedor_id == provId);
            const container = document.getElementById('listaAlbaranesPendientes');
            if (albaranesPendientes.length === 0) container.innerHTML = '<div class="p-20 text-center opacity-50 fs-13">No hay albaranes pendientes para este proveedor</div>';
            else {
                container.innerHTML = albaranesPendientes.map(a => `
                    <div class="alb-item" id="alb-row-${a.id}" onclick="toggleSeleccionAlbaran(${a.id}, ${a.total})">
                        <div>
                            <div class="fw-700">${a.numero_albaran}</div>
                            <div class="fs-11 text-muted">${new Date(a.fecha).toLocaleDateString()}</div>
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
        document.getElementById('facTotal').innerText = total.toLocaleString('es-ES', {
            minimumFractionDigits: 2
        });
        document.getElementById('btnGuardarFactura').disabled = (albaranesSeleccionados.length === 0);
    }

    async function guardarFactura() {
        const num = document.getElementById('facNum').value;
        if (!num) return alert('Nº Factura obligatorio');
        const btn = document.getElementById('btnGuardarFactura');
        btn.disabled = true;
        try {
            const r = await fetch('api/compras.php', {
                method: 'POST',
                body: JSON.stringify({
                    action: 'registrar_factura',
                    proveedor_id: document.getElementById('facProveedor').value,
                    numero_factura: num,
                    fecha: document.getElementById('facFecha').value,
                    metodo_pago: document.getElementById('facPago').value,
                    ids_albaranes: albaranesSeleccionados
                })
            });
            const res = await r.json();
            if (res.success) window.location.reload();
            else alert('Error: ' + res.error);
        } catch (e) {
            alert('Error de conexión');
        } finally {
            btn.disabled = false;
        }
    }

    // --- DETALLES ---
    async function verDetalleAlbaran(id) {
        const r = await fetch('api/compras.php?type=albaran&id=' + id);
        const a = await r.json();
        document.getElementById('detalleTitulo').innerText = 'Albarán ' + a.numero_albaran;
        document.getElementById('detalleContent').innerHTML = `
            <div class="mb-16"><b>Proveedor:</b> ${a.proveedor_nombre} <br> <b>Fecha:</b> ${new Date(a.fecha).toLocaleDateString()}</div>
            <table class="data-table">
                <thead><tr><th>Producto</th><th class="text-right">Cant.</th><th class="text-right">Total</th></tr></thead>
                <tbody>${a.lineas.map(l => `<tr><td>${l.producto_nombre}</td><td class="text-right">${l.cantidad}</td><td class="text-right font-mono">${(l.cantidad * l.precio_coste_neto * (1+(l.iva_pct/100)+(l.re_pct/100))).toFixed(2)} €</td></tr>`).join('')}</tbody>
            </table>
            <div class="text-right mt-16 fs-20 font-bold text-accent">Total: ${parseFloat(a.total).toFixed(2)} €</div>
        `;
        document.getElementById('modalDetalle').style.display = 'flex';
    }

    async function verDetalleFactura(id) {
        const r = await fetch('api/compras.php?type=factura&id=' + id);
        const f = await r.json();
        document.getElementById('detalleTitulo').innerText = 'Factura ' + f.numero_factura;
        document.getElementById('detalleContent').innerHTML = `
            <div class="mb-16">
                <b>Proveedor:</b> ${f.proveedor_nombre} <br> 
                <b>Fecha:</b> ${new Date(f.fecha_factura).toLocaleDateString()} <br>
                <b>Pago:</b> <span class="badge badge-info">${f.metodo_pago}</span>
            </div>
            <div class="fw-700 mb-8 tt-uppercase fs-11 opacity-50">Albaranes incluidos:</div>
            ${f.albaranes.map(a => `
                <div class="p-12 br-8 border-2 mb-4 d-flex jc-space-between ai-center">
                    <span>${a.numero_albaran} (${new Date(a.fecha).toLocaleDateString()})</span>
                    <span class="font-mono">${parseFloat(a.total).toFixed(2)} €</span>
                </div>
            `).join('')}
            <div class="text-right mt-16 fs-24 font-bold text-accent">Total Factura: ${parseFloat(f.total).toFixed(2)} €</div>
        `;
        document.getElementById('modalDetalle').style.display = 'flex';
    }

    function cerrarModalDetalle() {
        document.getElementById('modalDetalle').style.display = 'none';
    }
</script>