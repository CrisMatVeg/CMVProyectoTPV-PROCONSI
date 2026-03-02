</header>
</header>


<div class="main">
    <!-- CATALOG PANEL -->
    <div class="catalog-panel">
        <div class="search-bar">
            <div class="search-input-wrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input
                    class="search-input"
                    type="text"
                    id="searchInput"
                    oninput="handleSearch(this.value)"
                    placeholder="Buscar producto o referencia…" />
            </div>
            <button onclick="toggleAdvancedFilters()" class="btn-filter-toggle p-10 br-10 bg-surface border-2 cursor-pointer transition-all" title="Filtros avanzados">
                <i class="fa-solid fa-filter"></i>
            </button>
            <?php if ($avInicioPrivado['esAdmin']): ?>
            <button onclick="abrirModalNuevoProducto()" class="btn-add p-7-14 fs-13">
                <i class="fa-solid fa-plus"></i>
                Nuevo producto
            </button>
            <?php endif; ?>
        </div>

        <!-- ADVANCED FILTERS PANEL -->
        <div id="advancedFilters" class="advanced-filters-panel d-none bg-surface p-16 br-12 border-2 mb-10 shadow-sm">
            <div class="grid-4 gap-12 ai-end">
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase opacity-70">Precio Mín (€)</label>
                    <input type="number" id="filterPriceMin" class="form-input fs-13" placeholder="0.00" oninput="applyAdvancedFilters()">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase opacity-70">Precio Máx (€)</label>
                    <input type="number" id="filterPriceMax" class="form-input fs-13" placeholder="999.99" oninput="applyAdvancedFilters()">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase opacity-70">Stock</label>
                    <select id="filterStock" class="form-input fs-13" onchange="applyAdvancedFilters()">
                        <option value="all">Todos</option>
                        <option value="in-stock">En Stock</option>
                        <option value="low-stock">Stock Bajo (≤5)</option>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fs-11 tt-uppercase opacity-70">Ordenar por</label>
                    <select id="filterSort" class="form-input fs-13" onchange="applyAdvancedFilters()">
                        <option value="name-asc">Nombre (A-Z)</option>
                        <option value="name-desc">Nombre (Z-A)</option>
                        <option value="price-asc">Precio (Menor a Mayor)</option>
                        <option value="price-desc">Precio (Mayor a Menor)</option>
                        <option value="stock-asc">Stock (Menor a Mayor)</option>
                        <option value="stock-desc">Stock (Mayor a Menor)</option>
                    </select>
                </div>
            </div>
        </div>


        <div class="cat-tabs" id="catTabs">
            <button class="cat-tab active" data-cat="all">Todo</button>
            <button class="cat-tab" data-cat="audio">Audio</button>
            <button class="cat-tab" data-cat="movil">Móvil</button>
            <button class="cat-tab" data-cat="gaming">Gaming</button>
            <button class="cat-tab" data-cat="informatica">Informática</button>
            <button class="cat-tab" data-cat="cables">Cables y Cargadores</button>
            <button class="cat-tab" data-cat="foto">Foto y Video</button>
            <button class="cat-tab d-inline-flex ai-center gap-6 text-red border-red-light bg-red-light" data-cat="baja">
                <i class="fa-solid fa-arrow-trend-down"></i> De Baja
            </button>
        </div>

        <div class="products-grid" id="productsGrid"></div>
    </div>

    <!-- ORDER PANEL -->
    <div class="order-panel">
        <div class="order-header">
            <div class="d-flex ai-center gap-10">
                <span class="order-title">Pedido</span>
                <span class="order-count" id="orderCount">0</span>
            </div>
            <button class="btn-clear" onclick="clearCart()">Vaciar</button>
        </div>

        <div class="order-items" id="orderItems">
            <div class="empty-cart" id="emptyCart">
                <div class="empty-cart-icon">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
                <div>Añade productos<br />al pedido</div>
            </div>
        </div>

        <div class="order-totals">
            <div class="total-row">
                <span>Subtotal</span>
                <span id="subtotal">0,00 €</span>
            </div>
            <div
                class="total-row d-none text-green"
                id="discountRow">
                <span>Descuento</span>
                <span id="discountAmt">-0,00 €</span>
            </div>
            <div class="total-row">
                <span>IVA (21%)</span>
                <span id="vatAmt">0,00 €</span>
            </div>
            <div class="total-row main">
                <span>Total</span>
                <span id="totalAmt">0,00 €</span>
            </div>
        </div>

        <div class="payment-section">
            <div class="payment-label">Método de pago</div>
            <div class="payment-methods">
                <button
                    class="pay-btn selected"
                    data-method="efectivo"
                    onclick="selectPayment(this)">
                    <i class="fa-solid fa-money-bill-1-wave"></i>
                    Efectivo
                </button>
                <button
                    class="pay-btn"
                    data-method="tarjeta"
                    onclick="selectPayment(this)">
                    <i class="fa-solid fa-credit-card"></i>
                    Tarjeta
                </button>
                </div>

            <div class="discount-row">
                <input
                    class="discount-input"
                    type="text"
                    id="discountCode"
                    placeholder="Código descuento" />
                <button class="discount-apply" onclick="applyDiscount()">
                    Aplicar
                </button>
            </div>
            <span id="err-discount" class="form-error"></span>

            <button
                class="charge-btn"
                id="chargeBtn"
                onclick="processPayment()"
                disabled>
                Cobrar <span id="chargeTotal">0,00 €</span>
            </button>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal">
    <div class="modal modal-content gap-14 ai-stretch">
        <div class="modal-header mb-0">
            <div class="modal-title fs-16">Editar producto</div>
            <button onclick="document.getElementById('editModal').classList.remove('visible')" class="btn-close-modal">×</button>
        </div>
        <input type="hidden" id="editId" />
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Imagen / Icono</label>
                <div class="d-flex ai-center gap-12">
                    <div id="editImgPreview" class="prod-img-preview" style="width: 80px; height: 80px; flex-shrink: 0;">
                        <i class="fa-solid fa-image"></i>
                    </div>
                    <div class="flex-1">
                        <input type="file" id="editFile" accept="image/*" class="d-none" onchange="previewImageTPV(this, 'edit')">
                        <button type="button" onclick="document.getElementById('editFile').click()" class="btn-icon w-auto h-auto p-12-20 fs-13 gap-8 full-width">
                            <i class="fa-solid fa-upload"></i> Cambiar Imagen
                        </button>
                        <input type="hidden" id="editEmoji" />
                    </div>
                </div>
            </div>
            <div class="form-group flex-1">
                <label class="form-label">Nombre</label>
                <input id="editName" class="form-input" />
                <span class="form-error" id="err-editNombre"></span>
            </div>
            <div class="form-group-wrap grid-2">
                <div class="form-group">
                    <label class="form-label">Referencia</label>
                    <input id="editSku" class="form-input font-mono" />
                </div>
                <div class="form-group">
                    <label class="form-label">Precio (€)</label>
                    <input id="editPrice" class="form-input font-mono text-right" type="text" />
                    <span class="form-error" id="err-editPrecio"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">IVA (%)</label>
                    <input id="editIva" class="form-input font-mono text-right" type="number" min="0" max="100" step="1" placeholder="21" />
                </div>
                <div class="form-group">
                    <label class="form-label">Garantía (meses)</label>
                    <input id="editMesesGarantia" class="form-input font-mono text-right" type="number" min="0" max="120" step="1" placeholder="24" />
                </div>
            </div>
            <div class="form-group mb-0">
                <label class="d-flex ai-center gap-8 cursor-pointer fs-13">
                    <input type="checkbox" id="editSerial" class="w-16 h-16" />
                    <span>Requiere controlar Número de Serie en la venta</span>
                </label>
            </div>
        </div>
        <button class="btn-save mt-4 full-width" onclick="saveEdit()">
            Guardar cambios
        </button>
    </div>
</div>

<!-- ADD MODAL -->
<div class="modal-overlay" id="addModal">
    <div class="modal modal-content gap-14 ai-stretch">
        <div class="modal-header mb-0">
            <div class="modal-title fs-16">Nuevo producto</div>
            <button onclick="document.getElementById('addModal').classList.remove('visible')" class="btn-close-modal">×</button>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Imagen / Icono</label>
                <div class="d-flex ai-center gap-12">
                    <div id="addImgPreview" class="prod-img-preview" style="width: 80px; height: 80px; flex-shrink: 0;">
                        <i class="fa-solid fa-image"></i>
                    </div>
                    <div class="flex-1">
                        <input type="file" id="addFile" accept="image/*" class="d-none" onchange="previewImageTPV(this, 'add')">
                        <button type="button" onclick="document.getElementById('addFile').click()" class="btn-icon w-auto h-auto p-12-20 fs-13 gap-8 full-width">
                            <i class="fa-solid fa-upload"></i> Subir Imagen
                        </button>
                        <input type="hidden" id="addEmoji" />
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Nombre</label>
                <input id="addName" class="form-input" placeholder="Nombre completo" />
                <span class="form-error" id="err-addNombre"></span>
            </div>
            <div class="form-group-wrap grid-3">
                <div class="form-group">
                    <label class="form-label">Referencia (SKU)</label>
                    <input id="addSku" class="form-input font-mono" placeholder="PRO-001" />
                </div>
                <div class="form-group">
                    <label class="form-label">Precio (€)</label>
                    <input id="addPrice" class="form-input font-mono text-right" type="text" placeholder="0.00" />
                    <span class="form-error" id="err-addPrecio"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">IVA (%)</label>
                    <input id="addIva" class="form-input font-mono text-right" type="text" value="21" />
                </div>
            </div>
            <div class="form-group">
                <label class="d-flex ai-center gap-8 cursor-pointer fs-13">
                    <input type="checkbox" id="addSerial" class="w-16 h-16" />
                    <span>Requiere controlar Número de Serie en la venta</span>
                </label>
            </div>
            <div class="form-group">
                <label class="form-label">Categoría</label>
                <select id="addCat" class="form-input">
                    <option value="audio">Audio</option>
                    <option value="movil">Móvil</option>
                    <option value="gaming">Gaming</option>
                    <option value="informatica">Informática</option>
                    <option value="cables">Cables y Cargadores</option>
                    <option value="foto">Foto y Video</option>
                </select>
            </div>
        </div>
        <button class="btn-save mt-4 full-width" onclick="guardarNuevoProducto()">
            Crear producto
        </button>
    </div>
</div>

<!-- DELETE CONFIRM MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal modal-content gap-16">
        <div class="modal-icon text-red bg-red-light">
            <i class="fa-solid fa-trash-can"></i>
        </div>
        <div class="modal-title">Eliminar producto</div>
        <div class="modal-sub">
            ¿Seguro que quieres eliminar <strong id="delName"></strong>? Esta
            acción no se puede deshacer.
        </div>
        <div class="modal-footer full-width">
            <button onclick="document.getElementById('deleteModal').classList.remove('visible')" class="btn-cancel">Cancelar</button>
            <button id="delConfirmBtn" class="btn-save bg-red">Eliminar</button>
        </div>
    </div>
</div>


<!-- MODAL 1: TIPO DE CLIENTE (aparece al pulsar Cobrar) -->
<div class="modal-overlay" id="clienteModal">
    <div class="modal modal-content gap-18 ai-stretch w-360">
        <div class="modal-title text-center fs-18">¿Tipo de cliente?</div>
        <div class="grid-3 gap-12 mb-12">
            <button id="btnParticular" onclick="seleccionarTipoCliente('particular')"
                class="p-16-8 br-12 border-2 bg-surface2 cursor-pointer fs-13 d-flex flex-column ai-center gap-12 active-scale h-auto">
                <i class="fa-solid fa-user fs-28"></i>
                <span class="font-bold">Particular</span>
            </button>
            <button id="btnSocio" onclick="seleccionarTipoCliente('socio')"
                class="p-16-8 br-12 border-2 bg-surface2 cursor-pointer fs-13 d-flex flex-column ai-center gap-12 active-scale h-auto">
                <i class="fa-solid fa-id-card fs-28 text-accent"></i>
                <span class="font-bold">Socio</span>
            </button>
            <button id="btnEmpresa" onclick="seleccionarTipoCliente('empresa')"
                class="p-16-8 br-12 border-2 bg-surface2 cursor-pointer fs-13 d-flex flex-column ai-center gap-12 active-scale h-auto">
                <i class="fa-solid fa-building fs-28"></i>
                <span class="font-bold">Empresa</span>
            </button>
        </div>
        
        <!-- Buscador de Socio -->
        <div id="socioBusqueda" class="d-none flex-column gap-8 p-12 bg-surface2 br-8 border-2 mb-8">
            <div class="d-flex gap-8">
                <input id="socioSearch" class="form-input fs-13 flex-1" placeholder="DNI o Nombre del socio..." />
                <button onclick="buscarSocio()" class="btn-save w-auto p-4-12"><i class="fa-solid fa-search"></i></button>
            </div>
            <div id="socioInfo" class="fs-12 mt-4 text-accent font-bold"></div>
            <button id="btnAddSocio" onclick="mostrarRegistroSocio()" class="cat-tab p-4-8 fs-11 d-none">+ Registrar nuevo socio</button>
        </div>

        <!-- Registro de Socio -->
        <div id="socioRegistro" class="d-none flex-column gap-8 p-12 bg-surface2 br-8 border-2 mb-8">
            <div class="form-label fs-12 font-bold">Nuevo Socio</div>
            <input id="newSocioNombre" class="form-input fs-12" placeholder="Nombre completo" />
            <input id="newSocioNif" class="form-input fs-12 font-mono" placeholder="DNI / NIE" />
            <div class="d-flex gap-8">
               <button onclick="cancelarRegistroSocio()" class="btn-cancel fs-11 p-4">Cancelar</button>
               <button onclick="guardarNuevoSocio()" class="btn-save fs-11 p-4">Guardar y Usar</button>
            </div>
        </div>
        <!-- Datos de empresa -->
        <div id="empresaDatos" class="d-none flex-column gap-8">
            <div class="form-group">
                <label class="form-label">Razón social</label>
                <input id="empresaNombre" class="form-input" placeholder="Nombre de la empresa" />
                <span class="form-error" id="err-empresaNombre"></span>
            </div>
            <div class="form-group">
                <label class="form-label">CIF / NIF</label>
                <input id="empresaNif" class="form-input font-mono" placeholder="B12345678" />
                <span class="form-error" id="err-empresaNif"></span>
            </div>
        </div>
        <!-- Gestión de Efectivo -->
        <div id="efectivoGestion" class="d-none flex-column gap-8 p-12 bg-surface2 br-8 border-2">
            <div class="form-label">Pago en efectivo</div>
            <div class="form-group">
                <label class="fs-13 font-bold">Importe recibido</label>
                <input id="efectivoRecibido" type="text" class="form-input font-mono fs-16 text-right" oninput="calcularCambio()" />
            </div>
            <div class="d-flex jc-space-between ai-center mt-4">
                <span class="label fs-13">Cambio:</span>
                <span id="efectivoCambio" class="font-bold font-mono fs-16 text-accent">0,00 €</span>
            </div>
        </div>
        <div class="modal-footer">
            <button onclick="cerrarModalCliente()" class="btn-cancel">Cancelar</button>
            <button id="confirmarClienteBtn" onclick="confirmarCliente()" class="btn-save">Cobrar</button>
        </div>
    </div>
</div>

<!-- MODAL: NÚMERO DE SERIE -->
<div class="modal-overlay" id="serialModal">
    <div class="modal modal-content gap-16 ai-stretch w-400">
        <div class="modal-icon text-accent bg-blue-light">
            <i class="fa-solid fa-barcode"></i>
        </div>
        <div class="modal-title text-center">Control de Números de Serie</div>
        <div class="modal-sub">Este producto requiere registrar un número de serie para continuar.</div>
        <div id="serialInputsContainer" class="d-flex flex-column gap-12 max-h-300 overflow-y-auto pr-8">
            <!-- Dinámico -->
        </div>
        <div class="modal-footer full-width mt-12">
            <button id="confirmSerialBtn" class="btn-save py-12">Confirmar y Continuar</button>
        </div>
    </div>
</div>


