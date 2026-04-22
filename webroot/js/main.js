console.log("main.js: Script loaded and executing (v15)");
if (typeof I18N === 'undefined') window.I18N = {}; 
const PRODUCTS = typeof DB_PRODUCTS !== "undefined" ? DB_PRODUCTS : [];


// ── Estado global ──────────────────────────────────────────────────────────────
var __initCart;
try {
    __initCart = JSON.parse(localStorage.getItem("tpv_cart") || "{}");
} catch(e) { __initCart = {}; }
var cart = (__initCart && typeof __initCart === 'object' && !Array.isArray(__initCart)) ? __initCart : {};
window.cart = cart;
var selectedPayment = "efectivo";
let discountPct = 0;
let ticketNum = 1001;
let activeCat = "all";
let activeAttr = null;
let searchTerm = "";
let minPrice = -Infinity;
let maxPrice = 999999;
let stockFilter = "all";
let sortOrder = "name-asc";
let isAdmin =
  typeof IS_ADMIN_BACKEND !== "undefined" ? IS_ADMIN_BACKEND : false;
var currentTicketNum = null;
var socioActual = null;
const SOCIO_DISCOUNT = 5;
const PUNTOS_MIN_CANJE = 50;
const PUNTOS_VALOR_EURO = 0.05; // 50 pts = 2.5€ => 1 pt = 0.05€
let currentPromo = null;
const PROMOS = typeof DB_PROMOS !== "undefined" ? DB_PROMOS : [];
const TARIFAS = typeof DB_TARIFAS !== "undefined" ? DB_TARIFAS : [];
let valeAplicado = null;
let tpvOffset = 100;
let tpvLimit = 100;
let tpvCanLoadMore = true;
let tpvIsLoading = false;

function formatTicketNumber(numero, fecha, esFactura, tipoDocumento) {
  let prefix;
  if (tipoDocumento === 'abono') {
    prefix = 'A';
  } else {
    prefix = esFactura ? "F" : "T";
  }
  const d = new Date(fecha);
  const datePart = `${d.getDate()}${d.getMonth() + 1}${d.getFullYear()}`;
  return `${prefix}-${datePart}-${numero}`;
}

window.formatTicketNumber = formatTicketNumber;
var clienteSeleccionado = null;
var tipoClienteActual = "particular";
let ULTIMOS_CLIENTES_BUSCADOS = [];

// ── Interceptor CSRF para API ──────────────────────────────────────────────────
(function () {
  const originalFetch = window.fetch;
  window.fetch = async function (resource, config) {
    config = config || {};
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta && csrfMeta.content) {
      config.headers = {
        ...config.headers,
        "X-CSRF-Token": csrfMeta.content,
      };
    }
    return await originalFetch(resource, config);
  };
})();

// ── Motor de Precios Dinámicos ────────────────────────────────────────────────
function getEffectivePrice(p, socio = null) {
  let finalPrice = parseFloat(p.price);
  const ahora = new Date();
  const horaActual =
    ahora.getHours().toString().padStart(2, "0") +
    ":" +
    ahora.getMinutes().toString().padStart(2, "0") +
    ":" +
    ahora.getSeconds().toString().padStart(2, "0");

  // Filtrar tarifas validas
  const activas = TARIFAS.filter((t) => {
    // 0. Programación Temporal Avanzada
    const fechaActual = ahora.toISOString().split("T")[0];
    const diaActual = ahora.getDay(); // 0 (Dom) - 6 (Sáb)

    // Check Start Date
    if (t.fecha_aplicacion && fechaActual < t.fecha_aplicacion) return false;
    // Check End Date
    if (t.fecha_fin && fechaActual > t.fecha_fin) return false;
    // Check Days of Week
    if (t.dias_semana) {
      const diasPermitidos = t.dias_semana.split(",").map(Number);
      if (!diasPermitidos.includes(diaActual)) return false;
    }

    // 1. Scope de producto
    if (t.scope === "categoria" && p.cat !== t.categoria) return false;
    if (t.scope === "productos") {
      try {
        const ids = JSON.parse(t.producto_ids) || [];
        if (!ids.includes(parseInt(p.id))) return false;
      } catch (e) {
        return false;
      }
    }

    // 2. Horario
    if (t.hora_inicio && horaActual < t.hora_inicio) return false;
    if (t.hora_fin && horaActual > t.hora_fin) return false;

    // 3. Segmentación Cliente
    if (t.es_solo_socios && (!socio || parseInt(socio.es_socio) !== 1))
      return false;
    if (
      t.id_cliente &&
      (!socio || parseInt(socio.id) !== parseInt(t.id_cliente))
    )
      return false;
    if (t.tipo_cliente !== "todos") {
      if (!socio || socio.tipo !== t.tipo_cliente) {
        // Caso especial mayorista (es un flag, no un tipo ENUM en la DB original, pero lo mapeamos)
        if (
          t.tipo_cliente === "mayorista" &&
          (!socio || parseInt(socio.es_mayorista) !== 1)
        )
          return false;
        if (
          t.tipo_cliente !== "mayorista" &&
          (!socio || socio.tipo !== t.tipo_cliente)
        )
          return false;
      }
    }

    return true;
  });

  // Aplicar por prioridad
  activas.sort((a, b) => b.prioridad - a.prioridad);

  activas.forEach((t) => {
    const val = parseFloat(t.valor);
    let variation = 0;
    if (t.tipo === "percent") {
      variation = Math.round(finalPrice * (val / 100) * 100) / 100;
    } else {
      variation = val;
    }
    finalPrice += variation;
  });

  return Math.max(0, finalPrice);
}

// ── Estado de venta pospuesta ──────────────────────────────────────────────────
// [MODERN] Postponed sales now handled in AppState/CartManager (localStorage)
var currentPayments = []; // [NUEVO] Para pagos mixtos
let totalVentaActual = 0; // [NUEVO] Para sincronizar con el modal de cobro
let checkoutContext = 'mixto'; // [NUEVO] Contexto original del sidebar

// ── Tema (modo + acento) ──────────────────────────────────────────────────────
// Mantenemos funciones globales como delegadores a UiController vía App para compatibilidad legacy
function applyTheme(mode, accent, font) {
    if (window.app) window.app.applyTheme(mode, accent, font);
}

function setThemeMode(mode) {
    if (window.app) window.app.setThemeMode(mode);
}

function setThemeAccent(accent) {
    if (window.app) window.app.setThemeAccent(accent);
}

function setThemeFont(font) {
    if (window.app) window.app.setThemeFont(font);
}

// Nota: La inicialización basada en PHP (USER_THEME_*) ahora se gestiona 
// centralizadamente en app.js -> TpvApp.init()

// ── Navegación de Categorías (Drag-to-Scroll) ──────────────────────────────────
function initCategoryDragScroll() {
    const slider = document.getElementById('catTabs');
    if (!slider) return;

    let isDown = false;
    let startX;
    let scrollLeft;
    let moved = false;

    slider.addEventListener('mousedown', (e) => {
        isDown = true;
        moved = false;
        slider.classList.add('active');
        startX = e.pageX - slider.offsetLeft;
        scrollLeft = slider.scrollLeft;
        // Cambiar comportamiento de scroll para que el drag sea instantáneo
        slider.style.scrollBehavior = 'auto';
    });

    slider.addEventListener('mouseleave', () => {
        isDown = false;
        slider.classList.remove('active');
    });

    slider.addEventListener('mouseup', (e) => {
        isDown = false;
        slider.classList.remove('active');
        slider.style.scrollBehavior = 'smooth';
    });

    slider.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - slider.offsetLeft;
        const walk = (x - startX) * 2; // Velocidad de desplazamiento
        
        if (Math.abs(walk) > 5) moved = true;
        slider.scrollLeft = scrollLeft - walk;
    });

    // Prevenir clics accidentales si hubo arrastre
    slider.addEventListener('click', (e) => {
        if (moved) {
            e.preventDefault();
            e.stopPropagation();
        }
    }, true);
}
window.initCategoryDragScroll = initCategoryDragScroll;

// ── DOMContentLoaded ──────────────────────────────────────────────────────────
document.addEventListener("DOMContentLoaded", function () {
  // Inicializar navegación por arrastre
  initCategoryDragScroll();

  const finInfo = document.getElementById("finInfoPanel");
  if (finInfo) {
    finInfo.classList.add("d-none");
    finInfo.style.display = "none";
  }

  // Renderizar estado guardado en localStorage
  renderCart();
  updatePostponeUI();

  try {
    const oi = document.getElementById("orderItems");
    if (oi && !oi.classList.contains("panel-resizable"))
      oi.classList.add("panel-resizable");
  } catch (e) {}

  try {
    initSidebarResizer();
  } catch (e) {
    console.warn("Resizer init failed", e);
  }

  // Atributos filters: Gestionados por app.selectTag() en vInicioPrivado.php
});

// ── Formato monetario ──────────────────────────────────────────────────────────
function fmt(n) {
  const val = typeof n === "number" ? n : parseFloat(n) || 0;
  // Si el nmero tiene ms de 2 decimales, los mostramos todos (hasta 10)
  // para respetar la configuración de alta precisión.
  const str = val.toString();
  const parts = str.split('.');
  if (parts.length > 1 && parts[1].length > 2) {
    return val.toString().replace(".", ",") + " €";
  }
  return val.toFixed(2).replace(".", ",") + " €";
}

// ── Catálogo ───────────────────────────────────────────────────────────────────
// Delegamos al controlador de UI modular para evitar divergencias en el filtrado/renderizado
function renderProducts() {
  if (window.app && typeof window.app.refreshUI === 'function') {
      window.app.refreshUI();
  } else {
      // Supress warning during initial load noise
      // console.warn("app.refreshUI not ready...");
  }
}

function handleCardClick(e, id, el) {
  if (e.target.closest(".product-admin-bar")) return;
  const p = PRODUCTS.find((x) => x.id === id);
  if (!p || p.inactive) return;

  if (p.stock <= 0) {
    showToast('<i class="fa-solid fa-circle-xmark"></i> ' + I18N.outOfStock);
    return;
  }
  addToCart(id, el);
}

// ── Carrito ────────────────────────────────────────────────────────────────────
function addToCart(id, el) {
  const p = PRODUCTS.find((x) => x.id === id);
  if (!p) return;

  const cartKey = id;
  if (cart[cartKey]) {
    if (cart[cartKey].qty >= p.stock) {
      showToast(
        '<i class="fa-solid fa-circle-exclamation"></i> ' + I18N.lowStockLimit,
      );
      return;
    }
    cart[cartKey].qty++;
  } else {
    if (p.stock <= 0) return;

    const finalItem = { ...p };
    const priceToApply = getEffectivePrice(finalItem, socioActual);

    cart[cartKey] = {
      ...finalItem,
      qty: 1,
      price: priceToApply,
      maxStock: p.stock,
      iva: parseFloat(p.iva || 21),
      serials: [],
      cartKey: cartKey,
    };
  }

  el.classList.add("adding");
  setTimeout(() => el.classList.remove("adding"), 300);
  renderCart();
}

function changeQty(cartKey, delta) {
  if (!cart[cartKey]) return;
  const item = cart[cartKey];
  const p = item.id > 0 ? PRODUCTS.find((x) => x.id === item.id) : null;

  const limit = p
    ? item.maxStock !== undefined
      ? item.maxStock
      : p.stock
    : 999999;

  if (delta > 0 && item.qty >= limit) {
    showToast(
      '<i class="fa-solid fa-circle-exclamation"></i> ' + I18N.limitReached,
    );
    return;
  }

  item.qty += delta;
  if (item.qty <= 0) delete cart[cartKey];
  renderCart();
}

function clearCart() {
  // ── Estado del carrito ─────────────────────────────────────────────
  cart = {};
  window.cart = cart;
  discountPct = 0;
  currentPromo = null;
  currentPayments = [];
  localStorage.removeItem("tpv_cart");

  // ── Estado del cliente y puntos ───────────────────────────────────
  socioActual = null;
  clienteSeleccionado = null;
  puntosCanjeados = 0;
  puntosDescuentoAmt = 0;
  tipoClienteActual = "particular";

  // ── UI: selección de tipo de cliente y botones ────────────────────
  seleccionarTipoCliente("particular");

  // ── UI: datos de empresa ──────────────────────────────────────────
  const el_empresaNombre = document.getElementById("empresaNombre");
  const el_empresaNif    = document.getElementById("empresaNif");
  if (el_empresaNombre) el_empresaNombre.value = "";
  if (el_empresaNif)    el_empresaNif.value = "";

  // ── UI: cupón de descuento ─────────────────────────────────────────
  const el_discountCode = document.getElementById("discountCode");
  const el_discountRow  = document.getElementById("discountRow");
  const el_errDiscount  = document.getElementById("err-discount");
  if (el_discountCode) el_discountCode.value = "";
  if (el_discountRow)  el_discountRow.style.display = "none";
  if (el_errDiscount)  el_errDiscount.innerText = "";

  // ── UI: búsqueda de socio ──────────────────────────────────────────
  const el_socioSearch = document.getElementById("socioSearch");
  const el_socioInfo   = document.getElementById("socioInfo");
  const el_btnAddSocio = document.getElementById("btnAddSocio");
  if (el_socioSearch) el_socioSearch.value = "";
  if (el_socioInfo)   el_socioInfo.innerHTML = "";
  if (el_btnAddSocio) el_btnAddSocio.classList.add("d-none");

  // ── UI: búsqueda de cliente genérico ──────────────────────────────
  const el_clienteSearch = document.getElementById("clienteSearch");
  const el_clienteResult = document.getElementById("clienteResultados");
  const el_btnAddCliente = document.getElementById("btnAddCliente");
  if (el_clienteSearch) el_clienteSearch.value = "";
  if (el_clienteResult) el_clienteResult.innerHTML = "";
  if (el_btnAddCliente) el_btnAddCliente.classList.add("d-none");

  // ── UI: vales del cliente ─────────────────────────────────────────
  const el_valesContainer = document.getElementById("clienteValesContainer");
  if (el_valesContainer) el_valesContainer.classList.add("d-none");
  quitarValeAplicado();

  // ── UI: puntos canjeados ──────────────────────────────────────────
  quitarPuntosCanjeados();

  // ── UI: sección puntos (ocultarla si el cliente se va) ────────────
  const el_puntosSection = document.getElementById("puntosSection");
  if (el_puntosSection) el_puntosSection.classList.add("d-none");

  renderCart();
}


// [LEGACY] Sales postponement moved to CartManager.js / app.js

// ── Auto-aplicación de promociones de pack (sin código) ───────────────────────
function autoApplyBundlePromos() {
  if (window.CartManager) {
    window.CartManager.autoApplyBundles();
  }
}

function renderCart() {
  if (window.app && typeof window.app.refreshUI === 'function') {
      window.app.refreshUI();
  }
}

function updatePostponeUI() {
    if (window.app && typeof window.app.updatePostponeUI === 'function') {
        window.app.updatePostponeUI();
    }
}

function updateTotals() {
    if (window.app && typeof window.app.updateTotals === 'function') {
        window.app.updateTotals();
    }
}

// ── Pago ───────────────────────────────────────────────────────────────────────

async function selectSidebarPayment(el) {

  document
    .querySelectorAll(".pay-btn")
    .forEach((b) => b.classList.remove("selected"));
  el.classList.add("selected");
  selectedPayment = el.dataset.method;
  checkoutContext = selectedPayment; // Guardamos el contexto original del sidebar


  const finInfo = document.getElementById("finInfoPanel");
  const aCuentaInfo = document.getElementById("aCuentaGestion");

  // Reset panels
  if (finInfo) finInfo.classList.add("d-none");
  if (aCuentaInfo) aCuentaInfo.classList.add("d-none");

  if (selectedPayment === "a_cuenta") {
    if (aCuentaInfo) aCuentaInfo.classList.remove("d-none");
    validarACuenta();
  }
}





// ── Sidebar resizer ────────────────────────────────────────────────────────────
function initSidebarResizer() {
  const root = document.documentElement;
  const sidebar = document.querySelector(".order-panel");
  const resizer = document.getElementById("sidebarResizer");
  if (!sidebar || !resizer) return;

  const saved = localStorage.getItem("sidebarWidth");
  if (saved) root.style.setProperty("--sidebar-width", saved + "px");

  let dragging = false;
  let startX = 0;
  let startWidth = parseInt(getComputedStyle(sidebar).width, 10);

  const onMove = (e) => {
    if (!dragging) return;
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const dx = startX - clientX;
    let newWidth = startWidth + dx;
    const min = 260;
    const max = Math.max(360, window.innerWidth - 300);
    newWidth = Math.max(min, Math.min(max, newWidth));
    root.style.setProperty("--sidebar-width", newWidth + "px");
  };

  const stop = () => {
    if (!dragging) return;
    dragging = false;
    document.body.classList.remove("resizing-sidebar");
    const w = parseInt(getComputedStyle(sidebar).width, 10);
    localStorage.setItem("sidebarWidth", w);
    window.removeEventListener("mousemove", onMove);
    window.removeEventListener("touchmove", onMove);
    window.removeEventListener("mouseup", stop);
    window.removeEventListener("touchend", stop);
  };

  resizer.addEventListener("mousedown", (ev) => {
    dragging = true;
    startX = ev.clientX;
    startWidth = parseInt(getComputedStyle(sidebar).width, 10);
    document.body.classList.add("resizing-sidebar");
    window.addEventListener("mousemove", onMove);
    window.addEventListener("mouseup", stop);
  });

  resizer.addEventListener(
    "touchstart",
    (ev) => {
      dragging = true;
      startX = ev.touches[0].clientX;
      startWidth = parseInt(getComputedStyle(sidebar).width, 10);
      document.body.classList.add("resizing-sidebar");
      window.addEventListener("touchmove", onMove);
      window.addEventListener("touchend", stop);
    },
    { passive: true },
  );
}

// ── Descuentos ─────────────────────────────────────────────────────────────────
function applyDiscount() {
  const code = document
    .getElementById("discountCode")
    .value.trim()
    .toUpperCase();
  const el_errDiscount = document.getElementById("err-discount");
  if (el_errDiscount) el_errDiscount.innerText = "";

  const subtotal = Object.values(cart).reduce((a, b) => a + b.price * b.qty, 0);
  const promo = PROMOS.find((p) => p.codigo === code);

  if (promo) {
    const minSub = parseFloat(promo.min_subtotal) || 0;
    if (subtotal < minSub) {
      const msg = I18N.promoMinAmount.replace('{amount}', fmt(minSub));
      if (el_errDiscount) {
        el_errDiscount.innerText = msg;
      } else {
        showToast(
          `<i class="fa-solid fa-circle-exclamation"></i> ${msg}`,
        );
      }
      return;
    }

    currentPromo = promo;
    updateTotals(subtotal);

    let descText = "";
    if (promo.tipo === "percent") descText = `${promo.valor}% ${I18N.promoApplied}`;
    else if (promo.tipo === "amount")
      descText = `-${fmt(promo.valor)} ${I18N.promoApplied}`;
    else descText = I18N.promoApplied;

    showToast(
      `<i class="fa-solid fa-circle-check"></i> ${descText} (${promo.codigo})`,
    );
  } else {
    if (el_errDiscount) {
      el_errDiscount.innerText = I18N.promoInvalid;
    } else {
      showToast(
        '<i class="fa-solid fa-circle-xmark"></i> ' + I18N.promoInvalid,
      );
    }
  }
}


// Global trap handle for the checkout modal
let _clienteModalTrap = null;

function cerrarModalCliente() {
  document.getElementById("clienteModal").classList.remove("visible");
  document.querySelectorAll(".form-error").forEach((el) => (el.innerText = ""));
  if (window.FocusTrap && _clienteModalTrap) {
    window.FocusTrap.deactivate(_clienteModalTrap);
    _clienteModalTrap = null;
  }
}

function seleccionarTipoCliente(tipo) {
  tipoClienteActual = tipo;
  validarACuenta();
  const btnP = document.getElementById("btnParticular");
  const btnE = document.getElementById("btnEmpresa");
  const btnS = document.getElementById("btnSocio");

  if (btnP) btnP.classList.remove("selected-type");
  if (btnE) btnE.classList.remove("selected-type");
  if (btnS) btnS.classList.remove("selected-type");

  const empD = document.getElementById("empresaDatos");
  if (empD) empD.classList.add("d-none");
  const socB = document.getElementById("socioBusqueda");
  if (socB) socB.classList.add("d-none");
  const socR = document.getElementById("socioRegistro");
  if (socR) socR.classList.add("d-none");

  const gen = document.getElementById("clienteBusquedaGenerica");
  if (gen) gen.classList.add("d-none");
  const reg = document.getElementById("clienteRegistro");
  if (reg) reg.classList.add("d-none");
  const btnAdd = document.getElementById("btnAddCliente");
  if (btnAdd) btnAdd.classList.add("d-none");
  clienteSeleccionado = null;

  if (tipo === "particular") {
    if (btnP) btnP.classList.add("selected-type");
    socioActual = null;
    if (gen) gen.classList.remove("d-none");
    // [NUEVO] Si es una venta rápida de particular (efectivo), enfocamos el monto directamente
    if (selectedPayment === 'efectivo') {
        setTimeout(() => {
            const input = document.getElementById("mixPagoMonto");
            if (input) {
                input.focus();
                input.select();
            }
        }, 150);
    }
  } else if (tipo === "socio") {
    if (btnS) btnS.classList.add("selected-type");
    const elSocioBusqueda = document.getElementById("socioBusqueda");
    if (elSocioBusqueda) elSocioBusqueda.classList.remove("d-none");
    
    const elSocioSearch = document.getElementById("socioSearch");
    if (elSocioSearch) setTimeout(() => elSocioSearch.focus(), 100);
  } else {
    if (btnE) btnE.classList.add("selected-type");
    const elEmpresaDatos = document.getElementById("empresaDatos");
    if (elEmpresaDatos) elEmpresaDatos.classList.remove("d-none");
    
    const elEmpresaNombre = document.getElementById("empresaNombre");
    if (elEmpresaNombre) setTimeout(() => elEmpresaNombre.focus(), 100);
    socioActual = null;
    if (gen) gen.classList.remove("d-none");
  }

  const subtotal = Object.values(cart).reduce((a, b) => a + b.price * b.qty, 0);
  updateTotals(subtotal);

  quitarValeAplicado();
  quitarPuntosCanjeados();
}

async function buscarSocio() {
  const term = document.getElementById("socioSearch").value.trim();
  if (!term) return;

  const info = document.getElementById("socioInfo");
  const btnAdd = document.getElementById("btnAddSocio");
  info.innerText = I18N.searching;
  btnAdd.classList.add("d-none");

  try {
    const resp = await fetch("api/gestionCliente.php", {
      method: "POST",
      body: JSON.stringify({
        accion: "buscar",
        nif: term,
      }),
    });
    const r = await resp.json();
    if (r.ok) {
      socioActual = r.cliente;
      info.innerHTML = `<i class="fa-solid fa-check-circle text-green"></i> ${r.cliente.nombre} (${r.cliente.nif})`;
      showToast(I18N.socioFound);
      cargarValesCliente(r.cliente.id);
      mostrarPuntosCliente(r.cliente);
      renderCart(); // Re-render to show discounts if any
    } else {
      info.innerHTML = `<span class="text-red">${I18N.socioNotFound}</span>`;
      btnAdd.classList.remove("d-none");
      socioActual = null;
    }
  } catch (e) {
    console.error(e);
    info.innerText = I18N.connError;
  }
}

/**
 * Carga los vales disponibles para un cliente y los muestra en el panel del TPV.
 */
async function cargarValesCliente(idCliente) {
  const container = document.getElementById("clienteValesContainer");
  const listado = document.getElementById("listadoValesCliente");
  if (!container || !listado) return;

  quitarValeAplicado();
  listado.innerHTML = `<div class="fs-11 opacity-70">${I18N.vouchersSearching}</div>`;
  container.classList.remove("d-none");

  try {
    const resp = await fetch("api/listarValesCliente.php", {
      method: "POST",
      body: JSON.stringify({ id_cliente: idCliente }),
    });
    const r = await resp.json();

    if (r.ok && r.vales && r.vales.length > 0) {
      listado.innerHTML = r.vales
        .map(
          (v) => `
        <div class="d-flex jc-space-between ai-center p-8 bg-surface1 br-6 border-2">
          <div class="d-flex flex-column">
            <span class="fs-12 font-bold font-mono">${v.codigo}</span>
            <span class="fs-11 text-accent">${fmt(parseFloat(v.importe_restante))}</span>
          </div>
          <button type="button" onclick='aplicarVale(${JSON.stringify(v)})' class="btn-save fs-10 p-2-8 w-auto">${I18N.apply}</button>
        </div>
      `,
        )
        .join("");
    } else {
      listado.innerHTML =
        `<div class="fs-11 opacity-50 p-4">${I18N.vouchersNone}</div>`;
    }
  } catch (e) {
    console.error("Error cargando vales:", e);
    listado.innerHTML =
      `<div class="fs-11 text-red">${I18N.connError}</div>`;
  }
}

function aplicarVale(vale) {
  valeAplicado = vale;
  const resumen = document.getElementById("valeAplicadoResumen");
  const totalLabel = document.getElementById("valeAplicadoTotal");
  const pendienteLabel = document.getElementById("valePendienteCobro");
  const listado = document.getElementById("listadoValesCliente");

  const totalText = document.getElementById("totalAmt").textContent;
  const totalTPV =
    parseFloat(
      totalText.replace("€", "").replace(/\./g, "").replace(",", ".").trim(),
    ) || 0;

  if (resumen && totalLabel) {
    resumen.classList.remove("d-none");
    totalLabel.textContent = fmt(parseFloat(vale.importe_restante));
    if (pendienteLabel) {
      const rest = Math.max(0, totalTPV - parseFloat(vale.importe_restante));
      pendienteLabel.textContent = fmt(rest);
    }
  }
  if (listado) listado.classList.add("d-none");

  showToast(`<i class="fa-solid fa-ticket"></i> ${I18N.voucherApplied.replace('{code}', vale.codigo)}`);
  updateTotals(totalTPV); // Recalcular totales para reflejar el vale
}

function quitarValeAplicado() {
  valeAplicado = null;
  const resumen = document.getElementById("valeAplicadoResumen");
  const listado = document.getElementById("listadoValesCliente");

  if (resumen) resumen.classList.add("d-none");
  if (listado) listado.classList.remove("d-none");
  updateTotals(Object.values(cart).reduce((a, b) => a + b.price * b.qty, 0)); // Recalcular totales
}

function mostrarPuntosCliente(cliente) {
  const container = document.getElementById("clientePuntosContainer");
  const label = document.getElementById("labelPuntosDisponibles");
  const msgCanje = document.getElementById("puntosCanjeMsg");
  const controles = document.getElementById("controlesCanjePuntos");
  const select = document.getElementById("puntosAcanjearSelect");

  if (!container || !label || !msgCanje || !controles || !select) return;

  quitarPuntosCanjeados();
  container.classList.remove("d-none");
  clienteActualPuntos = parseInt(cliente.puntos || 0); // La API devuelve 'puntos'
  label.textContent = clienteActualPuntos;

  if (clienteActualPuntos >= PUNTOS_MIN_CANJE) {
    controles.classList.remove("d-none");
    msgCanje.classList.add("d-none");
    
    // Rellenamos el select con múltiplos de 50
    let html = "";
    const maxBloques = Math.floor(clienteActualPuntos / 50);
    for (let i = 1; i <= maxBloques; i++) {
        const pts = i * 50;
        const dto = (pts / 50) * 2.5; // 50 pts = 2.5€ (según diseño vInicio)
        html += `<option value="${pts}">${pts} pts (-${dto.toFixed(2)}€)</option>`;
    }
    select.innerHTML = html;
  } else {
    controles.classList.add("d-none");
    msgCanje.classList.remove("d-none");
    msgCanje.textContent = `Te faltan ${PUNTOS_MIN_CANJE - clienteActualPuntos} puntos para un descuento.`;
  }
}

function canjearPuntos() {
  const select = document.getElementById("puntosAcanjearSelect");
  if (!select) return;

  const pts = parseInt(select.value);
  if (isNaN(pts) || pts < PUNTOS_MIN_CANJE || pts > clienteActualPuntos) return;
  
  const dto = (pts / 50) * 2.5;
  
  puntosCanjeados = pts;
  puntosDescuentoAmt = dto;
  
  document.getElementById("puntosCanjeArea").classList.add("d-none");
  document.getElementById("puntosAplicadosResumen").classList.remove("d-none");
  
  const discLabel = document.getElementById("puntosDiscountVal");
  const ptsLabel = document.getElementById("puntosRedeemedVal");
  if (discLabel) discLabel.textContent = `-${dto.toFixed(2).replace(".", ",")} €`;
  if (ptsLabel) ptsLabel.textContent = pts;
  
  showToast(I18N.pointsApplied.replace("{amount}", pts));
  renderCart();
}

function updatePuntosDiscountPreview() {} // Placeholder for the onchange event

function quitarPuntosCanjeados() {
  puntosCanjeados = 0;
  puntosDescuentoAmt = 0;
  
  const area = document.getElementById("puntosCanjeArea");
  const res = document.getElementById("puntosAplicadosResumen");
  if (area) area.classList.remove("d-none");
  if (res) res.classList.add("d-none");
  
  renderCart();
}

function mostrarRegistroSocio() {
  document.getElementById("socioBusqueda").classList.add("d-none");
  document.getElementById("socioRegistro").classList.remove("d-none");
  // Pre-rellenar NIF si se buscó antes
  const searchNif = document.getElementById("socioSearch").value.trim();
  if (searchNif) document.getElementById("newSocioNif").value = searchNif;
}

function cancelarRegistroSocio() {
  document.getElementById("socioRegistro").classList.add("d-none");
  document.getElementById("socioBusqueda").classList.remove("d-none");
}

async function guardarNuevoSocio() {
  const nombre = document.getElementById("newSocioNombre").value.trim();
  const nif = document.getElementById("newSocioNif").value.trim();

  if (!nombre || !nif) {
    alert(I18N.nameNifRequired);
    return;
  }

  try {
    const resp = await fetch("api/gestionCliente.php", {
      method: "POST",
      body: JSON.stringify({
        accion: "registrar",
        nombre,
        nif,
        es_socio: 1,
      }),
    });
    const r = await resp.json();
    if (r.ok) {
      showToast("Socio registrado y seleccionado");
      socioActual = {
        id: r.id,
        nombre,
        nif,
        es_socio: true,
      };
      // Volver a la vista de búsqueda pero mostrando el éxito
      cancelarRegistroSocio();
      document.getElementById("socioInfo").innerHTML =
        `<i class="fa-solid fa-check-circle text-green"></i> ${nombre} (NUEVO)`;
      document.getElementById("btnAddSocio").classList.add("d-none");
      renderCart();
    } else {
      alert("Error al registrar: " + r.error);
    }
  } catch (e) {
    console.error(e);
    alert("Error de conexión");
  }
}

/* 
 * Migrated to PaymentManager.js 
 */
/*
function mostrarRegistroCliente() {
  document.getElementById("clienteBusquedaGenerica").classList.add("d-none");
  document.getElementById("clienteRegistro").classList.remove("d-none");
  const searchTxt = document.getElementById("clienteSearch").value.trim();
  if (searchTxt) {
    if (/^[0-9XYZ]/.test(searchTxt)) {
      document.getElementById("newClienteNif").value = searchTxt;
    } else {
      document.getElementById("newClienteNombre").value = searchTxt;
    }
  }
}

function cancelarRegistroCliente() {
  document.getElementById("clienteRegistro").classList.add("d-none");
  document.getElementById("clienteBusquedaGenerica").classList.remove("d-none");
}

async function guardarNuevoCliente() {
  // ... (Full implementation migrated)
}
*/

async function buscarClienteGuardado() {
  const term = document.getElementById("clienteSearch")?.value.trim() || "";
  if (!term) return;

  const resEl = document.getElementById("clienteResultados");
  if (resEl) resEl.innerText = "Buscando...";
  ULTIMOS_CLIENTES_BUSCADOS = [];

  let tipo = null;
  if (tipoClienteActual === "particular") {
    tipo = "particular";
  } else if (tipoClienteActual === "empresa") {
    tipo = "empresa";
  } else {
    if (resEl)
      resEl.innerText = "Selecciona Particular o Empresa para esta búsqueda.";
    return;
  }

  try {
    const resp = await fetch("api/gestionCliente.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ accion: "buscarTexto", term, tipo }),
    });
    const r = await resp.json();
    if (!r.ok) {
      if (resEl) resEl.innerText = r.error || "Error al buscar clientes.";
      return;
    }
    const lista = r.lista || [];
    ULTIMOS_CLIENTES_BUSCADOS = lista;
    if (!lista.length) {
      if (resEl) resEl.innerText = "Sin resultados para ese término.";
      const btnAdd = document.getElementById("btnAddCliente");
      if (btnAdd) btnAdd.classList.remove("d-none");
      return;
    }
    const btnAdd = document.getElementById("btnAddCliente");
    if (btnAdd) btnAdd.classList.add("d-none");
    if (!resEl) return;
    resEl.innerHTML = lista
      .map((c, idx) => {
        const nombreCompleto =
          (c.nombre || "") + (c.apellidos ? " " + c.apellidos : "");
        const nifTxt = c.nif ? ` (${c.nif})` : "";
        return `<button type="button" class="cat-tab p-4-8 fs-11 mb-4" onclick="seleccionarClienteGuardado(${idx})">
  <i class="fa-solid ${c.tipo === "empresa" ? "fa-building" : "fa-user"}"></i>
  ${nombreCompleto}${nifTxt}
</button>`;
      })
      .join("");
  } catch (e) {
    console.error(e);
    if (resEl) resEl.innerText = "Error de conexión al buscar clientes.";
  }
}

function seleccionarClienteGuardado(idx) {
  const c = ULTIMOS_CLIENTES_BUSCADOS[idx];
  if (!c) return;
  
  clienteSeleccionado = c;

  if (tipoClienteActual === "empresa") {
    const nombreCompleto =
      (c.nombre || "") + (c.apellidos ? " " + c.apellidos : "");
    const nomEl = document.getElementById("empresaNombre");
    const nifEl = document.getElementById("empresaNif");
    if (nomEl) nomEl.value = nombreCompleto;
    if (nifEl) nifEl.value = c.nif || "";
  }

  const resEl = document.getElementById("clienteResultados");
  if (resEl) {
    const nombreCompleto =
      (c.nombre || "") + (c.apellidos ? " " + c.apellidos : "");
    const nifTxt = c.nif ? ` (${c.nif})` : "";
    resEl.innerHTML = `<i class="fa-solid fa-check-circle text-success"></i> ${nombreCompleto}${nifTxt}`;
    cargarValesCliente(c.id);
    mostrarPuntosCliente(c);
  }

  showToast(I18N.clientIdentified);
  validarACuenta();
  renderCart(); // Por si el cliente tiene tarifas especiales o es socio
  app.updateMixSummary(); // Actualizar el resumen si hay
}

/**
 * Función auxiliar para calcular el total final del carrito (reutilizando la lógica de renderCart)
 */
function calculateFinalTotal() {
  const items = Object.values(cart);
  let subtotal = items.reduce((s, i) => s + i.price * i.qty, 0);

  // Bundle discounts
  let bundleDiscountTotal = 0;
  if (
    currentPromo &&
    (currentPromo.tipo === "bundle" || currentPromo.tipo === "fixed_bundle")
  ) {
    const groups = {};
    items.forEach((item) => {
      if (!groups[item.id])
        groups[item.id] = { baseId: item.id, cat: item.cat, units: [] };
      for (let i = 0; i < item.qty; i++) groups[item.id].units.push(item.price);
    });
    Object.values(groups).forEach((group) => {
      let applies = false;
      if (currentPromo.id_producto && currentPromo.id_producto == group.baseId)
        applies = true;
      else if (
        currentPromo.categoria_code &&
        currentPromo.categoria_code === group.cat
      )
        applies = true;
      else if (!currentPromo.id_producto && !currentPromo.categoria_code)
        applies = true;
      if (!applies) return;

      const buyQty = parseInt(currentPromo.bundle_buy_qty) || 0;
      const payQty = parseInt(currentPromo.bundle_pay_qty) || 0;
      const totalQty = group.units.length;

      if (currentPromo.tipo === "bundle" && buyQty > 0 && payQty > 0) {
        const sets = Math.floor(totalQty / buyQty);
        const freeUnits = sets * (buyQty - payQty);
        const sorted = [...group.units].sort((a, b) => a - b);
        bundleDiscountTotal += sorted
          .slice(0, freeUnits)
          .reduce((s, p) => s + p, 0);
      } else if (currentPromo.tipo === "fixed_bundle" && buyQty > 0) {
        const sets = Math.floor(totalQty / buyQty);
        const avgPrice = group.units.reduce((s, p) => s + p, 0) / totalQty;
        const normalPrice = sets * buyQty * avgPrice;
        const bundlePrice = sets * parseFloat(currentPromo.valor);
        if (normalPrice > bundlePrice)
          bundleDiscountTotal += normalPrice - bundlePrice;
      }
    });
  }

  const subtotalAfterBundles = subtotal - bundleDiscountTotal;
  let couponDiscount = 0;
  if (
    currentPromo &&
    (currentPromo.tipo === "percent" || currentPromo.tipo === "amount")
  ) {
    if (currentPromo.tipo === "percent")
      couponDiscount = (subtotalAfterBundles * currentPromo.valor) / 100;
    else couponDiscount = Math.min(subtotalAfterBundles, currentPromo.valor);
  }

  const socioAmt =
    socioActual && socioActual.es_socio
      ? (subtotalAfterBundles - couponDiscount) * (SOCIO_DISCOUNT / 100)
      : 0;
  
  // Descuento por puntos
  const puntosAmt = puntosDescuentoAmt;

  const subtotalFinal = subtotal - (bundleDiscountTotal + couponDiscount + socioAmt + puntosAmt);

  const discountFactor = subtotal > 0 ? subtotalFinal / subtotal : 1;
  let vatTotal = 0;
  items.forEach((item) => {
    const line = item.price * item.qty * discountFactor;
    vatTotal += line * (item.iva / 100);
  });

  return subtotalFinal + vatTotal;
}

function abrirModalPago() {
  const modal = document.getElementById("clienteModal");
  modal.classList.add("visible");
  validarACuenta();
  if (window.FocusTrap) {
    _clienteModalTrap = window.FocusTrap.activate(modal, cerrarModalCliente);
  }

  // [NUEVO] Enfocar el monto automáticamente si es efectivo
  if (selectedPayment === 'efectivo') {
    setTimeout(() => {
      const input = document.getElementById("mixPagoMonto");
      if (input) {
        input.focus();
        input.select();
      }
    }, 200);
  }
}

/**
 * Bridge function to unified saving logic in app.js
 */
async function ejecutarCobroFinal() {
  if (window.app) {
    window.app.confirmarCliente();
  } else {
    console.error("The modern TPV application (app.js) is not yet initialized.");
    showToast("<i class='fa-solid fa-circle-exclamation'></i> La aplicación aún se está cargando, por favor espera un momento.");
  }
}

function validarACuenta() {
  const pagado = parseFloat(document.getElementById("aCuentaPagado")?.value) || 0;
  const fecha = document.getElementById("aCuentaFechaLimite")?.value;
  const errFecha = document.getElementById("err-aCuentaFecha");
  const btn = document.getElementById("confirmarClienteBtn");
  const alerta = document.getElementById("aCuentaAlertaCliente");

  if (errFecha) errFecha.innerText = "";
  if (alerta) {
    alerta.classList.add("d-none");
    alerta.style.display = ""; // Remove any inline style
  }

  const elTotal = document.getElementById("mixTotalVenta") || document.getElementById("totalAmt");
  const total = elTotal ? parseFloat(elTotal.textContent.replace(",", ".")) || 0 : 0;

  if (btn) btn.disabled = false;

  const tieneAcuenta = selectedPayment === "a_cuenta" || currentPayments.some(p => p.metodo === "a_cuenta");

  if (tieneAcuenta) {
    const clienteId =
      tipoClienteActual === "socio"
        ? socioActual
          ? socioActual.id
          : null
        : clienteSeleccionado
          ? clienteSeleccionado.id
          : null;

    if (!clienteId && tipoClienteActual !== "empresa") {
      if (alerta) alerta.classList.remove("d-none");
      if (btn) btn.disabled = true;
    }

    if (!fecha) {
      if (errFecha) errFecha.innerText = I18N.dateRequired;
      if (btn) btn.disabled = true;
    }

    if (pagado >= total) {
      // Si va a pagar todo ahora, mejor que use efectivo/tarjeta
      // pero no lo bloqueamos, simplemente avisamos si acaso
    }
  }
}

async function cargarVenta(ticketNum) {
  const resp = await fetch("./api/obtenerVenta.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ numTicket: ticketNum }),
  });
  const data = await resp.json();
  if (!resp.ok || !data.ok)
    throw new Error(data.error || "Error al cargar venta");
  return data.venta;
}

async function verTicket(ticketNum) {
  try {
    const venta = await cargarVenta(ticketNum);
    mostrarTicket(venta, false);
  } catch (err) {
    showToast("<i class='fa-solid fa-circle-xmark'></i> " + err.message);
  }
}
window.verTicket = verTicket;
window.cargarVenta = cargarVenta;

function mostrarTicket(v, isFromTPV = true) {
  if (!v) return;
  currentTicketNum = v.numero_ticket;
  window.currentVentaId = v.id;
  window.currentVentaPendiente =
    parseFloat(v.total) - parseFloat(v.pagado_a_cuenta || 0);

  const emailSection = document.getElementById("ticketEmailSection");
  const btnPrint = document.querySelector('button[onclick="imprimirTicket()"]');
  const btnNuevaVenta = document.getElementById("btnNuevaVenta");

  if (emailSection) emailSection.style.display = ""; // Always show email section
  if (btnPrint) btnPrint.style.display = ""; // Always show print button
  if (btnNuevaVenta) btnNuevaVenta.style.display = isFromTPV ? "" : "none";

  const btnAnular = document.getElementById("btnAnularTicket");
  if (btnAnular) {
    const isAbono = (v.tipo_documento || 'venta') === 'abono';
    btnAnular.style.display =
      !isFromTPV && v.estado === "completada" && !isAbono ? "" : "none";
    btnAnular.onclick = () =>
      abrirModalAnulacionTicket(v.numero_ticket, v.fecha, v.id_cliente);
  }

  const emailInput = document.getElementById("tkEmailInput");
  if (emailInput) emailInput.value = v.cliente_email || "";
  const errEmail = document.getElementById("err-email");
  if (errEmail) errEmail.innerText = "";

  // uses global fmt(n)

  let date = new Date();
  if (v.fecha) {
    const fStr = v.fecha.includes("T") ? v.fecha : v.fecha.replace(" ", "T");
    date = new Date(fStr);
  }

  const fechaStr =
    date.toLocaleDateString("es-ES") +
    " " +
    date.toLocaleTimeString("es-ES", { hour: "2-digit", minute: "2-digit" });

  const isFactura = v.tipo_cliente === "empresa" || v.es_factura == 1;
  const isAbonoDocType = (v.tipo_documento || 'venta') === 'abono';
  const el_tkTipoDoc = document.getElementById("tkTipoDoc");
  if (el_tkTipoDoc) {
      if (isAbonoDocType) el_tkTipoDoc.textContent = I18N.ticket_type_abono || "TICKET DE ABONO";
      else el_tkTipoDoc.textContent = isFactura ? I18N.invoice : I18N.ticket;
  }

  const ticketWrapper = document.getElementById("ticketContenido");
  if (ticketWrapper) {
    if (isFactura) ticketWrapper.classList.add("es-factura");
    else ticketWrapper.classList.remove("es-factura");
  }

  const clienteSection = document.getElementById("tkClienteSection");
  const labelClienteMeta = document.getElementById("tkLabelClienteMeta");
  const clienteMeta = document.getElementById("tkClienteMeta");
  const labelNifMeta = document.getElementById("tkLabelNifMeta");
  const nifMeta = document.getElementById("tkNifMeta");

  if (isFactura && v.nombre_cliente) {
    if (clienteSection) clienteSection.classList.add("d-none");
    if (labelClienteMeta) labelClienteMeta.classList.remove("d-none");
    if (clienteMeta) {
      clienteMeta.classList.remove("d-none");
      clienteMeta.textContent = v.nombre_cliente || "—";
    }
    if (labelNifMeta) labelNifMeta.classList.remove("d-none");
    if (nifMeta) {
      nifMeta.classList.remove("d-none");
      nifMeta.textContent = v.nif_cliente || "—";
    }
  } else {
    if (clienteSection) clienteSection.classList.add("d-none");
    if (labelClienteMeta) labelClienteMeta.classList.add("d-none");
    if (clienteMeta) clienteMeta.classList.add("d-none");
    if (labelNifMeta) labelNifMeta.classList.add("d-none");
    if (nifMeta) nifMeta.classList.add("d-none");
  }

  // Fidelización: Actualizar información de puntos en el ticket
  const el_pointsEarned = document.getElementById("tkPointsEarnedTotal");
  const el_pointsRedeemed = document.getElementById("tkPointsRedeemed");
  const el_pointsBalance = document.getElementById("tkPointsTotalBalance");
  
  if (el_pointsEarned) el_pointsEarned.textContent = v.puntos_ganados || 0;
  if (el_pointsRedeemed) el_pointsRedeemed.textContent = v.puntos_canjeados || 0;
  if (el_pointsBalance) {
      // Si tenemos información del balance actual del cliente, la mostramos
      el_pointsBalance.textContent = v.puntos_cliente_actual || "—";
  }

  // Resetear pestañas del ticket
  switchTicketTab('summary');

  const el_tkNumero = document.getElementById("tkNumero");
  if (el_tkNumero) {
    const esFactura = v.tipo_cliente === 'empresa' || v.es_factura == 1;
    const tipoDoc   = v.tipo_documento || 'venta';
    el_tkNumero.textContent = window.formatTicketNumber ? window.formatTicketNumber(v.numero_ticket, v.fecha, esFactura, tipoDoc) : v.numero_ticket;
    // Mostrar badge visual para abonos
    const badgeAbono = document.getElementById('tkBadgeAbono');
    if (badgeAbono) badgeAbono.style.display = tipoDoc === 'abono' ? 'inline-flex' : 'none';
  }
  const el_tkFecha = document.getElementById("tkFecha");
  if (el_tkFecha) el_tkFecha.textContent = fechaStr;
  
  // Mostrar ticket origen si existe (abonos)
  const el_tkLabelNumOrig = document.getElementById("tkLabelNumOrig");
  const el_tkNumOrig = document.getElementById("tkNumOrig");
  if (el_tkLabelNumOrig && el_tkNumOrig) {
      if (v.numero_ticket_origen) {
          el_tkNumOrig.textContent = v.numero_ticket_origen;
          el_tkLabelNumOrig.classList.remove("d-none");
          el_tkNumOrig.classList.remove("d-none");
      } else {
          el_tkLabelNumOrig.classList.add("d-none");
          el_tkNumOrig.classList.add("d-none");
      }
  }

  const el_tkMetodo = document.getElementById("tkMetodo");
  if (el_tkMetodo)
    el_tkMetodo.textContent =
      v.metodo_pago.charAt(0).toUpperCase() + v.metodo_pago.slice(1);

  // Mostrar comentarios si existen
  const commentsSec = document.getElementById("tkCommentsSection");
  const commentsTxt = document.getElementById("tkCommentsText");
  if (commentsSec && commentsTxt) {
    if (v.comentarios && v.comentarios.trim() !== "") {
      commentsTxt.textContent = v.comentarios;
      commentsSec.classList.remove("d-none");
    } else {
      commentsSec.classList.add("d-none");
    }
  }

  // Ocultar botones de devolución y pestaña de puntos si el ticket es ya un abono
  const esAbono = (v.tipo_documento || 'venta') === 'abono';
  document.querySelectorAll('.btn-devolucion-ticket').forEach(b => {
    b.style.display = esAbono ? 'none' : '';
  });
  const el_tkTabPoints = document.getElementById('tkTabPoints');
  const el_tkTabsContainer = document.getElementById('tkTabsContainer');
  if (el_tkTabsContainer) el_tkTabsContainer.style.display = esAbono ? 'none' : 'flex';

  const tkCajero = document.getElementById("tkCajero");
  if (tkCajero) {
    if (v.nombre_cajero) tkCajero.textContent = v.nombre_cajero;
    else if (typeof CAJERO_NOMBRE !== "undefined" && CAJERO_NOMBRE)
      tkCajero.textContent = CAJERO_NOMBRE;
    else tkCajero.textContent = "—";
  }

  const lineasEl = document.getElementById("tkLineas");
  if (lineasEl)
    lineasEl.innerHTML = v.lineas
      .map((l) => {
        const vDate = new Date(v.fecha.replace(" ", "T"));
        const months = parseInt(l.meses_garantia || 24);
        const gDate = new Date(vDate);
        gDate.setMonth(gDate.getMonth() + months);
        const isExpired = gDate < new Date();
        const gStr = gDate.toLocaleDateString("es-ES");

        return `
          <div style="display:flex; justify-content:space-between; padding: 10px 0; border-bottom: 1px solid var(--surface2); ${l.devuelta ? "opacity:0.6; background:rgba(192,57,43,0.05);" : ""}">
            <div style="flex:1">
              <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                <div>
                  <span style="font-weight:600; ${l.devuelta ? "text-decoration:line-through;" : ""}">${l.nombre_producto}</span>
                  <span style="color:var(--text-muted); font-size:11px; margin-left:6px;">${l.codigo_producto}</span>
                </div>
                <span style="font-family:'DM Mono',monospace; font-weight:600;">${fmt(l.total_linea)}</span>
              </div>
              <div style="display:flex; align-items:center; gap:8px; margin-top:4px;">
                <span style="color:var(--text-muted); font-size:11px;">${l.cantidad} × ${fmt(l.precio_unitario)}</span>
                <span class="fs-10 px-6 py-2 br-4" style="background:${isExpired ? "var(--red-light)" : "var(--green-light)"}; color:${isExpired ? "var(--red)" : "var(--green)"}; font-weight:700;">
                  <i class="fa-solid fa-shield-halved"></i>
                  ${isExpired ? "Garantía AGOTADA" : "Garantía hasta " + gStr}
                </span>
                ${l.devuelta ? `<span class="fs-10 px-6 py-2 br-4" style="background:var(--red); color:white; font-weight:700;">DEVUELTO</span>` : ""}
              </div>
              ${l.devuelta && l.motivo_devolucion ? `<div style="margin-top:4px; font-size:11px; color:var(--red); font-style:italic;"><i class="fa-solid fa-circle-info"></i> Motivo: ${l.motivo_devolucion}</div>` : ""}
            </div>
            ${
              !isFromTPV && !l.devuelta && v.estado === "completada"
                ? `<div style="padding-left:12px; display:flex; align-items:center;">
                  <button onclick="abrirModalDevolucion(${l.id}, ${v.numero_ticket}, '${v.fecha}', ${v.id_cliente || "null"}, ${l.meses_garantia || 24}, '${l.nombre_producto.replace(/'/g, "\\'")}')" title="Devolver este producto" class="btn-icon text-red">
                    <i class="fa-solid fa-arrow-rotate-left"></i>
                  </button>
                </div>`
                : ""
            }
          </div>
        `;
      })
      .join("");

  // ── Desglose IVA por tipo (calculado desde las líneas) ─────────────────────
  const el_tkSubtotalRow = document.getElementById("tkSubtotalRow");
  const el_tkIvaDesglose = document.getElementById("tkIvaDesglose");
  const el_tkTotal = document.getElementById("tkTotal");

  const vTotal = parseFloat(v.total) || 0;
  const vSubtotal = parseFloat(v.subtotal) || 0;
  const vDescAmt = parseFloat(v.descuento_amt) || 0;
  const vDescPct = parseFloat(v.descuento_pct) || 0;

  // Si no hay ningún descuento aplicado, calcular directamente desde las líneas (factorDesc=1)
  // Si hay descuento, prorratear usando v.total / v.subtotal para distribuirlo proporcionalmente
  const vPuntosAmt = parseFloat(v.puntos_descuento_amt) || 0;
  // Si hay cualquier descuento (promo, cupón o puntos), prorratear usando v.total / v.subtotal
  const factorDesc =
    (vDescAmt > 0 || vDescPct > 0 || vPuntosAmt > 0) && vSubtotal > 0 ? vTotal / vSubtotal : 1;

  // Desglose IVA BRUTO (antes de descuentos).
  // Base + IVA = subtotal bruto de las líneas (ej: 130,94€).
  // Usamos precio_unitario × cantidad porque total_linea ya lleva descuentos aplicados.
  // Los descuentos (puntos, cupones, etc.) se muestran como ítems separados en el ticket.
  const ivaGrupos = {};
  const isAbonoDoc = (v.tipo_documento || 'venta') === 'abono';
  v.lineas.forEach((l) => {
    if (l.devuelta && !isAbonoDoc) return;
    const rate     = parseFloat(l.iva_aplicado ?? 21);
    const pvpBruto = parseFloat(l.precio_unitario) * parseFloat(l.cantidad); // Precio bruto sin descuentos globales
    const base     = pvpBruto / (1 + rate / 100);
    const tax      = pvpBruto - base;
    if (!ivaGrupos[rate]) ivaGrupos[rate] = { base: 0, tax: 0 };
    ivaGrupos[rate].base += base;
    ivaGrupos[rate].tax  += tax;
  });

  // displayTotal = total neto real (con descuentos aplicados) — viene directo del backend.
  // Se usa únicamente para la fila TOTAL; el desglose IVA muestra valores brutos.
  const displayTotal = isAbonoDoc ? -Math.abs(vTotal) : vTotal;


  // TOTAL: usar el valor neto real del backend (ya incluye todos los descuentos)
  if (el_tkTotal) {
      el_tkTotal.textContent = fmt(isAbonoDoc ? -Math.abs(vTotal) : vTotal);
  }

  // Ocultar fila Subtotal genérica (queda reemplazada por el desglose)
  if (el_tkSubtotalRow) el_tkSubtotalRow.style.display = "none";

  // Renderizar una fila Base imponible + IVA por cada tipo de IVA
  if (el_tkIvaDesglose) {
    el_tkIvaDesglose.innerHTML = Object.entries(ivaGrupos)
      .sort(([a], [b]) => parseFloat(a) - parseFloat(b))
      .map(
        ([rate, data]) => `
        <div class="ticket-total-row label text-muted">
          <span>Base imponible (${rate}%)</span><span>${fmt(data.base)}</span>
        </div>
        <div class="ticket-total-row label text-muted">
          <span>IVA ${rate}%</span><span>${fmt(data.tax)}</span>
        </div>
      `,
      )
      .join("");
  }

  const tkEfectivoRow = document.getElementById("tkEfectivoRow");
  if (tkEfectivoRow) {
    let tieneEfectivo = (v.metodo_pago === "efectivo");
    let importeEfectivoMixto = 0;
    if (v.metodo_pago === "mixto" && v.pagos && v.pagos.length > 0) {
      v.pagos.forEach(p => {
        if (p.metodo_pago === "efectivo" && parseFloat(p.importe) > 0) {
          tieneEfectivo = true;
          importeEfectivoMixto += parseFloat(p.importe);
        }
      });
    }

    const efectivoRecibido = parseFloat(v.efectivo_recibido) || 0;

    let efectivoCambio = 0;
    if (v.metodo_pago === "mixto") {
       efectivoCambio = Math.max(0, efectivoRecibido - importeEfectivoMixto);
    } else {
       efectivoCambio = Math.max(0, efectivoRecibido - displayTotal);
    }

    // En mixto: solo mostrar fila si hay cambio real que devolver.
    // En efectivo puro: mostrar siempre que haya recibido > 0.
    const mostrarFila = tieneEfectivo && (
      v.metodo_pago !== "mixto"
        ? efectivoRecibido > 0
        : efectivoCambio > 0.005
    );

    if (mostrarFila) {
      tkEfectivoRow.classList.remove("d-none");
      const el_tkEntregado = document.getElementById("tkEntregado");
      const el_tkCambio = document.getElementById("tkCambio");
      if (el_tkEntregado) el_tkEntregado.textContent = fmt(efectivoRecibido);
      if (el_tkCambio) el_tkCambio.textContent = fmt(efectivoCambio);
    } else {
      tkEfectivoRow.classList.add("d-none");
    }
  }


  // --- NUEVO: MOSTRAR PAGOS PARCIALES ---
  const tkPagosSection = document.getElementById("tkPagosSection");
  if (tkPagosSection) {
    const tkPagosLista = document.getElementById("tkPagosLista");
    const tkAbonarParteContainer = document.getElementById(
      "tkAbonarParteContainer",
    );

    // Si hay pagos o es a_cuenta (incluso sin pagos), mostramos la sección
    if (
      (v.pagos && v.pagos.length > 0) ||
      v.metodo_pago === "a_cuenta" ||
      v.estado === "pendiente_pago"
    ) {
      tkPagosSection.classList.remove("d-none");

      if (tkPagosLista) {
        if (!v.pagos || v.pagos.length === 0) {
          tkPagosLista.innerHTML =
            '<div class="text-muted fs-11">No hay abonos registrados aún.</div>';
        } else {
          tkPagosLista.innerHTML = v.pagos
            .map((p) => {
              const fStr = new Date(p.fecha).toLocaleString("es-ES", {
                dateStyle: "short",
                timeStyle: "short",
              });
              const mtd =
                p.metodo_pago.charAt(0).toUpperCase() + p.metodo_pago.slice(1);
              return `
              <div style="display: flex; justify-content: space-between; font-size: 11px; border-bottom: 1px solid rgba(0,0,0,0.05); padding-bottom: 2px;">
                <span><span class="text-muted">${fStr}</span> · ${mtd} ${p.nombre_usuario ? "(" + p.nombre_usuario + ")" : ""}</span>
                <span class="font-mono text-success fw-bold">+${fmt(parseFloat(p.importe))}</span>
              </div>
            `;
            })
            .join("");
        }
      }

      if (tkAbonarParteContainer) {
        // [NUEVO] Recalcular estado pendiente basado en displayTotal corregido 
        // para evitar mostrar botón en tickets legacy erroneos
        const sumPagos = (v.pagos || []).reduce((acc, p) => acc + parseFloat(p.importe), 0);
        const pendienteReal = Math.max(0, displayTotal - sumPagos);
        
        // [NUEVO] Asegurar que la variable global para el modal es la recalcalculada
        window.currentVentaPendiente = pendienteReal;

        if (pendienteReal > 0.01 || v.estado === "pendiente_pago" && displayTotal > sumPagos) {
          tkAbonarParteContainer.classList.remove("d-none");
        } else {
          tkAbonarParteContainer.classList.add("d-none");
        }
      }
    } else {
      tkPagosSection.classList.add("d-none");
    }
  }

  // [NUEVO] Mostrar Abonos Asociados si existen
  let tkAbonosAsociadosSection = document.getElementById("tkAbonosAsociadosSection");
  if (!tkAbonosAsociadosSection && tkPagosSection) {
    tkAbonosAsociadosSection = document.createElement("div");
    tkAbonosAsociadosSection.id = "tkAbonosAsociadosSection";
    tkAbonosAsociadosSection.style.marginTop = "16px";
    tkAbonosAsociadosSection.style.padding = "12px";
    tkAbonosAsociadosSection.style.border = "1px solid var(--red-light)";
    tkAbonosAsociadosSection.style.borderRadius = "8px";
    tkAbonosAsociadosSection.style.background = "rgba(192,57,43,0.03)";
    tkPagosSection.parentNode.insertBefore(tkAbonosAsociadosSection, tkPagosSection.nextSibling);
  }

  if (tkAbonosAsociadosSection) {
    if (v.abonos_asociados && v.abonos_asociados.length > 0) {
      tkAbonosAsociadosSection.classList.remove("d-none");
      tkAbonosAsociadosSection.innerHTML = `
        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; color: var(--red);">
          <i class="fa-solid fa-arrow-rotate-left"></i> Devoluciones Asociadas
        </div>
        <div style="display:flex; flex-direction:column; gap:6px;">
          ${v.abonos_asociados.map(a => {
            const dateStr = new Date(a.fecha.replace(" ", "T")).toLocaleString("es-ES", { dateStyle: "short", timeStyle: "short" });
            return `
            <div style="display:flex; justify-content:space-between; align-items:center; font-size:11px; border-bottom:1px solid rgba(192,57,43,0.1); padding-bottom:4px;">
              <span>
                <span style="background:var(--red);color:#fff;font-size:9px;font-weight:800;padding:2px 4px;border-radius:4px;">ABONO</span>
                <span class="text-muted" style="margin-left:4px;">${dateStr}</span> 
                <a href="#" onclick="event.preventDefault(); window.verTicket(${a.numero_ticket});" style="text-decoration:underline; cursor:pointer;">
                  #${String(a.numero_ticket).padStart(4, '0')}
                </a>
              </span>
              <span class="font-mono fw-bold text-red">${fmt(parseFloat(a.total))}</span>
            </div>`;
          }).join("")}
        </div>
      `;
    } else {
      tkAbonosAsociadosSection.classList.add("d-none");
    }
  }

  const descRow = document.getElementById("tkDescRow");
  if (descRow) {
    const discountAmt = parseFloat(v.descuento_amt || 0);
    const discountPctVal = parseFloat(v.descuento_pct || 0);
    if (discountAmt > 0 || discountPctVal > 0) {
      descRow.classList.remove("d-none");
      const el_tkDescLabel = document.getElementById("tkDescLabel");
      const el_tkDescAmt = document.getElementById("tkDescAmt");
      if (el_tkDescLabel) {
        if (v.descuento_label) {
          el_tkDescLabel.textContent = `Descuento (${v.descuento_label})`;
        } else if (discountPctVal > 0) {
          el_tkDescLabel.textContent = `Descuento (${discountPctVal}%)`;
        } else {
          el_tkDescLabel.textContent = "Descuento";
        }
      }
      if (el_tkDescAmt) {
        el_tkDescAmt.textContent =
          "−" +
          fmt(
            discountAmt > 0
              ? discountAmt
              : (parseFloat(v.subtotal) * discountPctVal) / 100,
          );
      }
    } else {
      descRow.classList.add("d-none");
    }
  }

  document.getElementById("ticketModal").classList.add("visible");

  // Sección de abonos asociados a esta venta
  renderAbonosSection(v);

  // Sincronizar estado en la UI de fondo (Historial)
  updateVentaStatusUI(v.numero_ticket, v.estado, v);
}

/**
 * Renderiza la sección de abonos/devoluciones en el modal del ticket.
 * @param {Object} venta - Objeto de la venta con array 'abonos'
 */
function renderAbonosSection(venta) {
  const seccion = document.getElementById('tkAbonosSection');
  if (!seccion) return;

  const abonos = venta.abonos || [];
  const tipoDoc = venta.tipo_documento || 'venta';

  // Si es un abono, mostrar enlace a la venta de origen
  if (tipoDoc === 'abono') {
    seccion.innerHTML = `
      <div style="margin-top:16px;padding:12px 16px;border-radius:10px;background:rgba(192,57,43,0.06);border:1px solid rgba(192,57,43,0.2);">
        <div style="font-size:11px;font-weight:700;color:var(--red);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">
          <i class="fa-solid fa-rotate-left" style="margin-right:6px;"></i>TICKET DE ABONO
        </div>
        <div style="font-size:12px;color:var(--text-muted);">
          Este documento es una nota de abono. Genera un reembolso sobre la venta original.
        </div>
      </div>
    `;
    seccion.classList.remove('d-none');
    return;
  }

  if (abonos.length === 0) {
    seccion.classList.add('d-none');
    return;
  }

  const fmt = (n) => new Intl.NumberFormat('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n) + ' €';

  seccion.innerHTML = `
    <div style="margin-top:16px;padding:12px 16px;border-radius:10px;background:rgba(192,57,43,0.06);border:1px solid rgba(192,57,43,0.2);">
      <div style="font-size:11px;font-weight:700;color:var(--red);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">
        <i class="fa-solid fa-rotate-left" style="margin-right:6px;"></i>ABONOS / DEVOLUCIONES (${abonos.length})
      </div>
      ${abonos.map(a => {
        const fStr = new Date(a.fecha.replace(' ','T')).toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'short' });
        const numFmt = formatTicketNumber(a.numero_ticket, a.fecha, false, 'abono');
        return `
          <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid rgba(192,57,43,0.1);gap:8px;">
            <div>
              <button onclick="verTicket(${a.numero_ticket})" style="font-size:12px;font-weight:700;color:var(--red);background:none;border:none;cursor:pointer;padding:0;text-decoration:underline;">${numFmt}</button>
              <span style="font-size:11px;color:var(--text-muted);margin-left:8px;">${fStr}</span>
            </div>
            <span style="font-size:13px;font-weight:700;color:var(--red);font-family:'DM Mono',monospace;white-space:nowrap;">${fmt(Math.abs(parseFloat(a.total)))}</span>
          </div>
        `;
      }).join('')}
    </div>
  `;
  seccion.classList.remove('d-none');
}

async function imprimirTicket() {
  if (!currentTicketNum) {
    showToast(
      "<i class='fa-solid fa-circle-xmark'></i> No hay ticket cargado para imprimir",
    );
    return;
  }
  window.open(`./api/imprimirTicket.php?id=${currentTicketNum}`, "_blank");
}

function descargarPDFTicket() {
  if (!currentTicketNum) {
    showToast("<i class='fa-solid fa-circle-xmark'></i> No hay ticket cargado");
    return;
  }

  const isFactura =
    document.getElementById("tkTipoDoc")?.textContent.includes("FACTURA") ||
    false;
  const tipo = isFactura ? "factura" : "ticket";

  const a = document.createElement("a");
  a.href = `./api/generarPDFTicket.php?id=${currentTicketNum}&tipo=${tipo}`;
  a.target = "_blank"; // Abrir en pestaña nueva para evitar bloqueos
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}

async function enviarTicketEmail() {
  const email = document.getElementById("tkEmailInput")?.value.trim() || "";
  const btn = document.getElementById("btnSendEmail");
  const errEmail = document.getElementById("tkEmailError");

  if (errEmail) errEmail.innerText = "";

  if (!email || !email.includes("@")) {
    showToast("<i class='fa-solid fa-circle-xmark'></i> Email inválido");
    if (errEmail) errEmail.innerText = "Email inválido";
    return;
  }

  if (!currentTicketNum) {
    showToast("<i class='fa-solid fa-circle-xmark'></i> No hay ticket cargado");
    return;
  }

  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando...';
  }

  try {
    const resp = await fetch("./api/enviarVentaEmail.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        numTicket: currentTicketNum,
        destinatario: email,
        tipo: "ticket",
      }),
    });
    const data = await resp.json();

    if (data.ok) {
      showToast(
        "<i class='fa-solid fa-circle-check'></i> Ticket enviado con éxito a: " +
          email,
      );
      const emailInput = document.getElementById("tkEmailInput");
      if (emailInput) emailInput.value = "";
    } else {
      throw new Error(data.error || "Error al enviar el email");
    }
  } catch (err) {
    console.error(err);
    showToast(
      "<i class='fa-solid fa-circle-xmark'></i> " +
        (err.message || "Error al enviar"),
    );
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Enviar';
    }
  }
}

function nuevaVenta() {
  const elTicketModal = document.getElementById("ticketModal");
  if (elTicketModal) elTicketModal.classList.remove("visible");
  clearCart();
}

function closeModal() {
  nuevaVenta();
}

async function devolverLinea(idLinea, numTicket) {
  abrirModalDevolucion(idLinea, numTicket);
}

async function devolverTicket(numTicket, fechaVenta, idCliente) {
  abrirModalAnulacionTicket(numTicket, fechaVenta, idCliente);
}

async function confirmarAnulacionTicket(numTicket) {
  const motivoBase = document.getElementById("returnReason").value;
  const nota = document.getElementById("returnNote").value.trim();
  const metodoReembolso =
    document.querySelector('input[name="metodoReembolso"]:checked')?.value ||
    "efectivo";
  const motivo = nota ? `${motivoBase}: ${nota}` : motivoBase;

  try {
    const reponerStock = document.getElementById("returnReponerStock")?.checked ?? true;
    const resp = await fetch("api/gestionDevolucion.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        accion: "devolverTicket",
        numTicket,
        motivo,
        metodoReembolso,
        reponerStock,
      }),
    });
    const r = await resp.json();
    if (r.ok) {
      document.getElementById("returnModal").classList.remove("visible");
      const numAbono = r.numTicketAbono;
      showToast(
        `<i class='fa-solid fa-check'></i> Abono <strong>A-${numAbono}</strong> generado correctamente`,
      );
      const v = await cargarVenta(numTicket);
      mostrarTicket(v, false);
      if (typeof updateVentaStatusUI === "function")
        updateVentaStatusUI(numTicket, v.status || v.estado);
    } else {
      throw new Error(r.error || "No se pudo anular el ticket");
    }
  } catch (e) {
    showToast("<i class='fa-solid fa-circle-xmark'></i> Error: " + e.message);
  }
}

function abrirModalAbonoParcial() {
  const modal = document.getElementById("abonoParcialModal");
  if (!modal) return;
  const inputImporte = document.getElementById("abonoImporte");
  if (inputImporte) inputImporte.value = "";
  const selectMetodo = document.getElementById("abonoMetodo");
  if (selectMetodo) selectMetodo.value = "efectivo";

  // [NUEVO] Mostrar el importe pendiente si lo tenemos
  const displayPendiente = document.getElementById("abonoPendienteDisplay");
  const valorPendiente = document.getElementById("abonoPendienteValor");
  if (displayPendiente && valorPendiente && window.currentVentaPendiente !== undefined) {
    valorPendiente.textContent = fmt(window.currentVentaPendiente);
    displayPendiente.classList.remove("d-none");
  } else if (displayPendiente) {
    displayPendiente.classList.add("d-none");
  }

  modal.classList.add("visible");
  if (inputImporte) setTimeout(() => inputImporte.focus(), 100);
}

async function procesarAbonoParcial() {
  const btn = document.querySelector("#abonoParcialModal .btn-save");
  const importeInput = document.getElementById("abonoImporte").value.trim();
  const importe = parseFloat(importeInput);
  const metodo = document.getElementById("abonoMetodo").value;

  if (!window.currentVentaId) {
    showToast("⚠️ No se pudo identificar la venta actual.");
    return;
  }

  if (isNaN(importe) || importe <= 0) {
    showToast("⚠️ Introduce un importe válido mayor que 0.");
    return;
  }

  if (
    window.currentVentaPendiente !== undefined &&
    importe - window.currentVentaPendiente > 0.001
  ) {
    showToast(
      "⚠️ El importe introducido (" +
        importe.toFixed(2) +
        "€) supera la cantidad pendiente (" +
        window.currentVentaPendiente.toFixed(2) +
        "€).",
    );
    return;
  }

  if (btn) {
    btn.disabled = true;
    btn.innerHTML =
      '<i class="fa-solid fa-spinner fa-spin"></i> Registrando...';
  }

  try {
    const resp = await fetch("./api/liquidarVenta.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        idVenta: window.currentVentaId,
        importe: importe,
        metodo: metodo,
      }),
    });

    const data = await resp.json();
    if (data.ok) {
      document.getElementById("abonoParcialModal").classList.remove("visible");
      showToast("✅ Abono registrado correctamente");
      // Recargar ticket actual para ver el pago reflejado
      verTicket(currentTicketNum);
    } else {
      throw new Error(data.error || "Error al registrar el abono");
    }
  } catch (err) {
    console.error(err);
    showToast("❌ " + err.message);
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.innerText = "Registrar Abono";
    }
  }
}

function updateVentaStatusUI(numTicket, nuevoEstado, extraData) {
  const el = document.getElementById("status-venta-" + numTicket);
  if (!el) return;

  if (nuevoEstado === "completada") {
    el.innerHTML = `
      <span class="status-pill status-active" title="Venta finalizada">
        <i class="fa-solid fa-check"></i>
      </span>
    `;
  } else if (nuevoEstado === "pendiente_pago") {
    const total = parseFloat(extraData.total || 0);
    const pagado = parseFloat(extraData.pagado_a_cuenta || 0);
    const pendiente = total - pagado;
    const fechaLimite = extraData.fecha_limite_pago;
    const vencida =
      fechaLimite && new Date(fechaLimite) < new Date().setHours(0, 0, 0, 0);

    el.innerHTML = `
      <span class="status-pill ${vencida ? "status-overdue" : "status-pending"}"
            title="${vencida ? "PAGO VENCIDO" : "Pendiente de cobro"} (Deuda: ${pendiente.toFixed(2).replace(".", ",")}€)">
        <i class="fa-solid ${vencida ? "fa-triangle-exclamation" : "fa-clock"}"></i>
        ${vencida ? "VENCIDA" : "PENDIENTE"}
      </span>
    `;
  } else if (nuevoEstado === "devuelta") {
    el.innerHTML = `
      <span class="status-pill" style="background: var(--red-light); color: var(--red); border-color: var(--red);" title="Venta devuelta">
        <i class="fa-solid fa-rotate-left"></i> Devuelta
      </span>
    `;
  } else if (nuevoEstado === "anulada") {
    el.innerHTML = `
      <span class="status-pill" style="background: var(--surface2); color: var(--text-muted);" title="Venta anulada">
        <i class="fa-solid fa-ban"></i> Anulada
      </span>
    `;
  }
}

function previewImageTPV(input, mode) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      document.getElementById(mode + "Emoji").value = e.target.result;
      const preview = document.getElementById(mode + "ImgPreview");
      preview.innerHTML = `<img src="${e.target.result}" style="width:100%; height:100%; object-fit:contain; border-radius:8px;">`;
    };
    reader.readAsDataURL(input.files[0]);
  }
}

// ── Admin ──────────────────────────────────────────────────────────────────────
function requireAdmin() {
  if (!isAdmin) {
    showToast(
      "<i class='fa-solid fa-triangle-exclamation'></i> Acceso restringido a administradores",
    );
    return false;
  }
  return true;
}

function addVariantToUI(mode, label = null, value = null) {
  const l =
    label || document.getElementById(mode + "NuevaVarianteLabel").value.trim();
  const v =
    value || document.getElementById(mode + "NuevaVarianteValue").value.trim();
  if (!l || !v) return;

  const container = document.getElementById(mode + "VariantesContainer");
  if (!container) return;

  const pill = document.createElement("div");
  pill.className = "cat-tab d-flex ai-center gap-8 p-4-12 fs-12";
  pill.dataset.label = l;
  pill.dataset.value = v;
  pill.innerHTML = `
    <span><strong>${l}:</strong> ${v}</span>
    <i class="fa-solid fa-xmark cursor-pointer opacity-70 hover-opacity-100" onclick="this.parentElement.remove()"></i>
  `;
  container.appendChild(pill);

  if (!label) {
    document.getElementById(mode + "NuevaVarianteLabel").value = "";
    document.getElementById(mode + "NuevaVarianteValue").value = "";
  }
}

function getVariantsFromUI(mode) {
  const container = document.getElementById(mode + "VariantesContainer");
  if (!container) return null;
  const pills = container.querySelectorAll("div");
  const variants = [];
  pills.forEach((p) => {
    variants.push({ label: p.dataset.label, valor: p.dataset.value });
  });
  return variants.length > 0 ? variants : null;
}

window.addVariantToUI = addVariantToUI;

function abrirModalNuevoProducto() {
  if (!requireAdmin()) return;
  document.getElementById("addName").value = "";
  document.getElementById("addSku").value = "";
  document.getElementById("addPrice").value = "";
  document.getElementById("addEmoji").value = "📦";
  document.getElementById("addCat").value = "audio";
  document.getElementById("addImgPreview").innerHTML =
    '<i class="fa-solid fa-image"></i>';
  document.getElementById("addVariantesContainer").innerHTML = "";
  document.querySelectorAll(".form-error").forEach((el) => (el.innerText = ""));
  document.getElementById("addModal").classList.add("visible");
}

async function guardarNuevoProducto() {
  const name = document.getElementById("addName").value.trim();
  const codigo = document.getElementById("addSku").value.trim();
  const price = parseFloat(document.getElementById("addPrice").value);
  const iva = parseFloat(document.getElementById("addIva").value) || 21;
  const icono = document.getElementById("addEmoji").value.trim();
  const cat = document.getElementById("addCat").value;

  document.querySelectorAll(".form-error").forEach((el) => (el.innerText = ""));
  try {
    const resp = await fetch("./api/gestionProducto.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        accion: "añadir",
        nombre: name,
        referencia: codigo,
        precio_venta: price,
        precio_coste: 0,
        iva,
        stock_actual: 0,
        stock_minimo: 0,
        meses_garantia: 24,
        icono,
        categoria: cat,
        descripcion: "",
        variantes: getVariantsFromUI("add"),
      }),
    });
    const data = await resp.json();

    if (!data.ok) {
      if (data.aErrores) {
        for (const [key, msg] of Object.entries(data.aErrores)) {
          const errEl = document.getElementById(
            "err-add" + key.charAt(0).toUpperCase() + key.slice(1),
          );
          if (errEl && msg) errEl.innerText = msg;
        }
        return;
      }
      throw new Error(data.error);
    }

    PRODUCTS.push(data.producto);
    document.getElementById("addModal").classList.remove("visible");
    renderProducts();
    showToast(
      "<i class='fa-solid fa-circle-check'></i> Producto añadido correctamente",
    );
  } catch (err) {
    showToast("<i class='fa-solid fa-circle-xmark'></i> " + err.message);
  }
}

function updateEditMargin() {
  const price = parseFloat(document.getElementById("editPrice")?.value) || 0;
  const cost = parseFloat(document.getElementById("editCost")?.value) || 0;
  const margin = price - cost;
  const marginPct = price > 0 ? (margin / price) * 100 : 0;

  const display = document.getElementById("editMarginDisplay");
  const pctDisplay = document.getElementById("editMarginPercent");
  const container = display?.parentElement;

  if (display) display.textContent = fmt(margin);
  if (pctDisplay) pctDisplay.textContent = marginPct.toFixed(1) + "%";

  if (container) {
    if (margin > 0) {
      container.style.color = "var(--green)";
    } else if (margin < 0) {
      container.style.color = "var(--red)";
    } else {
      container.style.color = "var(--text-muted)";
    }
  }
}

function toggleEditPrecisionUI() {
  const keepPrecision = document.getElementById('editMantenerPrecision').checked;
  const inputVenta = document.getElementById('editPrice');
  
  if (!keepPrecision) {
      const val = parseFloat(inputVenta.value.replace(',', '.')) || 0;
      inputVenta.value = val.toFixed(2);
  }
}

window.toggleEditPrecisionUI = toggleEditPrecisionUI;
window.updateEditMargin = updateEditMargin;

function editProduct(e, id) {
  e.stopPropagation();
  if (!requireAdmin()) return;
  const p = PRODUCTS.find((x) => x.id === id);
  const effPrice = typeof getEffectivePrice === "function" ? getEffectivePrice(p, socioActual) : p.price;
  window._currentEditFactor = p.price > 0.01 ? (effPrice / p.price) : 1;
  
  document.getElementById("editId").value = p.id;
  document.getElementById("editName").value = p.name;
  document.getElementById("editSku").value = p.codigo;
  document.getElementById("editPrice").value = effPrice;
  document.getElementById("editCost").value = p.precio_coste || 0;
  document.getElementById("editMesesGarantia").value = p.meses_garantia || 24;
  document.getElementById("editEmoji").value = p.icono;
  updateEditMargin();

  const preview = document.getElementById("editImgPreview");
  if (p.icono && p.icono.startsWith("data:image")) {
    preview.innerHTML = `<img src="${p.icono}" style="width:100%; height:100%; object-fit:contain; border-radius:8px;">`;
  } else {
    preview.innerHTML = `<span style="font-size: 24px;">${p.icono || "📦"}</span>`;
  }

  const container = document.getElementById("editVariantesContainer");
  if (container) {
    container.innerHTML = "";
    if (p.variantes) {
      try {
        const vars =
          typeof p.variantes === "string"
            ? JSON.parse(p.variantes)
            : p.variantes;
        if (Array.isArray(vars)) {
          vars.forEach((v) => addVariantToUI("edit", v.label, v.valor));
        } else {
          for (const [l, v] of Object.entries(vars))
            addVariantToUI("edit", l, v);
        }
      } catch (e) {
        console.error("Error parsing variantes", e);
      }
    }
  }

  document.querySelectorAll(".form-error").forEach((el) => (el.innerText = ""));
  document.getElementById("editModal").classList.add("visible");

  // Cargar variantes físicas si el producto las tiene
  if (p.atributos) {
    if (typeof cargarVariantesFisicasTPV === "function") {
      cargarVariantesFisicasTPV(id);
    }
  } else {
    const section = document.getElementById("sectionVariantesFisicasEdit");
    if (section) section.classList.add("d-none");
  }
}

async function saveEdit() {
  document.querySelectorAll(".form-error").forEach((el) => (el.innerText = ""));
  try {
    const id = parseInt(document.getElementById("editId")?.value);
    const name = document.getElementById("editName")?.value.trim();
    const codigo = document.getElementById("editSku")?.value.trim();
    const modalPrice = parseFloat(document.getElementById("editPrice")?.value);
    const priceToSave = modalPrice / (window._currentEditFactor || 1);
    const pOrig = PRODUCTS.find((x) => x.id === id);
    const iva = pOrig.iva || 21;
    const mesesGarantia =
      parseInt(document.getElementById("editMesesGarantia")?.value) || 24;
    const icono = document.getElementById("editEmoji")?.value.trim();

    const resp = await fetch("./api/gestionProducto.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        accion: "editar",
        id,
        nombre: name,
        referencia: codigo,
        precio_venta: priceToSave,
        precio_coste: pOrig.precio_coste || 0,
        iva,
        stock_actual: pOrig.stock || 0,
        stock_minimo: pOrig.stock_minimo || 0,
        meses_garantia: mesesGarantia,
        icono,
        categoria: pOrig.cat,
        descripcion: pOrig.descripcion || "",
        variantes: getVariantsFromUI("edit"),
        motivo_cambio_precio: "Cambio rápido desde TPV"
      }),
    });
    const data = await resp.json();

    if (!data.ok) {
      if (data.aErrores) {
        const msgs = Object.values(data.aErrores).filter(Boolean);
        showToast("❌ Errores de validación: " + msgs.join(" / "));
        for (const [key, msg] of Object.entries(data.aErrores)) {
          const errEl = document.getElementById(
            "err-edit" + key.charAt(0).toUpperCase() + key.slice(1),
          );
          if (errEl && msg) errEl.innerText = msg;
        }
        return;
      }
      throw new Error(data.error);
    }

    const p = PRODUCTS.find((x) => x.id === id);
    p.name = name;
    p.codigo = codigo;
    p.price = priceToSave;
    p.iva = iva;
    p.meses_garantia = mesesGarantia;
    p.icono = icono || p.icono;
    p.variantes = getVariantsFromUI("edit");

    document.getElementById("editModal").classList.remove("visible");
    renderProducts();
    showToast("✅ Producto actualizado correctamente");
  } catch (err) {
    showToast("<i class='fa-solid fa-circle-xmark'></i> " + err.message);
  }
}

function deleteProduct(e, id) {
  e.stopPropagation();
  if (!requireAdmin()) return;
  const p = PRODUCTS.find((x) => x.id === id);
  document.getElementById("delName").textContent = p.name;
  document.getElementById("delConfirmBtn").onclick = async () => {
    try {
      const resp = await fetch("./api/gestionProducto.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ accion: "eliminar", id }),
      });
      const data = await resp.json();
      if (!data.ok) throw new Error(data.error);

      PRODUCTS.splice(
        PRODUCTS.findIndex((x) => x.id === id),
        1,
      );
      if (cart[id]) {
        delete cart[id];
        renderCart();
      }
      document.getElementById("deleteModal").classList.remove("visible");
      renderProducts();
      showToast(
        "<i class='fa-solid fa-trash-can'></i> Producto eliminado de la BD",
      );
    } catch (err) {
      showToast("<i class='fa-solid fa-circle-xmark'></i> " + err.message);
    }
  };
  document.getElementById("deleteModal").classList.add("visible");
}

function abrirModalDevolucion(
  idLinea,
  numTicket,
  fechaVenta,
  idCliente,
  mesesGarantia,
  nombreProducto
) {
  const modal = document.getElementById("returnModal");
  if (!modal) return;

  modal.classList.add("visible");

  // 1. Resetear UI y estados
  const subtitle = document.getElementById("returnModalSubtitle");
  const stCommercial = document.getElementById("statusCommercial");
  const txtCommercial = document.getElementById("textCommercial");
  const stWarranty = document.getElementById("statusWarranty");
  const txtWarranty = document.getElementById("textWarranty");
  const optCash = document.getElementById("optCash");
  const optBalance = document.getElementById("optBalance");
  const optCard = document.getElementById("optCard");
  const optExchange = document.getElementById("optExchange");

  const productInfo = document.getElementById("returnProductInfo");
  const productName = document.getElementById("returnProductName");
  const productWarranty = document.getElementById("returnProductWarrantyBadge");
  const productStatus = document.getElementById("returnProductStatusBadge");

  // Limpiar clases previas
  [stCommercial, stWarranty].forEach((el) =>
    el.classList.remove("status-ok", "status-warn", "status-err"),
  );
  [optCash, optBalance, optCard, optExchange].forEach((el) => {
    if (el) {
        el.classList.remove("disabled");
        el.querySelector("input").disabled = false;
    }
  });

  // Mostrar info de producto
  if (productInfo) {
    productInfo.classList.remove("d-none");
    if (productName) productName.innerText = nombreProducto || "Producto";
    if (productWarranty) productWarranty.querySelector("span").innerText = `Garantía: ${mesesGarantia} meses`;
  }

  // 2. Calcular plazos
  const dateVenta = new Date(fechaVenta.replace(" ", "T"));
  const ahora = new Date();
  const diffTime = Math.abs(ahora - dateVenta);
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
  const diffMonths =
    (ahora.getFullYear() - dateVenta.getFullYear()) * 12 +
    (ahora.getMonth() - dateVenta.getMonth());

  // Plazo comercial (30 días)
  if (diffDays <= 15) {
    stCommercial.classList.add("status-ok");
    txtCommercial.innerText = `${diffDays} días transcurridos (Plazo OK)`;
  } else if (diffDays <= 30) {
    stCommercial.classList.add("status-warn");
    txtCommercial.innerText = `${diffDays} días (Límite 30 días)`;
  } else {
    stCommercial.classList.add("status-err");
    txtCommercial.innerText = `Excedido (${diffDays} días)`;
    // Deshabilitar reembolsos
    optCash.classList.add("disabled");
    optCash.querySelector("input").disabled = true;
    optBalance.classList.add("disabled");
    optBalance.querySelector("input").disabled = true;
    if (optCard) {
        optCard.classList.add("disabled");
        optCard.querySelector("input").disabled = true;
    }
  }

  // Plazo garantía
  if (diffMonths < mesesGarantia) {
    stWarranty.classList.add("status-ok");
    txtWarranty.innerText = `Hasta ${mesesGarantia} meses (OK)`;
    if (productStatus) {
        productStatus.classList.remove("d-none");
        productStatus.style.background = "var(--green-light)";
        productStatus.style.color = "var(--green)";
        productStatus.querySelector("span").innerText = "Garantía VIGENTE";
    }
  } else {
    stWarranty.classList.add("status-err");
    txtWarranty.innerText = `Garantía agotada`;
    if (productStatus) {
        productStatus.classList.remove("d-none");
        productStatus.style.background = "var(--red-light)";
        productStatus.style.color = "var(--red)";
        productStatus.querySelector("span").innerText = "Garantía AGOTADA";
    }
    optExchange.classList.add("disabled");
    optExchange.querySelector("input").disabled = true;
  }

  // Ya no restringimos "Vale / Cupón" a clientes registrados
  // La validación anterior ha sido eliminada.

  // Auto-seleccionar primera opción válida
  const availableInput = modal.querySelector(
    'input[name="metodoReembolso"]:not(:disabled)',
  );
  if (availableInput) availableInput.checked = true;

  subtitle.innerText = `Venta del ${dateVenta.toLocaleDateString()}`;

  document.getElementById("confirmReturnBtn").onclick = () =>
    confirmarDevolucion(idLinea, numTicket);
}

function abrirModalAnulacionTicket(numTicket, fechaVenta, idCliente) {
  const modal = document.getElementById("returnModal");
  if (!modal) return;

  modal.classList.add("visible");

  // 1. Resetear UI (usamos la misma lógica que en líneas individuales pero adaptada)
  const subtitle = document.getElementById("returnModalSubtitle");
  const stCommercial = document.getElementById("statusCommercial");
  const txtCommercial = document.getElementById("textCommercial");
  const stWarranty = document.getElementById("statusWarranty");
  const txtWarranty = document.getElementById("textWarranty");
  const optCash = document.getElementById("optCash");
  const optBalance = document.getElementById("optBalance");
  const optCard = document.getElementById("optCard");
  const optExchange = document.getElementById("optExchange");

  const productInfo = document.getElementById("returnProductInfo");
  if (productInfo) productInfo.classList.add("d-none");

  [stCommercial, stWarranty].forEach((el) =>
    el.classList.remove("status-ok", "status-warn", "status-err"),
  );
  [optCash, optBalance, optCard, optExchange].forEach((el) => {
    if (el) {
        el.classList.remove("disabled");
        el.querySelector("input").disabled = false;
    }
  });

  // La anulación de ticket COMPLETO es siempre por reembolso (comercial)
  const dateVenta = new Date(fechaVenta.replace(" ", "T"));
  const ahora = new Date();
  const diffDays = Math.floor(
    Math.abs(ahora - dateVenta) / (1000 * 60 * 60 * 24),
  );

  if (diffDays <= 30) {
    stCommercial.classList.add(diffDays <= 15 ? "status-ok" : "status-warn");
    txtCommercial.innerText = `${diffDays} días (Plazo Comercial OK)`;
  } else {
    stCommercial.classList.add("status-err");
    txtCommercial.innerText = `Excedido (${diffDays} días)`;
    [optCash, optBalance, optCard].forEach((el) => {
      if (el) {
        el.classList.add("disabled");
        el.querySelector("input").disabled = true;
      }
    });
  }

  // Deshabilitar "reemplazo" para ticket completo (es una anulación, no un cambio de garantía 1:1)
  optExchange.classList.add("disabled");
  optExchange.querySelector("input").disabled = true;
  stWarranty.style.opacity = "0.3";
  txtWarranty.innerText = "No aplica en anulación total";

  // Ya no restringimos "Vale / Cupón" a clientes registrados
  // La validación anterior ha sido eliminada.

  const availableInput = modal.querySelector(
    'input[name="metodoReembolso"]:not(:disabled)',
  );
  if (availableInput) availableInput.checked = true;

  subtitle.innerText = `Anulación Ticket #${String(numTicket).padStart(4, "0")}`;

  document.getElementById("confirmReturnBtn").onclick = () =>
    confirmarAnulacionTicket(numTicket);
}

async function confirmarDevolucion(idLinea, numTicket) {
  const motivo = document.getElementById("returnReason").value;
  const nota = document.getElementById("returnNote").value.trim();
  const metodoReembolso =
    document.querySelector('input[name="metodoReembolso"]:checked')?.value ||
    "efectivo";
  const cantidadEl = document.getElementById("returnQty");
  const cantidad = cantidadEl ? parseInt(cantidadEl.value) || null : null;
  const finalMotivo = nota ? `${motivo}: ${nota}` : motivo;

  try {
    const reponerStock = document.getElementById("returnReponerStock")?.checked ?? true;
    const resp = await fetch("./api/gestionDevolucion.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        accion: "devolverLinea",
        idLinea,
        cantidad,
        motivo: finalMotivo,
        metodoReembolso,
        reponerStock,
      }),
    });
    const data = await resp.json();
    if (!data.ok)
      throw new Error(data.error || "No se pudo realizar la devolución");

    document.getElementById("returnModal").classList.remove("visible");
    const numAbono = data.numTicketAbono;
    showToast(
      `<i class='fa-solid fa-check'></i> Abono <strong>A-${numAbono}</strong> generado correctamente`,
    );
    const ventaActualizada = await cargarVenta(numAbono);
    mostrarTicket(ventaActualizada, false);
    if (typeof updateVentaStatusUI === "function")
      updateVentaStatusUI(
        numTicket,
        ventaActualizada.status || ventaActualizada.estado,
      );
  } catch (err) {
    showToast("<i class='fa-solid fa-circle-xmark'></i> " + err.message);
  }
}

async function toggleBaja(e, id) {
  e.stopPropagation();
  if (!requireAdmin()) return;

  try {
    const resp = await fetch("./api/gestionProducto.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ accion: "baja", id }),
    });
    const data = await resp.json();
    if (!data.ok) throw new Error(data.error);

    const p = PRODUCTS.find((x) => x.id === id);
    p.inactive = !data.activo;

    if (p.inactive && cart[id]) {
      delete cart[id];
      renderCart();
    }
    renderProducts();
    showToast(
      p.inactive
        ? "<i class='fa-solid fa-pause'></i> Producto dado de baja"
        : "<i class='fa-solid fa-play'></i> Producto reactivado",
    );
  } catch (err) {
    showToast("❌ " + err.message);
  }
}

// ── Toast ──────────────────────────────────────────────────────────────────────
function showToast(msg) {
  let container = document.querySelector(".toast-container");
  if (!container) {
    container = document.createElement("div");
    container.className = "toast-container";
    document.body.appendChild(container);
  }

  const toast = document.createElement("div");
  toast.className = "toast";
  toast.innerHTML = msg;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.animation = "toastOut 0.5s ease forwards";
    setTimeout(() => toast.remove(), 500);
  }, 4000);
}

// ── Búsqueda y filtros ─────────────────────────────────────────────────────────
let searchTimeout = null;
function handleSearch(val) {
  searchTerm = val;
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(async () => {
    await loadMoreProducts(true);
  }, 300);
}

async function loadMoreProducts(reset = false) {
  if (tpvIsLoading) return;
  if (!reset && !tpvCanLoadMore) return;

  tpvIsLoading = true;
  if (reset) {
    tpvOffset = 0;
    tpvCanLoadMore = true;
    const grid = document.getElementById("productsGrid");
    if (grid) grid.innerHTML = '<div class="w-100 text-center p-40 opacity-50"><i class="fa-solid fa-circle-notch fa-spin fs-24 mb-12"></i><br>' + (I18N.loading || "Cargando catálogo...") + '</div>';
  }

  try {
    const resp = await fetch("api/gestionProducto.php?accion=listar", {
      method: "POST",
      body: JSON.stringify({
        limit: tpvLimit,
        offset: tpvOffset,
        term: searchTerm,
        cat: activeCat
      })
    });
    const r = await resp.json();
    if (r.ok) {
        const newProducts = (r.productos || []).map(p => ({
            id: p.id,
            name: p.nombre,
            codigo: p.referencia,
            price: p.precio_venta,
            icono: p.icono || '📦',
            cat: p.categoria,
            stock: p.stock,
            inactive: !p.activo,
            es_pack: p.es_pack,
            atributos: p.atributos,
            componentes_pack: p.componentes_pack
        }));

        if (reset) {
            tpvOffset = 0;
            tpvCanLoadMore = true; // Reiniciar para permitir scroll en la nueva categoría
            PRODUCTS.length = 0;
            const feedbackContainer = document.getElementById("loadingMoreFeedback");
            if (feedbackContainer) feedbackContainer.classList.add("hidden");
            console.log(`[TPV] Reiniciando carga para: ${activeCat}`);
        }
        
        PRODUCTS.push(...newProducts);
        tpvOffset += newProducts.length;

        if (newProducts.length < tpvLimit) {
            tpvCanLoadMore = false;
        }
        
        console.log(`[TPV] Cargados ${newProducts.length} productos. Nuevo offset: ${tpvOffset}. CanLoadMore: ${tpvCanLoadMore}`);
        renderProducts();
    }
  } catch (e) {
    console.error("Error loading products:", e);
  } finally {
    tpvIsLoading = false;
  }
}

function toggleAdvancedFilters() {
  const panel = document.getElementById("advancedFilters");
  if (panel) {
    const isHidden = panel.classList.contains("d-none");
    panel.classList.toggle("d-none");
    const btn = document.querySelector(".btn-filter-toggle");
    if (btn) btn.classList.toggle("is-open", !isHidden);
  }
}

function applyAdvancedFilters() {
  const elMin = document.getElementById("filterPriceMin");
  const elMax = document.getElementById("filterPriceMax");
  const elStock = document.getElementById("filterStock");
  const elSort = document.getElementById("filterSort");

  minPrice = parseFloat(elMin.value) || 0;
  maxPrice = parseFloat(elMax.value) || Infinity;
  stockFilter = elStock.value;
  sortOrder = elSort.value;

  renderProducts();
}

// ── Reloj ──────────────────────────────────────────────────────────────────────
function fmt(n) {
  return new Intl.NumberFormat("es-ES", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(n) + " €";
}

// Variables globales para fidelización (Portadas de PaymentManager)
var puntosCanjeados = 0;
var puntosDescuentoAmt = 0;
let clienteActualPuntos = 0;

function tick() {
  const clockEl = document.getElementById("clock");
  const dateEl = document.getElementById("datestr");
  const now = new Date();

  if (clockEl)
    clockEl.textContent = now.toLocaleTimeString("es-ES", {
      hour: "2-digit",
      minute: "2-digit",
    });
  if (dateEl)
    dateEl.textContent = now.toLocaleDateString("es-ES", {
      day: "2-digit",
      month: "short",
      year: "numeric",
    });
}
tick();
setInterval(tick, 1000);

if (typeof IS_TPV !== "undefined" && !IS_TPV) {
  const btnNV = document.getElementById("btnNuevaVenta");
  if (btnNV) btnNV.style.display = "none";
}

const ticketOverlay = document.getElementById("ticketModal");
if (ticketOverlay) {
  ticketOverlay.addEventListener("click", (e) => {
    if (e.target === ticketOverlay) nuevaVenta();
  });
}

// ── Inicialización ─────────────────────────────────────────────────────────────
const productsGrid = document.getElementById("productsGrid");

if (productsGrid) {
  if (typeof IS_TPV !== "undefined" && IS_TPV) {
    if (typeof CAJA_ABIERTA !== "undefined" && !CAJA_ABIERTA) {
      const modalApertura = document.getElementById("aperturaCajaModal");
      if (modalApertura) modalApertura.classList.add("visible");
    }
  }

  const catTabs = document.getElementById("catTabs");
  if (catTabs) {
    catTabs.addEventListener("click", (e) => {
      const tab = e.target.closest(".cat-tab");
      if (!tab) return;
      document
        .querySelectorAll(".cat-tab")
        .forEach((t) => t.classList.remove("active"));
      tab.classList.add("active");
      activeCat = tab.dataset.cat;
      loadMoreProducts(true);
    });
  }

  renderProducts();

  // Atributos (Tags) Navigation
  const attrTabs = document.getElementById("attrTabs");
  if (attrTabs) {
    attrTabs.addEventListener("click", (e) => {
      const tag = e.target.closest(".attr-tab-btn");
      if (!tag) return;
      tag.classList.toggle("active");
      applyAdvancedFilters(); // Llamamos a la lógica de filtros locales
    });
  }

  productsGrid.addEventListener("scroll", () => {
    if (productsGrid.scrollTop + productsGrid.clientHeight >= productsGrid.scrollHeight - 20) {
      loadMoreProducts();
    }
  });

  updatePostponeUI();
}

// [REMOVED] Comodín logic moved to modular architecture (CartManager.js / app.js)

