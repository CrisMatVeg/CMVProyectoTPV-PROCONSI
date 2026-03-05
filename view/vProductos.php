<?php // Vista de Productos 
?>
<div class="main-full p-24">

    <!-- CABECERA DE SECCIÓN -->
    <div class="section-header container-wider">
        <div class="section-title">
            <h1>Gestión de Productos</h1>
            <p>Administra el catálogo de productos, precios y categorías.</p>
        </div>
        <div class="d-flex gap-12 ai-center">
            <!-- Botón Exportar con dropdown -->
            <div class="ie-dropdown-wrap" id="exportDropdownWrap">
                <button class="btn-ie btn-ie-export" onclick="toggleExportDropdown()">
                    <i class="fa-solid fa-file-export"></i> Exportar <i class="fa-solid fa-chevron-down fs-10"></i>
                </button>
                <div class="ie-dropdown" id="exportDropdown">
                    <a href="api/exportarProductos.php?format=csv" class="ie-dropdown-item">
                        <i class="fa-solid fa-file-csv text-green"></i> CSV (Excel)
                    </a>
                    <a href="api/exportarProductos.php?format=json" class="ie-dropdown-item">
                        <i class="fa-solid fa-file-code text-accent"></i> JSON
                    </a>
                </div>
            </div>
            <!-- Botón Importar -->
            <button class="btn-ie btn-ie-import" onclick="document.getElementById('importFileInput').click()">
                <i class="fa-solid fa-file-import"></i> Importar
            </button>
            <input type="file" id="importFileInput" accept=".csv,.json" class="d-none" onchange="importarProductos(this)">
            <!-- Gestión de Categorías -->
            <button onclick="abrirModalGestionCategorias()" class="btn-filter">
                <i class="fa-solid fa-tags"></i> Categorías
            </button>
            <button onclick="abrirModalProducto()" class="btn-add">
                <i class="fa-solid fa-plus"></i> Nuevo Producto
            </button>
        </div>
    </div>

    <!-- PANEL DE FILTROS -->
    <div class="filters-panel container-wider">
        <div class="search-bar-wrap flex-1">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" id="prodSearch" placeholder="Buscar por nombre o SKU..." class="search-input">
        </div>
        <div class="d-flex gap-8">
            <input type="number" id="filterPriceMin" placeholder="Precio Min" class="form-input w-100 fs-12">
            <input type="number" id="filterPriceMax" placeholder="Precio Max" class="form-input w-100 fs-12">
        </div>
        <select id="filterCat" class="filter-input p-10 br-8" onchange="applyFilters()">
            <option value="all">Todas las categorías</option>
            <?php foreach ($avProductos['categorias'] as $c): ?>
                <option value="<?php echo htmlspecialchars($c['codigo']); ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
            <?php endforeach; ?>
        </select>
        <select id="filterEstado" class="filter-input p-10 br-8">
            <option value="all">Todos los estados</option>
            <option value="1">Activos</option>
            <option value="0">Inactivos</option>
        </select>
        <select id="sortOrder" class="filter-input p-10 br-8">
            <option value="none">Ordenar por...</option>
            <option value="price-asc">Precio: Menor a Mayor</option>
            <option value="price-desc">Precio: Mayor a Menor</option>
            <option value="stock-asc">Stock: Menor a Mayor</option>
            <option value="stock-desc">Stock: Mayor a Menor</option>
        </select>
    </div>

    <!-- TABLA DE PRODUCTOS -->
    <div class="table-container container-wider">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-60 pl-20">ID</th>
                    <th class="w-80 text-center">Icono</th>
                    <th>Producto</th>
                    <th>Código</th>
                    <th>Categoría</th>
                    <th class="text-right">Stock</th>
                    <th class="text-right">Precio</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center pr-20">Acciones</th>
                </tr>
            </thead>
            <tbody id="productsTableBody">
                <?php foreach ($avProductos['productos'] as $p): ?>
                    <tr class="product-row"
                        data-nombre="<?php echo strtolower(htmlspecialchars($p['nombre'])); ?>"
                        data-codigo="<?php echo strtolower(htmlspecialchars($p['codigo'])); ?>"
                        data-categoria="<?php echo $p['categoria']; ?>"
                        data-precio="<?php echo $p['precio']; ?>"
                        data-stock="<?php echo $p['stock']; ?>"
                        data-activo="<?php echo $p['activo'] ? '1' : '0'; ?>">
                        <td class="font-mono text-muted"><?php echo $p['id']; ?></td>
                        <td class="text-center">
                            <?php if (strpos($p['icono'], 'data:image') === 0): ?>
                                <img src="<?php echo $p['icono']; ?>" class="prod-img-fixed" alt="Icono">
                            <?php else: ?>
                                <span class="fs-24"><?php echo $p['icono']; ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="font-bold"><?php echo htmlspecialchars($p['nombre']); ?></td>
                        <td class="text-muted"><?php echo htmlspecialchars($p['codigo']); ?></td>
                        <td>
                            <span class="cat-pill">
                                <?php echo htmlspecialchars($p['categoria']); ?>
                            </span>
                            <?php
                            $atributosStr = $p['atributos'] ?? null;
                            if ($atributosStr) {
                                $atributosArr = json_decode($atributosStr, true);
                                if (is_array($atributosArr) && count($atributosArr) > 0) {
                                    echo '<div class="mt-4 d-flex flex-wrap gap-4">';
                                    foreach ($atributosArr as $attr) {
                                        echo '<span class="px-8 py-2 br-4 fs-10 bg-accent-soft text-accent border border-accent">' . htmlspecialchars($attr) . '</span>';
                                    }
                                    echo '</div>';
                                }
                            }
                            ?>
                        </td>
                        <td class="text-right font-bold font-mono <?php echo $p['stock'] <= 5 ? 'text-red' : ''; ?>">
                            <?php echo $p['stock']; ?>
                        </td>
                        <td class="text-right font-bold font-mono">
                            <?php echo number_format($p['precio'], 2, ',', '.'); ?> €
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
                                <button onclick='abrirModalProducto(<?php echo json_encode($p); ?>)' title="Editar" class="btn-icon">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button onclick="toggleEstadoProducto(<?php echo $p['id']; ?>)" title="<?php echo $p['activo'] ? 'Dar de baja' : 'Activar'; ?>" class="btn-icon <?php echo $p['activo'] ? 'text-red' : 'text-green'; ?>">
                                    <i class="fa-solid fa-<?php echo $p['activo'] ? 'arrow-down' : 'arrow-up'; ?>"></i>
                                </button>
                                <button onclick="abrirModalEntradaStock(<?php echo $p['id']; ?>, '<?php echo addslashes(htmlspecialchars($p['nombre'])); ?>', <?php echo (int)$p['stock']; ?>, <?php echo (float)$p['precio_coste']; ?>)" title="Entrada de Stock" class="btn-icon text-accent">
                                    <i class="fa-solid fa-warehouse"></i>
                                </button>
                                <button onclick="eliminarProducto(<?php echo $p['id']; ?>, '<?php echo addslashes(htmlspecialchars($p['nombre'])); ?>')" title="Eliminar" class="btn-icon text-red" style="opacity:0.7;">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr id="noResults" class="d-none">
                    <td colspan="9">
                        <div class="empty-state">
                            <i class="fa-solid fa-box-open"></i>
                            No se han encontrado productos con los filtros seleccionados.
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>

<!-- MODAL NÚMEROS DE SERIE -->
<div id="modalNS" class="modal-overlay-bg" style="z-index: 1100;">
    <div class="modal-content" style="max-width: 500px; padding: 0; overflow: hidden;">
        <div class="modal-header p-20 bg-surface2 border-bottom">
            <h2 class="m-0 fs-18">Números de Serie</h2>
            <button onclick="cerrarModalNS()" class="btn-close-modal">&times;</button>
        </div>
        <div class="p-24">
            <div class="d-flex gap-8 mb-16">
                <input type="text" id="nuevoNS" placeholder="Escribe un S/N..." class="form-input flex-1">
                <button onclick="añadirNS()" class="btn-save w-auto">Añadir</button>
            </div>
            <div class="table-container" style="max-height: 300px; overflow-y: auto;">
                <table class="w-100 fs-13">
                    <thead>
                        <tr>
                            <th class="text-left">Número de Serie</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="listaNSTablaBody">
                        <!-- JS filler -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ── Modal: Entrada de Stock ─────────────────────────────────────────── -->
<div id="modalEntradaStock" class="modal-overlay-bg" style="display:none;" onclick="if(event.target===this)cerrarModalEntradaStock()">
    <div class="modal-content" style="max-width:440px; padding: 0; overflow: hidden;">
        <div class="modal-header p-20 bg-surface2 border-bottom">
            <h2 class="m-0 fs-18"><i class="fa-solid fa-warehouse"></i> Entrada de Stock</h2>
            <button onclick="cerrarModalEntradaStock()" class="btn-close-modal">&times;</button>
        </div>
        <div class="modal-body p-24">
            <div class="d-flex flex-column gap-16">
                <div style="background:var(--surface2);border-radius:8px;padding:12px 16px;">
                    <div style="font-weight:700;font-size:15px;" id="esNombreProducto">—</div>
                    <div style="display:flex;gap:24px;margin-top:8px;font-size:13px;color:var(--text-muted);">
                        <span>Stock actual: <strong id="esStockActual" style="color:var(--text);">—</strong></span>
                        <span>CMP actual: <strong id="esCmpActual" style="color:var(--text);">—</strong> €</span>
                    </div>
                </div>

                <input type="hidden" id="esIdProducto">

                <div>
                    <label class="form-label">Cantidad a añadir *</label>
                    <input type="number" id="esCantidad" class="form-input" min="1" step="1" placeholder="Ej: 10" oninput="calcularNuevoCMP()">
                    <span class="form-error" id="err-esCantidad"></span>
                </div>

                <div>
                    <label class="form-label">Precio de coste por unidad (€) *</label>
                    <input type="number" id="esPrecioCoste" class="form-input" min="0" step="0.01" placeholder="Ej: 45.50" oninput="calcularNuevoCMP()">
                    <span class="form-error" id="err-esPrecioCoste"></span>
                </div>

                <div id="esCmpPreview" style="display:none;background:var(--surface2);border-radius:8px;padding:12px 16px;font-size:13px;border-left:3px solid var(--accent);">
                    <i class="fa-solid fa-calculator"></i>
                    Nuevo CMP: <strong id="esCmpNuevo" style="color:var(--accent);">—</strong> €
                    &nbsp;·&nbsp; Nuevo stock: <strong id="esStockNuevo">—</strong> uds.
                </div>

                <div>
                    <label class="form-label">Notas</label>
                    <input type="text" id="esNotas" class="form-input" placeholder="Nº albarán, proveedor...">
                </div>
            </div>

            <div class="modal-footer pt-16 mt-24 border-top d-flex">
                <button type="button" class="btn-cancel" onclick="cerrarModalEntradaStock()">Cancelar</button>
                <div class="flex-1"></div>
                <button type="button" class="btn-save w-auto px-24" id="btnGuardarEntrada" onclick="guardarEntradaStock()">
                    <i class="fa-solid fa-arrow-up-from-bracket"></i> Registrar Entrada
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        let _esStockActual = 0;
        let _esCmpActual = 0;

        window.abrirModalEntradaStock = function(id, nombre, stock, cmp) {
            _esStockActual = parseFloat(stock) || 0;
            _esCmpActual = parseFloat(cmp) || 0;
            document.getElementById('esIdProducto').value = id;
            document.getElementById('esNombreProducto').textContent = nombre;
            document.getElementById('esStockActual').textContent = _esStockActual;
            document.getElementById('esCmpActual').textContent = _esCmpActual.toFixed(2).replace('.', ',');
            document.getElementById('esCantidad').value = '';
            document.getElementById('esPrecioCoste').value = '';
            document.getElementById('esNotas').value = '';
            document.getElementById('esCmpPreview').style.display = 'none';
            document.getElementById('err-esCantidad').textContent = '';
            document.getElementById('err-esPrecioCoste').textContent = '';
            document.getElementById('modalEntradaStock').style.display = 'flex';
        };

        window.cerrarModalEntradaStock = function() {
            document.getElementById('modalEntradaStock').style.display = 'none';
        };

        window.calcularNuevoCMP = function() {
            const qty = parseInt(document.getElementById('esCantidad').value) || 0;
            const cost = parseFloat(document.getElementById('esPrecioCoste').value) || 0;
            const prev = document.getElementById('esCmpPreview');
            if (qty <= 0) {
                prev.style.display = 'none';
                return;
            }
            const stockNuevo = _esStockActual + qty;
            const cmpNuevo = (_esStockActual * _esCmpActual + qty * cost) / stockNuevo;
            document.getElementById('esCmpNuevo').textContent = cmpNuevo.toFixed(2).replace('.', ',');
            document.getElementById('esStockNuevo').textContent = stockNuevo;
            prev.style.display = 'block';
        };

        window.guardarEntradaStock = async function() {
            const id = document.getElementById('esIdProducto').value;
            const cantidad = parseInt(document.getElementById('esCantidad').value);
            const precioCoste = parseFloat(document.getElementById('esPrecioCoste').value);
            const notas = document.getElementById('esNotas').value.trim();

            let ok = true;
            document.getElementById('err-esCantidad').textContent = '';
            document.getElementById('err-esPrecioCoste').textContent = '';

            if (!cantidad || cantidad < 1) {
                document.getElementById('err-esCantidad').textContent = 'Introduce una cantidad válida (mín. 1)';
                ok = false;
            }
            if (isNaN(precioCoste) || precioCoste < 0) {
                document.getElementById('err-esPrecioCoste').textContent = 'Introduce un precio de coste válido';
                ok = false;
            }
            if (!ok) return;

            const btn = document.getElementById('btnGuardarEntrada');
            btn.disabled = true;
            btn.textContent = 'Guardando…';

            try {
                const resp = await fetch('./api/entradaStock.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id_producto: id,
                        cantidad,
                        precio_coste: precioCoste,
                        notas
                    }),
                });
                const data = await resp.json();
                if (data.ok) {
                    cerrarModalEntradaStock();
                    if (typeof showToast === 'function') {
                        showToast('<i class="fa-solid fa-circle-check"></i> ' + data.msg);
                    }
                    setTimeout(() => location.reload(), 1400);
                } else {
                    if (typeof showToast === 'function') showToast('<i class="fa-solid fa-circle-xmark"></i> ' + data.error);
                    else alert(data.error);
                }
            } catch (e) {
                alert('Error de conexión: ' + e.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-arrow-up-from-bracket"></i> Registrar Entrada';
            }
        };
    })();
</script>
<!-- MODAL AÑADIR/EDITAR PRODUCTO -->
<div id="modalProducto" class="modal-overlay-bg">
    <div class="modal-content" style="max-width: 800px; padding: 0; overflow: hidden;">
        <div class="modal-header p-20 bg-surface2 border-bottom">
            <h2 id="modalTitle" class="m-0 fs-18">Nuevo Producto</h2>
            <button onclick="cerrarModalProducto()" class="btn-close-modal">&times;</button>
        </div>
        <form id="formProducto" class="modal-body p-24">
            <input type="hidden" id="prodId">

            <div class="d-grid grid-1-3 gap-24 mb-24">
                <!-- Columna Izquierda: Imagen y Meta -->
                <div class="d-flex flex-column gap-16">
                    <div class="form-group mb-0">
                        <label class="form-label">Imagen del Producto</label>
                        <div class="d-flex flex-column ai-center gap-12 p-16 bg-surface2 br-12 border-2">
                            <div id="imgPreview" class="prod-img-preview m-0" style="width: 120px; height: 120px; background: var(--bg-body);">
                                <i class="fa-solid fa-image fs-32 opacity-20"></i>
                            </div>
                            <input type="file" id="prodFile" accept="image/*" class="d-none" onchange="previewImage(this)">
                            <button type="button" onclick="document.getElementById('prodFile').click()" class="btn-filter w-auto h-auto p-10-16 fs-12 gap-8">
                                <i class="fa-solid fa-upload"></i> Subir
                            </button>
                            <input type="hidden" id="prodIcono">
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Datos Principales -->
                <div class="d-flex flex-column gap-16">
                    <div class="form-group mb-0">
                        <label class="form-label">Nombre del Producto</label>
                        <input type="text" id="prodNombre" placeholder="Ej: Auriculares Pro Max" class="form-input">
                        <span class="form-error" id="err-nombre"></span>
                    </div>

                    <div class="d-grid grid-2 gap-16">
                        <div class="form-group mb-0">
                            <label class="form-label">Referencia (SKU)</label>
                            <input type="text" id="prodCodigo" placeholder="AUD-001" class="form-input font-mono">
                            <span class="form-error" id="err-referencia"></span>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Categoría</label>
                            <select id="prodCat" class="form-input">
                                <?php foreach ($avProductos['categorias'] as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c['codigo']); ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group mb-24">
                <label class="form-label">Descripción</label>
                <textarea id="prodDesc" placeholder="Detalles técnicos y descripción comercial..." class="form-input" rows="2"></textarea>
            </div>

            <div class="d-grid grid-3 gap-16 mb-24 p-16 bg-surface2 br-12 border-2">
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase d-flex ai-center gap-4">
                        Coste (€) <i class="fa-solid fa-circle-info text-muted" title="Para cambiarlo en productos existentes, usa el botón de Entrada de Stock"></i>
                    </label>
                    <input type="text" id="prodPrecioCoste" placeholder="0.00" class="form-input text-right font-mono">
                    <span class="form-error" id="err-precio_coste"></span>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase">Venta (€)</label>
                    <input type="text" id="prodPrecioVenta" placeholder="0.00" class="form-input text-right font-bold font-mono text-accent">
                    <span class="form-error" id="err-precio_venta"></span>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase">IVA (%)</label>
                    <select id="prodIvaTipo" class="form-input">
                        <?php foreach ($avProductos['tipos_iva'] as $t): ?>
                            <option value="<?php echo htmlspecialchars($t['codigo']); ?>">
                                <?php echo htmlspecialchars($t['codigo'] . ' - ' . $t['porcentaje'] . '%'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="form-error" id="err-iva"></span>
                </div>
            </div>

            <div class="d-grid grid-3 gap-16 mb-24">
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase d-flex ai-center gap-4">
                        Stock Actual <i class="fa-solid fa-circle-info text-muted" title="Para cambiarlo en productos existentes, usa el botón de Entrada de Stock"></i>
                    </label>
                    <input type="text" id="prodStock" placeholder="0" class="form-input text-center font-bold">
                    <span class="form-error" id="err-stock_actual"></span>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase">Stock Mínimo</label>
                    <input type="text" id="prodStockMin" placeholder="5" class="form-input text-center">
                    <span class="form-error" id="err-stock_minimo"></span>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase">Garantía (meses)</label>
                    <input type="text" id="prodGarantia" placeholder="24" class="form-input text-center">
                    <span class="form-error" id="err-meses_garantia"></span>
                </div>
            </div>

            <!-- Variantes -->
            <div id="sectionVariantes" class="p-16 border-2 br-12 mb-24">
                <label class="form-label mb-12 d-flex ai-center gap-8">
                    <i class="fa-solid fa-tags text-muted"></i> Variantes (Talla, Color, etc.)
                </label>
                <div id="variantesContainer" class="d-flex flex-wrap gap-8 mb-12">
                    <!-- JS filler -->
                </div>
                <div class="d-grid grid-1-1-60 gap-8">
                    <input type="text" id="nuevaVarianteLabel" placeholder="Atributo" class="form-input fs-12">
                    <input type="text" id="nuevaVarianteValue" placeholder="Valor" class="form-input fs-12">
                    <button type="button" onclick="añadirVarianteUI()" class="btn-save w-auto p-0-12 h-40">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
                <input type="hidden" id="prodVariantes">
            </div>

            <!-- Atributos / Etiquetas -->
            <div id="sectionAtributos" class="p-16 border-2 br-12 mb-24">
                <label class="form-label mb-12 d-flex ai-center gap-8">
                    <i class="fa-solid fa-bookmark text-muted"></i> Atributos Extras (Etiquetas, Ej: Navidad, Oferta)
                </label>
                <div id="atributosContainer" class="d-flex flex-wrap gap-8 mb-12">
                    <!-- JS filler -->
                </div>
                <div class="d-grid grid-1-100 gap-8" style="grid-template-columns: 1fr auto;">
                    <input type="text" id="nuevoAtributoValue" placeholder="Nuevo Atributo..." class="form-input fs-12">
                    <button type="button" onclick="añadirAtributoUI()" class="btn-save w-auto p-0-12 h-40">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
                <input type="hidden" id="prodAtributos">
            </div>

            <!-- Sección de Números de Serie -->
            <div id="sectionNS" class="d-none mb-24">
                <div class="d-flex jc-space-between ai-center p-12 bg-blue-light br-8 border-2 border-blue">
                    <div class="d-flex ai-center gap-12">
                        <i class="fa-solid fa-barcode text-accent"></i>
                        <div>
                            <div class="fs-13 font-bold">Números de Serie</div>
                            <div id="nsList" class="fs-11 text-muted"></div>
                        </div>
                    </div>
                    <button type="button" onclick="abrirModalNS()" class="btn-filter fs-11 h-32 px-12">Gestionar</button>
                </div>
            </div>

            <div class="modal-footer pt-16 border-top">
                <button type="button" onclick="cerrarModalProducto()" class="btn-cancel">Cancelar</button>
                <div class="flex-1"></div>
                <button type="submit" id="btnGuardarProd" class="btn-save w-auto px-32">Guardar Producto</button>
            </div>
        </form>
    </div>
</div>

<script>
    // IVA general vigente, calculado en el backend (para que se actualice automáticamente si cambia en tipos_iva)
    const IVA_GENERAL_ACTUAL = <?php echo json_encode($avProductos['ivaGeneral'] ?? 21.00); ?>;
    const TIPOS_IVA = <?php echo json_encode($avProductos['tipos_iva'] ?? []); ?>;
    /** 
     * Lógica de gestión de productos (JS integrado por ahora)
     */

    function abrirModalProducto(producto = null) {
        const modal = document.getElementById('modalProducto');
        const title = document.getElementById('modalTitle');
        const form = document.getElementById('formProducto');

        // Limpiar errores previos
        document.querySelectorAll('.form-error').forEach(el => el.innerText = '');

        if (producto) {
            title.innerText = 'Editar Producto';
            document.getElementById('prodId').value = producto.id;
            document.getElementById('prodIcono').value = producto.icono;
            document.getElementById('prodCodigo').value = producto.codigo;
            document.getElementById('prodNombre').value = producto.nombre;
            document.getElementById('prodDesc').value = producto.descripcion || '';
            document.getElementById('prodCat').value = producto.categoria;

            // Seleccionar el tipo de IVA actual del producto en el select
            const selIva = document.getElementById('prodIvaTipo');
            if (selIva) {
                selIva.value = producto.codigo_iva || 'GENERAL';
            }
            document.getElementById('prodGarantia').value = producto.meses_garantia || '24';
            document.getElementById('prodPrecioCoste').value = producto.precio_coste || '0.00';
            document.getElementById('prodPrecioVenta').value = producto.precio || '0.00';
            document.getElementById('prodStock').value = producto.stock;
            document.getElementById('prodStockMin').value = producto.stock_minimo || '0';

            // Deshabilitar edición manual de coste y stock (gestionado por Entradas)
            document.getElementById('prodPrecioCoste').readOnly = true;
            document.getElementById('prodStock').readOnly = true;
            document.getElementById('prodPrecioCoste').style.opacity = '0.7';
            document.getElementById('prodStock').style.opacity = '0.7';

            // Cargar Variantes
            const container = document.getElementById('variantesContainer');
            container.innerHTML = '';
            if (producto.variantes) {
                try {
                    const vars = typeof producto.variantes === 'string' ? JSON.parse(producto.variantes) : producto.variantes;
                    for (const [l, v] of Object.entries(vars)) {
                        if (Array.isArray(v)) {
                            v.forEach(val => añadirVarianteUI(l, val));
                        } else {
                            añadirVarianteUI(l, v);
                        }
                    }
                    syncVariantes();
                } catch (e) {
                    console.error("Error parsing variantes", e);
                }
            }

            // Cargar Atributos
            const attrContainer = document.getElementById('atributosContainer');
            attrContainer.innerHTML = '';
            if (producto.atributos) {
                try {
                    const attrs = typeof producto.atributos === 'string' ? JSON.parse(producto.atributos) : producto.atributos;
                    if (Array.isArray(attrs)) {
                        attrs.forEach(v => añadirAtributoUI(v));
                    }
                    syncAtributos();
                } catch (e) {
                    console.error("Error parsing atributos", e);
                }
            }

            document.getElementById('sectionNS').classList.remove('d-none');
            cargarNS(producto.id);

            // Mostrar preview
            const preview = document.getElementById('imgPreview');
            if (producto.icono && producto.icono.startsWith('data:image')) {
                preview.innerHTML = `<img src="${producto.icono}" style="width:100%; height:100%; object-fit:contain; border-radius:8px;">`;
            } else {
                preview.innerHTML = `<span class="fs-24">${producto.icono || '<i class="fa-solid fa-image"></i>'}</span>`;
            }
        } else {
            title.innerText = 'Nuevo Producto';
            form.reset();
            document.getElementById('prodId').value = '';
            document.getElementById('imgPreview').innerHTML = '<i class="fa-solid fa-image"></i>';
            document.getElementById('sectionNS').classList.add('d-none');
            document.getElementById('variantesContainer').innerHTML = '';
            document.getElementById('prodVariantes').value = '';

            document.getElementById('atributosContainer').innerHTML = '';
            document.getElementById('prodAtributos').value = '';

            // Habilitar campos si es producto nuevo
            document.getElementById('prodPrecioCoste').readOnly = false;
            document.getElementById('prodStock').readOnly = false;
            document.getElementById('prodPrecioCoste').style.opacity = '1';
            document.getElementById('prodStock').style.opacity = '1';

            // Nuevo producto: usar IVA general vigente como valor por defecto
            const selIva = document.getElementById('prodIvaTipo');
            if (selIva) selIva.value = 'GENERAL';
        }

        modal.style.display = 'flex';
    }

    function añadirVarianteUI(label = null, value = null) {
        const l = label || document.getElementById('nuevaVarianteLabel').value.trim();
        const v = value || document.getElementById('nuevaVarianteValue').value.trim();

        if (!l || !v) return;

        const container = document.getElementById('variantesContainer');
        const pill = document.createElement('div');
        pill.className = 'cat-tab d-flex ai-center gap-8 p-4-12 fs-12';
        pill.dataset.label = l;
        pill.dataset.value = v;
        pill.innerHTML = `
        <span><strong>${l}:</strong> ${v}</span>
        <i class="fa-solid fa-xmark cursor-pointer opacity-70 hover-opacity-100" onclick="this.parentElement.remove(); syncVariantes();"></i>
    `;
        container.appendChild(pill);

        if (!label) { // Si viene del botón manual
            document.getElementById('nuevaVarianteLabel').value = '';
            document.getElementById('nuevaVarianteValue').value = '';
            syncVariantes();
        }
    }

    function syncVariantes() {
        const pills = document.querySelectorAll('#variantesContainer > div');
        const variantes = {};
        pills.forEach(p => {
            const label = p.dataset.label;
            const value = p.dataset.value;
            if (!variantes[label]) {
                variantes[label] = [];
            }
            if (!variantes[label].includes(value)) {
                variantes[label].push(value);
            }
        });
        document.getElementById('prodVariantes').value = Object.keys(variantes).length ? JSON.stringify(variantes) : '';
    }

    function añadirAtributoUI(value = null) {
        const v = value || document.getElementById('nuevoAtributoValue').value.trim();

        if (!v) return;

        const container = document.getElementById('atributosContainer');
        const pill = document.createElement('div');
        pill.className = 'cat-tab d-flex ai-center gap-8 p-4-12 fs-12 bg-accent-soft text-accent border border-accent';
        pill.dataset.value = v;
        pill.innerHTML = `
        <span>${v}</span>
        <i class="fa-solid fa-xmark cursor-pointer opacity-70 hover-opacity-100" onclick="this.parentElement.remove(); syncAtributos();"></i>
        `;
        container.appendChild(pill);

        if (!value) {
            document.getElementById('nuevoAtributoValue').value = '';
            syncAtributos();
        }
    }

    function syncAtributos() {
        const pills = document.querySelectorAll('#atributosContainer > div');
        const atributos = [];
        pills.forEach(p => {
            const value = p.dataset.value;
            if (!atributos.includes(value)) {
                atributos.push(value);
            }
        });
        document.getElementById('prodAtributos').value = atributos.length ? JSON.stringify(atributos) : '';
    }

    function cargarNS(idProd) {
        // TODO: Implementar la carga de números de serie
        // Por ahora, solo actualizamos el texto de ejemplo
        const nsList = document.getElementById('nsList');
        if (idProd) {
            nsList.innerText = `Cargando números de serie para producto ${idProd}...`;
            // Aquí iría la llamada a la API para obtener los NS
        } else {
            nsList.innerText = 'No hay números de serie asociados.';
        }
    }

    function abrirModalNS() {
        alert('Funcionalidad de gestión de números de serie no implementada aún.');
    }

    function previewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('prodIcono').value = e.target.result;
                document.getElementById('imgPreview').innerHTML = `<img src="${e.target.result}" style="width:100%; height:100%; object-fit:contain; border-radius:8px;">`;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function cerrarModalProducto() {
        document.getElementById('modalProducto').style.display = 'none';
        document.querySelectorAll('.form-error').forEach(el => el.innerText = '');
    }

    document.getElementById('formProducto').onsubmit = async (e) => {
        e.preventDefault();

        // Limpiar errores previos
        document.querySelectorAll('.form-error').forEach(el => el.innerText = '');

        const id = document.getElementById('prodId').value;

        // Resolver tipo de IVA seleccionado
        const selIva = document.getElementById('prodIvaTipo');
        const codigoIva = selIva ? selIva.value : 'GENERAL';
        const tipoIva = TIPOS_IVA.find(t => t.codigo === codigoIva);
        const ivaValor = tipoIva ? tipoIva.porcentaje : IVA_GENERAL_ACTUAL;

        // ── Objeto datos completo con todos los campos requeridos ──
        const datos = {
            accion: id ? 'editar' : 'añadir',
            id: id,
            icono: document.getElementById('prodIcono').value,
            referencia: document.getElementById('prodCodigo').value,
            nombre: document.getElementById('prodNombre').value,
            descripcion: document.getElementById('prodDesc').value,
            categoria: document.getElementById('prodCat').value,
            iva: ivaValor,
            meses_garantia: document.getElementById('prodGarantia').value,
            precio_coste: document.getElementById('prodPrecioCoste').value,
            precio_venta: document.getElementById('prodPrecioVenta').value,
            stock_actual: document.getElementById('prodStock').value,
            stock_minimo: document.getElementById('prodStockMin').value,
            requiere_serial: 0, // ── CORRECCIÓN: campo obligatorio para ProductoPDO
            variantes: document.getElementById('prodVariantes').value || null, // ── CORRECCIÓN: campo obligatorio para ProductoPDO
            atributos: document.getElementById('prodAtributos').value || null,
            codigo_iva: codigoIva,
        };

        try {
            // ── CORRECCIÓN: añadir Content-Type para que PHP pueda leer php://input ──
            const resp = await fetch('api/gestionProducto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(datos)
            });
            const r = await resp.json();

            if (r.ok) {
                location.reload();
            } else {
                if (r.aErrores) {
                    const map = {
                        'referencia': 'err-referencia',
                        'nombre': 'err-nombre',
                        'precio_coste': 'err-precio_coste',
                        'precio_venta': 'err-precio_venta',
                        'iva': 'err-iva',
                        'stock_actual': 'err-stock_actual',
                        'stock_minimo': 'err-stock_minimo',
                        'meses_garantia': 'err-meses_garantia'
                    };
                    for (const [key, msg] of Object.entries(r.aErrores)) {
                        const errId = map[key] || ('err-' + key);
                        const errEl = document.getElementById(errId);
                        if (errEl && msg) errEl.innerText = msg;
                    }
                } else {
                    alert('Error: ' + r.error);
                }
            }
        } catch (err) {
            console.error(err);
            alert('Error de conexión con el servidor');
        }
    };

    async function toggleEstadoProducto(id) {
        try {
            const resp = await fetch('api/gestionProducto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    accion: 'baja',
                    id: id
                })
            });
            const r = await resp.json();
            if (r.ok) {
                const activo = r.activo; // true = activado, false = dado de baja

                // Encontrar la fila por el botón
                const btn = document.querySelector(`button[onclick="toggleEstadoProducto(${id})"]`);
                if (!btn) return;
                const row = btn.closest('tr');

                // Actualizar botón
                btn.className = `btn-icon ${activo ? 'text-red' : 'text-green'}`;
                btn.title = activo ? 'Dar de baja' : 'Activar';
                btn.querySelector('i').className = `fa-solid fa-${activo ? 'arrow-down' : 'arrow-up'}`;

                // Actualizar data-activo (para los filtros)
                row.dataset.activo = activo ? '1' : '0';

                // Actualizar pastilla de estado
                const pill = row.querySelector('.status-pill');
                if (pill) {
                    pill.className = activo ? 'status-pill status-active' : 'status-pill status-inactive';
                    pill.innerHTML = activo ?
                        '<i class="fa-solid fa-circle-check"></i> Activo' :
                        '<i class="fa-solid fa-circle-xmark"></i> Inactivo';
                }

                showNotification(
                    `<i class="fa-solid fa-circle-check"></i> Producto ${activo ? 'activado' : 'dado de baja'}.`,
                    activo ? 'success' : 'info'
                );
            } else {
                showNotification("<i class='fa-solid fa-circle-xmark'></i> " + (r.error || 'No se pudo cambiar estado'), 'error');
            }
        } catch (err) {
            console.error(err);
            showNotification("<i class='fa-solid fa-circle-xmark'></i> Error al cambiar estado", 'error');
        }
    }

    async function eliminarProducto(id, nombre) {
        if (!confirm(`¿Seguro que deseas ELIMINAR el producto "${nombre}"?\n\nEsta acción no se puede deshacer.`)) return;
        try {
            const resp = await fetch('api/gestionProducto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    accion: 'eliminar',
                    id: id
                })
            });
            const r = await resp.json();
            if (r.ok) {
                // Eliminar la fila del DOM sin recargar
                const allBtns = document.querySelectorAll('.product-row td:last-child button');
                let targetRow = null;
                document.querySelectorAll('.product-row').forEach(row => {
                    const btn = row.querySelector(`button[onclick*="eliminarProducto(${id},"]`);
                    if (btn) targetRow = row;
                });
                if (targetRow) {
                    targetRow.style.transition = 'opacity 0.3s, transform 0.3s';
                    targetRow.style.opacity = '0';
                    targetRow.style.transform = 'translateX(12px)';
                    setTimeout(() => targetRow.remove(), 300);
                }
                showNotification('<i class="fa-solid fa-circle-check"></i> Producto eliminado.', 'success');
            } else {
                showNotification('<i class="fa-solid fa-circle-xmark"></i> Error: ' + (r.error || 'No se pudo eliminar'), 'error');
            }
        } catch (err) {
            console.error(err);
            showNotification('<i class="fa-solid fa-circle-xmark"></i> Error de conexión.', 'error');
        }
    }

    // Lógica de filtrado y ordenación
    function applyFilters() {
        const term = document.getElementById('prodSearch').value.toLowerCase().trim();
        const cat = document.getElementById('filterCat').value;
        const estado = document.getElementById('filterEstado').value;
        const priceMin = parseFloat(document.getElementById('filterPriceMin').value) || 0;
        const priceMax = parseFloat(document.getElementById('filterPriceMax').value) || 999999;

        const rows = document.querySelectorAll('.product-row');
        let hasResults = false;

        rows.forEach(row => {
            const nombre = row.dataset.nombre;
            const codigo = row.dataset.codigo;
            const categoria = row.dataset.categoria;
            const activo = row.dataset.activo;
            const precio = parseFloat(row.dataset.precio);

            const matchesTerm = !term || nombre.includes(term) || codigo.includes(term);
            const matchesCat = cat === 'all' || categoria === cat;
            const matchesEstado = estado === 'all' || activo === estado;
            const matchesPrice = precio >= priceMin && precio <= priceMax;

            if (matchesTerm && matchesCat && matchesEstado && matchesPrice) {
                row.style.display = '';
                hasResults = true;
            } else {
                row.style.display = 'none';
            }
        });

        document.getElementById('noResults').classList.toggle('d-none', hasResults);
    }

    document.getElementById('filterPriceMin').addEventListener('input', applyFilters);
    document.getElementById('filterPriceMax').addEventListener('input', applyFilters);

    function applySort() {
        const order = document.getElementById('sortOrder').value;
        if (order === 'none') return;

        const tbody = document.getElementById('productsTableBody');
        const rows = Array.from(tbody.querySelectorAll('.product-row'));

        rows.sort((a, b) => {
            let valA, valB;
            if (order.startsWith('price')) {
                valA = parseFloat(a.dataset.precio);
                valB = parseFloat(b.dataset.precio);
            } else if (order.startsWith('stock')) {
                valA = parseInt(a.dataset.stock);
                valB = parseInt(b.dataset.stock);
            }

            return order.endsWith('asc') ? valA - valB : valB - valA;
        });

        // Devolvemos el noResults al final
        const noRes = document.getElementById('noResults');
        rows.forEach(row => tbody.appendChild(row));
        tbody.appendChild(noRes);
    }

    // NS Logic
    function abrirModalNS() {
        document.getElementById('modalNS').style.display = 'flex';
    }

    function cerrarModalNS() {
        document.getElementById('modalNS').style.display = 'none';
    }

    async function cargarNS(idProd) {
        const listDiv = document.getElementById('nsList');
        const tableBody = document.getElementById('listaNSTablaBody');
        listDiv.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Cargando...';
        tableBody.innerHTML = '';

        try {
            // ── CORRECCIÓN: añadir Content-Type ──
            const resp = await fetch('api/gestionNS.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    accion: 'listar',
                    id_producto: idProd
                })
            });
            const r = await resp.json();
            if (r.ok) {
                if (r.lista.length === 0) {
                    listDiv.innerText = 'Sin números de serie registrados.';
                    tableBody.innerHTML = '<tr><td colspan="3" class="text-center p-16 text-muted">No hay números de serie para este producto.</td></tr>';
                } else {
                    listDiv.innerText = r.lista.length + ' unidades con S/N (Ver detalles en Gestionar)';
                    r.lista.forEach(item => {
                        const row = document.createElement('tr');
                        const color = item.estado === 'disponible' ? 'text-success' : 'text-danger';
                        row.innerHTML = `
                        <td class="p-8">${item.numero_serie}</td>
                        <td class="text-center p-8"><span class="${color} font-bold">${item.estado}</span></td>
                        <td class="text-center p-8">
                            <button onclick="eliminarNS(${item.id})" class="btn-icon p-4" title="Eliminar"><i class="fa-solid fa-trash text-danger"></i></button>
                        </td>
                    `;
                        tableBody.appendChild(row);
                    });
                }
            }
        } catch (err) {
            console.error(err);
        }
    }

    async function añadirNS() {
        const idProd = document.getElementById('prodId').value;
        const ns = document.getElementById('nuevoNS').value;
        if (!ns) return;

        try {
            // ── CORRECCIÓN: añadir Content-Type ──
            const resp = await fetch('api/gestionNS.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    accion: 'añadir',
                    id_producto: idProd,
                    numero_serie: ns
                })
            });
            const r = await resp.json();
            if (r.ok) {
                document.getElementById('nuevoNS').value = '';
                cargarNS(idProd);
            } else {
                alert('Error: ' + (r.error || 'No se pudo añadir'));
            }
        } catch (err) {
            console.error(err);
        }
    }

    async function eliminarNS(id) {
        if (!confirm('¿Seguro que deseas eliminar este número de serie?')) return;
        try {
            // ── CORRECCIÓN: añadir Content-Type ──
            const resp = await fetch('api/gestionNS.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    accion: 'eliminar',
                    id: id
                })
            });
            const r = await resp.json();
            if (r.ok) {
                cargarNS(document.getElementById('prodId').value);
            }
        } catch (err) {
            console.error(err);
        }
    }

    document.getElementById('prodSearch').addEventListener('input', applyFilters);
    document.getElementById('filterCat').addEventListener('change', applyFilters);
    document.getElementById('filterEstado').addEventListener('change', applyFilters);
    document.getElementById('sortOrder').addEventListener('change', applySort);

    // ── IMPORT / EXPORT ────────────────────────────────────────────────────────
    function toggleExportDropdown() {
        document.getElementById('exportDropdown').classList.toggle('open');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const wrap = document.getElementById('exportDropdownWrap');
        if (wrap && !wrap.contains(e.target)) {
            document.getElementById('exportDropdown').classList.remove('open');
        }
    });

    async function importarProductos(input) {
        if (!input.files || !input.files[0]) return;
        const file = input.files[0];
        const ext = file.name.split('.').pop().toLowerCase();

        if (!['csv', 'json'].includes(ext)) {
            showNotification('<i class="fa-solid fa-circle-xmark"></i> Formato no soportado. Usa CSV o JSON.', 'error');
            input.value = '';
            return;
        }

        const formData = new FormData();
        formData.append('file', file);

        showNotification('<i class="fa-solid fa-spinner fa-spin"></i> Importando productos...', 'info');

        try {
            const resp = await fetch('api/importarProductos.php', {
                method: 'POST',
                body: formData
            });
            const r = await resp.json();
            if (r.ok) {
                const {
                    creados,
                    actualizados,
                    errores
                } = r.stats;
                showNotification(
                    `<i class="fa-solid fa-circle-check"></i> Importación completada: <strong>${creados} creados</strong>, <strong>${actualizados} actualizados</strong>${errores > 0 ? `, <span class="text-red">${errores} errores</span>` : ''}`,
                    'success'
                );
                setTimeout(() => location.reload(), 2500);
            } else {
                showNotification('<i class="fa-solid fa-circle-xmark"></i> Error: ' + (r.error || 'Desconocido'), 'error');
            }
        } catch (err) {
            showNotification('<i class="fa-solid fa-circle-xmark"></i> Error de conexión.', 'error');
        }

        input.value = '';
    }

    // Mini-toast local para esta página (puede no tener el global de main.js)
    function showNotification(html, type = 'info') {
        let notif = document.getElementById('ie-notif');
        if (!notif) {
            notif = document.createElement('div');
            notif.id = 'ie-notif';
            document.body.appendChild(notif);
        }
        const colors = {
            info: '#1a2fbf',
            success: '#0f8060',
            error: '#c0392b'
        };
        notif.style.cssText = `position:fixed;bottom:24px;right:24px;background:${colors[type]};color:white;padding:14px 20px;border-radius:12px;font-size:13px;font-weight:600;z-index:99999;box-shadow:0 4px 20px rgba(0,0,0,0.2);transition:opacity 0.3s;max-width:400px;line-height:1.4;`;
        notif.innerHTML = html;
        notif.style.opacity = '1';
        clearTimeout(notif._timeout);
        notif._timeout = setTimeout(() => {
            notif.style.opacity = '0';
        }, 3500);
    }

    // --- GESTIÓN DE CATEGORÍAS ---
    function abrirModalGestionCategorias() {
        document.getElementById('modalCategorias').style.display = 'flex';
    }

    function cerrarModalCategorias() {
        document.getElementById('modalCategorias').style.display = 'none';
        location.reload(); // Recargar para ver cambios en los selects
    }

    async function añadirCategoria() {
        const nombre = document.getElementById('newCatNombre').value.trim();
        const codigo = document.getElementById('newCatCodigo').value.trim();

        if (!nombre || !codigo) {
            alert('Nombre y código son obligatorios');
            return;
        }

        try {
            const resp = await fetch('api/gestionCategoria.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    accion: 'añadir',
                    nombre,
                    codigo
                })
            });
            const r = await resp.json();
            if (r.ok) {
                document.getElementById('newCatNombre').value = '';
                document.getElementById('newCatCodigo').value = '';
                location.reload();
            } else {
                alert('Error: ' + (r.error || 'No se pudo añadir'));
            }
        } catch (e) {
            console.error(e);
        }
    }

    async function eliminarCategoria(id, nombre) {
        if (!confirm(`¿Seguro que deseas eliminar la categoría "${nombre}"? \nLos productos asignados a ella no se borrarán, pero su categoría dejará de estar disponible.`)) return;

        try {
            const resp = await fetch('api/gestionCategoria.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    accion: 'eliminar',
                    id
                })
            });
            const r = await resp.json();
            if (r.ok) {
                location.reload();
            } else {
                alert('Error: ' + (r.error || 'No se pudo eliminar'));
            }
        } catch (e) {
            console.error(e);
        }
    }
</script>

<!-- MODAL GESTIÓN CATEGORÍAS -->
<div id="modalCategorias" class="modal-overlay-bg" style="z-index: 1200;">
    <div class="modal-content" style="max-width: 500px; padding: 0; overflow: hidden;">
        <div class="modal-header p-20 bg-surface2 border-bottom">
            <h2 class="m-0 fs-18">Gestionar Categorías</h2>
            <button onclick="cerrarModalCategorias()" class="btn-close-modal">&times;</button>
        </div>
        <div class="p-24">
            <div class="d-grid grid-1-1 gap-8 mb-20 p-16 bg-surface2 br-12 border-2">
                <div class="form-group mb-0">
                    <label class="form-label fs-11">Nombre</label>
                    <input type="text" id="newCatNombre" placeholder="Ej: Audio" class="form-input h-40">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11">Código (valor interno)</label>
                    <input type="text" id="newCatCodigo" placeholder="ej: audio" class="form-input h-40">
                </div>
                <button onclick="añadirCategoria()" class="btn-save w-full mt-12 grid-col-span-2">
                    <i class="fa-solid fa-plus"></i> Añadir Categoría
                </button>
            </div>

            <div class="table-container" style="max-height: 300px; overflow-y: auto;">
                <table class="w-100 fs-13">
                    <thead>
                        <tr>
                            <th class="text-left py-12 pl-12">Categoría</th>
                            <th class="text-left py-12">Código</th>
                            <th class="text-center py-12">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($avProductos['categorias'] as $c): ?>
                            <tr class="border-bottom">
                                <td class="py-12 pl-12 font-bold"><?php echo htmlspecialchars($c['nombre']); ?></td>
                                <td class="py-12 text-muted"><?php echo htmlspecialchars($c['codigo']); ?></td>
                                <td class="py-12 text-center">
                                    <button onclick="eliminarCategoria(<?php echo $c['id']; ?>, '<?php echo addslashes(htmlspecialchars($c['nombre'])); ?>')" class="btn-icon text-red">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>