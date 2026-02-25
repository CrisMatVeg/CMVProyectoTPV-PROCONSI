const PRODUCTS = [
  // AUDIO
  {
    id: 1,
    name: "Auriculares BT Pro X",
    sku: "AUD-001",
    price: 89.99,
    emoji: "🎧",
    cat: "audio",
  },
  {
    id: 2,
    name: "Altavoz JBL Portable",
    sku: "AUD-002",
    price: 59.99,
    emoji: "🔊",
    cat: "audio",
  },
  {
    id: 3,
    name: "Auriculares In-Ear TWS",
    sku: "AUD-003",
    price: 39.99,
    emoji: "🎵",
    cat: "audio",
  },
  {
    id: 4,
    name: "Barra Sonido 2.1",
    sku: "AUD-004",
    price: 129.99,
    emoji: "📻",
    cat: "audio",
  },
  // MÓVIL
  {
    id: 5,
    name: "Funda iPhone 15 Pro",
    sku: "MOV-001",
    price: 14.99,
    emoji: "📱",
    cat: "movil",
  },
  {
    id: 6,
    name: "Protector Pantalla",
    sku: "MOV-002",
    price: 9.99,
    emoji: "🛡️",
    cat: "movil",
  },
  {
    id: 7,
    name: "Soporte Coche Mag",
    sku: "MOV-003",
    price: 19.99,
    emoji: "🚗",
    cat: "movil",
  },
  {
    id: 8,
    name: "Power Bank 20000mAh",
    sku: "MOV-004",
    price: 34.99,
    emoji: "🔋",
    cat: "movil",
  },
  // GAMING
  {
    id: 9,
    name: "Mando PS5 DualSense",
    sku: "GAM-001",
    price: 74.99,
    emoji: "🎮",
    cat: "gaming",
  },
  {
    id: 10,
    name: "Headset Gaming RGB",
    sku: "GAM-002",
    price: 49.99,
    emoji: "🎯",
    cat: "gaming",
  },
  {
    id: 11,
    name: "Mousepad XL",
    sku: "GAM-003",
    price: 14.99,
    emoji: "🖱️",
    cat: "gaming",
  },
  {
    id: 12,
    name: "Tarjeta PSN 50€",
    sku: "GAM-004",
    price: 50.0,
    emoji: "💳",
    cat: "gaming",
  },
  // INFORMÁTICA
  {
    id: 13,
    name: "Teclado Mecánico",
    sku: "INF-001",
    price: 79.99,
    emoji: "⌨️",
    cat: "informatica",
  },
  {
    id: 14,
    name: "Ratón Inalámbrico",
    sku: "INF-002",
    price: 44.99,
    emoji: "🖱️",
    cat: "informatica",
  },
  {
    id: 15,
    name: "Hub USB-C 7 en 1",
    sku: "INF-003",
    price: 34.99,
    emoji: "🔌",
    cat: "informatica",
  },
  {
    id: 16,
    name: "SSD Externo 1TB",
    sku: "INF-004",
    price: 89.99,
    emoji: "💾",
    cat: "informatica",
  },
  // CABLES Y CARGADORES
  {
    id: 17,
    name: "Cable USB-C a USB-C 2m",
    sku: "CAB-001",
    price: 12.99,
    emoji: "🔗",
    cat: "cables",
  },
  {
    id: 18,
    name: "Cargador GaN 65W",
    sku: "CAB-002",
    price: 29.99,
    emoji: "⚡",
    cat: "cables",
  },
  {
    id: 19,
    name: "Cable Lightning 1m",
    sku: "CAB-003",
    price: 9.99,
    emoji: "🍎",
    cat: "cables",
  },
  {
    id: 20,
    name: "Cargador Inalámbrico",
    sku: "CAB-004",
    price: 24.99,
    emoji: "🌀",
    cat: "cables",
  },
  // FOTO Y VIDEO
  {
    id: 21,
    name: "Trípode Flexible 45cm",
    sku: "FOT-001",
    price: 17.99,
    emoji: "📷",
    cat: "foto",
  },
  {
    id: 22,
    name: 'Ring Light LED 10"',
    sku: "FOT-002",
    price: 39.99,
    emoji: "💡",
    cat: "foto",
  },
  {
    id: 23,
    name: "Tarjeta SD 128GB V30",
    sku: "FOT-003",
    price: 22.99,
    emoji: "💿",
    cat: "foto",
  },
  {
    id: 24,
    name: "Micrófono Condensador",
    sku: "FOT-004",
    price: 54.99,
    emoji: "🎙️",
    cat: "foto",
  },
];

// ── Estado global ──────────────────────────────────────────────────────────────
let cart = {};
let selectedPayment = "efectivo";
let discountPct = 0;
let ticketNum = 1001;
let activeCat = "all";
let searchTerm = "";
let isAdmin = typeof IS_ADMIN_BACKEND !== 'undefined' ? IS_ADMIN_BACKEND : false;

// ── Formato monetario ──────────────────────────────────────────────────────────
function fmt(n) {
  return n.toFixed(2).replace(".", ",") + " €";
}

// ── Catálogo ───────────────────────────────────────────────────────────────────
function renderProducts() {
    const grid = document.getElementById("productsGrid");
    // ... resto de tu lógica de filtrado ...

    grid.innerHTML = filtered.map(p => `
        <div class="product-card${p.inactive ? " inactive" : ""}" id="card-${p.id}" onclick="handleCardClick(event, ${p.id}, this)">
            ${p.inactive ? '<div class="baja-pill">Baja</div>' : ""}
            <span class="product-emoji">${p.emoji}</span>
            <div>
                <div class="product-name">${p.name}</div>
                <div class="product-sku">${p.sku}</div>
            </div>
            <div class="product-price" style="margin-top:auto">${fmt(p.price)}</div>
            
            <div class="product-admin-bar">
                <button class="admin-action edit" onclick="editProduct(event,${p.id})">Editar</button>
                <button class="admin-action delete" onclick="deleteProduct(event,${p.id})">Borrar</button>
                <button class="admin-action baja" onclick="toggleBaja(event,${p.id})">${p.inactive ? "Alta" : "Baja"}</button>
            </div>
        </div>
    `).join("");

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
      <span class="order-item-emoji">${item.emoji}</span>
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

function processPayment() {
  const total = document.getElementById("totalAmt").textContent;
  document.getElementById("modalAmount").textContent = total;
  document.getElementById("modalSub").textContent =
    `Pago con ${selectedPayment} · Ticket #${ticketNum++}`;
  document.getElementById("modalOverlay").classList.add("visible");
}

function closeModal() {
  document.getElementById("modalOverlay").classList.remove("visible");
  clearCart();
}

// ── Modo administrador ─────────────────────────────────────────────────────────
function requireAdmin() {
  if (!isAdmin) {
    showToast("⚠️ Acceso restringido a administradores");
    return false;
  }
  return true;
}

function editProduct(e, id) {
  e.stopPropagation();
  if (!requireAdmin()) return;
  const p = PRODUCTS.find((x) => x.id === id);
  document.getElementById("editId").value = p.id;
  document.getElementById("editName").value = p.name;
  document.getElementById("editSku").value = p.sku;
  document.getElementById("editPrice").value = p.price;
  document.getElementById("editEmoji").value = p.emoji;
  document.getElementById("editModal").classList.add("visible");
}

function saveEdit() {
  const id = parseInt(document.getElementById("editId").value);
  const p = PRODUCTS.find((x) => x.id === id);
  p.name = document.getElementById("editName").value.trim() || p.name;
  p.sku = document.getElementById("editSku").value.trim() || p.sku;
  p.price = parseFloat(document.getElementById("editPrice").value) || p.price;
  p.emoji = document.getElementById("editEmoji").value.trim() || p.emoji;
  document.getElementById("editModal").classList.remove("visible");
  renderProducts();
  showToast("✅ Producto actualizado");
}

function deleteProduct(e, id) {
  e.stopPropagation();
  if (!requireAdmin()) return;
  const p = PRODUCTS.find((x) => x.id === id);
  document.getElementById("delName").textContent = p.name;
  document.getElementById("delConfirmBtn").onclick = () => {
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
    showToast("🗑️ Producto eliminado");
  };
  document.getElementById("deleteModal").classList.add("visible");
}

function toggleBaja(e, id) {
  e.stopPropagation();
  if (!requireAdmin()) return;
  const p = PRODUCTS.find((x) => x.id === id);
  p.inactive = !p.inactive;
  if (p.inactive && cart[id]) {
    delete cart[id];
    renderCart();
  }
  renderProducts();
  showToast(p.inactive ? "⏸️ Producto dado de baja" : "▶️ Producto reactivado");
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
