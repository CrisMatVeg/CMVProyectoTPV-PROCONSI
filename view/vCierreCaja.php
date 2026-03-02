</header>
<div class="main-full container container-wider p-24 mt-24">

    <?php if ($avCierreCaja['mensajeExito']): ?>
        <div class="bg-green-light text-green p-16 br-12 mb-24 d-flex ai-center gap-12 fs-14 font-bold">
            <i class="fa-solid fa-circle-check fs-20"></i>
            <?php echo $avCierreCaja['mensajeExito']; ?>
        </div>
    <?php endif; ?>

    <!-- TÍTULO -->
    <div class="section-header">
        <div>
            <h1 class="section-title">Cierre de Caja</h1>
            <div class="text-muted fs-13 mt-4">
                Resumen del día · <?php echo $avCierreCaja['fecha']; ?> · <?php echo $avCierreCaja['nombre_completo']; ?>
            </div>
        </div>
        <div class="d-flex gap-8">
            <button onclick="window.print()" class="btn-icon w-auto h-auto gap-8 fs-13 p-9-18">
                <i class="fa-solid fa-print"></i> Imprimir
            </button>
            <form method="post" action="index.php" class="m-0">
                <input type="hidden" name="paginaAnterior" value="cierreCaja">
                <button type="submit" name="irInicio" class="btn-save p-9-18 fs-13">
                    ← Volver al TPV
                </button>
            </form>
        </div>
    </div>

    <!-- RESUMEN EN CARDS -->
    <div class="grid-4 gap-14 mb-28">
        <div class="stat-card">
            <div class="summary-label">Ventas</div>
            <div class="fs-28 font-bold font-mono mt-6">
                <?php echo $avCierreCaja['resumen']['totalVentas']; ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="summary-label">Efectivo en caja</div>
            <div class="fs-22 font-bold font-mono mt-6 text-green">
                <?php echo number_format($avCierreCaja['resumen']['totalEfectivo'], 2, ',', '.'); ?> €
            </div>
        </div>
        <div class="stat-card">
            <div class="summary-label">Ventas a cuenta / tarjeta</div>
            <div class="fs-22 font-bold font-mono mt-6 text-blue">
                <?php echo number_format($avCierreCaja['resumen']['totalTarjeta'], 2, ',', '.'); ?> €
            </div>
        </div>
        <div class="stat-card bg-accent border-accent">
            <div class="summary-label text-white-70">Total recaudado</div>
            <div class="fs-22 font-bold font-mono mt-6 text-white">
                <?php echo number_format($avCierreCaja['resumen']['totalBruto'], 2, ',', '.'); ?> €
            </div>
        </div>
    </div>

    <!-- IVA DESGLOSADO -->
    <div class="table-container p-18 mb-28 d-flex gap-32 br-12">
        <div>
            <div class="summary-label">Total base imponible</div>
            <div class="fs-18 font-bold font-mono mt-4">
                <?php echo number_format($avCierreCaja['resumen']['totalBruto'] - $avCierreCaja['resumen']['totalIVA'], 2, ',', '.'); ?> €
            </div>
        </div>
        <div>
            <div class="summary-label">IVA recaudado (21%)</div>
            <div class="fs-18 font-bold font-mono text-accent mt-4">
                <?php echo number_format($avCierreCaja['resumen']['totalIVA'], 2, ',', '.'); ?> €
            </div>
        </div>
    </div>

    <!-- APERTURA Y RETIRADAS DE CAJA -->
    <div class="table-container p-24 mb-28 border-2">
        <h3 class="mb-16 d-flex ai-center gap-10">
            <i class="fa-solid fa-cash-register text-accent"></i> 
            Gestión de Caja (Apertura y Retiradas)
        </h3>

        <?php if (!$avCierreCaja['turno']): ?>
            <form method="post" class="d-grid grid-3 gap-16">
                <div class="form-group">
                    <label class="form-label fs-13">Fondo inicial de caja (€)</label>
                    <input type="number" step="0.01" name="fondoInicial" class="form-input font-mono" placeholder="0.00" required>
                </div>
                <div class="form-group d-flex ai-flex-end">
                    <button type="submit" name="abrirCaja" class="btn-save w-auto p-12-24">
                        <i class="fa-solid fa-door-open"></i> Abrir caja
                    </button>
                </div>
                <div class="form-group fs-12 text-muted">
                    Registra aquí el efectivo que dejas al inicio del turno. Se tendrá en cuenta para el arqueo final.
                </div>
            </form>
        <?php else: ?>
            <div class="d-grid grid-3 gap-24 mb-16">
                <div>
                    <div class="summary-label">Fondo inicial del turno</div>
                    <div class="fs-18 font-mono font-bold mt-4">
                        <?php echo number_format($avCierreCaja['fondoInicial'], 2, ',', '.'); ?> €
                    </div>
                </div>
                <div>
                    <div class="summary-label">Retirado durante el turno</div>
                    <div class="fs-18 font-mono font-bold mt-4 text-red">
                        <?php echo number_format($avCierreCaja['totalRetirado'], 2, ',', '.'); ?> €
                    </div>
                </div>
                <div>
                    <div class="summary-label">Efectivo esperado según turno</div>
                    <div class="fs-18 font-mono font-bold mt-4 text-blue">
                        <?php echo number_format($avCierreCaja['esperadoTurno'], 2, ',', '.'); ?> €
                    </div>
                </div>
            </div>

            <form method="post" class="d-grid grid-3 gap-16">
                <div class="form-group">
                    <label class="form-label fs-13">Registrar retirada de efectivo</label>
                    <input type="number" step="0.01" name="importeRetiro" class="form-input font-mono" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label class="form-label fs-13">Concepto</label>
                    <input type="text" name="conceptoRetiro" class="form-input" placeholder="Caja fuerte, banco, etc.">
                </div>
                <div class="form-group d-flex ai-flex-end">
                    <button type="submit" name="registrarRetiro" class="btn-cancel">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Registrar retirada
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <!-- ARQUEO DE CAJA (Z) -->
    <div class="table-container p-24 mb-28 border-2" style="border-color: var(--accent);">
        <h3 class="mb-16 d-flex ai-center gap-10">
            <i class="fa-solid fa-vault text-accent"></i> 
            Arqueo de Caja y Cierre Fiscal (Z)
        </h3>
        <form method="post" id="formCierre">
            <div class="grid-3 gap-24">
                <div class="form-group">
                    <label class="form-label fs-13">Efectivo esperado (ventas + fondo - retiradas)</label>
                    <div class="fs-20 font-mono font-bold">
                        <?php echo number_format($avCierreCaja['esperadoTurno'], 2, ',', '.'); ?> €
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label fs-13">Efectivo real en caja</label>
                    <input type="number" step="0.01" name="realEfectivo" id="realEfectivo" class="form-input fs-18 font-mono" placeholder="0.00" oninput="calcularDiferencia()" required>
                    <input type="hidden" name="totalTarjeta" value="<?php echo $avCierreCaja['resumen']['totalTarjeta']; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label fs-13">Diferencia / Descuadre</label>
                    <div id="diffCaja" class="fs-20 font-mono font-bold">0,00 €</div>
                </div>
            </div>
            <div class="grid-2 gap-24 mt-16">
                <div class="form-group">
                    <label class="form-label fs-13">Fondo para siguiente turno</label>
                    <input type="number" step="0.01" name="fondoSiguiente" id="fondoSiguiente" class="form-input fs-16 font-mono" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label class="form-label fs-13">Importe que se retira al cierre</label>
                    <div id="importeRetiradaCierre" class="fs-20 font-mono font-bold">0,00 €</div>
                </div>
            </div>
            <div class="mt-20 d-flex jc-between ai-center">
                <p class="fs-12 text-muted max-w-500">
                    Al confirmar, se registrará el cierre en el historial fiscal y se asignará un número de Reporte Z. Las ventas actuales quedarán marcadas como cerradas.
                </p>
                <button type="submit" name="doCierre" class="btn-save w-auto p-12-24 bg-accent">
                    Confirmar Cierre y Generar Z
                </button>
            </div>
        </form>
    </div>

    <script>
    function calcularDiferencia() {
        const esperado = <?php echo $avCierreCaja['esperadoTurno']; ?>;
        const realInput = document.getElementById('realEfectivo');
        const real = parseFloat(realInput.value) || 0;
        const diff = real - esperado;
        const el = document.getElementById('diffCaja');
        el.innerText = (diff >= 0 ? '+' : '') + diff.toFixed(2).replace('.', ',') + ' €';
        el.className = 'fs-20 font-mono font-bold ' + (diff === 0 ? 'text-green' : 'text-red');

        // Calcular importe que se retira al cierre según fondo que se deja
        const fondoSig = parseFloat(document.getElementById('fondoSiguiente').value) || 0;
        const retiradoCierre = Math.max(0, real - fondoSig);
        const elRet = document.getElementById('importeRetiradaCierre');
        elRet.innerText = retiradoCierre.toFixed(2).replace('.', ',') + ' €';

        // Validación suave: no dejar un fondo superior al efectivo real en caja
        if (fondoSig > real) {
            realInput.setCustomValidity('No puedes dejar un fondo superior al efectivo real en caja.');
        } else {
            realInput.setCustomValidity('');
        }
    }

    document.getElementById('fondoSiguiente')?.addEventListener('input', calcularDiferencia);
    </script>


    <div class="table-container">
        <div class="p-14-20 border-bottom font-bold fs-13">
            Tickets del día
        </div>
        <?php if (empty($avCierreCaja['ventas'])): ?>
            <div class="empty-state p-32">
                No hay ventas registradas hoy.
            </div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="pl-20">Ticket</th>
                        <th>Hora</th>
                        <th>Cliente</th>
                        <th>Pago</th>
                        <th class="text-right">Base imp.</th>
                        <th class="text-right">IVA</th>
                        <th class="text-right pr-20">Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($avCierreCaja['ventas'] as $i => $v): ?>
                    <tr>
                        <td class="pl-20 ticket-num">
                            #<?php echo str_pad($v['numero_ticket'], 4, '0', STR_PAD_LEFT); ?>
                        </td>
                        <td class="font-mono text-muted">
                            <?php echo date('H:i', strtotime($v['fecha'])); ?>
                        </td>
                        <td>
                            <?php if ($v['tipo_cliente'] === 'empresa'): ?>
                                <span class="status-pill status-active p-4-8 fs-11 gap-5">
                                    <i class="fa-solid fa-building"></i> <?php echo htmlspecialchars($v['nombre_cliente'] ?? 'Empresa'); ?>
                                </span>
                            <?php else: ?>
                                <span class="fs-11 text-muted d-inline-flex ai-center gap-5">
                                    <i class="fa-solid fa-user"></i> Particular
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($v['metodo_pago'] === 'efectivo'): ?>
                                <span class="status-pill status-active p-4-8 fs-11 gap-5 bg-green-light text-green">
                                    <i class="fa-solid fa-money-bill-1-wave"></i> Efectivo
                                </span>
                            <?php else: ?>
                                <span class="status-pill status-active p-4-8 fs-11 gap-5 bg-blue-light text-blue-light">
                                    <i class="fa-solid fa-credit-card"></i> Tarjeta
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right font-mono">
                            <?php echo number_format($v['base_imponible'], 2, ',', '.'); ?> €
                        </td>
                        <td class="text-right font-mono text-accent">
                            <?php echo number_format($v['iva_amt'], 2, ',', '.'); ?> €
                        </td>
                        <td class="text-right font-mono font-bold pr-20">
                            <?php echo number_format($v['total'], 2, ',', '.'); ?> €
                        </td>
                        <td class="text-center">
                            <button title="Ver ticket/factura" class="btn-icon" onclick="verTicket(<?php echo $v['numero_ticket']; ?>)">
                                <i class="fa-solid fa-receipt"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>    </div>

</div>
