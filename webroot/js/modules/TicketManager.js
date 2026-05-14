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
        
        // Sincronizar los globales que usan los botones de main.js (imprimir, PDF, anular)
        window.currentTicketNum = v.numero_ticket;
        window.currentVentaId   = v.id;
        window.currentVentaPendiente = parseFloat(v.total) - parseFloat(v.pagado_a_cuenta || 0);

        const fmt2 = Utils.fmt2;
        const el_tkTipoDoc = document.getElementById("tkTipoDoc");
        if (el_tkTipoDoc) {
            const isFactura = v.tipo_cliente === 'empresa' || v.es_factura == 1;
            const isAbono = (v.tipo_documento || 'venta') === 'abono';
            if (isAbono) el_tkTipoDoc.textContent = window.I18N?.ticket_type_abono || "TICKET DE ABONO";
            else el_tkTipoDoc.textContent = isFactura ? (window.I18N?.invoice || "FACTURA") : (window.I18N?.ticket || "TICKET DE VENTA");
        }

        const el_tkNum = document.getElementById("tkNumero");
        if (el_tkNum) {
            const esFactura = v.tipo_cliente === 'empresa' || v.es_factura == 1;
            const tipoDoc   = v.tipo_documento || 'venta';
            el_tkNum.textContent = formatTicketNumber(v.numero_ticket, v.fecha, esFactura, tipoDoc);
            // Mostrar badge visual para abonos
            const badgeAbono = document.getElementById('tkBadgeAbono');
            if (badgeAbono) badgeAbono.style.display = tipoDoc === 'abono' ? 'inline-flex' : 'none';
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

                const appliedDiscounts = l.descuentos || [];
                const discountBadges = appliedDiscounts
                  .filter(d => {
                    // No mostrar si es un cupón (ya sale en el total general)
                    if (d.tipo_descuento === 'cupon') return false;
                    // No mostrar si el nombre coincide exactamente con el descuento general del ticket
                    if (v.descuento_label && (d.nombre === v.descuento_label || d.nombre_descuento === v.descuento_label)) return false;
                    return true;
                  })
                  .map(d => {
                    const dName = d.nombre || d.nombre_descuento || 'Desc.';
                    const isTarifa = d.tipo_descuento === 'tarifa';
                    const bgColor = isTarifa ? 'bg-accent-soft' : 'bg-green-light';
                    const textColor = isTarifa ? 'text-accent' : 'text-green';
                    const icon = isTarifa ? 'fa-tag' : 'fa-percent';
                    return `
                      <span class="fs-9 px-6 py-2 br-4 ${bgColor} ${textColor} fw-800 tt-uppercase d-inline-flex ai-center gap-4" style="border: 1px solid rgba(var(--${isTarifa ? 'accent' : 'green'}-rgb), 0.1);">
                        <i class="fa-solid ${icon} fs-8 opacity-70"></i> ${dName}
                      </span>
                    `;
                  }).join("");

                return `
          <div style="display:flex; justify-content:space-between; padding: 10px 0; border-bottom: 1px solid var(--surface2); ${l.devuelta ? "opacity:0.6; background:rgba(192,57,43,0.05);" : ""}">
            <div style="flex:1">
              <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                <div>
                  <span style="font-weight:600; ${l.devuelta ? "text-decoration:line-through;" : ""}">${l.nombre_producto}</span>
                  <span style="color:var(--text-muted); font-size:11px; margin-left:6px;">${l.codigo_producto}</span>
                  <div style="display:flex; gap:4px; margin-top:2px;">${discountBadges}</div>
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
        const vPuntosAmt = parseFloat(v.puntos_descuento_amt) || 0;
        const vDescPct = parseFloat(v.descuento_pct || 0);
        
        const ivaGrupos = {};
        v.lineas.forEach((l) => {
            if (l.devuelta) return;
            const rate = parseFloat(l.iva_aplicado ?? 21);
            // Usamos precio_unitario × cantidad (precio BRUTO antes de descuentos globales).
            // Los descuentos (puntos, cupones) se muestran como ítems separados en el ticket.
            const pvpBruto = parseFloat(l.precio_unitario) * parseFloat(l.cantidad);
            const base = pvpBruto / (1 + rate / 100);
            const tax = pvpBruto - base;
            if (!ivaGrupos[rate]) ivaGrupos[rate] = { base: 0, tax: 0 };
            ivaGrupos[rate].base += base;
            ivaGrupos[rate].tax += tax;
        });

        // El displayTotal ahora es simplemente lo que suma el desglose (que cuadra con v.total)
        let displayTotal = Object.values(ivaGrupos).reduce((acc, g) => acc + g.base + g.tax, 0);

        if (el_tkTotal) el_tkTotal.textContent = fmt2(vTotal);
        
        if (el_tkSubtotalRow) {
            el_tkSubtotalRow.style.display = "flex";
            const el_tkSubtotalVal = document.getElementById("tkSubtotal");
            if (el_tkSubtotalVal) el_tkSubtotalVal.textContent = fmt2(vSubtotal);
        }

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

        const el_tkDescRow = document.getElementById("tkDescRow");
        if (el_tkDescRow) {
            const diff = Math.max(0, vSubtotal - vTotal);
            // Si hay un descuento explícito o una diferencia entre subtotal y total
            if (vDescAmt > 0 || diff > 0.01) {
                el_tkDescRow.classList.remove("d-none");
                el_tkDescRow.style.display = "flex";
                const el_tkDescLabel = document.getElementById("tkDescLabel");
                const el_tkDescAmt = document.getElementById("tkDescAmt");
                
                if (el_tkDescLabel) {
                    if (v.descuento_label) {
                        el_tkDescLabel.textContent = `Descuento (${v.descuento_label})`;
                    } else if (vDescPct > 0) {
                        el_tkDescLabel.textContent = `Descuento (${vDescPct}%)`;
                    } else {
                        el_tkDescLabel.textContent = "Descuento";
                    }
                }
                if (el_tkDescAmt) {
                    const amt = diff > 0.01 ? diff : vDescAmt;
                    el_tkDescAmt.textContent = "−" + fmt2(amt);
                }
            } else {
                el_tkDescRow.classList.add("d-none");
                el_tkDescRow.style.display = "none";
            }
        }

        const el_tkPuntosRow = document.getElementById("tkPuntosRow");
        if (el_tkPuntosRow) {
            if (vPuntosAmt > 0) {
                el_tkPuntosRow.classList.remove("d-none");
                el_tkPuntosRow.innerHTML = `<span><i class="fa-solid fa-star text-gold"></i> Puntos canjeados (${v.puntos_canjeados})</span><span>-${fmt2(vPuntosAmt)}</span>`;
            } else {
                el_tkPuntosRow.classList.add("d-none");
            }
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

        const el_tkEmail = document.getElementById("tkEmailInput");
        if (el_tkEmail) {
            el_tkEmail.value = v.cliente_email || "";
        }

        // --- Comentarios / Observaciones ---
        const el_tkCommentsSection = document.getElementById("tkCommentsSection");
        const el_tkCommentsText = document.getElementById("tkCommentsText");
        if (el_tkCommentsSection && el_tkCommentsText) {
            if (v.comentarios && v.comentarios.trim() !== "") {
                el_tkCommentsSection.classList.remove("d-none");
                // Construcción DOM segura: evita XSS con datos de usuario
                el_tkCommentsText.innerHTML = '';
                const strong = document.createElement('strong');
                strong.textContent = 'Observaciones:';
                el_tkCommentsText.appendChild(strong);
                el_tkCommentsText.appendChild(document.createElement('br'));
                v.comentarios.split('\n').forEach((linea, i, arr) => {
                    el_tkCommentsText.appendChild(document.createTextNode(linea));
                    if (i < arr.length - 1) el_tkCommentsText.appendChild(document.createElement('br'));
                });
            } else {
                el_tkCommentsSection.classList.add("d-none");
                el_tkCommentsText.innerHTML = '';
            }
        }

        // --- Multi-Payment Breakdown ---
        const el_tkPagosSection = document.getElementById("tkPagosSection");
        const el_tkPagosLista = document.getElementById("tkPagosLista");
        
        if (el_tkPagosSection && el_tkPagosLista) {
            const pagos = v.pagos || [];
            if (pagos.length > 0) {
                el_tkPagosSection.classList.remove("d-none");
                
                const methodIcons = {
                    efectivo: '<i class="fa-solid fa-money-bill-wave" style="color:var(--green)"></i>',
                    tarjeta: '<i class="fa-solid fa-credit-card" style="color:var(--accent)"></i>',
                    bizum: '<i class="fa-solid fa-mobile-screen-button" style="color:var(--accent2)"></i>',
                    vale: '<i class="fa-solid fa-ticket" style="color:var(--orange)"></i>',
                    puntos: '<i class="fa-solid fa-star" style="color:var(--gold)"></i>',
                    a_cuenta: '<i class="fa-solid fa-clock-rotate-left" style="color:var(--text-muted)"></i>'
                };

                el_tkPagosLista.innerHTML = pagos
                    .filter(p => (p.metodo || p.metodo_pago) !== 'puntos')
                    .map(p => {
                        const method = p.metodo || p.metodo_pago || 'efectivo';
                        const icon = methodIcons[method] || '<i class="fa-solid fa-circle-dollar-to-slot"></i>';
                        const label = method.charAt(0).toUpperCase() + method.slice(1).replace('_', ' ');
                        
                        return `
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                                <span style="display: flex; align-items: center; gap: 8px;">
                                    ${icon}
                                    <span style="font-weight: 500;">${label}</span>
                                </span>
                                <span style="font-family:'DM Mono',monospace; font-weight: 700;">${fmt2(p.importe)}</span>
                            </div>
                        `;
                    }).join("");

                // Show change if cash was received (using global efectivo_recibido from sales header)
                const totalImporteEfectivo = pagos
                    .filter(p => (p.metodo || p.metodo_pago) === 'efectivo')
                    .reduce((sum, p) => sum + parseFloat(p.importe || 0), 0);
                const efRecibido = parseFloat(v.efectivo_recibido || 0);
                
                if (totalImporteEfectivo > 0 && efRecibido > 0) {
                    if (efRecibido > totalImporteEfectivo) {
                        const cambio = efRecibido - totalImporteEfectivo;
                        el_tkPagosLista.innerHTML += `
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; margin-top: 8px; border-top: 1px dashed var(--border); padding-top: 4px; opacity: 0.8;">
                                <span style="font-weight: 500;">Efectivo entregado:</span>
                                <span style="font-family:'DM Mono',monospace;">${fmt2(efRecibido)}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; font-weight: 700; color: var(--green);">
                                <span style="font-weight: 600;">Cambio devuelto:</span>
                                <span style="font-family:'DM Mono',monospace;">${fmt2(cambio)}</span>
                            </div>
                        `;
                    }
                }
            } else {
                el_tkPagosSection.classList.add("d-none");
            }
        }

        // --- Botones de acción (Heredado de mostrarTicket en main.js) ---
        const btnAnular = document.getElementById("btnAnularTicket");
        const btnNuevaVenta = document.getElementById("btnNuevaVenta");

        if (btnAnular) {
            const isAbono = (v.tipo_documento || 'venta') === 'abono';
            // Mostrar botón anular si es historial, completada y no es un abono
            btnAnular.style.display = (!isFromTPV && (v.estado === "completada" || v.estado === "parcialmente_devuelta") && !isAbono) ? "flex" : "none";
            btnAnular.onclick = () => {
                if (typeof window.abrirModalAnulacionTicket === 'function') {
                    window.abrirModalAnulacionTicket(v.numero_ticket, v.fecha, v.id_cliente);
                }
            };
        }

        if (btnNuevaVenta) {
            btnNuevaVenta.style.display = isFromTPV ? "flex" : "none";
        }

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
            const data = await ApiService.request("./api/enviarVentaEmail.php", {
                method: "POST",
                body: JSON.stringify({
                    numTicket: this.currentTicketNum,
                    destinatario: email,
                    tipo: tipo,
                }),
            });

            if (data.ok) {
                Utils.showToast("Enviado con éxito", "success");
            } else {
                throw new Error(data.error || "Error al enviar el correo");
            }
        } catch (err) {
            console.error("Error email:", err);
            Utils.showToast(err.message, "error");
        }
    }
};