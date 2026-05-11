/**
 * UiController.js
 * Manages DOM updates, visibility, and global UI state.
 */

import { AppConfig, AppState } from './AppConfig.js';
import { Utils, FocusTrap } from './Utils.js';

export const UiController = {
  
  /**
   * Modal Management
   */
  openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
      modal.classList.add("visible");
      // FocusTrap is activated via MutationObserver (initModalAccessibility)
    }
  },

  closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
      modal.classList.remove("visible");
      // FocusTrap is deactivated via MutationObserver (initModalAccessibility)
    }
  },

  /**
   * Initializes a global MutationObserver that auto-activates/deactivates
   * the FocusTrap for any .modal-overlay that gains/loses the 'visible' class.
   * This provides out-of-the-box keyboard accessibility for ALL modals.
   */
  initModalAccessibility() {
    // Map of overlayElement -> active trap handle
    const traps = new WeakMap();

    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.type !== 'attributes' || mutation.attributeName !== 'class') return;
        const overlay = mutation.target;
        if (!overlay.classList.contains('modal-overlay')) return;

        const isVisible = overlay.classList.contains('visible');
        const innerDialog = overlay.querySelector('[role="dialog"]') || overlay;

        if (isVisible && !traps.has(overlay)) {
          // Find a close trigger inside this modal (btn-close-modal or btn-cancel)
          const closeTrigger = overlay.querySelector('.btn-close-modal, .btn-cancel');
          const onClose = closeTrigger ? () => closeTrigger.click() : () => overlay.classList.remove('visible');
          const trap = FocusTrap.activate(innerDialog, onClose);
          if (trap) traps.set(overlay, trap);
        } else if (!isVisible && traps.has(overlay)) {
          FocusTrap.deactivate(traps.get(overlay));
          traps.delete(overlay);
        }
      });
    });

    // Observe all existing and future modal overlays
    document.querySelectorAll('.modal-overlay').forEach((el) => {
      observer.observe(el, { attributes: true });
    });

    // Also observe the document body for dynamically added modals
    const bodyObserver = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (node.nodeType === 1) {
            const overlays = node.classList?.contains('modal-overlay') ? [node] : Array.from(node.querySelectorAll?.('.modal-overlay') || []);
            overlays.forEach((el) => observer.observe(el, { attributes: true }));
          }
        });
      });
    });
    bodyObserver.observe(document.body, { childList: true, subtree: false });
  },

  /**
   * Comodín (Custom Product) Modal Handlers
   */
  openModalComodin() {
    const elDesc = document.getElementById("comodinDesc");
    const elPrice = document.getElementById("comodinPrice");
    const elIva = document.getElementById("comodinIva");
    const elErr = document.getElementById("comodinError");

    if (elDesc) elDesc.value = "";
    if (elPrice) elPrice.value = "";
    if (elIva) elIva.value = "21";
    if (elErr) elErr.classList.add("d-none");

    this.openModal("modalComodin");
    if (elDesc) setTimeout(() => elDesc.focus(), 100);
  },

  closeModalComodin() {
    this.closeModal("modalComodin");
  },

  /**
   * Theme Management
   */
  setMode(mode) {
    if (!mode) return;
    document.body.dataset.themeMode = mode;
    localStorage.setItem("theme-mode", mode);
    
    // Update hidden input for DB save
    const input = document.getElementById("theme_mode_input");
    if (input) input.value = mode;

    // Update UI buttons
    const modeButtons = { light: "themeModeLight", dark: "themeModeDark", black: "themeModeBlack" };
    Object.entries(modeButtons).forEach(([m, id]) => {
      const btn = document.getElementById(id);
      if (btn) btn.classList.toggle("active", m === mode);
    });
  },

  setAccent(accent) {
    if (!accent) return;
    document.body.dataset.themeAccent = accent;
    localStorage.setItem("theme-accent", accent);

    // Update hidden input for DB save
    const input = document.getElementById("theme_accent_input");
    if (input) input.value = accent;

    // Update UI buttons matching main.js definitions
    const accentButtons = { 
        blue: "themeAccentBlue", 
        green: "themeAccentGreen",
        red: "themeAccentRed",
        purple: "themeAccentPurple",
        amber: "themeAccentAmber",
        pink: "themeAccentPink",
        orange: "themeAccentOrange"
    };
    Object.entries(accentButtons).forEach(([a, id]) => {
      const btn = document.getElementById(id);
      if (btn) btn.classList.toggle("active", a === accent);
    });
  },

  setFont(font) {
    if (!font) return;
    document.body.dataset.themeFont = font;
    localStorage.setItem("theme-font", font);

    // Update hidden input for DB save
    const input = document.getElementById("theme_font_input");
    if (input) input.value = font;

    // Update UI buttons matching main.js definitions
    const fontButtons = { 
        "dm-mono": "themeFontMono", 
        "poppins": "themeFontPoppins", 
        "inter": "themeFontInter", 
        "outfit": "themeFontOutfit",
        "roboto": "themeFontRoboto",
        "system": "themeFontSystem"
    };
    Object.entries(fontButtons).forEach(([f, id]) => {
      const btn = document.getElementById(id);
      if (btn) btn.classList.toggle("active", f === font);
    });
  },

  applyTheme(mode, accent, font) {
    if (mode) this.setMode(mode);
    if (accent) this.setAccent(accent);
    if (font) this.setFont(font);
  },

  initSidebarResizer() {
    const resizer = document.getElementById("sidebarResizer");
    const sidebar = document.querySelector(".order-panel");
    if (!resizer || !sidebar) return;

    // Cargar ancho guardado
    const savedWidth = localStorage.getItem("tpv-sidebar-width");
    if (savedWidth) {
        sidebar.style.width = savedWidth + "px";
    }

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
            localStorage.setItem("tpv-sidebar-width", width);
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

    // We render the PRODUCTS array which is managed by ProductManager
    // but we can still apply secondary local filters (like tags or price ranges)
    let filtered = AppConfig.products.filter((p) => {
      // Packs category: show only packs; all other categories: exclude packs
      if (AppState.activeCat === "packs") {
        if (!p.es_pack) return false;
        // Apply search/price/stock filters but skip category matching
      } else {
        if (p.es_pack) return false;
        // Category filter (mostly server-side, but good to keep for consistency)
        let matchesCat = (AppState.activeCat === "all") ||
                         (AppState.activeCat === "baja" && p.inactive) ||
                         (!p.inactive && p.cat == AppState.activeCat);
        if (!matchesCat) return false;
      }

      // Local search refinement (if any)
      const matchesSearch = (p.name || "").toLowerCase().includes(AppState.searchTerm.toLowerCase()) || 
                           (p.codigo || "").toLowerCase().includes(AppState.searchTerm.toLowerCase());
      if (!matchesSearch) return false;

      const matchesPrice = (p.price >= AppState.minPrice && p.price <= AppState.maxPrice);
      if (!matchesPrice) return false;

      let matchesStock = true;
      if (AppState.stockFilter === "in-stock") matchesStock = p.stock > 0;
      else if (AppState.stockFilter === "low-stock") matchesStock = p.stock > 0 && p.stock <= 5;
      if (!matchesStock) return false;

      // Tag/Attribute Filter
      if (AppState.activeTag) {
        if (!p.atributos) return false;
        let attrs = [];
        try {
          attrs = typeof p.atributos === 'string' ? JSON.parse(p.atributos) : p.atributos;
        } catch (e) {
          // Fallback for simple comma-separated strings
          if (typeof p.atributos === 'string') {
            attrs = p.atributos.split(',').map(s => s.trim());
          }
        }
        
        if (!Array.isArray(attrs)) return false;

        // Flatten in case of nested arrays and normalize to uppercase for comparison
        const flatAttrs = attrs.flat(2).map(a => String(a).toUpperCase());
        const searchTag = String(AppState.activeTag).toUpperCase();
        
        if (!flatAttrs.includes(searchTag)) return false;
      }

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
    this.updatePostponeUI();

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

  updatePostponeUI() {
    const btnResume = document.getElementById("btnResumeSale");
    const btnPostpone = document.getElementById("btnPostponeSale");
    
    const saved = JSON.parse(localStorage.getItem("postponed-sales") || "[]");
    const hasParked = saved.length > 0;
    const hasItems = Object.keys(AppState.cart).length > 0;

    // RULE: If something is parked, ALWAYS show Resume and HIDE Postpone
    if (hasParked) {
        if (btnResume) btnResume.style.display = "flex";
        if (btnPostpone) btnPostpone.style.display = "none";
    } else {
        // If nothing is parked, only show Postpone if there are items
        if (btnResume) btnResume.style.display = "none";
        if (btnPostpone) btnPostpone.style.display = hasItems ? "flex" : "none";
    }
  },

  updateTotalsUI(totals) {
    document.getElementById("subtotal").textContent = Utils.fmt2(totals.subtotal);
    document.getElementById("totalAmt").textContent = Utils.fmt2(totals.total);
    document.getElementById("chargeTotal").textContent = Utils.fmt2(totals.total);
    document.getElementById("discountAmt").textContent = "-" + Utils.fmt2(totals.totalDiscount);
    
    // Render dynamic breakdown
    const breakdownEl = document.getElementById("ivaBreakdown");
    if (breakdownEl) {
        if (!totals.breakdown || Object.keys(totals.breakdown).length === 0) {
            breakdownEl.innerHTML = "";
        } else {
            const numRates = Object.keys(totals.breakdown).length;
            breakdownEl.innerHTML = Object.entries(totals.breakdown).map(([rate, vals]) => `
                ${numRates > 1 ? `
                <div class="total-row fs-12 opacity-80" style="margin-top: 4px; border-bottom: 1px dashed rgba(0,0,0,0.05); padding-bottom: 2px;">
                    <span style="font-weight: 600;">Base imponible (${rate}% iva):</span>
                    <span>${Utils.fmt2(vals.base)}</span>
                </div>` : ''}
                <div class="total-row fs-12 opacity-80" ${numRates === 1 ? 'style="margin-top: 4px;"' : ''}>
                    <span style="font-weight: 600;">Total IVA (${rate}%):</span>
                    <span>${Utils.fmt2(vals.tax)}</span>
                </div>
            `).join("");
        }
    }

    const discRow = document.getElementById("discountRow");
    if (discRow) discRow.style.display = totals.totalDiscount > 0 ? "flex" : "none";
    
    const chargeBtn = document.getElementById("chargeBtn");
    if (chargeBtn) chargeBtn.disabled = Object.keys(AppState.cart).length === 0;
  },

  /**
   * Ticket Tab Management
   */
  switchTicketTab(tab) {
    const contents = document.querySelectorAll(".ticket-tab-content");
    const tabs = document.querySelectorAll(".ticket-tab");
    
    // Hide all contents and deactivate all tabs
    contents.forEach(c => c.classList.add("d-none"));
    tabs.forEach(t => t.classList.remove("active"));
    
    // Show target content
    const targetContent = document.getElementById(tab === 'summary' ? 'tkSummaryTab' : 'tkPointsTab');
    if (targetContent) {
      targetContent.classList.remove("d-none");
    }
    
    // Highlight the button that matches the target tab
    tabs.forEach(t => {
      const onclick = t.getAttribute("onclick") || "";
      if (onclick.includes(`'${tab}'`)) {
        t.classList.add("active");
      }
    });
  },

  templates: {
    productCard(p) {
        const effectivePrice = AppState.getEffectivePrice ? AppState.getEffectivePrice(p) : p.price;
        const priceHtml = Math.abs(effectivePrice - p.price) > 0.01 
            ? `<span class="price-old">${Utils.fmt2(p.price)}</span> <span class="price-current">${Utils.fmt2(effectivePrice)}</span>`
            : `<span class="price-current">${Utils.fmt2(p.price)}</span>`;

        const stockClass = p.stock <= 0 ? "none" : (p.stock <= 5 ? "low" : "");
        const stockLabel = p.stock <= 0 ? (window.I18N?.outOfStock || "Agotado") : `${window.I18N?.stock || "Stock"}: ${p.stock}`;

        let tagsHtml = '';
        if (p.atributos) {
          try {
            let attrs = typeof p.atributos === 'string' ? JSON.parse(p.atributos) : p.atributos;
            if (Array.isArray(attrs)) {
              // Flatten and deduplicate
              const flatAttrs = [...new Set(attrs.flat(2))];
              let badgeHtml = '';
              flatAttrs.forEach(a => {
                badgeHtml += `<span class="product-badge">${a}</span>`;
              });
              tagsHtml = `<div class="product-tags">${badgeHtml}</div>`;
            }
          } catch (e) {
            console.error("Error parsing tags for card:", e);
          }
        }

        return `
          <div class="product-card ${p.inactive ? "inactive" : ""} ${p.stock <= 0 ? "out-of-stock" : ""}" id="card-${p.id}" onclick="app.handleProductClick(event, ${p.id}, this)">
              ${p.inactive ? `<div class="baja-pill">${window.I18N?.inactive || "Baja"}</div>` : ""}
              ${tagsHtml}
              
              
              <div class="product-icon">
                  ${p.icono && p.icono.startsWith("data:image") ? `<img src="${p.icono}" class="prod-img-tpv" alt="${p.name}">` : `<span class="product-emoji">${p.icono || "?"}</span>`}
              </div>
              
              <div class="product-info">
                  <div class="product-name" title="${p.name}">${p.name || "Sin nombre"}</div>
                  <div class="product-sku font-mono opacity-60 fs-11">${p.codigo || "---"}</div>
                  <div class="product-stock fs-10 opacity-70 ${stockClass}" style="margin-top: 2px;">
                    <i class="fa-solid fa-box-open fs-9"></i> ${stockLabel}
                  </div>
                  <div class="product-price">${priceHtml}</div>
              </div>

              ${AppConfig.isAdmin ? `
              <div class="product-admin-bar">
                  <button class="admin-action edit" onclick="app.editProduct(event,${p.id})" title="${window.I18N?.edit || "Editar"}" aria-label="${window.I18N?.edit || "Editar"}"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></button>
                  <button class="admin-action baja" onclick="app.toggleBaja(event,${p.id})" title="${p.inactive ? (window.I18N?.active || "Alta") : (window.I18N?.inactive || "Baja")}" aria-label="${p.inactive ? (window.I18N?.active || "Alta") : (window.I18N?.inactive || "Baja")}"><i class="fa-solid ${p.inactive ? "fa-arrow-up" : "fa-arrow-down"}" aria-hidden="true"></i></button>
                  <button class="admin-action delete" onclick="app.deleteProduct(event,${p.id})" title="${window.I18N?.delete || "Borrar"}" aria-label="${window.I18N?.delete || "Borrar"}"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
              </div>` : ""}
          </div>`;
    },
    cartItem(item) {
        const priceModified = item._customPrice && item.basePriceSnapshot != null && Math.abs(item.price - item.basePriceSnapshot) > 0.001;
        return `
          <div class="order-item">
            <span class="order-item-emoji">
              ${item.icono && item.icono.startsWith("data:image") ? `<img src="${item.icono}" class="order-item-img" alt="${item.name}">` : item.icono}
            </span>
            <div class="order-item-info">
              <div class="order-item-name">${item.name}</div>
              <div class="order-item-price"><input type="number" class="price-input${priceModified ? ' price-modified' : ''}" value="${parseFloat(item.price).toFixed(2)}" min="0" step="0.01" onchange="app.setItemPrice('${item.cartKey}', this.value)" onfocus="this.select()" title="Editar precio (solo esta venta)">€ × ${item.qty}</div>
            </div>
            <div class="qty-ctrl">
              <button class="qty-btn" onclick="app.changeQty('${item.cartKey}', -1)" aria-label="${window.I18N?.reduceQty || "Reducir cantidad"}"><i class="fa-solid fa-minus" aria-hidden="true"></i></button>
              <input type="number" class="qty-input" value="${item.qty}" min="0" onchange="app.setQty('${item.cartKey}', this.value)" onfocus="this.select()" aria-label="${window.I18N?.quantity || "Cantidad"}">
              <button class="qty-btn" onclick="app.changeQty('${item.cartKey}', +1)" aria-label="${window.I18N?.increaseQty || "Aumentar cantidad"}"><i class="fa-solid fa-plus" aria-hidden="true"></i></button>
            </div>
            <div class="order-item-total">${Utils.fmt2(item.price * item.qty)}</div>
            <button class="btn-remove-item" onclick="app.removeFromCart('${item.cartKey}')" title="Quitar todo" aria-label="Quitar todo">
              <i class="fa-solid fa-trash"></i>
            </button>
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
