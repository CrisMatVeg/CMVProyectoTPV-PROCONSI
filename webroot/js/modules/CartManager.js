/**
 * CartManager.js
 * Handles cart state, pricing engine, and promotions.
 */

import { AppConfig, AppState } from './AppConfig.js';
import { Utils } from './Utils.js';

export const CartManager = {
  /**
   * Price Engine: Calculate dynamic price based on tariffs and dates
   */
  getEffectivePrice(product, socio = null) {
    const basePrice = parseFloat(product.price || 0);
    let finalPrice = basePrice;
    const appliedTariffs = [];
    const now = new Date();
    const currentTime =
      now.getHours().toString().padStart(2, "0") +
      ":" +
      now.getMinutes().toString().padStart(2, "0") +
      ":" +
      now.getSeconds().toString().padStart(2, "0");

    const activeTariffs = AppConfig.tarifas.filter((t) => {
      const currentDate = now.toISOString().split("T")[0];
      const currentDay = now.getDay();

      if (t.fecha_aplicacion && currentDate < t.fecha_aplicacion) return false;
      if (t.fecha_fin && currentDate > t.fecha_fin) return false;

      if (t.dias_semana) {
        const allowedDays = t.dias_semana.split(",").map(Number);
        if (!allowedDays.includes(currentDay)) return false;
      }

      if (t.scope === "categoria" && product.cat !== t.categoria) return false;
      if (t.scope === "productos") {
        try {
          const ids = JSON.parse(t.producto_ids) || [];
          if (!ids.includes(parseInt(product.id))) return false;
        } catch (e) {
          return false;
        }
      }

      if (t.hora_inicio && currentTime < t.hora_inicio) return false;
      if (t.hora_fin && currentTime > t.hora_fin) return false;

      // 3. Segmentación Cliente
      if (t.es_solo_socios && (!socio || parseInt(socio.es_socio) !== 1))
        return false;

      // 3.1. Cliente Específico (id_cliente o cliente_ids)
      let isSpecificMatch = false;
      if (t.id_cliente && socio && parseInt(socio.id) === parseInt(t.id_cliente)) {
        isSpecificMatch = true;
      }
      if (!isSpecificMatch && t.cliente_ids && socio) {
        try {
          const ids = JSON.parse(t.cliente_ids);
          if (Array.isArray(ids) && ids.includes(parseInt(socio.id))) {
            isSpecificMatch = true;
          }
        } catch (e) {}
      }

      // Si la tarifa especifica clientes y este no lo es, fuera.
      if ((t.id_cliente || t.cliente_ids) && !isSpecificMatch) return false;

      // 3.2. Roles / Segmentos (Solo si no es match específico)
      if (!isSpecificMatch) {
        const rolCliente = socio && socio.rol ? socio.rol.toLowerCase() : "general";

        // Comprobar roles_segmento (nuevo formato)
        if (t.roles_segmento) {
          const segmentos = t.roles_segmento
            .toLowerCase()
            .split(",")
            .map((s) => s.trim());
          if (!segmentos.includes(rolCliente)) return false;
        }
        // Comprobar tipo_cliente (legacy)
        else if (t.tipo_cliente && t.tipo_cliente !== "todos") {
          const tipoReq = t.tipo_cliente.toLowerCase();
          if (tipoReq === "mayorista") {
            if (rolCliente !== "mayorista" && (!socio || parseInt(socio.es_mayorista) !== 1)) return false;
          } else if (tipoReq !== rolCliente) {
            return false;
          }
        }
      }

      return true;
    });

    activeTariffs.sort((a, b) => b.prioridad - a.prioridad);

    // Solo aplicar la tarifa de mayor prioridad
    activeTariffs.slice(0, 1).forEach((t) => {
      const val = parseFloat(t.valor);
      const keepPrecision = !!parseInt(product.mantener_precision || 0);
      let variation = 0;
      if (t.tipo === "percent") {
        variation = keepPrecision
          ? finalPrice * (val / 100)
          : Math.round(finalPrice * (val / 100) * 100) / 100;
      } else {
        variation = val;
      }

      if (variation !== 0) {
        finalPrice += variation;
        appliedTariffs.push({
          id_origen: t.id,
          tipo_descuento: "tarifa",
          nombre: t.nombre,
          valor_descontado: variation,
        });
      }
    });

    return {
      price: Math.max(0, finalPrice),
      appliedTariffs,
      basePrice,
    };
  },

  /**
   * Recalculate all items in cart based on current socio
   */
  recalculateCartPrices() {
    const cart = AppState.cart;
    let changed = false;
    Object.keys(cart).forEach((key) => {
      const item = cart[key];
      // Skip custom products (comodines)
      if (String(key).startsWith("comodin_") || item.id === -1) return;
      // Skip items already validated by the server API in the active checkout session
      if (item._tariffApplied) return;

      const product = AppConfig.products.find((p) => String(p.id) === String(item.id));
      if (!product) return;

      const effective = this.getEffectivePrice(product, AppState.socioActual);
      if (item.price !== effective.price) {
        item.price = effective.price;
        item.appliedTariffs = effective.appliedTariffs;
        item.basePriceSnapshot = effective.basePrice;
        changed = true;
      }
    });
    if (changed) {
      AppState.cart = cart;
    }
  },

  /**
   * Core Cart Actions
   */
  addToCart(id) {
    const product = AppConfig.products.find((p) => String(p.id) === String(id));
    if (!product || product.inactive || product.stock <= 0) return false;

    const cart = AppState.cart; // Read ONCE into a local variable
    if (cart[id]) {
      if (cart[id].qty >= product.stock) return "stock_limit";
      cart[id].qty++;
    } else {
      const basePrice = parseFloat(product.price || 0);
      cart[id] = {
        ...product,
        qty: 1,
        price: basePrice,
        appliedTariffs: [],
        basePriceSnapshot: basePrice,
        iva: parseFloat(product.iva || 21),
        cartKey: String(id),
        serials: [],
      };
    }
    AppState.cart = cart; // Write back via setter to persist
    return true;
  },

  addCustomProduct(name, price, iva) {
    const cart = AppState.cart;
    const timestamp = new Date().getTime();
    const cartKey = `comodin_${timestamp}`;

    cart[cartKey] = {
      id: -1,
      name: name,
      codigo: "COMODIN",
      price: parseFloat(price),
      iva: parseFloat(iva || 21),
      qty: 1,
      icono: '<i class="fa-solid fa-box-open"></i>',
      maxStock: 999999,
      cartKey: cartKey,
      serials: []
    };

    AppState.cart = cart;
    return true;
  },

  removeFromCart(id) {
    const cart = AppState.cart;
    if (cart[id]) {
      delete cart[id];
      AppState.cart = cart;
      return true;
    }
    return false;
  },

  changeQty(id, delta) {
    const cart = AppState.cart; // Read ONCE
    if (!cart[id]) return;
    const item = cart[id];
    const product = AppConfig.products.find(p => String(p.id) === String(item.id));
    
    if (delta > 0 && item.qty >= product.stock) return "stock_limit";
    
    item.qty += delta;
    if (item.qty <= 0) {
      delete cart[id];
    }
    AppState.cart = cart; // Write back
  },

  setQty(id, value) {
    const cart = AppState.cart; // Read ONCE
    if (!cart[id]) return;
    const item = cart[id];
    const product = AppConfig.products.find(p => String(p.id) === String(item.id));
    
    let newQty = parseInt(value);
    if (isNaN(newQty) || newQty < 0) newQty = 1;

    if (newQty === 0) {
      delete cart[id];
    } else if (newQty > product.stock) {
      item.qty = product.stock;
      AppState.cart = cart; // Write back
      return "stock_limit";
    } else {
      item.qty = newQty;
    }
    AppState.cart = cart; // Write back
    return true;
  },

  /**
   * Bundle & Promo Logic
   */
  applyDiscountCode(code) {
    if (!code) {
      AppState.currentPromo = null;
      return { ok: false, error: "Código vacío" };
    }
    const cleanCode = code.trim().toUpperCase();
    const promo = AppConfig.promos.find(p => p.codigo && p.codigo.toUpperCase() === cleanCode);
    
    if (promo) {
      const totals = this.calculateTotals();
      const minSub = parseFloat(promo.min_subtotal) || 0;
      
      if (totals.subtotal < minSub) {
        return { ok: false, error: `Pedido mínimo: ${Utils.formatCurrency(minSub)}` };
      }
      
      AppState.currentPromo = { ...promo, _manual: true };
      return { ok: true, promo };
    }
    
    return { ok: false, error: "Código no válido" };
  },

  autoApplyBundles() {
    if (AppState.currentPromo && AppState.currentPromo.codigo) return;

    const items = Object.values(AppState.cart);
    if (!items.length) {
      if (AppState.currentPromo && !AppState.currentPromo._manual) AppState.currentPromo = null;
      return;
    }

    const qtyByBaseId = {};
    const catByBaseId = {};
    items.forEach(item => {
      qtyByBaseId[item.id] = (qtyByBaseId[item.id] || 0) + item.qty;
      catByBaseId[item.id] = item.cat;
    });

    const autoPromo = AppConfig.promos.find(p => {
      if (p.tipo !== "bundle" && p.tipo !== "fixed_bundle") return false;
      if (p.codigo) return false;
      
      const buyQty = parseInt(p.bundle_buy_qty) || 0;
      return Object.entries(qtyByBaseId).some(([id, qty]) => {
        if (qty < buyQty) return false;
        if (!p.id_producto && !p.categoria_code) return true;
        if (p.id_producto && p.id_producto == id) return true;
        if (p.categoria_code && p.categoria_code.split(',').includes(catByBaseId[id])) return true;
        return false;
      });
    });

    if (autoPromo) {
      if (!AppState.currentPromo || AppState.currentPromo.id !== autoPromo.id) {
        AppState.currentPromo = { ...autoPromo, _manual: false };
      }
    } else {
       if (AppState.currentPromo && !AppState.currentPromo._manual) AppState.currentPromo = null;
    }
  },

  calculateTotals() {
    this.autoApplyBundles();
    const items = Object.values(AppState.cart);
    let subtotal = 0;
    let bundleDiscountTotal = 0;

    // 1. Calculate base subtotal (pre-promo)
    items.forEach((item) => {
      let p = parseFloat(item.price || 0);
      const q = parseInt(item.qty || 0);
      subtotal += p * q;
    });

    // 2. Apply bundle discounts (2x1, fixed Price bundle, etc.)
    if (AppState.currentPromo && (AppState.currentPromo.tipo === "bundle" || AppState.currentPromo.tipo === "fixed_bundle")) {
      const groups = {};
      items.forEach((item) => {
        const key = item.id;
        const p = parseFloat(item.price || 0);
        const q = parseInt(item.qty || 0);
        if (!groups[key]) groups[key] = { baseId: item.id, cat: item.cat, units: [] };
        for (let i = 0; i < q; i++) groups[key].units.push(p);
      });

      Object.values(groups).forEach((group) => {
        let promoApplies = false;
        if (AppState.currentPromo.id_producto && AppState.currentPromo.id_producto == group.baseId) promoApplies = true;
        else if (AppState.currentPromo.categoria_code) {
          const allowedCats = AppState.currentPromo.categoria_code.split(',');
          if (allowedCats.includes(group.cat)) promoApplies = true;
        } else if (!AppState.currentPromo.id_producto && !AppState.currentPromo.categoria_code) {
          promoApplies = true;
        }

        if (!promoApplies) return;

        const buyQty = parseInt(AppState.currentPromo.bundle_buy_qty) || 0;
        const payQty = parseInt(AppState.currentPromo.bundle_pay_qty) || 0;
        const totalQty = group.units.length;

        if (AppState.currentPromo.tipo === "bundle" && buyQty > 0 && payQty > 0) {
          const sets = Math.floor(totalQty / buyQty);
          const freeUnits = sets * (buyQty - payQty);
          const sorted = [...group.units].sort((a, b) => a - b);
          bundleDiscountTotal += sorted.slice(0, freeUnits).reduce((s, p) => s + p, 0);
        } else if (AppState.currentPromo.tipo === "fixed_bundle" && buyQty > 0) {
          const sets = Math.floor(totalQty / buyQty);
          const sumPrices = group.units.reduce((s, p) => s + p, 0);
          const avgPrice = sumPrices / totalQty;
          const normalPrice = sets * buyQty * avgPrice;
          const bundlePrice = sets * parseFloat(AppState.currentPromo.valor || 0);
          if (normalPrice > bundlePrice) bundleDiscountTotal += normalPrice - bundlePrice;
        }
      });
    }

    // 3. Apply general discounts (percent or fixed amount)
    const subtotalAfterBundles = subtotal - bundleDiscountTotal;
    let generalDiscount = 0;

    if (AppState.currentPromo && (AppState.currentPromo.tipo === "percent" || AppState.currentPromo.tipo === "amount")) {
      const val = parseFloat(AppState.currentPromo.valor || 0);
      if (AppState.currentPromo.tipo === "percent") {
        generalDiscount = (subtotalAfterBundles * val) / 100;
      } else {
        generalDiscount = Math.min(subtotalAfterBundles, val);
      }
    }

    // 4. Partner (Socio) Discount (accumulative 5% over remainder)
    const socioAmt = (AppState.socioActual && AppState.socioActual.es_socio) 
      ? (subtotalAfterBundles - generalDiscount) * (AppConfig.socioDiscount / 100) 
      : 0;

    // IMPORTANT: Loyalty points are treated as a direct "discount" on the price per final requirements.
    // This reduces the 'totals.total' and consequently the taxable base and VAT.
    const totalDiscount = bundleDiscountTotal + generalDiscount + socioAmt + (AppState.puntosDescuentoAmt || 0);
    const subtotalFinal = Math.max(0, subtotal - totalDiscount);

    // 5. Derive Base and Tax from PVP (which already contains VAT)
    // Según requerimientos del usuario: los descuentos/promos NO afectan a la base imponible ni al IVA.
    // Se calculan sobre el subtotal bruto (post-tarifa, pre-promo).
    const subtotalTaxable = subtotal; 
    const discountFactor = 1.0; 
    let totalBase = 0;
    let totalTax = 0;
    const breakdown = {};
 
    items.forEach((item) => {
      const p = parseFloat(item.price || 0);
      const q = parseInt(item.qty || 0);

      const itemPvpFinal = p * q;
      
      const rate = (item.iva !== undefined && item.iva !== null) ? parseFloat(item.iva) : 21;
      const base = itemPvpFinal / (1 + rate / 100);
      const tax = itemPvpFinal - base;
 
      if (!breakdown[rate]) breakdown[rate] = { base: 0, tax: 0 };
      breakdown[rate].base += base;
      breakdown[rate].tax += tax;
 
      totalBase += base;
      totalTax += tax;
    });

    return {
      subtotal,          
      bundleDiscount: bundleDiscountTotal,
      generalDiscount: generalDiscount,
      socioDiscount: socioAmt,
      puntosDiscount: (AppState.puntosDescuentoAmt || 0),
      totalDiscount,      
      subtotalFinal,     
      totalBase,         
      totalTax,          
      total: subtotalFinal,
      breakdown
    };
  },

  postponeSale() {
    const saved = JSON.parse(localStorage.getItem("postponed-sales") || "[]");
    if (saved.length > 0) {
        Utils.showToast("Ya existe una venta aparcada", "warning");
        return;
    }

    const items = Object.values(AppState.cart);
    if (!items.length) {
        Utils.showToast("El carrito está vacío", "warning");
        return;
    }

    saved.push({
        id: Date.now(),
        date: new Date().toISOString(),
        items: items,
        socio: AppState.socioActual
    });
    localStorage.setItem("postponed-sales", JSON.stringify(saved));
    AppState.cart = {};
    AppState.socioActual = null;
    AppState.saveCart();
    Utils.showToast("Venta pospuesta", "success");
  },

  resumeSale(id) {
    const saved = JSON.parse(localStorage.getItem("postponed-sales") || "[]");
    if (!saved.length) return;

    if (Object.keys(AppState.cart).length > 0) {
        Utils.showToast("Vacía el carrito primero para recuperar la venta", "warning");
        return;
    }

    let index = -1;
    if (id === undefined) {
        index = saved.length - 1; // Resume last one
    } else {
        index = saved.findIndex(s => s.id === id);
    }
    
    if (index === -1) return;

    const sale = saved.splice(index, 1)[0];
    localStorage.setItem("postponed-sales", JSON.stringify(saved));
    
    // Merge or replace? For consistency, replace current cart
    AppState.cart = {};
    sale.items.forEach(item => {
        AppState.cart[item.id] = item;
    });
    AppState.socioActual = sale.socio;
    AppState.saveCart();
    Utils.showToast("Venta recuperada", "success");
  }
};

// Expose to global scope for legacy main.js
if (typeof window !== "undefined") {
    window.CartManager = CartManager;
}

