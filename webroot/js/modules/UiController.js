/**
 * UiController.js
 * Manages DOM updates, visibility, and global UI state.
 */

import { AppConfig, AppState } from './AppConfig.js';
import { Utils } from './Utils.js';

export const UiController = {
  
  /**
   * Modal Management
   */
  openModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.add("visible");
  },

  closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove("visible");
  },

  /**
   * Theme Management
   */
  setMode(mode) {
    document.body.dataset.themeMode = mode;
    localStorage.setItem("theme-mode", mode);
    
    // Update UI buttons
    const modeButtons = { light: "themeModeLight", dark: "themeModeDark", black: "themeModeBlack" };
    Object.entries(modeButtons).forEach(([m, id]) => {
      const btn = document.getElementById(id);
      if (btn) btn.classList.toggle("active", m === mode);
    });
  },

  setAccent(accent) {
    document.body.dataset.themeAccent = accent;
    localStorage.setItem("theme-accent", accent);
  },

  initSidebarResizer() {
    const resizer = document.getElementById("sidebarResizer");
    const sidebar = document.querySelector(".order-panel");
    if (!resizer || !sidebar) return;

    let isResizing = false;
    resizer.addEventListener("mousedown", (e) => {
        isResizing = true;
        document.body.classList.add("is-resizing");
    });

    document.addEventListener("mousemove", (e) => {
        if (!isResizing) return;
        const width = window.innerWidth - e.clientX;
        if (width >= 350 && width <= 600) {
            sidebar.style.width = width + "px";
        }
    });

    document.addEventListener("mouseup", () => {
        isResizing = false;
        document.body.classList.remove("is-resizing");
    });
  },

  /**
   * Product Catalog Rendering
   */
  renderProducts() {
    const grid = document.getElementById("productsGrid");
    if (!grid) return;

    let filtered = AppConfig.products.filter((p) => {
      let matchesCat = (AppState.activeCat === "all") || (AppState.activeCat === "baja" && p.inactive) || (!p.inactive && p.cat === AppState.activeCat);
      if (!matchesCat) return false;

      const matchesSearch = p.name.toLowerCase().includes(AppState.searchTerm.toLowerCase()) || p.codigo.toLowerCase().includes(AppState.searchTerm.toLowerCase());
      if (!matchesSearch) return false;

      const matchesPrice = AppState.maxPrice === 999999 ? true : (p.price >= AppState.minPrice && p.price <= AppState.maxPrice);
      if (!matchesPrice) return false;

      let matchesStock = true;
      if (AppState.stockFilter === "in-stock") matchesStock = p.stock > 0;
      else if (AppState.stockFilter === "low-stock") matchesStock = p.stock > 0 && p.stock <= 5;
      if (!matchesStock) return false;

      return true;
    });

    // Sorting
    filtered.sort((a, b) => {
      switch (AppState.sortOrder) {
        case "name-asc": return a.name.localeCompare(b.name);
        case "name-desc": return b.name.localeCompare(a.name);
        case "price-asc": return a.price - b.price;
        case "price-desc": return b.price - a.price;
        default: return 0;
      }
    });

    grid.innerHTML = filtered.map(p => this.templates.productCard(p)).join("");
    grid.classList.toggle("is-admin", AppConfig.isAdmin);
  },

  renderCart(totals) {
    const items = Object.values(AppState.cart);
    const container = document.getElementById("orderItems");
    const orderCountEl = document.getElementById("orderCount");

    if (!container || !orderCountEl) return;
    orderCountEl.textContent = items.reduce((a, b) => a + b.qty, 0);

    if (!items.length) {
      container.innerHTML = this.templates.emptyCart();
      this.updateTotalsUI({ subtotal: 0, totalDiscount: 0, totalBase: 0, totalTax: 0, total: 0, breakdown: {} });
      return;
    }

    container.innerHTML = items.map(item => this.templates.cartItem(item)).join("");
    this.updateTotalsUI(totals);
  },

  updateTotalsUI(totals) {
    document.getElementById("subtotal").textContent = Utils.fmt2(totals.totalBase);
    document.getElementById("totalAmt").textContent = Utils.fmt2(totals.total);
    document.getElementById("chargeTotal").textContent = Utils.fmt2(totals.total);
    document.getElementById("discountAmt").textContent = "-" + Utils.fmt2(totals.totalDiscount);
    
    // Render dynamic breakdown
    const breakdownEl = document.getElementById("ivaBreakdown");
    if (breakdownEl) {
        if (!totals.breakdown || Object.keys(totals.breakdown).length === 0) {
            breakdownEl.innerHTML = "";
        } else {
            breakdownEl.innerHTML = Object.entries(totals.breakdown).map(([rate, vals]) => `
                <div class="total-row fs-12 opacity-80" style="margin-top: 4px; border-bottom: 1px dashed rgba(0,0,0,0.05); padding-bottom: 2px;">
                    <span style="font-weight: 600;">Base imponible (${rate}% iva):</span>
                    <span>${Utils.fmt2(vals.base)}</span>
                </div>
                <div class="total-row fs-12 opacity-80">
                    <span style="font-weight: 600;">Total IVA (${rate}%):</span>
                    <span>${Utils.fmt2(vals.tax)}</span>
                </div>
            `).join("");
        }
    }

    const discRow = document.getElementById("discountRow");
    if (discRow) discRow.style.display = totals.totalDiscount > 0 ? "flex" : "none";
    
    const chargeBtn = document.getElementById("chargeBtn");
    if (chargeBtn) chargeBtn.disabled = totals.subtotal === 0;
  },

  templates: {
    productCard(p) {
        const effectivePrice = AppState.getEffectivePrice ? AppState.getEffectivePrice(p) : p.price;
        const priceHtml = Math.abs(effectivePrice - p.price) > 0.01 
            ? `<span style="text-decoration:line-through; font-size:0.8em; opacity:0.6; margin-right:4px;">${Utils.fmt2(p.price)}</span> ${Utils.fmt2(effectivePrice)}`
            : Utils.fmt2(p.price);

        return `
          <div class="product-card${p.inactive ? " inactive" : ""}${p.stock <= 0 ? " out-of-stock" : ""}" id="card-${p.id}" onclick="app.handleProductClick(event, ${p.id}, this)">
              ${p.inactive ? '<div class="baja-pill">Baja</div>' : ""}
              ${p.stock <= 0 ? '<div class="stock-pill" style="background:var(--red); color:white; position:absolute; top:10px; right:10px; padding:2px 8px; border-radius:10px; font-size:10px; font-weight:700;">AGOTADO</div>' : ""}
              <div class="product-icon" style="background: white; border-radius: 8px;">
                  ${p.icono && p.icono.startsWith("data:image") ? `<img src="${p.icono}" class="prod-img-tpv" alt="${p.name}">` : `<span class="product-emoji">${p.icono}</span>`}
              </div>
              <div>
                  <div class="product-name">${p.name}</div>
                  <div class="product-sku">${p.codigo}</div>
                  <div class="product-stock" style="font-size:11px; color:${p.stock <= 5 ? "var(--red)" : "var(--text-muted)"}; font-weight:600;">Stock: ${p.stock}</div>
              </div>
              <div class="product-price" style="margin-top:auto">${priceHtml}</div>
              <div class="product-admin-bar">
                  <button class="admin-action edit" onclick="app.editProduct(event,${p.id})"><i class="fa-solid fa-pen-to-square"></i> Editar</button>
                  <button class="admin-action delete" onclick="app.deleteProduct(event,${p.id})"><i class="fa-solid fa-trash"></i> Borrar</button>
                  <button class="admin-action baja" onclick="app.toggleBaja(event,${p.id})"><i class="fa-solid ${p.inactive ? "fa-arrow-up" : "fa-arrow-down"}"></i> ${p.inactive ? "Alta" : "Baja"}</button>
              </div>
          </div>`;
    },
    cartItem(item) {
        return `
          <div class="order-item">
            <span class="order-item-emoji">
              ${item.icono && item.icono.startsWith("data:image") ? `<img src="${item.icono}" class="order-item-img" alt="${item.name}">` : item.icono}
            </span>
            <div class="order-item-info">
              <div class="order-item-name">${item.name}</div>
              <div class="order-item-price">${Utils.fmt2(item.price)} × ${item.qty}</div>
            </div>
            <div class="qty-ctrl">
              <button class="qty-btn" onclick="app.changeQty('${item.cartKey}', -1)"><i class="fa-solid fa-minus"></i></button>
              <input type="number" class="qty-input" value="${item.qty}" min="0" onchange="app.setQty('${item.cartKey}', this.value)" onfocus="this.select()">
              <button class="qty-btn" onclick="app.changeQty('${item.cartKey}', +1)"><i class="fa-solid fa-plus"></i></button>
            </div>
            <div class="order-item-total">${Utils.fmt2(item.price * item.qty)}</div>
          </div>`;
    },
    emptyCart() {
        return `
          <div class="empty-cart">
            <div class="empty-cart-icon"><i class="fa-solid fa-cart-shopping"></i></div>
            <div>Añade productos<br>al pedido</div>
          </div>`;
    }
  }
};
