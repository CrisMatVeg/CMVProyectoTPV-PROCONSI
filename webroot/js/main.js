const PRODUCTS = typeof DB_PRODUCTS !== "undefined" ? DB_PRODUCTS : [];

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
let isAdmin =
  typeof IS_ADMIN_BACKEND !== "undefined" ? IS_ADMIN_BACKEND : false;
let currentTicketNum = null;
let socioActual = null; // Almacena el objeto cliente si es socio/empresa identificado
const SOCIO_DISCOUNT = 5; // 5% de descuento para socios

// ── Tema (modo + acento) ──────────────────────────────────────────────────────
function applyTheme(mode, accent) {
  const body = document.body;
  if (!body) return;
  if (mode) body.dataset.themeMode = mode;
  if (accent) body.dataset.themeAccent = accent;
}

function setThemeMode(mode, persist = true) {
  applyTheme(mode, null);
  if (!persist) return;
  try {
    fetch("api/guardarTema.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ theme_mode: mode }),
    });
  } catch (e) {
    console.error(e);
  }
}

function setThemeAccent(accent, persist = true) {
  applyTheme(null, accent);
  if (!persist) return;
  try {
    fetch("api/guardarTema.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ theme_accent: accent }),
    });
  } catch (e) {
    console.error(e);
  }
}

// Inicializar tema desde constantes PHP si existen
if (typeof USER_THEME_MODE !== "undefined" || typeof USER_THEME_ACCENT !== "undefined") {
  applyTheme(USER_THEME_MODE || "light", USER_THEME_ACCENT || "blue");
}

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

    // Filtros Avanzados
    const matchesSearch =
      p.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      p.codigo.toLowerCase().includes(searchTerm.toLowerCase());
    if (!matchesSearch) return false;

    const matchesPrice = p.price >= minPrice && p.price <= maxPrice;
    if (!matchesPrice) return false;

    let matchesStock = true;
    if (stockFilter === "in-stock") matchesStock = p.stock > 0;
    else if (stockFilter === "low-stock")
      matchesStock = p.stock > 0 && p.stock <= 5;
    if (!matchesStock) return false;

    return true;
  });

  // Ordenación
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
            <div class="product-icon">
                ${p.icono && p.icono.startsWith("data:image") ? `<img src="${p.icono}" class="prod-img-tpv" alt="${p.name}">` : `<span class="product-emoji">${p.icono}</span>`}
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
    `,
    )
    .join("");

  // Aplicamos la clase que muestra/oculta las opciones según el rol
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
  addToCart(id, el);
}

// ── Carrito ────────────────────────────────────────────────────────────────────
function addToCart(id, el) {
  const p = PRODUCTS.find((x) => x.id === id);
  if (!p) return;

  if (cart[id]) {
    if (cart[id].qty >= p.stock) {
      showToast(
        '<i class="fa-solid fa-circle-exclamation"></i> No hay más stock disponible',
      );
      return;
    }
    cart[id].qty++;
  } else {
    if (p.stock <= 0) return;
    cart[id] = { ...p, qty: 1 };
    // Ensure IVA is a number, fallback to 21
    cart[id].iva = parseFloat(p.iva || 21);
    // Support for serial numbers
    cart[id].serials = [];
  }

  el.classList.add("adding");
  setTimeout(() => el.classList.remove("adding"), 300);
  renderCart();
}

function changeQty(id, delta) {
  if (!cart[id]) return;
  const p = PRODUCTS.find((x) => x.id === id);

  if (delta > 0 && cart[id].qty >= p.stock) {
    showToast(
      '<i class="fa-solid fa-circle-exclamation"></i> Límite de stock alcanzado',
    );
    return;
  }

  cart[id].qty += delta;
  if (cart[id].qty <= 0) delete cart[id];
  renderCart();
}

function clearCart() {
  cart = {};
  discountPct = 0;
  const el_discountCode = document.getElementById("discountCode");
  const el_discountRow = document.getElementById("discountRow");
  const el_errDiscount = document.getElementById("err-discount");
  if (el_discountCode) el_discountCode.value = "";
  if (el_discountRow) el_discountRow.style.display = "none";
  if (el_errDiscount) el_errDiscount.innerText = "";
  renderCart();
}

function renderCart() {
  const items = Object.values(cart);
  const container = document.getElementById("orderItems");
  const orderCountEl = document.getElementById("orderCount");

  // Si no estamos en el TPV, los elementos del carrito no existen, así que salimos
  if (!container || !orderCountEl) return;

  orderCountEl.textContent = items.reduce((a, b) => a + b.qty, 0);

  // Carrito vacío — siempre se regenera con innerHTML para evitar errores de nodo
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
        ${item.icono && item.icono.startsWith("data:image") ? `<img src="${item.icono}" class="order-item-img" alt="${item.name}">` : item.icono}
      </span>
      <div class="order-item-info">
        <div class="order-item-name">${item.name}</div>
        <div class="order-item-price">${fmt(item.price)} × ${item.qty}</div>
      </div>
      <div class="qty-ctrl">
        <button class="qty-btn" onclick="changeQty(${item.id}, -1)"><i class="fa-solid fa-minus"></i></button>
        <span class="qty-val">${item.qty}</span>
        <button class="qty-btn" onclick="changeQty(${item.id}, +1)"><i class="fa-solid fa-plus"></i></button>
      </div>
      <div class="order-item-total">${fmt(item.price * item.qty)}</div>
    </div>
  `,
    )
    .join("");

  const subtotal = items.reduce((a, b) => a + b.price * b.qty, 0);
  updateTotals(subtotal);
}

function updateTotals(subtotal) {
  const items = Object.values(cart);
  const discountAmt = (subtotal * discountPct) / 100;
  const socioAmt =
    socioActual && socioActual.es_socio ? (subtotal * SOCIO_DISCOUNT) / 100 : 0;

  const totalDiscount = discountAmt + socioAmt;
  const discountFactor =
    subtotal > 0 ? (subtotal - totalDiscount) / subtotal : 1;

  // Calculate VAT per item considering its specific rate and applied discount
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
}

// ── Pago ───────────────────────────────────────────────────────────────────────
// Catálogo de promociones cargado desde backend
const PROMOS = typeof DB_PROMOS !== "undefined" ? DB_PROMOS : [];
let currentPromo = null;
function selectPayment(el) {
  document
    .querySelectorAll(".pay-btn")
    .forEach((b) => b.classList.remove("selected"));
  el.classList.add("selected");
  selectedPayment = el.dataset.method;
}

function applyDiscount() {
  const code = document
    .getElementById("discountCode")
    .value.trim()
    .toUpperCase();
  const el_errDiscount = document.getElementById("err-discount");
  if (el_errDiscount) el_errDiscount.innerText = "";

  const subtotal = Object.values(cart).reduce(
    (a, b) => a + b.price * b.qty,
    0,
  );

  const promo = PROMOS.find((p) => p.code === code);

  if (promo) {
    if (subtotal < promo.minSubtotal) {
      if (el_errDiscount) {
        el_errDiscount.innerText = `Importe mínimo ${fmt(promo.minSubtotal)} para usar este cupón`;
      } else {
        showToast(
          `<i class="fa-solid fa-circle-exclamation"></i> Importe mínimo ${fmt(promo.minSubtotal)} para usar este cupón`,
        );
      }
      return;
    }

    if (promo.type === "percent") {
      discountPct = promo.value;
    } else {
      // importe fijo → lo convertimos a % sobre el subtotal actual
      discountPct = subtotal > 0 ? (promo.value / subtotal) * 100 : 0;
    }

    currentPromo = promo;
    document.getElementById("discountRow").style.display = "flex";
    updateTotals(subtotal);

    const descText =
      promo.type === "percent"
        ? `${promo.value}% aplicado (${promo.code})`
        : `-${promo.value.toFixed(2)} € aplicado (${promo.code})`;

    showToast(`<i class="fa-solid fa-circle-check"></i> ${descText}`);
  } else {
    if (el_errDiscount) {
      el_errDiscount.innerText =
        "Código no válido o inactivo";
    } else {
      showToast(
        '<i class="fa-solid fa-circle-xmark"></i> Código no válido o inactivo',
      );
    }
  }
}

// Estado del tipo de cliente seleccionado
let tipoClienteActual = "particular";
// Cliente seleccionado desde búsquedas (particular / empresa)
let clienteSeleccionado = null;
let ULTIMOS_CLIENTES_BUSCADOS = [];

// Paso 1: Abrir el modal de tipo de cliente
function processPayment() {
  // Resetear selección
  tipoClienteActual = "particular";
  document.getElementById("empresaDatos").style.display = "none";
  document.getElementById("empresaNombre").value = "";
  document.getElementById("empresaNif").value = "";

  // Resetear gestión de efectivo
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

// Paso 2: Seleccionar tipo de cliente (particular, socio o empresa)
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
      const subtotal = Object.values(cart).reduce(
        (a, b) => a + b.price * b.qty,
        0,
      );
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
    // socios usan buscarSocio()
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
  document.getElementById("newSocioNif").value =
    document.getElementById("socioSearch").value;
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
      document.getElementById("socioInfo").innerHTML =
        `<i class="fa-solid fa-check-circle"></i> ${nombre} (${nif})`;
      const subtotal = Object.values(cart).reduce(
        (a, b) => a + b.price * b.qty,
        0,
      );
      updateTotals(subtotal);
      showToast("Socio registrado y aplicado");
    }
  } catch (e) {
    console.error(e);
  }
}

// Cálculo de cambio en tiempo real
function calcularCambio() {
  const recibido =
    parseFloat(document.getElementById("efectivoRecibido").value) || 0;
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

// Paso 3: Confirmar cliente y enviar la venta a la BD via API
async function confirmarCliente() {
  const items = Object.values(cart);

  // 1. Verificar si hay productos que requieren Nº Serie
  const itemsWithSerial = items.filter(
    (it) => it.requiere_serial && it.qty > 0,
  );

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
      abrirModalPago(); // Volver al flujo de pago
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

  // Calcular descuentos finales
  const discountAmt = (subtotal * discountPct) / 100;
  const socioAmt =
    socioActual && socioActual.es_socio ? (subtotal * SOCIO_DISCOUNT) / 100 : 0;

  const clienteId =
    tipoClienteActual === "socio"
      ? socioActual
        ? socioActual.id
        : null
      : clienteSeleccionado
        ? clienteSeleccionado.id
        : null;

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
    subtotal: subtotal,
    total:
      (subtotal -
        (subtotal *
          (discountPct +
            (socioActual && socioActual.es_socio ? SOCIO_DISCOUNT : 0))) /
          100) *
      1.21,
    descuentoPct:
      discountPct + (socioActual && socioActual.es_socio ? SOCIO_DISCOUNT : 0),
    lineas: items.map((it) => ({
      id: it.id,
      name: it.name,
      codigo: it.codigo,
      price: it.price,
      qty: it.qty,
      serials: it.serials || [], // Enviar seriales
    })),
    efectivo: {
      recibido:
        parseFloat(document.getElementById("efectivoRecibido").value) || 0,
    },
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

    // Inyectamos el importe recibido para el ticket
    if (data.venta) {
      data.venta.efectivo_recibido = payload.efectivo.recibido;
      // Actualizamos el número de ticket global
      currentTicketNum = data.venta.numero_ticket;

      // MOSTRAR TICKET PRIMERO
      mostrarTicket(data.venta);

      // CERRAR MODAL DESPUÉS
      cerrarModalCliente();

      // LIMPIAR CARRITO AUTOMÁTICAMENTE
      clearCart();

      showToast(
        "<i class='fa-solid fa-circle-check'></i> Venta guardada correctamente",
      );
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

// helper: resolver una venta por número de ticket mediante AJAX
async function cargarVenta(ticketNum) {
  const resp = await fetch("./api/obtenerVenta.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ numTicket: ticketNum }),
  });
  const data = await resp.json();
  if (!resp.ok || !data.ok) {
    throw new Error(data.error || "Error al cargar venta");
  }
  return data.venta;
}

// invocado desde tablas de historial/cierre para mostrar el ticket
async function verTicket(ticketNum) {
  try {
    const venta = await cargarVenta(ticketNum);
    mostrarTicket(venta, false); // false = desde historial, no es interactivo
  } catch (err) {
    showToast("<i class='fa-solid fa-circle-xmark'></i> " + err.message);
  }
}
// asegurar globals para el onclick
window.verTicket = verTicket;
window.cargarVenta = cargarVenta;

// Paso 4: Rellenar y mostrar el documento de venta
// @param v objeto venta
// @param isFromTPV boolean - true si es desde TPV (interactivo), false si es desde historial/cierre (lectura)
function mostrarTicket(v, isFromTPV = true) {
  if (!v) return;
  // Actualizamos el número de ticket actual para acciones posteriores (ej: email)
  currentTicketNum = v.numero_ticket;

  // Mostrar/ocultar sección de acciones según contexto
  const emailSection = document.getElementById("ticketEmailSection");
  const btnPrint = document.querySelector('button[onclick="imprimirTicket()"]');
  const btnNuevaVenta = document.getElementById("btnNuevaVenta");

  if (emailSection) emailSection.style.display = isFromTPV ? "" : "none";
  if (btnPrint) btnPrint.style.display = isFromTPV ? "" : "none";
  if (btnNuevaVenta) btnNuevaVenta.style.display = isFromTPV ? "" : "none";

  // Mostrar botón de anulación total solo si es modo historial y está completada
  const btnAnular = document.getElementById("btnAnularTicket");
  if (btnAnular) {
    btnAnular.style.display =
      !isFromTPV && v.estado === "completada" ? "" : "none";
    btnAnular.onclick = () => abrirModalAnulacionTicket(v.numero_ticket);
  }

  // Resetear campo email y errores
  const emailInput = document.getElementById("tkEmailInput");
  if (emailInput) emailInput.value = "";
  const errEmail = document.getElementById("err-email");
  if (errEmail) errEmail.innerText = "";

  const fmt2 = (n) => parseFloat(n).toFixed(2).replace(".", ",") + " €";

  // Parseo de fecha robusto
  let date = new Date();
  if (v.fecha) {
    const fStr = v.fecha.includes("T") ? v.fecha : v.fecha.replace(" ", "T");
    date = new Date(fStr);
  }

  const fechaStr =
    date.toLocaleDateString("es-ES") +
    " " +
    date.toLocaleTimeString("es-ES", { hour: "2-digit", minute: "2-digit" });

  // Tipo de documento
  const isFactura = v.tipo_cliente === "empresa";
  const el_tkTipoDoc = document.getElementById("tkTipoDoc");
  if (el_tkTipoDoc)
    el_tkTipoDoc.textContent = isFactura ? "FACTURA" : "TICKET DE VENTA";

  // Agregar clase visual para facturas
  const ticketWrapper = document.getElementById("ticketContenido");
  if (ticketWrapper) {
    if (isFactura) {
      ticketWrapper.classList.add("es-factura");
    } else {
      ticketWrapper.classList.remove("es-factura");
    }
  }

  // Mostrar datos del cliente prominentemente en facturas
  const clienteSection = document.getElementById("tkClienteSection");
  const labelClienteMeta = document.getElementById("tkLabelClienteMeta");
  const clienteMeta = document.getElementById("tkClienteMeta");
  const labelNifMeta = document.getElementById("tkLabelNifMeta");
  const nifMeta = document.getElementById("tkNifMeta");

  if (isFactura && v.nombre_cliente) {
    // Ocultar caja separada
    if (clienteSection) clienteSection.classList.add("d-none");

    // Mostrar en metadatos integrado
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
    // Ticket particular: ocultar datos de cliente
    if (clienteSection) clienteSection.classList.add("d-none");
    if (labelClienteMeta) labelClienteMeta.classList.add("d-none");
    if (clienteMeta) clienteMeta.classList.add("d-none");
    if (labelNifMeta) labelNifMeta.classList.add("d-none");
    if (nifMeta) nifMeta.classList.add("d-none");
  }

  // Meta
  const el_tkNumero = document.getElementById("tkNumero");
  if (el_tkNumero)
    el_tkNumero.textContent = "#" + String(v.numero_ticket).padStart(4, "0");
  const el_tkFecha = document.getElementById("tkFecha");
  if (el_tkFecha) el_tkFecha.textContent = fechaStr;
  const el_tkMetodo = document.getElementById("tkMetodo");
  if (el_tkMetodo)
    el_tkMetodo.textContent =
      v.metodo_pago.charAt(0).toUpperCase() + v.metodo_pago.slice(1);

  // Cajero
  const tkCajero = document.getElementById("tkCajero");
  if (tkCajero) {
    // Usamos el nombre del cajero del registro de venta o el global de sesión
    if (v.nombre_cajero) {
      tkCajero.textContent = v.nombre_cajero;
    } else if (typeof CAJERO_NOMBRE !== "undefined" && CAJERO_NOMBRE) {
      tkCajero.textContent = CAJERO_NOMBRE;
    } else {
      tkCajero.textContent = "—";
    }
  }

  // Líneas de producto
  const lineasEl = document.getElementById("tkLineas");
  if (lineasEl)
    lineasEl.innerHTML = v.lineas
      .map((l) => {
        // Cálculo de garantía
        const vDate = new Date(v.fecha.replace(" ", "T"));
        const months = parseInt(l.meses_garantia || 24);
        const gDate = new Date(vDate);
        gDate.setMonth(gDate.getMonth() + months);
        const isExpired = gDate < new Date();
        const gStr = gDate.toLocaleDateString("es-ES");
        // Números de serie (pueden venir como JSON en numero_serie o como lista separada en numeros_serie)
        let serialDisplay = "";
        if (l.numeros_serie) {
          serialDisplay = String(l.numeros_serie)
            .split(",")
            .map((s) => s.trim())
            .filter(Boolean)
            .join(", ");
        } else if (l.numero_serie) {
          try {
            const parsed = JSON.parse(l.numero_serie);
            if (Array.isArray(parsed)) {
              serialDisplay = parsed.filter(Boolean).join(", ");
            }
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
            ${
              l.devuelta
                ? `
                <span class="fs-10 px-6 py-2 br-4" style="background:var(--red); color:white; font-weight:700;">DEVUELTO</span>
            `
                : ""
            }
        </div>

        ${
          serialDisplay
            ? `
            <div style="margin-top:4px; font-size:11px; color:var(--text-muted);">
                <i class="fa-solid fa-barcode"></i> N.º serie: ${serialDisplay}
            </div>
        `
            : ""
        }

        ${
          l.devuelta && l.motivo_devolucion
            ? `
            <div style="margin-top:4px; font-size:11px; color:var(--red); font-style:italic;">
                <i class="fa-solid fa-circle-info"></i> Motivo: ${l.motivo_devolucion}
            </div>
        `
            : ""
        }
      </div>

      ${
        !isFromTPV && !l.devuelta && v.estado === "completada"
          ? `
          <div style="padding-left:12px; display:flex; align-items:center;">
              <button onclick="abrirModalDevolucion(${l.id}, ${v.numero_ticket})" title="Devolver este producto" class="btn-icon text-red">
                  <i class="fa-solid fa-arrow-rotate-left"></i>
              </button>
          </div>
      `
          : ""
      }
    </div>
  `;
      })
      .join("");

  // Totales
  const el_tkSubtotal = document.getElementById("tkSubtotal");
  if (el_tkSubtotal) el_tkSubtotal.textContent = fmt2(v.subtotal);
  const el_tkBase = document.getElementById("tkBase");
  if (el_tkBase) el_tkBase.textContent = fmt2(v.base_imponible);
  const el_tkIva = document.getElementById("tkIva");
  if (el_tkIva) el_tkIva.textContent = fmt2(v.iva_amt);
  const el_tkTotal = document.getElementById("tkTotal");
  if (el_tkTotal) el_tkTotal.textContent = fmt2(v.total);

  // Gestión de efectivo en ticket
  const tkEfectivoRow = document.getElementById("tkEfectivoRow");
  if (tkEfectivoRow) {
    if (v.metodo_pago === "efectivo" && parseFloat(v.efectivo_recibido) > 0) {
      tkEfectivoRow.classList.remove("d-none");
      const el_tkEntregado = document.getElementById("tkEntregado");
      const el_tkCambio = document.getElementById("tkCambio");
      if (el_tkEntregado)
        el_tkEntregado.textContent = fmt2(v.efectivo_recibido);
      if (el_tkCambio)
        el_tkCambio.textContent = fmt2(v.efectivo_recibido - v.total);
    } else {
      tkEfectivoRow.classList.add("d-none");
    }
  }

  const descRow = document.getElementById("tkDescRow");
  if (descRow) {
    if (parseFloat(v.descuento_pct) > 0) {
      descRow.classList.remove("d-none");
      const el_tkDescLabel = document.getElementById("tkDescLabel");
      const el_tkDescAmt = document.getElementById("tkDescAmt");
      if (el_tkDescLabel)
        el_tkDescLabel.textContent = `Descuento (${v.descuento_pct}%)`;
      if (el_tkDescAmt) el_tkDescAmt.textContent = "−" + fmt2(v.descuento_amt);
    } else {
      descRow.classList.add("d-none");
    }
  }

  document.getElementById("ticketModal").classList.add("visible");
}

// Imprimir ticket: intenta ESC/POS vía API y, si falla o está desactivado, usa window.print()
async function imprimirTicket() {
  if (typeof ESC_POS_ENABLED !== "undefined" && ESC_POS_ENABLED) {
    if (!currentTicketNum) {
      showToast(
        "<i class='fa-solid fa-circle-xmark'></i> No hay ticket cargado para imprimir",
      );
      return;
    }

    try {
      const resp = await fetch("./api/imprimirTicket.php?id=" + currentTicketNum);
      const data = await resp.json();
      if (data.ok) {
        showToast(
          "<i class='fa-solid fa-print'></i> Ticket enviado a la impresora térmica",
        );
        return;
      }
      throw new Error(data.error || "Error al imprimir el ticket (ESC/POS)");
    } catch (err) {
      console.error("ESC/POS print error:", err);
      showToast(
        "<i class='fa-solid fa-circle-exclamation'></i> Error ESC/POS, usando impresión del navegador",
      );
      // Fallback
      window.print();
    }
  } else {
    window.print();
  }
}

// Enviar ticket por email
async function enviarTicketEmail() {
  const email = document.getElementById("tkEmailInput").value.trim();
  const btn = document.getElementById("btnSendEmail");
  const errEmail = document.getElementById("err-email");

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

  const originalContent = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando...';

  try {
    const resp = await fetch("./api/enviarVentaEmail.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        numTicket: currentTicketNum,
        email: email,
      }),
    });
    const data = await resp.json();

    if (data.ok) {
      showToast(
        "<i class='fa-solid fa-circle-check'></i> Ticket enviado con éxito",
      );
      document.getElementById("tkEmailInput").value = "";
    } else {
      if (data.aErrores && data.aErrores.email) {
        if (errEmail) errEmail.innerText = data.aErrores.email;
      } else {
        throw new Error(data.error || "Error al enviar el email");
      }
    }
  } catch (err) {
    showToast("<i class='fa-solid fa-circle-xmark'></i> " + err.message);
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalContent;
  }
}

// Nueva venta: cerrar ticket y resetear carrito
function nuevaVenta() {
  document.getElementById("ticketModal").classList.remove("visible");
  clearCart();
}

// Función legacy por si queda alguna referencia
function closeModal() {
  nuevaVenta();
}

async function devolverLinea(idLinea, numTicket) {
  // Mantener compatibilidad: redirigir al flujo con modal
  abrirModalDevolucion(idLinea, numTicket);
}

async function devolverTicket(numTicket) {
  // Mantener compatibilidad si quedara alguna llamada legacy
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

// Función para previsualizar imagen en modales TPV
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

// ── Modo administrador ─────────────────────────────────────────────────────────
function requireAdmin() {
  if (!isAdmin) {
    showToast(
      "<i class='fa-solid fa-triangle-exclamation'></i> Acceso restringido a administradores",
    );
    return false;
  }
  return true;
}

// Abrir modal de nuevo producto
function abrirModalNuevoProducto() {
  if (!requireAdmin()) return;
  document.getElementById("addName").value = "";
  document.getElementById("addSku").value = "";
  document.getElementById("addPrice").value = "";
  document.getElementById("addEmoji").value = "📦";
  document.getElementById("addCat").value = "audio";
  document.getElementById("addImgPreview").innerHTML =
    '<i class="fa-solid fa-image"></i>';
  document.querySelectorAll(".form-error").forEach((el) => (el.innerText = ""));
  document.getElementById("addModal").classList.add("visible");
}

// Guardar nuevo producto en BD
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
        iva: iva,
        stock_actual: 0,
        stock_minimo: 0,
        meses_garantia: 24,
        requiere_serial: document.getElementById("addSerial").checked ? 1 : 0,
        icono,
        categoria: cat,
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

  document.querySelectorAll(".form-error").forEach((el) => (el.innerText = ""));
  document.getElementById("editModal").classList.add("visible");
}

async function saveEdit() {
  document.querySelectorAll(".form-error").forEach((el) => (el.innerText = ""));
  try {
    alert(
      "🔧 DEBUG v6: saveEdit ejecutado. Si ves este mensaje, el JS actualizado está activo.",
    );
    const id = parseInt(document.getElementById("editId")?.value);
    const name = document.getElementById("editName")?.value.trim();
    const codigo = document.getElementById("editSku")?.value.trim();
    const price = parseFloat(document.getElementById("editPrice")?.value);
    const iva = parseFloat(document.getElementById("editIva")?.value) || 21;
    const mesesGarantia =
      parseInt(document.getElementById("editMesesGarantia")?.value) || 24;
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
        iva: iva,
        stock_actual: pOrig.stock || 0,
        stock_minimo: pOrig.stock_minimo || 0,
        meses_garantia: mesesGarantia,
        requiere_serial: document.getElementById("editSerial").checked ? 1 : 0,
        icono,
        categoria: pOrig.cat,
      }),
    });
    const data = await resp.json();

    if (!data.ok) {
      if (data.aErrores) {
        const msgs = Object.values(data.aErrores).filter(Boolean);
        showToast("\u274c Errores de validaci\u00f3n: " + msgs.join(" / "));
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
    p.price = price;
    p.iva = iva;
    p.meses_garantia = mesesGarantia;
    p.requiere_serial = document.getElementById("editSerial").checked ? 1 : 0;
    p.icono = icono || p.icono;

    document.getElementById("editModal").classList.remove("visible");
    renderProducts();
    const d = data._debug || {};
    showToast(
      `✅ Enviado IVA=${d.enviado_iva} → BD=${d.bd_iva} | Meses=${d.enviado_meses} → BD=${d.bd_meses}`,
    );
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

// Devoluciones promediadas con Modal
function abrirModalDevolucion(idLinea, numTicket) {
  const modal = document.getElementById("returnModal");
  if (!modal) {
    console.error(
      "No se encontró el modal de devolución (returnModal) en esta vista.",
    );
    showToast(
      "<i class='fa-solid fa-circle-xmark'></i> No se puede abrir el modal de devolución en esta pantalla.",
    );
    return;
  }

  modal.classList.add("visible");

  document.getElementById("confirmReturnBtn").onclick = () => {
    confirmarDevolucion(idLinea, numTicket);
  };
}

// Anulación total de ticket usando el mismo modal de motivo
function abrirModalAnulacionTicket(numTicket) {
  const modal = document.getElementById("returnModal");
  if (!modal) {
    console.error(
      "No se encontró el modal de devolución (returnModal) en esta vista.",
    );
    showToast(
      "<i class='fa-solid fa-circle-xmark'></i> No se puede abrir el modal de anulación en esta pantalla.",
    );
    return;
  }

  modal.classList.add("visible");

  document.getElementById("confirmReturnBtn").onclick = () => {
    confirmarAnulacionTicket(numTicket);
  };
}

async function confirmarDevolucion(idLinea, numTicket) {
  const motivo = document.getElementById("returnReason").value;
  const nota = document.getElementById("returnNote").value.trim();
  const finalMotivo = nota ? `${motivo}: ${nota}` : motivo;

  try {
    const resp = await fetch("./api/gestionDevolucion.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        accion: "devolverLinea",
        idLinea: idLinea,
        motivo: finalMotivo,
      }),
    });
    const data = await resp.json();
    if (!data.ok) throw new Error(data.error || "No se pudo realizar la devolución");

    document.getElementById("returnModal").classList.remove("visible");
    showToast("✅ Producto devuelto correctamente");
    // Recargar ticket con los datos actualizados
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
    p.inactive = !data.activo; // El API devuelve 'activo', nosotros usamos 'inactive'

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

// ── Toast (Sistema de notificaciones visuales) ───────────────────────────────
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

  // Eliminar tras 4 segundos
  setTimeout(() => {
    toast.style.animation = "toastOut 0.5s ease forwards";
    setTimeout(() => toast.remove(), 500);
  }, 4000);
}

// ── Búsqueda y Filtros Avanzados ───────────────────────────────────────────────
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
    if (btn) {
      btn.classList.toggle("is-open", !isHidden);
    }
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

// ── Reloj (Seguro) ─────────────────────────────────────────────────────────────
function tick() {
  const clockEl = document.getElementById("clock");
  const dateEl = document.getElementById("datestr");
  const now = new Date();

  if (clockEl) {
    clockEl.textContent = now.toLocaleTimeString("es-ES", {
      hour: "2-digit",
      minute: "2-digit",
    });
  }
  if (dateEl) {
    dateEl.textContent = now.toLocaleDateString("es-ES", {
      day: "2-digit",
      month: "short",
      year: "numeric",
    });
  }
}
tick();
setInterval(tick, 1000);

// ocultar botón nueva venta si no estamos en el TPV
if (typeof IS_TPV !== "undefined" && !IS_TPV) {
  const btnNV = document.getElementById("btnNuevaVenta");
  if (btnNV) btnNV.style.display = "none";
}

// cerrar ticket al hacer click fuera del contenido
const ticketOverlay = document.getElementById("ticketModal");
if (ticketOverlay) {
  ticketOverlay.addEventListener("click", (e) => {
    if (e.target === ticketOverlay) {
      nuevaVenta();
    }
  });
}

// ── Inicialización Protegida ──────────────────────────────────────────────────
// Solo ejecutamos lógica de TPV si estamos en la vista del catálogo
const productsGrid = document.getElementById("productsGrid");

if (productsGrid) {
  // Si estamos en TPV y la caja no está abierta, forzamos apertura antes de permitir ventas
  if (typeof IS_TPV !== "undefined" && IS_TPV) {
    if (typeof CAJA_ABIERTA !== "undefined" && !CAJA_ABIERTA) {
      const modalApertura = document.getElementById("aperturaCajaModal");
      if (modalApertura) {
        modalApertura.classList.add("visible");
      }
    }
  }

  // Eventos de categorías
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
  // Carga inicial de productos
  renderProducts();
}
