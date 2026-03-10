<div class="main-full p-24">

    <!-- CABECERA DE SECCIÓN -->
    <div class="section-header container-wider">
        <div class="section-title">
            <h1>Mantenimiento de Proveedores</h1>
            <p>Gestiona tu listín de proveedores, datos de contacto y régimen fiscal (RE).</p>
        </div>
        <div class="d-flex gap-12 ai-center">
            <a href="index.php?irDashboard=1" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>
            <button onclick="abrirModalProveedor()" class="btn-add">
                <i class="fa-solid fa-truck-field"></i> Nuevo Proveedor
            </button>
        </div>
    </div>

    <!-- TABLA DE PROVEEDORES -->
    <div class="table-container container-wider">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-60 pl-20">ID</th>
                    <th>Proveedor / CIF</th>
                    <th>Contacto</th>
                    <th>Régimen</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center pr-20">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($avInicioPrivado['proveedores'])): ?>
                    <tr>
                        <td colspan="6" class="p-40 text-center">
                            <div class="empty-state">
                                <i class="fa-solid fa-truck-ramp-box opacity-20 fs-48 mb-16"></i>
                                <p class="text-muted">No hay proveedores registrados.</p>
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
                            <td>
                                <?php if ($p['aplica_re']): ?>
                                    <span class="px-8 py-2 br-4 fs-11 bg-blue-light text-blue border border-blue">
                                        <i class="fa-solid fa-percent fs-10"></i> Recargo Eq.
                                    </span>
                                <?php else: ?>
                                    <span class="px-8 py-2 br-4 fs-11 bg-surface2 text-muted border border-border">
                                        Régimen General
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($p['activo']): ?>
                                    <span class="status-pill status-active">
                                        <i class="fa-solid fa-circle-check"></i> Activo
                                    </span>
                                <?php else: ?>
                                    <span class="status-pill status-inactive">
                                        <i class="fa-solid fa-circle-xmark"></i> Inactivo
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex jc-center gap-8 pr-20">
                                    <button class="btn-icon" onclick='editarProveedor(<?php echo json_encode($p); ?>)' title="Editar">
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
</div>

<!-- MODAL PROVEEDOR (Integrado con estilos de la app) -->
<div id="modalProveedor" class="modal-overlay-bg">
    <div class="modal-content w-modal-md">
        <div class="modal-header">
            <h2 id="modalProveedorTitle">Nuevo Proveedor</h2>
            <button class="btn-close-modal" onclick="cerrarModalProveedor()">&times;</button>
        </div>
        <form id="formProveedor" onsubmit="guardarProveedor(event)">
            <input type="hidden" id="provId">

            <div class="form-group mb-16">
                <label class="form-label">Nombre Comercial *</label>
                <input type="text" id="provNombre" class="form-input" required placeholder="Ej: Distribuciones Tecnológicas SL">
            </div>

            <div class="d-grid grid-2 gap-16 mb-16">
                <div class="form-group mb-0">
                    <label class="form-label">CIF / NIF *</label>
                    <input type="text" id="provCif" class="form-input font-mono" required placeholder="B12345678">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Teléfono</label>
                    <input type="text" id="provTel" class="form-input" placeholder="+34 ...">
                </div>
            </div>

            <div class="form-group mb-16">
                <label class="form-label">Email de pedidos</label>
                <input type="email" id="provEmail" class="form-input" placeholder="comercial@proveedor.com">
            </div>

            <div class="form-group mb-16">
                <label class="form-label">Dirección Fiscal / Notas</label>
                <input type="text" id="provDireccion" class="form-input" placeholder="Calle, número, CP, Ciudad...">
            </div>

            <!-- Recargo de Equivalencia Toggle -->
            <div class="p-16 br-12 mb-16 border-2 d-flex ai-center gap-12 bg-blue-light border-blue">
                <div class="d-flex ai-center jc-center w-40 h-40 br-8 bg-blue text-white fs-20">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <div class="flex-1">
                    <label class="form-label mb-4 d-flex ai-center gap-8 cursor-pointer">
                        <input type="checkbox" id="provRE" style="width: 18px; height: 18px;">
                        <span class="font-bold fs-14">Aplica Recargo de Equivalencia</span>
                    </label>
                    <p class="fs-11 text-muted m-0">El coste de los productos incluirá IVA y RE automáticamente.</p>
                </div>
            </div>

            <div class="form-group mb-16 d-flex ai-center gap-8" id="divProvActivo" style="display:none;">
                <input type="checkbox" id="provActivo" checked style="width: 18px; height: 18px;">
                <label class="form-label mb-0 fs-14">Proveedor Activo</label>
            </div>

            <div class="modal-footer pt-16 border-top">
                <button type="button" class="btn-cancel" onclick="cerrarModalProveedor()">Cancelar</button>
                <div class="flex-1"></div>
                <button type="submit" class="btn-save w-auto px-32">Guardar Proveedor</button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModalProveedor() {
        document.getElementById('formProveedor').reset();
        document.getElementById('provId').value = '';
        document.getElementById('modalProveedorTitle').innerText = 'Nuevo Proveedor';
        document.getElementById('divProvActivo').style.display = 'none';
        document.getElementById('modalProveedor').style.display = 'flex';
    }

    function cerrarModalProveedor() {
        document.getElementById('modalProveedor').style.display = 'none';
    }

    function editarProveedor(p) {
        document.getElementById('provId').value = p.id;
        document.getElementById('provNombre').value = p.nombre;
        document.getElementById('provCif').value = p.cif_nif;
        document.getElementById('provTel').value = p.telefono;
        document.getElementById('provEmail').value = p.email;
        document.getElementById('provDireccion').value = p.direccion;
        document.getElementById('provRE').checked = p.aplica_re == 1;
        document.getElementById('provActivo').checked = p.activo == 1;
        document.getElementById('divProvActivo').style.display = 'flex';
        document.getElementById('modalProveedorTitle').innerText = 'Editar Proveedor';
        document.getElementById('modalProveedor').style.display = 'flex';
    }

    async function guardarProveedor(e) {
        e.preventDefault();
        const id = document.getElementById('provId').value;
        const data = {
            id: id || undefined,
            nombre: document.getElementById('provNombre').value,
            cif_nif: document.getElementById('provCif').value,
            telefono: document.getElementById('provTel').value,
            email: document.getElementById('provEmail').value,
            direccion: document.getElementById('provDireccion').value,
            aplica_re: document.getElementById('provRE').checked ? 1 : 0,
            activo: document.getElementById('provActivo').checked ? 1 : 0
        };

        try {
            const res = await fetch('api/proveedores.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) {
                window.location.reload();
            } else {
                alert('Error al guardar: ' + (result.error || 'Revisa los datos'));
            }
        } catch (err) {
            console.error(err);
            alert('Error de conexión');
        }
    }
</script>