<?php // Vista de Promociones 
?>
<script>
    // Helpers para evitar TypeErrors si algún elemento no existe
    const setVal = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.value = val;
    };
    const setChecked = (id, bool) => {
        const el = document.getElementById(id);
        if (el) el.checked = !!bool;
    };
    const setText = (id, txt) => {
        const el = document.getElementById(id);
        if (el) el.innerText = txt;
    };

    window.limpiarErroresPromo = function() {
        const errors = document.querySelectorAll('#promoModal .form-error');
        if (errors) errors.forEach(el => el.innerText = '');
    };

    window.togglePromoFields = function() {
        const tipoEl = document.getElementById('promoTipo');
        if (!tipoEl) return;
        const tipo = tipoEl.value;
        const fieldsBundle = document.getElementById('fieldsBundle');
        const fieldsGeneral = document.getElementById('fieldsGeneral');
        const blockPayQty = document.getElementById('blockPayQty');
        const labelValor = document.getElementById('labelValor');

        if (tipo === 'bundle' || tipo === 'fixed_bundle') {
            if (fieldsBundle) fieldsBundle.classList.remove('d-none');
            if (blockPayQty) blockPayQty.classList.toggle('d-none', tipo === 'fixed_bundle');
            if (fieldsGeneral) fieldsGeneral.classList.toggle('d-none', tipo === 'bundle');
            if (tipo === 'fixed_bundle' && labelValor) labelValor.innerText = 'Precio total del Pack (€)';
        } else {
            if (fieldsBundle) fieldsBundle.classList.add('d-none');
            if (fieldsGeneral) fieldsGeneral.classList.remove('d-none');
            if (labelValor) labelValor.innerText = 'Valor del Descuento';
        }
    };

    window.abrirModalPromo = function(promo = null) {
        limpiarErroresPromo();
        const modal = document.getElementById('promoModal');
        if (!modal) return;

        if (promo) {
            setText('promoModalTitle', 'Editar promoción');
            setVal('promoId', promo.id);
            setVal('promoCodigo', promo.codigo || '');
            setVal('promoDescripcion', promo.descripcion || '');
            setVal('promoTipo', promo.tipo || 'percent');
            setVal('promoValor', promo.valor || 0);
            setVal('promoMin', promo.min_subtotal || 0);
            setVal('promoBuyQty', promo.bundle_buy_qty || '');
            setVal('promoPayQty', promo.bundle_pay_qty || '');
            setVal('promoProducto', promo.id_producto || '');
            setVal('promoCategoria', promo.categoria_code || '');
            setChecked('promoSoloSocios', parseInt(promo.solo_socios));
            setChecked('promoActiva', parseInt(promo.activo));
        } else {
            setText('promoModalTitle', 'Nueva promoción');
            setVal('promoId', '');
            setVal('promoCodigo', '');
            setVal('promoDescripcion', '');
            setVal('promoTipo', 'percent');
            setVal('promoValor', '');
            setVal('promoMin', '');
            setVal('promoBuyQty', '');
            setVal('promoPayQty', '');
            setVal('promoProducto', '');
            setVal('promoCategoria', '');
            setChecked('promoSoloSocios', false);
            setChecked('promoActiva', true);
        }

        togglePromoFields();
        modal.classList.add('visible');
    };

    window.cerrarModalPromo = function() {
        const modal = document.getElementById('promoModal');
        if (modal) modal.classList.remove('visible');
        limpiarErroresPromo();
    };

    window.guardarPromo = async function() {
        limpiarErroresPromo();
        const id = document.getElementById('promoId')?.value;
        const payload = {
            accion: id ? 'editar' : 'añadir',
            id: id || null,
            codigo: document.getElementById('promoCodigo')?.value.trim() || '',
            descripcion: document.getElementById('promoDescripcion')?.value.trim() || '',
            tipo: document.getElementById('promoTipo')?.value || 'percent',
            valor: document.getElementById('promoValor')?.value || 0,
            min_subtotal: document.getElementById('promoMin')?.value || 0,
            bundle_buy_qty: document.getElementById('promoBuyQty')?.value || '',
            bundle_pay_qty: document.getElementById('promoPayQty')?.value || '',
            id_producto: document.getElementById('promoProducto')?.value || '',
            categoria_code: document.getElementById('promoCategoria')?.value || '',
            solo_socios: document.getElementById('promoSoloSocios')?.checked ? 1 : 0,
            activo: document.getElementById('promoActiva')?.checked ? 1 : 0,
        };

        try {
            const resp = await fetch('api/gestionPromocion.php', {
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
                    setText('err-' + field, msg);
                }
            } else {
                alert('Error: ' + (r.error || 'No se pudo guardar la promoción'));
            }
        } catch (e) {
            console.error(e);
            alert('Error de conexión con el servidor');
        }
    };

    window.togglePromo = async function(id) {
        if (!id) return;
        try {
            const resp = await fetch('api/gestionPromocion.php', {
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
            if (r.ok) {
                location.reload();
            } else {
                alert('Error: ' + (r.error || 'No se pudo cambiar el estado'));
            }
        } catch (e) {
            console.error(e);
        }
    };

    window.eliminarPromo = async function(id) {
        if (!id || !confirm('¿Seguro que deseas eliminar esta promoción?')) return;
        try {
            const resp = await fetch('api/gestionPromocion.php', {
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
            if (r.ok) {
                location.reload();
            } else {
                alert('Error: ' + (r.error || 'No se pudo eliminar la promoción'));
            }
        } catch (e) {
            console.error(e);
        }
    };
</script>


<div class="main-full p-24">
    <div class="section-header container-wider">
        <div class="section-title">
            <h1>Gestión de Descuentos y Promociones</h1>
            <p>Configura los cupones y reglas de descuento disponibles en el TPV.</p>
        </div>
        <div class="d-flex gap-12">
            <button onclick="window.abrirModalPromo()" class="btn-add">
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
                    <th>Configuración</th>
                    <th class="text-right">Mínimo</th>
                    <th class="text-center">Solo socios</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center pr-20">Acciones</th>
                </tr>
            </thead>
            <tbody id="promosTableBody">
                <?php foreach ($avPromos['lista'] as $p): ?>
                    <tr data-id="<?php echo $p['id']; ?>">
                        <td class="pl-20 font-mono"><?php echo htmlspecialchars($p['codigo'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($p['descripcion'] ?? ''); ?></td>
                        <td class="text-center">
                            <?php
                            $badge = 'status-card';
                            $label = 'Descuento';
                            if ($p['tipo'] === 'percent') {
                                $badge = 'status-card';
                                $label = '% Total';
                            }
                            if ($p['tipo'] === 'amount') {
                                $badge = 'status-cash';
                                $label = '€ Total';
                            }
                            if ($p['tipo'] === 'bundle') {
                                $badge = 'status-active';
                                $label = 'Pack (2x1...)';
                            }
                            if ($p['tipo'] === 'fixed_bundle') {
                                $badge = 'status-active';
                                $label = 'Precio Fijo';
                            }
                            ?>
                            <span class="status-pill <?php echo $badge; ?>">
                                <?php echo $label; ?>
                            </span>
                        </td>
                        <td class="fs-12">
                            <?php if ($p['tipo'] === 'bundle'): ?>
                                <strong><?php echo $p['bundle_buy_qty']; ?>x<?php echo $p['bundle_pay_qty']; ?></strong>
                            <?php elseif ($p['tipo'] === 'fixed_bundle'): ?>
                                <strong><?php echo $p['bundle_buy_qty']; ?> por <?php echo number_format($p['valor'], 2, ',', '.'); ?> €</strong>
                            <?php else: ?>
                                <strong><?php echo $p['valor']; ?><?php echo $p['tipo'] === 'percent' ? '%' : '€'; ?></strong>
                            <?php endif; ?>

                            <?php if ($p['id_producto']): ?>
                                <div class="text-muted fs-11 mt-4">Prod ID: <?php echo $p['id_producto']; ?></div>
                            <?php elseif ($p['categoria_code']): ?>
                                <div class="text-muted fs-11 mt-4">Cat: <?php echo htmlspecialchars($p['categoria_code'] ?? ''); ?></div>
                            <?php endif; ?>
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
                                <button onclick="window.abrirModalPromo(<?php echo htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8'); ?>)" title="Editar" class="btn-icon">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button onclick="window.togglePromo(<?php echo $p['id']; ?>)" title="<?php echo $p['activo'] ? 'Desactivar' : 'Activar'; ?>" class="btn-icon <?php echo $p['activo'] ? 'text-red' : 'text-green'; ?>">
                                    <i class="fa-solid fa-<?php echo $p['activo'] ? 'pause' : 'play'; ?>"></i>
                                </button>
                                <button onclick="window.eliminarPromo(<?php echo $p['id']; ?>)" title="Eliminar" class="btn-icon btn-icon-danger">
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
    <div class="modal modal-content gap-16 ai-stretch w-500">
        <div class="modal-header mb-0">
            <h2 id="promoModalTitle" class="m-0 fs-18">Nueva promoción</h2>
            <button onclick="cerrarModalPromo()" class="btn-close-modal">&times;</button>
        </div>
        <form id="promoForm" class="modal-body p-20">
            <input type="hidden" id="promoId">

            <div class="d-grid grid-2 gap-16">
                <div class="form-group">
                    <label class="form-label">Código <span class="text-muted fs-11" style="font-weight: normal;">(Opcional si es Automático)</span></label>
                    <input type="text" id="promoCodigo" class="form-input font-mono" placeholder="EJ: 2X1AUDIO">
                    <span class="form-error" id="err-codigo"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo de Oferta</label>
                    <select id="promoTipo" class="form-input" onchange="togglePromoFields()">
                        <option value="percent">% Descuento sobre Total</option>
                        <option value="amount">Euros fijos sobre Total</option>
                        <option value="bundle">Pack de Unidades (Ej: 2x1, 3x2...)</option>
                        <option value="fixed_bundle">Precio Fijo x Unidades (Ej: 3 por 10€)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Descripción para el TPV</label>
                <input type="text" id="promoDescripcion" class="form-input" placeholder="Ej: Oferta 2x1 en Auriculares">
                <span class="form-error" id="err-descripcion"></span>
            </div>

            <!-- Campos Dinámicos -->
            <div id="fieldsGeneral" class="d-grid grid-2 gap-16">
                <div class="form-group mb-0">
                    <label class="form-label" id="labelValor">Valor del Descuento</label>
                    <input type="number" id="promoValor" class="form-input text-right" step="0.01" min="0">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Mín. compra (€)</label>
                    <input type="number" id="promoMin" class="form-input text-right" step="0.01" min="0">
                </div>
            </div>

            <div id="fieldsBundle" class="d-none p-16 bg-surface2 br-12 border-2 mb-16">
                <div class="d-grid grid-2 gap-16">
                    <div class="form-group">
                        <label class="form-label">Cantidad a llevar (X)</label>
                        <input type="number" id="promoBuyQty" class="form-input h-40" placeholder="Ej: 2">
                    </div>
                    <div id="blockPayQty" class="form-group">
                        <label class="form-label">Cantidad a pagar (Y)</label>
                        <input type="number" id="promoPayQty" class="form-input h-40" placeholder="Ej: 1">
                    </div>
                </div>
            </div>

            <div class="form-group mt-12 bg-surface2 p-16 br-12 border-2">
                <label class="form-label"><i class="fa-solid fa-filter mr-4"></i> Aplicar a (opcional)</label>
                <div class="d-grid grid-2 gap-16">
                    <select id="promoProducto" class="form-input fs-12 h-40">
                        <option value="">Cualquier Producto</option>
                        <?php foreach ($avPromos['productos'] as $prod): ?>
                            <option value="<?php echo $prod->getId(); ?>"><?php echo htmlspecialchars($prod->getNombre()); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="promoCategoria" class="form-input fs-12 h-40">
                        <option value="">Cualquier Categoría</option>
                        <?php foreach ($avPromos['categorias'] as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['codigo']); ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>

                </div>
                <p class="fs-10 text-muted mt-8">Si se deja vacío, la promoción se aplica a todo el carrito.</p>
            </div>

            <div class="d-grid grid-2 gap-16 mt-16">
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
        <div class="modal-footer pt-0 border-top-0">
            <button onclick="window.cerrarModalPromo()" class="btn-cancel">Cancelar</button>
            <button onclick="window.guardarPromo()" class="btn-save w-auto px-24">Guardar promoción</button>

        </div>
    </div>
</div>