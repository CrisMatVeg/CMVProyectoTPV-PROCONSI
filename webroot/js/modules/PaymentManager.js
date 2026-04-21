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

  // State (Recovered)
  totalVentaActual: 0,
  checkoutContext: 'efectivo',

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
      const resp = await ApiService.request("./api/getCajaEstadoActual.php");
      if (resp.ok) {
        window.cajaEfectivoActual = resp.efectivoActual;
      }
    } catch (e) {
      console.error("Error al obtener estado de caja:", e);
    }

    const totals = CartManager.calculateTotals();
    this.totalVentaActual = totals ? totals.total : 0;

    const activeSidebarBtn = document.querySelector(".pay-btn.selected");
    if (activeSidebarBtn) {
      this.checkoutContext = activeSidebarBtn.dataset.method;
      AppState.selectedPayment = this.checkoutContext;
    }

    // Resetear selección de cliente a Particular por defecto (si no hay uno ya activo)
    if (!AppState.clienteSeleccionado && !AppState.socioActual) {
      const btnPart = document.getElementById("btnParticular");
      if (btnPart) btnPart.click();
    }
    
    // Si hay un vale aplicado, lo añadimos como primer pago si no existe ya
    if (AppState.valeAplicado) {
       const existing = window.currentPayments.find(p => p.metodo === "vale");
       if (!existing) {
         window.currentPayments.push({
           metodo: "vale",
           importe: Math.min(this.totalVentaActual, parseFloat(AppState.valeAplicado.importe_restante)),
           label: `Vale: ${AppState.valeAplicado.codigo}`
         });
       }
    }

    const totalPagadoVales = window.currentPayments.reduce((acc, p) => acc + p.importe, 0);
    const pendiente = Math.max(0, this.totalVentaActual - totalPagadoVales);

    // Pre-añadir el método seleccionado en el sidebar (Efectivo por defecto)
    if (pendiente > 0.01 && AppState.selectedPayment && AppState.selectedPayment !== 'mixto') {
      const existing = window.currentPayments.find(p => p.metodo === AppState.selectedPayment);
      if (existing) {
        existing.importe = pendiente;
        existing.recibido = AppState.selectedPayment === 'efectivo' ? 0 : pendiente;
      } else {
        const tieneCliente = !!(AppState.clienteSeleccionado || AppState.socioActual);
        if (AppState.selectedPayment !== 'a_cuenta' || tieneCliente) {
          window.currentPayments.push({
            metodo: AppState.selectedPayment,
            importe: pendiente,
            recibido: AppState.selectedPayment === 'efectivo' ? 0 : pendiente,
            label: AppState.selectedPayment.charAt(0).toUpperCase() + AppState.selectedPayment.slice(1).replace('_', ' ')
          });
        }
      }
    }

    if (typeof window.abrirModalPago === "function") {
      window.abrirModalPago();
    } else {
      document.getElementById("clienteModal")?.classList.add("visible");
    }

    this.updateMixSummary();
    this.updateTypeButtons();

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
    
    AppState.clienteSeleccionado = null;

    if (tipo === "particular") {
      AppState.socioActual = null;
      if (gen) gen.classList.remove("d-none");
    } else if (tipo === "socio") {
      const elSocioBusqueda = document.getElementById("socioBusqueda");
      if (elSocioBusqueda) elSocioBusqueda.classList.remove("d-none");
      const elSocioSearch = document.getElementById("socioSearch");
      if (elSocioSearch) setTimeout(() => elSocioSearch.focus(), 100);
    } else {
      const elEmpresaDatos = document.getElementById("empresaDatos");
      if (elEmpresaDatos) elEmpresaDatos.classList.remove("d-none");
      const elEmpresaNombre = document.getElementById("empresaNombre");
      if (elEmpresaNombre) setTimeout(() => elEmpresaNombre.focus(), 100);
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
        AppState.tipoClienteActual = "socio";
        this.updateTypeButtons();
        info.innerHTML = `<i class="fa-solid fa-check-circle text-green"></i> ${r.cliente.nombre} (${r.cliente.nif})`;
        Utils.showToast("Socio identificado");
        this.cargarValesCliente(r.cliente.id);
        this.mostrarPuntosCliente(r.cliente);
        const totals = CartManager.calculateTotals();
        UiController.renderCart(totals);
      } else {
        throw new Error(r.error || "Socio no encontrado");
      }
    } catch (e) {
      console.error(e);
      info.innerHTML = `<span class="text-red">Socio no encontrado.</span>`;
      btnAdd.classList.remove("d-none");
      AppState.socioActual = null;
      Utils.showToast(e.message, "error");
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
      } else if (r.ok) {
        listado.innerHTML = '<div class="fs-11 opacity-50 p-4">No hay vales activos para este cliente.</div>';
      } else {
        throw new Error(r.error || "Error al cargar vales");
      }
    } catch (e) {
      console.error("Error cargando vales:", e);
      listado.innerHTML = '<div class="fs-11 text-red">Error al cargar vales.</div>';
      Utils.showToast(e.message, "error");
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

    if (typeof window.validarDocumento === "function" && !window.validarDocumento(nif)) {
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
        AppState.tipoClienteActual = "socio";
        this.updateTypeButtons();
        this.cancelarRegistroSocio();
        document.getElementById("socioInfo").innerHTML = `<i class="fa-solid fa-check-circle text-green"></i> ${nombre} (NUEVO)`;
        this.cargarValesCliente(r.id);
        
        const totals = CartManager.calculateTotals();
        if (totals) UiController.renderCart(totals);
      } else {
        throw new Error(r.error || "Error al registrar socio");
      }
    } catch (e) {
      console.error(e);
      Utils.showToast(e.message, "error");
    }
  },

  mostrarRegistroCliente() {
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
    const tit = document.getElementById("clienteRegistroTitulo");
    if (tit) {
      const label = window.I18N?.newLabel?.replace('(', '').replace(')', '') || "Nuevo";
      tit.innerText = label + ' ' + (AppState.tipoClienteActual === "empresa" ? (window.I18N?.empresa || "Empresa") : (window.I18N?.particular || "Particular"));
    }
  },

  cancelarRegistroCliente() {
    document.getElementById("clienteRegistro").classList.add("d-none");
    document.getElementById("clienteBusquedaGenerica").classList.remove("d-none");
  },

  async guardarNuevoCliente() {
    const nombreInput = document.getElementById("newClienteNombre");
    const nifInput = document.getElementById("newClienteNif");
    const nombre = nombreInput?.value.trim() || "";
    const nif = nifInput?.value.trim() || "";
    const tipo = AppState.tipoClienteActual;

    if (!nombre) {
      Utils.showToast(window.I18N?.fieldRequired || "Campo requerido", "warning");
      return;
    }

    if (nif && typeof window.validarDocumento === "function" && !window.validarDocumento(nif)) {
      Utils.showToast("El NIF/CIF del cliente no es válido", "warning");
      return;
    }

    try {
      const r = await ApiService.request("api/gestionCliente.php", {
        method: "POST",
        body: JSON.stringify({
          accion: "registrar",
          nombre,
          nif,
          tipo,
          es_socio: 0,
        })
      });

      if (r.ok) {
        Utils.showToast("Cliente registrado y seleccionado", "success");
        const clientObj = {
          id: r.id,
          nombre,
          nif,
          tipo,
          es_socio: false,
        };
        AppState.clienteSeleccionado = clientObj;

        if (tipo === "empresa") {
          const empNom = document.getElementById("empresaNombre");
          const empNif = document.getElementById("empresaNif");
          if (empNom) empNom.value = nombre;
          if (empNif) empNif.value = nif;
        }

        const resEl = document.getElementById("clienteResultados");
        if (resEl) {
          resEl.innerHTML = `<i class="fa-solid fa-check-circle text-green"></i> ${nombre} (NUEVO)`;
        }

        this.cancelarRegistroCliente();
        const totals = CartManager.calculateTotals();
        if (totals) UiController.renderCart(totals);
      } else {
        throw new Error(r.error || "Error al registrar cliente");
      }
    } catch (e) {
      console.error(e);
      Utils.showToast(e.message, "error");
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
      if (r.ok) {
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
      } else {
        throw new Error(r.error || "Error al buscar cliente");
      }
    } catch (e) {
      console.error(e);
      if (resEl) resEl.innerText = "Error al buscar.";
      Utils.showToast(e.message, "error");
    }
  },

  seleccionarClienteGuardado(idx) {
    const c = this.ultimosClientesBuscados[idx];
    if (!c) return;
    AppState.clienteSeleccionado = c;
    AppState.tipoClienteActual = c.tipo || "particular";
    this.updateTypeButtons();

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
    const modal = document.getElementById("clienteModal");
    if (modal) modal.classList.remove("visible");
    document.querySelectorAll(".form-error").forEach((el) => (el.innerText = ""));
  },

  // --- RECOVERED SPLIT PAYMENTS LOGIC ---

  updateMixSummary() {
    const totals = CartManager.calculateTotals();
    this.totalVentaActual = totals ? totals.total : this.totalVentaActual;
    const displayTotal = this.totalVentaActual; 

    const totalPagado = window.currentPayments.reduce((acc, p) => acc + p.importe, 0);
    const pendiente = Math.max(0, displayTotal - totalPagado);

    const inputMonto = document.getElementById("mixPagoMonto");
    if (inputMonto && this.checkoutContext !== 'mixto') {
        if (AppState.selectedPayment === 'a_cuenta' || AppState.selectedPayment === 'efectivo') {
            inputMonto.value = displayTotal.toFixed(2);
        }
    }

    const el_Total = document.getElementById("mixTotalVenta");
    const el_Pagado = document.getElementById("mixTotalPagado");
    const el_Pendiente = document.getElementById("mixTotalPendiente");

    if (el_Total) el_Total.textContent = Utils.formatCurrency(displayTotal);
    if (el_Pagado) el_Pagado.textContent = Utils.formatCurrency(totalPagado);
    
    if (el_Pendiente) {
      el_Pendiente.textContent = Utils.formatCurrency(pendiente);
      el_Pendiente.parentElement.classList.toggle("text-red", pendiente > 0.01);
      el_Pendiente.parentElement.classList.toggle("text-green", pendiente <= 0.01);
    }

    this.calcularCambioMix();

    const el_Lista = document.getElementById("mixListaPagos");
    const el_PendienteRow = el_Pendiente?.parentElement;
    const el_BtnAdd = document.getElementById("mixBtnAddPago");

    if (this.checkoutContext !== 'mixto') {
        if (el_Lista) el_Lista.style.setProperty("display", "none", "important");
        if (el_PendienteRow) el_PendienteRow.style.setProperty("display", "none", "important");
        if (el_BtnAdd) el_BtnAdd.style.setProperty("display", "none", "important");
        
        // [NUEVO] El contenedor de gestión solo se muestra si hay algo que gestionar
        const el_gestion = document.getElementById("cobroMixtoGestion");
        const requiereMonto = (AppState.selectedPayment === 'efectivo' || AppState.selectedPayment === 'a_cuenta');
        
        if (el_gestion) {
            el_gestion.classList.toggle("d-none", !requiereMonto);
            
            // [RESTAURADO] Ocultar selector y etiqueta "Añadir" dentro del contenedor si no es mixto
            const el_selector = document.getElementById("selectorMetodoPago");
            const labelAdd = document.getElementById("labelAddPago");
            if (el_selector) el_selector.style.setProperty("display", "none", "important");
            if (labelAdd) labelAdd.classList.add("d-none");
        }

        const area = document.getElementById("pagoMontoArea");
        if (area && requiereMonto) {
            area.classList.remove("d-none");
            area.style.display = "block";
        } else if (area) {
            area.classList.add("d-none");
            area.style.display = "none";
        }

        // Mostrar un mensaje informativo si es Tarjeta/Bizum/etc (pago exacto)
        const feedbackEl = document.getElementById("mixPagoStatusFeedback");
        if (feedbackEl && !requiereMonto && AppState.selectedPayment) {
            feedbackEl.className = "mt-8 p-12 br-8 text-center font-bold fs-15 bg-accent-soft text-accent border-1";
            const label = AppState.selectedPayment.charAt(0).toUpperCase() + AppState.selectedPayment.slice(1);
            feedbackEl.innerHTML = `<i class="fa-solid fa-credit-card"></i> Pago con ${label}: <strong>${Utils.formatCurrency(this.totalVentaActual)}</strong>`;
            feedbackEl.classList.remove("d-none");
        }
    } else {
        if (el_Lista) el_Lista.style.display = "";
        if (el_PendienteRow) el_PendienteRow.style.display = "";
        if (el_BtnAdd) el_BtnAdd.style.display = "";
        const el_gestion = document.getElementById("cobroMixtoGestion");
        const el_selector = document.getElementById("selectorMetodoPago");
        const labelAdd = document.getElementById("labelAddPago");
        if (el_gestion) el_gestion.classList.remove("d-none");
        if (el_selector) el_selector.style.display = "";
        if (labelAdd) labelAdd.classList.remove("d-none");
    }

    // [NUEVO] Sincronizar visibilidad de campos extra y botones de método
    const el_extraEfectivo = document.getElementById("extraEfectivo");
    const el_extraAcuenta = document.getElementById("extraAcuenta");
    if (el_extraEfectivo) el_extraEfectivo.classList.toggle("d-none", AppState.selectedPayment !== "efectivo");
    if (el_extraAcuenta) el_extraAcuenta.classList.toggle("d-none", AppState.selectedPayment !== "a_cuenta");

    // Resaltar el botón correspondiente en el selector del modal
    document.querySelectorAll(".btn-tpv-method").forEach((b) => {
        b.classList.remove("border-accent", "bg-accent-soft");
        b.style.borderColor = "";
        const m = b.id.replace("btn", "").toLowerCase();
        const currentM = AppState.selectedPayment === 'a_cuenta' ? 'acuenta' : AppState.selectedPayment;
        if (m === currentM) {
            b.classList.add("border-accent", "bg-accent-soft");
            b.style.borderColor = "var(--accent)";
        }
    });

    const btnFinal = document.getElementById("confirmarClienteBtn");
    if (btnFinal) {
      const esAcuenta = (this.checkoutContext === 'a_cuenta' || (AppState.selectedPayment === 'a_cuenta' && this.checkoutContext !== 'mixto'));
      btnFinal.disabled = esAcuenta ? false : (pendiente > 0.01);
    }

    this.renderPaymentsList();
  },

  renderPaymentsList() {
    const container = document.getElementById("mixListaPagos");
    if (!container) return;

    if (window.currentPayments.length === 0) {
      container.innerHTML = `<div class="p-12 text-center opacity-50 fs-12 italic border-2 br-8 dashed" style="border-style: dashed;">${window.I18N?.noPayments || "No hay pagos añadidos"}</div>`;
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

    container.innerHTML = window.currentPayments
      .map((p, index) => {
          const hasCambio = (p.metodo === 'efectivo' && p.recibido > p.importe + 0.005);
          const cambio = hasCambio ? (p.recibido - p.importe) : 0;
          return `
      <div class="d-flex ai-center jc-space-between p-8-12 bg-surface1 br-8 border-1 mb-4 animate-slide-right">
          <div class="d-flex ai-center gap-8 flex-wrap">
              <i class="fa-solid ${icons[p.metodo] || "fa-wallet"} opacity-70"></i>
              <span class="badge ${p.metodo === "efectivo" ? "bg-accent text-white" : "bg-surface3 border-1 text-primary"} p-2-6 br-4 fs-9 font-bold uppercase">${window.I18N?.[p.metodo] || p.metodo.replace("_", " ")}</span>
              <span class="font-mono font-bold">${Utils.formatCurrency(p.importe)}</span>
              ${hasCambio ? `<span class="fs-10 text-muted">(Entregado: ${Utils.formatCurrency(p.recibido)} &middot; <span class="text-green font-bold">Cambio: ${Utils.formatCurrency(cambio)}</span>)</span>` : ''}
          </div>
          <button onclick="app.removePagoMixto(${index})" class="text-red border-none bg-none cursor-pointer hover-scale p-4">
              <i class="fa-solid fa-trash-can"></i>
          </button>
      </div>
    `;
      })
      .join("");
  },

  selectModalPayment(btn) {
    const metodo = btn.id.replace("btn", "").toLowerCase();
    AppState.selectedPayment = (metodo === "acuenta" || metodo === "a_cuenta") ? "a_cuenta" : metodo;
    
    this.updateMixSummary();

    const inputMonto = document.getElementById("mixPagoMonto");
    if (inputMonto) {
      inputMonto.focus();
      inputMonto.select();
    }
  },

  addPagoMixto() {
    const inputMonto = document.getElementById("mixPagoMonto");
    const importeIngresado = parseFloat(inputMonto.value) || 0;

    const metodosValidos = ['efectivo', 'tarjeta', 'bizum', 'a_cuenta'];
    if (!AppState.selectedPayment || !metodosValidos.includes(AppState.selectedPayment)) {
      Utils.showToast("Selecciona un método de pago", 'warning');
      return;
    }

    if (importeIngresado <= 0) {
      Utils.showToast(window.I18N?.enterAmount || "Introduce un importe", "warning");
      return;
    }

    const totalPagado = window.currentPayments.reduce((acc, p) => acc + p.importe, 0);
    const pendiente = Math.max(0, this.totalVentaActual - totalPagado);
    
    // Para efectivo: el importe aplicado está limitado al pendiente, pero guardamos el recibido real
    const esEfectivo = (AppState.selectedPayment === "efectivo");
    const importeAplicado = Math.min(importeIngresado, pendiente);
    const recibidoReal = esEfectivo ? importeIngresado : importeAplicado;

    window.currentPayments.push({
      metodo: AppState.selectedPayment,
      importe: importeAplicado,
      recibido: recibidoReal,
      fecha_limite: AppState.selectedPayment === "a_cuenta" ? document.getElementById("aCuentaFechaLimite")?.value : null,
    });

    inputMonto.value = "";
    this.updateMixSummary();
  },

  removePagoMixto(index) {
    window.currentPayments.splice(index, 1);
    this.updateMixSummary();
  },

  calcularCambioMix() {
    const inputMonto = document.getElementById("mixPagoMonto");
    const montoIngresado = parseFloat(inputMonto?.value) || 0;
    
    // Si NO es mixto, el "pendiente" sobre el que calculamos el cambio es el TOTAL de la venta
    // Si ES mixto, es lo que falta por pagar de la venta
    const totalPagadoConfirmado = (this.checkoutContext === 'mixto') 
        ? window.currentPayments.reduce((acc, p) => acc + p.importe, 0)
        : 0;
    const pendienteReal = Math.max(0, this.totalVentaActual - totalPagadoConfirmado);

    const cambioEl = document.getElementById("efectivoCambio");
    const feedbackEl = document.getElementById("mixPagoStatusFeedback");
    if (!cambioEl) return;

    if (AppState.selectedPayment === "efectivo") {
        const falta  = pendienteReal - montoIngresado;
        const cambio = Math.max(0, montoIngresado - pendienteReal);
        if (feedbackEl) {
            if (falta > 0.005) {
                feedbackEl.className = "mt-8 p-10 br-8 text-center font-bold fs-13 bg-red-soft text-red";
                feedbackEl.innerHTML = `<i class="fa-solid fa-hourglass-half"></i> Faltan: ${Utils.formatCurrency(falta)}`;
                feedbackEl.classList.remove("d-none");
            } else {
                // Si hay cambio o es exacto, ocultamos el feedback secundario para no duplicar con el área de Cambio
                feedbackEl.innerHTML = "";
                feedbackEl.classList.add("d-none");
            }
        }
        cambioEl.textContent = Utils.formatCurrency(Math.max(0, montoIngresado - pendienteReal));
    } else {
        if (feedbackEl) {
            feedbackEl.innerHTML = "";
            feedbackEl.classList.add("d-none");
        }
        cambioEl.textContent = "0,00 €";
    }

    if (montoIngresado > 0) {
        const el_Pendiente = document.getElementById("mixTotalPendiente");
        if (el_Pendiente) {
            const pnd = Math.max(0, pendienteReal - montoIngresado);
            el_Pendiente.textContent = Utils.formatCurrency(pnd);
            el_Pendiente.parentElement.classList.toggle("text-red", pnd > 0.01);
        }

        // [NUEVO] Sincronizar el importe recibido para el registro de la venta si es un pago único de efectivo
        if (AppState.selectedPayment === "efectivo" && this.checkoutContext !== 'mixto') {
            const efectivoPago = window.currentPayments.find(p => p.metodo === 'efectivo');
            if (efectivoPago) {
                efectivoPago.recibido = montoIngresado;
            }
        }
    }
  },

  /**
   * Ejecutar el cobro final y guardar en base de datos.
   * Logic recovered and standardized for modular architecture.
   */
  async ejecutarCobroFinal() {
    const btn = document.getElementById("confirmarClienteBtn");
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> PROCESANDO...';
    }

    // 1. Validar "A cuenta" si aplica
    const esAcuentaIndividual = (this.checkoutContext === 'a_cuenta' || (AppState.selectedPayment === 'a_cuenta' && this.checkoutContext !== 'mixto'));
    const tieneAcuentaEnMixto = window.currentPayments.some(p => p.metodo === "a_cuenta");
    
    if (esAcuentaIndividual || tieneAcuentaEnMixto) {
        const clienteId = AppState.socioActual ? AppState.socioActual.id : (AppState.clienteSeleccionado ? AppState.clienteSeleccionado.id : null);
        if (!clienteId) {
            Utils.showToast("Debes seleccionar un cliente para fiar la compra.", "error");
            if (btn) { btn.disabled = false; btn.textContent = "Confirmar Cobro"; }
            return;
        }
    }

    // 2. Validar Factura: si está activada, el cliente debe estar identificado (Nombre y NIF)
    if (AppState.esFactura) {
      const tieneSocio = !!AppState.socioActual;
      const tieneEmpresa = !!(document.getElementById("empresaNombre")?.value && document.getElementById("empresaNif")?.value);
      const tieneParticularIdentificado = !!AppState.clienteSeleccionado;
      const tieneIdentificado = tieneSocio || tieneEmpresa || tieneParticularIdentificado;

      if (!tieneIdentificado) {
        Utils.showToast('<i class="fa-solid fa-circle-exclamation"></i> Debes identificar al cliente (Nombre y NIF) para generar una factura.', "error");
        if (btn) { btn.disabled = false; btn.textContent = "Confirmar Cobro"; }
        return;
      }
    }

    // 3. Preparar payload
    const totals = CartManager.calculateTotals();
    const payload = {
      cliente: AppState.clienteSeleccionado,
      socio: AppState.socioActual,
      tipoCliente: AppState.tipoClienteActual,
      lineas: Object.values(window.cart).map(it => ({
        id: it.id,
        name: it.name,
        codigo: it.codigo,
        price: it.price,
        qty: it.qty,
        iva: it.iva,
        variant: it.variant,
        basePriceSnapshot: it.basePriceSnapshot,
        appliedTariffs: it.appliedTariffs
      })),
      pagos: window.currentPayments,
      metodoPago: AppState.selectedPayment || 'efectivo',
      efectivoRecibido: window.currentPayments.find(p => p.metodo === 'efectivo')?.recibido || 0,
      total: totals.total,
      subtotal: totals.subtotal,
      descuentoAmt: totals.totalDiscount,
      descuentoPct: (AppState.currentPromo && AppState.currentPromo.tipo === 'percent') ? AppState.currentPromo.valor : 0,
      esFactura: AppState.esFactura ? 1 : 0,
      puntosCanjeados: AppState.puntosCanjeados || 0,
      puntosDescuentoAmt: AppState.puntosDescuentoAmt || 0,
      codigoCupon: (AppState.currentPromo && AppState.currentPromo.codigo) ? AppState.currentPromo.codigo : null
    };

    try {
      const data = await ApiService.request("./api/guardarVenta.php", {
        method: "POST",
        body: JSON.stringify(payload)
      });
      
      if (data.ok) {
        Utils.showToast("Venta guardada con éxito", "success");
        if (typeof window.clearCart === "function") {
          window.clearCart();
        } else {
          CartManager.clearCart();
        }
        window.currentPayments = [];
        TicketManager.showTicket(data.venta, true);
        this.cerrarModalCliente();
      } else {
        throw new Error(data.error || "Fallo al guardar la venta en el servidor.");
      }
    } catch (err) {
      console.error("Save error:", err);
      Utils.showToast(err.message, "error");
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.textContent = "Confirmar Cobro";
      }
    }
  }
};
