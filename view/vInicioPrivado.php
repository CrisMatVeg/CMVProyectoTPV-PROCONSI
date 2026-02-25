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
    
    <form method="post" action="index.php" style="margin-left: 10px;">
        <input type="hidden" name="paginaAnterior" value="Login">
        <button type="submit" name="atras" class="btn-clear" style="color: var(--red); font-size: 11px;">Salir</button>
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
        </div>

        <div class="cat-tabs" id="catTabs">
            <button class="cat-tab active" data-cat="all">Todo</button>
            <button class="cat-tab" data-cat="audio">Audio</button>
            <button class="cat-tab" data-cat="movil">Móvil</button>
            <button class="cat-tab" data-cat="gaming">Gaming</button>
            <button class="cat-tab" data-cat="informatica">Informática</button>
            <button class="cat-tab" data-cat="cables">Cables y Cargadores</button>
            <button class="cat-tab" data-cat="foto">Foto y Video</button>
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
                <button
                    class="pay-btn"
                    data-method="bizum"
                    onclick="selectPayment(this)">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8">
                        <rect x="5" y="2" width="14" height="20" rx="2" />
                        <path d="M12 18h.01" />
                    </svg>
                    Bizum
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

<div class="modal-overlay" id="modalOverlay">
    <div class="modal">
        <div class="modal-icon success">✅</div>
        <div class="modal-title">Pago completado</div>
        <div class="modal-amount" id="modalAmount">0,00 €</div>
        <div class="modal-sub" id="modalSub">
            Pago con efectivo · Ticket #0001
        </div>
        <button class="modal-close" onclick="closeModal()">Nueva venta</button>
    </div>
</div>