/**
 * app.js
 * Main entry point and orchestration layer.
 */

import { AppConfig, AppState } from './modules/AppConfig.js';
import { ApiService } from './modules/ApiService.js';
import { Utils } from './modules/Utils.js';
import { TicketManager } from './modules/TicketManager.js';
import { ProductManager } from './modules/ProductManager.js';
import { CartManager } from './modules/CartManager.js';
import { UiController } from './modules/UiController.js';
import { PaymentManager } from './modules/PaymentManager.js';

const TpvApp = {
  /**
   * Payment & Checkout
   */
  processPayment() {
    PaymentManager.processPayment();
  },

  selectModalPayment(btn) {
    PaymentManager.selectModalPayment(btn);
  },

  addPagoMixto() {
    PaymentManager.addPagoMixto();
  },

  removePagoMixto(index) {
    PaymentManager.removePagoMixto(index);
  },

  calcularCambioMix() {
    PaymentManager.calcularCambioMix();
  },

  selectPayment(method, btn) {
    AppState.selectedPayment = method;
    if (btn) {
      document.querySelectorAll(".pay-btn").forEach(b => b.classList.remove("selected"));
      btn.classList.add("selected");
    }
    this.refreshUI();
  },

  confirmarCliente() {
    PaymentManager.ejecutarCobroFinal();
  },

  async seleccionarTipoCliente(tipo) {
    await PaymentManager.seleccionarTipoCliente(tipo);
  },

  aplicarVale(vale) {
    PaymentManager.aplicarVale(vale);
  },

  quitarValeAplicado() {
    PaymentManager.quitarValeAplicado();
  },

  buscarSocio() {
    PaymentManager.buscarSocio();
  },

  mostrarRegistroSocio() {
    PaymentManager.mostrarRegistroSocio();
  },

  cancelarRegistroSocio() {
    PaymentManager.cancelarRegistroSocio();
  },

  guardarNuevoSocio() {
    PaymentManager.guardarNuevoSocio();
  },

  buscarClienteGuardado() {
    PaymentManager.buscarClienteGuardado();
  },

  async seleccionarClienteGuardado(idx) {
    await PaymentManager.seleccionarClienteGuardado(idx);
  },

  mostrarRegistroCliente() {
    PaymentManager.mostrarRegistroCliente();
  },

  cancelarRegistroCliente() {
    PaymentManager.cancelarRegistroCliente();
  },

  guardarNuevoCliente() {
    PaymentManager.guardarNuevoCliente();
  },

  cerrarModalCliente() {
    PaymentManager.cerrarModalCliente();
  },

  deseleccionarCliente() {
    PaymentManager.deseleccionarCliente();
  },

  cancelarTelefonoBizum() {
    PaymentManager.cancelarTelefonoBizum();
  },

  async confirmarTelefonoBizum() {
    await PaymentManager.confirmarTelefonoBizum();
  },

  canjearPuntos() {
    PaymentManager.canjearPuntos();
  },

  quitarPuntosCanjeados() {
    PaymentManager.quitarPuntosCanjeados();
  },

  updatePuntosDiscountPreview() {
    // Placeholder for legacy event
  },

  updateMixSummary() {
    PaymentManager.updateMixSummary();
  },

  handleFacturaToggle(val) {
    AppState.esFactura = val;
    PaymentManager.checkMostrarPanelNif();
  },

  applyDiscount() {
    const input = document.getElementById("discountCode");
    const res = CartManager.applyDiscountCode(input.value);
    if (res.ok) {
        Utils.showToast(`Cupón "${res.promo.codigo}" aplicado`, "success");
        input.value = "";
    } else {
        Utils.showToast(res.error, "error");
    }
    this.refreshUI();
  },


  async init() {
    console.log("TpvApp: Initializing...");
    
    // 1. Sync state with global legacy variables
    if (window.DB_PRODUCTS) AppConfig.products = window.DB_PRODUCTS;
    if (window.DB_PROMOS) AppConfig.promos = window.DB_PROMOS;
    AppConfig.isAdmin = (window.IS_ADMIN_BACKEND === true || window.USER_ROLE === 'admin' || window.USER_ROLE === 'administrador');
    console.log("TpvApp: isAdmin =", AppConfig.isAdmin, "| Role:", window.USER_ROLE);

    // 2. Setup UI Features
    UiController.initSidebarResizer();
    UiController.initModalAccessibility(); // ARIA focus-trap for all modals


    // 3. Load Theme
    const mode = localStorage.getItem("theme-mode") || (window.USER_THEME_MODE !== undefined ? window.USER_THEME_MODE : "light");
    const accent = localStorage.getItem("theme-accent") || (window.USER_THEME_ACCENT !== undefined ? window.USER_THEME_ACCENT : "blue");
    const font = localStorage.getItem("theme-font") || (window.USER_THEME_FONT !== undefined ? window.USER_THEME_FONT : "dm-mono");
    UiController.applyTheme(mode, accent, font);

    // 4. Initial Render
    ProductManager.loadProducts(true).then(() => this.refreshUI());
    
    // Setup Infinite Scroll
    const grid = document.getElementById("productsGrid");
    if (grid) {
        grid.onscroll = () => {
            if (grid.scrollTop + grid.clientHeight >= grid.scrollHeight - 50) {
                ProductManager.loadProducts(false).then(() => this.refreshUI());
            }
        };
    }
    
    // 5. Legacy bridge cleanup
    // We no longer call window.renderProducts() here to avoid conflicts
    if (typeof window.renderCart === 'function') window.renderCart();

    console.log("TpvApp: Ready.");
  },

  refreshUI() {
    UiController.renderProducts();
    const totals = CartManager.calculateTotals();
    UiController.renderCart(totals);
  },

  /**
   * Catalog Handlers
   */
  handleProductClick(e, id, el) {
    if (e.target.closest(".product-admin-bar")) return;
    
    // Evitar múltiples clics mientras se procesa el añadido
    if (el && el.classList.contains("adding")) return;

    const result = CartManager.addToCart(id);
    if (result === "stock_limit") {
      Utils.showToast('<i class="fa-solid fa-circle-exclamation"></i> No hay más stock', "error");
    } else if (result) {
      if (el) {
        el.classList.add("adding");
        setTimeout(() => el.classList.remove("adding"), 300);
      }
      this.refreshUI();
    }
  },

  handleSearch(val) {
    AppState.searchTerm = val;
    if (this._searchTimeout) clearTimeout(this._searchTimeout);
    this._searchTimeout = setTimeout(() => {
        ProductManager.loadProducts(true).then(() => this.refreshUI());
    }, 200);
  },

  toggleAdvancedFilters() {
    const panel = document.getElementById("advancedFilters");
    if (panel) {
      const isHidden = panel.classList.contains("d-none");
      panel.classList.toggle("d-none");
      const btn = document.querySelector(".btn-filter-toggle");
      if (btn) btn.classList.toggle("is-open", !isHidden);
    }
  },

  applyAdvancedFilters() {
    const elMin = document.getElementById("filterPriceMin");
    const elMax = document.getElementById("filterPriceMax");
    const elStock = document.getElementById("filterStock");
    const elSort = document.getElementById("filterSort");

    AppState.minPrice = elMin ? parseFloat(elMin.value) || 0 : 0;
    AppState.maxPrice = elMax ? parseFloat(elMax.value) || 999999 : 999999;
    AppState.stockFilter = elStock ? elStock.value : "all";
    AppState.sortOrder = elSort ? elSort.value : "name-asc";

    // Recargar del servidor para que el orden server-side se aplique correctamente
    // (client-side sort solo funciona sobre productos ya cargados, rompiendo la paginación)
    ProductManager.loadProducts(true).then(() => this.refreshUI());
  },

  selectTag(tag) {
    // Disable all tag buttons to prevent double-click race condition
    const tagBtns = document.querySelectorAll('.attr-tab-btn');
    tagBtns.forEach(b => { b.disabled = true; b.style.pointerEvents = 'none'; });

    if (AppState.activeTag === tag) {
        AppState.activeTag = null;
    } else {
        AppState.activeTag = tag;
    }

    // Actualizar UI de botones de etiquetas
    tagBtns.forEach(b => {
        const isSelected = (b.dataset.attr === AppState.activeTag);
        b.classList.toggle('active', isSelected);
    });

    // Refresh from server, re-enable buttons after load
    ProductManager.loadProducts(true).then(() => {
        this.refreshUI();
        tagBtns.forEach(b => { b.disabled = false; b.style.pointerEvents = ''; });
    });
  },

  abrirModalComodin() {
    UiController.openModalComodin();
  },

  cerrarModalComodin() {
    UiController.closeModalComodin();
  },

  agregarComodin() {
    const elDesc = document.getElementById("comodinDesc");
    const elPrice = document.getElementById("comodinPrice");
    const elIva = document.getElementById("comodinIva");
    const elErr = document.getElementById("comodinError");

    const desc = elDesc?.value.trim();
    const price = parseFloat(elPrice?.value);
    const iva = parseFloat(elIva?.value || 21);

    if (!desc) {
      if (elErr) {
        elErr.textContent = window.I18N?.customDescError || "Introduce una descripción";
        elErr.classList.remove("d-none");
      }
      return;
    }

    if (isNaN(price) || price < 0) {
      if (elErr) {
        elErr.textContent = window.I18N?.customPriceError || "Introduce un precio válido";
        elErr.classList.remove("d-none");
      }
      return;
    }

    CartManager.addCustomProduct(desc, price, iva);
    this.cerrarModalComodin();
    this.refreshUI();
    Utils.showToast('<i class="fa-solid fa-check"></i> Producto añadido', "success");
  },

  selectCategory(cat) {
    AppState.activeCat = cat;
    // Highlight active category tab
    document.querySelectorAll(".cat-tab").forEach(t => {
        const isSelected = (t.dataset.cat == cat);
        t.classList.toggle("active", isSelected);
    });
    ProductManager.loadProducts(true).then(() => this.refreshUI());
  },

  /**
   * Product Management
   */
  editProduct(e, id) {
    if (e) e.stopPropagation();
    ProductManager.editProduct(id);
  },

  saveProductEdit() {
    ProductManager.saveEdit().then(() => this.refreshUI());
  },

  deleteProduct(e, id) {
    if (e) e.stopPropagation();
    ProductManager.deleteProduct(id).then(() => this.refreshUI());
  },

  toggleBaja(e, id) {
    if (e) e.stopPropagation();
    ProductManager.toggleBaja(id).then(() => this.refreshUI());
  },

  /**
   * Cart Actions
   */
  clearCart() {
    window.showCustomConfirm(
        "Vaciar pedido", 
        "¿Estás seguro de que quieres eliminar todos los productos del pedido actual?", 
        () => {
            AppState.cart = {};
            AppState.saveCart();
            this.refreshUI();
        },
        "Vaciar",
        "danger"
    );
  },

  changeQty(id, delta) {
    const result = CartManager.changeQty(id, delta);
    if (result === "stock_limit") {
       Utils.showToast('<i class="fa-solid fa-circle-exclamation"></i> Límite de stock', "error");
    }
    this.refreshUI();
  },

  setQty(id, val) {
    CartManager.setQty(id, val);
    this.refreshUI();
  },

  setItemPrice(cartKey, newPrice) {
    CartManager.setItemPrice(cartKey, newPrice);
    const totals = CartManager.calculateTotals();
    UiController.renderCart(totals);
  },

  removeFromCart(id) {
    window.showCustomConfirm(
        "Eliminar producto", 
        "¿Quitar este producto del pedido?", 
        () => {
            CartManager.removeFromCart(id);
            this.refreshUI();
        },
        "Eliminar",
        "danger"
    );
  },

  /**
   * Postponed Sales
   */
  postponeSale() {
    CartManager.postponeSale();
    this.refreshUI();
  },

  resumeSale(id) {
    CartManager.resumeSale(id);
    this.refreshUI();
  },

  /**
   * Ticket & Reports
   */
  verTicket(id) {
    ApiService.request('api/obtenerVenta.php?id=' + id)
      .then(data => {
        if (data.ok) TicketManager.showTicket(data.venta);
      });
  },

  imprimirTicket() {
    TicketManager.printTicket();
  },

  descargarPDFTicket() {
    TicketManager.downloadPDF();
  },

  enviarTicketEmail() {
    TicketManager.sendEmail();
  },

  switchTicketTab(tab) {
    UiController.switchTicketTab(tab);
  },

  /**
   * Theme Settings
   */
  setThemeMode(mode) {
    UiController.setMode(mode);
  },

  setThemeAccent(accent) {
    UiController.setAccent(accent);
  },

  setThemeFont(font) {
    UiController.setFont(font);
  },

  applyTheme(mode, accent, font) {
    UiController.applyTheme(mode, accent, font);
  },

  nuevaVenta() {
    document.getElementById("ticketModal").classList.remove("visible");
    AppState.currentPromo = null;
    AppState.cart = {};
    AppState.saveCart();
    this.refreshUI();
  }
};

// Bridge with global scope for legacy template handlers
window.app = TpvApp;
window.TpvApp = TpvApp; // Security alias for modular string templates
window.switchTicketTab = (tab) => TpvApp.switchTicketTab(tab);
window.getEffectivePrice = (...args) => CartManager.getEffectivePrice(...args);

// Auto-init when DOM ready
document.addEventListener("DOMContentLoaded", () => TpvApp.init());

export default TpvApp;
