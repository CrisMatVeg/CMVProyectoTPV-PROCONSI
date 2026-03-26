console.log("main.js: Script loaded and executing (v15)");
const PRODUCTS = typeof DB_PRODUCTS !== "undefined" ? DB_PRODUCTS : [];


// ── Estado global ──────────────────────────────────────────────────────────────
let cart = JSON.parse(localStorage.getItem("tpv_cart")) || {};
if (Array.isArray(cart)) cart = {}; // Migration from previous bad state
let selectedPayment = "efectivo";
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
let currentTicketNum = null;
let socioActual = null;
const SOCIO_DISCOUNT = 5;
const PUNTOS_MIN_CANJE = 50;
const PUNTOS_VALOR_EURO = 0.05; // 50 pts = 2.5€ => 1 pt = 0.05€
let currentPromo = null;
const PROMOS = typeof DB_PROMOS !== "undefined" ? DB_PROMOS : [];
const TARIFAS = typeof DB_TARIFAS !== "undefined" ? DB_TARIFAS : [];
let valeAplicado = null;

function formatTicketNumber(numero, fecha, esFactura) {
  const prefix = esFactura ? "F" : "T";
  const d = new Date(fecha);
  const datePart = `${d.getDate()}${d.getMonth() + 1}${d.getFullYear()}`;
  return `${prefix}-${datePart}-${numero}`;
}

window.formatTicketNumber = formatTicketNumber;
let clienteSeleccionado = null;
let tipoClienteActual = "particular";
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
    if (t.tipo === "percent") {
      finalPrice *= 1 + val / 100;
    } else {
      finalPrice += val;
    }
  });

  return Math.max(0, finalPrice);
}

// ── Estado de venta pospuesta ──────────────────────────────────────────────────
let postponedSale = JSON.parse(sessionStorage.getItem("postponedSale")) || null;
let currentPayments = []; // [NUEVO] Para pagos mixtos
let totalVentaActual = 0; // [NUEVO] Para sincronizar con el modal de cobro
let checkoutContext = 'mixto'; // [NUEVO] Contexto original del sidebar

// ── Tema (modo + acento) ──────────────────────────────────────────────────────
function applyTheme(mode, accent) {
  const body = document.body;
  if (!body) return;
  if (mode) body.dataset.themeMode = mode;
  if (accent) body.dataset.themeAccent = accent;

  const modeButtons = {
    light: "themeModeLight",
    dark: "themeModeDark",
    black: "themeModeBlack",
  };
  const accentButtons = {
    blue: "themeAccentBlue",
    green: "themeAccentGreen",
    red: "themeAccentRed",
    purple: "themeAccentPurple",
    amber: "themeAccentAmber",
  };

  if (mode) {
    Object.entries(modeButtons).forEach(([m, id]) => {
      const btn = document.getElementById(id);
      if (btn) btn.classList.toggle("active", m === mode);
    });
  }
  if (accent) {
    Object.entries(accentButtons).forEach(([a, id]) => {
      const btn = document.getElementById(id);
      if (btn) btn.classList.toggle("active", a === accent);
    });
  }
}

function setThemeMode(mode) {
  applyTheme(mode, null);
  const input = document.getElementById("theme_mode_input");
  if (input) input.value = mode;
}

function setThemeAccent(accent) {
  applyTheme(null, accent);
  const input = document.getElementById("theme_accent_input");
  if (input) input.value = accent;
}

if (
  typeof USER_THEME_MODE !== "undefined" ||
  typeof USER_THEME_ACCENT !== "undefined"
) {
  applyTheme(USER_THEME_MODE || "light", USER_THEME_ACCENT || "blue");
}

// ── DOMContentLoaded ──────────────────────────────────────────────────────────
document.addEventListener("DOMContentLoaded", function () {
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

  // Atributos filters
  document.querySelectorAll(".attr-tab").forEach((btn) => {
    btn.addEventListener("click", () => {
      const isCurrentlyActive = btn.classList.contains("active");

      // Desmarcar todos primero
      document.querySelectorAll(".attr-tab").forEach((b) => {
        b.classList.remove("active");
      });

      if (isCurrentlyActive) {
        // Toggle off
        activeAttr = null;
      } else {
        // Toggle on
        btn.classList.add("active");
        activeAttr = btn.dataset.attr;
      }

      renderProducts();
    });
  });
});

// ── Formato monetario ──────────────────────────────────────────────────────────
function fmt(n) {
  const val = typeof n === "number" ? n : parseFloat(n) || 0;
  return val.toFixed(2).replace(".", ",") + " €";
}

// ── Catálogo ───────────────────────────────────────────────────────────────────
function renderProducts() {
  const grid = document.getElementById("productsGrid");
  if (!grid) return;

  let filtered = PRODUCTS.filter((p) => {
    let matchesCat = false;
    if (activeCat === "all") {
      matchesCat = true; // Show all products (active and inactive)
    } else if (activeCat === "baja") {
      matchesCat = p.inactive;
    } else {
      matchesCat = !p.inactive && p.cat === activeCat;
    }

    if (!matchesCat) return false;

    const matchesSearch =
      p.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      p.codigo.toLowerCase().includes(searchTerm.toLowerCase());
    if (!matchesSearch) return false;

    const matchesPrice =
      maxPrice === 999999
        ? true // No user-set filter, show all
        : p.price >= minPrice && p.price <= maxPrice;
    if (!matchesPrice) return false;

    let matchesStock = true;
    if (stockFilter === "in-stock") matchesStock = p.stock > 0;
    else if (stockFilter === "low-stock")
      matchesStock = p.stock > 0 && p.stock <= 5;
    if (!matchesStock) return false;

    if (activeAttr !== null) {
      if (!p.atributos) return false;
      try {
        const pAttrs =
          typeof p.atributos === "string"
            ? JSON.parse(p.atributos)
            : p.atributos;
        if (!Array.isArray(pAttrs) || !pAttrs.includes(activeAttr))
          return false;
      } catch (e) {
        return false;
      }
    }

    return true;
  });

  filtered.sort((a, b) => {
    switch (sortOrder) {
      case "name-asc":
        return a.name.localeCompare(b.name);
      case "name-desc":
        return b.name.localeCompare(a.name);
      case "price-asc":
        return a.price - b.price;
      case "price-desc":
        return b.price - a.price;
      case "stock-asc":
        return a.stock - b.stock;
      case "stock-desc":
        return b.stock - a.stock;
      default:
        return 0;
    }
  });

  grid.innerHTML = filtered
    .map(
      (p) => `
      <div class="product-card${p.inactive ? " inactive" : ""}${p.stock <= 0 ? " out-of-stock" : ""}" id="card-${p.id}" onclick="handleCardClick(event, ${p.id}, this)">
          ${p.inactive ? '<div class="baja-pill">Baja</div>' : ""}
          ${p.stock <= 0 ? '<div class="stock-pill" style="background:var(--red); color:white; position:absolute; top:10px; right:10px; padding:2px 8px; border-radius:10px; font-size:10px; font-weight:700;">AGOTADO</div>' : ""}
          ${(() => {
            const attr = p.atributos;
            if (!attr) return "";
            try {
              const parsedAttr =
                typeof attr === "string" ? JSON.parse(attr) : attr;
              if (Array.isArray(parsedAttr) && parsedAttr.length > 0) {
                return (
                  '<div style="position:absolute; top:35px; left:5px; display:flex; flex-direction:column; gap:4px;">' +
                  parsedAttr
                    .map(
                      (a) =>
                        `<span style="background:var(--accent-soft); color:var(--accent); font-size:9px; padding:2px 6px; border-radius:4px; font-weight:700; border:1px solid var(--accent); white-space:nowrap;">${a}</span>`,
                    )
                    .join("") +
                  "</div>"
                );
              }
            } catch (e) {}
            return "";
          })()}
          <div class="product-icon" style="background: white; border-radius: 8px;">
              ${
                p.icono && p.icono.startsWith("data:image")
                  ? `<img src="${p.icono}" class="prod-img-tpv" alt="${p.name}">`
                  : `<span class="product-emoji">${p.icono}</span>`
              }
          </div>
          <div>
              <div class="product-name">${p.name}</div>
              <div class="product-sku">${p.codigo}</div>
              <div class="product-stock" style="font-size:11px; color:${p.stock <= 5 ? "var(--red)" : "var(--text-muted)"}; font-weight:600;">Stock: ${p.stock}</div>
          </div>
          <div class="product-price" style="margin-top:auto">
            ${(() => {
              const eff = getEffectivePrice(p, socioActual);
              return fmt(eff);
            })()}
          </div>
          <div class="product-admin-bar">
              <button class="admin-action edit" onclick="editProduct(event,${p.id})"><i class="fa-solid fa-pen-to-square"></i> ${I18N.edit}</button>
              <button class="admin-action delete" onclick="deleteProduct(event,${p.id})"><i class="fa-solid fa-trash"></i> ${I18N.delete}</button>
              <button class="admin-action baja" onclick="toggleBaja(event,${p.id})"><i class="fa-solid ${p.inactive ? "fa-arrow-up" : "fa-arrow-down"}"></i> ${p.inactive ? I18N.active : I18N.inactive}</button>
          </div>
      </div>
    `,
    )
    .join("");

  grid.classList.toggle("is-admin", isAdmin);
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
  cart = {};
  discountPct = 0;
  socioActual = null;
  currentPromo = null;
  localStorage.removeItem("tpv_cart"); // Persist empty state
  const el_discountCode = document.getElementById("discountCode");
  const el_discountRow = document.getElementById("discountRow");
  const el_errDiscount = document.getElementById("err-discount");
  if (el_discountCode) el_discountCode.value = "";
  if (el_discountRow) el_discountRow.style.display = "none";
  if (el_errDiscount) el_errDiscount.innerText = "";
  renderCart();
}

function postponeSale() {
  if (Object.keys(cart).length === 0) {
    showToast(
      '<i class="fa-solid fa-circle-exclamation"></i> ' + I18N.cartEmpty,
    );
    return;
  }

  postponedSale = {
    cart: JSON.parse(JSON.stringify(cart)),
    discountPct,
    currentPromo,
    socioActual,
    selectedPayment,
  };

  sessionStorage.setItem("postponedSale", JSON.stringify(postponedSale));
  clearCart();
  updatePostponeUI();
  showToast('<i class="fa-solid fa-pause"></i> ' + I18N.salePostponed);
}

function resumeSale() {
  if (!postponedSale) return;

  if (Object.keys(cart).length > 0) {
    if (
      !confirm(
        I18N.confirmResume,
      )
    )
      return;
  }

  cart = postponedSale.cart;
  discountPct = postponedSale.discountPct;
  currentPromo = postponedSale.currentPromo;
  socioActual = postponedSale.socioActual;
  selectedPayment = postponedSale.selectedPayment;

  postponedSale = null;
  sessionStorage.removeItem("postponedSale");

  if (currentPromo) {
    const el_discountCode = document.getElementById("discountCode");
    if (el_discountCode) el_discountCode.value = currentPromo.code;
  }

  updatePostponeUI();
  renderCart();
  showToast('<i class="fa-solid fa-play"></i> ' + I18N.saleResumed);
}

function updatePostponeUI() {
  const btnResume = document.getElementById("btnResumeSale");
  if (btnResume) {
    btnResume.style.display = postponedSale ? "flex" : "none";
  }
}

// ── Auto-aplicación de promociones de pack (sin código) ───────────────────────
function autoApplyBundlePromos() {
  // Si ya hay una promo manual activa (con código), no la pisamos
  if (currentPromo && currentPromo.codigo) return;

  const items = Object.values(cart);
  if (!items.length) {
    if (currentPromo && !currentPromo._manual) currentPromo = null;
    return;
  }

  // Agrupar cantidades por id base del producto (suma de todas sus variantes)
  const qtyByBaseId = {};
  const catByBaseId = {};
  items.forEach((item) => {
    qtyByBaseId[item.id] = (qtyByBaseId[item.id] || 0) + item.qty;
    catByBaseId[item.id] = item.cat;
  });

  // Buscar la primera promo de pack sin código que aplique al carrito
  const autoPromo = PROMOS.find((p) => {
    if (p.tipo !== "bundle" && p.tipo !== "fixed_bundle") return false;
    if (p.codigo) return false;

    const buyQty = parseInt(p.bundle_buy_qty) || 0;
    if (buyQty < 2) return false;

    // Comprobar si algún producto (suma de variantes) activa la promo
    return Object.entries(qtyByBaseId).some(([baseId, totalQty]) => {
      if (totalQty < buyQty) return false;
      if (!p.id_producto && !p.categoria_code) return true;
      if (p.id_producto && p.id_producto == baseId) return true;
      if (p.categoria_code && p.categoria_code === catByBaseId[baseId])
        return true;
      return false;
    });
  });

  if (autoPromo) {
    if (!currentPromo || currentPromo.id !== autoPromo.id) {
      currentPromo = { ...autoPromo, _manual: false };
    }
  } else {
    if (currentPromo && !currentPromo._manual) currentPromo = null;
  }
}

function renderCart() {
  // Guardar estado en localStorage
  localStorage.setItem("tpv_cart", JSON.stringify(cart));

  // Auto-aplicar promos de pack antes de calcular totales
  autoApplyBundlePromos();

  const items = Object.values(cart);
  const container = document.getElementById("orderItems");
  const orderCountEl = document.getElementById("orderCount");

  if (!container || !orderCountEl) return;

  orderCountEl.textContent = items.reduce((a, b) => a + b.qty, 0);

  if (!items.length) {
    container.innerHTML = `
      <div class="empty-cart">
        <div class="empty-cart-icon"><i class="fa-solid fa-cart-shopping"></i></div>
        <div>Añade productos<br>al pedido</div>
      </div>`;
    updateTotals(0);
    return;
  }

  container.innerHTML = items
    .map(
      (item) => `
      <div class="order-item">
        <span class="order-item-emoji">
          ${
            item.icono && item.icono.startsWith("data:image")
              ? `<img src="${item.icono}" class="order-item-img" alt="${item.name}">`
              : item.icono
          }
        </span>
        <div class="order-item-info">
          <div class="order-item-name">${item.name}</div>
          ${
            item.variant
              ? `<div class="order-item-variant"><i class="fa-solid fa-tag"></i> ${item.variant}</div>`
              : ""
          }
          <div class="order-item-price">${fmt(item.price)} × ${item.qty}</div>
        </div>
        <div class="qty-ctrl">
          <button class="qty-btn" onclick="changeQty('${item.cartKey || item.id}', -1)"><i class="fa-solid fa-minus"></i></button>
          <span class="qty-val">${item.qty}</span>
          <button class="qty-btn" onclick="changeQty('${item.cartKey || item.id}', +1)"><i class="fa-solid fa-plus"></i></button>
        </div>
        <div class="order-item-total">${fmt(item.price * item.qty)}</div>
      </div>
    `,
    )
    .join("");

  const subtotal = items.reduce((a, b) => a + b.price * b.qty, 0);
  updateTotals(subtotal);
}

function updateTotals(initialSubtotal) {
  const items = Object.values(cart);
  let subtotal = 0;
  let bundleDiscountTotal = 0;

  // 1. Calcular descuentos por PACK (2x1, 3 por 10€, etc.)
  // Sumamos el subtotal por ítem primero
  items.forEach((item) => {
    subtotal += item.price * item.qty;
  });

  if (
    currentPromo &&
    (currentPromo.tipo === "bundle" || currentPromo.tipo === "fixed_bundle")
  ) {
    // Agrupar ítems por id base de producto (para manejar variantes)
    const groups = {};
    items.forEach((item) => {
      const key = item.id;
      if (!groups[key])
        groups[key] = { baseId: item.id, cat: item.cat, units: [] };
      for (let i = 0; i < item.qty; i++) groups[key].units.push(item.price);
    });

    Object.values(groups).forEach((group) => {
      let promoApplies = false;
      if (currentPromo.id_producto && currentPromo.id_producto == group.baseId)
        promoApplies = true;
      else if (
        currentPromo.categoria_code &&
        currentPromo.categoria_code === group.cat
      )
        promoApplies = true;
      else if (!currentPromo.id_producto && !currentPromo.categoria_code)
        promoApplies = true;

      if (!promoApplies) return;

      const buyQty = parseInt(currentPromo.bundle_buy_qty) || 0;
      const payQty = parseInt(currentPromo.bundle_pay_qty) || 0;
      const totalQty = group.units.length;

      if (currentPromo.tipo === "bundle" && buyQty > 0 && payQty > 0) {
        const sets = Math.floor(totalQty / buyQty);
        const freeUnits = sets * (buyQty - payQty);
        // La/s unidad/es más barata/s en cada set son las gratis
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

  // 2. Aplicar descuento global (cupón % o importe fijo sobre lo que queda)
  const subtotalAfterBundles = subtotal - bundleDiscountTotal;
  let generalDiscount = 0;

  if (
    currentPromo &&
    (currentPromo.tipo === "percent" || currentPromo.tipo === "amount")
  ) {
    if (currentPromo.tipo === "percent") {
      generalDiscount = (subtotalAfterBundles * currentPromo.valor) / 100;
    } else {
      generalDiscount = Math.min(subtotalAfterBundles, currentPromo.valor);
    }
  }

  // Descuento de socio (5% sobre el total tras otros descuentos)
  const socioAmt =
    socioActual && socioActual.es_socio
      ? (subtotalAfterBundles - generalDiscount) * (SOCIO_DISCOUNT / 100)
      : 0;

  const totalDiscount = bundleDiscountTotal + generalDiscount + socioAmt;
  const subtotalFinal = subtotal - totalDiscount;

  // Factor de prorrateo para el IVA (si el total tiene descuento, el IVA baja proporcionalmente)
  const discountFactor = subtotal > 0 ? subtotalFinal / subtotal : 1;

  // 3. Desglose de IVA por tipos para la UI
  const ivaGroups = {};
  items.forEach((item) => {
    const rate = parseFloat(item.iva || 21);
    const itemOrigSubtotal = item.price * item.qty;
    const itemDiscounted = itemOrigSubtotal * discountFactor;
    // Si el precio ya incluye IVA (PVP), el IVA contenido es: P * (iva/121)
    const itemBase = itemDiscounted / (1 + rate / 100);
    const itemTax = itemDiscounted - itemBase;

    if (!ivaGroups[rate]) {
      ivaGroups[rate] = { base: 0, tax: 0 };
    }
    ivaGroups[rate].base += itemBase;
    ivaGroups[rate].tax += itemTax;
  });

  const totalVat = Object.values(ivaGroups).reduce((acc, g) => acc + g.tax, 0);
  const total = subtotalFinal; // El total es el subtotal tras descuentos, ya incluye IVA (PVP)
  const baseImponible = subtotalFinal - totalVat;

  // Actualizar UI
  const elSubtotal = document.getElementById("subtotal");
  const elTotalAmt = document.getElementById("totalAmt");
  const elChargeTotal = document.getElementById("chargeTotal");
  const elDiscountAmt = document.getElementById("discountAmt");
  const elIvaBreakdown = document.getElementById("ivaBreakdown");

  if (!elSubtotal) return;

  // Mostramos la Base Imponible en el campo Subtotal
  elSubtotal.textContent = fmt(baseImponible);

  if (elTotalAmt) elTotalAmt.textContent = fmt(total);
  if (elChargeTotal) elChargeTotal.textContent = fmt(total);
  if (elDiscountAmt) elDiscountAmt.textContent = "-" + fmt(totalDiscount);

  const el_discountRow = document.getElementById("discountRow");
  if (el_discountRow) {
    el_discountRow.style.display = totalDiscount > 0 ? "flex" : "none";
  }

  // Renderizar desglose de IVA con el nuevo formato (Base por separado del IVA)
  if (elIvaBreakdown) {
    const rowSubtotal = document.getElementById("rowSubtotal");
    if (rowSubtotal) rowSubtotal.style.display = "none";

    elIvaBreakdown.innerHTML = Object.entries(ivaGroups)
      .map(
        ([rate, data]) => `
        <div class="total-row" style="margin-bottom: 2px;">
          <span>Base imponible (${rate}%)</span>
          <span>${fmt(data.base)}</span>
        </div>
        <div class="total-row" style="margin-bottom: 8px; border-bottom: 1px solid rgba(0,0,0,0.05); padding-bottom: 4px;">
          <span>IVA ${rate}%</span>
          <span>${fmt(data.tax)}</span>
        </div>
      `,
      )
      .join("");
  }

  const chargeBtn = document.getElementById("chargeBtn");
  if (chargeBtn) chargeBtn.disabled = subtotal === 0;

  // Sincronizar total global para el modal de cobro
  totalVentaActual = total;

  // Factura obligatoria si total >= 3000€
  const toggleFactura = document.getElementById("facturaToggle");
  if (toggleFactura) {
    if (total >= 3000) {
      toggleFactura.checked = true;
      toggleFactura.disabled = true;
    } else {
      toggleFactura.disabled = false;
    }
  }




  if (selectedPayment === "a_cuenta") {
    validarACuenta();
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


function cerrarModalCliente() {
  document.getElementById("clienteModal").classList.remove("visible");
  document.querySelectorAll(".form-error").forEach((el) => (el.innerText = ""));
}

function seleccionarTipoCliente(tipo) {
  tipoClienteActual = tipo;
  const btnP = document.getElementById("btnParticular");
  const btnE = document.getElementById("btnEmpresa");
  const btnS = document.getElementById("btnSocio");

  btnP.classList.remove("selected-type");
  btnE.classList.remove("selected-type");
  if (btnS) btnS.classList.remove("selected-type");

  document.getElementById("empresaDatos").classList.add("d-none");
  document.getElementById("socioBusqueda").classList.add("d-none");
  document.getElementById("socioRegistro").classList.add("d-none");

  const gen = document.getElementById("clienteBusquedaGenerica");
  if (gen) gen.classList.add("d-none");
  const reg = document.getElementById("clienteRegistro");
  if (reg) reg.classList.add("d-none");
  const btnAdd = document.getElementById("btnAddCliente");
  if (btnAdd) btnAdd.classList.add("d-none");
  clienteSeleccionado = null;

  if (tipo === "particular") {
    btnP.classList.add("selected-type");
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
    document.getElementById("socioBusqueda").classList.remove("d-none");
    setTimeout(() => document.getElementById("socioSearch").focus(), 100);
  } else {
    btnE.classList.add("selected-type");
    document.getElementById("empresaDatos").classList.remove("d-none");
    setTimeout(() => document.getElementById("empresaNombre").focus(), 100);
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

function mostrarRegistroCliente() {
  document.getElementById("clienteBusquedaGenerica").classList.add("d-none");
  document.getElementById("clienteRegistro").classList.remove("d-none");
  const searchTxt = document.getElementById("clienteSearch").value.trim();
  if (searchTxt) {
    // Si parece un NIF (letra al final o principio y números), lo ponemos en NIF
    if (/^[0-9XYZ]/.test(searchTxt)) {
      document.getElementById("newClienteNif").value = searchTxt;
    } else {
      document.getElementById("newClienteNombre").value = searchTxt;
    }
  }
  const tit = document.getElementById("clienteRegistroTitulo");
  if (tit)
    tit.innerText =
      I18N.newLabel.replace('(', '').replace(')', '') + ' ' +
      (tipoClienteActual === "empresa"
        ? I18N.empresa
        : I18N.particular);
}

function cancelarRegistroCliente() {
  document.getElementById("clienteRegistro").classList.add("d-none");
  document.getElementById("clienteBusquedaGenerica").classList.remove("d-none");
}

async function guardarNuevoCliente() {
  const nombre = document.getElementById("newClienteNombre").value.trim();
  const nif = document.getElementById("newClienteNif").value.trim();
  const tipo = tipoClienteActual; // particular o empresa

  if (!nombre) {
    alert(I18N.fieldRequired);
    return;
  }

  try {
    const resp = await fetch("api/gestionCliente.php", {
      method: "POST",
      body: JSON.stringify({
        accion: "registrar",
        nombre,
        nif,
        tipo,
        es_socio: 0,
      }),
    });
    const r = await resp.json();
    if (r.ok) {
      showToast("Cliente registrado y seleccionado");
      const clientObj = {
        id: r.id,
        nombre,
        nif,
        tipo,
        es_socio: false,
      };
      clienteSeleccionado = clientObj;

      if (tipo === "empresa") {
        document.getElementById("empresaNombre").value = nombre;
        document.getElementById("empresaNif").value = nif;
      }

      cancelarRegistroCliente();
      const resEl = document.getElementById("clienteResultados");
      if (resEl)
        resEl.innerHTML = `<i class="fa-solid fa-check-circle text-green"></i> ${nombre} (NUEVO)`;
      const btnAdd = document.getElementById("btnAddCliente");
      if (btnAdd) btnAdd.classList.add("d-none");

      renderCart();
    } else {
      alert("Error al registrar: " + r.error);
    }
  } catch (e) {
    console.error(e);
    alert("Error de conexión");
  }
}

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
  updateMixSummary(); // Actualizar el resumen si hay
}

async function processPayment() {
  // Sincronizar totalVentaActual desde el DOM (fuente de verdad actualizada por renderCart/UiController)
  const elTotalAmt = document.getElementById("totalAmt");
  if (elTotalAmt) {
    totalVentaActual = parseFloat(elTotalAmt.textContent.replace(/[^\d,-]/g, '').replace(',', '.')) || 0;
  }

  // 1. Sincronizar contexto y método desde el sidebar
  const activeSidebarBtn = document.querySelector(".pay-btn.selected");
  if (activeSidebarBtn) {
    checkoutContext = activeSidebarBtn.dataset.method;
    selectedPayment = checkoutContext;
  }

  // 2. Resetear selección de cliente a Particular por defecto (si no hay uno ya activo)
  if (!clienteSeleccionado && !socioActual) {
    document.getElementById("btnParticular").click();
  }
  
  currentPayments = []; // Reset pagos mixtos

  // 2. Si hay un vale aplicado, lo añadimos como primer pago
  if (valeAplicado) {
    currentPayments.push({
      metodo: "vale",
      importe: Math.min(totalVentaActual, parseFloat(valeAplicado.importe_restante)),
      label: `Vale: ${valeAplicado.codigo}`,
    });
  }

  // Si hay puntos canjeados, los añadimos como un pago
  if (puntosCanjeados > 0 && puntosDescuentoAmt > 0) {
    currentPayments.push({
      metodo: "puntos",
      importe: Math.min(totalVentaActual - currentPayments.reduce((acc, p) => acc + p.importe, 0), puntosDescuentoAmt),
      label: `Puntos: ${puntosCanjeados}`,
    });
  }

  // 3. Pre-añadir el método seleccionado en el sidebar (Efectivo por defecto)
  const totalPagadoValesPuntos = currentPayments.reduce((acc, p) => acc + p.importe, 0);
  const pendiente = Math.max(0, totalVentaActual - totalPagadoValesPuntos);

  // Pre-añadimos siempre que no sea mixto (incluso efectivo ahora para simplificar el modal)
  if (pendiente > 0.01 && selectedPayment && selectedPayment !== 'mixto') {
    // Si ya existe un pago de este método, lo actualizamos en lugar de añadir otro
    const existing = currentPayments.find(p => p.metodo === selectedPayment);
    if (existing) {
        existing.importe = pendiente;
        existing.recibido = selectedPayment === 'efectivo' ? pendiente : pendiente;
    } else {
        // Si es a_cuenta, solo lo pre-añadimos si hay un cliente seleccionado
        const tieneCliente = !!(clienteSeleccionado || socioActual);
        
        if (selectedPayment !== 'a_cuenta' || tieneCliente) {
          currentPayments.push({
            metodo: selectedPayment,
            importe: pendiente,
            recibido: selectedPayment === 'efectivo' ? pendiente : pendiente,
            label: selectedPayment.charAt(0).toUpperCase() + selectedPayment.slice(1).replace('_', ' ')
          });
        }
    }
  }

  abrirModalPago();
  updateMixSummary();
  
  // 4. Lógica de UI Condicional: Ocultar selector si no es mixto
  const el_selector = document.getElementById("selectorMetodoPago");
  const el_pagoMontoArea = document.getElementById("pagoMontoArea");
  const el_btnAnadir = el_pagoMontoArea ? el_pagoMontoArea.querySelector("button") : null;
  const el_labelMonto = document.getElementById("labelMontoPago");
  const el_gestionArea = document.getElementById("cobroMixtoGestion");

  // Función helper para mostrar un elemento superando el !important de updateMixSummary
  function forceShow(el) {
    if (!el) return;
    el.style.removeProperty("display");
    el.classList.remove("d-none");
    el.style.display = "flex";
  }
  function forceHide(el) {
    if (!el) return;
    el.style.setProperty("display", "none", "important");
  }

  if (selectedPayment === 'mixto') {
      if (el_selector) el_selector.classList.remove("d-none");
      if (el_gestionArea) el_gestionArea.classList.remove("d-none");
      if (el_labelMonto) el_labelMonto.textContent = "Importe a añadir (€)";
      forceShow(el_pagoMontoArea);
  } else {
      // Es un método individual: ocultar selector de métodos del modal
      if (el_selector) el_selector.classList.add("d-none");

      if (selectedPayment === 'efectivo') {
          if (el_labelMonto) el_labelMonto.textContent = "Efectivo Recibido (€)";
          if (el_gestionArea) el_gestionArea.classList.remove("d-none");
          // Mostrar área de input superando !important
          forceShow(el_pagoMontoArea);
          // Ocultar botón "Añadir" pero mantener el input visible
          if (el_btnAnadir) el_btnAnadir.classList.add("d-none");
          // Seleccionar efectivo en el selector de modal (aunque esté oculto)
          const btnEfe = document.getElementById("btnEfectivo");
          if (btnEfe) selectModalPayment(btnEfe);
          // Focus en el input de monto
          setTimeout(() => {
            const inputMonto = document.getElementById("mixPagoMonto");
            if (inputMonto) { inputMonto.value = ""; inputMonto.focus(); }
          }, 100);
      } else if (selectedPayment === 'a_cuenta') {
          const tieneCliente = !!(clienteSeleccionado || socioActual);
          if (tieneCliente) {
              if (el_gestionArea) el_gestionArea.classList.add("d-none");
          } else {
              if (el_labelMonto) el_labelMonto.textContent = "Importe Fiado";
              if (el_gestionArea) el_gestionArea.classList.remove("d-none");
              forceShow(el_pagoMontoArea);
              const btnAcu = document.getElementById("btnAcuenta");
              if (btnAcu) selectModalPayment(btnAcu);
          }
      } else {
          // Tarjeta, Bizum: ya se añadieron automáticamente arriba, ocultar área de input
          forceHide(el_pagoMontoArea);
          if (el_gestionArea) el_gestionArea.classList.add("d-none");
      }
  }
}

 // ── Pagos Mixtos (Split Payments) ─────────────────────────────────────────────
function updateMixSummary() {
  const totalPagado = currentPayments.reduce((acc, p) => acc + p.importe, 0);
  const pendiente = Math.max(0, totalVentaActual - totalPagado);

  const el_Total = document.getElementById("mixTotalVenta");
  const el_Pagado = document.getElementById("mixTotalPagado");
  const el_Pendiente = document.getElementById("mixTotalPendiente");

  if (el_Total) el_Total.textContent = fmt(totalVentaActual);
  if (el_Pagado) el_Pagado.textContent = fmt(totalPagado);
  
  const el_PendienteRow = el_Pendiente?.parentElement;
  const el_Lista = document.getElementById("mixListaPagos");
  const el_BtnAdd = document.getElementById("mixBtnAddPago");

  if (el_Pendiente) {
    el_Pendiente.textContent = fmt(pendiente);
    el_Pendiente.parentElement.classList.toggle("text-red", pendiente > 0.01);
    el_Pendiente.parentElement.classList.toggle("text-green", pendiente <= 0.01);
  }

  // Si no es mixto, ocultamos la lista de pagos, el selector y el botón de AÑADIR (petición usuario)
  if (checkoutContext !== 'mixto') {
      if (el_Lista) el_Lista.style.setProperty("display", "none", "important");
      if (el_PendienteRow) el_PendienteRow.style.setProperty("display", "none", "important");
      if (el_BtnAdd) el_BtnAdd.style.setProperty("display", "none", "important");
      
      const el_selector = document.getElementById("selectorMetodoPago");
      if (el_selector) el_selector.style.setProperty("display", "none", "important");

      // Ocultar también cualquier fila extra del resumen excepto el Total
      const resContainer = document.getElementById("pagosMixResumen");
      if (resContainer) {
          Array.from(resContainer.children).forEach((child, idx) => {
              if (idx > 0) child.style.setProperty("display", "none", "important");
          });
      }
  } else {
      if (el_Lista) el_Lista.style.display = "";
      if (el_PendienteRow) el_PendienteRow.style.display = "";
      if (el_BtnAdd) el_BtnAdd.style.display = "";
      const el_selector = document.getElementById("selectorMetodoPago");
      if (el_selector) el_selector.style.display = "";

      // [NUEVO] Restricción: No permitir 'A Cuenta' en cobros mixtos
      const bAcu = document.getElementById("btnAcuenta");
      if (bAcu) bAcu.style.setProperty("display", "none", "important");

      const resContainer = document.getElementById("pagosMixResumen");
      if (resContainer) {
          Array.from(resContainer.children).forEach((child) => {
              child.style.display = "";
          });
      }
  }

  // Si ya no queda nada, habilitar botón de cobrar
  const btnFinal = document.getElementById("confirmarClienteBtn");
  if (btnFinal) {
    btnFinal.disabled = (pendiente > 0.01 && currentPayments.length > 0) || (currentPayments.length === 0 && pendiente > 0.01);
    // Realmente, si hay pagos y el pendiente es 0, habilitamos.
    // Si no hay pagos, se puede cobrar si el total es > 0? No, en mixto debe haber pagos.
    btnFinal.disabled = pendiente > 0.01;
  }

  renderPaymentsList();
}

function renderPaymentsList() {
  const container = document.getElementById("mixListaPagos");
  if (!container) return;

  if (currentPayments.length === 0) {
    container.innerHTML = `<div class="p-12 text-center opacity-50 fs-12 italic border-2 br-8 dashed" style="border-style: dashed;">${I18N.noPayments}</div>`;
    return;
  }

  const icons = {
    efectivo: "fa-money-bill-1",
    tarjeta: "fa-credit-card",
    bizum: "fa-mobile-screen",
    a_cuenta: "fa-file-invoice-dollar",
    vale: "fa-ticket",
    puntos: "fa-star"
  };

  container.innerHTML = currentPayments
    .map(
      (p, index) => `
    <div class="d-flex ai-center jc-space-between p-8-12 bg-surface1 br-8 border-1 mb-4 animate-slide-right">
        <div class="d-flex ai-center gap-8">
            <i class="fa-solid ${icons[p.metodo] || "fa-wallet"} opacity-70"></i>
            <span class="badge ${p.metodo === "efectivo" ? "bg-accent text-white" : "bg-surface3 border-1 text-primary"} p-2-6 br-4 fs-9 font-bold uppercase">${I18N[p.metodo] || p.metodo.replace("_", " ")}</span>
            <span class="font-mono font-bold">${fmt(p.importe)}</span>
        </div>
        <button onclick="removePagoMixto(${index})" class="text-red border-none bg-none cursor-pointer hover-scale p-4">
            <i class="fa-solid fa-trash-can"></i>
        </button>
    </div>
  `,
    )
    .join("");
}

function selectModalPayment(btn) {
  // Limpiar selección previa de botones del selector
  document.querySelectorAll(".btn-tpv-method").forEach((b) => {
    b.classList.remove("border-accent", "bg-accent-soft");
    b.style.borderColor = "";
  });

  btn.classList.add("border-accent", "bg-accent-soft");
  btn.style.borderColor = "var(--accent)";

  const metodo = btn.id.replace("btn", "").toLowerCase();
  selectedPayment = metodo === "acuenta" ? "a_cuenta" : metodo;

  // Mostrar área de monto forzando sobre el !important de updateMixSummary
  const area = document.getElementById("pagoMontoArea");
  if (area) {
    area.style.removeProperty("display");
    area.classList.remove("d-none");
    area.style.display = "block";
  }

  // Ajustar labels y visibilidad de extras
  document.getElementById("extraEfectivo").classList.toggle("d-none", metodo !== "efectivo");
  document.getElementById("extraAcuenta").classList.toggle("d-none", metodo !== "acuenta");

  // Pre-rellenar con lo que falta
  const totalPagado = currentPayments.reduce((acc, p) => acc + p.importe, 0);
  const pendiente = Math.max(0, totalVentaActual - totalPagado);
  const inputMonto = document.getElementById("mixPagoMonto");
  if (inputMonto) {
    inputMonto.value = pendiente.toFixed(2);
    inputMonto.focus();
    inputMonto.select();
    calcularCambioMix();
  }

  // Auto-scroll para que el input sea visible en el modal
  setTimeout(() => {
    if (area) area.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }, 50);
}


function addPagoMixto() {
  const inputMonto = document.getElementById("mixPagoMonto");
  const importeIngresado = parseFloat(inputMonto.value) || 0;

  if (importeIngresado <= 0) {
    showToast(`<i class='fa-solid fa-circle-exclamation'></i> ${I18N.enterAmount}`);
    return;
  }

  // Verificamos si es a_cuenta y si hay cliente
  if (selectedPayment === "a_cuenta") {
    const clienteId = tipoClienteActual === 'socio' ? (socioActual ? socioActual.id : null) : (clienteSeleccionado ? clienteSeleccionado.id : null);
    if (!clienteId) {
      document.getElementById("aCuentaAlertaCliente").classList.remove("d-none");
      showToast(`<i class='fa-solid fa-user-xmark'></i> ${I18N.clientNotFound}`);
      return;
    }
    const fecha = document.getElementById("aCuentaFechaLimite").value;
    if (!fecha) {
      showToast(`<i class='fa-solid fa-calendar-xmark'></i> ${I18N.enterDate}`);
      return;
    }
  }

  const totalPagado = currentPayments.reduce((acc, p) => acc + p.importe, 0);
  const pendiente = Math.max(0, totalVentaActual - totalPagado);
  
  // Sincronizar contexto desde el sidebar (Back-up por si falló la global)
  const activeSideBtn = document.querySelector(".pay-btn.selected");
  const currentCtx = activeSideBtn ? activeSideBtn.dataset.method : checkoutContext;

  // VALIDACIÓN SEGURIDAD: Si venimos de un modo individual "Efectivo", bloquear pagos parciales (petición usuario)
  if (currentCtx === 'efectivo' && importeIngresado < pendiente) {
    showToast(`<i class='fa-solid fa-circle-exclamation'></i> ${I18N.cashTotalRequired}`);
    return;
  }

  const importeAplicado = Math.min(importeIngresado, pendiente);

  // Añadir pago
  currentPayments.push({
    metodo: selectedPayment,
    importe: importeAplicado,
    recibido: selectedPayment === "efectivo" ? importeIngresado : importeAplicado,
    fecha_limite: selectedPayment === "a_cuenta" ? document.getElementById("aCuentaFechaLimite").value : null,
  });

  // Limpiar y actualizar
  inputMonto.value = "";
  const cambioEl = document.getElementById("efectivoCambio");
  if (cambioEl) cambioEl.textContent = "0,00 €";

  updateMixSummary();
  showToast(`<i class='fa-solid fa-circle-check'></i> ${I18N.paymentIdentified}`);

  // Resetear selección de botones
  document.querySelectorAll(".btn-tpv-method").forEach((b) => {
    b.classList.remove("border-accent", "bg-accent-soft");
    b.style.borderColor = "";
  });
  document.getElementById("pagoMontoArea").classList.add("d-none");
}

function removePagoMixto(index) {
  currentPayments.splice(index, 1);
  updateMixSummary();
}

function calcularCambioMix() {
  const inputMonto = document.getElementById("mixPagoMonto");
  const montoIngresado = parseFloat(inputMonto?.value) || 0;
  
  const totalPagado = currentPayments.reduce((acc, p) => acc + p.importe, 0);
  const pendiente = Math.max(0, totalVentaActual - totalPagado);

  const el_labelMonto = document.getElementById("labelMontoPago");
  const cambioEl = document.getElementById("efectivoCambio");
  if (!cambioEl) return;

  if (selectedPayment === "efectivo" && montoIngresado > 0) {
    // Si es un pago individual de efectivo (no mixto), actualizamos el recibido del pago de efectivo real
    if (checkoutContext === 'efectivo') {
        const pagoEfeActual = currentPayments.find(p => p.metodo === 'efectivo');
        if (pagoEfeActual) {
            pagoEfeActual.recibido = montoIngresado;
        }
    }

    // Calcular cambio relativo al total de la venta si es el único pago
    const totalPagar = totalVentaActual - (valeAplicado ? valeAplicado.importe : 0) - puntosDescuentoAmt;
    if (montoIngresado > totalPagar) {
        cambioEl.textContent = fmt(montoIngresado - totalPagar);
    } else {
        cambioEl.textContent = "0,00 €";
    }
  } else if (selectedPayment === "efectivo" && montoIngresado > pendiente) {
    const cambio = montoIngresado - pendiente;
    cambioEl.textContent = fmt(cambio);
  } else {
    cambioEl.textContent = "0,00 €";
  }

  // [NUEVO] Actualizar el resumen para que se vea el cambio si se ha modificado el recibido
  updateMixSummary();
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
  document.getElementById("clienteModal").classList.add("visible");
  validarACuenta();
}

async function ejecutarCobroFinal() {
  const btn = document.getElementById("confirmarClienteBtn");
  btn.disabled = true;
  btn.textContent = I18N.saving;

  const items = Object.values(cart);

  // ── Replicar exactamente el cálculo de updateTotals ──────────────────────────
  let subtotal = 0;
  let bundleDiscountTotal = 0;

  // Acumular subtotal linea a linea
  items.forEach((item) => {
    subtotal += item.price * item.qty;
  });

  // Descuento de pack (bundle) — agrupa variantes del mismo producto
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

  // Descuento de cupón (% o importe fijo)
  let couponDiscount = 0;
  if (
    currentPromo &&
    (currentPromo.tipo === "percent" || currentPromo.tipo === "amount")
  ) {
    if (currentPromo.tipo === "percent") {
      couponDiscount = (subtotalAfterBundles * currentPromo.valor) / 100;
    } else {
      couponDiscount = Math.min(subtotalAfterBundles, currentPromo.valor);
    }
  }

  // Descuento de socio (5%)
  const socioAmt =
    socioActual && socioActual.es_socio
      ? (subtotalAfterBundles - couponDiscount) * (SOCIO_DISCOUNT / 100)
      : 0;
  
  // Descuento por puntos
  const puntosAmt = puntosDescuentoAmt;

  const totalDiscountAmt = bundleDiscountTotal + couponDiscount + socioAmt + puntosAmt;
  const subtotalFinal = subtotal - totalDiscountAmt;

  // IVA prorateado
  const discountFactor = subtotal > 0 ? subtotalFinal / subtotal : 1;
  let vatTotal = 0;
  items.forEach((item) => {
    const line = item.price * item.qty * discountFactor;
    vatTotal += line * (item.iva / 100);
  });
  const totalFinal = subtotalFinal + vatTotal;

  // Etiqueta del descuento para el ticket
  let descuentoLabel = "";
  if (bundleDiscountTotal > 0 && currentPromo) {
    descuentoLabel =
      currentPromo.label ||
      `${currentPromo.bundle_buy_qty}x${currentPromo.bundle_pay_qty}`;
  }
  if (socioAmt > 0) {
    descuentoLabel +=
      (descuentoLabel ? " + " : "") + I18N.socio + " " + SOCIO_DISCOUNT + "%";
  }
  if (couponDiscount > 0 && currentPromo && currentPromo.codigo) {
    descuentoLabel +=
      (descuentoLabel ? " + " : "") + "Cupón " + currentPromo.codigo;
  }
  if (puntosAmt > 0) {
    descuentoLabel +=
      (descuentoLabel ? " + " : "") + I18N.pointsRedeemedLabel.replace('{amount}', puntosCanjeados);
  }
  // ──────────────────────────────────────────────────────────────────────────────

  const clienteId =
    tipoClienteActual === "socio"
      ? socioActual
        ? socioActual.id
        : null
      : clienteSeleccionado
        ? clienteSeleccionado.id
        : null;

  const facturaActiva =
    document.getElementById("facturaToggle") &&
    document.getElementById("facturaToggle").checked;
  if (facturaActiva && tipoClienteActual === "particular") {
    const nombreCli =
      document.getElementById("newClienteNombre")?.value ||
      (clienteSeleccionado ? clienteSeleccionado.nombre : null);
    if (!clienteId && !nombreCli) {
      showToast(I18N.facturaRequired);
      btn.disabled = false;
      btn.textContent = I18N.charge;
      return;
    }
  }

  console.log("ejecutarCobroFinal: Procediendo con el cobro...", checkoutContext);

  // 1. VALIDACIÓN SEGURIDAD: Efectivo no puede ser inferior al total si es el único pago (petición usuario)
  if (checkoutContext === "efectivo") {
    // Buscamos el pago de efectivo que hemos pre-añadido al 100%
    const pagoEfe = currentPayments.find((p) => p.metodo === "efectivo");
    const totalVenta = totalVentaActual - (valeAplicado ? valeAplicado.importe : 0) - puntosDescuentoAmt;

    if (!pagoEfe || (parseFloat(pagoEfe.recibido) || 0) < totalVenta - 0.01) {
      showToast(
        `<i class='fa-solid fa-circle-exclamation'></i> ${I18N.cashTotalRequired}`,
      );
      btn.disabled = false;
      btn.textContent = I18N.charge;
      return;
    }
  }

  const totalEfectivoRecibido = currentPayments
    .filter((p) => p.metodo === "efectivo")
    .reduce((sum, p) => sum + (p.recibido || p.importe), 0);

  const totalOtrosPagos = currentPayments
    .filter((p) => p.metodo !== "efectivo")
    .reduce((sum, p) => sum + p.importe, 0);

  const totalVenta = totalVentaActual - (valeAplicado ? valeAplicado.importe : 0) - puntosDescuentoAmt;
  const efectivoNecesario = Math.max(0, totalVenta - totalOtrosPagos);
  const cambioARetornar = Math.max(0, totalEfectivoRecibido - efectivoNecesario);

  // 2. VALIDACIÓN CAJA: No permitir devolución si no hay efectivo suficiente en el cajón
  if (cambioARetornar > 0.01) {
    try {
      const respCaja = await fetch("./api/cajaEstadoActual.php").then(r => r.json());
      if (respCaja.ok) {
        const efectivoEnCaja = parseFloat(respCaja.efectivoActual) || 0;
        if (cambioARetornar > efectivoEnCaja + 0.01) {
          showToast(
            `<i class='fa-solid fa-vault'></i> No hay suficiente efectivo en caja para devolver el cambio (${fmt(cambioARetornar)}). Disponible: ${fmt(efectivoEnCaja)}`,
          );
          btn.disabled = false;
          btn.textContent = "Cobrar";
          return;
        }
      }
    } catch (errCaja) {
      console.error("Error validando saldo de caja:", errCaja);
      // Opcional: ¿Bloqueamos si falla la API de caja? Por seguridad, mejor solo loguear si no es crítico.
    }
  }

  const payload = {
    tipoCliente: tipoClienteActual,
    nombreCliente:
      tipoClienteActual === "empresa"
        ? document.getElementById("empresaNombre").value
        : socioActual
          ? socioActual.nombre
          : clienteSeleccionado
            ? clienteSeleccionado.nombre
            : null,
    nifCliente:
      tipoClienteActual === "empresa"
        ? document.getElementById("empresaNif").value
        : socioActual
          ? socioActual.nif
          : clienteSeleccionado
            ? clienteSeleccionado.nif
            : null,
    idCliente: clienteId,
    esFactura: facturaActiva ? 1 : 0,
    comentarios:
      document.getElementById("ticketComentarios")?.value.trim() || null,
    metodoPago:
      currentPayments.filter(p => parseFloat(p.importe) > 0).length === 1 
        ? currentPayments.filter(p => parseFloat(p.importe) > 0)[0].metodo 
        : "mixto",
    pagos: currentPayments.filter(p => parseFloat(p.importe) > 0),
    subtotal,
    total: totalFinal,
    descuentoAmt: totalDiscountAmt,
    descuentoLabel,
    puntosGanados: Math.floor(totalVentaActual),
    puntosCanjeados: puntosCanjeados,
    puntosDescuentoAmt: puntosDescuentoAmt,
    lineas: items.map((it) => ({
      id: it.id,
      name: it.variant ? `${it.name} (${it.variant})` : it.name,
      id_variante: it.variant_id || null,
      codigo: it.codigo,
      price: it.price,
      qty: it.qty,
      serials: it.serials || [],
    })),
    efectivo: {
      recibido: totalEfectivoRecibido,
    },
    idVale: valeAplicado ? valeAplicado.id : null,
    codigoVale: valeAplicado ? valeAplicado.codigo : null,
    importeVale: valeAplicado
      ? Math.min(totalFinal, parseFloat(valeAplicado.importe_restante))
      : 0,
    puntosCanjeados: puntosCanjeados,
    importePuntosCanjeados: puntosDescuentoAmt,
    codigoCupon:
      currentPromo && currentPromo.codigo ? currentPromo.codigo : null,
  };


  try {
    const resp = await fetch("./api/guardarVenta.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    const data = await resp.json();

    if (!data.ok) {
      if (data.aErrores) {
        for (const [key, msg] of Object.entries(data.aErrores)) {
          const errEl = document.getElementById("err-" + key);
          if (errEl && msg) errEl.innerText = msg;
        }
        return;
      }
      throw new Error(data.error || "Error al guardar la venta");
    }

    if (data.venta) {
      data.venta.efectivo_recibido = payload.efectivo.recibido;
      currentTicketNum = data.venta.numero_ticket;
      mostrarTicket(data.venta);
      cerrarModalCliente();
      clearCart();
      showToast(
        `<i class='fa-solid fa-circle-check'></i> ${I18N.successSave}`,
      );
    } else {
      throw new Error("La API no devolvió los datos de la venta.");
    }
  } catch (err) {
    console.error("Error en confirmarCliente:", err);
    showToast("<i class='fa-solid fa-circle-xmark'></i> " + err.message);
  } finally {
    btn.disabled = false;
    btn.textContent = I18N.charge;
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
    btnAnular.style.display =
      !isFromTPV && v.estado === "completada" ? "" : "none";
    btnAnular.onclick = () =>
      abrirModalAnulacionTicket(v.numero_ticket, v.fecha, v.id_cliente);
  }

  const emailInput = document.getElementById("tkEmailInput");
  if (emailInput) emailInput.value = "";
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
  const el_tkTipoDoc = document.getElementById("tkTipoDoc");
  if (el_tkTipoDoc)
    el_tkTipoDoc.textContent = isFactura ? I18N.invoice : I18N.ticket;

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
    el_tkNumero.textContent = window.formatTicketNumber ? window.formatTicketNumber(v.numero_ticket, v.fecha, esFactura) : v.numero_ticket;
  }
  const el_tkFecha = document.getElementById("tkFecha");
  if (el_tkFecha) el_tkFecha.textContent = fechaStr;
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
                  <button onclick="abrirModalDevolucion(${l.id}, ${v.numero_ticket}, '${v.fecha}', ${v.id_cliente || "null"}, ${l.meses_garantia || 24})" title="Devolver este producto" class="btn-icon text-red">
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
  const factorDesc =
    (vDescAmt > 0 || vDescPct > 0) && vSubtotal > 0 ? vTotal / vSubtotal : 1;

  // Agrupar base e IVA por tipo, usando total_linea y excluyendo devueltas
  const ivaGrupos = {};
  v.lineas.forEach((l) => {
    if (l.devuelta) return;
    const rate = parseFloat(l.iva_aplicado ?? 21);
    const pvpDesc = parseFloat(l.total_linea) * factorDesc;
    const base = pvpDesc / (1 + rate / 100);
    const tax = pvpDesc - base;
    if (!ivaGrupos[rate]) ivaGrupos[rate] = { base: 0, tax: 0 };
    ivaGrupos[rate].base += base;
    ivaGrupos[rate].tax += tax;
  });

  // TOTAL coherente: suma exacta del desglose (base+tax de cada grupo)
  // Esto evita que un v.total incorrecto en BD cause inconsistencias visuales
  const displayTotal = Object.values(ivaGrupos).reduce(
    (acc, g) => acc + g.base + g.tax,
    0,
  );

  // Actualizar TOTAL derivado del desglose
  if (el_tkTotal) el_tkTotal.textContent = fmt(displayTotal);

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

    if (tieneEfectivo && parseFloat(v.efectivo_recibido) > 0) {
      tkEfectivoRow.classList.remove("d-none");
      const el_tkEntregado = document.getElementById("tkEntregado");
      const el_tkCambio = document.getElementById("tkCambio");
      
      let efectivoCambio = 0;
      if (v.metodo_pago === "mixto") {
         efectivoCambio = Math.max(0, parseFloat(v.efectivo_recibido) - importeEfectivoMixto);
      } else {
         efectivoCambio = Math.max(0, parseFloat(v.efectivo_recibido) - displayTotal);
      }

      if (el_tkEntregado) el_tkEntregado.textContent = fmt(v.efectivo_recibido);
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
          // Factor de corrección: si total en BD difiere del displayTotal (ventas con datos corruptos)
          // escalamos los importes para que sean coherentes con el desglose
          const pagoFactor = vTotal > 0 ? displayTotal / vTotal : 1;
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
                <span class="font-mono text-success fw-bold">+${fmt(parseFloat(p.importe) * pagoFactor)}</span>
              </div>
            `;
            })
            .join("");
        }
      }

      if (tkAbonarParteContainer) {
        if (v.estado === "pendiente_pago") {
          tkAbonarParteContainer.classList.remove("d-none");
        } else {
          tkAbonarParteContainer.classList.add("d-none");
        }
      }
    } else {
      tkPagosSection.classList.add("d-none");
    }
  }

  // Duplicated block removed

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

  // Sincronizar estado en la UI de fondo (Historial)
  updateVentaStatusUI(v.numero_ticket, v.estado, v);
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
  document.getElementById("ticketModal").classList.remove("visible");
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
    const resp = await fetch("api/gestionDevolucion.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        accion: "devolverTicket",
        numTicket,
        motivo,
        metodoReembolso,
      }),
    });
    const r = await resp.json();
    if (r.ok) {
      document.getElementById("returnModal").classList.remove("visible");
      showToast(
        "<i class='fa-solid fa-check'></i> Ticket completado devuelto correctamente",
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
  const optExchange = document.getElementById("optExchange");

  // Limpiar clases previas
  [stCommercial, stWarranty].forEach((el) =>
    el.classList.remove("status-ok", "status-warn", "status-err"),
  );
  [optCash, optBalance, optExchange].forEach((el) => {
    el.classList.remove("disabled");
    el.querySelector("input").disabled = false;
  });

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
  }

  // Plazo garantía
  if (diffMonths < mesesGarantia) {
    stWarranty.classList.add("status-ok");
    txtWarranty.innerText = `Hasta ${mesesGarantia} meses (OK)`;
  } else {
    stWarranty.classList.add("status-err");
    txtWarranty.innerText = `Garantía agotada`;
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
  const optExchange = document.getElementById("optExchange");

  [stCommercial, stWarranty].forEach((el) =>
    el.classList.remove("status-ok", "status-warn", "status-err"),
  );
  [optCash, optBalance, optExchange].forEach((el) => {
    el.classList.remove("disabled");
    el.querySelector("input").disabled = false;
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
    [optCash, optBalance].forEach((el) => {
      el.classList.add("disabled");
      el.querySelector("input").disabled = true;
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
  const finalMotivo = nota ? `${motivo}: ${nota}` : motivo;

  try {
    const resp = await fetch("./api/gestionDevolucion.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        accion: "devolverLinea",
        idLinea,
        motivo: finalMotivo,
        metodoReembolso,
      }),
    });
    const data = await resp.json();
    if (!data.ok)
      throw new Error(data.error || "No se pudo realizar la devolución");

    document.getElementById("returnModal").classList.remove("visible");
    showToast(
      "<i class='fa-solid fa-check'></i> Devolución procesada correctamente",
    );
    const ventaActualizada = await cargarVenta(numTicket);
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
function handleSearch(val) {
  searchTerm = val;
  renderProducts();
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
let puntosCanjeados = 0;
let puntosDescuentoAmt = 0;
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
      renderProducts();
    });
  }

  renderProducts();
  updatePostponeUI();
}

// ── Producto Comodín ───────────────────────────────────────────────────────────
function abrirModalComodin() {
  document.getElementById("comodinDesc").value = "";
  document.getElementById("comodinPrice").value = "";
  document.getElementById("comodinIva").value = "21";
  document.getElementById("comodinError").classList.add("d-none");
  document.getElementById("modalComodin").classList.add("visible");
  document.getElementById("comodinDesc").focus();
}

function cerrarModalComodin() {
  document.getElementById("modalComodin").classList.remove("visible");
}

function agregarComodin() {
  const desc = document.getElementById("comodinDesc").value.trim();
  const price = parseFloat(document.getElementById("comodinPrice").value);
  const iva = parseFloat(document.getElementById("comodinIva").value);
  const errorEl = document.getElementById("comodinError");

  if (!desc) {
    errorEl.textContent = I18N.customDescError || "Introduce una descripción";
    errorEl.classList.remove("d-none");
    return;
  }
  if (isNaN(price) || price < 0) {
    errorEl.textContent = I18N.customPriceError || "Introduce un precio válido";
    errorEl.classList.remove("d-none");
    return;
  }

  const timestamp = new Date().getTime();
  const cartKey = `comodin_${timestamp}`;

  cart[cartKey] = {
    id: -1,
    name: desc,
    codigo: "COMODIN",
    price: price,
    iva: iva,
    qty: 1,
    icono: '<i class="fa-solid fa-box-open"></i>',
    maxStock: 999999,
    cartKey: cartKey,
  };

  cerrarModalComodin();
  renderCart();
  showToast('<i class="fa-solid fa-check"></i> Producto añadido');
}
