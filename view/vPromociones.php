<?php // Vista de Promociones ?>
<div class="main-full p-24">
    <div class="section-header container-wider">
        <div class="section-title">
            <h1>Gestión de Descuentos y Promociones</h1>
            <p>Configura los cupones y reglas de descuento disponibles en el TPV.</p>
        </div>
        <div class="d-flex gap-12">
            <button onclick="abrirModalPromo()" class="btn-add">
                <i class="fa-solid fa-plus"></i> Nueva Promoción
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
                    <th>Descripción</th>
                    <th class="text-center">Tipo</th>
                    <th class="text-right">Valor</th>
                    <th class="text-right">Mínimo</th>
                    <th class="text-center">Solo socios</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center pr-20">Acciones</th>
                </tr>
            </thead>
            <tbody id="promosTableBody">
                <?php foreach ($avPromos['lista'] as $p): ?>
                <tr data-id="<?php echo $p['id']; ?>">
                    <td class="pl-20 font-mono"><?php echo htmlspecialchars($p['codigo']); ?></td>
                    <td><?php echo htmlspecialchars($p['descripcion']); ?></td>
                    <td class="text-center">
                        <span class="status-pill <?php echo $p['tipo'] === 'percent' ? 'status-card' : 'status-cash'; ?>">
                            <?php echo $p['tipo'] === 'percent' ? '% sobre total' : 'Importe fijo'; ?>
                        </span>
                    </td>
                    <td class="text-right font-mono font-bold">
                        <?php echo $p['tipo'] === 'percent'
                            ? number_format($p['valor'], 2, ',', '.') . ' %'
                            : number_format($p['valor'], 2, ',', '.') . ' €'; ?>
                    </td>
                    <td class="text-right font-mono">
                        <?php echo number_format($p['min_subtotal'], 2, ',', '.'); ?> €
                    </td>
                    <td class="text-center">
                        <?php if ($p['solo_socios']): ?>
                            <span class="status-pill status-card"><i class="fa-solid fa-id-card"></i> Sí</span>
                        <?php else: ?>
                            <span class="status-pill text-muted">No</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($p['activo']): ?>
                            <span class="status-pill status-active">
                                <i class="fa-solid fa-circle-check"></i> Activa
                            </span>
                        <?php else: ?>
                            <span class="status-pill status-inactive">
                                <i class="fa-solid fa-circle-xmark"></i> Inactiva
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="d-flex jc-center gap-8 pr-20">
                            <button onclick='abrirModalPromo(<?php echo json_encode($p); ?>)' title="Editar" class="btn-icon">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button onclick="togglePromo(<?php echo $p['id']; ?>)" title="<?php echo $p['activo'] ? 'Desactivar' : 'Activar'; ?>" class="btn-icon <?php echo $p['activo'] ? 'text-red' : 'text-green'; ?>">
                                <i class="fa-solid fa-<?php echo $p['activo'] ? 'pause' : 'play'; ?>"></i>
                            </button>
                            <button onclick="eliminarPromo(<?php echo $p['id']; ?>)" title="Eliminar" class="btn-icon btn-icon-danger">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($avPromos['lista'])): ?>
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="fa-solid fa-ticket"></i>
                            Aún no hay promociones configuradas.
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
    <div class="modal modal-content gap-16 ai-stretch w-400">
        <div class="modal-header mb-0">
            <h2 id="promoModalTitle" class="m-0 fs-18">Nueva promoción</h2>
            <button onclick="cerrarModalPromo()" class="btn-close-modal">&times;</button>
        </div>
        <form id="promoForm" class="modal-body p-20">
            <input type="hidden" id="promoId">

            <div class="form-group">
                <label class="form-label">Código</label>
                <input type="text" id="promoCodigo" class="form-input font-mono" placeholder="EJ: DESC10">
                <span class="form-error" id="err-codigo"></span>
            </div>

            <div class="form-group">
                <label class="form-label">Descripción</label>
                <input type="text" id="promoDescripcion" class="form-input" placeholder="Texto que verás en el TPV">
                <span class="form-error" id="err-descripcion"></span>
            </div>

            <div class="d-grid grid-3 gap-16">
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase">Tipo</label>
                    <select id="promoTipo" class="form-input">
                        <option value="percent">% sobre total</option>
                        <option value="amount">Importe fijo (€)</option>
                    </select>
                    <span class="form-error" id="err-tipo"></span>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase">Valor</label>
                    <input type="number" id="promoValor" class="form-input text-right" step="0.01" min="0">
                    <span class="form-error" id="err-valor"></span>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase">Mín. compra (€)</label>
                    <input type="number" id="promoMin" class="form-input text-right" step="0.01" min="0">
                    <span class="form-error" id="err-min_subtotal"></span>
                </div>
            </div>

            <div class="d-grid grid-2 gap-16 mt-12">
                <label class="d-flex ai-center gap-8 cursor-pointer fs-13">
                    <input type="checkbox" id="promoSoloSocios">
                    <span>Solo para socios</span>
                </label>
                <label class="d-flex ai-center gap-8 cursor-pointer fs-13">
                    <input type="checkbox" id="promoActiva" checked>
                    <span>Activa</span>
                </label>
            </div>
        </form>
        <div class="modal-footer full-width">
            <button onclick="cerrarModalPromo()" class="btn-cancel">Cancelar</button>
            <button onclick="guardarPromo()" class="btn-save">Guardar promoción</button>
        </div>
    </div>
</div>

<script>
    function limpiarErroresPromo() {
        document.querySelectorAll('#promoModal .form-error').forEach(el => el.innerText = '');
    }

    function abrirModalPromo(promo = null) {
        limpiarErroresPromo();
        const modal = document.getElementById('promoModal');
        const title = document.getElementById('promoModalTitle');

        if (promo) {
            title.innerText = 'Editar promoción';
            document.getElementById('promoId').value = promo.id;
            document.getElementById('promoCodigo').value = promo.codigo;
            document.getElementById('promoDescripcion').value = promo.descripcion;
            document.getElementById('promoTipo').value = promo.tipo;
            document.getElementById('promoValor').value = promo.valor;
            document.getElementById('promoMin').value = promo.min_subtotal;
            document.getElementById('promoSoloSocios').checked = !!parseInt(promo.solo_socios);
            document.getElementById('promoActiva').checked = !!parseInt(promo.activo);
        } else {
            title.innerText = 'Nueva promoción';
            document.getElementById('promoId').value = '';
            document.getElementById('promoCodigo').value = '';
            document.getElementById('promoDescripcion').value = '';
            document.getElementById('promoTipo').value = 'percent';
            document.getElementById('promoValor').value = '';
            document.getElementById('promoMin').value = '';
            document.getElementById('promoSoloSocios').checked = false;
            document.getElementById('promoActiva').checked = true;
        }

        modal.classList.add('visible');
    }

    function cerrarModalPromo() {
        document.getElementById('promoModal').classList.remove('visible');
        limpiarErroresPromo();
    }

    async function guardarPromo() {
        limpiarErroresPromo();
        const id = document.getElementById('promoId').value;
        const payload = {
            accion: id ? 'editar' : 'añadir',
            id: id || null,
            codigo: document.getElementById('promoCodigo').value.trim(),
            descripcion: document.getElementById('promoDescripcion').value.trim(),
            tipo: document.getElementById('promoTipo').value,
            valor: document.getElementById('promoValor').value,
            min_subtotal: document.getElementById('promoMin').value,
            solo_socios: document.getElementById('promoSoloSocios').checked ? 1 : 0,
            activo: document.getElementById('promoActiva').checked ? 1 : 0,
        };

        try {
            const resp = await fetch('api/gestionPromocion.php', {
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
                alert('Error: ' + (r.error || 'No se pudo guardar la promoción'));
            }
        } catch (e) {
            console.error(e);
            alert('Error de conexión con el servidor');
        }
    }

    async function togglePromo(id) {
        if (!id) return;
        try {
            const resp = await fetch('api/gestionPromocion.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ accion: 'toggle', id }),
            });
            const r = await resp.json();
            if (r.ok) {
                location.reload();
            } else {
                alert('Error: ' + (r.error || 'No se pudo cambiar el estado'));
            }
        } catch (e) {
            console.error(e);
        }
    }

    async function eliminarPromo(id) {
        if (!id || !confirm('¿Seguro que deseas eliminar esta promoción?')) return;
        try {
            const resp = await fetch('api/gestionPromocion.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ accion: 'eliminar', id }),
            });
            const r = await resp.json();
            if (r.ok) {
                location.reload();
            } else {
                alert('Error: ' + (r.error || 'No se pudo eliminar la promoción'));
            }
        } catch (e) {
            console.error(e);
        }
    }
</script>

