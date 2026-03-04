const PRODUCTS = typeof DB_PRODUCTS !== "undefined" ? DB_PRODUCTS : [];

// Constants for financing
const FINANCING_MIN_AMOUNT = 200;

// ── Estado global ──────────────────────────────────────────────────────────────
let cart = {};
let selectedPayment = "efectivo";
let discountPct = 0;
let ticketNum = 1001;
let activeCat = "all";
let searchTerm = "";
let minPrice = 0;
let maxPrice = Infinity;
let stockFilter = "all";
let sortOrder = "name-asc";
let isAdmin = typeof IS_ADMIN_BACKEND !== "undefined" ? IS_ADMIN_BACKEND : false;
let currentTicketNum = null;
let socioActual = null;
const SOCIO_DISCOUNT = 5;
let financingAccepted = false;

// ── Estado de venta pospuesta ──────────────────────────────────────────────────
let postponedSale = JSON.parse(sessionStorage.getItem("postponedSale")) || null;

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

if (typeof USER_THEME_MODE !== "undefined" || typeof USER_THEME_ACCENT !== "undefined") {
  applyTheme(USER_THEME_MODE || "light", USER_THEME_ACCENT || "blue");
}

// ── DOMContentLoaded ──────────────────────────────────────────────────────────
document.addEventListener("DOMContentLoaded", function () {
  // Asegurar que el panel de financiación esté oculto al cargar
  const finInfo = document.getElementById("finInfoPanel");
  if (finInfo) {
    finInfo.classList.add("d-none");
    finInfo.style.display = "none";
  }

  // Trigger initial total calculation
  const initialSubtotal = Object.values(cart).reduce((a, b) => a + b.price * b.qty, 0);
  if (initialSubtotal > 0) {
    updateTotals(initialSubtotal);
  }

  try {
    const fp = document.getElementById("financingPanel");
    if (fp && !fp.classList.contains("panel-resizable")) fp.classList.add("panel-resizable");
    const oi = document.getElementById("orderItems");
    if (oi && !oi.classList.contains("panel-resizable")) oi.classList.add("panel-resizable");
  } catch (e) {}

  try { initSidebarResizer(); } catch (e) { console.warn("Resizer init failed", e); }
});

// ── Formato monetario ──────────────────────────────────────────────────────────
function fmt(n) {
  return n.toFixed(2).replace(".", ",") + " €";
}

// ── Catálogo ───────────────────────────────────────────────────────────────────
function renderProducts() {
  const grid = document.getElementById("productsGrid");
  if (!grid) return;

  let filtered = PRODUCTS.filter((p) => {
    let matchesCat = false;
    if (activeCat === "all") {
      matchesCat = true;
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

    const matchesPrice = p.price >= minPrice && p.price <= maxPrice;
    if (!matchesPrice) return false;

    let matchesStock = true;
    if (stockFilter === "in-stock") matchesStock = p.stock > 0;
    else if (stockFilter === "low-stock") matchesStock = p.stock > 0 && p.stock <= 5;
    if (!matchesStock) return false;

    return true;
  });

  filtered.sort((a, b) => {
    switch (sortOrder) {
      case "name-asc": return a.name.localeCompare(b.name);
      case "name-desc": return b.name.localeCompare(a.name);
      case "price-asc": return a.price - b.price;
      case "price-desc": return b.price - a.price;
      case "stock-asc": return a.stock - b.stock;
      case "stock-desc": return b.stock - a.stock;
      default: return 0;
    }
  });

  grid.innerHTML = filtered
    .map((p) => `
      <div class="product-card${p.inactive ? " inactive" : ""}${p.stock <= 0 ? " out-of-stock" : ""}" id="card-${p.id}" onclick="handleCardClick(event, ${p.id}, this)">
          ${p.inactive ? '<div class="baja-pill">Baja</div>' : ""}
          ${p.stock <= 0 ? '<div class="stock-pill" style="background:var(--red); color:white; position:absolute; top:10px; right:10px; padding:2px 8px; border-radius:10px; font-size:10px; font-weight:700;">AGOTADO</div>' : ""}
          ${(() => {
            const v = p.variantes;
            if (!v) return "";
            if (Array.isArray(v) && v.length > 0) return '<div class="variant-badge">Opciones</div>';
            if (typeof v === "object" && Object.keys(v).length > 0) return '<div class="variant-badge">Opciones</div>';
            if (typeof v === "string" && v.length > 2) {
              try {
                const parsed = JSON.parse(v);
                if (
                  (Array.isArray(parsed) && parsed.length > 0) ||
                  (typeof parsed === "object" && parsed !== null && Object.keys(parsed).length > 0)
                ) {
                  return '<div class="variant-badge">Opciones</div>';
                }
              } catch (e) {}
            }
            return "";
          })()}
          <div class="product-icon">
              ${p.icono && p.icono.startsWith("data:image")
                ? `<img src="${p.icono}" class="prod-img-tpv" alt="${p.name}">`
                : `<span class="product-emoji">${p.icono}</span>`}
          </div>
          <div>
              <div class="product-name">${p.name}</div>
              <div class="product-sku">${p.codigo}</div>
              <div class="product-stock" style="font-size:11px; color:${p.stock <= 5 ? "var(--red)" : "var(--text-muted)"}; font-weight:600;">Stock: ${p.stock}</div>
          </div>
          <div class="product-price" style="margin-top:auto">${fmt(p.price)}</div>
          <div class="product-admin-bar">
              <button class="admin-action edit" onclick="editProduct(event,${p.id})"><i class="fa-solid fa-pen-to-square"></i> Editar</button>
              <button class="admin-action delete" onclick="deleteProduct(event,${p.id})"><i class="fa-solid fa-trash"></i> Borrar</button>
              <button class="admin-action baja" onclick="toggleBaja(event,${p.id})"><i class="fa-solid ${p.inactive ? "fa-arrow-up" : "fa-arrow-down"}"></i> ${p.inactive ? "Alta" : "Baja"}</button>
          </div>
      </div>
    `)
    .join("");

  grid.classList.toggle("is-admin", isAdmin);
}

function handleCardClick(e, id, el) {
  if (e.target.closest(".product-admin-bar")) return;
  const p = PRODUCTS.find((x) => x.id === id);
  if (p && p.inactive) return;
  if (p && p.stock <= 0) {
    showToast('<i class="fa-solid fa-circle-xmark"></i> Producto agotado');
    return;
  }

  let vars = p.variantes;
  if (typeof vars === "string") {
    try { vars = JSON.parse(vars); } catch (e) { vars = null; }
  }

  const hasVariants =
    vars &&
    ((Array.isArray(vars) && vars.length > 0) ||
      (typeof vars === "object" && Object.keys(vars).length > 0));

  if (hasVariants) {
    p.variantes_obj = vars;
    showVariantPicker(p, el);
  } else {
    addToCart(id, el);
  }
}

function showVariantPicker(p, el) {
  const modal = document.getElementById("variantModal");
  const container = document.getElementById("variantButtonsContainer");
  if (!modal || !container) return;

  container.innerHTML = "";

  let options = [];
  const vars = p.variantes_obj || p.variantes;

  if (Array.isArray(vars)) {
    options = vars.map((v) => {
      if (typeof v === "string") return { nombre: v, label: "" };
      return { nombre: v.valor || v.nombre || "Opción", label: v.label || "" };
    });
  } else if (vars && typeof vars === "object") {
    options = Object.entries(vars).map(([k, v]) => ({
      nombre: v && typeof v !== "object" ? String(v) : k,
      label: k,
    }));
  }

  options.forEach((opt) => {
    const btn = document.createElement("button");
    btn.className = "cat-tab p-12-16 br-12 border-2 bg-surface2 cursor-pointer fs-13 d-flex flex-column ai-center gap-8 active-scale h-auto hover-accent";
    const displayName = opt.nombre;
    const labelInfo = opt.label
      ? `<span class="fs-10 opacity-70 tt-uppercase font-bold">${opt.label}</span>`
      : "";
    btn.innerHTML = `
      <i class="fa-solid fa-tag fs-18 text-accent"></i>
      ${labelInfo}
      <span class="font-bold fs-14">${displayName}</span>
    `;
    btn.onclick = () => {
      addToCart(p.id, el, displayName);
      modal.classList.remove("visible");
    };
    container.appendChild(btn);
  });

  modal.classList.add("visible");
}

// ── Carrito ────────────────────────────────────────────────────────────────────
function addToCart(id, el, variantName = null) {
  const p = PRODUCTS.find((x) => x.id === id);
  if (!p) return;

  const cartKey = variantName ? `${id}-${variantName}` : id;

  if (cart[cartKey]) {
    if (cart[cartKey].qty >= p.stock) {
      showToast('<i class="fa-solid fa-circle-exclamation"></i> No hay más stock disponible');
      return;
    }
    cart[cartKey].qty++;
  } else {
    if (p.stock <= 0) return;

    const finalItem = { ...p };
    if (variantName) {
      finalItem.name = `${p.name} (${variantName})`;
      finalItem.variant = variantName;
      if (
        p.variantes &&
        typeof p.variantes === "object" &&
        !Array.isArray(p.variantes) &&
        p.variantes[variantName]
      ) {
        const vVal = p.variantes[variantName];
        if (typeof vVal === "number") finalItem.price = vVal;
      }
    }

    cart[cartKey] = { ...finalItem, qty: 1 };
    cart[cartKey].iva = parseFloat(p.iva || 21);
    cart[cartKey].serials = [];
    cart[cartKey].cartKey = cartKey;
  }

  el.classList.add("adding");
  setTimeout(() => el.classList.remove("adding"), 300);
  renderCart();
}

function changeQty(cartKey, delta) {
  if (!cart[cartKey]) return;
  const item = cart[cartKey];
  const p = PRODUCTS.find((x) => x.id === item.id);

  if (delta > 0 && item.qty >= p.stock) {
    showToast('<i class="fa-solid fa-circle-exclamation"></i> Límite de stock alcanzado');
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
    showToast('<i class="fa-solid fa-circle-exclamation"></i> El carrito está vacío');
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
  showToast('<i class="fa-solid fa-pause"></i> Venta pospuesta');
}

function resumeSale() {
  if (!postponedSale) return;

  if (Object.keys(cart).length > 0) {
    if (!confirm("Se perderá el carrito actual. ¿Deseas retomar la venta pospuesta?")) return;
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
  showToast('<i class="fa-solid fa-play"></i> Venta recuperada');
}

function updatePostponeUI() {
  const btnResume = document.getElementById("btnResumeSale");
  if (btnResume) {
    btnResume.style.display = postponedSale ? "flex" : "none";
  }
}

function renderCart() {
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
    .map((item) => `
      <div class="order-item">
        <span class="order-item-emoji">
          ${item.icono && item.icono.startsWith("data:image")
            ? `<img src="${item.icono}" class="order-item-img" alt="${item.name}">`
            : item.icono}
        </span>
        <div class="order-item-info">
          <div class="order-item-name">${item.name}</div>
          <div class="order-item-price">${fmt(item.price)} × ${item.qty}</div>
        </div>
        <div class="qty-ctrl">
          <button class="qty-btn" onclick="changeQty('${item.cartKey || item.id}', -1)"><i class="fa-solid fa-minus"></i></button>
          <span class="qty-val">${item.qty}</span>
          <button class="qty-btn" onclick="changeQty('${item.cartKey || item.id}', +1)"><i class="fa-solid fa-plus"></i></button>
        </div>
        <div class="order-item-total">${fmt(item.price * item.qty)}</div>
      </div>
    `)
    .join("");

  const subtotal = items.reduce((a, b) => a + b.price * b.qty, 0);
  updateTotals(subtotal);
}

function updateTotals(subtotal) {
  const items = Object.values(cart);
  const discountAmt = (subtotal * discountPct) / 100;
  const socioAmt = socioActual && socioActual.es_socio ? (subtotal * SOCIO_DISCOUNT) / 100 : 0;
  const totalDiscount = discountAmt + socioAmt;
  const discountFactor = subtotal > 0 ? (subtotal - totalDiscount) / subtotal : 1;

  let vat = 0;
  items.forEach((item) => {
    const itemSubtotal = item.price * item.qty;
    const itemBase = itemSubtotal * discountFactor;
    const itemVat = itemBase * (item.iva / 100);
    vat += itemVat;
  });

  const base = subtotal - totalDiscount;
  const total = base + vat;

  document.getElementById("subtotal").textContent = fmt(subtotal);
  document.getElementById("vatAmt").textContent = fmt(vat);
  document.getElementById("totalAmt").textContent = fmt(total);
  document.getElementById("chargeTotal").textContent = fmt(total);
  document.getElementById("discountAmt").textContent = "-" + fmt(totalDiscount);

  const el_discountRow = document.getElementById("discountRow");
  if (el_discountRow) {
    el_discountRow.style.display = totalDiscount > 0 ? "flex" : "none";
  }

  document.getElementById("chargeBtn").disabled = subtotal === 0;

  if (selectedPayment === "financiado") {
    financingAccepted = false;
    if (window._financingRecalcTimer) clearTimeout(window._financingRecalcTimer);
    window._financingRecalcTimer = setTimeout(() => {
      try { calcularCuotaFinanciacion(); } catch (e) { console.warn("Error recalculando financiación:", e); }
    }, 80);
  }

  // Gestión del botón de financiación
  const finanBtn = document.getElementById("btnFinanciacion");
  if (finanBtn) {
    const finanMinBadge = document.getElementById("finanMinBadge");
    const finanLockIcon = document.getElementById("finanLockIcon");
    const finanBtnLabel = document.getElementById("finanBtnLabel");

    if (total >= FINANCING_MIN_AMOUNT) {
      finanBtn.classList.remove("locked");
      finanBtn.disabled = false;
      finanBtn.style.opacity = "1";
      finanBtn.style.filter = "none";
      finanBtn.style.cursor = "pointer";
      if (finanLockIcon) finanLockIcon.className = "fa-solid fa-percent";
      if (finanMinBadge) { finanMinBadge.textContent = "DISPONIBLE"; finanMinBadge.classList.add("finan-badge-available"); }
      if (finanBtnLabel) finanBtnLabel.textContent = "Financiación";
    } else {
      // Si el método actual es financiado y el total baja del mínimo, volver a efectivo
      if (selectedPayment === "financiado") {
        const efectivoBtn = document.querySelector('.pay-btn[data-method="efectivo"]');
        if (efectivoBtn) selectPayment(efectivoBtn);
      }
      finanBtn.classList.add("locked");
      finanBtn.disabled = true;
      finanBtn.style.opacity = "0.5";
      finanBtn.style.filter = "grayscale(100%) brightness(0.6)";
      finanBtn.style.cursor = "not-allowed";
      if (finanLockIcon) finanLockIcon.className = "fa-solid fa-lock";
      if (finanMinBadge) { finanMinBadge.textContent = `Mín. ${fmt(FINANCING_MIN_AMOUNT)}`; finanMinBadge.classList.remove("finan-badge-available"); }
      if (finanBtnLabel) finanBtnLabel.textContent = "Financiación";
    }
  }

  if (selectedPayment === "financiado") {
    calcularCuotaFinanciacion();
  }
}

// ── Pago ───────────────────────────────────────────────────────────────────────
const PROMOS = typeof DB_PROMOS !== "undefined" ? DB_PROMOS : [];
let currentPromo = null;

function selectPayment(el) {
  if (el.disabled) {
    showToast('<i class="fa-solid fa-lock"></i> Financiación disponible a partir de ' + fmt(FINANCING_MIN_AMOUNT));
    return;
  }

  document.querySelectorAll(".pay-btn").forEach((b) => b.classList.remove("selected"));
  el.classList.add("selected");
  selectedPayment = el.dataset.method;

  // Siempre resetear estado de financiación al cambiar método
  financingAccepted = false;

  const finInfo = document.getElementById("finInfoPanel");

  if (selectedPayment === "financiado") {
    // Mostrar panel de financiación
    if (finInfo) {
      finInfo.classList.remove("d-none");
      finInfo.style.display = "flex";
    }
    cargarFinancieras();
    updateFinancingInfo();
  } else {
    // Ocultar panel de financiación para cualquier otro método
    if (finInfo) {
      finInfo.classList.add("d-none");
      finInfo.style.display = "none";
    }
  }
}

let LISTA_FINANCIERAS = [];

let financingParams = {
  entidad: "",
  meses: "12",
  pagaCliente: false,
};

function updateFinancingParams(mode = "inline") {
  const suffix = mode === "modal" ? "_modal" : "";
  const e = document.getElementById("finanEntidad" + suffix);
  const m = document.getElementById("finanMeses" + suffix);
  const p = document.getElementById("finanPagaCliente" + suffix);
  if (e) financingParams.entidad = e.value;
  if (m) financingParams.meses = m.value;
  if (p) financingParams.pagaCliente = p.checked;
}

async function cargarFinancieras() {
  const el = document.getElementById("finanEntidad");
  const elModal = document.getElementById("finanEntidad_modal");
  const mesesEl = document.getElementById("finanMeses");
  const mesesModal = document.getElementById("finanMeses_modal");
  const pagaEl = document.getElementById("finanPagaCliente");
  const pagaModal = document.getElementById("finanPagaCliente_modal");

  try {
    const resp = await fetch("api/listarFinancieras.php");
    const r = await resp.json();
    if (r.ok) {
      LISTA_FINANCIERAS = r.financieras;
      const opts = r.financieras
        .map((f) => `<option value="${f.id}" data-min="${f.min_importe}">${f.nombre}</option>`)
        .join("");
      if (el) {
        el.innerHTML = '<option value="">Seleccione...</option>' + opts;
        if (financingParams.entidad) el.value = financingParams.entidad;
      }
      if (elModal) {
        elModal.innerHTML = '<option value="">Seleccione...</option>' + opts;
        if (financingParams.entidad) elModal.value = financingParams.entidad;
      }
      if (mesesEl && financingParams.meses) mesesEl.value = financingParams.meses;
      if (mesesModal && financingParams.meses) mesesModal.value = financingParams.meses;
      if (pagaEl) pagaEl.checked = financingParams.pagaCliente;
      if (pagaModal) pagaModal.checked = financingParams.pagaCliente;
    }
  } catch (e) {
    console.warn("Error al cargar financieras:", e);
  }
}

async function updateFinancingInfo() {
  const entidadEl = document.getElementById("finanEntidad");
  const mesesEl = document.getElementById("finanMeses");
  const pagaEl = document.getElementById("finanPagaCliente");

  if (!entidadEl || !mesesEl || !pagaEl) return;

  const total = parseFloat(document.getElementById("totalAmt").textContent.replace(",", "."));
  if (isNaN(total)) return;

  const entidadId = entidadEl.value;
  if (!entidadId) return;

  const financier = LISTA_FINANCIERAS.find((f) => f.id == entidadId);
  if (!financier) return;

  const meses = parseInt(mesesEl.value);
  const pagaCliente = pagaEl.checked;

  const totalCost = Object.values(cart).reduce((acc, item) => {
    const pInfo = PRODUCTS.find((p) => p.id == item.id);
    const cost = pInfo ? pInfo.precio_coste || 0 : 0;
    return acc + cost * item.qty;
  }, 0);

  const subtotalSinIVA = total / 1.21;

  let comisionPct = 0;
  try {
    const comisionRes = await fetch(`api/obtenerComisionesPlazo.php?id=${financier.id}&meses=${meses}`);
    const comisionData = await comisionRes.json();
    if (comisionData.ok && comisionData.comision) {
      const valor = pagaCliente
        ? comisionData.comision.comision_con_interes
        : comisionData.comision.comision_sin_interes;
      comisionPct = parseFloat(valor) || 0;
    }
  } catch (e) {
    console.warn("Error al obtener comisión:", e);
  }

  comisionPct = parseFloat(comisionPct) || 0;
  const comisionAmt = total * (comisionPct / 100);
  const netProfit = subtotalSinIVA - totalCost - comisionAmt;

  const statusEl = document.getElementById("finInfoStatus");
  const amountEl = document.getElementById("finInfoAmount");
  const panelEl = document.getElementById("finInfoPanel");

  if (statusEl && amountEl && panelEl) {
    if (netProfit >= 0) {
      statusEl.textContent = "✓ GANANCIA";
      statusEl.style.color = "var(--green)";
      amountEl.textContent = fmt(netProfit);
      amountEl.style.color = "var(--green)";
      panelEl.style.borderColor = "var(--green)";
      panelEl.style.backgroundColor = "rgba(76, 175, 80, 0.05)";
    } else {
      statusEl.textContent = "✕ PÉRDIDA";
      statusEl.style.color = "var(--red)";
      amountEl.textContent = fmt(Math.abs(netProfit));
      amountEl.style.color = "var(--red)";
      panelEl.style.borderColor = "var(--red)";
      panelEl.style.backgroundColor = "rgba(244, 67, 54, 0.05)";
    }
  }
}

function calcularCuotaFinanciacion(mode = "inline") {
  if (selectedPayment !== "financiado") return;

  const suffix = mode === "modal" ? "_modal" : "";
  const entidadEl = document.getElementById("finanEntidad" + suffix);
  const mesesEl = document.getElementById("finanMeses" + suffix);
  const pagaEl = document.getElementById("finanPagaCliente" + suffix);

  if (!entidadEl || !mesesEl || !pagaEl) return;

  financingParams.entidad = entidadEl.value;
  financingParams.meses = mesesEl.value;
  financingParams.pagaCliente = pagaEl.checked;

  const total = parseFloat(document.getElementById("totalAmt").textContent.replace(",", "."));
  if (isNaN(total)) return;

  const entidadId = entidadEl.value;

  if (mode === "inline") {
    updateFinancingInfo();
    return;
  }

  if (mode === "modal") {
    const configEl = document.getElementById("finDetalleConfig");
    if (!entidadId) {
      if (configEl) configEl.classList.add("d-none");
      return;
    }
    if (configEl) configEl.classList.remove("d-none");
  }

  const financier = LISTA_FINANCIERAS.find((f) => f.id == entidadId);
  if (!financier) return;

  const meses = parseInt(mesesEl.value);
  const pagaCliente = pagaEl.checked;
  const cuota = total / meses;

  const elCuota = document.getElementById("finanCuota" + suffix);
  if (elCuota) elCuota.textContent = fmt(cuota);

  calcularRentabilidad(total, financier, meses, pagaCliente, mode);
}

async function calcularRentabilidad(total, financier, meses, pagaCliente, mode = "inline") {
  const suffix = mode === "modal" ? "_modal" : "";

  const totalCost = Object.values(cart).reduce((acc, item) => {
    const pInfo = PRODUCTS.find((p) => p.id == item.id);
    const cost = pInfo ? pInfo.precio_coste || 0 : 0;
    return acc + cost * item.qty;
  }, 0);

  const subtotalSinIVA = total / 1.21;

  let comisionPct = 0;
  try {
    const comisionRes = await fetch(`api/obtenerComisionesPlazo.php?id=${financier.id}&meses=${meses}`);
    const comisionData = await comisionRes.json();
    if (comisionData.ok && comisionData.comision) {
      const valor = pagaCliente
        ? comisionData.comision.comision_con_interes
        : comisionData.comision.comision_sin_interes;
      comisionPct = parseFloat(valor) || 0;
    }
  } catch (e) {
    console.warn("Error al obtener comisión:", e);
  }

  comisionPct = parseFloat(comisionPct) || 0;
  const comisionAmt = total * (comisionPct / 100);
  const netProfit = subtotalSinIVA - totalCost - comisionAmt;
  const interesesTotal = comisionAmt;

  if (mode === "modal") {
    const margenEl = document.getElementById("finMargenBruto");
    if (margenEl) margenEl.textContent = fmt(subtotalSinIVA - totalCost);

    const comisionPctEl = document.getElementById("finComisionPct");
    if (comisionPctEl) comisionPctEl.textContent = `(${comisionPct.toFixed(2)}%)`;

    const comisionEl = document.getElementById("finComisionBanco");
    if (comisionEl) comisionEl.textContent = `-${fmt(comisionAmt)}`;

    const totalAmtEl = document.getElementById("finTotalAmt");
    if (totalAmtEl) totalAmtEl.textContent = fmt(total);

    const interesesEl = document.getElementById("finInteresesAmt");
    if (interesesEl) interesesEl.textContent = fmt(interesesTotal);

    const resultEl = document.getElementById("profitStatus_modal");
    if (resultEl) {
      resultEl.textContent = fmt(netProfit);
      const color = netProfit >= subtotalSinIVA * 0.12
        ? "var(--green)"
        : netProfit > 0
        ? "var(--orange)"
        : "var(--red)";
      resultEl.style.color = color;
    }

    const btnConfirm = document.getElementById("btnConfirmarFinanciacion");
    if (btnConfirm) btnConfirm.disabled = false;
  }
}

function confirmFinancingAccept() {
  financingAccepted = true;
  const confirmarBtn = document.getElementById("confirmarClienteBtn");
  if (confirmarBtn) {
    confirmarBtn.disabled = false;
    confirmarBtn.style.opacity = "1";
  }
  showToast('<i class="fa-solid fa-check-circle"></i> Financiación aceptada - Procede al pago');

  ["", "_modal"].forEach((s) => {
    const dc = document.getElementById("financingDecisionContainer" + s);
    if (dc) dc.style.display = "none";
  });
}

function rejectFinancing() {
  financingAccepted = false;

  const efectivoBtn = document.querySelector('.pay-btn[data-method="efectivo"]');
  if (efectivoBtn) selectPayment(efectivoBtn);

  try { document.getElementById("finanPagaCliente").checked = false; } catch (e) {}
  try { document.getElementById("finanMeses").value = "12"; } catch (e) {}
  try { document.getElementById("finanPagaCliente_modal").checked = false; } catch (e) {}
  try { document.getElementById("finanMeses_modal").value = "12"; } catch (e) {}

  showToast('<i class="fa-solid fa-arrow-right"></i> Financiación rechazada - Método cambiado a efectivo');
}

function openFinancingModal() {
  const modal = document.getElementById("financiacionModal");
  if (!modal) return;

  const entidadModal = document.getElementById("finanEntidad_modal");
  const mesesModal = document.getElementById("finanMeses_modal");
  const pagaClienteModal = document.getElementById("finanPagaCliente_modal");

  if (entidadModal && financingParams.entidad) entidadModal.value = financingParams.entidad;
  if (mesesModal && financingParams.meses) mesesModal.value = financingParams.meses;
  if (pagaClienteModal) pagaClienteModal.checked = financingParams.pagaCliente;

  if (entidadModal) entidadModal.onchange = () => { updateFinancingParams("modal"); calcularCuotaFinanciacion("modal"); };
  if (mesesModal) mesesModal.onchange = () => { updateFinancingParams("modal"); calcularCuotaFinanciacion("modal"); };
  if (pagaClienteModal) pagaClienteModal.onchange = () => { updateFinancingParams("modal"); calcularCuotaFinanciacion("modal"); };

  modal.classList.add("visible");
  cargarFinancieras();
  setTimeout(() => calcularCuotaFinanciacion("modal"), 80);
}

function closeFinancingModal() {
  const modal = document.getElementById("financiacionModal");
  if (!modal) return;
  modal.classList.remove("visible");

  const entInline = document.getElementById("finanEntidad");
  const mesesInline = document.getElementById("finanMeses");
  const pagaInline = document.getElementById("finanPagaCliente");
  if (entInline) entInline.value = financingParams.entidad;
  if (mesesInline) mesesInline.value = financingParams.meses;
  if (pagaInline) pagaInline.checked = financingParams.pagaCliente;

  if (selectedPayment === "financiado") updateFinancingInfo();
}

function confirmarFinanciacionModal() {
  const entidadModal = document.getElementById("finanEntidad_modal");
  const mesesModal = document.getElementById("finanMeses_modal");
  const pagaClienteModal = document.getElementById("finanPagaCliente_modal");

  if (!entidadModal || !entidadModal.value) {
    showToast("⚠️ Selecciona una entidad financiera");
    return;
  }

  const entidadHidden = document.getElementById("finanEntidad");
  const mesesHidden = document.getElementById("finanMeses");
  const pagaClienteHidden = document.getElementById("finanPagaCliente");

  if (entidadHidden) entidadHidden.value = entidadModal.value;
  if (mesesHidden) mesesHidden.value = mesesModal ? mesesModal.value : "12";
  if (pagaClienteHidden) pagaClienteHidden.value = pagaClienteModal && pagaClienteModal.checked ? "1" : "0";

  closeFinancingModal();
  showToast("✓ Financiación configurada");
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

  resizer.addEventListener("touchstart", (ev) => {
    dragging = true;
    startX = ev.touches[0].clientX;
    startWidth = parseInt(getComputedStyle(sidebar).width, 10);
    document.body.classList.add("resizing-sidebar");
    window.addEventListener("touchmove", onMove);
    window.addEventListener("touchend", stop);
  }, { passive: true });
}

// ── Descuentos ─────────────────────────────────────────────────────────────────
function applyDiscount() {
  const code = document.getElementById("discountCode").value.trim().toUpperCase();
  const el_errDiscount = document.getElementById("err-discount");
  if (el_errDiscount) el_errDiscount.innerText = "";

  const subtotal = Object.values(cart).reduce((a, b) => a + b.price * b.qty, 0);
  const promo = PROMOS.find((p) => p.code === code);

  if (promo) {
    if (subtotal < promo.minSubtotal) {
      if (el_errDiscount) {
        el_errDiscount.innerText = `Importe mínimo ${fmt(promo.minSubtotal)} para usar este cupón`;
      } else {
        showToast(`<i class="fa-solid fa-circle-exclamation"></i> Importe mínimo ${fmt(promo.minSubtotal)} para usar este cupón`);
      }
      return;
    }

    if (promo.type === "percent") {
      discountPct = promo.value;
    } else {
      discountPct = subtotal > 0 ? (promo.value / subtotal) * 100 : 0;
    }

    currentPromo = promo;
    document.getElementById("discountRow").style.display = "flex";
    updateTotals(subtotal);

    const descText = promo.type === "percent"
      ? `${promo.value}% aplicado (${promo.code})`
      : `-${promo.value.toFixed(2)} € aplicado (${promo.code})`;
    showToast(`<i class="fa-solid fa-circle-check"></i> ${descText}`);
  } else {
    if (el_errDiscount) {
      el_errDiscount.innerText = "Código no válido o inactivo";
    } else {
      showToast('<i class="fa-solid fa-circle-xmark"></i> Código no válido o inactivo');
    }
  }
}

// ── Modal tipo de cliente ──────────────────────────────────────────────────────
let tipoClienteActual = "particular";
let clienteSeleccionado = null;
let ULTIMOS_CLIENTES_BUSCADOS = [];

function processPayment() {
  tipoClienteActual = "particular";
  document.getElementById("empresaDatos").style.display = "none";
  document.getElementById("empresaNombre").value = "";
  document.getElementById("empresaNif").value = "";
  document.getElementById("efectivoRecibido").value = "";

  const cambioEl = document.getElementById("efectivoCambio");
  cambioEl.textContent = "0,00 €";
  cambioEl.classList.remove("text-red");
  cambioEl.classList.add("text-accent");

  const efectivoGestion = document.getElementById("efectivoGestion");
  if (selectedPayment === "efectivo") {
    efectivoGestion.style.display = "flex";
    setTimeout(() => document.getElementById("efectivoRecibido").focus(), 100);
  } else {
    efectivoGestion.style.display = "none";
  }

  const btnP = document.getElementById("btnParticular");
  const btnE = document.getElementById("btnEmpresa");
  const btnS = document.getElementById("btnSocio");
  btnP.classList.add("selected-type");
  btnE.classList.remove("selected-type");
  if (btnS) btnS.classList.remove("selected-type");

  socioActual = null;
  document.getElementById("socioBusqueda").style.display = "none";
  document.getElementById("socioRegistro").style.display = "none";
  document.getElementById("socioInfo").innerText = "";
  clienteSeleccionado = null;

  const gen = document.getElementById("clienteBusquedaGenerica");
  if (gen) {
    gen.style.display = "none";
    const res = document.getElementById("clienteResultados");
    if (res) res.innerHTML = "";
  }

  document.querySelectorAll(".form-error").forEach((el) => (el.innerText = ""));
  document.getElementById("clienteModal").classList.add("visible");
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

  document.getElementById("empresaDatos").style.display = "none";
  document.getElementById("socioBusqueda").style.display = "none";
  document.getElementById("socioRegistro").style.display = "none";

  const gen = document.getElementById("clienteBusquedaGenerica");
  if (gen) gen.style.display = "none";
  clienteSeleccionado = null;

  if (tipo === "particular") {
    btnP.classList.add("selected-type");
    socioActual = null;
    if (gen) gen.style.display = "flex";
  } else if (tipo === "socio") {
    if (btnS) btnS.classList.add("selected-type");
    document.getElementById("socioBusqueda").style.display = "flex";
    setTimeout(() => document.getElementById("socioSearch").focus(), 100);
  } else {
    btnE.classList.add("selected-type");
    document.getElementById("empresaDatos").style.display = "flex";
    setTimeout(() => document.getElementById("empresaNombre").focus(), 100);
    socioActual = null;
    if (gen) gen.style.display = "flex";
  }

  const subtotal = Object.values(cart).reduce((a, b) => a + b.price * b.qty, 0);
  updateTotals(subtotal);
}

async function buscarSocio() {
  const term = document.getElementById("socioSearch").value.trim();
  if (!term) return;

  const info = document.getElementById("socioInfo");
  const btnAdd = document.getElementById("btnAddSocio");
  info.innerText = "Buscando...";
  btnAdd.classList.add("d-none");

  try {
    const resp = await fetch("api/gestionCliente.php", {
      method: "POST",
      body: JSON.stringify({ accion: "buscar", nif: term }),
    });
    const r = await resp.json();
    if (r.ok) {
      socioActual = r.cliente;
      info.innerHTML = `<i class="fa-solid fa-check-circle"></i> ${r.cliente.nombre} (${r.cliente.nif})`;
      showToast("Socio identificado");
      const subtotal = Object.values(cart).reduce((a, b) => a + b.price * b.qty, 0);
      updateTotals(subtotal);
    } else {
      info.innerText = "Socio no encontrado.";
      btnAdd.classList.remove("d-none");
      socioActual = null;
    }
  } catch (e) {
    console.error(e);
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
    if (resEl) resEl.innerText = "Selecciona Particular o Empresa para esta búsqueda.";
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
      return;
    }
    if (!resEl) return;
    resEl.innerHTML = lista
      .map((c, idx) => {
        const nombreCompleto = (c.nombre || "") + (c.apellidos ? " " + c.apellidos : "");
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
    const nombreCompleto = (c.nombre || "") + (c.apellidos ? " " + c.apellidos : "");
    const nomEl = document.getElementById("empresaNombre");
    const nifEl = document.getElementById("empresaNif");
    if (nomEl) nomEl.value = nombreCompleto;
    if (nifEl) nifEl.value = c.nif || "";
  }

  const resEl = document.getElementById("clienteResultados");
  if (resEl) {
    const nombreCompleto = (c.nombre || "") + (c.apellidos ? " " + c.apellidos : "");
    const nifTxt = c.nif ? ` (${c.nif})` : "";
    resEl.innerHTML = `<i class="fa-solid fa-check-circle"></i> ${nombreCompleto}${nifTxt}`;
  }

  showToast("Cliente seleccionado");
}

function mostrarRegistroSocio() {
  document.getElementById("socioBusqueda").style.display = "none";
  document.getElementById("socioRegistro").style.display = "flex";
  document.getElementById("newSocioNif").value = document.getElementById("socioSearch").value;
}

function cancelarRegistroSocio() {
  document.getElementById("socioRegistro").style.display = "none";
  document.getElementById("socioBusqueda").style.display = "flex";
}

async function guardarNuevoSocio() {
  const nombre = document.getElementById("newSocioNombre").value.trim();
  const nif = document.getElementById("newSocioNif").value.trim();
  if (!nombre || !nif) return;

  try {
    const resp = await fetch("api/gestionCliente.php", {
      method: "POST",
      body: JSON.stringify({ accion: "registrar", nombre, nif }),
    });
    const r = await resp.json();
    if (r.ok) {
      socioActual = { id: r.id, nombre, nif, es_socio: true };
      document.getElementById("socioRegistro").style.display = "none";
      document.getElementById("socioBusqueda").style.display = "flex";
      document.getElementById("socioInfo").innerHTML = `<i class="fa-solid fa-check-circle"></i> ${nombre} (${nif})`;
      const subtotal = Object.values(cart).reduce((a, b) => a + b.price * b.qty, 0);
      updateTotals(subtotal);
      showToast("Socio registrado y aplicado");
    }
  } catch (e) {
    console.error(e);
  }
}

function calcularCambio() {
  const recibido = parseFloat(document.getElementById("efectivoRecibido").value) || 0;
  const items = Object.values(cart);
  const subtotal = items.reduce((a, b) => a + b.price * b.qty, 0);
  const discountAmt = (subtotal * discountPct) / 100;
  const base = subtotal - discountAmt;
  const total = base * 1.21;

  const cambio = recibido - total;
  const cambioEl = document.getElementById("efectivoCambio");
  const confirmarBtn = document.getElementById("confirmarClienteBtn");

  if (recibido > 0) {
    cambioEl.textContent = fmt(Math.max(0, cambio));
    if (cambio < -0.01) {
      cambioEl.classList.remove("text-accent");
      cambioEl.classList.add("text-red");
      confirmarBtn.disabled = true;
    } else {
      cambioEl.classList.remove("text-red");
      cambioEl.classList.add("text-accent");
      confirmarBtn.disabled = false;
    }
  } else {
    cambioEl.textContent = "0,00 €";
    confirmarBtn.disabled = true;
  }
}

async function confirmarCliente() {
  const items = Object.values(cart);
  const itemsWithSerial = items.filter((it) => it.requiere_serial && it.qty > 0);

  if (itemsWithSerial.length > 0) {
    cerrarModalCliente();
    pedirNumerosSerie(itemsWithSerial);
    return;
  }

  ejecutarCobroFinal();
}

function pedirNumerosSerie(items) {
  const modal = document.getElementById("serialModal");
  const container = document.getElementById("serialInputsContainer");
  container.innerHTML = "";

  items.forEach((item) => {
    for (let i = 0; i < item.qty; i++) {
      const div = document.createElement("div");
      div.className = "form-group";
      div.innerHTML = `
        <label class="fs-11 font-bold tt-uppercase text-muted">${item.name} (${i + 1}/${item.qty})</label>
        <input type="text" class="form-input font-mono serial-input"
               data-id="${item.id}" data-idx="${i}"
               placeholder="Introduce el nº de serie..." required />
      `;
      container.appendChild(div);
    }
  });

  modal.classList.add("visible");

  document.getElementById("confirmSerialBtn").onclick = () => {
    const inputs = container.querySelectorAll(".serial-input");
    let allOk = true;

    inputs.forEach((input) => {
      const val = input.value.trim();
      if (!val) {
        input.style.borderColor = "var(--red)";
        allOk = false;
      } else {
        const id = parseInt(input.dataset.id);
        const item = cart[id];
        item.serials = item.serials || [];
        item.serials[parseInt(input.dataset.idx)] = val;
      }
    });

    if (allOk) {
      modal.classList.remove("visible");
      abrirModalPago();
    } else {
      showToast("⚠️ Debes introducir todos los números de serie");
    }
  };
}

function abrirModalPago() {
  document.getElementById("clienteModal").classList.add("visible");
}

async function ejecutarCobroFinal() {
  const btn = document.getElementById("confirmarClienteBtn");
  btn.disabled = true;
  btn.textContent = "Guardando…";

  const items = Object.values(cart);
  const subtotal = items.reduce((a, b) => a + b.price * b.qty, 0);
  const discountAmt = (subtotal * discountPct) / 100;
  const socioAmt = socioActual && socioActual.es_socio ? (subtotal * SOCIO_DISCOUNT) / 100 : 0;

  const clienteId =
    tipoClienteActual === "socio"
      ? socioActual ? socioActual.id : null
      : clienteSeleccionado ? clienteSeleccionado.id : null;

  if (selectedPayment === "financiado" && !financingAccepted) {
    showToast("⚠ Debes aceptar o rechazar la financiación antes de proceder");
    btn.disabled = false;
    btn.textContent = "Cobrar";
    return;
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
    metodoPago: selectedPayment,
    subtotal,
    total:
      (subtotal -
        (subtotal *
          (discountPct + (socioActual && socioActual.es_socio ? SOCIO_DISCOUNT : 0))) /
          100) *
      1.21,
    descuentoPct: discountPct + (socioActual && socioActual.es_socio ? SOCIO_DISCOUNT : 0),
    lineas: items.map((it) => ({
      id: it.id,
      name: it.name,
      codigo: it.codigo,
      price: it.price,
      qty: it.qty,
      serials: it.serials || [],
    })),
    efectivo: {
      recibido: parseFloat(document.getElementById("efectivoRecibido").value) || 0,
    },
  };

  if (selectedPayment === "financiado") {
    const totalVenta = payload.total;
    const entidadEl = document.getElementById("finanEntidad");
    const mesesEl = document.getElementById("finanMeses");
    const pagaClienteEl = document.getElementById("finanPagaCliente");

    if (!entidadEl || !entidadEl.value) {
      showToast("⚠️ Selecciona una entidad financiera");
      btn.disabled = false;
      btn.textContent = "Cobrar";
      return;
    }

    const meses = parseInt(mesesEl?.value || 12);
    const pagaCliente = pagaClienteEl?.checked ? true : false;

    payload.financiacion = {
      idFinanciera: entidadEl.value,
      meses,
      cuotaMensual: totalVenta / meses,
      importeIntereses: 0,
      modalidad: pagaCliente ? "cliente_paga_intereses" : "vendedor_paga_intereses",
      notas: "Financiado desde TPV (Smart System)",
    };
  }

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
      showToast("<i class='fa-solid fa-circle-check'></i> Venta guardada correctamente");
    } else {
      throw new Error("La API no devolvió los datos de la venta.");
    }
  } catch (err) {
    console.error("Error en confirmarCliente:", err);
    showToast("<i class='fa-solid fa-circle-xmark'></i> " + err.message);
  } finally {
    btn.disabled = false;
    btn.textContent = "Cobrar";
  }
}

async function cargarVenta(ticketNum) {
  const resp = await fetch("./api/obtenerVenta.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ numTicket: ticketNum }),
  });
  const data = await resp.json();
  if (!resp.ok || !data.ok) throw new Error(data.error || "Error al cargar venta");
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

  const emailSection = document.getElementById("ticketEmailSection");
  const btnPrint = document.querySelector('button[onclick="imprimirTicket()"]');
  const btnNuevaVenta = document.getElementById("btnNuevaVenta");

  if (emailSection) emailSection.style.display = isFromTPV ? "" : "none";
  if (btnPrint) btnPrint.style.display = isFromTPV ? "" : "none";
  if (btnNuevaVenta) btnNuevaVenta.style.display = isFromTPV ? "" : "none";

  const btnAnular = document.getElementById("btnAnularTicket");
  if (btnAnular) {
    btnAnular.style.display = !isFromTPV && v.estado === "completada" ? "" : "none";
    btnAnular.onclick = () => abrirModalAnulacionTicket(v.numero_ticket);
  }

  const emailInput = document.getElementById("tkEmailInput");
  if (emailInput) emailInput.value = "";
  const errEmail = document.getElementById("err-email");
  if (errEmail) errEmail.innerText = "";

  const fmt2 = (n) => parseFloat(n).toFixed(2).replace(".", ",") + " €";

  let date = new Date();
  if (v.fecha) {
    const fStr = v.fecha.includes("T") ? v.fecha : v.fecha.replace(" ", "T");
    date = new Date(fStr);
  }

  const fechaStr =
    date.toLocaleDateString("es-ES") +
    " " +
    date.toLocaleTimeString("es-ES", { hour: "2-digit", minute: "2-digit" });

  const isFactura = v.tipo_cliente === "empresa";
  const el_tkTipoDoc = document.getElementById("tkTipoDoc");
  if (el_tkTipoDoc) el_tkTipoDoc.textContent = isFactura ? "FACTURA" : "TICKET DE VENTA";

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
    if (clienteMeta) { clienteMeta.classList.remove("d-none"); clienteMeta.textContent = v.nombre_cliente || "—"; }
    if (labelNifMeta) labelNifMeta.classList.remove("d-none");
    if (nifMeta) { nifMeta.classList.remove("d-none"); nifMeta.textContent = v.nif_cliente || "—"; }
  } else {
    if (clienteSection) clienteSection.classList.add("d-none");
    if (labelClienteMeta) labelClienteMeta.classList.add("d-none");
    if (clienteMeta) clienteMeta.classList.add("d-none");
    if (labelNifMeta) labelNifMeta.classList.add("d-none");
    if (nifMeta) nifMeta.classList.add("d-none");
  }

  const el_tkNumero = document.getElementById("tkNumero");
  if (el_tkNumero) el_tkNumero.textContent = "#" + String(v.numero_ticket).padStart(4, "0");
  const el_tkFecha = document.getElementById("tkFecha");
  if (el_tkFecha) el_tkFecha.textContent = fechaStr;
  const el_tkMetodo = document.getElementById("tkMetodo");
  if (el_tkMetodo) el_tkMetodo.textContent = v.metodo_pago.charAt(0).toUpperCase() + v.metodo_pago.slice(1);

  const tkCajero = document.getElementById("tkCajero");
  if (tkCajero) {
    if (v.nombre_cajero) tkCajero.textContent = v.nombre_cajero;
    else if (typeof CAJERO_NOMBRE !== "undefined" && CAJERO_NOMBRE) tkCajero.textContent = CAJERO_NOMBRE;
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

        let serialDisplay = "";
        if (l.numeros_serie) {
          serialDisplay = String(l.numeros_serie).split(",").map((s) => s.trim()).filter(Boolean).join(", ");
        } else if (l.numero_serie) {
          try {
            const parsed = JSON.parse(l.numero_serie);
            if (Array.isArray(parsed)) serialDisplay = parsed.filter(Boolean).join(", ");
          } catch (e) {
            serialDisplay = l.numero_serie;
          }
        }

        return `
          <div style="display:flex; justify-content:space-between; padding: 10px 0; border-bottom: 1px solid var(--surface2); ${l.devuelta ? "opacity:0.6; background:rgba(192,57,43,0.05);" : ""}">
            <div style="flex:1">
              <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                <div>
                  <span style="font-weight:600; ${l.devuelta ? "text-decoration:line-through;" : ""}">${l.nombre_producto}</span>
                  <span style="color:var(--text-muted); font-size:11px; margin-left:6px;">${l.codigo_producto}</span>
                </div>
                <span style="font-family:'DM Mono',monospace; font-weight:600;">${fmt2(l.total_linea)}</span>
              </div>
              <div style="display:flex; align-items:center; gap:8px; margin-top:4px;">
                <span style="color:var(--text-muted); font-size:11px;">${l.cantidad} × ${fmt2(l.precio_unitario)}</span>
                <span class="fs-10 px-6 py-2 br-4" style="background:${isExpired ? "var(--red-light)" : "var(--green-light)"}; color:${isExpired ? "var(--red)" : "var(--green)"}; font-weight:700;">
                  <i class="fa-solid fa-shield-halved"></i>
                  ${isExpired ? "Garantía AGOTADA" : "Garantía hasta " + gStr}
                </span>
                ${l.devuelta ? `<span class="fs-10 px-6 py-2 br-4" style="background:var(--red); color:white; font-weight:700;">DEVUELTO</span>` : ""}
              </div>
              ${serialDisplay ? `<div style="margin-top:4px; font-size:11px; color:var(--text-muted);"><i class="fa-solid fa-barcode"></i> N.º serie: ${serialDisplay}</div>` : ""}
              ${l.devuelta && l.motivo_devolucion ? `<div style="margin-top:4px; font-size:11px; color:var(--red); font-style:italic;"><i class="fa-solid fa-circle-info"></i> Motivo: ${l.motivo_devolucion}</div>` : ""}
            </div>
            ${!isFromTPV && !l.devuelta && v.estado === "completada"
              ? `<div style="padding-left:12px; display:flex; align-items:center;">
                  <button onclick="abrirModalDevolucion(${l.id}, ${v.numero_ticket})" title="Devolver este producto" class="btn-icon text-red">
                    <i class="fa-solid fa-arrow-rotate-left"></i>
                  </button>
                </div>`
              : ""}
          </div>
        `;
      })
      .join("");

  const el_tkSubtotal = document.getElementById("tkSubtotal");
  if (el_tkSubtotal) el_tkSubtotal.textContent = fmt2(v.subtotal);
  const el_tkBase = document.getElementById("tkBase");
  if (el_tkBase) el_tkBase.textContent = fmt2(v.base_imponible);
  const el_tkIva = document.getElementById("tkIva");
  if (el_tkIva) el_tkIva.textContent = fmt2(v.iva_amt);
  const el_tkTotal = document.getElementById("tkTotal");
  if (el_tkTotal) el_tkTotal.textContent = fmt2(v.total);

  const tkEfectivoRow = document.getElementById("tkEfectivoRow");
  if (tkEfectivoRow) {
    if (v.metodo_pago === "efectivo" && parseFloat(v.efectivo_recibido) > 0) {
      tkEfectivoRow.classList.remove("d-none");
      const el_tkEntregado = document.getElementById("tkEntregado");
      const el_tkCambio = document.getElementById("tkCambio");
      if (el_tkEntregado) el_tkEntregado.textContent = fmt2(v.efectivo_recibido);
      if (el_tkCambio) el_tkCambio.textContent = fmt2(v.efectivo_recibido - v.total);
    } else {
      tkEfectivoRow.classList.add("d-none");
    }
  }

  const tkFinancingRow = document.getElementById("tkFinancingRow");
  if (tkFinancingRow) {
    if (v.metodo_pago === "financiado" && v.nombre_financiera) {
      tkFinancingRow.classList.remove("d-none");
      const el_tkFinanEntidad = document.getElementById("tkFinanEntidad");
      const el_tkFinanPlazo = document.getElementById("tkFinanPlazo");
      const el_tkFinanCuota = document.getElementById("tkFinanCuota");
      if (el_tkFinanEntidad) el_tkFinanEntidad.textContent = v.nombre_financiera;
      if (el_tkFinanPlazo) el_tkFinanPlazo.textContent = v.meses + " meses";
      if (el_tkFinanCuota) el_tkFinanCuota.textContent = fmt2(v.cuota_mensual);
    } else {
      tkFinancingRow.classList.add("d-none");
    }
  }

  const descRow = document.getElementById("tkDescRow");
  if (descRow) {
    if (parseFloat(v.descuento_pct) > 0) {
      descRow.classList.remove("d-none");
      const el_tkDescLabel = document.getElementById("tkDescLabel");
      const el_tkDescAmt = document.getElementById("tkDescAmt");
      if (el_tkDescLabel) el_tkDescLabel.textContent = `Descuento (${v.descuento_pct}%)`;
      if (el_tkDescAmt) el_tkDescAmt.textContent = "−" + fmt2(v.descuento_amt);
    } else {
      descRow.classList.add("d-none");
    }
  }

  document.getElementById("ticketModal").classList.add("visible");
}

async function imprimirTicket() {
  if (!currentTicketNum) {
    showToast("<i class='fa-solid fa-circle-xmark'></i> No hay ticket cargado para imprimir");
    return;
  }
  window.open(`./api/imprimirTicket.php?id=${currentTicketNum}`, "_blank");
}

function descargarPDFTicket() {
  if (!currentTicketNum) {
    showToast("<i class='fa-solid fa-circle-xmark'></i> No hay ticket cargado");
    return;
  }
  window.location.href = `./api/generarPDFTicket.php?id=${currentTicketNum}`;
  showToast("<i class='fa-solid fa-file-pdf'></i> Descargando PDF...");
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
      body: JSON.stringify({ numTicket: currentTicketNum, destinatario: email, tipo: "ticket" }),
    });
    const data = await resp.json();

    if (data.ok) {
      showToast("<i class='fa-solid fa-circle-check'></i> Ticket enviado con éxito a: " + email);
      const emailInput = document.getElementById("tkEmailInput");
      if (emailInput) emailInput.value = "";
    } else {
      throw new Error(data.error || "Error al enviar el email");
    }
  } catch (err) {
    console.error(err);
    showToast("<i class='fa-solid fa-circle-xmark'></i> " + (err.message || "Error al enviar"));
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

async function devolverTicket(numTicket) {
  abrirModalAnulacionTicket(numTicket);
}

async function confirmarAnulacionTicket(numTicket) {
  const motivoBase = document.getElementById("returnReason").value;
  const nota = document.getElementById("returnNote").value.trim();
  const motivo = nota ? `${motivoBase}: ${nota}` : motivoBase;

  try {
    const resp = await fetch("api/gestionDevolucion.php", {
      method: "POST",
      body: JSON.stringify({ accion: "devolverTicket", numTicket, motivo }),
    });
    const r = await resp.json();
    if (r.ok) {
      document.getElementById("returnModal").classList.remove("visible");
      showToast("Ticket devuelto y stock restaurado");
      const v = await cargarVenta(numTicket);
      mostrarTicket(v, false);
    } else {
      throw new Error(r.error || "No se pudo anular el ticket");
    }
  } catch (e) {
    showToast("Error: " + e.message);
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
    showToast("<i class='fa-solid fa-triangle-exclamation'></i> Acceso restringido a administradores");
    return false;
  }
  return true;
}

function addVariantToUI(mode, label = null, value = null) {
  const l = label || document.getElementById(mode + "NuevaVarianteLabel").value.trim();
  const v = value || document.getElementById(mode + "NuevaVarianteValue").value.trim();
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
  pills.forEach((p) => { variants.push({ label: p.dataset.label, valor: p.dataset.value }); });
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
  document.getElementById("addImgPreview").innerHTML = '<i class="fa-solid fa-image"></i>';
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
        requiere_serial: document.getElementById("addSerial").checked ? 1 : 0,
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
          const errEl = document.getElementById("err-add" + key.charAt(0).toUpperCase() + key.slice(1));
          if (errEl && msg) errEl.innerText = msg;
        }
        return;
      }
      throw new Error(data.error);
    }

    PRODUCTS.push(data.producto);
    document.getElementById("addModal").classList.remove("visible");
    renderProducts();
    showToast("<i class='fa-solid fa-circle-check'></i> Producto añadido correctamente");
  } catch (err) {
    showToast("<i class='fa-solid fa-circle-xmark'></i> " + err.message);
  }
}

function editProduct(e, id) {
  e.stopPropagation();
  if (!requireAdmin()) return;
  const p = PRODUCTS.find((x) => x.id === id);
  document.getElementById("editId").value = p.id;
  document.getElementById("editName").value = p.name;
  document.getElementById("editSku").value = p.codigo;
  document.getElementById("editPrice").value = p.price;
  document.getElementById("editIva").value = p.iva || 21;
  document.getElementById("editMesesGarantia").value = p.meses_garantia || 24;
  document.getElementById("editSerial").checked = !!p.requiere_serial;
  document.getElementById("editEmoji").value = p.icono;

  const preview = document.getElementById("editImgPreview");
  if (p.icono && p.icono.startsWith("data:image")) {
    preview.innerHTML = `<img src="${p.icono}" style="width:100%; height:100%; object-fit:contain; border-radius:8px;">`;
  } else {
    preview.innerHTML = `<span style="font-size: 24px;">${p.icono || "📦"}</span>`;
  }

  const container = document.getElementById("editVariantesContainer");
  container.innerHTML = "";
  if (p.variantes) {
    try {
      const vars = typeof p.variantes === "string" ? JSON.parse(p.variantes) : p.variantes;
      if (Array.isArray(vars)) {
        vars.forEach((v) => addVariantToUI("edit", v.label, v.valor));
      } else {
        for (const [l, v] of Object.entries(vars)) addVariantToUI("edit", l, v);
      }
    } catch (e) { console.error("Error parsing variantes", e); }
  }

  document.querySelectorAll(".form-error").forEach((el) => (el.innerText = ""));
  document.getElementById("editModal").classList.add("visible");
}

async function saveEdit() {
  document.querySelectorAll(".form-error").forEach((el) => (el.innerText = ""));
  try {
    alert("🔧 DEBUG v6: saveEdit ejecutado. Si ves este mensaje, el JS actualizado está activo.");
    const id = parseInt(document.getElementById("editId")?.value);
    const name = document.getElementById("editName")?.value.trim();
    const codigo = document.getElementById("editSku")?.value.trim();
    const price = parseFloat(document.getElementById("editPrice")?.value);
    const iva = parseFloat(document.getElementById("editIva")?.value) || 21;
    const mesesGarantia = parseInt(document.getElementById("editMesesGarantia")?.value) || 24;
    const icono = document.getElementById("editEmoji")?.value.trim();

    const pOrig = PRODUCTS.find((x) => x.id === id);
    const resp = await fetch("./api/gestionProducto.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        accion: "editar",
        id,
        nombre: name,
        referencia: codigo,
        precio_venta: price,
        precio_coste: pOrig.precio_coste || 0,
        iva,
        stock_actual: pOrig.stock || 0,
        stock_minimo: pOrig.stock_minimo || 0,
        meses_garantia: mesesGarantia,
        requiere_serial: document.getElementById("editSerial").checked ? 1 : 0,
        icono,
        categoria: pOrig.cat,
        descripcion: pOrig.descripcion || "",
        variantes: getVariantsFromUI("edit"),
      }),
    });
    const data = await resp.json();

    if (!data.ok) {
      if (data.aErrores) {
        const msgs = Object.values(data.aErrores).filter(Boolean);
        showToast("❌ Errores de validación: " + msgs.join(" / "));
        for (const [key, msg] of Object.entries(data.aErrores)) {
          const errEl = document.getElementById("err-edit" + key.charAt(0).toUpperCase() + key.slice(1));
          if (errEl && msg) errEl.innerText = msg;
        }
        return;
      }
      throw new Error(data.error);
    }

    const p = PRODUCTS.find((x) => x.id === id);
    p.name = name;
    p.codigo = codigo;
    p.price = price;
    p.iva = iva;
    p.meses_garantia = mesesGarantia;
    p.requiere_serial = document.getElementById("editSerial").checked ? 1 : 0;
    p.icono = icono || p.icono;
    p.variantes = getVariantsFromUI("edit");

    document.getElementById("editModal").classList.remove("visible");
    renderProducts();
    const d = data._debug || {};
    showToast(`✅ Enviado IVA=${d.enviado_iva} → BD=${d.bd_iva} | Meses=${d.enviado_meses} → BD=${d.bd_meses}`);
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

      PRODUCTS.splice(PRODUCTS.findIndex((x) => x.id === id), 1);
      if (cart[id]) { delete cart[id]; renderCart(); }
      document.getElementById("deleteModal").classList.remove("visible");
      renderProducts();
      showToast("<i class='fa-solid fa-trash-can'></i> Producto eliminado de la BD");
    } catch (err) {
      showToast("<i class='fa-solid fa-circle-xmark'></i> " + err.message);
    }
  };
  document.getElementById("deleteModal").classList.add("visible");
}

function abrirModalDevolucion(idLinea, numTicket) {
  const modal = document.getElementById("returnModal");
  if (!modal) {
    console.error("No se encontró el modal de devolución (returnModal) en esta vista.");
    showToast("<i class='fa-solid fa-circle-xmark'></i> No se puede abrir el modal de devolución en esta pantalla.");
    return;
  }
  modal.classList.add("visible");
  document.getElementById("confirmReturnBtn").onclick = () => confirmarDevolucion(idLinea, numTicket);
}

function abrirModalAnulacionTicket(numTicket) {
  const modal = document.getElementById("returnModal");
  if (!modal) {
    console.error("No se encontró el modal de devolución (returnModal) en esta vista.");
    showToast("<i class='fa-solid fa-circle-xmark'></i> No se puede abrir el modal de anulación en esta pantalla.");
    return;
  }
  modal.classList.add("visible");
  document.getElementById("confirmReturnBtn").onclick = () => confirmarAnulacionTicket(numTicket);
}

async function confirmarDevolucion(idLinea, numTicket) {
  const motivo = document.getElementById("returnReason").value;
  const nota = document.getElementById("returnNote").value.trim();
  const finalMotivo = nota ? `${motivo}: ${nota}` : motivo;

  try {
    const resp = await fetch("./api/gestionDevolucion.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ accion: "devolverLinea", idLinea, motivo: finalMotivo }),
    });
    const data = await resp.json();
    if (!data.ok) throw new Error(data.error || "No se pudo realizar la devolución");

    document.getElementById("returnModal").classList.remove("visible");
    showToast("✅ Producto devuelto correctamente");
    const ventaActualizada = await cargarVenta(numTicket);
    mostrarTicket(ventaActualizada, false);
  } catch (err) {
    showToast("❌ No se pudo procesar la devolución: " + err.message);
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

    if (p.inactive && cart[id]) { delete cart[id]; renderCart(); }
    renderProducts();
    showToast(
      p.inactive
        ? "<i class='fa-solid fa-pause'></i> Producto dado de baja"
        : "<i class='fa-solid fa-play'></i> Producto reactivado"
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
function tick() {
  const clockEl = document.getElementById("clock");
  const dateEl = document.getElementById("datestr");
  const now = new Date();

  if (clockEl) clockEl.textContent = now.toLocaleTimeString("es-ES", { hour: "2-digit", minute: "2-digit" });
  if (dateEl) dateEl.textContent = now.toLocaleDateString("es-ES", { day: "2-digit", month: "short", year: "numeric" });
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
      document.querySelectorAll(".cat-tab").forEach((t) => t.classList.remove("active"));
      tab.classList.add("active");
      activeCat = tab.dataset.cat;
      renderProducts();
    });
  }

  renderProducts();
  updatePostponeUI();
}