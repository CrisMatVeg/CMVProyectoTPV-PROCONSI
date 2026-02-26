<div class="topbar-user">
    <div class="avatar">
        <?php 
            // Sacamos las iniciales del nombre
            $nombres = explode(" ", $avInicioPrivado["nombre_completo"]);
            echo strtoupper(substr($nombres[0], 0, 1) . (isset($nombres[1]) ? substr($nombres[1], 0, 1) : ""));
        ?>
    </div>
    <span class="fs-13">
        <?php echo $avInicioPrivado["nombre_completo"]; ?> 
        <small>(<?php echo $avInicioPrivado["rol"]; ?>)</small>
    </span>

    <form method="post" action="index.php" class="ml-12">
        <input type="hidden" name="paginaAnterior" value="TPV">
        <button type="submit" name="volver" class="cat-tab p-5-12 fs-11 d-flex ai-center gap-6">
            <i class="fa-solid fa-house"></i> Dashboard
        </button>
    </form>
    
    <?php if ($avInicioPrivado['esAdmin']): ?>
    <form method="post" action="index.php" class="ml-8">
        <input type="hidden" name="paginaAnterior" value="cierreCaja">
        <button type="submit" name="irCierreCaja" class="cat-tab p-5-12 fs-11 d-flex ai-center gap-6">
            <i class="fa-solid fa-vault"></i> Cierre de caja
        </button>
    </form>
    <?php endif; ?>

    <form method="post" action="index.php" class="ml-6">
        <input type="hidden" name="paginaAnterior" value="Login">
        <button type="submit" name="atras" class="cat-tab p-5-12 fs-11 text-red border-red bg-red-light d-flex ai-center gap-6">
            <i class="fa-solid fa-right-from-bracket"></i>
            Salir
        </button>
    </form>
</div>
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
                    placeholder="Buscar producto o referencia…" />
            </div>
            <?php if ($avInicioPrivado['esAdmin']): ?>
            <button onclick="abrirModalNuevoProducto()" class="btn-add p-7-14 fs-13">
                <i class="fa-solid fa-plus"></i>
                Nuevo producto
            </button>
            <?php endif; ?>
        </div>

        <div class="cat-tabs" id="catTabs">
            <button class="cat-tab active" data-cat="all">Todo</button>
            <button class="cat-tab" data-cat="audio">Audio</button>
            <button class="cat-tab" data-cat="movil">Móvil</button>
            <button class="cat-tab" data-cat="gaming">Gaming</button>
            <button class="cat-tab" data-cat="informatica">Informática</button>
            <button class="cat-tab" data-cat="cables">Cables y Cargadores</button>
            <button class="cat-tab" data-cat="foto">Foto y Video</button>
            <button class="cat-tab" data-cat="baja" class="text-red border-red-light bg-red-light d-inline-flex ai-center gap-6">
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
            <div class="d-flex gap-10 ai-flex-end">
                <div class="form-group">
                    <label class="form-label">Emoji</label>
                    <input id="editEmoji" class="form-input text-center w-80 fs-18" />
                </div>
                <div class="form-group flex-1">
                    <label class="form-label">Nombre</label>
                    <input id="editName" class="form-input" />
                </div>
            </div>
            <div class="form-group-wrap grid-2">
                <div class="form-group">
                    <label class="form-label">Referencia</label>
                    <input id="editSku" class="form-input font-mono" />
                </div>
                <div class="form-group">
                    <label class="form-label">Precio (€)</label>
                    <input id="editPrice" class="form-input font-mono text-right" type="number" step="0.01" />
                </div>
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
                <label class="form-label">Emoji / Icono</label>
                <input id="addEmoji" class="form-input text-center fs-18" placeholder="📦" />
            </div>
            <div class="form-group">
                <label class="form-label">Nombre</label>
                <input id="addName" class="form-input" placeholder="Nombre completo" />
            </div>
            <div class="form-group-wrap grid-2">
                <div class="form-group">
                    <label class="form-label">Referencia (SKU)</label>
                    <input id="addSku" class="form-input font-mono" placeholder="PRO-001" />
                </div>
                <div class="form-group">
                    <label class="form-label">Precio (€)</label>
                    <input id="addPrice" class="form-input font-mono text-right" type="number" step="0.01" placeholder="0.00" />
                </div>
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

<!-- TOAST -->
<div id="toast"></div>

<!-- MODAL 1: TIPO DE CLIENTE (aparece al pulsar Cobrar) -->
<div class="modal-overlay" id="clienteModal">
    <div class="modal modal-content gap-18 ai-stretch w-360">
        <div class="modal-title text-center fs-18">¿Tipo de cliente?</div>
        <div class="grid-2 gap-12 mb-12">
            <button id="btnParticular" onclick="seleccionarTipoCliente('particular')"
                class="p-20-10 br-12 border-2 bg-surface2 cursor-pointer fs-14 d-flex flex-column ai-center gap-12 active-scale">
                <i class="fa-solid fa-user fs-28"></i>
                Particular
            </button>
            <button id="btnEmpresa" onclick="seleccionarTipoCliente('empresa')"
                class="p-20-10 br-12 border-2 bg-surface2 cursor-pointer fs-14 d-flex flex-column ai-center gap-12 active-scale">
                <i class="fa-solid fa-building fs-28"></i>
                Empresa
            </button>
        </div>
        <!-- Datos de empresa -->
        <div id="empresaDatos" class="d-none flex-column gap-8">
            <div class="form-group">
                <label class="form-label">Razón social</label>
                <input id="empresaNombre" class="form-input" placeholder="Nombre de la empresa" />
            </div>
            <div class="form-group">
                <label class="form-label">CIF / NIF</label>
                <input id="empresaNif" class="form-input font-mono" placeholder="B12345678" />
            </div>
        </div>
        <!-- Gestión de Efectivo -->
        <div id="efectivoGestion" class="d-none flex-column gap-8 p-12 bg-surface2 br-8 border-2">
            <div class="form-label">Pago en efectivo</div>
            <div class="form-group">
                <label class="fs-13 font-bold">Importe recibido</label>
                <input id="efectivoRecibido" type="number" step="0.01" class="form-input font-mono fs-16 text-right" oninput="calcularCambio()" />
            </div>
            <div class="d-flex jc-space-between ai-center mt-4">
                <span class="label fs-13">Cambio:</span>
                <span id="efectivoCambio" class="font-bold font-mono fs-16 text-accent">0,00 €</span>
            </div>
        </div>
        <div class="modal-footer">
            <button onclick="document.getElementById('clienteModal').classList.remove('visible')" class="btn-cancel">Cancelar</button>
            <button id="confirmarClienteBtn" onclick="confirmarCliente()" class="btn-save">Cobrar</button>
        </div>
    </div>
</div>

<!-- MODAL 2: TICKET / FACTURA -->
<div class="modal-overlay" id="ticketModal">
    <div class="modal ticket-wrapper" id="ticketContenido">
        <div class="ticket-brand-header">
            <div class="title"><i class="fa-solid fa-bolt-lightning"></i> ElectroBazar</div>
            <div class="info">C/ Tecnología 24, 28001 Madrid · NIF: B87654321</div>
        </div>

        <div class="ticket-meta">
            <span id="tkTipoDoc" class="doc-type">TICKET DE VENTA</span>
            <span class="label">Nº Ticket</span> <span id="tkNumero" class="value">#—</span>
            <span class="label">Fecha</span> <span id="tkFecha">—</span>
            <span class="label">Cajero</span> <span id="tkCajero">—</span>
            <span class="label">Pago</span> <span id="tkMetodo">—</span>
            <!-- Empresa -->
            <span id="tkLabelCliente" class="label d-none">Cliente</span>
            <span id="tkNombreCliente" class="d-none"></span>
            <span id="tkLabelNif" class="label d-none">CIF/NIF</span>
            <span id="tkNifCliente" class="d-none font-mono"></span>
        </div>

        <div id="tkLineas" class="ticket-items"></div>

        <div class="ticket-totals">
            <div class="ticket-total-row label text-muted">
                <span>Subtotal</span><span id="tkSubtotal">—</span>
            </div>
            <div id="tkDescRow" class="ticket-total-row d-none text-green">
                <span id="tkDescLabel">Descuento</span><span id="tkDescAmt">—</span>
            </div>
            <div class="ticket-total-row label text-muted">
                <span>Base imponible</span><span id="tkBase">—</span>
            </div>
            <div class="ticket-total-row label text-muted">
                <span>IVA (21%)</span><span id="tkIva">—</span>
            </div>
            <div class="ticket-total-row ticket-total-main">
                <span>TOTAL</span><span id="tkTotal" class="font-mono">—</span>
            </div>
            <!-- Efectivo -->
            <div id="tkEfectivoRow" class="d-none flex-column gap-4 mt-8 pt-8 border-top text-muted fs-12">
                <div class="ticket-total-row"><span>Entregado</span><span id="tkEntregado">—</span></div>
                <div class="ticket-total-row"><span>Cambio</span><span id="tkCambio">—</span></div>
            </div>
        </div>

        <div class="ticket-email-section">
            <label class="form-label fs-11">Enviar por email</label>
            <div class="d-flex gap-8">
                <input type="email" id="tkEmailInput" placeholder="cliente@ejemplo.com" class="form-input font-mono fs-13">
                <button onclick="enviarTicketEmail()" id="btnSendEmail" class="btn-filter h-40 p-0-20 bg-blue">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </div>
        </div>

        <div class="modal-footer p-0-24-20">
            <button onclick="imprimirTicket()" class="btn-cancel d-flex ai-center jc-center gap-8">
                <i class="fa-solid fa-print"></i> Ticket
            </button>
            <button onclick="nuevaVenta()" class="btn-save">Nueva venta</button>
        </div>
    </div>
</div>
