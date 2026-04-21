/**
 * AppConfig.js
 * Centralized configuration and global state for the TPV application.
 */

export const AppConfig = {
  // Constants

  socioDiscount: 5,

  // Global Data (Injected from PHP)
  products: typeof DB_PRODUCTS !== "undefined" ? DB_PRODUCTS : [],
  promos: typeof DB_PROMOS !== "undefined" ? DB_PROMOS : [],
  tarifas: typeof DB_TARIFAS !== "undefined" ? DB_TARIFAS : [],
  isAdmin: typeof IS_ADMIN_BACKEND !== "undefined" ? IS_ADMIN_BACKEND : false,

  // UI Settings
  sidebarWidth: 420,
};

/**
 * AppState
 * Manage the reactive state of the application.
 * Bridged with legacy globals for compatibility.
 */
export const AppState = {
  // Persistence
  get cart() {
    return typeof window !== 'undefined' && window.cart ? window.cart : {};
  },
  set cart(val) {
    if (typeof window !== 'undefined') window.cart = val;
    localStorage.setItem("tpv_cart", JSON.stringify(val));
    if (typeof window !== 'undefined' && typeof window.renderCart === 'function') {
        // No auto-render explicitly needed here unless AppState sets it from out-of-bounds,
        // but it's safe since main.js drives the layout.
    }
  },

  // Bridge with legacy globals
  // Note: selectedPayment in main.js might be a local variable, we prioritize window.selectedPayment
  get selectedPayment() { return window.selectedPayment || "efectivo"; },
  set selectedPayment(val) { window.selectedPayment = val; },

  get socioActual() { return window.socioActual || null; },
  set socioActual(val) { window.socioActual = val; },

  // Points can be set directly or found in currentPayments (legacy behavior)
  get puntosDescuentoAmt() { 
    if (window.puntosDescuentoAmt) return window.puntosDescuentoAmt;
    if (window.currentPayments) {
        const p = window.currentPayments.find(p => p.metodo === "puntos");
        return p ? parseFloat(p.importe) : 0;
    }
    return 0;
  },
  set puntosDescuentoAmt(val) { window.puntosDescuentoAmt = val; },

  get puntosCanjeados() { 
    if (window.puntosCanjeados) return window.puntosCanjeados;
    // Fallback calculation: amount / 0.05 (assuming 1 point = 0.05€)
    return Math.round(this.puntosDescuentoAmt / 0.05);
  },
  set puntosCanjeados(val) { window.puntosCanjeados = val; },

  get tipoClienteActual() { return window.tipoClienteActual || "particular"; },
  set tipoClienteActual(val) { window.tipoClienteActual = val; },

  get clienteSeleccionado() { return window.clienteSeleccionado || null; },
  set clienteSeleccionado(val) { window.clienteSeleccionado = val; },

  get discountPct() { return window.discountPct || 0; },
  set discountPct(val) { window.discountPct = val; },

  get currentPromo() { return window.currentPromo || null; },
  set currentPromo(val) { window.currentPromo = val; },

  get esFactura() { return window.esFactura || false; },
  set esFactura(val) { window.esFactura = val; },

  ticketNum: 1001,
  activeCat: "all",
  activeAttr: null,
  activeTag: null,
  // Reactivity & Legacy Bridge
  get searchTerm() { return window.searchTerm || ""; },
  set searchTerm(val) { window.searchTerm = val; },

  get activeCat() { return window.activeCat || "all"; },
  set activeCat(val) { window.activeCat = val; },

  get minPrice() { return window.minPrice || 0; },
  set minPrice(val) { window.minPrice = val; },

  get maxPrice() { return window.maxPrice || 999999; },
  set maxPrice(val) { window.maxPrice = val; },

  get stockFilter() { return window.stockFilter || "all"; },
  set stockFilter(val) { window.stockFilter = val; },

  get sortOrder() { return window.sortOrder || "name-asc"; },
  set sortOrder(val) { window.sortOrder = val; },

  get offset() { return window.tpvOffset || 0; },
  set offset(val) { window.tpvOffset = val; },

  get limit() { return window.tpvLimit || 100; },
  set limit(val) { window.tpvLimit = val; },

  get canLoadMore() { return window.tpvCanLoadMore !== false; },
  set canLoadMore(val) { window.tpvCanLoadMore = val; },

  get isLoading() { return window.tpvIsLoading || false; },
  set isLoading(val) { window.tpvIsLoading = val; },

  saveCart() {
    localStorage.setItem("tpv_cart", JSON.stringify(this.cart));
  },
  
  clearCart() {
    // Delegate to main.js's clearCart() which has access to the private 'cart' let variable.
    // This ensures the UI is also updated (renderCart is called inside).
    if (typeof window.clearCart === "function") {
        window.clearCart();
    } else {
        // Fallback: just clear localStorage
        localStorage.removeItem("tpv_cart");
    }
    // Clear points state
    this.puntosDescuentoAmt = 0;
    this.puntosCanjeados = 0;
    this.tipoClienteActual = "particular";
    this.socioActual = null;
    this.clienteSeleccionado = null;
    this.activeTag = null;
    this.searchTerm = "";
    
    if (window.currentPayments) {
        window.currentPayments = window.currentPayments.filter(p => p.metodo !== "puntos");
    }
    if (typeof window.clearPuntosRedemption === "function") window.clearPuntosRedemption();
  },
};

// Channels
export const cashChannel = new BroadcastChannel("tpv_cash_updates");
// Convenience exports
export const PRODUCTS = AppConfig.products;
export const PROMOS = AppConfig.promos;
