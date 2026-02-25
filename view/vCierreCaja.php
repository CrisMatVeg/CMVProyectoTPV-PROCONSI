</header>
<div class="main container-full" style="padding: 24px; max-width: 900px; margin: 0 auto;">

    <!-- TÍTULO -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 700; margin: 0;">Cierre de Caja</h1>
            <div style="color: var(--text-muted); font-size: 13px; margin-top: 4px;">
                Resumen del día · <?php echo $avCierreCaja['fecha']; ?> · <?php echo $avCierreCaja['nombre_completo']; ?>
            </div>
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" style="padding: 9px 18px; border-radius: 8px; border: 1.5px solid var(--border); background: var(--surface); font-size: 13px; cursor: pointer;">
                🖨️ Imprimir
            </button>
            <form method="post" action="index.php" style="margin: 0;">
                <input type="hidden" name="paginaAnterior" value="cierreCaja">
                <button type="submit" name="irInicio" style="padding: 9px 18px; border-radius: 8px; border: none; background: var(--accent); color: #fff; font-size: 13px; font-weight: 600; cursor: pointer;">
                    ← Volver al TPV
                </button>
            </form>
        </div>
    </div>

    <!-- RESUMEN EN CARDS -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 28px;">
        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 18px;">
            <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em;">Ventas realizadas</div>
            <div style="font-size: 28px; font-weight: 700; font-family: 'DM Mono', monospace; margin-top: 6px;">
                <?php echo $avCierreCaja['resumen']['totalVentas']; ?>
            </div>
        </div>
        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 18px;">
            <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em;">Efectivo</div>
            <div style="font-size: 22px; font-weight: 700; font-family: 'DM Mono', monospace; margin-top: 6px; color: var(--green);">
                <?php echo number_format($avCierreCaja['resumen']['totalEfectivo'], 2, ',', '.'); ?> €
            </div>
        </div>
        <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 18px;">
            <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em;">Tarjeta</div>
            <div style="font-size: 22px; font-weight: 700; font-family: 'DM Mono', monospace; margin-top: 6px; color: var(--accent2);">
                <?php echo number_format($avCierreCaja['resumen']['totalTarjeta'], 2, ',', '.'); ?> €
            </div>
        </div>
        <div style="background: var(--accent); border-radius: 12px; padding: 18px;">
            <div style="font-size: 11px; color: rgba(255,255,255,0.7); text-transform: uppercase; letter-spacing: 0.06em;">Total recaudado</div>
            <div style="font-size: 22px; font-weight: 700; font-family: 'DM Mono', monospace; margin-top: 6px; color: #fff;">
                <?php echo number_format($avCierreCaja['resumen']['totalBruto'], 2, ',', '.'); ?> €
            </div>
        </div>
    </div>

    <!-- IVA DESGLOSADO -->
    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 18px; margin-bottom: 28px; display: flex; gap: 32px;">
        <div>
            <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em;">Total base imponible</div>
            <div style="font-size: 18px; font-weight: 600; font-family: 'DM Mono', monospace; margin-top: 4px;">
                <?php echo number_format($avCierreCaja['resumen']['totalBruto'] - $avCierreCaja['resumen']['totalIVA'], 2, ',', '.'); ?> €
            </div>
        </div>
        <div>
            <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em;">IVA recaudado (21%)</div>
            <div style="font-size: 18px; font-weight: 600; font-family: 'DM Mono', monospace; color: var(--gold); margin-top: 4px;">
                <?php echo number_format($avCierreCaja['resumen']['totalIVA'], 2, ',', '.'); ?> €
            </div>
        </div>
    </div>

    <!-- LISTADO DE TICKETS -->
    <div style="background: var(--surface); border: 1px solid var(--border); border-radius: 12px; overflow: hidden;">
        <div style="padding: 14px 20px; border-bottom: 1px solid var(--border); font-weight: 600; font-size: 13px;">
            Tickets del día
        </div>
        <?php if (empty($avCierreCaja['ventas'])): ?>
            <div style="padding: 32px; text-align: center; color: var(--text-muted); font-size: 14px;">
                No hay ventas registradas hoy.
            </div>
        <?php else: ?>
            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background: var(--surface2); color: var(--text-muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em;">
                        <th style="padding: 10px 20px; text-align: left;">Ticket</th>
                        <th style="padding: 10px; text-align: left;">Hora</th>
                        <th style="padding: 10px; text-align: left;">Cliente</th>
                        <th style="padding: 10px; text-align: left;">Pago</th>
                        <th style="padding: 10px; text-align: right;">Base imp.</th>
                        <th style="padding: 10px 20px; text-align: right;">IVA</th>
                        <th style="padding: 10px 20px; text-align: right; font-weight: 700;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($avCierreCaja['ventas'] as $i => $v): ?>
                    <tr style="border-top: 1px solid var(--border); <?php echo $i % 2 === 0 ? '' : 'background: var(--surface2);'; ?>">
                        <td style="padding: 10px 20px; font-family: 'DM Mono', monospace; font-weight: 600;">
                            #<?php echo str_pad($v['numero_ticket'], 4, '0', STR_PAD_LEFT); ?>
                        </td>
                        <td style="padding: 10px; color: var(--text-muted);">
                            <?php echo date('H:i', strtotime($v['creado_en'])); ?>
                        </td>
                        <td style="padding: 10px;">
                            <?php if ($v['tipo_cliente'] === 'empresa'): ?>
                                <span style="font-size: 11px; background: var(--blue-light); color: var(--accent); padding: 2px 6px; border-radius: 4px; font-weight: 600;">🏢 <?php echo htmlspecialchars($v['nombre_cliente'] ?? 'Empresa'); ?></span>
                            <?php else: ?>
                                <span style="font-size: 11px; color: var(--text-muted);">👤 Particular</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 10px;">
                            <?php echo $v['metodo_pago'] === 'efectivo' ? '💵 Efectivo' : '💳 Tarjeta'; ?>
                        </td>
                        <td style="padding: 10px; text-align: right; font-family: 'DM Mono', monospace;">
                            <?php echo number_format($v['base_imponible'], 2, ',', '.'); ?> €
                        </td>
                        <td style="padding: 10px 20px; text-align: right; font-family: 'DM Mono', monospace; color: var(--gold);">
                            <?php echo number_format($v['iva_amt'], 2, ',', '.'); ?> €
                        </td>
                        <td style="padding: 10px 20px; text-align: right; font-family: 'DM Mono', monospace; font-weight: 700;">
                            <?php echo number_format($v['total'], 2, ',', '.'); ?> €
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>
