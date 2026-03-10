<?php // Vista de Productos 
?>
<div class="main-full p-24">

    <div class="section-header container-wider">
        <div class="section-title">
            <h1>Catálogo</h1>
            <p>Administra tus productos estándar y packs de venta combinados.</p>
        </div>
        <div class="d-flex gap-12 ai-center flex-wrap">
            <a href="index.php?irDashboard=1" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>

            <div class="tabs-nav mr-12">
                <button class="tab-btn active" onclick="switchTab('productos')" id="btnTabProductos">
                    <i class="fa-solid fa-box-open"></i>Productos
                </button>
                <button class="tab-btn" onclick="switchTab('packs')" id="btnTabPacks">
                    <i class="fa-solid fa-boxes-stacked"></i>Packs
                </button>
            </div>

            <div class="d-flex gap-8 ai-center bg-surface2 p-4 br-12 border">
                <!-- Botón Exportar -->
                <div class="ie-dropdown-wrap" id="exportDropdownWrap">
                    <button class="btn-filter" onclick="toggleExportDropdown()" style="border:none; background:transparent;">
                        <i class="fa-solid fa-file-export"></i> Exportar
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

                <div class="vr mx-4" style="height: 20px; width: 1px; background: var(--border); opacity: 0.5;"></div>

                <button class="btn-filter" onclick="document.getElementById('importFileInput').click()" style="border:none; background:transparent;">
                    <i class="fa-solid fa-file-import"></i> Importar
                </button>
                <input type="file" id="importFileInput" accept=".csv,.json" class="d-none" onchange="importarProductos(this)">

                <div class="vr mx-4" style="height: 20px; width: 1px; background: var(--border); opacity: 0.5;"></div>

                <button onclick="abrirModalGestionCategorias()" class="btn-filter" style="border:none; background:transparent;">
                    <i class="fa-solid fa-tags"></i> Categorías
                </button>

                <div class="vr mx-4" style="height: 20px; width: 1px; background: var(--border); opacity: 0.5;"></div>

                <button onclick="generarPedidoAutomatico()" class="btn-filter" id="btnPedidoAuto" style="border:none; background:transparent; color: var(--accent);">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Pedido Auto
                </button>
            </div>

            <div class="flex-1"></div>

            <button onclick="abrirModalProducto()" class="btn-save h-44 px-20 shadow-sm" id="btnNuevoProducto">
                <i class="fa-solid fa-plus"></i> Nuevo Producto
            </button>
            <button onclick="abrirModalPack()" class="btn-save h-44 px-20 shadow-sm d-none" id="btnNuevoPack">
                <i class="fa-solid fa-plus"></i> Nuevo Pack
            </button>
        </div>
    </div>

    <!-- PANEL DE FILTROS -->
    <div class="filters-panel container-wider">
        <div class="search-bar-wrap flex-1">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" id="prodSearch" placeholder="Buscar por nombre o SKU..." class="search-input">
        </div>
        <div class="d-flex gap-8" id="filtersPrecios">
            <input type="number" id="filterPriceMin" placeholder="Precio Min" class="form-input w-100 fs-12">
            <input type="number" id="filterPriceMax" placeholder="Precio Max" class="form-input w-100 fs-12">
        </div>
        <select id="filterCat" class="filter-input p-10 br-8" onchange="applyFilters()">
            <option value="all">Todas las categorías</option>
            <?php foreach ($avProductos['categorias'] as $c): ?>
                <option value="<?php echo htmlspecialchars($c['codigo']); ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
            <?php endforeach; ?>
        </select>
        <select id="filterEstado" class="filter-input p-10 br-8" onchange="applyFilters()">
            <option value="all">Todos los estados</option>
            <option value="1">Activos</option>
            <option value="0">Inactivos</option>
            <option value="bajo_stock">Bajo Stock</option>
        </select>
    </div>

    <!-- TABLA DE PRODUCTOS (PESTAÑA 1) -->
    <div class="table-container container-wider tab-content active" id="tabContentProductos">
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
                <?php foreach ($avProductos['productos'] as $p): if (!empty($p['es_pack'])) continue; ?>
                    <tr class="product-row row-tipo-producto"
                        data-nombre="<?php echo strtolower(htmlspecialchars($p['nombre'])); ?>"
                        data-codigo="<?php echo strtolower(htmlspecialchars($p['codigo'])); ?>"
                        data-categoria="<?php echo $p['categoria']; ?>"
                        data-precio="<?php echo $p['precio']; ?>"
                        data-stock="<?php echo $p['stock']; ?>"
                        data-stock-minimo="<?php echo $p['stock_minimo']; ?>"
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
                        <td class="text-right font-bold font-mono">
                            <?php
                            $stock = (int)$p['stock'];
                            $stockMin = (int)$p['stock_minimo'];
                            $esCritico = ($stock <= $stockMin);
                            ?>
                            <div class="d-flex flex-column ai-end">
                                <span class="<?php echo $esCritico ? 'text-red bg-red-soft px-4 br-4' : ''; ?>">
                                    <?php echo $stock; ?>
                                </span>
                                <?php if ($esCritico): ?>
                                    <span class="fs-9 tt-uppercase text-red font-bold">Mín: <?php echo $stockMin; ?></span>
                                <?php endif; ?>
                            </div>
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
                                <button onclick="abrirModalHistorial(<?php echo $p['id']; ?>, '<?php echo addslashes(htmlspecialchars($p['nombre'])); ?>')" title="Historial Stock" class="btn-icon text-accent">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                </button>
                                <button onclick='abrirModalProducto(<?php echo json_encode($p); ?>)' title="Editar" class="btn-icon">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button onclick="toggleEstadoProducto(<?php echo $p['id']; ?>)" title="<?php echo $p['activo'] ? 'Dar de baja' : 'Activar'; ?>" class="btn-icon <?php echo $p['activo'] ? 'text-red' : 'text-green'; ?>">
                                    <i class="fa-solid fa-<?php echo $p['activo'] ? 'arrow-down' : 'arrow-up'; ?>"></i>
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

    <!-- TABLA DE PACKS (PESTAÑA 2) -->
    <div class="table-container container-wider tab-content d-none" id="tabContentPacks">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-60 pl-20">ID</th>
                    <th class="w-80 text-center">Icono</th>
                    <th>Nombre del Pack</th>
                    <th>Referencia</th>
                    <th>Componentes</th>
                    <th class="text-right">Precio Venta</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center pr-20">Acciones</th>
                </tr>
            </thead>
            <tbody id="packsTableBody">
                <?php foreach ($avProductos['productos'] as $p): if (empty($p['es_pack'])) continue; ?>
                    <tr class="product-row row-tipo-pack"
                        data-nombre="<?php echo strtolower(htmlspecialchars($p['nombre'])); ?>"
                        data-codigo="<?php echo strtolower(htmlspecialchars($p['codigo'])); ?>"
                        data-categoria="<?php echo $p['categoria']; ?>"
                        data-precio="<?php echo $p['precio']; ?>"
                        data-activo="<?php echo $p['activo'] ? '1' : '0'; ?>">
                        <td class="font-mono text-muted"><?php echo $p['id']; ?></td>
                        <td class="text-center">
                            <?php if (strpos($p['icono'], 'data:image') === 0): ?>
                                <img src="<?php echo $p['icono']; ?>" class="prod-img-fixed" alt="Icono">
                            <?php else: ?>
                                <span class="fs-24"><?php echo $p['icono']; ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="font-bold">
                            <?php echo htmlspecialchars($p['nombre']); ?>
                            <span class="ml-8 px-6 py-2 br-4 fs-10 bg-accent-soft text-accent border border-accent">PACK</span>
                        </td>
                        <td class="text-muted"><?php echo htmlspecialchars($p['codigo']); ?></td>
                        <td class="fs-12 text-muted">
                            <?php
                            if (!empty($p['componentes_pack']) && is_array($p['componentes_pack'])) {
                                $comps = [];
                                foreach ($p['componentes_pack'] as $c) {
                                    $comps[] = "{$c['cantidad']}x " . htmlspecialchars($c['nombre']);
                                }
                                echo implode('<br>', $comps);
                            } else {
                                echo '<em>Sin componentes</em>';
                            }
                            ?>
                        </td>
                        <td class="text-right font-bold font-mono text-accent">
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
                                <button onclick='abrirModalPack(<?php echo json_encode($p); ?>)' title="Editar Pack" class="btn-icon">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button onclick="toggleEstadoProducto(<?php echo $p['id']; ?>)" title="<?php echo $p['activo'] ? 'Dar de baja' : 'Activar'; ?>" class="btn-icon <?php echo $p['activo'] ? 'text-red' : 'text-green'; ?>">
                                    <i class="fa-solid fa-<?php echo $p['activo'] ? 'arrow-down' : 'arrow-up'; ?>"></i>
                                </button>
                                <button onclick="eliminarProducto(<?php echo $p['id']; ?>, '<?php echo addslashes(htmlspecialchars($p['nombre'])); ?>')" title="Eliminar Pack" class="btn-icon text-red" style="opacity:0.7;">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr id="noResultsPacks" class="d-none">
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="fa-solid fa-boxes-stacked"></i>
                            No se han encontrado packs con los filtros seleccionados.
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>



<script>

</script>


<!-- MODAL AÑADIR/EDITAR PRODUCTO -->
<div id="modalProducto" class="modal-overlay-bg">
    <div class="modal-content" style="max-width: 800px; padding: 0; overflow: hidden;">
        <div class="modal-header">
            <h2 id="modalTitle">Nuevo Producto</h2>
            <button onclick="cerrarModalProducto()" class="btn-close-modal">&times;</button>
        </div>
        <form id="formProducto" class="modal-body">
            <input type="hidden" id="prodId">

            <div class="d-grid grid-1-3 gap-24 mb-24">
                <!-- Columna Izquierda: Imagen y Meta -->
                <div class="d-flex flex-column gap-16">
                    <div class="form-group mb-0">
                        <label class="form-label">Imagen del Producto</label>
                        <div class="d-flex flex-column ai-center gap-12 p-16 bg-surface2 br-12 border-2">
                            <div id="imgPreview" class="prod-img-preview m-0" style="width: 120px; height: 120px;">
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

            <div class="d-grid gap-16 mb-24 p-16 bg-surface2 br-12 border-2" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase">Proveedor</label>
                    <select id="prodProveedor" class="form-input" onchange="seleccionarProveedor(this)">
                        <option value="">-- Sin Proveedor --</option>
                        <?php foreach ($avProductos['proveedores'] as $p): ?>
                            <option value="<?php echo $p['id']; ?>" data-re="<?php echo $p['aplica_re']; ?>">
                                <?php echo htmlspecialchars($p['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase">Precio Proveedor (€)</label>
                    <input type="text" id="prodPrecioProveedor" placeholder="0.00" class="form-input text-right font-mono" oninput="calcularCosteAutomatico()">
                    <span class="form-error" id="err-precio_proveedor"></span>
                </div>
            </div>

            <div class="d-grid gap-16 mb-24 p-16 bg-surface2 br-12 border-2" style="grid-template-columns: 1fr;">
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase">Tipo IVA</label>
                    <select id="prodIvaTipo" class="form-input" onchange="calcularCosteAutomatico()">
                        <?php foreach ($avProductos['tipos_iva'] as $t): ?>
                            <option value="<?php echo htmlspecialchars($t['codigo']); ?>"
                                data-porcentaje="<?php echo $t['porcentaje']; ?>"
                                data-re="<?php echo $t['recargo_equivalencia'] ?? 0; ?>">
                                <?php echo htmlspecialchars($t['nombre'] . ' (' . (float)$t['porcentaje'] . '%)'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>


            <div class="d-grid grid-2 gap-16 mb-24">
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase d-flex ai-center gap-4">
                        PVP Coste (Calculado €) <i class="fa-solid fa-calculator text-muted"></i>
                    </label>
                    <input type="text" id="prodPrecioCoste" placeholder="0.00" class="form-input text-right font-mono bg-disabled" readonly>
                    <span class="form-error" id="err-precio_coste"></span>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase">PVP Venta (€)</label>
                    <input type="text" id="prodPrecioVenta" placeholder="0.00" class="form-input text-right font-bold font-mono text-accent">
                    <span class="form-error" id="err-precio_venta"></span>
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

            <!-- (Eliminado: Componentes del Pack, ahora están en el modal exclusivo modalPack) -->

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

            <!-- Gestión de Variantes Físicas (Stock individual) -->
            <div id="sectionVariantesFisicas" class="p-16 border-2 br-12 mb-24 d-none">
                <label class="form-label mb-12 d-flex ai-center gap-8">
                    <i class="fa-solid fa-boxes-stacked text-muted"></i> Gestión de Variantes Físicas (Stock Individual)
                </label>
                <div class="table-container" style="max-height: 250px; overflow-y: auto; background: var(--bg-body);">
                    <table class="w-100 fs-12">
                        <thead>
                            <tr style="background: var(--surface2);">
                                <th class="p-8 text-left">Variante</th>
                                <th class="p-8 text-left">SKU</th>
                                <th class="p-8 text-center" style="width: 80px;">Stock</th>
                                <th class="p-8 text-right" style="width: 100px;">Precio (€)</th>
                                <th class="p-8 text-center" style="width: 60px;">Activa</th>
                                <th class="p-8 text-center" style="width: 40px;"></th>
                            </tr>
                        </thead>
                        <tbody id="listaVariantesBody">
                            <!-- JS filler -->
                        </tbody>
                    </table>
                </div>
            </div>


            <div class="modal-footer">
                <button type="button" onclick="cerrarModalProducto()" class="btn-cancel">Cancelar</button>
                <div class="flex-1"></div>
                <button type="submit" id="btnGuardarProd" class="btn-save w-auto px-32">Guardar Producto</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL AÑADIR/EDITAR PACK -->
<div id="modalPack" class="modal-overlay-bg d-none">
    <div class="modal-content" style="max-width: 1000px; padding: 0; overflow: hidden;">
        <div class="modal-header">
            <h2 id="modalPackTitle">Nuevo Pack de Venta</h2>
            <button type="button" onclick="cerrarModalPack()" class="btn-close-modal">&times;</button>
        </div>
        <form id="formPack" class="modal-body">
            <input type="hidden" id="packId">

            <div class="d-grid grid-1-3 gap-24 mb-24">
                <!-- Columna Izquierda: Imagen y Meta -->
                <div class="d-flex flex-column gap-16">
                    <div class="form-group mb-0">
                        <label class="form-label">Imagen del Pack</label>
                        <div class="d-flex flex-column ai-center gap-12 p-16 bg-surface2 br-12 border-2">
                            <div id="imgPreviewPack" class="prod-img-preview m-0" style="width: 120px; height: 120px;">
                                <i class="fa-solid fa-boxes-stacked fs-32 opacity-20"></i>
                            </div>
                            <input type="file" id="packFile" accept="image/*" class="d-none" onchange="previewImagePack(this)">
                            <button type="button" onclick="document.getElementById('packFile').click()" class="btn-filter w-auto h-auto p-10-16 fs-12 gap-8">
                                <i class="fa-solid fa-upload"></i> Subir
                            </button>
                            <input type="hidden" id="packIcono">
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Datos Principales -->
                <div class="d-flex flex-column gap-16">
                    <div class="form-group mb-0">
                        <label class="form-label">Nombre del Pack</label>
                        <input type="text" id="packNombre" placeholder="Ej: Pack Ahorro Invierno" class="form-input" required>
                    </div>

                    <div class="d-grid grid-2 gap-16">
                        <div class="form-group mb-0">
                            <label class="form-label">Referencia (Opcional)</label>
                            <input type="text" id="packCodigo" placeholder="PACK-INV-001" class="form-input font-mono">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Categoría</label>
                            <select id="packCat" class="form-input">
                                <?php foreach ($avProductos['categorias'] as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c['codigo']); ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="d-grid grid-2 gap-16">
                        <div class="form-group mb-0">
                            <label class="form-label fs-11 tt-uppercase">Precio Venta Pack (€)</label>
                            <input type="text" id="packPrecioVenta" placeholder="0.00" class="form-input text-right font-bold font-mono text-accent" required>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label fs-11 tt-uppercase">IVA (%)</label>
                            <select id="packIvaTipo" class="form-input">
                                <?php foreach ($avProductos['tipos_iva'] as $t): ?>
                                    <option value="<?php echo htmlspecialchars($t['codigo']); ?>">
                                        <?php echo htmlspecialchars($t['codigo'] . ' - ' . $t['porcentaje'] . '%'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Componentes del Pack -->
            <div class="p-20 border-2 border-accent br-12 mb-24 bg-surface1 col-span-1-3 pack-components-section" style="width: 100%; box-sizing: border-box; grid-column: 1 / -1;">

                <label class="form-label mb-12 d-flex ai-center gap-8 text-accent font-bold">
                    <i class="fa-solid fa-boxes-packing"></i> Componentes Físicos del Pack
                </label>
                <div class="fs-12 text-muted mb-24">
                    Añade los productos que componen este pack. Al vender el pack, el TPV descontará automáticamente el stock especificado de cada uno de los productos que lo conforman.
                </div>

                <div class="d-flex mb-24" style="width: 100%;">
                    <div class="pack-search-container" style="width: 100%; position: relative;">
                        <i class="fa-solid fa-magnifying-glass pack-search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); z-index: 10;"></i>
                        <input type="text" id="packSearchInput" class="form-input border-accent pl-40" style="width: 100%; padding-left: 40px !important;" placeholder="Buscar producto por nombre o ref..." autocomplete="off" oninput="buscarProductoParaPack(this.value)">
                        <div id="packSearchResults" class="search-results-dropdown" style="display:none; position: absolute; width: 100%; left: 0; top: 100%; z-index: 1000; background: var(--surface); border: 1px solid var(--border); box-shadow: var(--shadow-lg); border-radius: 8px; max-height: 300px; overflow-y: auto;"></div>
                    </div>
                </div>


                <div class="table-container mt-12" style="max-height: 250px; overflow-y: auto; background: var(--bg-body);">
                    <table class="full-width fs-12 pack-components-table">
                        <thead>
                            <tr style="background: var(--surface2);">
                                <th class="p-8 text-left">SKU</th>
                                <th class="p-8 text-left">Producto</th>
                                <th class="p-8 text-center" style="width: 80px;">Cant.</th>
                                <th class="p-8 text-center" style="width: 40px;"></th>
                            </tr>
                        </thead>
                        <tbody id="listaComponentesBody">
                            <!-- JS filler -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="cerrarModalPack()" class="btn-cancel">Cancelar</button>
                <div class="flex-1"></div>
                <button type="submit" id="btnGuardarPack" class="btn-save w-auto px-32" style="background: var(--accent); color: white;">Guardar Pack</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL HISTORIAL STOCK -->
<style>
    #modalHistorialStock .modal-content {
        max-width: 900px !important;
        width: 90% !important;
        background: var(--surface) !important;
        color: var(--text);
    }

    #modalHistorialStock .historial-table-wrapper {
        width: 100%;
        overflow-x: auto;
    }

    #modalHistorialStock .stock-log-table {
        width: 100% !important;
        border-collapse: collapse !important;
        table-layout: fixed !important;
        display: table !important;
        margin: 0 !important;
    }

    #modalHistorialStock .stock-log-table thead {
        display: table-header-group !important;
    }

    #modalHistorialStock .stock-log-table tbody {
        display: table-row-group !important;
    }

    #modalHistorialStock .stock-log-table tr {
        display: table-row !important;
    }

    #modalHistorialStock .stock-log-table th,
    #modalHistorialStock .stock-log-table td {
        display: table-cell !important;
        padding: 16px 20px !important;
        border-bottom: 1px solid var(--border) !important;
        text-align: left;
        vertical-align: middle;
    }

    #modalHistorialStock .stock-log-table thead th {
        background: var(--surface2) !important;
        color: var(--text-muted) !important;
        font-size: 11px !important;
        text-transform: uppercase !important;
        font-weight: bold !important;
        position: sticky;
        top: 0;
        z-index: 20;
    }

    #modalHistorialStock .empty-log-container {
        padding: 80px 40px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        width: 100%;
        color: var(--text-muted);
    }

    #modalHistorialStock .empty-log-icon {
        font-size: 56px;
        margin-bottom: 24px;
        opacity: 0.15;
    }
</style>
<div id="modalHistorialStock" class="modal-overlay-bg d-none">
    <div class="modal-content printable-area" style="padding: 0; overflow: visible; border: none; border-radius: 20px; box-shadow: 0 30px 60px rgba(0,0,0,0.4); background: var(--surface);">
        <div class="modal-header" style="background: linear-gradient(135deg, var(--surface) 0%, var(--surface2) 100%); padding: 32px; border-bottom: 1px solid var(--border); position: relative;">
            <div class="d-flex ai-center gap-20">
                <div class="bg-accent-soft p-16 br-16 text-accent" style="box-shadow: 0 8px 16px rgba(var(--accent-rgb), 0.1);">
                    <i class="fa-solid fa-clock-rotate-left fs-28"></i>
                </div>
                <div>
                    <h2 id="historialTitle" class="m-0 fs-24 font-bold mb-4">Historial de Stock</h2>
                    <div id="historialSubtitle" class="fs-14 opacity-70"></div>
                </div>
            </div>
            <button type="button" onclick="cerrarModalHistorial()" class="btn-close-modal" style="position: absolute; top: 32px; right: 32px; background: var(--surface2); width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border); transition: all 0.2s;">&times;</button>
        </div>

        <div class="modal-body" style="padding: 0; background: var(--surface); min-height: 400px;">
            <div class="table-container" style="max-height: 550px; overflow-y: auto; border-radius: 0;">
                <table class="stock-log-table">
                    <thead>
                        <tr>
                            <th style="width: 180px;">Fecha y Hora</th>
                            <th style="width: 160px;">Operación</th>
                            <th style="width: 110px;" class="text-center">Variación</th>
                            <th style="width: 150px;">Responsable</th>
                            <th style="width: auto;">Observaciones</th>
                        </tr>
                    </thead>
                    <tbody id="historialTableBody" class="fs-13">
                        <!-- JS filler -->
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal-footer" style="padding: 24px 32px; background: var(--surface); border-top: 1px solid var(--border); display: flex; align-items: center;">
            <button type="button" onclick="cerrarModalHistorial()" class="btn-cancel" style="border: 1px solid var(--border); padding: 12px 24px; border-radius: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-xmark"></i> Cerrar
            </button>
        </div>
    </div>
</div>

<script>
    // IVA general vigente, calculado en el backend (para que se actualice automáticamente si cambia en tipos_iva)
    const IVA_GENERAL_ACTUAL = <?php echo json_encode($avProductos['ivaGeneral'] ?? 21.00); ?>;
    const TIPOS_IVA = <?php echo json_encode($avProductos['tipos_iva'] ?? []); ?>;
    const TODOS_LOS_PRODUCTOS = <?php echo json_encode($avProductos['productos'] ?? []); ?>;
    let componentesPackActivos = [];

    /** 
     * Lógica de gestión de productos (JS integrado por ahora)
     */

    function calcularCosteAutomatico() {
        const inputProveedor = document.getElementById('prodPrecioProveedor');
        const selIva = document.getElementById('prodIvaTipo');
        const selProv = document.getElementById('prodProveedor');
        const inputCoste = document.getElementById('prodPrecioCoste');

        if (!inputProveedor || !selIva || !selProv || !inputCoste) return;

        const precioProv = parseFloat(inputProveedor.value.replace(',', '.')) || 0;
        const optIva = selIva.options[selIva.selectedIndex];
        const ivaPerc = parseFloat(optIva.dataset.porcentaje) || 0;

        const optProv = selProv.options[selProv.selectedIndex];
        const appliesRE = optProv && parseInt(optProv.dataset.re) === 1;
        const rePerc = appliesRE ? (parseFloat(optIva.dataset.re) || 0) : 0;

        const totalFactor = 1 + (ivaPerc / 100) + (rePerc / 100);
        const costeFinal = (precioProv * totalFactor).toFixed(2);

        inputCoste.value = costeFinal;
    }

    function seleccionarProveedor(select) {
        calcularCosteAutomatico();
    }

    // --- FUNCIONES GLOBALES DE MODALES ---

    function abrirModalProducto(producto = null) {
        const modal = document.getElementById('modalProducto');
        const title = document.getElementById('modalTitle');
        const form = document.getElementById('formProducto');

        // Cerrar cualquier otro modal-overlay antes de abrir este
        document.querySelectorAll('.modal-overlay-bg').forEach(m => {
            m.style.display = 'none';
        });

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
            document.getElementById('prodProveedor').value = producto.id_proveedor || '';
            document.getElementById('prodGarantia').value = producto.meses_garantia || '24';

            // Seleccionar el tipo de IVA actual del producto en el select
            const selIva = document.getElementById('prodIvaTipo');
            if (selIva) {
                selIva.value = producto.codigo_iva || 'GENERAL';
            }
            document.getElementById('prodPrecioProveedor').value = producto.precio_proveedor || '0.00';
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


            cargarVariantesFisicas(producto.id);

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
            document.getElementById('prodProveedor').value = '';
            document.getElementById('imgPreview').innerHTML = '<i class="fa-solid fa-image"></i>';


            document.getElementById('sectionVariantesFisicas').classList.add('d-none');
            document.getElementById('variantesContainer').innerHTML = '';
            document.getElementById('atributosContainer').innerHTML = '';
            document.getElementById('prodAtributos').value = '';
            document.getElementById('prodVariantes').value = '';

            // Habilitar campos si es producto nuevo
            document.getElementById('prodPrecioProveedor').value = '0.00';
            document.getElementById('prodPrecioCoste').readOnly = true;
            document.getElementById('prodStock').readOnly = false;
            document.getElementById('prodPrecioCoste').style.opacity = '0.7';
            document.getElementById('prodStock').style.opacity = '1';


            // Nuevo producto: usar IVA general vigente como valor por defecto
            const selIva = document.getElementById('prodIvaTipo');
            if (selIva) selIva.value = 'GENERAL';
        }

        modal.style.display = 'flex';
    }

    function cerrarModalProducto() {
        document.getElementById('modalProducto').style.display = 'none';
        document.querySelectorAll('.form-error').forEach(el => el.innerText = '');
    }

    function abrirModalPack(pack = null) {
        const modal = document.getElementById('modalPack');
        const title = document.getElementById('modalPackTitle');
        const form = document.getElementById('formPack');

        if (!modal || !title || !form) return;

        // Cerrar cualquier otro modal-overlay antes de abrir este
        document.querySelectorAll('.modal-overlay-bg').forEach(m => {
            m.style.display = 'none';
        });

        form.reset();
        document.querySelectorAll('.form-error').forEach(e => e.textContent = '');
        componentesPackActivos = [];

        if (pack) {
            title.textContent = 'Editar Pack: ' + pack.nombre;
            document.getElementById('packId').value = pack.id;
            document.getElementById('packNombre').value = pack.nombre;
            document.getElementById('packCodigo').value = pack.codigo || '';
            document.getElementById('packPrecioVenta').value = pack.precio;
            document.getElementById('packCat').value = pack.categoria;
            document.getElementById('packIcono').value = pack.icono || '';

            // Cargar componentes guardados
            componentesPackActivos = pack.componentes_pack ? [...pack.componentes_pack] : [];

            // Buscar IVA actual del producto
            let codigoIva = 'GENERAL';
            for (const [key, t] of Object.entries(TIPOS_IVA)) {
                if (parseFloat(t.porcentaje) === parseFloat(pack.iva_aplicado)) {
                    codigoIva = key;
                    break;
                }
            }
            if (document.getElementById('packIvaTipo')) {
                document.getElementById('packIvaTipo').value = codigoIva;
            }

            // Preview imagen
            const preview = document.getElementById('imgPreviewPack');
            if (pack.icono && pack.icono.startsWith('data:image')) {
                preview.innerHTML = `<img src="${pack.icono}" style="width:100%; height:100%; object-fit:contain; border-radius:8px;">`;
            } else {
                preview.innerHTML = `<span class="fs-24">${pack.icono || '<i class="fa-solid fa-boxes-stacked"></i>'}</span>`;
            }
        } else {
            title.textContent = 'Nuevo Pack de Venta';
            document.getElementById('packId').value = '';
            document.getElementById('packIcono').value = '';
            document.getElementById('imgPreviewPack').innerHTML = `<i class="fa-solid fa-boxes-stacked fs-32 opacity-20"></i>`;

            const selIva = document.getElementById('packIvaTipo');
            if (selIva) selIva.value = 'GENERAL';
        }

        renderComponentesPack();
        modal.classList.remove('d-none');
        modal.style.display = 'flex';
    }

    function cerrarModalPack() {
        const modal = document.getElementById('modalPack');
        if (modal) {
            modal.classList.add('d-none');
            modal.style.display = 'none';
        }
    }

    function abrirModalGestionCategorias() {
        const modal = document.getElementById('modalCategorias');
        if (!modal) return;

        // Cerrar cualquier otro modal-overlay antes de abrir este
        document.querySelectorAll('.modal-overlay-bg').forEach(m => {
            m.style.display = 'none';
        });

        modal.classList.remove('d-none');
        modal.style.display = 'flex';
    }

    function cerrarModalCategorias() {
        const modal = document.getElementById('modalCategorias');
        if (modal) modal.style.display = 'none';
        location.reload();
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



    async function cargarVariantesFisicas(idProd) {
        const container = document.getElementById('sectionVariantesFisicas');
        const body = document.getElementById('listaVariantesBody');
        body.innerHTML = '<tr><td colspan="6" class="p-12 text-center text-muted">Cargando variantes...</td></tr>';

        try {
            const resp = await fetch(`api/gestionVariante.php?accion=listar&id_producto=${idProd}`);
            const data = await resp.json();

            if (data.ok && data.lista.length > 0) {
                container.classList.remove('d-none');
                body.innerHTML = '';
                data.lista.forEach(v => {
                    const tr = document.createElement('tr');
                    tr.className = 'border-bottom-1';
                    tr.innerHTML = `
                        <td class="p-8">${v.nombre}</td>
                        <td class="p-8"><input type="text" class="form-input fs-11 p-4 w-100 var-sku" value="${v.sku}" data-id="${v.id}"></td>
                        <td class="p-8"><input type="number" class="form-input fs-11 p-4 w-100 text-center var-stock" value="${v.stock_actual}" data-id="${v.id}" oninput="sincronizarStockTotal()"></td>
                        <td class="p-8"><input type="number" class="form-input fs-11 p-4 w-100 text-right var-precio" value="${v.precio_venta || ''}" placeholder="Base" step="0.01" data-id="${v.id}"></td>
                        <td class="p-8 text-center"><input type="checkbox" class="var-activo" ${parseInt(v.activo) ? 'checked' : ''} data-id="${v.id}"></td>
                        <td class="p-8 text-center"></td>
                    `;
                    body.appendChild(tr);
                });
                sincronizarStockTotal();
            } else {
                container.classList.add('d-none');
            }
        } catch (e) {
            console.error(e);
            body.innerHTML = '<tr><td colspan="6" class="p-12 text-center text-red">Error al cargar variantes</td></tr>';
        }
    }

    function sincronizarStockTotal() {
        const stocks = document.querySelectorAll('.var-stock');
        let total = 0;
        let tieneVariantes = false;
        stocks.forEach(s => {
            total += parseInt(s.value || 0);
            tieneVariantes = true;
        });

        if (tieneVariantes) {
            const inputStock = document.getElementById('prodStock');
            inputStock.value = total;
            inputStock.readOnly = true;
            inputStock.style.opacity = '0.7';
            inputStock.title = "El stock total se calcula sumando las variantes";
        } else {
            document.getElementById('prodStock').readOnly = false;
            document.getElementById('prodStock').style.opacity = '1';
        }
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

        // Recoger variantes físicas si existen
        const listaVariantes = [];
        const filasVariantes = document.querySelectorAll('#listaVariantesBody tr');
        filasVariantes.forEach(tr => {
            const inSku = tr.querySelector('.var-sku');
            if (!inSku) return;

            listaVariantes.push({
                id: inSku.dataset.id,
                sku: inSku.value.trim(),
                stock_actual: tr.querySelector('.var-stock').value,
                precio_venta: tr.querySelector('.var-precio').value,
                activo: tr.querySelector('.var-activo').checked ? 1 : 0
            });
        });

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
            requiere_serial: 0,
            variantes: document.getElementById('prodVariantes').value || null,
            atributos: document.getElementById('prodAtributos').value || null,
            codigo_iva: codigoIva,
            precio_proveedor: document.getElementById('prodPrecioProveedor').value,
            id_proveedor: document.getElementById('prodProveedor').value || null,
            aplica_re: document.getElementById('prodAplicaRE').checked ? 1 : 0,
            es_pack: 0,

            componentes_pack: null,

            variantes_fisicas: listaVariantes.length > 0 ? listaVariantes : null
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
                        'precio_proveedor': 'err-precio_proveedor',
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

    // --- TABS LOGIC ---
    function switchTab(tabId) {
        // Pestañas
        const btnProd = document.getElementById('btnTabProductos');
        const btnPacks = document.getElementById('btnTabPacks');
        const contentProd = document.getElementById('tabContentProductos');
        const contentPacks = document.getElementById('tabContentPacks');

        // Botones de acción y filtros
        const btnNuevoProd = document.getElementById('btnNuevoProducto');
        const btnNuevoPack = document.getElementById('btnNuevoPack');
        const filtersPrecios = document.getElementById('filtersPrecios');

        if (tabId === 'productos') {
            btnProd.classList.add('active');
            btnPacks.classList.remove('active');

            contentProd.classList.add('active');
            contentProd.classList.remove('d-none');
            contentPacks.classList.add('d-none');
            contentPacks.classList.remove('active');

            btnNuevoProd.classList.remove('d-none');
            btnNuevoPack.classList.add('d-none');
            filtersPrecios.classList.remove('d-none');
        } else {
            btnPacks.classList.add('active');
            btnProd.classList.remove('active');

            contentPacks.classList.add('active');
            contentPacks.classList.remove('d-none');
            contentProd.classList.add('d-none');
            contentProd.classList.remove('active');

            btnNuevoPack.classList.remove('d-none');
            btnNuevoProd.classList.add('d-none');
            filtersPrecios.classList.add('d-none');
        }
    }

    // --- PACKS LOGIC ---




    function previewImagePack(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('packIcono').value = e.target.result;
                document.getElementById('imgPreviewPack').innerHTML = `<img src="${e.target.result}" style="width:100%; height:100%; object-fit:contain; border-radius:8px;">`;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }


    function buscarProductoParaPack(query) {
        const resCont = document.getElementById('packSearchResults');
        query = query.trim().toLowerCase();
        if (query.length < 2) {
            resCont.style.display = 'none';
            return;
        }

        const idEnEdicion = parseInt(document.getElementById('packId').value) || 0;
        const yaAñadidos = componentesPackActivos.map(c => parseInt(c.id_producto || c.id));

        const filtrados = TODOS_LOS_PRODUCTOS.filter(p => {
            if (p.id === idEnEdicion) return false; // Not itself
            if (p.es_pack) return false; // Pack of packs not supported yet
            if (yaAñadidos.includes(p.id)) return false; // Already added

            return p.nombre.toLowerCase().includes(query) || (p.codigo && p.codigo.toLowerCase().includes(query));
        }).slice(0, 8); // top 8

        resCont.innerHTML = '';
        if (filtrados.length === 0) {
            resCont.innerHTML = '<div class="p-16 text-muted fs-12 text-center"><i class="fa-solid fa-circle-info mr-8"></i> No hay coincidencias</div>';
        } else {
            filtrados.forEach(p => {
                const div = document.createElement('div');
                div.className = 'search-result-item';
                div.innerHTML = `
                    <div class="d-flex ai-center gap-12 full-width">
                        <div class="result-icon"><i class="fa-solid fa-box fs-14"></i></div>
                        <div class="flex-1 overflow-hidden">
                            <div class="result-title text-ellipsis">${p.nombre}</div>
                            <div class="result-meta font-mono">${p.codigo || 'S/R'}</div>
                        </div>
                        <div class="result-price">${(parseFloat(p.precio)).toFixed(2)}€</div>
                    </div>
                `;
                div.onclick = () => {
                    añadirComponente(p);
                    document.getElementById('packSearchInput').value = '';
                    resCont.style.display = 'none';
                };
                resCont.appendChild(div);
            });
        }
        resCont.style.display = 'block';
    }

    // Hide search results when click outside
    document.addEventListener('click', (e) => {
        if (e.target.id !== 'packSearchInput') {
            const sr = document.getElementById('packSearchResults');
            if (sr) sr.style.display = 'none';
        }
    });

    function añadirComponente(prod) {
        componentesPackActivos.push({
            id_producto: prod.id,
            id: prod.id, // For compatibility
            nombre: prod.nombre,
            referencia: prod.codigo,
            cantidad: 1
        });
        renderComponentesPack();
    }

    function removerComponentePack(id) {
        componentesPackActivos = componentesPackActivos.filter(c => c.id_producto != id && c.id != id);
        renderComponentesPack();
    }

    function actualizarCantidadComponente(id, input) {
        let val = parseInt(input.value) || 1;
        if (val < 1) val = 1;
        input.value = val;

        const c = componentesPackActivos.find(c => c.id_producto == id || c.id == id);
        if (c) c.cantidad = val;
    }

    function renderComponentesPack() {
        const body = document.getElementById('listaComponentesBody');
        body.innerHTML = '';

        if (componentesPackActivos.length === 0) {
            body.innerHTML = `
                <tr>
                    <td colspan="4" class="p-32 text-center text-muted">
                        <div class="opacity-40 mb-12"><i class="fa-solid fa-boxes-stacked fs-32"></i></div>
                        <div class="fs-13 font-bold">Aún no hay componentes</div>
                        <div class="fs-11">Usa el buscador superior para añadir productos al pack</div>
                    </td>
                </tr>
            `;
            return;
        }

        componentesPackActivos.forEach(c => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="p-8 text-muted font-mono">${c.referencia || ''}</td>
                <td class="p-8 font-bold">${c.nombre || ''}</td>
                <td class="p-8 text-center" style="width: 80px;">
                    <input type="number" min="1" class="form-input text-center p-4 fs-12 full-width input-qty-mini" value="${c.cantidad}" onchange="actualizarCantidadComponente(${c.id_producto || c.id}, this)">
                </td>
                <td class="p-8 text-center">
                    <button type="button" class="btn-icon text-red" onclick="removerComponentePack(${c.id_producto || c.id})">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </td>
            `;
            body.appendChild(tr);
        });
    }

    document.getElementById('prodSearch').addEventListener('input', applyFilters);
    document.getElementById('filterCat').addEventListener('change', applyFilters);
    document.getElementById('filterEstado').addEventListener('change', applyFilters);

    // Guardar Pack Form Submit Listener
    document.getElementById('formPack').addEventListener('submit', async (e) => {
        e.preventDefault();

        if (componentesPackActivos.length === 0) {
            alert("Un pack debe tener al menos un componente.");
            return;
        }

        const btn = document.getElementById('btnGuardarPack');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

        const id = document.getElementById('packId').value || null;
        const codIva = document.getElementById('packIvaTipo').value;
        const listaIvas = Array.isArray(TIPOS_IVA) ? TIPOS_IVA : Object.values(TIPOS_IVA);
        const tipoIva = listaIvas.find(t => t.codigo === codIva);
        const ivaPerc = tipoIva ? tipoIva.porcentaje : 21.00;

        const payload = {
            accion: id ? 'editar' : 'añadir',
            id: id,
            nombre: document.getElementById('packNombre').value,
            referencia: document.getElementById('packCodigo').value.trim() || null,
            icono: document.getElementById('packIcono').value || '',
            categoria: document.getElementById('packCat').value,
            precio_coste: 0,
            precio_venta: document.getElementById('packPrecioVenta').value,
            iva: ivaPerc,
            codigo_iva: codIva,
            stock_actual: 0,
            stock_minimo: 0,
            meses_garantia: 24,
            descripcion: 'Pack de productos',
            es_pack: 1,
            componentes_pack: componentesPackActivos
        };

        try {
            const resp = await fetch('api/gestionProducto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const data = await resp.json();

            if (data.ok) {
                cerrarModalPack();
                location.reload();
            } else {
                let msg = data.error || 'Error desconocido';
                if (data.aErrores) {
                    const errorList = Object.entries(data.aErrores)
                        .filter(([k, v]) => v !== null && v !== '')
                        .map(([k, v]) => `- ${k}: ${v}`)
                        .join('\n');
                    if (errorList) msg += '\n\nDetalles:\n' + errorList;
                }
                alert('Error al guardar el pack:\n' + msg);
            }
        } catch (err) {
            alert('Error de conexión al servidor al guardar el pack.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Guardar Pack';
        }
    });

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

    // --- HISTORIAL DE STOCK ---
    async function abrirModalHistorial(id, nombre) {
        const modal = document.getElementById('modalHistorialStock');
        const title = document.getElementById('historialTitle');
        const subtitle = document.getElementById('historialSubtitle');
        const body = document.getElementById('historialTableBody');

        title.innerText = "Historial: " + nombre;
        subtitle.innerText = "Cargando movimientos...";
        body.innerHTML = '<tr><td colspan="5" class="text-center p-24 opacity-50">Consultando base de datos...</td></tr>';

        modal.classList.remove('d-none');
        modal.style.display = 'flex';

        try {
            const resp = await fetch(`api/obtenerHistorialStock.php?id=${id}`);
            const data = await resp.json();

            if (data.ok) {
                subtitle.innerText = `${data.historial.length} movimientos registrados`;
                if (data.historial.length === 0) {
                    body.innerHTML = `
                        <tr>
                            <td colspan="5">
                                <div class="empty-log-container">
                                    <div class="empty-log-icon"><i class="fa-solid fa-ghost"></i></div>
                                    <div class="fs-18 font-bold mb-8" style="color: var(--text);">Sin movimientos</div>
                                    <div class="fs-14 opacity-60">No hay registros de cambios de stock para este producto.</div>
                                </div>
                            </td>
                        </tr>
                    `;
                } else {
                    body.innerHTML = data.historial.map(m => `
                        <tr class="hover-bg-surface2 transition">
                            <td class="font-mono fs-12">
                                <span class="text-muted">${new Date(m.fecha).toLocaleDateString()}</span><br>
                                <span class="font-bold">${new Date(m.fecha).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                            </td>
                            <td>
                                <div class="d-flex ai-center gap-10">
                                    <div class="p-8 br-8 ${getTipoMovClase(m.tipo_movimiento)} d-flex ai-center jc-center" style="width:32px; height:32px; flex-shrink:0;">
                                        <i class="fa-solid ${getTipoMovIcono(m.tipo_movimiento)} fs-13"></i>
                                    </div>
                                    <span class="tt-uppercase font-bold fs-10" style="letter-spacing: 0.05em;">${m.tipo_movimiento}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="p-6-10 br-10 font-mono font-bold fs-14 ${m.cantidad > 0 ? 'bg-green-soft text-green' : 'text-red bg-red-soft'}">
                                    ${m.cantidad > 0 ? '+' : ''}${m.cantidad}
                                </div>
                            </td>
                            <td>
                                <div class="d-flex ai-center gap-10">
                                    <div class="bg-surface2 br-circle d-flex ai-center jc-center fs-10 font-bold" style="width:28px; height:28px; border:1px solid var(--border); flex-shrink:0;">
                                        ${(m.nombre_usuario || 'S').charAt(0).toUpperCase()}
                                    </div>
                                    <span class="opacity-90 fs-12">${m.nombre_usuario || 'Sistema'}</span>
                                </div>
                            </td>
                            <td class="fs-12 italic opacity-70">${m.notas || '<span class="opacity-30">Sin notas</span>'}</td>
                        </tr>
                    `).join('');
                }
            } else {
                body.innerHTML = `<tr><td colspan="5" class="text-center p-48 text-red font-bold">Error: ${data.error}</td></tr>`;
            }
        } catch (e) {
            body.innerHTML = '<tr><td colspan="5" class="text-center p-24 text-red">Error de red</td></tr>';
        }
    }

    function getTipoMovClase(tipo) {
        switch (tipo) {
            case 'inicial':
                return 'bg-accent-soft text-accent';
            case 'compra':
                return 'bg-green-soft text-green';
            case 'venta':
                return 'bg-red-soft text-red';
            case 'devolucion':
                return 'bg-yellow-soft text-yellow';
            case 'ajuste':
                return 'bg-surface2 text-muted';
            default:
                return 'bg-surface2 text-muted';
        }
    }

    function getTipoMovIcono(tipo) {
        switch (tipo) {
            case 'inicial':
                return 'fa-star';
            case 'compra':
                return 'fa-truck-ramp-box';
            case 'venta':
                return 'fa-hand-holding-dollar';
            case 'devolucion':
                return 'fa-rotate-left';
            case 'ajuste':
                return 'fa-wrench';
            default:
                return 'fa-circle-info';
        }
    }

    function cerrarModalHistorial() {
        document.getElementById('modalHistorialStock').style.display = 'none';
    }


    // --- PEDIDO AUTOMÁTICO ---
    async function generarPedidoAutomatico() {
        if (!confirm("Se generarán pedidos de compra (albaranes pendientes) para todos los productos que estén por debajo de su stock mínimo. \n\n¿Deseas continuar?")) return;

        showNotification("<i class='fa-solid fa-spinner fa-spin'></i> Calculando pedido...", 'info');

        try {
            const resp = await fetch('api/generarPedidoAuto.php');
            const data = await resp.json();

            if (data.ok) {
                if (data.pedidos > 0) {
                    showNotification(` < i class = 'fa-solid fa-circle-check' > < /i> ${data.mensaje}`, 'success');
                    setTimeout(() => {
                        if (confirm("¿Deseas ir a la sección de compras para revisar los pedidos generados?")) {
                            location.href = 'index.php?irAlbaranes=1'; // Asumiendo que esta es la página de albaranes de compra
                        }
                    }, 2000);
                } else {
                    showNotification("<i class='fa-solid fa-circle-info'></i> " + data.mensaje, 'info');
                }
            } else {
                showNotification("<i class='fa-solid fa-circle-xmark'></i> Error: " + data.error, 'error');
            }
        } catch (e) {
            showNotification("<i class='fa-solid fa-circle-xmark'></i> Error de conexión", 'error');
        }
    }

    // Actualizar applyFilters para el filtro Bajo Stock
    const applyFiltersOriginal = window.applyFilters;
    window.applyFilters = function() {
        const query = document.getElementById('prodSearch').value.toLowerCase();
        const cat = document.getElementById('filterCat').value;
        const estado = document.getElementById('filterEstado').value;
        const pMin = parseFloat(document.getElementById('filterPriceMin').value) || 0;
        const pMax = parseFloat(document.getElementById('filterPriceMax').value) || 9999999;

        const rows = document.querySelectorAll('.product-row');
        let visibles = 0;

        rows.forEach(row => {
            const nombre = row.dataset.nombre || '';
            const codigo = row.dataset.codigo || '';
            const categoria = row.dataset.categoria || '';
            const precio = parseFloat(row.dataset.price || row.dataset.precio) || 0;
            const activo = row.dataset.activo || '1';
            const stock = parseInt(row.dataset.stock) || 0;
            const stockMin = parseInt(row.dataset.stockMinimo) || 0;

            let match = true;

            if (query && !nombre.includes(query) && !codigo.includes(query)) match = false;
            if (cat !== 'all' && categoria !== cat) match = false;

            if (estado === 'bajo_stock') {
                if (stock > stockMin) match = false;
            } else if (estado !== 'all' && activo !== estado) match = false;

            if (precio < pMin || precio > pMax) match = false;

            row.style.display = match ? 'table-row' : 'none';
            if (match) visibles++;
        });

        // Toggle empty states
        const noRes = document.getElementById('noResults');
        if (noRes) noRes.classList.toggle('d-none', visibles > 0 || document.getElementById('btnTabPacks').classList.contains('active'));
    };
</script>

<!-- MODAL GESTIÓN CATEGORÍAS -->
<div id="modalCategorias" class="modal-overlay-bg">
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