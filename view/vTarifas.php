<?php // Vista de Tarifas de Precios ?>
<div class="main-full p-24">
    <div class="section-header container-wider">
        <div class="section-title">
            <h1>Tarifas de Precios</h1>
            <p>Registra subidas o bajadas generales de precios por fecha.</p>
        </div>
        <div class="d-flex gap-12">
            <button onclick="abrirModalTarifa()" class="btn-add">
                <i class="fa-solid fa-plus"></i> Nueva Tarifa
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
                    <th class="text-right">Valor</th>
                    <th class="text-center">Fecha aplicación</th>
                    <th class="text-center">Ámbito</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center pr-20">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($avTarifas['lista'] as $t): ?>
                <tr>
                    <td class="pl-20"><?php echo htmlspecialchars($t['nombre']); ?></td>
                    <td>
                        <span class="status-pill <?php echo $t['tipo'] === 'percent' ? 'status-card' : 'status-cash'; ?>">
                            <?php echo $t['tipo'] === 'percent' ? '% sobre precio actual' : 'Importe fijo (€)'; ?>
                        </span>
                    </td>
                    <td class="text-right font-mono font-bold">
                        <?php echo $t['tipo'] === 'percent'
                            ? number_format($t['valor'], 2, ',', '.') . ' %'
                            : number_format($t['valor'], 2, ',', '.') . ' €'; ?>
                    </td>
                    <td class="text-center font-mono fs-12">
                        <?php echo date('d/m/Y', strtotime($t['fecha_aplicacion'])); ?>
                    </td>
                    <td class="text-center fs-12">
                        <?php
                        $scopeLabel = 'Todos los productos';
                        if (($t['scope'] ?? 'todos') === 'categoria') {
                            $scopeLabel = 'Categoría: ' . htmlspecialchars($t['categoria'] ?? '-');
                        } elseif (($t['scope'] ?? 'todos') === 'productos') {
                            $scopeLabel = 'Productos seleccionados';
                        }
                        echo $scopeLabel;
                        ?>
                    </td>
                    <td class="text-center">
                        <?php if ($t['aplicada']): ?>
                            <span class="status-pill status-active">
                                <i class="fa-solid fa-circle-check"></i> Aplicada
                            </span>
                        <?php else: ?>
                            <span class="status-pill status-inactive">
                                <i class="fa-solid fa-clock"></i> Pendiente
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="d-flex jc-center gap-8 pr-20">
                            <?php if (!$t['aplicada']): ?>
                            <button onclick="aplicarTarifa(<?php echo $t['id']; ?>)" title="Aplicar ahora" class="btn-icon">
                                <i class="fa-solid fa-arrow-up-wide-short"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($avTarifas['lista'])): ?>
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="fa-solid fa-scale-balanced"></i>
                            Aún no hay tarifas registradas.
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL NUEVA TARIFA -->
<div class="modal-overlay" id="tarifaModal">
    <div class="modal modal-content gap-16 ai-stretch w-400">
        <div class="modal-header mb-0">
            <h2 class="m-0 fs-18">Nueva Tarifa de Precios</h2>
            <button onclick="cerrarModalTarifa()" class="btn-close-modal">&times;</button>
        </div>
        <form id="tarifaForm" class="modal-body p-20">
            <div class="form-group">
                <label class="form-label">Nombre</label>
                <input type="text" id="tarifaNombre" class="form-input" placeholder="Ej: Subida general 2027">
                <span class="form-error" id="err-nombre"></span>
            </div>
            <div class="d-grid grid-3 gap-16">
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase">Tipo</label>
                    <select id="tarifaTipo" class="form-input">
                        <option value="percent">% sobre precio</option>
                        <option value="amount">Importe fijo (€)</option>
                    </select>
                    <span class="form-error" id="err-tipo"></span>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase">Valor</label>
                    <input type="number" id="tarifaValor" class="form-input text-right" step="0.01">
                    <span class="form-error" id="err-valor"></span>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase">Fecha aplicación</label>
                    <input type="date" id="tarifaFecha" class="form-input">
                    <span class="form-error" id="err-fecha_aplicacion"></span>
                </div>
            </div>

            <div class="mt-16">
                <label class="form-label fs-11 tt-uppercase">Ámbito de aplicación</label>
                <div class="d-grid grid-2 gap-16">
                    <div class="form-group mb-0">
                        <select id="tarifaScope" class="form-input" onchange="onScopeChangeTarifa()">
                            <option value="todos">Todos los productos</option>
                            <option value="categoria">Por categoría</option>
                            <option value="productos">Productos concretos</option>
                        </select>
                    </div>
                    <div class="form-group mb-0" id="tarifaCategoriaWrapper" style="display:none;">
                        <input type="text" id="tarifaCategoria" class="form-input" placeholder="Nombre de la categoría">
                        <span class="form-error" id="err-categoria"></span>
                    </div>
                </div>
                <div class="form-group mt-12" id="tarifaProductosWrapper" style="display:none; max-height:200px; overflow:auto;">
                    <label class="form-label fs-11 tt-uppercase mb-4">Selecciona productos</label>
                    <div class="checkbox-list">
                        <?php foreach ($avTarifas['productos'] as $p): ?>
                            <label class="checkbox-item d-flex ai-center gap-8">
                                <input type="checkbox" class="tarifa-prod-checkbox" value="<?php echo $p->getId(); ?>">
                                <span class="fs-12">
                                    <?php echo htmlspecialchars($p->getNombre()); ?>
                                    <span class="text-muted">[<?php echo htmlspecialchars($p->getReferencia()); ?>]</span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <span class="form-error" id="err-producto_ids"></span>
                </div>
            </div>
        </form>
        <div class="modal-footer full-width">
            <button onclick="cerrarModalTarifa()" class="btn-cancel">Cancelar</button>
            <button onclick="guardarTarifa()" class="btn-save">Guardar tarifa</button>
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
    catWrapper.style.display = scope === 'categoria' ? '' : 'none';
    prodWrapper.style.display = scope === 'productos' ? '' : 'none';
}

function abrirModalTarifa() {
    limpiarErroresTarifa();
    document.getElementById('tarifaNombre').value = '';
    document.getElementById('tarifaTipo').value = 'percent';
    document.getElementById('tarifaValor').value = '';
    document.getElementById('tarifaFecha').value = '';
    document.getElementById('tarifaScope').value = 'todos';
    onScopeChangeTarifa();
    document.querySelectorAll('.tarifa-prod-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('tarifaModal').classList.add('visible');
}

function cerrarModalTarifa() {
    document.getElementById('tarifaModal').classList.remove('visible');
    limpiarErroresTarifa();
}

async function guardarTarifa() {
    limpiarErroresTarifa();
    const scope = document.getElementById('tarifaScope').value;
    const categoria = document.getElementById('tarifaCategoria').value.trim();
    const prodIds = Array.from(document.querySelectorAll('.tarifa-prod-checkbox:checked'))
        .map(cb => parseInt(cb.value, 10))
        .filter(id => !isNaN(id));
    const payload = {
        accion: 'añadir',
        nombre: document.getElementById('tarifaNombre').value.trim(),
        tipo: document.getElementById('tarifaTipo').value,
        valor: document.getElementById('tarifaValor').value,
        fecha_aplicacion: document.getElementById('tarifaFecha').value,
        scope,
        categoria: scope === 'categoria' ? categoria : null,
        producto_ids: scope === 'productos' ? prodIds : [],
    };
    try {
        const resp = await fetch('api/gestionTarifa.php', {
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
            alert('Error: ' + (r.error || 'No se pudo guardar la tarifa'));
        }
    } catch (e) {
        console.error(e);
        alert('Error de conexión con el servidor');
    }
}

async function aplicarTarifa(id) {
    if (!id || !confirm('¿Aplicar esta tarifa a todos los precios actuales? Esta operación no se puede deshacer.')) return;
    try {
        const resp = await fetch('api/gestionTarifa.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'aplicar', id }),
        });
        const r = await resp.json();
        if (r.ok) {
            location.reload();
        } else {
            alert('Error: ' + (r.error || 'No se pudo aplicar la tarifa'));
        }
    } catch (e) {
        console.error(e);
        alert('Error de conexión con el servidor');
    }
}
</script>

