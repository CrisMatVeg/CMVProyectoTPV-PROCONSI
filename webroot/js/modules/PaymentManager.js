/**
 * PaymentManager.js
 * Handles checkout process, payment methods, and client/socio selection.
 */

import { AppState } from './AppConfig.js';
import { ApiService } from './ApiService.js';
import { Utils } from './Utils.js';
import { CartManager } from './CartManager.js';
import { UiController } from './UiController.js';
import { TicketManager } from './TicketManager.js';

export const PaymentManager = {
  // Constants
  SOCIO_DISCOUNT: 5,

  init() {
    this.setupEventListeners();
  },

  setupEventListeners() {
    // Search input for clients
    const clientSearchInput = document.getElementById("clienteSearch");
    if (clientSearchInput) {
      clientSearchInput.addEventListener("keypress", (e) => {
        if (e.key === "Enter") this.buscarClienteGuardado();
      });
    }

    const socioSearchInput = document.getElementById("socioSearch");
    if (socioSearchInput) {
      socioSearchInput.addEventListener("keypress", (e) => {
        if (e.key === "Enter") this.buscarSocio();
      });
    }
  },

  /**
   * Inicializa el proceso de pago
   */
  async processPayment() {
    // Resetear estado de comprobación de efectivo
    window.cajaEfectivoActual = null;
    try {
      const resp = await ApiService.getCajaEstadoActual();
      if (resp.ok) {
        window.cajaEfectivoActual = resp.efectivoActual;
      }
    } catch (e) {
      console.error("Error al obtener estado de caja:", e);
    }

    AppState.tipoClienteActual = "particular";
    document.getElementById("empresaDatos").classList.add("d-none");
    document.getElementById("empresaNombre").value = "";
    document.getElementById("empresaNif").value = "";
    document.getElementById("efectivoRecibido").value = "";

    const aCuentaPagado = document.getElementById("aCuentaPagado");
    const aCuentaFecha = document.getElementById("aCuentaFechaLimite");
    if (aCuentaPagado) aCuentaPagado.value = "0.00";
    if (aCuentaFecha) {
      const nextMonth = new Date();
      nextMonth.setMonth(nextMonth.getMonth() + 1);
      aCuentaFecha.value = nextMonth.toISOString().split("T")[0];
    }

    const cambioEl = document.getElementById("efectivoCambio");
    if (cambioEl) {
      cambioEl.textContent = "0,00 €";
      cambioEl.classList.remove("text-red");
      cambioEl.classList.add("text-accent");
    }

    const efectivoGestion = document.getElementById("efectivoGestion");
    if (AppState.selectedPayment === "efectivo") {
      efectivoGestion.classList.remove("d-none");
      setTimeout(() => document.getElementById("efectivoRecibido").focus(), 100);
    } else {
      efectivoGestion.classList.add("d-none");
    }

    this.updateTypeButtons();

    AppState.socioActual = null;
    document.getElementById("socioBusqueda").classList.add("d-none");
    document.getElementById("socioRegistro").classList.add("d-none");
    document.getElementById("socioInfo").innerText = "";
    AppState.clienteSeleccionado = null;

    const gen = document.getElementById("clienteBusquedaGenerica");
    if (gen) {
      gen.classList.add("d-none");
      const res = document.getElementById("clienteResultados");
      if (res) res.innerHTML = "";
      const btnAdd = document.getElementById("btnAddCliente");
      if (btnAdd) btnAdd.classList.add("d-none");
    }
    const reg = document.getElementById("clienteRegistro");
    if (reg) reg.classList.add("d-none");

    document.querySelectorAll(".form-error").forEach((el) => (el.innerText = ""));
    
    this.quitarValeAplicado();
    const valeContainer = document.getElementById("clienteValesContainer");
    if (valeContainer) valeContainer.classList.add("d-none");

    this.quitarPuntosCanjeados();
    const puntosContainer = document.getElementById("clientePuntosContainer");
    if (puntosContainer) puntosContainer.classList.add("d-none");

    document.getElementById("clienteModal").classList.add("visible");
  },

  updateTypeButtons() {
    const btnP = document.getElementById("btnParticular");
    const btnE = document.getElementById("btnEmpresa");
    const btnS = document.getElementById("btnSocio");
    if (btnP) btnP.classList.toggle("selected-type", AppState.tipoClienteActual === "particular");
    if (btnE) btnE.classList.toggle("selected-type", AppState.tipoClienteActual === "empresa");
    if (btnS) btnS.classList.toggle("selected-type", AppState.tipoClienteActual === "socio");
  },

  seleccionarTipoCliente(tipo) {
    AppState.tipoClienteActual = tipo;
    this.updateTypeButtons();

    document.getElementById("empresaDatos").classList.add("d-none");
    document.getElementById("socioBusqueda").classList.add("d-none");
    document.getElementById("socioRegistro").classList.add("d-none");

    const gen = document.getElementById("clienteBusquedaGenerica");
    if (gen) gen.classList.add("d-none");
    const reg = document.getElementById("clienteRegistro");
    if (reg) reg.classList.add("d-none");
    const btnAdd = document.getElementById("btnAddCliente");
    if (btnAdd) btnAdd.classList.add("d-none");
    
    AppState.clienteSeleccionado = null;

    if (tipo === "particular") {
      AppState.socioActual = null;
      if (gen) gen.classList.remove("d-none");
    } else if (tipo === "socio") {
      document.getElementById("socioBusqueda").classList.remove("d-none");
      setTimeout(() => document.getElementById("socioSearch").focus(), 100);
    } else {
      document.getElementById("empresaDatos").classList.remove("d-none");
      setTimeout(() => document.getElementById("empresaNombre").focus(), 100);
      AppState.socioActual = null;
      if (gen) gen.classList.remove("d-none");
    }

    const totals = CartManager.calculateTotals();
    UiController.renderCart(totals);
  },

  async buscarSocio() {
    const term = document.getElementById("socioSearch").value.trim();
    if (!term) return;

    const info = document.getElementById("socioInfo");
    const btnAdd = document.getElementById("btnAddSocio");
    info.innerText = "Buscando...";
    btnAdd.classList.add("d-none");

    try {
      const r = await ApiService.request("api/gestionCliente.php", {
        method: "POST",
        body: JSON.stringify({ accion: "buscar", nif: term })
      });
      if (r.ok) {
        AppState.socioActual = r.cliente;
        info.innerHTML = `<i class="fa-solid fa-check-circle text-green"></i> ${r.cliente.nombre} (${r.cliente.nif})`;
        Utils.showToast("Socio identificado");
        this.cargarValesCliente(r.cliente.id);
        this.mostrarPuntosCliente(r.cliente);
        const totals = CartManager.calculateTotals();
        UiController.renderCart(totals);
      } else {
        info.innerHTML = `<span class="text-red">Socio no encontrado.</span>`;
        btnAdd.classList.remove("d-none");
        AppState.socioActual = null;
      }
    } catch (e) {
      console.error(e);
      info.innerText = "Error en la búsqueda.";
    }
  },

  async cargarValesCliente(idCliente) {
    const container = document.getElementById("clienteValesContainer");
    const listado = document.getElementById("listadoValesCliente");
    if (!container || !listado) return;

    this.quitarValeAplicado();
    listado.innerHTML = '<div class="fs-11 opacity-70">Buscando vales...</div>';
    container.classList.remove("d-none");

    try {
      const r = await ApiService.request("api/listarValesCliente.php", {
        method: "POST",
        body: JSON.stringify({ id_cliente: idCliente })
      });

      if (r.ok && r.vales && r.vales.length > 0) {
        listado.innerHTML = r.vales.map(v => `
          <div class="d-flex jc-space-between ai-center p-8 bg-surface1 br-6 border-2">
            <div class="d-flex flex-column">
              <span class="fs-12 font-bold font-mono">${v.codigo}</span>
              <span class="fs-11 text-accent">${Utils.formatCurrency(parseFloat(v.importe_restante))}</span>
            </div>
            <button type="button" onclick='app.aplicarVale(${JSON.stringify(v)})' class="btn-save fs-10 p-2-8 w-auto">Aplicar</button>
          </div>
        `).join("");
      } else {
        listado.innerHTML = '<div class="fs-11 opacity-50 p-4">No hay vales activos para este cliente.</div>';
      }
    } catch (e) {
      console.error("Error cargando vales:", e);
      listado.innerHTML = '<div class="fs-11 text-red">Error al cargar vales.</div>';
    }
  },

  aplicarVale(vale) {
    AppState.valeAplicado = vale;
    const resumen = document.getElementById("valeAplicadoResumen");
    const totalLabel = document.getElementById("valeAplicadoTotal");
    const pendienteLabel = document.getElementById("valePendienteCobro");
    const listado = document.getElementById("listadoValesCliente");
    
    const totals = CartManager.calculateTotals();
    const totalTPV = totals.total;

    if (resumen && totalLabel) {
      resumen.classList.remove("d-none");
      totalLabel.textContent = Utils.formatCurrency(parseFloat(vale.importe_restante));
      if (pendienteLabel) {
        const rest = Math.max(0, totalTPV - parseFloat(vale.importe_restante));
        pendienteLabel.textContent = Utils.formatCurrency(rest);
      }
    }
    if (listado) listado.classList.add("d-none");
    
    Utils.showToast(`<i class="fa-solid fa-ticket"></i> Vale ${vale.codigo} aplicado`);
  },

  quitarValeAplicado() {
    AppState.valeAplicado = null;
    const resumen = document.getElementById("valeAplicadoResumen");
    const listado = document.getElementById("listadoValesCliente");
    
    if (resumen) resumen.classList.add("d-none");
    if (listado) listado.classList.remove("d-none");
  },

  async ejecutarCobroFinal() {
    const btn = document.getElementById("confirmarClienteBtn");
    btn.disabled = true;
    btn.textContent = "Guardando…";

    const totals = CartManager.calculateTotals();
    const items = Object.values(AppState.cart);

    const clienteId = AppState.tipoClienteActual === "socio" 
        ? (AppState.socioActual ? AppState.socioActual.id : null)
        : (AppState.clienteSeleccionado ? AppState.clienteSeleccionado.id : null);

    const facturaActiva = document.getElementById("facturaToggle")?.checked;
    
    // Validations
    if (facturaActiva && AppState.tipoClienteActual === "particular" && !clienteId) {
        Utils.showToast("⚠ Factura obligatoria. Selecciona o registra un cliente.", "warning");
        btn.disabled = false;
        btn.textContent = "Cobrar";
        return;
    }

    if (AppState.tipoClienteActual === "empresa") {
        const empNif = this.getClienteNif();
        if (!validarDocumento(empNif)) {
            Utils.showToast("⚠ El CIF/NIF de la empresa no tiene un formato válido.", "warning");
            btn.disabled = false;
            btn.textContent = "Cobrar";
            return;
        }
    }

    if (AppState.selectedPayment === "a_cuenta") {
        const aCFecha = document.getElementById("aCuentaFechaLimite").value;
        if (!validarFechas(new Date().toISOString().split("T")[0], aCFecha)) {
            Utils.showToast("⚠ La fecha límite para pago a cuenta debe ser igual o posterior a hoy.", "warning");
            btn.disabled = false;
            btn.textContent = "Cobrar";
            return;
        }
    }

    const payload = {
        tipoCliente: AppState.tipoClienteActual,
        nombreCliente: this.getClienteNombre(),
        nifCliente: this.getClienteNif(),
        idCliente: clienteId,
        esFactura: facturaActiva ? 1 : 0,
        metodoPago: AppState.selectedPayment,
        subtotal: totals.subtotal,
        total: totals.total,
        descuentoAmt: totals.totalDiscount,
        descuentoLabel: this.getDescuentoLabel(),
        lineas: items.map(it => ({
            id: it.id,
            name: it.name,
            codigo: it.codigo,
            price: it.price,
            qty: it.qty,
            serials: it.serials || []
        })),
        efectivo: {
            recibido: parseFloat(document.getElementById("efectivoRecibido").value) || 0
        },
        pagadoACuenta: AppState.selectedPayment === "a_cuenta" ? parseFloat(document.getElementById("aCuentaPagado").value) || 0 : 0,
        fechaLimitePago: AppState.selectedPayment === "a_cuenta" ? document.getElementById("aCuentaFechaLimite").value : null,
        idVale: AppState.valeAplicado ? AppState.valeAplicado.id : null,
        codigoVale: AppState.valeAplicado ? AppState.valeAplicado.codigo : null,
        importeVale: AppState.valeAplicado ? AppState.valeAplicado.importe_restante : 0,
        puntosGanados: Math.floor(totals.total),
        puntosCanjeados: AppState.puntosCanjeados || 0,
        puntosDescuentoAmt: AppState.puntosDescuentoAmt || 0
    };


    try {
        const data = await ApiService.request("./api/guardarVenta.php", {
            method: "POST",
            body: JSON.stringify(payload)
        });
        if (data.ok) {
            Utils.showToast("Venta guardada con éxito", "success");
            AppState.cart = {};
            AppState.saveCart();
            TicketManager.showTicket(data.venta);
            document.getElementById("clienteModal").classList.remove("visible");
        } else {
            Utils.showToast("Error: " + data.error, "error");
        }
    } catch (e) {
        console.error(e);
        Utils.showToast("Error de conexión", "error");
    } finally {
        btn.disabled = false;
        btn.textContent = "Cobrar";
    }
  },

  getClienteNombre() {
    if (AppState.tipoClienteActual === "empresa") return document.getElementById("empresaNombre").value;
    if (AppState.socioActual) return AppState.socioActual.nombre;
    if (AppState.clienteSeleccionado) return AppState.clienteSeleccionado.nombre;
    return null;
  },

  getClienteNif() {
    if (AppState.tipoClienteActual === "empresa") return document.getElementById("empresaNif").value;
    if (AppState.socioActual) return AppState.socioActual.nif;
    if (AppState.clienteSeleccionado) return AppState.clienteSeleccionado.nif;
    return null;
  },

  getDescuentoLabel() {
    let label = "";
    // Simplified label logic for now
    if (AppState.socioActual && AppState.socioActual.es_socio) label += "Socio 5%";
    return label;
  },

  mostrarRegistroSocio() {
    document.getElementById("socioBusqueda").classList.add("d-none");
    document.getElementById("socioRegistro").classList.remove("d-none");
    const searchNif = document.getElementById("socioSearch").value.trim();
    if (searchNif) document.getElementById("newSocioNif").value = searchNif;
  },

  cancelarRegistroSocio() {
    document.getElementById("socioRegistro").classList.add("d-none");
    document.getElementById("socioBusqueda").classList.remove("d-none");
  },

  async guardarNuevoSocio() {
    const nombre = document.getElementById("newSocioNombre").value.trim();
    const nif = document.getElementById("newSocioNif").value.trim();

    if (!nombre || !nif) {
      Utils.showToast("Nombre y NIF son obligatorios", "error");
      return;
    }

    if (!validarDocumento(nif)) {
      Utils.showToast("El DNI/NIE de socio no es válido", "warning");
      return;
    }

    try {
      const r = await ApiService.request("api/gestionCliente.php", {
        method: "POST",
        body: JSON.stringify({ accion: "registrar", nombre, nif, es_socio: 1 })
      });
      if (r.ok) {
        Utils.showToast("Socio registrado y seleccionado", "success");
        AppState.socioActual = { id: r.id, nombre, nif, es_socio: true };
        this.cancelarRegistroSocio();
        document.getElementById("socioInfo").innerHTML = `<i class="fa-solid fa-check-circle text-green"></i> ${nombre} (NUEVO)`;
        this.cargarValesCliente(r.id);
        
        const totals = CartManager.calculateTotals();
        UiController.renderCart(totals);
      } else {
        Utils.showToast("Error: " + r.error, "error");
      }
    } catch (e) {
      console.error(e);
      Utils.showToast("Error de conexión", "error");
    }
  },

  async buscarClienteGuardado() {
    const term = document.getElementById("clienteSearch")?.value.trim() || "";
    if (!term) return;

    const resEl = document.getElementById("clienteResultados");
    if (resEl) resEl.innerText = "Buscando...";
    this.ultimosClientesBuscados = [];

    let tipo = AppState.tipoClienteActual;
    if (tipo !== "particular" && tipo !== "empresa") {
      if (resEl) resEl.innerText = "Selecciona Particular o Empresa.";
      return;
    }

    try {
      const r = await ApiService.request("api/gestionCliente.php", {
        method: "POST",
        body: JSON.stringify({ accion: "buscarTexto", term, tipo })
      });
      if (!r.ok) {
          if (resEl) resEl.innerText = r.error || "Error al buscar.";
          return;
      }
      this.ultimosClientesBuscados = r.lista || [];
      if (!this.ultimosClientesBuscados.length) {
        if (resEl) resEl.innerText = "Sin resultados.";
        document.getElementById("btnAddCliente")?.classList.remove("d-none");
        return;
      }
      document.getElementById("btnAddCliente")?.classList.add("d-none");
      if (resEl) {
        resEl.innerHTML = this.ultimosClientesBuscados.map((c, idx) => `
          <button type="button" class="cat-tab p-4-8 fs-11 mb-4" onclick="app.seleccionarClienteGuardado(${idx})">
            <i class="fa-solid ${c.tipo === "empresa" ? "fa-building" : "fa-user"}"></i>
            ${c.nombre} ${c.apellidos || ""} (${c.nif || ""})
          </button>
        `).join("");
      }
    } catch (e) {
      console.error(e);
      if (resEl) resEl.innerText = "Error de conexión.";
    }
  },

  seleccionarClienteGuardado(idx) {
    const c = this.ultimosClientesBuscados[idx];
    if (!c) return;
    AppState.clienteSeleccionado = c;

    if (AppState.tipoClienteActual === "empresa") {
        document.getElementById("empresaNombre").value = `${c.nombre} ${c.apellidos || ""}`.trim();
        document.getElementById("empresaNif").value = c.nif || "";
    }

    const resEl = document.getElementById("clienteResultados");
    if (resEl) {
        resEl.innerHTML = `<i class="fa-solid fa-check-circle text-success"></i> ${c.nombre} (${c.nif || ""})`;
        this.cargarValesCliente(c.id);
    }

    Utils.showToast("Cliente seleccionado");
    this.mostrarPuntosCliente(c);
    const totals = CartManager.calculateTotals();
    UiController.renderCart(totals);
  },

  mostrarPuntosCliente(cliente) {
    const container = document.getElementById("clientePuntosContainer");
    const label = document.getElementById("labelPuntosDisponibles");
    const btnCanje = document.getElementById("btnCanjearPuntos");
    const msgCanje = document.getElementById("puntosCanjeMsg");
    const controles = document.getElementById("controlesCanjePuntos");
    const select = document.getElementById("puntosAcanjearSelect");

    if (!container) return;
    
    // Check expiration locally (approximate)
    if (cliente.ultima_compra) {
        const last = new Date(cliente.ultima_compra);
        const now = new Date();
        if (now.getTime() - last.getTime() > 365 * 24 * 60 * 60 * 1000) {
            cliente.puntos = 0;
        }
    }

    AppState.clienteActualPuntos = parseInt(cliente.puntos || 0);
    container.classList.remove("d-none");
    label.textContent = AppState.clienteActualPuntos;

    if (AppState.clienteActualPuntos >= 50) {
        if (controles) controles.classList.remove("d-none");
        if (msgCanje) msgCanje.classList.add("d-none");
        
        // Fill select with multiples of 50
        if (select) {
            let html = "";
            const maxBlocks = Math.floor(AppState.clienteActualPuntos / 50);
            for (let i = 1; i <= maxBlocks; i++) {
                const pts = i * 50;
                const dto = (pts / 50) * 2.5;
                html += `<option value="${pts}">${pts} pts (-${dto.toFixed(2)}€)</option>`;
            }
            select.innerHTML = html;
        }
        this.updatePuntosDiscountPreview();
    } else {
        if (controles) controles.classList.add("d-none");
        if (msgCanje) {
            msgCanje.classList.remove("d-none");
            msgCanje.textContent = `Te faltan ${50 - AppState.clienteActualPuntos} puntos para 2,50€`;
        }
    }
    this.quitarPuntosCanjeados();
  },

  updatePuntosDiscountPreview() {
    // This could optionally update a preview label in the UI if needed
  },

  canjearPuntos() {
    const select = document.getElementById("puntosAcanjearSelect");
    if (!select) return;

    const pts = parseInt(select.value);
    if (isNaN(pts) || pts < 50 || pts > AppState.clienteActualPuntos) return;
    
    const dto = (pts / 50) * 2.5;
    
    AppState.puntosCanjeados = pts;
    AppState.puntosDescuentoAmt = dto;
    
    document.getElementById("puntosCanjeArea").classList.add("d-none");
    document.getElementById("puntosAplicadosResumen").classList.remove("d-none");
    
    const discLabel = document.getElementById("puntosDiscountVal");
    const ptsLabel = document.getElementById("puntosRedeemedVal");
    if (discLabel) discLabel.textContent = `-${dto.toFixed(2).replace(".", ",")} €`;
    if (ptsLabel) ptsLabel.textContent = pts;
    
    Utils.showToast(`¡Puntos canjeados! Descuento de ${dto.toFixed(2)}€ aplicado`);
    
    const totals = CartManager.calculateTotals();
    UiController.renderCart(totals);
  },

  quitarPuntosCanjeados() {
    AppState.puntosCanjeados = 0;
    AppState.puntosDescuentoAmt = 0;
    
    const area = document.getElementById("puntosCanjeArea");
    const res = document.getElementById("puntosAplicadosResumen");
    if (area) area.classList.remove("d-none");
    if (res) res.classList.add("d-none");
    
    const totals = CartManager.calculateTotals();
    if (totals) UiController.renderCart(totals);
  },

  cerrarModalCliente() {
    document.getElementById("clienteModal").classList.remove("visible");
    document.querySelectorAll(".form-error").forEach((el) => (el.innerText = ""));
  }
};
