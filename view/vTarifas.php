<?php // Vista de Tarifas de Precios 
?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
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
            <a href="index.php?irDashboard=1" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="table-container container-wider">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="pl-20" style="width: 40px;"></th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th class="text-right">Valor</th>
                    <th class="text-center">Fecha aplicación</th>
                    <th class="text-center">Ámbito</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center pr-20">Acciones</th>
                </tr>
            </thead>
            <tbody id="tarifasTableBody">
                <?php foreach ($avTarifas['lista'] as $t): ?>
                    <tr data-id="<?php echo $t['id']; ?>">
                        <td class="pl-20">
                            <i class="fa-solid fa-grip-vertical drag-handle"></i>
                        </td>
                        <td><?php echo htmlspecialchars($t['nombre']); ?></td>
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
                            <div class="d-flex flex-column gap-2">
                                <span>
                                    <?php echo date('d/m/Y', strtotime($t['fecha_aplicacion'])); ?>
                                    <?php if (!empty($t['fecha_fin'])): ?>
                                        - <?php echo date('d/m/Y', strtotime($t['fecha_fin'])); ?>
                                    <?php endif; ?>
                                </span>
                                <?php if (!empty($t['dias_semana'])): ?>
                                    <span class="fs-10 text-accent font-bold">
                                        <?php
                                        $mapDias = [1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S', 0 => 'D'];
                                        $dArray = explode(',', $t['dias_semana']);
                                        $labels = array_map(fn($d) => $mapDias[$d], $dArray);
                                        echo 'Días: ' . implode(',', $labels);
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </div>
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
                            <div class="d-flex flex-column gap-4 ai-center">
                                <?php if ($t['aplicada']): ?>
                                    <span class="status-pill status-active" style="padding: 2px 8px; font-size: 10px;">
                                        <i class="fa-solid fa-circle-check"></i> APLICADA
                                    </span>
                                <?php else: ?>
                                    <span class="status-pill status-inactive" style="padding: 2px 8px; font-size: 10px;">
                                        <i class="fa-solid fa-clock"></i> PENDIENTE
                                    </span>
                                <?php endif; ?>

                                <span onclick="toggleTarifa(<?php echo $t['id']; ?>)"
                                    class="status-pill cursor-pointer <?php echo $t['activo'] ? 'status-paid' : 'text-muted border-2'; ?>"
                                    style="padding: 2px 8px; font-size: 10px; width: fit-content;"
                                    title="Click para cambiar estado">
                                    <i class="fa-solid <?php echo $t['activo'] ? 'fa-play' : 'fa-pause'; ?>"></i>
                                    <?php echo $t['activo'] ? 'ACTIVA' : 'PAUSADA'; ?>
                                </span>
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="d-flex jc-center gap-8 pr-20">
                                <?php if (!$t['aplicada']): ?>
                                    <button onclick="aplicarTarifa(<?php echo $t['id']; ?>)" title="Aplicar permanentemente" class="btn-icon">
                                        <i class="fa-solid fa-bolt"></i>
                                    </button>
                                <?php endif; ?>
                                <button onclick='abrirModalTarifa(<?php echo json_encode($t, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' title="Editar regla" class="btn-icon">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button onclick="eliminarTarifa(<?php echo $t['id']; ?>)" title="Eliminar regla" class="btn-icon text-red">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
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
    <div class="modal modal-content gap-16 ai-stretch w-modal-lg">
        <div class="modal-header mb-0">
            <h2 class="m-0 fs-18">Configurar Regla de Tarifa / Precio</h2>
            <button onclick="cerrarModalTarifa()" class="btn-close-modal">&times;</button>
        </div>
        <form id="tarifaForm" class="modal-body p-20">
            <input type="hidden" id="tarifaId">
            <div class="form-group">
                <label class="form-label">Nombre de la Regla</label>
                <input type="text" id="tarifaNombre" class="form-input" placeholder="Ej: Happy Hour, Tarifa Mayoristas...">
                <span class="form-error" id="err-nombre"></span>
            </div>

            <div class="d-grid grid-3 gap-16">
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase">Tipo Variación</label>
                    <select id="tarifaTipo" class="form-input">
                        <option value="percent">% sobre precio</option>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase">Valor (+ o -)</label>
                    <input type="number" id="tarifaValor" class="form-input text-right" step="0.01">
                    <span class="form-error" id="err-valor"></span>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase">Prioridad</label>
                    <input type="number" id="tarifaPrioridad" class="form-input text-right" value="0">
                </div>
            </div>

            <div class="mt-16 bg-surface2 p-12 br-8 border-2">
                <label class="form-label fs-11 tt-uppercase mb-8"><i class="fa-solid fa-clock"></i> Programación Temporal</label>
                <div class="d-grid grid-2 gap-16">
                    <div class="form-group mb-0">
                        <label class="form-label fs-10">Fecha Inicio</label>
                        <input type="date" id="tarifaFecha" class="form-input">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label fs-10">Fecha Fin (Opcional)</label>
                        <input type="date" id="tarifaFechaFin" class="form-input">
                    </div>
                </div>

                <div class="form-group mt-12 mb-12">
                    <label class="form-label fs-10 mb-8">Días de la semana (si no marcas ninguno, se aplica todos los días)</label>
                    <div class="d-flex flex-wrap gap-12">
                        <?php
                        $dias = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 0 => 'Dom'];
                        foreach ($dias as $val => $label): ?>
                            <label class="checkbox-item d-flex ai-center gap-4 cursor-pointer">
                                <input type="checkbox" class="tarifa-dia-checkbox" value="<?php echo $val; ?>">
                                <span class="fs-11"><?php echo $label; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="d-grid grid-2 gap-16">
                    <div class="form-group mb-0">
                        <label class="form-label fs-10">Hora Inicio</label>
                        <input type="time" id="tarifaHoraInicio" class="form-input">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label fs-10">Hora Fin</label>
                        <input type="time" id="tarifaHoraFin" class="form-input">
                    </div>
                </div>
            </div>

            <div class="mt-16 bg-surface2 p-12 br-8 border-2">
                <label class="form-label fs-11 tt-uppercase mb-8"><i class="fa-solid fa-user-tag"></i> Segmentación por Cliente</label>
                <div class="d-grid grid-3 gap-16">
                    <div class="form-group mb-0">
                        <label class="form-label fs-10">Tipo de Cliente</label>
                        <select id="tarifaTipoCliente" class="form-input">
                            <option value="todos">Cualquiera</option>
                            <option value="particular">Particular</option>
                            <option value="empresa">Empresa</option>
                            <option value="mayorista">Mayorista</option>
                        </select>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label fs-10">Cliente Específico</label>
                        <select id="tarifaIdCliente" class="form-input">
                            <option value="">- Seleccionar -</option>
                            <?php foreach ($avTarifas['clientes'] as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nombre'] . ' ' . ($c['apellidos'] ?? '')); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-0 d-flex ai-end pb-8">
                        <label class="checkbox-item d-flex ai-center gap-8 cursor-pointer">
                            <input type="checkbox" id="tarifaSoloSocios">
                            <span class="fs-12">Solo Socios</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-16">
                <label class="form-label fs-11 tt-uppercase">Ámbito de Productos</label>
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
                    </div>
                </div>
                <div class="form-group mt-12" id="tarifaProductosWrapper" style="display:none; max-height:150px; overflow:auto;">
                    <div class="checkbox-list">
                        <?php foreach ($avTarifas['productos'] as $p): ?>
                            <label class="checkbox-item d-flex ai-center gap-8">
                                <input type="checkbox" class="tarifa-prod-checkbox" value="<?php echo $p->getId(); ?>">
                                <span class="fs-11">
                                    <?php echo htmlspecialchars($p->getNombre()); ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </form>
        <div class="modal-footer full-width">
            <button onclick="cerrarModalTarifa()" class="btn-cancel">Cancelar</button>
            <button onclick="guardarTarifa()" class="btn-save">Guardar Regla</button>
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

    function abrirModalTarifa(t = null) {
        limpiarErroresTarifa();
        if (t) {
            document.getElementById('tarifaId').value = t.id;
            document.getElementById('tarifaNombre').value = t.nombre;
            document.getElementById('tarifaTipo').value = t.tipo;
            document.getElementById('tarifaValor').value = t.valor;
            document.getElementById('tarifaPrioridad').value = t.prioridad || 0;
            document.getElementById('tarifaFecha').value = t.fecha_aplicacion;
            document.getElementById('tarifaFechaFin').value = t.fecha_fin || '';
            document.getElementById('tarifaHoraInicio').value = t.hora_inicio || '';
            document.getElementById('tarifaHoraFin').value = t.hora_fin || '';

            // Días de la semana
            const diasRule = t.dias_semana ? t.dias_semana.split(',').map(Number) : [];
            document.querySelectorAll('.tarifa-dia-checkbox').forEach(cb => {
                cb.checked = diasRule.includes(parseInt(cb.value));
            });
            document.getElementById('tarifaTipoCliente').value = t.tipo_cliente || 'todos';
            document.getElementById('tarifaIdCliente').value = t.id_cliente || '';
            document.getElementById('tarifaSoloSocios').checked = parseInt(t.es_solo_socios) === 1;
            document.getElementById('tarifaScope').value = t.scope || 'todos';
            document.getElementById('tarifaCategoria').value = t.categoria || '';

            // Checkboxes de productos
            document.querySelectorAll('.tarifa-prod-checkbox').forEach(cb => {
                cb.checked = false;
                if (t.producto_ids) {
                    try {
                        const ids = JSON.parse(t.producto_ids);
                        if (Array.isArray(ids) && ids.includes(parseInt(cb.value))) {
                            cb.checked = true;
                        }
                    } catch (e) {}
                }
            });
            document.querySelector('.modal-header h2').innerText = 'Editar Regla de Tarifa';
        } else {
            document.getElementById('tarifaId').value = '';
            document.getElementById('tarifaNombre').value = '';
            document.getElementById('tarifaTipo').value = 'percent';
            document.getElementById('tarifaValor').value = '';
            document.getElementById('tarifaPrioridad').value = '0';
            document.getElementById('tarifaFecha').value = '';
            document.getElementById('tarifaFechaFin').value = '';
            document.getElementById('tarifaHoraInicio').value = '';
            document.getElementById('tarifaHoraFin').value = '';
            document.querySelectorAll('.tarifa-dia-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('tarifaTipoCliente').value = 'todos';
            document.getElementById('tarifaIdCliente').value = '';
            document.getElementById('tarifaSoloSocios').checked = false;
            document.getElementById('tarifaScope').value = 'todos';
            document.getElementById('tarifaCategoria').value = '';
            document.querySelectorAll('.tarifa-prod-checkbox').forEach(cb => cb.checked = false);
            document.querySelector('.modal-header h2').innerText = 'Configurar Regla de Tarifa / Precio';
        }
        onScopeChangeTarifa();
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
        const tId = document.getElementById('tarifaId').value;
        const diasSema = Array.from(document.querySelectorAll('.tarifa-dia-checkbox:checked'))
            .map(cb => parseInt(cb.value, 10));

        const payload = {
            accion: tId ? 'editar' : 'añadir',
            id: tId || null,
            nombre: document.getElementById('tarifaNombre').value.trim(),
            tipo: document.getElementById('tarifaTipo').value,
            valor: document.getElementById('tarifaValor').value,
            prioridad: document.getElementById('tarifaPrioridad')?.value || 0,
            fecha_aplicacion: document.getElementById('tarifaFecha').value,
            fecha_fin: document.getElementById('tarifaFechaFin').value || null,
            dias_semana: diasSema,
            hora_inicio: document.getElementById('tarifaHoraInicio')?.value || null,
            hora_fin: document.getElementById('tarifaHoraFin')?.value || null,
            tipo_cliente: document.getElementById('tarifaTipoCliente')?.value || 'todos',
            id_cliente: document.getElementById('tarifaIdCliente')?.value || null,
            es_solo_socios: document.getElementById('tarifaSoloSocios')?.checked ? 1 : 0,
            scope,
            categoria: scope === 'categoria' ? categoria : null,
            producto_ids: scope === 'productos' ? prodIds : [],
        };
        try {
            const resp = await fetch('api/gestionTarifa.php', {
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
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    accion: 'aplicar',
                    id
                }),
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
    async function toggleTarifa(id) {
        if (!id) return;
        try {
            const resp = await fetch('api/gestionTarifa.php', {
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
            if (r.ok) location.reload();
            else alert('Error: ' + (r.error || 'No se pudo cambiar el estado'));
        } catch (e) {
            console.error(e);
            alert('Error de conexión');
        }
    }

    async function eliminarTarifa(id) {
        if (!id || !confirm('¿Seguro que quieres eliminar esta regla de tarifa?')) return;
        try {
            const resp = await fetch('api/gestionTarifa.php', {
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
            if (r.ok) location.reload();
            else alert('Error: ' + (r.error || 'No se pudo eliminar la tarifa'));
        } catch (e) {
            console.error(e);
            alert('Error de conexión');
        }
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const el = document.getElementById('tarifasTableBody');
        if (!el) return;

        Sortable.create(el, {
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'sortable-ghost',
            onEnd: async function() {
                const ids = Array.from(el.querySelectorAll('tr[data-id]')).map(tr => tr.dataset.id);
                try {
                    const resp = await fetch('api/gestionTarifa.php', {
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
                        alert('Error al guardar el nuevo orden: ' + (r.error || 'Desconocido'));
                        location.reload();
                    }
                } catch (e) {
                    console.error(e);
                    alert('Error de conexión al reordenar');
                    location.reload();
                }
            }
        });
    });
</script>

<style>
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
</style>