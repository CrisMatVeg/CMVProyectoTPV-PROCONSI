<div class="topbar-user">
    <div class="avatar">
        <?php 
            // Sacamos las iniciales del nombre
            $nombres = explode(" ", $avInicioPrivado["nombre_completo"]);
            echo strtoupper(substr($nombres[0], 0, 1) . (isset($nombres[1]) ? substr($nombres[1], 0, 1) : ""));
        ?>
    </div>
    <span style="font-size: 13px">
        <?php echo $avInicioPrivado["nombre_completo"]; ?> 
        <small>(<?php echo $avInicioPrivado["rol"]; ?>)</small>
    </span>
    
    <?php if ($avInicioPrivado['esAdmin']): ?>
    <form method="post" action="index.php" style="margin-left: 8px;">
        <input type="hidden" name="paginaAnterior" value="cierreCaja">
        <button type="submit" name="irCierreCaja" class="cat-tab"
            style="padding: 5px 12px; font-size: 11px; display: flex; align-items: center; gap: 4px;">
            🏦 Cierre de caja
        </button>
    </form>
    <?php endif; ?>

    <form method="post" action="index.php" style="margin-left: 6px;">
        <input type="hidden" name="paginaAnterior" value="Login">
        <button type="submit" name="atras" class="cat-tab" style="padding: 5px 12px; font-size: 11px; color: var(--red); border-color: var(--red); background: var(--red-light); display: flex; align-items: center; gap: 4px;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
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
                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2">
                    <circle cx="11" cy="11" r="8" />
                    <path d="m21 21-4.35-4.35" />
                </svg>
                <input
                    class="search-input"
                    type="text"
                    id="searchInput"
                    placeholder="Buscar producto o referencia…" />
            </div>
            <?php if ($avInicioPrivado['esAdmin']): ?>
            <button onclick="abrirModalNuevoProducto()" class="cat-tab"
                style="white-space: nowrap; padding: 7px 14px; background: var(--accent); color: #fff;
                       border-color: var(--accent); display: flex; align-items: center; gap: 5px;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
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
            <button class="cat-tab" data-cat="baja" style="color: var(--red); border-color: var(--red-light); background: var(--red-light);">📉 De Baja</button>
        </div>

        <div class="products-grid" id="productsGrid"></div>
    </div>

    <!-- ORDER PANEL -->
    <div class="order-panel">
        <div class="order-header">
            <div style="display: flex; align-items: center; gap: 10px">
                <span class="order-title">Pedido</span>
                <span class="order-count" id="orderCount">0</span>
            </div>
            <button class="btn-clear" onclick="clearCart()">Vaciar</button>
        </div>

        <div class="order-items" id="orderItems">
            <div class="empty-cart" id="emptyCart">
                <div class="empty-cart-icon">🛒</div>
                <div>Añade productos<br />al pedido</div>
            </div>
        </div>

        <div class="order-totals">
            <div class="total-row">
                <span>Subtotal</span>
                <span id="subtotal">0,00 €</span>
            </div>
            <div
                class="total-row"
                id="discountRow"
                style="display: none; color: var(--green)">
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
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8">
                        <rect x="2" y="6" width="20" height="12" rx="2" />
                        <path d="M22 10H2" />
                        <path d="M22 14H2" />
                    </svg>
                    Efectivo
                </button>
                <button
                    class="pay-btn"
                    data-method="tarjeta"
                    onclick="selectPayment(this)">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8">
                        <rect x="2" y="5" width="20" height="14" rx="2" />
                        <path d="M2 10h20" />
                    </svg>
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
    <div class="modal" style="gap: 14px; align-items: stretch">
        <div
            style="
            display: flex;
            justify-content: space-between;
            align-items: center;
          ">
            <div class="modal-title" style="font-size: 16px">Editar producto</div>
            <button
                onclick="
              document.getElementById('editModal').classList.remove('visible')
            "
                style="
              background: none;
              border: none;
              font-size: 20px;
              cursor: pointer;
              color: var(--text-muted);
            ">
                ×
            </button>
        </div>
        <input type="hidden" id="editId" />
        <div style="display: grid; gap: 10px">
            <label
                style="
              font-size: 12px;
              font-weight: 600;
              color: var(--text-muted);
              letter-spacing: 0.06em;
              text-transform: uppercase;
            ">Emoji
                <input
                    id="editEmoji"
                    class="edit-field"
                    style="width: 80px; margin-left: 8px" />
            </label>
            <label
                style="
              font-size: 12px;
              font-weight: 600;
              color: var(--text-muted);
              letter-spacing: 0.06em;
              text-transform: uppercase;
              display: block;
            ">Nombre
                <input
                    id="editName"
                    class="edit-field"
                    style="width: 100%; margin-top: 4px" />
            </label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px">
                <label
                    style="
                font-size: 12px;
                font-weight: 600;
                color: var(--text-muted);
                letter-spacing: 0.06em;
                text-transform: uppercase;
                display: block;
              ">Referencia
                    <input
                        id="editSku"
                        class="edit-field"
                        style="
                  width: 100%;
                  margin-top: 4px;
                  font-family:DM Mono, monospace;
                " />
                </label>
                <label
                    style="
                font-size: 12px;
                font-weight: 600;
                color: var(--text-muted);
                letter-spacing: 0.06em;
                text-transform: uppercase;
                display: block;
              ">Precio (€)
                    <input
                        id="editPrice"
                        class="edit-field"
                        type="number"
                        step="0.01"
                        style="
                  width: 100%;
                  margin-top: 4px;
                  font-family:DM Mono, monospace;
                " />
                </label>
            </div>
        </div>
        <button
            class="modal-close"
            onclick="saveEdit()"
            style="margin-top: 4px">
            Guardar cambios
        </button>
    </div>
</div>

<!-- ADD MODAL -->
<div class="modal-overlay" id="addModal">
    <div class="modal" style="gap: 14px; align-items: stretch">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div class="modal-title" style="font-size: 16px">Nuevo producto</div>
            <button onclick="document.getElementById('addModal').classList.remove('visible')"
                style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--text-muted);">
                ×
            </button>
        </div>
        <div style="display: grid; gap: 10px">
            <label style="font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Emoji / Icono
                <input id="addEmoji" class="edit-field" style="width: 100%; margin-top: 4px" placeholder="📦" />
            </label>
            <label style="font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Nombre
                <input id="addName" class="edit-field" style="width: 100%; margin-top: 4px" placeholder="Nombre completo" />
            </label>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px">
                <label style="font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Referencia (SKU)
                    <input id="addSku" class="edit-field" style="width: 100%; margin-top: 4px; font-family: 'DM Mono', monospace;" placeholder="PRO-001" />
                </label>
                <label style="font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Precio (€)
                    <input id="addPrice" class="edit-field" type="number" step="0.01" style="width: 100%; margin-top: 4px; font-family: 'DM Mono', monospace;" placeholder="0.00" />
                </label>
            </div>
            <label style="font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Categoría
                <select id="addCat" class="edit-field" style="width: 100%; margin-top: 4px; background: var(--surface2)">
                    <option value="audio">Audio</option>
                    <option value="movil">Móvil</option>
                    <option value="gaming">Gaming</option>
                    <option value="informatica">Informática</option>
                    <option value="cables">Cables y Cargadores</option>
                    <option value="foto">Foto y Video</option>
                </select>
            </label>
        </div>
        <button class="modal-close" onclick="guardarNuevoProducto()" style="margin-top: 4px">
            Crear producto
        </button>
    </div>
</div>

<!-- DELETE CONFIRM MODAL -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="gap: 16px">
        <div
            class="modal-icon"
            style="background: var(--red-light); font-size: 26px">
            🗑️
        </div>
        <div class="modal-title">Eliminar producto</div>
        <div class="modal-sub">
            ¿Seguro que quieres eliminar <strong id="delName"></strong>? Esta
            acción no se puede deshacer.
        </div>
        <div style="display: flex; gap: 10px; width: 100%">
            <button
                onclick="
              document.getElementById('deleteModal').classList.remove('visible')
            "
                style="
              flex: 1;
              padding: 12px;
              border-radius: 8px;
              border: 1.5px solid var(--border);
              background: var(--surface);
              font-family:DM Sans, sans-serif;
              font-size: 14px;
              cursor: pointer;
            ">
                Cancelar
            </button>
            <button
                id="delConfirmBtn"
                style="
              flex: 1;
              padding: 12px;
              border-radius: 8px;
              border: none;
              background: var(--red);
              color: #fff;
              font-family:DM Sans, sans-serif;
              font-size: 14px;
              font-weight: 600;
              cursor: pointer;
            ">
                Eliminar
            </button>
        </div>
    </div>
</div>

<!-- TOAST -->
<div id="toast"></div>

<!-- MODAL 1: TIPO DE CLIENTE (aparece al pulsar Cobrar) -->
<div class="modal-overlay" id="clienteModal">
    <div class="modal" style="gap: 18px; align-items: stretch; width: 360px;">
        <div class="modal-title" style="font-size: 18px; text-align: center;">¿Tipo de cliente?</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <button id="btnParticular" onclick="seleccionarTipoCliente('particular')"
                style="padding: 20px 10px; border-radius: 12px; border: 2px solid var(--border);
                       background: var(--surface2); cursor: pointer; font-size: 14px; font-family: 'DM Mono', sans-serif;
                       display: flex; flex-direction: column; align-items: center; gap: 8px; transition: all 0.15s;">
                <span style="font-size: 28px;">👤</span>
                Particular
            </button>
            <button id="btnEmpresa" onclick="seleccionarTipoCliente('empresa')"
                style="padding: 20px 10px; border-radius: 12px; border: 2px solid var(--border);
                       background: var(--surface2); cursor: pointer; font-size: 14px; font-family: 'DM Mono', sans-serif;
                       display: flex; flex-direction: column; align-items: center; gap: 8px; transition: all 0.15s;">
                <span style="font-size: 28px;">🏢</span>
                Empresa
            </button>
        </div>
        <!-- Datos de empresa (ocultos por defecto) -->
        <div id="empresaDatos" style="display: none; flex-direction: column; gap: 8px;">
            <label style="font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em;">
                Razón social
                <input id="empresaNombre" class="edit-field" placeholder="Nombre de la empresa" style="width: 100%; margin-top: 4px;" />
            </label>
            <label style="font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em;">
                CIF / NIF
                <input id="empresaNif" class="edit-field" placeholder="B12345678" style="width: 100%; margin-top: 4px;" />
            </label>
        </div>
        <!-- Gestión de Efectivo (Solo si metodo_pago es efectivo) -->
        <div id="efectivoGestion" style="display: none; flex-direction: column; gap: 8px; padding: 12px; background: var(--surface2); border-radius: 8px; border: 1px solid var(--border);">
            <div style="font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Pago en efectivo</div>
            <label style="font-size: 13px; font-weight: 500;">
                Importe recibido
                <input id="efectivoRecibido" type="number" step="0.01" class="edit-field" placeholder="0.00" style="width: 100%; margin-top: 4px; font-family: 'DM Mono', monospace; font-size: 16px;" oninput="calcularCambio()" />
            </label>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                <span style="font-size: 13px; color: var(--text-muted);">Cambio a devolver:</span>
                <span id="efectivoCambio" style="font-family: 'DM Mono', monospace; font-weight: 700; font-size: 16px; color: var(--accent);">0,00 €</span>
            </div>
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="document.getElementById('clienteModal').classList.remove('visible')"
                style="flex: 1; padding: 12px; border-radius: 8px; border: 1.5px solid var(--border);
                       background: var(--surface); font-family: 'DM Mono', sans-serif; font-size: 14px; cursor: pointer;">
                Cancelar
            </button>
            <button id="confirmarClienteBtn" onclick="confirmarCliente()"
                style="flex: 2; padding: 12px; border-radius: 8px; border: none; background: var(--accent);
                       color: #fff; font-family: 'DM Mono', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer;">
                Cobrar
            </button>
        </div>
    </div>
</div>

<!-- MODAL 2: TICKET / FACTURA (aparece tras guardar la venta en BD) -->
<div class="modal-overlay" id="ticketModal">
    <div class="modal" id="ticketContenido"
         style="gap: 0; align-items: stretch; width: 380px; padding: 0; border-radius: 12px; overflow: hidden;">
        <!-- Encabezado de la tienda -->
        <div style="background: var(--accent); color: #fff; padding: 20px 24px; text-align: center;">
            <div style="font-size: 18px; font-weight: 700; letter-spacing: 0.04em;">⚡ ElectroBazar</div>
            <div style="font-size: 11px; opacity: 0.8; margin-top: 4px;">C/ Tecnología 24, 28001 Madrid · NIF: B87654321</div>
        </div>

        <!-- Meta del ticket -->
        <div style="padding: 16px 24px; border-bottom: 1px dashed var(--border); display: grid; grid-template-columns: 1fr 1fr; gap: 4px; font-size: 12px;">
            <span id="tkTipoDoc" style="font-weight: 700; font-size: 14px; grid-column: 1/-1; margin-bottom: 4px;">TICKET DE VENTA</span>
            <span style="color: var(--text-muted);">Nº Ticket</span>     <span id="tkNumero" style="font-family: 'DM Mono', monospace; font-weight: 600;">#—</span>
            <span style="color: var(--text-muted);">Fecha</span>         <span id="tkFecha">—</span>
            <span style="color: var(--text-muted);">Cajero</span>        <span id="tkCajero">—</span>
            <span style="color: var(--text-muted);">Pago</span>          <span id="tkMetodo">—</span>
            <!-- Datos empresa (si aplica) -->
            <span id="tkLabelCliente" style="color: var(--text-muted); display: none;">Cliente</span>
            <span id="tkNombreCliente" style="display: none;"></span>
            <span id="tkLabelNif" style="color: var(--text-muted); display: none;">CIF/NIF</span>
            <span id="tkNifCliente" style="display: none; font-family: 'DM Mono', monospace;"></span>
        </div>

        <!-- Líneas de productos -->
        <div id="tkLineas" style="padding: 12px 24px; border-bottom: 1px dashed var(--border); max-height: 220px; overflow-y: auto; font-size: 13px;"></div>

        <!-- Totales -->
        <div style="padding: 12px 24px; border-bottom: 1px dashed var(--border); display: grid; gap: 4px; font-size: 13px;">
            <div style="display: flex; justify-content: space-between; color: var(--text-muted);">
                <span>Subtotal</span><span id="tkSubtotal">—</span>
            </div>
            <div id="tkDescRow" style="display: none; justify-content: space-between; color: var(--green);">
                <span id="tkDescLabel">Descuento</span><span id="tkDescAmt">—</span>
            </div>
            <div style="display: flex; justify-content: space-between; color: var(--text-muted);">
                <span>Base imponible</span><span id="tkBase">—</span>
            </div>
            <div style="display: flex; justify-content: space-between; color: var(--text-muted);">
                <span>IVA (21%)</span><span id="tkIva">—</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: 700; margin-top: 6px; padding-top: 8px; border-top: 1.5px solid var(--border);">
                <span>TOTAL</span><span id="tkTotal" style="font-family: 'DM Mono', monospace;">—</span>
            </div>
            <!-- Detalles de efectivo en ticket -->
            <div id="tkEfectivoRow" style="display: none; flex-direction: column; gap: 2px; margin-top: 8px; padding-top: 8px; border-top: 1px dashed var(--border); font-size: 12px; color: var(--text-muted);">
                <div style="display: flex; justify-content: space-between;">
                    <span>Entregado</span><span id="tkEntregado">—</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Cambio</span><span id="tkCambio">—</span>
                </div>
            </div>
        </div>

        <!-- Acciones -->
        <div style="padding: 16px 24px; display: flex; gap: 8px;">
            <button onclick="imprimirTicket()"
                style="flex: 1; padding: 11px; border-radius: 8px; border: 1.5px solid var(--border);
                       background: var(--surface); font-family: 'DM Mono', sans-serif; font-size: 13px; cursor: pointer;">
                🖨️ Imprimir
            </button>
            <button onclick="nuevaVenta()"
                style="flex: 2; padding: 11px; border-radius: 8px; border: none; background: var(--accent);
                       color: #fff; font-family: 'DM Mono', sans-serif; font-size: 14px; font-weight: 600; cursor: pointer;">
                Nueva venta →
            </button>
        </div>
    </div>
</div>