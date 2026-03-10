<?php // Vista de gestión de clientes y socios 
?>
<div class="main-full p-24">
    <div class="section-header container-wider">
        <div class="section-title">
            <h1>Clientes y Socios</h1>
            <p>Gestión de clientes habituales y socios con descuentos.</p>
        </div>
        <div class="d-flex gap-12">
            <button onclick="abrirModalCliente()" class="btn-add">
                <i class="fa-solid fa-user-plus"></i> Nuevo Cliente
            </button>
            <a href="index.php?irDashboard=1" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="table-container container-wider">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="pl-20">Nombre</th>
                    <th>Tipo</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th class="text-center">Socio</th>
                    <th class="text-center">Alta</th>
                    <th class="text-center pr-20">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($avClientes['lista'] as $c): ?>
                    <tr>
                        <td class="pl-20">
                            <?php echo htmlspecialchars(trim($c['nombre'] . ' ' . ($c['apellidos'] ?? ''))); ?>
                        </td>
                        <td>
                            <span class="status-pill">
                                <i class="fa-solid <?php echo $c['tipo'] === 'empresa' ? 'fa-building' : 'fa-user'; ?>"></i>
                                <?php echo ucfirst($c['tipo']); ?>
                            </span>
                        </td>
                        <td class="font-mono"><?php echo htmlspecialchars($c['nif'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($c['email'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($c['telefono'] ?? ''); ?></td>
                        <td class="text-center">
                            <div class="d-flex flex-column gap-4 ai-center">
                                <?php if ($c['rol'] === 'socio'): ?>
                                    <span class="status-pill status-active" style="padding: 2px 8px; font-size: 10px;">
                                        <i class="fa-solid fa-id-card"></i> SOCIO
                                    </span>
                                <?php elseif ($c['rol'] === 'mayorista'): ?>
                                    <span class="status-pill status-paid" style="padding: 2px 8px; font-size: 10px; background: rgba(156, 39, 176, 0.1); color: #9c27b0; border-color: rgba(156, 39, 176, 0.2);">
                                        <i class="fa-solid fa-truck-fast"></i> MAYORISTA
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted fs-11" style="text-transform: uppercase;"><?php echo htmlspecialchars($c['rol'] ?? 'GENERAL'); ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-center font-mono fs-12">
                            <?php echo date('d/m/Y', strtotime($c['fecha_alta'])); ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex jc-center gap-8 pr-20">
                                <button class="btn-icon text-primary" title="Historial/Deudas" onclick="abrirHistorialCliente(<?php echo $c['id']; ?>)">
                                    <i class="fa-solid fa-file-invoice-dollar"></i>
                                </button>
                                <button class="btn-icon" title="Editar" onclick='editarCliente(<?php echo json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button class="btn-icon text-danger" title="Eliminar" onclick="eliminarCliente(<?php echo $c['id']; ?>)">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($avClientes['lista'])): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="fa-solid fa-user-group"></i>
                                Aún no hay clientes registrados.
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL ALTA/EDICIÓN CLIENTE -->
<div class="modal-overlay" id="clienteAdminModal">
    <div class="modal modal-content gap-16 ai-stretch w-modal-md">
        <div class="modal-header mb-0">
            <h2 class="m-0 fs-18" id="clienteModalTitle">Nuevo Cliente</h2>
            <button onclick="cerrarAdminModalCliente()" class="btn-close-modal">&times;</button>
        </div>
        <form id="clienteForm" class="modal-body p-20">
            <input type="hidden" id="clienteId">
            <div class="form-group mb-0">
                <label class="form-label">Tipo</label>
                <select id="clienteTipo" class="form-input">
                    <option value="particular">Particular</option>
                    <option value="empresa">Empresa</option>
                </select>
            </div>
            <div class="form-group mb-0 mt-10">
                <label class="form-label">Nombre</label>
                <input type="text" id="clienteNombre" class="form-input" required>
            </div>
            <div class="form-group mb-0 mt-10">
                <label class="form-label">Apellidos</label>
                <input type="text" id="clienteApellidos" class="form-input">
            </div>
            <div class="form-group mb-0 mt-10">
                <label class="form-label">NIF / DNI</label>
                <input type="text" id="clienteNif" class="form-input font-mono">
            </div>
            <div class="form-group mb-0 mt-10">
                <label class="form-label">Email</label>
                <input type="email" id="clienteEmail" class="form-input">
            </div>
            <div class="form-group mb-0 mt-10">
                <label class="form-label">Teléfono</label>
                <input type="text" id="clienteTelefono" class="form-input">
            </div>
            <div class="form-group mb-0 mt-10">
                <label class="form-label">Notas</label>
                <textarea id="clienteNotas" class="form-input" rows="2"></textarea>
            </div>
            <div class="form-group mb-0 mt-10">
                <label class="form-label" style="display:flex; justify-content:space-between;">
                    <span>Rol del Cliente</span>
                    <button type="button" class="btn-text fs-12 text-blue p-0" style="background:none; border:none; cursor:pointer;" onclick="crearNuevoRol()">+ Nuevo Rol</button>
                </label>
                <select id="clienteRol" class="form-input">
                    <?php if (!empty($avClientes['roles'])): ?>
                        <?php foreach ($avClientes['roles'] as $r): ?>
                            <option value="<?php echo htmlspecialchars(strtolower($r['nombre'])); ?>">
                                <?php echo htmlspecialchars(ucfirst($r['nombre'])); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="general">General</option>
                    <?php endif; ?>
                </select>
            </div>
        </form>
        <div class="modal-footer full-width">
            <button onclick="cerrarAdminModalCliente()" class="btn-cancel">Cancelar</button>
            <button onclick="guardarCliente()" class="btn-save">Guardar</button>
        </div>
    </div>
</div>

<!-- MODAL HISTORIAL CLIENTE -->
<div class="modal-overlay" id="historialClienteModal">
    <div class="modal modal-content gap-16 ai-stretch w-600">
        <div class="modal-header mb-0">
            <h2 class="m-0 fs-18">Historial y Deudas del Cliente</h2>
            <button onclick="cerrarAdminHistorialModal()" class="btn-close-modal">&times;</button>
        </div>
        <div class="modal-body p-20" style="max-height: 400px; overflow-y: auto;">
            <table class="data-table">
                <thead class="bg-surface2">
                    <tr>
                        <th>Fecha</th>
                        <th>Ticket</th>
                        <th>Estado</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Pagado</th>
                        <th class="text-right">Pendiente</th>
                    </tr>
                </thead>
                <tbody id="historialBody">
                    <tr>
                        <td colspan="6" class="text-center text-muted">Cargando...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="modal-footer full-width jc-end">
            <button onclick="cerrarAdminHistorialModal()" class="btn-cancel">Cerrar</button>
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
                alert('Error: ' + (data.error || 'No se pudo guardar el cliente'));
                return;
            }
            location.reload();
        } catch (e) {
            console.error(e);
            alert('Error de conexión con el servidor');
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
        document.getElementById('clienteModalTitle').innerText = 'Nuevo Cliente';
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
        document.getElementById('clienteModalTitle').innerText = 'Editar Cliente';

        document.getElementById('clienteAdminModal').classList.add('visible');
    }

    function cerrarAdminModalCliente() {
        document.getElementById('clienteAdminModal').classList.remove('visible');
    }

    async function eliminarCliente(id) {
        if (!confirm('¿Estás seguro de que deseas dar de baja a este cliente? Esta acción no eliminará las ventas pero ocultará al cliente de los listados.')) return;

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
                alert('Error: ' + (data.error || 'No se pudo eliminar el cliente'));
                return;
            }
            location.reload();
        } catch (e) {
            console.error(e);
            alert('Error de conexión con el servidor');
        }
    }

    async function abrirHistorialCliente(id) {
        document.getElementById('historialClienteModal').classList.add('visible');
        const tbody = document.getElementById('historialBody');
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Cargando...</td></tr>';

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
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Error: ${data.error}</td></tr>`;
                return;
            }

            if (data.ventas.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No hay ventas registradas para este cliente.</td></tr>';
                return;
            }

            let html = '';
            for (const v of data.ventas) {
                const total = parseFloat(v.total) || 0;
                const pagado = parseFloat(v.pagado_a_cuenta) || 0;
                let pendiente = 0;

                if (v.estado === 'pendiente_pago') {
                    pendiente = total - pagado;
                } else if (v.estado === 'completada') {
                    pendiente = 0;
                }

                const fechaStr = new Date(v.fecha).toLocaleString('es-ES', {
                    dateStyle: 'short',
                    timeStyle: 'short'
                });

                let badgeClass = 'status-active'; // green
                if (v.estado === 'pendiente_pago') badgeClass = 'status-pending'; // orange
                else if (v.estado === 'devuelta' || v.estado === 'anulada') badgeClass = 'status-cancelled'; // red

                html += `
                    <tr>
                        <td>${fechaStr}</td>
                        <td class="font-mono">#${v.numero_ticket}</td>
                        <td><span class="status-pill ${badgeClass}" style="padding: 2px 8px; font-size: 10px; text-transform: uppercase;">${v.estado.replace('_', ' ')}</span></td>
                        <td class="text-right font-mono">${total.toFixed(2)}€</td>
                        <td class="text-right font-mono">${v.estado === 'pendiente_pago' ? pagado.toFixed(2) + '€' : '-'}</td>
                        <td class="text-right font-mono fw-bold ${pendiente > 0 ? 'text-danger' : 'text-success'}">${pendiente.toFixed(2)}€</td>
                    </tr>
                `;
            }
            tbody.innerHTML = html;
        } catch (e) {
            console.error(e);
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error de conexión</td></tr>';
        }
    }

    function cerrarAdminHistorialModal() {
        document.getElementById('historialClienteModal').classList.remove('visible');
    }

    async function crearNuevoRol() {
        const nombre = prompt('Introduce el nombre para el nuevo rol de cliente:');
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
                alert('Error: ' + (data.error || 'No se pudo crear el rol'));
                return;
            }

            // Añadir al select
            const sel = document.getElementById('clienteRol');
            const opt = document.createElement('option');
            opt.value = data.nombre;
            opt.text = data.nombre.charAt(0).toUpperCase() + data.nombre.slice(1);
            sel.appendChild(opt);
            sel.value = data.nombre; // dejarlo seleccionado

            // Recargar para que aparezca en el PHP
            location.reload();
        } catch (e) {
            console.error(e);
            alert('Error al conectar con el servidor.');
        }
    }
</script>