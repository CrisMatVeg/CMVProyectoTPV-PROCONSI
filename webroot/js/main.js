const PRODUCTS = typeof DB_PRODUCTS !== "undefined" ? DB_PRODUCTS : [];

// ── Estado global ──────────────────────────────────────────────────────────────
let cart = {};
let selectedPayment = "efectivo";
let discountPct = 0;
let ticketNum = 1001;
let activeCat = "all";
let searchTerm = "";
let isAdmin =
  typeof IS_ADMIN_BACKEND !== "undefined" ? IS_ADMIN_BACKEND : false;

// ── Formato monetario ──────────────────────────────────────────────────────────
function fmt(n) {
  return n.toFixed(2).replace(".", ",") + " €";
}

// ── Catálogo ───────────────────────────────────────────────────────────────────
function renderProducts() {
  const grid = document.getElementById("productsGrid");
  if (!grid) return;

  let filtered = PRODUCTS.filter((p) => {
    const matchesCat = activeCat === "all" || p.cat === activeCat;
    const matchesSearch =
      p.name.toLowerCase().includes(searchTerm) ||
      p.codigo.toLowerCase().includes(searchTerm);
    return matchesCat && matchesSearch;
  });

  grid.innerHTML = filtered
    .map(
      (p) => `
        <div class="product-card${p.inactive ? " inactive" : ""}" id="card-${p.id}" onclick="handleCardClick(event, ${p.id}, this)">
            ${p.inactive ? '<div class="baja-pill">Baja</div>' : ""}
            <span class="product-emoji">${p.icono}</span>
            <div>
                <div class="product-name">${p.name}</div>
                <div class="product-sku">${p.codigo}</div>
            </div>
            <div class="product-price" style="margin-top:auto">${fmt(p.price)}</div>
            
            <div class="product-admin-bar">
                <button class="admin-action edit" onclick="editProduct(event,${p.id})">Editar</button>
                <button class="admin-action delete" onclick="deleteProduct(event,${p.id})">Borrar</button>
                <button class="admin-action baja" onclick="toggleBaja(event,${p.id})">${p.inactive ? "Alta" : "Baja"}</button>
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
  addToCart(id, el);
}

// ── Carrito ────────────────────────────────────────────────────────────────────
function addToCart(id, el) {
  el.classList.add("adding");
  setTimeout(() => el.classList.remove("adding"), 300);
  if (cart[id]) {
    cart[id].qty++;
  } else {
    const p = PRODUCTS.find((x) => x.id === id);
    cart[id] = { ...p, qty: 1 };
  }
  renderCart();
}

function changeQty(id, delta) {
  if (!cart[id]) return;
  cart[id].qty += delta;
  if (cart[id].qty <= 0) delete cart[id];
  renderCart();
}

function clearCart() {
  cart = {};
  discountPct = 0;
  document.getElementById("discountCode").value = "";
  document.getElementById("discountRow").style.display = "none";
  renderCart();
}

function renderCart() {
  const items = Object.values(cart);
  const container = document.getElementById("orderItems");

  document.getElementById("orderCount").textContent = items.reduce(
    (a, b) => a + b.qty,
    0,
  );

  // Carrito vacío — siempre se regenera con innerHTML para evitar errores de nodo
  if (!items.length) {
    container.innerHTML = `
      <div class="empty-cart">
        <div class="empty-cart-icon">🛒</div>
        <div>Añade productos<br>al pedido</div>
      </div>`;
    updateTotals(0);
    return;
  }

  container.innerHTML = items
    .map(
      (item) => `
    <div class="order-item">
      <span class="order-item-emoji">${item.icono}</span>
      <div class="order-item-info">
        <div class="order-item-name">${item.name}</div>
        <div class="order-item-price">${fmt(item.price)} × ${item.qty}</div>
      </div>
      <div class="qty-ctrl">
        <button class="qty-btn" onclick="changeQty(${item.id}, -1)">−</button>
        <span class="qty-val">${item.qty}</span>
        <button class="qty-btn" onclick="changeQty(${item.id}, +1)">+</button>
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
  const discountAmt = (subtotal * discountPct) / 100;
  const base = subtotal - discountAmt;
  const vat = base * 0.21;
  const total = base + vat;

  document.getElementById("subtotal").textContent = fmt(subtotal);
  document.getElementById("vatAmt").textContent = fmt(vat);
  document.getElementById("totalAmt").textContent = fmt(total);
  document.getElementById("chargeTotal").textContent = fmt(total);
  document.getElementById("discountAmt").textContent = "-" + fmt(discountAmt);

  document.getElementById("chargeBtn").disabled = subtotal === 0;
}

// ── Pago ───────────────────────────────────────────────────────────────────────
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
  const codes = { DESC10: 10, OFERTA20: 20, VIP15: 15 };
  if (codes[code]) {
    discountPct = codes[code];
    document.getElementById("discountRow").style.display = "flex";
    const subtotal = Object.values(cart).reduce(
      (a, b) => a + b.price * b.qty,
      0,
    );
    updateTotals(subtotal);
    showToast(`✅ Descuento del ${discountPct}% aplicado`);
  } else {
    showToast("❌ Código no válido. Prueba: DESC10, OFERTA20, VIP15");
  }
}

// Estado del tipo de cliente seleccionado
let tipoClienteActual = "particular";

// Paso 1: Abrir el modal de tipo de cliente
function processPayment() {
  // Resetear selección
  tipoClienteActual = "particular";
  document.getElementById("empresaDatos").style.display = "none";
  document.getElementById("empresaNombre").value = "";
  document.getElementById("empresaNif").value = "";
  const btnP = document.getElementById("btnParticular");
  const btnE = document.getElementById("btnEmpresa");
  btnP.style.borderColor = "var(--accent)";
  btnP.style.background = "var(--blue-light)";
  btnE.style.borderColor = "var(--border)";
  btnE.style.background = "var(--surface2)";
  document.getElementById("clienteModal").classList.add("visible");
}

// Paso 2: Seleccionar tipo de cliente (particular o empresa)
function seleccionarTipoCliente(tipo) {
  tipoClienteActual = tipo;
  const btnP = document.getElementById("btnParticular");
  const btnE = document.getElementById("btnEmpresa");
  if (tipo === "particular") {
    btnP.style.borderColor = "var(--accent)";
    btnP.style.background = "var(--blue-light)";
    btnE.style.borderColor = "var(--border)";
    btnE.style.background = "var(--surface2)";
    document.getElementById("empresaDatos").style.display = "none";
  } else {
    btnE.style.borderColor = "var(--accent)";
    btnE.style.background = "var(--blue-light)";
    btnP.style.borderColor = "var(--border)";
    btnP.style.background = "var(--surface2)";
    document.getElementById("empresaDatos").style.display = "flex";
  }
}

// Paso 3: Confirmar cliente y enviar la venta a la BD via API
async function confirmarCliente() {
  const btn = document.getElementById("confirmarClienteBtn");
  btn.disabled = true;
  btn.textContent = "Guardando…";

  const items = Object.values(cart);
  const subtotal = items.reduce((a, b) => a + b.price * b.qty, 0);

  const payload = {
    tipoCliente: tipoClienteActual,
    nombreCliente:
      tipoClienteActual === "empresa"
        ? document.getElementById("empresaNombre").value
        : null,
    nifCliente:
      tipoClienteActual === "empresa"
        ? document.getElementById("empresaNif").value
        : null,
    metodoPago: selectedPayment,
    subtotal: subtotal,
    descuentoPct: discountPct,
    lineas: items.map((it) => ({
      id: it.id,
      name: it.name,
      codigo: it.codigo,
      price: it.price,
      qty: it.qty,
    })),
  };

  try {
    const resp = await fetch("./api/guardarVenta.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    const data = await resp.json();

    if (!resp.ok || !data.ok) {
      throw new Error(data.error || "Error al guardar la venta");
    }

    // Ocultar modal de cliente y mostrar ticket
    document.getElementById("clienteModal").classList.remove("visible");
    mostrarTicket(data.venta);
  } catch (err) {
    showToast("❌ " + err.message);
  } finally {
    btn.disabled = false;
    btn.textContent = "Cobrar";
  }
}

// Paso 4: Rellenar y mostrar el documento de venta
function mostrarTicket(v) {
  const fmt2 = (n) => parseFloat(n).toFixed(2).replace(".", ",") + " €";
  const date = new Date(v.creado_en.replace(" ", "T"));
  const fechaStr =
    date.toLocaleDateString("es-ES") +
    " " +
    date.toLocaleTimeString("es-ES", { hour: "2-digit", minute: "2-digit" });

  // Tipo de documento
  document.getElementById("tkTipoDoc").textContent =
    v.tipo_cliente === "empresa" ? "FACTURA" : "TICKET DE VENTA";

  // Meta
  document.getElementById("tkNumero").textContent =
    "#" + String(v.numero_ticket).padStart(4, "0");
  document.getElementById("tkFecha").textContent = fechaStr;
  document.getElementById("tkMetodo").textContent =
    v.metodo_pago.charAt(0).toUpperCase() + v.metodo_pago.slice(1);

  // Cajero
  const tkCajero = document.getElementById("tkCajero");
  if (typeof CAJERO_NOMBRE !== "undefined") {
    tkCajero.textContent = CAJERO_NOMBRE;
  } else {
    tkCajero.textContent = "—";
  }

  // Datos de empresa
  const showEmp = v.tipo_cliente === "empresa";
  ["tkLabelCliente", "tkNombreCliente", "tkLabelNif", "tkNifCliente"].forEach(
    (id) => {
      document.getElementById(id).style.display = showEmp ? "" : "none";
    },
  );
  if (showEmp) {
    document.getElementById("tkNombreCliente").textContent =
      v.nombre_cliente || "—";
    document.getElementById("tkNifCliente").textContent = v.nif_cliente || "—";
  }

  // Líneas de producto
  const lineasEl = document.getElementById("tkLineas");
  lineasEl.innerHTML = v.lineas
    .map(
      (l) => `
    <div style="display:flex; justify-content:space-between; padding: 4px 0; border-bottom: 1px solid var(--surface2);">
      <div>
        <span style="font-weight:600;">${l.nombre_producto}</span>
        <span style="color:var(--text-muted); font-size:11px; margin-left:6px;">${l.codigo_producto}</span><br>
        <span style="color:var(--text-muted); font-size:11px;">${l.cantidad} × ${fmt2(l.precio_unitario)}</span>
      </div>
      <span style="font-family:'DM Mono',monospace; font-weight:600; align-self:center;">${fmt2(l.total_linea)}</span>
    </div>
  `,
    )
    .join("");

  // Totales
  document.getElementById("tkSubtotal").textContent = fmt2(v.subtotal);
  document.getElementById("tkBase").textContent = fmt2(v.base_imponible);
  document.getElementById("tkIva").textContent = fmt2(v.iva_amt);
  document.getElementById("tkTotal").textContent = fmt2(v.total);

  const descRow = document.getElementById("tkDescRow");
  if (parseFloat(v.descuento_pct) > 0) {
    descRow.style.display = "flex";
    document.getElementById("tkDescLabel").textContent =
      `Descuento (${v.descuento_pct}%)`;
    document.getElementById("tkDescAmt").textContent =
      "−" + fmt2(v.descuento_amt);
  } else {
    descRow.style.display = "none";
  }

  document.getElementById("ticketModal").classList.add("visible");
}

// Imprimir solo el área del ticket
function imprimirTicket() {
  window.print();
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

// ── Modo administrador ─────────────────────────────────────────────────────────
function requireAdmin() {
  if (!isAdmin) {
    showToast("⚠️ Acceso restringido a administradores");
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
  document.getElementById("addModal").classList.add("visible");
}

// Guardar nuevo producto en BD
async function guardarNuevoProducto() {
  const name = document.getElementById("addName").value.trim();
  const codigo = document.getElementById("addSku").value.trim();
  const price = parseFloat(document.getElementById("addPrice").value);
  const icono = document.getElementById("addEmoji").value.trim();
  const cat = document.getElementById("addCat").value;

  if (!name || !codigo || isNaN(price)) {
    showToast("❌ Por favor, rellena todos los campos");
    return;
  }

  try {
    const resp = await fetch("./api/gestionProducto.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        accion: "añadir",
        nombre: name,
        codigo,
        precio: price,
        icono,
        categoria: cat,
      }),
    });
    const data = await resp.json();

    if (!data.ok) throw new Error(data.error);

    PRODUCTS.push(data.producto);
    document.getElementById("addModal").classList.remove("visible");
    renderProducts();
    showToast("✅ Producto añadido correctamente");
  } catch (err) {
    showToast("❌ " + err.message);
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
  document.getElementById("editEmoji").value = p.icono;
  document.getElementById("editModal").classList.add("visible");
}

async function saveEdit() {
  const id = parseInt(document.getElementById("editId").value);
  const name = document.getElementById("editName").value.trim();
  const codigo = document.getElementById("editSku").value.trim();
  const price = parseFloat(document.getElementById("editPrice").value);
  const icono = document.getElementById("editEmoji").value.trim();

  try {
    const resp = await fetch("./api/gestionProducto.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        accion: "editar",
        id,
        nombre: name,
        codigo,
        precio: price,
        icono,
        categoria: PRODUCTS.find((x) => x.id === id).cat,
      }),
    });
    const data = await resp.json();

    if (!data.ok) throw new Error(data.error);

    const p = PRODUCTS.find((x) => x.id === id);
    p.name = name || p.name;
    p.codigo = codigo || p.codigo;
    p.price = price || p.price;
    p.icono = icono || p.icono;

    document.getElementById("editModal").classList.remove("visible");
    renderProducts();
    showToast("✅ Producto actualizado en BD");
  } catch (err) {
    showToast("❌ " + err.message);
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
      showToast("🗑️ Producto eliminado de la BD");
    } catch (err) {
      showToast("❌ " + err.message);
    }
  };
  document.getElementById("deleteModal").classList.add("visible");
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
      p.inactive ? "⏸️ Producto dado de baja" : "▶️ Producto reactivado",
    );
  } catch (err) {
    showToast("❌ " + err.message);
  }
}

// ── Toast ──────────────────────────────────────────────────────────────────────
function showToast(msg) {
  const t = document.getElementById("toast");
  t.textContent = msg;
  t.classList.add("show");
  setTimeout(() => t.classList.remove("show"), 2500);
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

// ── Inicialización Protegida ──────────────────────────────────────────────────
// Solo ejecutamos lógica de TPV si estamos en la vista del catálogo
const productsGrid = document.getElementById("productsGrid");

if (productsGrid) {
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

  // Eventos de búsqueda
  const searchInput = document.getElementById("searchInput");
  if (searchInput) {
    searchInput.addEventListener("input", (e) => {
      searchTerm = e.target.value.toLowerCase();
      renderProducts();
    });
  }

  // Carga inicial de productos
  renderProducts();
}
