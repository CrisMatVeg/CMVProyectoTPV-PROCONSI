</header>
<div class="main-full container container-wider p-24 mt-24">

    <?php if ($avCierreCaja['mensajeExito']): ?>
        <div class="bg-green-light text-green p-16 br-12 mb-24 d-flex ai-center gap-12 fs-14 font-bold">
            <i class="fa-solid fa-circle-check fs-20"></i>
            <?php echo $avCierreCaja['mensajeExito']; ?>
        </div>
    <?php endif; ?>

    <div class="printable-area">
        <!-- TÍTULO -->
        <div class="section-header">
            <div>
                <h1 class="section-title">Cierre de Caja</h1>
                <div class="text-muted fs-13 mt-4">
                    Resumen del día · <?php echo $avCierreCaja['fecha']; ?> · <?php echo $avCierreCaja['nombre_completo']; ?>
                </div>
            </div>
            <div class="d-flex gap-8 no-print">
                <form method="post" action="index.php" class="m-0">
                    <input type="hidden" name="paginaAnterior" value="cierreCaja">
                    <button type="submit" name="irInicio" class="btn-save p-9-18 fs-13">
                        ← Volver al TPV
                    </button>
                </form>
            </div>
        </div>

        <!-- BLOQUE 1: RESUMEN DE VENTAS Y PAGOS -->
        <div class="mb-32">
            <h3 class="mb-20 d-flex ai-center gap-10">
                <div class="w-32 h-32 br-50 bg-accent-light text-accent d-flex ai-center jc-center"><i class="fa-solid fa-chart-pie"></i></div>
                Resumen de Ingresos
            </h3>

            <div class="grid-2 gap-20 mb-20">
                <div class="stat-card bg-surface border-2 pt-24 pb-24 d-flex flex-column jc-center ai-center shadow-sm">
                    <div class="summary-label text-muted fs-13 tt-uppercase mb-8">Base Imponible (Sin IVA)</div>
                    <div class="fs-28 font-bold font-mono text-text">
                        <?php echo number_format($avCierreCaja['resumen']['totalBruto'] - $avCierreCaja['resumen']['totalIVA'], 2, ',', '.'); ?> €
                    </div>
                </div>
                <div class="stat-card bg-accent border-accent pt-24 pb-24 d-flex flex-column jc-center ai-center shadow-sm">
                    <div class="summary-label text-white-70 fs-13 tt-uppercase mb-8">Total Recaudado (Con IVA)</div>
                    <div class="fs-32 font-bold font-mono text-white">
                        <?php echo number_format($avCierreCaja['resumen']['totalBruto'], 2, ',', '.'); ?> €
                    </div>
                </div>
            </div>

            <div class="grid-4 gap-16">
                <div class="stat-card bg-surface border-2 text-center pt-20 pb-20 shadow-sm">
                    <div class="summary-label text-green fs-13 tt-uppercase mb-8"><i class="fa-solid fa-money-bill-1-wave"></i> Efectivo</div>
                    <div class="fs-24 font-bold font-mono text-green">
                        <?php echo number_format($avCierreCaja['resumen']['totalEfectivo'], 2, ',', '.'); ?> €
                    </div>
                </div>
                <div class="stat-card bg-surface border-2 text-center pt-20 pb-20 shadow-sm">
                    <div class="summary-label text-blue fs-13 tt-uppercase mb-8"><i class="fa-solid fa-credit-card"></i> Tarjeta</div>
                    <div class="fs-24 font-bold font-mono text-blue">
                        <?php echo number_format($avCierreCaja['resumen']['totalTarjeta'], 2, ',', '.'); ?> €
                    </div>
                </div>
                <div class="stat-card bg-surface border-2 text-center pt-20 pb-20 shadow-sm">
                    <div class="summary-label text-accent fs-13 tt-uppercase mb-8"><i class="fa-solid fa-mobile-screen-button"></i> Bizum</div>
                    <div class="fs-24 font-bold font-mono text-accent">
                        <?php echo number_format($avCierreCaja['resumen']['totalBizum'], 2, ',', '.'); ?> €
                    </div>
                </div>
                <div class="stat-card bg-surface border-2 text-center pt-20 pb-20 shadow-sm">
                    <div class="summary-label fs-13 tt-uppercase mb-8" style="color: #6c5ce7;"><i class="fa-solid fa-calendar-check"></i> Financiación</div>
                    <div class="fs-24 font-bold font-mono" style="color: #6c5ce7;">
                        <?php echo number_format($avCierreCaja['resumen']['totalFinanciado'], 2, ',', '.'); ?> €
                    </div>
                </div>
            </div>
            <div class="p-16 bg-blue-light text-blue border br-8 d-flex ai-center gap-10 mt-16 fs-13">
                <i class="fa-solid fa-circle-info fs-18"></i>
                <span>Los cobros por <strong>Tarjeta, Bizum y Financiación</strong> van directamente a la cuenta bancaria, por lo que no afectan al cuadre de efectivo físico en caja.</span>
            </div>
        </div>

        <!-- BLOQUE 2: FLUJO DE EFECTIVO EN CAJA -->
        <div class="mb-32">
            <h3 class="mb-20 d-flex ai-center gap-10">
                <div class="w-32 h-32 br-50 bg-green-light text-green d-flex ai-center jc-center"><i class="fa-solid fa-cash-register"></i></div>
                Flujo de Efectivo en el Cajón
            </h3>

            <?php if (!$avCierreCaja['turno']): ?>
                <div class="p-32 br-12 border-2 bg-surface d-flex flex-column ai-center text-center shadow-sm">
                    <div class="w-64 h-64 br-50 bg-surface2 d-flex ai-center jc-center fs-24 text-muted mb-16 shadow-sm"><i class="fa-solid fa-door-open"></i></div>
                    <h4 class="mb-8 fs-18">Apertura de turno requerida</h4>
                    <p class="text-muted fs-14 mb-24 max-w-500">Para poder registrar el cierre de caja, necesitas tener un turno abierto. Por favor, abre la caja registrando el fondo inicial o el cambio que has dejado al abrir.</p>
                    <form method="post" class="d-flex ai-center gap-16 bg-surface2 p-16 br-12 border">
                        <div class="d-flex ai-center gap-10 border br-8 bg-surface px-16 h-48 shadow-sm">
                            <i class="fa-solid fa-money-bill-1-wave text-muted"></i>
                            <input type="number" step="0.01" name="fondoInicial" class="form-input border-0 bg-transparent h-100 p-0 font-mono fs-18 font-bold" style="width: 120px;" placeholder="0.00" required>
                            <span class="font-bold text-muted fs-18">€</span>
                        </div>
                        <button type="submit" name="abrirCaja" class="btn-save h-48 px-24 fs-14">
                            <i class="fa-solid fa-unlock-keyhole"></i> Abrir caja
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="d-grid grid-4 gap-0 mb-20 border-2 br-12 bg-surface shadow-sm overflow-hidden text-center">
                    <!-- Fórmula Visual -->
                    <div class="p-24 border-right d-flex flex-column jc-center ai-center">
                        <div class="summary-label fs-12 tt-uppercase text-muted mb-8 d-flex ai-center gap-8">
                            Fondo inicial (Apertura)
                            <div class="w-20 h-20 br-50 bg-surface2 d-flex ai-center jc-center fs-10 text-muted" style="width: 20px; height: 20px;"><i class="fa-solid fa-plus"></i></div>
                        </div>
                        <div class="fs-24 font-mono font-bold text-text">
                            <?php echo number_format($avCierreCaja['fondoInicial'], 2, ',', '.'); ?> €
                        </div>
                    </div>

                    <div class="p-24 border-right d-flex flex-column jc-center ai-center">
                        <div class="summary-label fs-12 tt-uppercase text-muted mb-8 d-flex ai-center gap-8">
                            Cobrado en Efectivo
                            <div class="w-20 h-20 br-50 bg-green-light text-green d-flex ai-center jc-center fs-10" style="width: 20px; height: 20px;"><i class="fa-solid fa-plus"></i></div>
                        </div>
                        <div class="fs-24 font-mono font-bold text-green">
                            <?php echo number_format($avCierreCaja['resumen']['totalEfectivo'], 2, ',', '.'); ?> €
                        </div>
                    </div>

                    <div class="p-24 border-right d-flex flex-column jc-center ai-center">
                        <div class="summary-label fs-12 tt-uppercase text-muted mb-8 d-flex ai-center gap-8">
                            Retiradas del Turno
                            <div class="w-20 h-20 br-50 bg-red-light text-red d-flex ai-center jc-center fs-10" style="width: 20px; height: 20px;"><i class="fa-solid fa-minus"></i></div>
                        </div>
                        <div class="fs-24 font-mono font-bold text-red">
                            <?php echo number_format($avCierreCaja['totalRetirado'], 2, ',', '.'); ?> €
                        </div>
                    </div>

                    <div class="p-24 d-flex flex-column jc-center ai-center bg-accent-light" style="background: rgba(var(--accent-rgb), 0.05);">
                        <div class="summary-label fs-12 tt-uppercase text-accent mb-8 d-flex ai-center gap-8 font-bold">
                            Efectivo Esperado
                            <div class="w-20 h-20 br-50 bg-accent text-white d-flex ai-center jc-center fs-10" style="width: 20px; height: 20px;"><i class="fa-solid fa-equals"></i></div>
                        </div>
                        <div class="fs-28 font-mono font-bold text-accent">
                            <?php echo number_format($avCierreCaja['esperadoTurno'], 2, ',', '.'); ?> €
                        </div>
                    </div>
                </div>

                <!-- Formularios de Retiradas Integrado -->
                <div class="p-24 br-12 border-2 bg-surface2">
                    <div class="fs-14 font-bold mb-16 d-flex ai-center gap-10">
                        <i class="fa-solid fa-hand-holding-dollar text-muted fs-18"></i>
                        Registrar un gasto o retirada de la caja física
                    </div>
                    <form method="post" class="d-flex ai-end gap-16">
                        <div class="form-group flex-1 mb-0">
                            <label class="form-label fs-12">Concepto del gasto</label>
                            <input type="text" name="conceptoRetiro" class="form-input h-48" placeholder="Ej. Pago a proveedor, banco..." required>
                        </div>
                        <div class="form-group mb-0" style="width: 200px;">
                            <label class="form-label fs-12">Importe (€)</label>
                            <input type="number" step="0.01" name="importeRetiro" class="form-input font-mono h-48 font-bold" placeholder="0.00" required>
                        </div>
                        <div class="form-group mb-0">
                            <button type="submit" name="registrarRetiro" class="btn-cancel bg-surface h-48 px-24 font-bold border-2">
                                Registrar
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- BLOQUE 3: ARQUEO DE CAJA Y CIERRE (Z) -->
        <div class="mb-32">
            <h3 class="mb-20 d-flex ai-center gap-10">
                <div class="w-32 h-32 br-50 bg-surface text-muted d-flex ai-center jc-center font-bold fs-16 shadow-sm border-2">Z</div>
                Cierre Fiscal y Cuadre Final
            </h3>

            <div class="p-32 border-2 br-12 bg-surface shadow-sm" style="border-color: var(--accent);">
                <form method="post" id="formCierre">
                    <div class="grid-2 gap-32 border-bottom pb-32 mb-32">

                        <!-- PASO 1: Conteo real -->
                        <div class="d-flex flex-column gap-16">
                            <div class="d-flex ai-center gap-12 mb-8">
                                <div class="w-28 h-28 br-50 bg-accent text-white d-flex ai-center jc-center font-bold fs-14" style="width: 28px; height: 28px;">1</div>
                                <div class="fs-14 font-bold tt-uppercase text-muted">¿Cuánto efectivo has contado?</div>
                            </div>
                            <div class="p-24 bg-surface2 br-12 border d-flex ai-center jc-between gap-16 flex-1">
                                <i class="fa-solid fa-coins text-accent fs-32 opacity-50"></i>
                                <div class="d-flex ai-center gap-8 border-2 br-8 bg-surface px-16 h-56 shadow-sm flex-1" style="max-width: 200px;">
                                    <input type="number" step="0.01" name="realEfectivo" id="realEfectivo" class="form-input border-0 bg-transparent h-100 p-0 font-mono fs-24 font-bold text-accent text-right w-100 outline-none" placeholder="0.00" oninput="calcularDiferencia()" required>
                                    <span class="fs-22 font-bold text-muted">€</span>
                                </div>
                            </div>

                            <!-- DESCUADRE DINÁMICO -->
                            <div class="bg-surface2 br-12 p-16 text-center border">
                                <div class="fs-11 tt-uppercase text-muted font-bold mb-4">Descuadre</div>
                                <div id="diffCaja" class="fs-24 font-mono font-bold text-muted">0,00 €</div>
                                <div class="fs-10 text-muted mt-4 italic">Se esperaba <?php echo number_format($avCierreCaja['esperadoTurno'], 2, ',', '.'); ?> €</div>
                            </div>
                        </div>

                        <!-- PASO 2: Fondo para mañana -->
                        <div class="d-flex flex-column gap-16">
                            <div class="d-flex ai-center gap-12 mb-8">
                                <div class="w-28 h-28 br-50 bg-accent text-white d-flex ai-center jc-center font-bold fs-14" style="width: 28px; height: 28px;">2</div>
                                <div class="fs-14 font-bold tt-uppercase text-muted">Fondo para el próximo turno</div>
                            </div>
                            <div class="p-24 bg-surface2 br-12 border d-flex ai-center jc-between gap-16 flex-1">
                                <i class="fa-solid fa-piggy-bank text-muted fs-32 opacity-50"></i>
                                <div class="d-flex ai-center gap-8 border-2 br-8 bg-surface px-16 h-56 shadow-sm flex-1" style="max-width: 200px;">
                                    <input type="number" step="0.01" name="fondoSiguiente" id="fondoSiguiente" class="form-input border-0 bg-transparent h-100 p-0 font-mono fs-24 font-bold text-text text-right w-100 outline-none" placeholder="0.00" required>
                                    <span class="fs-22 font-bold text-muted">€</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PASO 3: Cantidad a sacar (Calculado) -->
                    <div class="d-flex ai-center jc-between bg-surface2 p-32 br-12 border flex-wrap gap-24" style="background: linear-gradient(145deg, var(--surface2) 0%, rgba(var(--accent-rgb), 0.05) 100%);">
                        <div class="d-flex ai-center gap-24 flex-1" style="min-width: 300px;">
                            <div class="w-64 h-64 br-12 bg-surface shadow-sm d-flex ai-center jc-center border-2" style="flex-shrink: 0;"><i class="fa-solid fa-arrow-right-from-bracket text-accent fs-32"></i></div>
                            <div>
                                <div class="fs-14 tt-uppercase text-accent font-bold mb-4">Llevarte en mano / Ingresar al banco</div>
                                <div id="importeRetiradaCierre" class="fs-40 font-mono font-bold" style="letter-spacing: -1px;">0,00 €</div>
                            </div>
                        </div>
                        <div class="text-right d-flex flex-column ai-end gap-12" style="min-width: 300px;">
                            <input type="hidden" name="totalTarjeta" value="<?php echo $avCierreCaja['resumen']['totalTarjeta']; ?>">
                            <input type="hidden" name="totalBizum" value="<?php echo $avCierreCaja['resumen']['totalBizum']; ?>">
                            <input type="hidden" name="totalFinanciado" value="<?php echo $avCierreCaja['resumen']['totalFinanciado']; ?>">

                            <div class="d-flex gap-12">
                                <button type="submit" name="doCierreTurno" class="btn-cancel h-56 px-24 fs-14 border-2 shadow-sm hover-scale d-flex ai-center gap-10 m-0" style="background: var(--surface2);">
                                    <i class="fa-solid fa-clock-rotate-left"></i> Solo Cerrar Turno
                                </button>
                                <button type="submit" name="doCierreZ" class="btn-save h-56 px-32 fs-16 shadow-lg hover-scale d-flex ai-center gap-10 m-0" style="background: var(--green); border-color: var(--green); white-space: nowrap;">
                                    <i class="fa-solid fa-check-double fs-18"></i> <span class="font-bold">Realizar Cierre de Caja / Jornada</span>
                                </button>
                            </div>
                            <div class="fs-11 text-muted max-w-400">
                                <b>Cerrar Turno:</b> Solo cierra tu caja actual sin archivar el día. <br>
                                <b>Cierre de Caja:</b> Archiva todas las ventas, cierra el turno y genera el informe fiscal diario.
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function calcularDiferencia() {
                const esperado = <?php echo $avCierreCaja['esperadoTurno']; ?>;
                const realInput = document.getElementById('realEfectivo');
                const real = parseFloat(realInput.value) || 0;
                const diff = real - esperado;
                const el = document.getElementById('diffCaja');
                el.innerText = (diff >= 0 ? '+' : '') + diff.toFixed(2).replace('.', ',') + ' €';
                el.className = 'fs-24 font-mono font-bold ' + (diff === 0 ? 'text-green' : 'text-red');

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


        <!-- TICKETS DEL DÍA -->
        <div class="p-24 border-2 br-12 bg-surface mb-28" style="background: var(--surface);">
            <h3 class="mb-16 d-flex ai-center gap-10">
                <i class="fa-solid fa-receipt text-muted"></i>
                Tickets Emitidos Hoy
            </h3>
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
                                    <?php elseif ($v['metodo_pago'] === 'bizum'): ?>
                                        <span class="status-pill status-active p-4-8 fs-11 gap-5 bg-accent-light text-accent">
                                            <i class="fa-solid fa-mobile-screen-button"></i> Bizum
                                        </span>
                                    <?php elseif ($v['metodo_pago'] === 'financiado'): ?>
                                        <span class="status-pill status-active p-4-8 fs-11 gap-5" style="background: #efecff; color: #6c5ce7;">
                                            <i class="fa-solid fa-calendar-check"></i> Financiación
                                        </span>
                                    <?php else: ?>
                                        <span class="status-pill status-active p-4-8 fs-11 gap-5 bg-blue-light text-blue">
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
                                    <button type="button" title="Ver ticket/factura" class="btn-icon" onclick="verTicket(<?php echo $v['numero_ticket']; ?>)">
                                        <i class="fa-solid fa-receipt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>
</div>