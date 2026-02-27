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
let currentTicketNum = null;
let socioActual = null; // Almacena el objeto cliente si es socio/empresa identificado
const SOCIO_DISCOUNT = 5; // 5% de descuento para socios

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
      p.name.toLowerCase().includes(searchTerm) ||
      p.codigo.toLowerCase().includes(searchTerm);
    return matchesSearch;
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
  const discountAmt = (subtotal * discountPct) / 100;
  const socioAmt =
    socioActual && socioActual.es_socio ? (subtotal * SOCIO_DISCOUNT) / 100 : 0;

  const totalDiscount = discountAmt + socioAmt;
  const base = subtotal - totalDiscount;
  const vat = base * 0.21;
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
  const el_errDiscount = document.getElementById("err-discount");
  if (el_errDiscount) el_errDiscount.innerText = "";

  if (codes[code]) {
    discountPct = codes[code];
    document.getElementById("discountRow").style.display = "flex";
    const subtotal = Object.values(cart).reduce(
      (a, b) => a + b.price * b.qty,
      0,
    );
    updateTotals(subtotal);
    showToast(
      `<i class="fa-solid fa-circle-check"></i> Descuento del ${discountPct}% aplicado`,
    );
  } else {
    if (el_errDiscount) {
      el_errDiscount.innerText =
        "Código no válido. Prueba: DESC10, OFERTA20, VIP15";
    } else {
      showToast(
        '<i class="fa-solid fa-circle-xmark"></i> Código no válido. Prueba: DESC10, OFERTA20, VIP15',
      );
    }
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

  if (tipo === "particular") {
    btnP.classList.add("selected-type");
    socioActual = null;
  } else if (tipo === "socio") {
    if (btnS) btnS.classList.add("selected-type");
    document.getElementById("socioBusqueda").style.display = "flex";
    setTimeout(() => document.getElementById("socioSearch").focus(), 100);
  } else {
    btnE.classList.add("selected-type");
    document.getElementById("empresaDatos").style.display = "flex";
    setTimeout(() => document.getElementById("empresaNombre").focus(), 100);
    socioActual = null;
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
        : socioActual
          ? socioActual.nif
          : null,
    idCliente: socioActual ? socioActual.id : null,
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
    btnAnular.onclick = () => devolverTicket(v.numero_ticket);
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
      .map(
        (l) => `
    <div style="display:flex; justify-content:space-between; padding: 4px 0; border-bottom: 1px solid var(--surface2); ${l.devuelta ? "opacity:0.6; background:rgba(192,57,43,0.05);" : ""}">
      <div>
        <span style="font-weight:600; ${l.devuelta ? "text-decoration:line-through;" : ""}">${l.nombre_producto}</span>
        <span style="color:var(--text-muted); font-size:11px; margin-left:6px;">${l.codigo_producto}</span>
        ${l.devuelta ? '<span class="status-pill p-2-4 fs-10" style="background:var(--red); color:white; margin-left:8px;">DEVUELTO</span>' : ""}
        <br>
        <span style="color:var(--text-muted); font-size:11px;">${l.cantidad} × ${fmt2(l.precio_unitario)}</span>
      </div>
      <div style="display:flex; align-items:center; gap:12px;">
        <span style="font-family:'DM Mono',monospace; font-weight:600;">${fmt2(l.total_linea)}</span>
        ${
          !isFromTPV && !l.devuelta && v.estado === "completada"
            ? `
            <button onclick="devolverLinea(${l.id}, ${v.numero_ticket})" title="Devolver este producto" style="border:none; background:none; color:var(--red); cursor:pointer; padding:4px;">
                <i class="fa-solid fa-arrow-rotate-left"></i>
            </button>
        `
            : ""
        }
      </div>
    </div>
  `,
      )
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

// Imprimir solo el área del ticket
function imprimirTicket() {
  window.print();
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
  if (!confirm("¿Deseas devolver este producto y reponer stock?")) return;
  try {
    const resp = await fetch("api/gestionDevolucion.php", {
      method: "POST",
      body: JSON.stringify({ accion: "devolverLinea", idLinea }),
    });
    const r = await resp.json();
    if (r.ok) {
      showToast("Producto devuelto con éxito");
      // Recargar ticket
      const v = await cargarVenta(numTicket);
      mostrarTicket(v, false);
    } else {
      throw new Exception(r.error);
    }
  } catch (e) {
    showToast("Error: " + e.message);
  }
}

async function devolverTicket(numTicket) {
  if (
    !confirm(
      "¿Deseas ANULAR y DEVOLVER el ticket completo? Esta acción es irreversible.",
    )
  )
    return;
  try {
    const resp = await fetch("api/gestionDevolucion.php", {
      method: "POST",
      body: JSON.stringify({ accion: "devolverTicket", numTicket }),
    });
    const r = await resp.json();
    if (r.ok) {
      showToast("Ticket devuelto y stock restaurado");
      const v = await cargarVenta(numTicket);
      mostrarTicket(v, false);
    } else {
      throw new Exception(r.error);
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
        codigo,
        precio: price,
        icono,
        categoria: cat,
        stock: 0, // Default stock for new products from TPV
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
  const id = parseInt(document.getElementById("editId").value);
  const name = document.getElementById("editName").value.trim();
  const codigo = document.getElementById("editSku").value.trim();
  const price = parseFloat(document.getElementById("editPrice").value);
  const icono = document.getElementById("editEmoji").value.trim();

  document.querySelectorAll(".form-error").forEach((el) => (el.innerText = ""));
  try {
    const pOrig = PRODUCTS.find((x) => x.id === id);
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
        categoria: pOrig.cat,
        stock: pOrig.stock,
      }),
    });
    const data = await resp.json();

    if (!data.ok) {
      if (data.aErrores) {
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
    p.name = name || p.name;
    p.codigo = codigo || p.codigo;
    p.price = price || p.price;
    p.icono = icono || p.icono;

    document.getElementById("editModal").classList.remove("visible");
    renderProducts();
    showToast("✅ Producto actualizado en BD");
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
