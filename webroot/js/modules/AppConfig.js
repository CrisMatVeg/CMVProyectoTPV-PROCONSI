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
 */
export const AppState = {
  cart: (function() {
    try {
      const saved = localStorage.getItem("tpv_cart");
      if (!saved) return {};
      const parsed = JSON.parse(saved);
      // Ensure it is an object, not an array, to avoid JSON.stringify issues with non-numeric keys
      return (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) ? parsed : {};
    } catch (e) { return {}; }
  })(),
  selectedPayment: "efectivo",
  discountPct: 0,
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
    this.cart = {};
    localStorage.removeItem("tpv_cart");
  },
};

// Channels
export const cashChannel = new BroadcastChannel("tpv_cash_updates");
