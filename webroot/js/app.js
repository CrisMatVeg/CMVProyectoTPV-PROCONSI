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

  seleccionarTipoCliente(tipo) {
    PaymentManager.seleccionarTipoCliente(tipo);
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

  seleccionarClienteGuardado(idx) {
    PaymentManager.seleccionarClienteGuardado(idx);
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

  applyDiscount() {
    CartManager.applyDiscountCode(document.getElementById("discountCode").value);
    this.refreshUI();
  },


  async init() {
    console.log("TpvApp: Initializing...");
    
    // 1. Sync state with global legacy variables
    if (window.PRODUCTS) AppConfig.products = window.PRODUCTS;
    if (window.PROMOS) AppConfig.promos = window.PROMOS;
    if (window.isAdmin !== undefined) AppConfig.isAdmin = window.isAdmin;

    // 2. Setup UI Features
    UiController.initSidebarResizer();
    UiController.updateClock();
    setInterval(() => UiController.updateClock(), 1000 * 60);

    // 3. Load Theme
    const mode = localStorage.getItem("theme-mode") || "light";
    const accent = localStorage.getItem("theme-accent") || "indigo";
    UiController.setMode(mode);
    UiController.setAccent(accent);

    // 4. Initial Render
    this.refreshUI();
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
    UiController.renderProducts();
  },

  selectCategory(cat) {
    AppState.activeCat = cat;
    UiController.renderProducts();
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

  /**
   * Cart Actions
   */
  clearCart() {
    if (confirm("¿Estás seguro de que quieres vaciar el carrito?")) {
        AppState.cart = {};
        AppState.saveCart();
        this.refreshUI();
    }
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

  /**
   * Theme Settings
   */
  setThemeMode(mode) {
    UiController.setMode(mode);
  },

  setThemeAccent(accent) {
    UiController.setAccent(accent);
  },

  nuevaVenta() {
    document.getElementById("ticketModal").classList.remove("visible");
    AppState.cart = {};
    AppState.saveCart();
    this.refreshUI();
  }
};

// Expose to global scope for legacy onclick handlers
window.app = TpvApp;

// Auto-init when DOM ready
document.addEventListener("DOMContentLoaded", () => TpvApp.init());

export default TpvApp;
