/**
 * TicketManager.js
 * Handles ticket rendering, printing, PDF generation, and email.
 */
import { AppState } from './AppConfig.js';
import { formatTicketNumber, Utils, showToast } from './Utils.js';

export const TicketManager = {
    currentTicketNum: null,

    showTicket(v, isFromTPV = false) {
        this.currentTicketNum = v.numero_ticket;
        const fmt2 = Utils.fmt2;

        const el_tkNum = document.getElementById("tkNumero");
        if (el_tkNum) {
            const esFactura = v.tipo_cliente === 'empresa' || v.es_factura == 1;
            el_tkNum.textContent = formatTicketNumber(v.numero_ticket, v.fecha, esFactura);
        }

        const el_tkFecha = document.getElementById("tkFecha");
        const fechaStr = new Date(v.fecha.replace(" ", "T")).toLocaleString("es-ES", {
            dateStyle: "short",
            timeStyle: "short",
        });
        if (el_tkFecha) el_tkFecha.textContent = fechaStr;

        const el_tkMetodo = document.getElementById("tkMetodo");
        if (el_tkMetodo)
            el_tkMetodo.textContent = v.metodo_pago.charAt(0).toUpperCase() + v.metodo_pago.slice(1);

        const tkCajero = document.getElementById("tkCajero");
        if (tkCajero) {
            tkCajero.textContent = v.nombre_cajero || "—";
        }

        const lineasEl = document.getElementById("tkLineas");
        if (lineasEl)
            lineasEl.innerHTML = v.lineas.map((l) => {
                const vDate = new Date(v.fecha.replace(" ", "T"));
                const months = parseInt(l.meses_garantia || 24);
                const gDate = new Date(vDate);
                gDate.setMonth(gDate.getMonth() + months);
                const isExpired = gDate < new Date();
                const gStr = gDate.toLocaleDateString("es-ES");

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
            </div>
          </div>`;
            }).join("");

        // Desglose IVA por tipo desde las líneas
        const el_tkSubtotalRow = document.getElementById("tkSubtotalRow");
        const el_tkIvaDesglose = document.getElementById("tkIvaDesglose");
        const el_tkTotal = document.getElementById("tkTotal");

        const vTotal = parseFloat(v.total) || 0;
        const vSubtotal = parseFloat(v.subtotal) || 0;
        const vDescAmt = parseFloat(v.descuento_amt) || 0;
        const vDescPct = parseFloat(v.descuento_pct) || 0;
        const factorDesc = (vDescAmt > 0 || vDescPct > 0) && vSubtotal > 0
            ? vTotal / vSubtotal
            : 1;

        const ivaGrupos = {};
        v.lineas.forEach((l) => {
            if (l.devuelta) return;
            const rate = parseFloat(l.iva_aplicado ?? 21);
            const pvpDesc = parseFloat(l.total_linea) * factorDesc;
            const base = pvpDesc / (1 + rate / 100);
            const tax = pvpDesc - base;
            if (!ivaGrupos[rate]) ivaGrupos[rate] = { base: 0, tax: 0 };
            ivaGrupos[rate].base += base;
            ivaGrupos[rate].tax += tax;
        });

        let displayTotal = Object.values(ivaGrupos).reduce((acc, g) => acc + g.base + g.tax, 0);

        // [NUEVO] Aplicar descuento de puntos si existe
        const puntosDescuentoAmt = parseFloat(v.puntos_descuento_amt || 0);
        if (puntosDescuentoAmt > 0) {
            displayTotal = Math.max(0, displayTotal - puntosDescuentoAmt);
        }

        if (el_tkTotal) el_tkTotal.textContent = fmt2(displayTotal);
        if (el_tkSubtotalRow) el_tkSubtotalRow.style.display = "none";

        if (el_tkIvaDesglose) {
            el_tkIvaDesglose.innerHTML = Object.entries(ivaGrupos)
                .sort(([a], [b]) => parseFloat(a) - parseFloat(b))
                .map(([rate, data]) => `
                    <div class="ticket-total-row label text-muted">
                        <span>Base imponible (${rate}%)</span><span>${fmt2(data.base)}</span>
                    </div>
                    <div class="ticket-total-row label text-muted">
                        <span>IVA ${rate}%</span><span>${fmt2(data.tax)}</span>
                    </div>
                `).join("");
        }

        // Reset tabs
        if (typeof window.switchTicketTab === 'function') {
            window.switchTicketTab('summary');
        }

        // Fill Points Info
        const el_tkPointsEarned = document.getElementById("tkPointsEarnedTotal");
        if (el_tkPointsEarned) el_tkPointsEarned.textContent = v.puntos_ganados || 0;

        const el_tkPointsRedeemed = document.getElementById("tkPointsRedeemed");
        if (el_tkPointsRedeemed) el_tkPointsRedeemed.textContent = v.puntos_canjeados || 0;

        const el_tkPointsTotal = document.getElementById("tkPointsTotalBalance");
        if (el_tkPointsTotal) el_tkPointsTotal.textContent = v.puntos_cliente_total || (v.cliente_puntos !== undefined ? v.cliente_puntos : "—");

        document.getElementById("ticketModal").classList.add("visible");
    },

    printTicket() {
        if (!this.currentTicketNum) {
            Utils.showToast("No hay ticket cargado", "error");
            return;
        }
        window.open(`./api/imprimirTicket.php?id=${this.currentTicketNum}`, "_blank");
    },

    downloadPDF() {
        if (!this.currentTicketNum) {
            Utils.showToast("No hay ticket cargado", "error");
            return;
        }

        const isFactura = document.getElementById("facturaToggle")?.checked || false;
        const tipo = isFactura ? 'factura' : 'ticket';
        const formattedNum = document.getElementById("tkNum")?.textContent || this.currentTicketNum;
        const filename = `${tipo === 'factura' ? 'Factura' : 'Ticket'}_${formattedNum}.pdf`;

        Utils.showToast("Generando PDF...", "info");

        const a = document.createElement('a');
        a.href = `./api/generarPDFTicket.php?id=${this.currentTicketNum}&tipo=${tipo}`;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);

        Utils.showToast("PDF descargado", "success");
    },

    async sendEmail() {
        const email = document.getElementById("tkEmailInput")?.value.trim() || "";
        if (!email || !email.includes("@")) {
            Utils.showToast("Email inválido", "error");
            return;
        }

        if (!this.currentTicketNum) {
            Utils.showToast("No hay ticket cargado", "error");
            return;
        }

        const isFactura = document.getElementById("facturaToggle")?.checked || false;
        const tipo = isFactura ? 'factura' : 'ticket';

        Utils.showToast("Enviando correo...", "info");

        try {
            const resp = await fetch("./api/enviarVentaEmail.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    numTicket: this.currentTicketNum,
                    destinatario: email,
                    tipo: tipo,
                }),
            });
            const data = await resp.json();

            if (data.ok) {
                Utils.showToast("Enviado con éxito", "success");
            } else {
                throw new Error(data.error);
            }
        } catch (err) {
            console.error("Error email:", err);
            Utils.showToast("Error al enviar: " + err.message, "error");
        }
    }
};