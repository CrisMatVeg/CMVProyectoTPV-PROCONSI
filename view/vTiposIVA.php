<?php // Vista de Tipos de IVA ?>
<div class="main-full p-24">
    <div class="section-header container-wider">
        <div class="section-title">
            <h1>Gestión de Tipos de IVA</h1>
            <p>Configura los tipos de IVA y sus vigencias por fecha.</p>
        </div>
        <div class="d-flex gap-12">
            <button onclick="abrirModalIva()" class="btn-add">
                <i class="fa-solid fa-plus"></i> Nuevo Tipo de IVA
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
                    <th class="pl-20">Código</th>
                    <th>Nombre</th>
                    <th class="text-right">Porcentaje</th>
                    <th class="text-center">Vigencia</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center pr-20">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($avTiposIva['lista'] as $t): ?>
                <tr data-id="<?php echo $t['id']; ?>">
                    <td class="pl-20 font-mono"><?php echo htmlspecialchars($t['codigo']); ?></td>
                    <td><?php echo htmlspecialchars($t['nombre']); ?></td>
                    <td class="text-right font-mono font-bold">
                        <?php echo number_format($t['porcentaje'], 2, ',', '.'); ?> %
                    </td>
                    <td class="text-center fs-12">
                        <?php
                            $fi = $t['fecha_inicio'] ? date('d/m/Y', strtotime($t['fecha_inicio'])) : '—';
                            $ff = $t['fecha_fin'] ? date('d/m/Y', strtotime($t['fecha_fin'])) : 'Indefinido';
                        ?>
                        <?php echo $fi . ' → ' . $ff; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($t['activo']): ?>
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
                            <button onclick='abrirModalIva(<?php echo json_encode($t); ?>)' title="Editar" class="btn-icon">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button onclick="eliminarIva(<?php echo $t['id']; ?>)" title="Eliminar" class="btn-icon btn-icon-danger">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($avTiposIva['lista'])): ?>
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i class="fa-solid fa-percent"></i>
                            Aún no hay tipos de IVA configurados.
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL NUEVO/EDITAR IVA -->
<div class="modal-overlay" id="ivaModal">
    <div class="modal modal-content gap-16 ai-stretch w-400">
        <div class="modal-header mb-0">
            <h2 id="ivaModalTitle" class="m-0 fs-18">Nuevo Tipo de IVA</h2>
            <button onclick="cerrarModalIva()" class="btn-close-modal">&times;</button>
        </div>
        <form id="ivaForm" class="modal-body p-20">
            <input type="hidden" id="ivaId">

            <div class="form-group">
                <label class="form-label">Código</label>
                <input type="text" id="ivaCodigo" class="form-input font-mono" placeholder="GENERAL, REDUCIDO...">
                <span class="form-error" id="err-codigo"></span>
            </div>

            <div class="form-group">
                <label class="form-label">Nombre</label>
                <input type="text" id="ivaNombre" class="form-input" placeholder="IVA general, IVA reducido...">
                <span class="form-error" id="err-nombre"></span>
            </div>

            <div class="d-grid grid-3 gap-16">
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase">Porcentaje (%)</label>
                    <input type="number" id="ivaPorcentaje" class="form-input text-right" step="0.01" min="0">
                    <span class="form-error" id="err-porcentaje"></span>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase">F. Inicio</label>
                    <input type="date" id="ivaFechaInicio" class="form-input">
                    <span class="form-error" id="err-fecha_inicio"></span>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase">F. Fin</label>
                    <input type="date" id="ivaFechaFin" class="form-input">
                </div>
            </div>

            <div class="mt-12">
                <label class="d-flex ai-center gap-8 cursor-pointer fs-13">
                    <input type="checkbox" id="ivaActivo" checked>
                    <span>Activo</span>
                </label>
            </div>
        </form>
        <div class="modal-footer full-width">
            <button onclick="cerrarModalIva()" class="btn-cancel">Cancelar</button>
            <button onclick="guardarIva()" class="btn-save">Guardar tipo de IVA</button>
        </div>
    </div>
</div>

<script>
    function limpiarErroresIva() {
        document.querySelectorAll('#ivaModal .form-error').forEach(el => el.innerText = '');
    }

    function abrirModalIva(iva = null) {
        limpiarErroresIva();
        const modal = document.getElementById('ivaModal');
        const title = document.getElementById('ivaModalTitle');

        if (iva) {
            title.innerText = 'Editar Tipo de IVA';
            document.getElementById('ivaId').value = iva.id;
            document.getElementById('ivaCodigo').value = iva.codigo;
            document.getElementById('ivaNombre').value = iva.nombre;
            document.getElementById('ivaPorcentaje').value = iva.porcentaje;
            document.getElementById('ivaFechaInicio').value = iva.fecha_inicio;
            document.getElementById('ivaFechaFin').value = iva.fecha_fin || '';
            document.getElementById('ivaActivo').checked = !!parseInt(iva.activo);
        } else {
            title.innerText = 'Nuevo Tipo de IVA';
            document.getElementById('ivaId').value = '';
            document.getElementById('ivaCodigo').value = '';
            document.getElementById('ivaNombre').value = '';
            document.getElementById('ivaPorcentaje').value = '';
            document.getElementById('ivaFechaInicio').value = '';
            document.getElementById('ivaFechaFin').value = '';
            document.getElementById('ivaActivo').checked = true;
        }

        modal.classList.add('visible');
    }

    function cerrarModalIva() {
        document.getElementById('ivaModal').classList.remove('visible');
        limpiarErroresIva();
    }

    async function guardarIva() {
        limpiarErroresIva();
        const id = document.getElementById('ivaId').value;
        const payload = {
            accion: id ? 'editar' : 'añadir',
            id: id || null,
            codigo: document.getElementById('ivaCodigo').value.trim(),
            nombre: document.getElementById('ivaNombre').value.trim(),
            porcentaje: document.getElementById('ivaPorcentaje').value,
            fecha_inicio: document.getElementById('ivaFechaInicio').value,
            fecha_fin: document.getElementById('ivaFechaFin').value,
            activo: document.getElementById('ivaActivo').checked ? 1 : 0,
        };

        try {
            const resp = await fetch('api/gestionTipoIva.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const r = await resp.json();
            if (r.ok) {
                location.reload();
            } else if (r.aErrores) {
                for (const [field, msg] of Object.entries(r.aErrores)) {
                    const el = document.getElementById('err-' + field);
                    if (el && msg) el.innerText = msg;
                }
            } else {
                alert('Error: ' + (r.error || 'No se pudo guardar el tipo de IVA'));
            }
        } catch (e) {
            console.error(e);
            alert('Error de conexión con el servidor');
        }
    }

    async function eliminarIva(id) {
        if (!id || !confirm('¿Seguro que deseas eliminar este tipo de IVA?')) return;
        try {
            const resp = await fetch('api/gestionTipoIva.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ accion: 'eliminar', id }),
            });
            const r = await resp.json();
            if (r.ok) {
                location.reload();
            } else {
                alert('Error: ' + (r.error || 'No se pudo eliminar el tipo de IVA'));
            }
        } catch (e) {
            console.error(e);
        }
    }
</script>

