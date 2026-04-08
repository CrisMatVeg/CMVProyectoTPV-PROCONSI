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
    let finalPrice = parseFloat(product.price);
    const now = new Date();
    const currentTime = now.getHours().toString().padStart(2, "0") + ":" +
                        now.getMinutes().toString().padStart(2, "0") + ":" +
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
        } catch (e) { return false; }
      }

      if (t.hora_inicio && currentTime < t.hora_inicio) return false;
      if (t.hora_fin && currentTime > t.hora_fin) return false;

      if (t.es_solo_socios && (!socio || parseInt(socio.es_socio) !== 1)) return false;
      if (t.id_cliente && (!socio || parseInt(socio.id) !== parseInt(t.id_cliente))) return false;
      
      if (t.tipo_cliente !== "todos") {
        if (!socio || (t.tipo_cliente === "mayorista" && parseInt(socio.es_mayorista) !== 1) || (t.tipo_cliente !== "mayorista" && socio.tipo !== t.tipo_cliente)) {
            return false;
        }
      }

      return true;
    });

    activeTariffs.sort((a, b) => b.prioridad - a.prioridad);

    activeTariffs.forEach((t) => {
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
  },

  /**
   * Core Cart Actions
   */
  addToCart(id) {
    const product = AppConfig.products.find(p => p.id === id);
    if (!product || product.inactive || product.stock <= 0) return false;

    if (AppState.cart[id]) {
      if (AppState.cart[id].qty >= product.stock) return "stock_limit";
      AppState.cart[id].qty++;
    } else {
      const finalPrice = this.getEffectivePrice(product, AppState.socioActual);
      AppState.cart[id] = {
        ...product,
        qty: 1,
        price: finalPrice,
        iva: parseFloat(product.iva || 21),
        cartKey: String(id),
        serials: []
      };
    }
    AppState.saveCart();
    return true;
  },

  changeQty(id, delta) {
    if (!AppState.cart[id]) return;
    const item = AppState.cart[id];
    const product = AppConfig.products.find(p => p.id === item.id);
    
    if (delta > 0 && item.qty >= product.stock) return "stock_limit";
    
    item.qty += delta;
    if (item.qty <= 0) {
      delete AppState.cart[id];
    }
    AppState.saveCart();
  },

  setQty(id, value) {
    if (!AppState.cart[id]) return;
    const item = AppState.cart[id];
    const product = AppConfig.products.find(p => p.id === item.id);
    
    let newQty = parseInt(value);
    if (isNaN(newQty) || newQty < 0) newQty = 1;

    if (newQty === 0) {
      delete AppState.cart[id];
    } else if (newQty > product.stock) {
      item.qty = product.stock;
      AppState.saveCart();
      return "stock_limit";
    } else {
      item.qty = newQty;
    }
    AppState.saveCart();
    return true;
  },

  /**
   * Bundle & Promo Logic
   */
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

    // 1. Calculate base subtotal
    items.forEach((item) => {
      const p = parseFloat(item.price || 0);
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

    const totalDiscount = bundleDiscountTotal + generalDiscount + socioAmt + (AppState.puntosDescuentoAmt || 0);
    const subtotalFinal = Math.max(0, subtotal - totalDiscount);

    // 5. Derive Base and Tax from PVP (which already contains VAT)
    const discountFactor = subtotal > 0 ? subtotalFinal / subtotal : 1;
    let totalBase = 0;
    let totalTax = 0;
    const breakdown = {};
 
    items.forEach((item) => {
      const p = parseFloat(item.price || 0);
      const q = parseInt(item.qty || 0);
      const itemPvpOrig = p * q;
      const itemPvpFinal = itemPvpOrig * discountFactor;
      
      const rate = (item.iva !== undefined && item.iva !== null) ? parseFloat(item.iva) : 25;
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
      totalDiscount,      
      subtotalFinal,     
      totalBase,         
      totalTax,          
      total: subtotalFinal,
      breakdown
    };
  },

  postponeSale() {
    const items = Object.values(AppState.cart);
    if (!items.length) {
        Utils.showToast("El carrito está vacío", "warning");
        return;
    }
    const saved = JSON.parse(localStorage.getItem("postponed-sales") || "[]");
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
    const index = saved.findIndex(s => s.id === id);
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
