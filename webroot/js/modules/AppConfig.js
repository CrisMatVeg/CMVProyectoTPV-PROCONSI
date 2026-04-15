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
    try {
      const saved = localStorage.getItem("tpv_cart");
      if (!saved) return {};
      const parsed = JSON.parse(saved);
      return (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) ? parsed : {};
    } catch (e) { return {}; }
  },
  set cart(val) {
    localStorage.setItem("tpv_cart", JSON.stringify(val));
    // Note: 'cart' in main.js is a 'let' variable (not window.cart),
    // so we only sync via localStorage. main.js reads from localStorage on next renderCart().
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

  ticketNum: 1001,
  activeCat: "all",
  activeAttr: null,
  searchTerm: "",
  minPrice: 0,
  maxPrice: 999999,
  stockFilter: "all",
  sortOrder: "name-asc",

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
