<?php // Vista de gestión de clientes y socios ?>
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
            <form method="post">
                <button type="submit" name="volver" class="btn-back">
                    <i class="fa-solid fa-arrow-left"></i> Volver
                </button>
            </form>
        </div>
    </div>

    <div class="table-container container-wider">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="pl-20">Nombre</th>
                    <th>Tipo</th>
                    <th>NIF</th>
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
                        <?php if (!empty($c['es_socio'])): ?>
                            <span class="status-pill status-active">
                                <i class="fa-solid fa-id-card"></i> Socio
                            </span>
                        <?php else: ?>
                            <span class="status-pill text-muted fs-11">No</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center font-mono fs-12">
                        <?php echo date('d/m/Y', strtotime($c['fecha_alta'])); ?>
                    </td>
                    <td class="text-center">
                        <div class="d-flex jc-center gap-8 pr-20">
                            <button class="btn-icon" title="Editar" onclick='editarCliente(<?php echo json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                <i class="fa-solid fa-pen"></i>
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
    <div class="modal modal-content gap-16 ai-stretch w-400">
        <div class="modal-header mb-0">
            <h2 class="m-0 fs-18" id="clienteModalTitle">Nuevo Cliente</h2>
            <button onclick="cerrarModalCliente()" class="btn-close-modal">&times;</button>
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
                <label class="d-flex ai-center gap-8 cursor-pointer fs-13">
                    <input type="checkbox" id="clienteEsSocio" class="w-16 h-16" />
                    <span>Marcar como socio</span>
                </label>
            </div>
        </form>
        <div class="modal-footer full-width">
            <button onclick="cerrarModalCliente()" class="btn-cancel">Cancelar</button>
            <button onclick="guardarCliente()" class="btn-save">Guardar</button>
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
        es_socio: document.getElementById('clienteEsSocio').checked ? 1 : 0,
    };

    try {
        const resp = await fetch('api/gestionCliente.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
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
    document.getElementById('clienteEsSocio').checked = false;
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
    document.getElementById('clienteEsSocio').checked = !!c.es_socio;
    document.getElementById('clienteModalTitle').innerText = 'Editar Cliente';
    document.getElementById('clienteAdminModal').classList.add('visible');
}

function cerrarModalCliente() {
    document.getElementById('clienteAdminModal').classList.remove('visible');
}
</script>

