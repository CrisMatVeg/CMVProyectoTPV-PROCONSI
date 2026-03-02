<?php // Vista de Productos 
?>
<div class="main-full p-24">

    <!-- CABECERA DE SECCIÓN -->
    <div class="section-header container-wider">
        <div class="section-title">
            <h1>Gestión de Productos</h1>
            <p>Administra el catálogo de productos, precios y categorías.</p>
        </div>
        <div class="d-flex gap-12">
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
        <select id="filterCat" class="filter-input p-10 br-8">
            <option value="all">Todas las categorías</option>
            <option value="audio">Audio</option>
            <option value="movil">Móvil</option>
            <option value="gaming">Gaming</option>
            <option value="informatica">Informática</option>
            <option value="cables">Cables</option>
            <option value="foto">Foto</option>
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
                                <option value="audio">Audio</option>
                                <option value="movil">Móvil</option>
                                <option value="gaming">Gaming</option>
                                <option value="informatica">Informática</option>
                                <option value="cables">Cables</option>
                                <option value="foto">Foto</option>
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
                    <label class="form-label fs-11 tt-uppercase">Coste (€)</label>
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
                    <label class="form-label fs-11 tt-uppercase">Stock Actual</label>
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

            // Cargar Variantes
            const container = document.getElementById('variantesContainer');
            container.innerHTML = '';
            if (producto.variantes) {
                try {
                    const vars = typeof producto.variantes === 'string' ? JSON.parse(producto.variantes) : producto.variantes;
                    for (const [l, v] of Object.entries(vars)) {
                        añadirVarianteUI(l, v);
                    }
                    syncVariantes();
                } catch (e) {
                    console.error("Error parsing variantes", e);
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
            variantes[p.dataset.label] = p.dataset.value;
        });
        document.getElementById('prodVariantes').value = JSON.stringify(variantes);
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
            // ── CORRECCIÓN: añadir Content-Type ──
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
                showToast('✅ Estado actualizado');
                location.reload();
            } else {
                showToast("<i class='fa-solid fa-circle-xmark'></i> " + (r.error || 'No se pudo cambiar estado'));
            }
        } catch (err) {
            console.error(err);
            showToast("<i class='fa-solid fa-circle-xmark'></i> Error al cambiar estado");
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
</script>