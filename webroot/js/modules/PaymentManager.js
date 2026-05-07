/**
 * PaymentManager.js
 * Handles checkout process, payment methods, and client/socio selection.
 */

import { AppConfig, AppState } from './AppConfig.js';
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
      const resp = await ApiService.request("./api/cajaEstadoActual.php");
      if (resp.ok) {
        window.cajaEfectivoActual = parseFloat(resp.efectivoActual);
        console.log('[Caja] Efectivo en turno calculado por API:', window.cajaEfectivoActual, '€');
        const lbl = document.getElementById('cajaEfectivoLabel');
        if (lbl) lbl.textContent = Utils.formatCurrency(window.cajaEfectivoActual);
      }
    } catch (e) {
      console.error("Error al obtener estado de caja:", e);
    }

    await this.syncCartPricesWithServer();

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

    const totalPagadoVales = window.currentPayments.reduce((acc, p) => acc + (p.metodo === 'vale' ? p.importe : 0), 0);
    const pendiente = Math.max(0, this.totalVentaActual - totalPagadoVales);

    // Pre-añadir el método seleccionado en el sidebar (Efectivo por defecto)
    if (pendiente > 0.01 && AppState.selectedPayment && AppState.selectedPayment !== 'mixto') {
      const existing = window.currentPayments.find(p => p.metodo === AppState.selectedPayment);
      if (existing) {
        existing.importe = pendiente;
        existing.recibido = AppState.selectedPayment === 'efectivo' ? pendiente : pendiente;
      } else {
        window.currentPayments.push({
            metodo: AppState.selectedPayment,
            importe: pendiente,
            recibido: pendiente,
            label: AppState.selectedPayment.charAt(0).toUpperCase() + AppState.selectedPayment.slice(1).replace('_', ' ')
          });
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
    
    // this.quitarValeAplicado(); // [ELIMINADO] No quitar el vale aquí, dejar que se gestione en el flujo de pagos
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

  async seleccionarTipoCliente(tipo) {
    AppState.tipoClienteActual = tipo;
    this.updateTypeButtons();

    await this.syncCartPricesWithServer();
    this.updateMixSummary();

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

    CartManager.recalculateCartPrices();
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
        info.innerHTML = `
          <div class="d-flex ai-center jc-space-between bg-surface2 p-8 br-8 border-1">
            <span class="fs-13 fw-600"><i class="fa-solid fa-check-circle text-green mr-8"></i> ${r.cliente.nombre} (${r.cliente.nif})</span>
            <button type="button" class="btn-tpv-icon sm text-red" onclick="app.deseleccionarCliente()" title="Deseleccionar">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
        `;
        Utils.showToast("Socio identificado");
        this.cargarValesCliente(r.cliente.id);
        this.mostrarPuntosCliente(r.cliente);
        
        await this.syncCartPricesWithServer();
        
        CartManager.recalculateCartPrices();
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

  deseleccionarCliente() {
    AppState.socioActual = null;
    AppState.clienteSeleccionado = null;
    AppState.tipoClienteActual = "particular";
    
    // Limpiar UI de resultados
    const resEl = document.getElementById("clienteResultados");
    if (resEl) resEl.innerHTML = "";
    
    const socioInfo = document.getElementById("socioInfo");
    if (socioInfo) socioInfo.innerHTML = "";
    
    const socioSearch = document.getElementById("socioSearch");
    if (socioSearch) socioSearch.value = "";
    
    const clienteSearch = document.getElementById("clienteSearch");
    if (clienteSearch) clienteSearch.value = "";

    // Resetear botones de tipo
    this.updateTypeButtons();
    
    // Ocultar paneles de vales y puntos
    const valeContainer = document.getElementById("clienteValesContainer");
    if (valeContainer) valeContainer.classList.add("d-none");
    
    const puntosContainer = document.getElementById("clientePuntosContainer");
    if (puntosContainer) puntosContainer.classList.add("d-none");
    
    this.quitarValeAplicado();
    this.quitarPuntosCanjeados();
    
    // Recalcular precios (quitar tarifas de socio si las hubiera)
    CartManager.recalculateCartPrices();
    const totals = CartManager.calculateTotals();
    UiController.renderCart(totals);
    
    this.updateMixSummary();
    Utils.showToast("Cliente deseleccionado");
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
    
    // [NUEVO] Añadir el vale directamente a la lista de pagos actual para que se refleje en el total pendiente
    const existing = window.currentPayments.find(p => p.metodo === "vale");
    if (!existing) {
        const totals = CartManager.calculateTotals();
        window.currentPayments.push({
            metodo: "vale",
            importe: Math.min(totals.total, parseFloat(vale.importe_restante)),
            label: `Vale: ${vale.codigo}`
        });
    }

    const resumen = document.getElementById("valeAplicadoResumen");
    const totalLabel = document.getElementById("valeAplicadoTotal");
    const listado = document.getElementById("listadoValesCliente");
    
    if (resumen && totalLabel) {
      resumen.classList.remove("d-none");
      totalLabel.textContent = Utils.formatCurrency(parseFloat(vale.importe_restante));
    }
    if (listado) listado.classList.add("d-none");
    
    this.updateMixSummary(); // Forzar actualización de sumas en el modal
    Utils.showToast(`<i class="fa-solid fa-ticket"></i> Vale ${vale.codigo} aplicado`);
  },

  quitarValeAplicado() {
    AppState.valeAplicado = null;
    
    // [NUEVO] Quitar de la lista de pagos
    window.currentPayments = window.currentPayments.filter(p => p.metodo !== "vale");
    
    const resumen = document.getElementById("valeAplicadoResumen");
    const listado = document.getElementById("listadoValesCliente");
    
    if (resumen) resumen.classList.add("d-none");
    if (listado) listado.classList.remove("d-none");

    this.updateMixSummary();
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
    const idType = document.getElementById("newSocioIdType").value;
    const pais = document.getElementById("newSocioPais").value.trim() || "ES";

    if (!nombre || !nif) {
      Utils.showToast("Nombre y NIF son obligatorios", "error");
      return;
    }

    if (idType === "01" && typeof window.validarDocumento === "function" && !window.validarDocumento(nif)) {
      Utils.showToast("El DNI/NIE de socio no es válido", "warning");
      return;
    }

    try {
      const r = await ApiService.request("api/gestionCliente.php", {
        method: "POST",
        body: JSON.stringify({ accion: "registrar", nombre, nif, es_socio: 1, aeat_id_type: idType, aeat_codigo_pais: pais })
      });
      if (r.ok) {
        Utils.showToast("Socio registrado y seleccionado", "success");
        AppState.socioActual = { id: r.id, nombre, nif, es_socio: true, aeat_id_type: idType, aeat_codigo_pais: pais };
        AppState.tipoClienteActual = "socio";
        this.updateTypeButtons();
        this.cancelarRegistroSocio();
        document.getElementById("socioInfo").innerHTML = `<i class="fa-solid fa-check-circle text-green"></i> ${nombre} (NUEVO)`;
        this.cargarValesCliente(r.id);
        
        await this.syncCartPricesWithServer();
        
        CartManager.recalculateCartPrices();
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
    const idTypeInput = document.getElementById("newClienteIdType");
    const paisInput = document.getElementById("newClientePais");
    const nombre = nombreInput?.value.trim() || "";
    const nif = nifInput?.value.trim() || "";
    const idType = idTypeInput?.value || "01";
    const pais = paisInput?.value.trim() || "ES";
    const tipo = AppState.tipoClienteActual;

    if (!nombre) {
      Utils.showToast(window.I18N?.fieldRequired || "Campo requerido", "warning");
      return;
    }

    if (idType === "01" && nif && typeof window.validarDocumento === "function" && !window.validarDocumento(nif)) {
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
          aeat_id_type: idType,
          aeat_codigo_pais: pais,
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
          aeat_id_type: idType,
          aeat_codigo_pais: pais,
        };
        AppState.clienteSeleccionado = clientObj;

        if (tipo === "empresa") {
          const empNom = document.getElementById("empresaNombre");
          const empNif = document.getElementById("empresaNif");
          if (empNom) empNom.value = nombre;
          if (empNif) empNif.value = nif;
          const empIdT = document.getElementById("empresaIdType");
          const empP = document.getElementById("empresaPais");
          if (empIdT) empIdT.value = idType;
          if (empP) empP.value = pais;
        }

        const resEl = document.getElementById("clienteResultados");
        if (resEl) {
          resEl.innerHTML = `<i class="fa-solid fa-check-circle text-green"></i> ${nombre} (NUEVO)`;
        }

        this.cancelarRegistroCliente();
        
        await this.syncCartPricesWithServer();
        
        CartManager.recalculateCartPrices();
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

  async seleccionarClienteGuardado(idx) {
    const c = this.ultimosClientesBuscados[idx];
    if (!c) return;
    AppState.clienteSeleccionado = c;
    AppState.tipoClienteActual = c.tipo || "particular";
    AppState.socioActual = null;
    
    await this.syncCartPricesWithServer();

    this.updateTypeButtons();

    if (AppState.tipoClienteActual === "empresa") {
        document.getElementById("empresaNombre").value = `${c.nombre} ${c.apellidos || ""}`.trim();
        document.getElementById("empresaNif").value = c.nif || "";
        const empIdT = document.getElementById("empresaIdType");
        const empP = document.getElementById("empresaPais");
        if (empIdT) empIdT.value = c.aeat_id_type || '01';
        if (empP) empP.value = c.aeat_codigo_pais || 'ES';
    }

    const resEl = document.getElementById("clienteResultados");
    if (resEl) {
        resEl.innerHTML = `
          <div class="d-flex ai-center jc-space-between bg-surface2 p-8 br-8 border-1 full-width">
            <span class="fs-13 fw-600"><i class="fa-solid fa-check-circle text-success mr-8"></i> ${c.nombre} (${c.nif || ""})</span>
            <button type="button" class="btn-tpv-icon sm text-red" onclick="app.deseleccionarCliente()" title="Deseleccionar">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>
        `;
        this.cargarValesCliente(c.id);
    }

    Utils.showToast("Cliente seleccionado: " + c.nombre);
    this.mostrarPuntosCliente(c);
    
    CartManager.recalculateCartPrices();
    const totals = CartManager.calculateTotals();
    UiController.renderCart(totals);
    this.updateMixSummary();
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

  async syncCartPricesWithServer() {
    try {
      const idCliente = AppState.socioActual?.id ?? AppState.clienteSeleccionado?.id ?? null;
      const cartLines = Object.values(AppState.cart).map(it => ({ id: it.id, qty: it.qty }));
      
      const recalcResp = await ApiService.request('./api/recalcularPreciosCarrito.php', {
        method: 'POST',
        body: JSON.stringify({
          lineas: cartLines,
          id_cliente: idCliente,
          codigoCupon: AppState.currentPromo?.codigo ?? null
        })
      });

      if (recalcResp.ok && recalcResp.lineas) {
        const cart = AppState.cart;
        recalcResp.lineas.forEach(serverLine => {
          if (serverLine.comodin) return;
          const key = Object.keys(cart).find(k => String(cart[k].id) === String(serverLine.id));
          if (key) {
            cart[key].price            = serverLine.precio_final;
            cart[key].basePriceSnapshot = serverLine.precio_base;
            cart[key].appliedTariffs   = serverLine.appliedTariffs;
            cart[key]._tariffApplied   = true;
          }
        });
        AppState.cart = cart;
        
        // Actualizar el total local tras sincronizar
        const totals = CartManager.calculateTotals();
        this.totalVentaActual = totals ? totals.total : this.totalVentaActual;
      }
    } catch (e) {
      console.error('[Tariff recalc] Error al recalcular precios de tarifa:', e);
    }
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
        if (AppState.selectedPayment === 'efectivo') {
            const totalPagadoOtros = window.currentPayments
                .filter(p => p.metodo !== AppState.selectedPayment)
                .reduce((acc, p) => acc + p.importe, 0);
            const faltaReal = Math.max(0, this.totalVentaActual - totalPagadoOtros);
            inputMonto.value = faltaReal.toFixed(2);
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

    // [NUEVO] Aviso de tarifa aplicada
    const el_TariffNotice = document.getElementById("tariffNotice");
    const el_TariffText = document.getElementById("tariffNoticeText");
    if (el_TariffNotice) {
      const itemsWithTariffs = Object.values(AppState.cart).filter(it => it.appliedTariffs && it.appliedTariffs.length > 0);
      if (itemsWithTariffs.length > 0) {
        // Obtenemos los nombres únicos de las tarifas aplicadas (excluyendo cupones/promos que se muestran aparte)
        const tariffNames = [...new Set(itemsWithTariffs.flatMap(it => 
          it.appliedTariffs
            .filter(t => t.tipo_descuento !== 'cupon' && t.tipo_descuento !== 'promocion')
            .map(t => t.nombre || t.nombre_descuento || 'Tarifa')
        ))].filter(name => !!name);
        
        if (tariffNames.length > 0) {
            el_TariffText.textContent = "Tarifa" + (tariffNames.length > 1 ? "s" : "") + ": " + tariffNames.join(", ");
            el_TariffNotice.classList.remove("d-none");
            el_TariffNotice.classList.add("d-flex");
        } else {
            el_TariffNotice.classList.add("d-none");
            el_TariffNotice.classList.remove("d-flex");
        }
      } else {
        el_TariffNotice.classList.add("d-none");
        el_TariffNotice.classList.remove("d-flex");
      }
    }

    // [NUEVO] Aviso de Promo/Cupón aplicado
    const el_PromoNotice = document.getElementById("promoNoticeSummary");
    const el_PromoText = document.getElementById("promoNoticeText");
    if (el_PromoNotice) {
      if (AppState.currentPromo) {
        let descText = AppState.currentPromo.nombre || AppState.currentPromo.codigo;
        if (AppState.currentPromo.tipo === "percent") descText += ` (-${AppState.currentPromo.valor}%)`;
        else if (AppState.currentPromo.tipo === "amount") descText += ` (-${Utils.formatCurrency(AppState.currentPromo.valor)})`;
        
        el_PromoText.textContent = "Promo: " + descText;
        el_PromoNotice.classList.remove("d-none");
        el_PromoNotice.classList.add("d-flex");
      } else {
        el_PromoNotice.classList.add("d-none");
        el_PromoNotice.classList.remove("d-flex");
      }
    }

    // [NUEVO] Sincronizar el importe del vale si el total ha cambiado
    if (AppState.valeAplicado) {
        const pVale = window.currentPayments.find(p => p.metodo === "vale");
        if (pVale) {
            const maxCubrible = parseFloat(AppState.valeAplicado.importe_restante);
            // El vale debe cubrir lo que pueda del total, pero no más de lo que vale
            pVale.importe = Math.min(this.totalVentaActual, maxCubrible);
        }
    }

    // [NUEVO] Si no es mixto, asegurar que el pago principal cubra lo que falta
    if (this.checkoutContext !== 'mixto' && AppState.selectedPayment) {
        const pMain = window.currentPayments.find(p => p.metodo === AppState.selectedPayment);
        if (pMain) {
            const totalPagadoOtros = window.currentPayments
                .filter(p => p !== pMain)
                .reduce((acc, p) => acc + p.importe, 0);
            const pendienteReal = Math.max(0, this.totalVentaActual - totalPagadoOtros);
            
            const oldImporte = pMain.importe;
            pMain.importe = pendienteReal;
            
            if (pMain.metodo === 'efectivo') {
                if (Math.abs(pMain.recibido - oldImporte) < 0.01 || pMain.recibido < 0.01) {
                    pMain.recibido = pMain.importe;
                }
            }
        }
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
        const requiereMonto = (AppState.selectedPayment === 'efectivo');
        
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

        // Mostrar el área de importe siempre que haya un método seleccionado en modo mixto
        const area = document.getElementById("pagoMontoArea");
        if (area) {
            if (AppState.selectedPayment) {
                area.classList.remove("d-none");
                area.style.display = "block";
            } else {
                area.classList.add("d-none");
                area.style.display = "none";
            }
        }
    }

    // Sincronizar visibilidad del bloque de cambio en efectivo
    const el_extraEfectivo = document.getElementById("extraEfectivo");
    if (el_extraEfectivo) el_extraEfectivo.classList.toggle("d-none", AppState.selectedPayment !== "efectivo");

    // Resaltar el botón correspondiente en el selector del modal
    document.querySelectorAll(".btn-tpv-method").forEach((b) => {
        b.classList.remove("border-accent", "bg-accent-soft");
        b.style.borderColor = "";
        const m = b.id.replace("btn", "").toLowerCase();
        if (m === AppState.selectedPayment) {
            b.classList.add("border-accent", "bg-accent-soft");
            b.style.borderColor = "var(--accent)";
        }
    });

    const btnFinal = document.getElementById("confirmarClienteBtn");
    if (btnFinal) {
      btnFinal.disabled = (pendiente > 0.01);
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
    AppState.selectedPayment = metodo;
    
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

    const metodosValidos = ['efectivo', 'tarjeta', 'bizum'];
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

    // [NUEVO] Validar que hay cambio suficiente ANTES de añadir el pago
    if (esEfectivo) {
        const cambioNecesario = Math.max(0, recibidoReal - importeAplicado);
        if (cambioNecesario > 0.01) {
            const disponible = parseFloat(window.cajaEfectivoActual) || 0;
            if (cambioNecesario > (disponible + 0.01)) {
                Utils.showToast(`No puedes añadir este pago: El cambio (${Utils.formatCurrency(cambioNecesario)}) es mayor que el efectivo disponible (${Utils.formatCurrency(disponible)})`, "error");
                return;
            }
        }
    }

    window.currentPayments.push({
      metodo: AppState.selectedPayment,
      importe: importeAplicado,
      recibido: recibidoReal,
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
    
    // En modo mixto, todos los pagos ya añadidos están comprometidos → el pendiente
    // es total menos TODOS los existentes. En modo simple, excluimos los del mismo método
    // para que el campo de efectivo muestre cuánto falta cubrir con ese método.
    const totalPagadoOtros = (this.checkoutContext === 'mixto')
        ? window.currentPayments.reduce((acc, p) => acc + p.importe, 0)
        : window.currentPayments
            .filter(p => p.metodo !== AppState.selectedPayment)
            .reduce((acc, p) => acc + p.importe, 0);
    const pendienteReal = Math.max(0, this.totalVentaActual - totalPagadoOtros);

    const cambioEl = document.getElementById("efectivoCambio");
    const feedbackEl = document.getElementById("mixPagoStatusFeedback");
    if (!cambioEl) return;

    if (AppState.selectedPayment === "efectivo") {
        const falta = pendienteReal - montoIngresado;
        const cambio = Math.max(0, montoIngresado - pendienteReal);
        const disponible = parseFloat(window.cajaEfectivoActual) || 0;
        const btnConf = document.getElementById("confirmarClienteBtn");
        console.log('[Cambio] montoIngresado:', montoIngresado, '| pendiente:', pendienteReal, '| cambio:', cambio, '| disponible en caja:', disponible);

        if (falta > 0.01) {
            feedbackEl.className = "mt-8 p-10 br-8 text-center font-bold fs-13 bg-red-light text-red";
            feedbackEl.classList.remove("d-none");
            feedbackEl.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i> Faltan: ${Utils.formatCurrency(falta)}`;
            // El botón ya está deshabilitado por pendiente > 0 en updateMixSummary
        } else if (cambio > 0.01) {
            const esExcesivo = (cambio > disponible + 0.01);
            feedbackEl.style.cssText = "";
            feedbackEl.className = esExcesivo
                ? "mt-8 p-12 br-8 text-center font-bold fs-13 bg-red-light text-red border border-red"
                : "mt-8 p-10 br-8 text-center font-bold fs-13 bg-green-light text-green";
            feedbackEl.classList.remove("d-none");
            const icono = esExcesivo ? "fa-triangle-exclamation" : "fa-hand-holding-dollar";
            const txtLabel = esExcesivo ? "CAMBIO EXCESIVO — NO SE PUEDE CONFIRMAR" : "Cambio a devolver";
            const txtExtra = esExcesivo
                ? `<div class="fs-11 mt-4" style="opacity:0.92;">Efectivo disponible en caja: ${Utils.formatCurrency(disponible)}</div>`
                : "";
            feedbackEl.innerHTML = `<i class="fa-solid ${icono}"></i> ${txtLabel}: <strong>${Utils.formatCurrency(cambio)}</strong>${txtExtra}`;

            // Bloquear/desbloquear el botón según disponibilidad
            if (btnConf) {
                btnConf.disabled = esExcesivo;
                btnConf.style.opacity = esExcesivo ? "0.5" : "";
                btnConf.style.cursor  = esExcesivo ? "not-allowed" : "";
                btnConf.title = esExcesivo
                    ? `Sin efectivo suficiente. Necesitas ${Utils.formatCurrency(cambio)}, hay ${Utils.formatCurrency(disponible)}`
                    : "";
            }
        } else {
            feedbackEl.innerHTML = "";
            feedbackEl.classList.add("d-none");
            if (btnConf) {
                btnConf.disabled = false;
                btnConf.style.opacity = "";
                btnConf.style.cursor  = "";
                btnConf.title = "";
            }
        }

        if (this.checkoutContext !== "mixto") {
            const pEfectivo = window.currentPayments.find(p => p.metodo === "efectivo");
            if (pEfectivo) pEfectivo.recibido = isNaN(montoIngresado) ? pendienteReal : montoIngresado;
        }

        cambioEl.textContent = Utils.formatCurrency(cambio);
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

        if (AppState.selectedPayment === "efectivo" && this.checkoutContext !== 'mixto') {
            const efectivoPago = window.currentPayments.find(p => p.metodo === 'efectivo');
            if (efectivoPago) {
                efectivoPago.recibido = isNaN(montoIngresado) ? pendienteReal : montoIngresado;
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

    // [MEJORADO] Sumar todo el cambio de pagos en efectivo (por si hay varios en mixto)
    const totalCambioEfectivo = window.currentPayments.reduce((acc, p) => {
        if (p.metodo === 'efectivo') return acc + Math.max(0, p.recibido - p.importe);
        return acc;
    }, 0);

    if (totalCambioEfectivo > 0.009) {
        // [CRÍTICO] Forzar refresco de efectivo disponible justo antes de validar
        try {
            const resp = await ApiService.request("./api/cajaEstadoActual.php");
            if (resp.ok) window.cajaEfectivoActual = parseFloat(resp.efectivoActual);
        } catch (e) {
            console.error("No se pudo refrescar el estado de la caja", e);
        }

        const disponible = parseFloat(window.cajaEfectivoActual) || 0;
        if (totalCambioEfectivo > (disponible + 0.01)) {
            Utils.showToast(`BLOQUEADO: No hay efectivo suficiente en el cajón para devolver el cambio (${Utils.formatCurrency(totalCambioEfectivo)}). Disponible: ${Utils.formatCurrency(disponible)}`, "error");
            if (btn) {
                btn.disabled = false;
                btn.textContent = "Confirmar Cobro";
            }
            return;
        }
    }

    // 1. Validar Factura: si está activada, el cliente debe estar identificado (Nombre y NIF)
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

    // [NUEVO] Capturar metadatos AEAT de identificación si es empresa manual
    let manualAeatIdType = null;
    let manualAeatPais = null;
    if (AppState.tipoClienteActual === 'empresa') {
        manualAeatIdType = document.getElementById("empresaIdType")?.value;
        manualAeatPais = document.getElementById("empresaPais")?.value.trim() || 'ES';
    }

    // 3. Preparar payload
    const totals = CartManager.calculateTotals();

    // Derivar metodoPago desde el contexto de cobro, no del último botón pulsado en el modal.
    // En modo mixto AppState.selectedPayment cambia con cada método que se selecciona dentro del modal,
    // por lo que usamos checkoutContext como fuente autoritativa.
    const metodosUsados = [...new Set(window.currentPayments.map(p => p.metodo))];
    const derivedMetodoPago = (this.checkoutContext === 'mixto' || metodosUsados.length > 1)
        ? 'mixto'
        : (window.currentPayments[0]?.metodo || AppState.selectedPayment || 'efectivo');

    const payload = {
      cliente: AppState.clienteSeleccionado,
      socio: AppState.socioActual,
      tipoCliente: AppState.tipoClienteActual,
      aeatIdType: manualAeatIdType,
      aeatCodigoPais: manualAeatPais,
      lineas: Object.values(window.cart).map(it => ({
        id: it.id,
        name: it.name,
        codigo: it.codigo,
        price: it.price,
        qty: it.qty,
        iva: it.iva,
        variant: it.variant,
        basePriceSnapshot: it.basePriceSnapshot,
        descuentos: it.appliedTariffs,
        appliedTariffs: it.appliedTariffs
      })),
      pagos: window.currentPayments,
      metodoPago: derivedMetodoPago,
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
        
        // [NUEVO] Actualizar stock localmente para refrescar la UI de inmediato
        const itemsVendidos = Object.values(AppState.cart);
        itemsVendidos.forEach(item => {
            if (item.id === -1) return; // Comodines
            const p = AppConfig.products.find(x => String(x.id) === String(item.id));
            if (p) {
                p.stock = Math.max(0, (p.stock || 0) - item.qty);
            }
        });
        // Refrescar el catálogo para mostrar productos agotados
        UiController.renderProducts();

        if (typeof window.clearCart === "function") {
          window.clearCart();
        } else {
          CartManager.clearCart();
        }
        window.currentPayments = [];
        AppState.valeAplicado = null; // Limpiar vale tras éxito
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
