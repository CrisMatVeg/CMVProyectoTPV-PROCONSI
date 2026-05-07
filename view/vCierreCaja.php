</header>
<style>
    .cierre-center-container {
        width: 100%;
        margin: 0 auto;
    }



    .stat-card {
        height: 100%;
    }
    
    .caja-flow-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1px;
        background: var(--border-color);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        overflow: hidden;
    }
    
    .caja-flow-item {
        background: var(--surface);
        padding: 24px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        transition: all 0.2s ease;
    }
    
    .caja-flow-item:hover {
        background: var(--surface2);
    }
    
    .caja-flow-item.total-highlight {
        background: rgba(var(--accent-rgb), 0.05);
    }

</style>
<?php if (!isset($avCierreCaja) || !is_array($avCierreCaja)) return; ?>
<div class="main-full p-24 cierre-center-container">
    <div class="container-wider">


    <?php if ($avCierreCaja['mensajeExito']): ?>
        <div class="bg-green-light text-green p-20 br-20 mb-32 d-flex ai-center gap-16 fs-15 font-bold shadow-sm border" style="border-color: rgba(46, 204, 113, 0.2); background: linear-gradient(145deg, rgba(46, 204, 113, 0.05) 0%, rgba(46, 204, 113, 0.1) 100%);">
            <div class="w-40 h-40 br-10 bg-green text-white d-flex ai-center jc-center shadow-sm">
                <i class="fa-solid fa-check-circle fs-20"></i>
            </div>
            <?php echo $avCierreCaja['mensajeExito']; ?>
        </div>
    <?php endif; ?>

    <?php if ($avCierreCaja['mensajeError']): ?>
        <div class="bg-red-light text-red p-20 br-20 mb-32 d-flex ai-center gap-16 fs-15 font-bold shadow-sm border" style="border-color: rgba(231, 76, 60, 0.2); background: linear-gradient(145deg, rgba(231, 76, 60, 0.05) 0%, rgba(231, 76, 60, 0.1) 100%);">
            <div class="w-40 h-40 br-10 bg-red text-white d-flex ai-center jc-center shadow-sm">
                <i class="fa-solid fa-circle-xmark fs-20"></i>
            </div>
            <?php echo $avCierreCaja['mensajeError']; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($avCierreCaja['pendientesArqueo']) && !isset($avCierreCaja['modoEdicionPendiente'])): ?>
        <div class="bg-orange-light text-orange p-32 mb-32 border-2 d-flex flex-column gap-24 shadow-md no-print" style="border-color: rgba(230, 126, 34, 0.3); background: linear-gradient(145deg, rgba(230, 126, 34, 0.05) 0%, rgba(230, 126, 34, 0.1) 100%); border-radius: 20px;">
            <div class="d-flex ai-center gap-20">
                <div class="w-56 h-56 bg-orange text-white d-flex ai-center jc-center shadow-lg" style="transform: rotate(-5deg); border-radius: 16px;">
                    <i class="fa-solid fa-vault fs-28"></i>
                </div>
                <div class="flex-1">
                    <h3 class="m-0 fs-22 font-bold" style="letter-spacing: -0.5px;"><?php echo L('cash_pending_vault_title'); ?></h3>
                    <p class="m-0 fs-14 opacity-80 mt-4 max-w-700"><?php echo L('cash_pending_vault_sub'); ?></p>
                </div>
            </div>

            <div class="grid-2 gap-20 mt-8">
                <?php foreach ($avCierreCaja['pendientesArqueo'] as $pa): ?>
                    <div class="bg-surface p-24 border d-flex ai-center jc-between shadow-sm transition-all hover-translate-y" style="border-color: rgba(230, 126, 34, 0.15); background: var(--surface); gap: 20px; border-radius: 20px;">
                        <div class="d-flex ai-center gap-20">
                            <div class="w-48 h-48 bg-surface2 text-orange d-flex ai-center jc-center shadow-xs border" style="border-radius: 14px;">
                                <i class="fa-solid fa-clock-rotate-left fs-20"></i>
                            </div>
                            <div>
                                <div class="fs-15 fw-800 text-text"><?php echo L('cash_pending_shift_label'); ?>: <?php echo date('d/m/Y', strtotime($pa['fecha_apertura'])); ?> <span class="opacity-50 fw-400 ml-4"><?php echo date('H:i', strtotime($pa['fecha_apertura'])); ?></span></div>
                                <div class="fs-12 text-muted mt-6 d-flex ai-center gap-8">
                                    <i class="fa-solid fa-user-circle opacity-50"></i>
                                    <span><?php echo L('cash_pending_opened_by'); ?> <span class="font-bold text-text"><?php echo htmlspecialchars($pa['nombre_usuario_apertura'] ?? L('tpv_unknown', true)); ?></span></span>
                                </div>
                            </div>
                        </div>
                        <form method="post" action="index.php?irCierreCaja" class="m-0">
                            <input type="hidden" name="idTurnoPendiente" value="<?php echo $pa['id']; ?>">
                            <button type="submit" name="realizarArqueoPendiente" class="btn-save bg-orange border-0 fs-13 p-12-24 d-flex ai-center gap-12 shadow-sm hover-scale-sm" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); white-space: nowrap; border-radius: 12px;">
                                <i class="fa-solid fa-coins"></i> <?php echo L('cash_pending_btn_arqueo'); ?>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="printable-area">
        <!-- TÍTULO -->
        <div class="section-header pb-24 border-bottom mb-40 d-flex ai-center jc-between flex-wrap gap-20">
            <div class="d-flex ai-center gap-24">
                <div class="w-72 h-72 br-18 bg-surface2 d-flex ai-center jc-center shadow-sm border">
                    <i class="fa-solid fa-cash-register fs-32 text-accent"></i>
                </div>
                <div>
                    <?php if (isset($avCierreCaja['modoEdicionPendiente']) && $avCierreCaja['modoEdicionPendiente']): ?>
                        <h1 class="section-title text-orange m-0 fs-32 fw-800" style="letter-spacing: -1px;"><?php echo L('cash_close_title_arqueo'); ?></h1>
                        <div class="text-muted fs-15 mt-6 d-flex ai-center gap-12">
                            <span class="bg-orange-light text-orange px-10 py-4 br-8 fw-700 fs-11 tt-uppercase ls-1 shadow-xs"><?php echo L('cash_close_label_recovered'); ?></span>
                            <span class="opacity-70"><?php echo L('cash_close_label_shift_of'); ?></span> <span class="font-bold text-text"><?php echo date('d/m/Y H:i', strtotime($avCierreCaja['turno']['fecha_apertura'])); ?></span> ·
                            <span class="font-bold text-text"><?php echo htmlspecialchars($avCierreCaja['turno']['nombre_usuario_apertura'] ?? L('tpv_unknown', true)); ?></span>
                        </div>
                    <?php else: ?>
                        <h1 class="section-title m-0 fs-32 fw-800" style="letter-spacing: -1px;"><?php echo L('cash_close_title_z'); ?></h1>
                        <div class="text-muted fs-15 mt-6 d-flex ai-center gap-12">
                            <div class="d-flex ai-center gap-6 bg-surface2 px-12 py-4 br-8 border shadow-xs">
                                <i class="fa-solid fa-calendar-day text-accent opacity-70"></i>
                                <?php echo L('cash_close_label_day'); ?> <span class="font-bold text-text ml-4"><?php echo $avCierreCaja['fecha']; ?></span>
                            </div>
                            <div class="d-flex ai-center gap-6 bg-surface2 px-12 py-4 br-8 border shadow-xs">
                                <i class="fa-solid fa-user-tie text-accent opacity-70"></i>
                                <?php echo L('cash_close_label_cashier'); ?>: <span class="font-bold text-text ml-4"><?php echo $avCierreCaja['nombre_completo']; ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex gap-16 no-print">
                <?php if (isset($avCierreCaja['modoEdicionPendiente']) && $avCierreCaja['modoEdicionPendiente']): ?>
                    <a href="index.php?irCierreCaja" class="btn-cancel bg-surface d-flex ai-center gap-10 shadow-sm transition-all hover-translate-y" style="text-decoration: none; border: 1px solid var(--border); color: var(--text-muted); height: 48px; padding: 0 24px; border-radius: 12px; font-weight: 600;">
                        <i class="fa-solid fa-times"></i> <?php echo L('modal_cancel'); ?>
                    </a>
                <?php endif; ?>
                <form method="post" action="index.php" class="m-0">
                    <input type="hidden" name="paginaAnterior" value="cierreCaja">
                    <button type="submit" name="irInicio" class="btn-prominent-back" style="height: 48px; padding: 0 28px; border-radius: 12px;">
                        <i class="fa-solid fa-arrow-left-long mr-8"></i> <?php echo L('cash_close_btn_return_tpv'); ?>
                    </button>
                </form>
            </div>
        </div>


        <!-- BLOQUE 1: RESUMEN DE VENTAS Y PAGOS -->
        <div class="mb-32">
            <h3 class="mb-20 d-flex ai-center gap-10">
                <div class="w-32 h-32 br-50 bg-accent-light text-accent d-flex ai-center jc-center shadow-xs border"><i class="fa-solid fa-chart-pie"></i></div>
                <?php echo L('cash_close_section_income'); ?>
            </h3>

            <div class="grid-2 gap-24 mb-24">
                <div class="stat-card bg-surface border pt-40 pb-40 d-flex flex-column jc-center ai-center shadow-sm br-20 transition-all hover-translate-y">
                    <div class="summary-label text-muted fs-12 tt-uppercase fw-700 mb-12" style="letter-spacing: 1px; opacity: 0.6;"><?php echo L('cash_close_label_base'); ?></div>
                    <div class="fs-34 font-bold font-mono text-text">
                        <?php echo number_format($avCierreCaja['resumen']['totalBruto'] - $avCierreCaja['resumen']['totalIVA'], 2, ',', '.'); ?> <span class="fs-18 fw-500 opacity-40">€</span>
                    </div>
                </div>
                <div class="stat-card bg-accent border-accent pt-40 pb-40 d-flex flex-column jc-center ai-center shadow-lg br-20 transition-all hover-scale-sm" style="background: linear-gradient(135deg, var(--accent) 0%, #2980b9 100%);">
                    <div class="summary-label text-white-70 fs-12 tt-uppercase fw-700 mb-12" style="letter-spacing: 1px;"><?php echo L('cash_close_label_total'); ?></div>
                    <div class="fs-48 font-bold font-mono text-white">
                        <?php echo number_format($avCierreCaja['resumen']['totalBruto'], 2, ',', '.'); ?> <span class="fs-24 fw-500 opacity-70">€</span>
                    </div>
                </div>
            </div>

            <div class="grid-3 gap-16">
                <div class="stat-card bg-surface border text-center pt-24 pb-24 br-20 shadow-sm transition-all hover-translate-y">
                    <div class="summary-label text-green fs-12 tt-uppercase fw-600 mb-10"><i class="fa-solid fa-money-bill-1-wave"></i> <?php echo L('tpv_method_cash'); ?></div>
                    <div class="fs-26 font-bold font-mono text-green">
                        <?php echo number_format($avCierreCaja['resumen']['totalEfectivo'], 2, ',', '.'); ?> <span class="fs-14 fw-500 opacity-50">€</span>
                    </div>
                </div>
                <div class="stat-card bg-surface border text-center pt-24 pb-24 br-20 shadow-sm transition-all hover-translate-y">
                    <div class="summary-label text-blue fs-12 tt-uppercase fw-600 mb-10"><i class="fa-solid fa-credit-card"></i> <?php echo L('tpv_method_card'); ?></div>
                    <div class="fs-26 font-bold font-mono text-blue">
                        <?php echo number_format($avCierreCaja['resumen']['totalTarjeta'], 2, ',', '.'); ?> <span class="fs-14 fw-500 opacity-50">€</span>
                    </div>
                </div>
                <div class="stat-card bg-surface border text-center pt-24 pb-24 br-20 shadow-sm transition-all hover-translate-y">
                    <div class="summary-label text-accent fs-12 tt-uppercase fw-600 mb-10"><i class="fa-solid fa-mobile-screen-button"></i> <?php echo L('tpv_method_bizum'); ?></div>
                    <div class="fs-26 font-bold font-mono text-accent">
                        <?php echo number_format($avCierreCaja['resumen']['totalBizum'], 2, ',', '.'); ?> <span class="fs-14 fw-500 opacity-50">€</span>
                    </div>
                </div>
            </div>
            <div class="p-16 bg-blue-light text-blue border br-20 d-flex ai-center gap-12 mt-20 fs-13 shadow-xs">
                <i class="fa-solid fa-circle-info fs-20 opacity-70"></i>
                <span class="flex-1"><?php echo L('cash_close_info_banking'); ?></span>
            </div>
        </div>

        <!-- BLOQUE 2: FLUJO DE EFECTIVO EN CAJA -->
        <div class="mb-48">
            <h3 class="mb-24 d-flex ai-center gap-12">
                <div class="w-40 h-40 br-12 bg-green-light text-green d-flex ai-center jc-center shadow-xs border"><i class="fa-solid fa-cash-register fs-18"></i></div>
                <span class="fs-20 fw-800" style="letter-spacing: -0.5px;"><?php echo L('cash_close_section_flow'); ?></span>
            </h3>


            <?php if (!$avCierreCaja['turno']): ?>
                <div class="p-48 br-20 border-2 bg-surface d-flex flex-column ai-center text-center shadow-sm">
                    <div class="w-80 h-80 br-50 bg-surface2 d-flex ai-center jc-center fs-32 text-muted mb-20 shadow-sm border">
                        <i class="fa-solid fa-<?php echo $avCierreCaja['esRelevo'] ? 'user-clock' : 'door-open'; ?>"></i>
                    </div>
                    <h4 class="mb-12 fs-22 font-bold"><?php echo $avCierreCaja['esRelevo'] ? L('cash_close_relevo_title', true) : L('cash_close_apertura_title', true); ?></h4>

                    <?php if ($avCierreCaja['esRelevo']): ?>
                        <div class="p-16 bg-blue-light text-blue border br-12 d-flex ai-center gap-12 mb-24 fs-14 shadow-xs max-w-500">
                            <i class="fa-solid fa-lock fs-18 opacity-70"></i>
                            <span><?php echo L('cash_close_relevo_info'); ?></span>
                        </div>
                    <?php else: ?>
                        <p class="text-muted fs-15 mb-32 max-w-500"><?php echo L('cash_close_apertura_info', true, ['{amount}' => number_format($avCierreCaja['fondoSugerido'], 2, ',', '.')]); ?></p>
                    <?php endif; ?>

                    <form method="post" class="d-flex ai-center gap-20 bg-surface2 p-24 br-20 border shadow-xs">
                        <div class="d-flex ai-center gap-12 border br-16 bg-surface px-20 h-56 shadow-sm">
                            <i class="fa-solid fa-money-bill-1-wave text-muted fs-20"></i>
                            <input type="number" step="0.01" name="fondoInicial"
                                class="form-input border-0 bg-transparent h-100 p-0 font-mono fs-24 font-bold"
                                style="width: 140px;"
                                placeholder="0.00"
                                value="<?= number_format($avCierreCaja['fondoSugerido'], 2, '.', '') ?>"
                                required>
                            <span class="font-bold text-muted fs-20">€</span>
                        </div>
                        <button type="submit" name="abrirCaja" class="btn-save h-56 px-32 fs-15 font-bold shadow-sm hover-scale">
                            <i class="fa-solid fa-unlock-keyhole mr-8"></i> <?php echo $avCierreCaja['esRelevo'] ? L('cash_close_btn_enter_shift', true) : L('cash_close_btn_open_cash', true); ?>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="caja-flow-grid mb-32 shadow-sm">
                    <!-- Fórmula Visual -->
                    <div class="caja-flow-item">
                        <div class="summary-label fs-11 tt-uppercase text-muted mb-12 d-flex ai-center gap-8 fw-600">
                            <?php echo L('cash_close_label_initial_fund'); ?>
                            <div class="w-20 h-20 br-50 bg-surface2 d-flex ai-center jc-center fs-10 text-muted shadow-xs"><i class="fa-solid fa-plus"></i></div>
                        </div>
                        <div class="fs-28 font-mono font-bold text-text">
                            <?php echo number_format($avCierreCaja['fondoInicial'], 2, ',', '.'); ?> <span class="fs-16 fw-500 opacity-40 text-text">€</span>
                        </div>
                        <div class="fs-10 text-muted mt-4 tt-uppercase opacity-60"><?php echo htmlspecialchars($avCierreCaja['turno']['nombre_usuario_apertura'] ?? L('tpv_unknown', true)); ?></div>
                    </div>

                    <div class="caja-flow-item">
                        <div class="summary-label fs-11 tt-uppercase text-muted mb-12 d-flex ai-center gap-8 fw-600">
                            <?php echo L('cash_close_label_collected_cash'); ?>
                            <div class="w-20 h-20 br-50 bg-green-light text-green d-flex ai-center jc-center fs-10 shadow-xs"><i class="fa-solid fa-plus"></i></div>
                        </div>
                        <div class="fs-28 font-mono font-bold text-green">
                            <?php echo number_format($avCierreCaja['resumen']['totalEfectivo'], 2, ',', '.'); ?> <span class="fs-16 fw-500 opacity-40">€</span>
                        </div>
                        <div class="fs-10 text-green mt-4 tt-uppercase opacity-70 font-bold"><?php echo L('cash_close_label_shift_sales'); ?></div>
                    </div>

                    <div class="caja-flow-item">
                        <div class="summary-label fs-11 tt-uppercase text-muted mb-12 d-flex ai-center gap-8 fw-600">
                            <?php echo L('cash_close_label_manual_income'); ?>
                            <div class="w-20 h-20 br-50 bg-blue-light text-accent d-flex ai-center jc-center fs-10 shadow-xs"><i class="fa-solid fa-plus"></i></div>
                        </div>
                        <div class="fs-28 font-mono font-bold text-accent">
                            <?php echo number_format($avCierreCaja['totalIngresado'] ?? 0, 2, ',', '.'); ?> <span class="fs-16 fw-500 opacity-40">€</span>
                        </div>
                        <div class="fs-10 text-accent mt-4 tt-uppercase opacity-70 font-bold"><?php echo L('cash_close_label_manual_entries'); ?></div>
                    </div>

                    <div class="caja-flow-item">
                        <div class="summary-label fs-11 tt-uppercase text-muted mb-12 d-flex ai-center gap-8 fw-600">
                            <?php echo L('cash_close_label_withdrawals'); ?>
                            <div class="w-20 h-20 br-50 bg-red-light text-red d-flex ai-center jc-center fs-10 shadow-xs"><i class="fa-solid fa-minus"></i></div>
                        </div>
                        <div class="fs-28 font-mono font-bold text-red">
                            <?php echo number_format($avCierreCaja['totalRetirado'], 2, ',', '.'); ?> <span class="fs-16 fw-500 opacity-40">€</span>
                        </div>
                        <div class="fs-10 text-red mt-4 tt-uppercase opacity-70 font-bold"><?php echo L('cash_close_label_cash_outs'); ?></div>
                    </div>

                    <div class="caja-flow-item total-highlight">
                        <div class="summary-label fs-11 tt-uppercase text-accent mb-12 d-flex ai-center gap-8 font-bold">
                            <?php echo L('cash_close_label_expected_cash'); ?>
                            <div class="w-24 h-24 br-50 bg-accent text-white d-flex ai-center jc-center fs-12 shadow-sm"><i class="fa-solid fa-equals"></i></div>
                        </div>
                        <div class="fs-34 font-mono font-bold text-accent">
                            <?php echo number_format($avCierreCaja['esperadoTurno'], 2, ',', '.'); ?> <span class="fs-18 fw-500 opacity-60">€</span>
                        </div>
                    </div>
                </div>


                <!-- Formularios de Retiradas/Ingresos Integrados -->
                <?php if (empty($avCierreCaja['pendientesArqueo'])): ?>
                    <div class="d-grid grid-2 gap-24 mb-40">
                        <!-- RETIRADA -->
                        <div class="p-24 br-20 border bg-surface shadow-sm transition-all hover-translate-y">
                            <div class="fs-14 font-bold mb-16 d-flex ai-center gap-10">
                                <div class="w-32 h-32 br-10 bg-red-light text-red d-flex ai-center jc-center shadow-xs border"><i class="fa-solid fa-hand-holding-dollar fs-14"></i></div>
                                <?php echo L('cash_close_register_withdrawal'); ?>
                            </div>
                            <form method="post" class="d-flex flex-column gap-20">
                                <div class="d-flex gap-16">
                                    <div class="form-group mb-0" style="flex: 2;">
                                        <label class="form-label fs-11 fw-700 opacity-60 tt-uppercase"><?php echo L('cash_close_label_concept'); ?></label>
                                        <input type="text" name="conceptoRetiro" class="form-input h-48 px-16 br-10" placeholder="<?php echo L('cash_close_placeholder_withdrawal'); ?>" required>
                                    </div>
                                    <div class="form-group mb-0" style="flex: 1;">
                                        <label class="form-label fs-11 fw-700 opacity-60 tt-uppercase"><?php echo L('cash_close_label_amount'); ?> (€)</label>
                                        <input type="number" step="0.01" min="0.01" name="importeRetiro" class="form-input font-mono h-48 px-16 br-10 font-bold" placeholder="0.00" required>
                                    </div>
                                </div>
                                <button type="submit" name="registrarRetiro" class="btn-save bg-red font-bold shadow-md hover-scale-sm w-100" style="height: 48px; font-size: 14px; border-radius: 12px;">
                                    <i class="fa-solid fa-arrow-up-from-bracket mr-8"></i> <?php echo L('cash_close_btn_withdraw'); ?>
                                </button>
                            </form>
                        </div>

                        <!-- INGRESO -->
                        <div class="p-24 br-20 border bg-surface shadow-sm transition-all hover-translate-y">
                            <div class="fs-14 font-bold mb-16 d-flex ai-center gap-10">
                                <div class="w-32 h-32 br-10 bg-accent-light text-accent d-flex ai-center jc-center shadow-xs border"><i class="fa-solid fa-piggy-bank fs-14"></i></div>
                                <?php echo L('cash_close_register_income'); ?>
                            </div>
                            <form method="post" class="d-flex flex-column gap-20">
                                <div class="d-flex gap-16">
                                    <div class="form-group mb-0" style="flex: 2;">
                                        <label class="form-label fs-11 fw-700 opacity-60 tt-uppercase"><?php echo L('cash_close_label_concept'); ?></label>
                                        <input type="text" name="conceptoIngreso" class="form-input h-48 px-16 br-10" placeholder="<?php echo L('cash_close_placeholder_deposit'); ?>" required>
                                    </div>
                                    <div class="form-group mb-0" style="flex: 1;">
                                        <label class="form-label fs-11 fw-700 opacity-60 tt-uppercase"><?php echo L('cash_close_label_amount'); ?> (€)</label>
                                        <input type="number" step="0.01" min="0.01" name="importeIngreso" class="form-input font-mono h-48 px-16 br-10 font-bold" placeholder="0.00" required>
                                    </div>
                                </div>
                                <button type="submit" name="registrarIngreso" class="btn-save bg-accent font-bold shadow-md hover-scale-sm w-100" style="height: 48px; font-size: 14px; border-radius: 12px;">
                                    <i class="fa-solid fa-arrow-down-to-bracket mr-8"></i> <?php echo L('cash_close_btn_deposit'); ?>
                                </button>
                            </form>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="p-24 br-16 border-2 bg-surface shadow-sm text-center" style="border-style: dashed; opacity: 0.7;">
                        <div class="w-48 h-48 br-50 bg-surface2 text-muted d-flex ai-center jc-center mx-auto mb-12 border">
                            <i class="fa-solid fa-hand-holding-dollar fs-20"></i>
                        </div>
                        <div class="fs-14 font-bold text-muted"><?php echo L('cash_close_locked_title'); ?></div>
                        <p class="fs-12 text-muted mt-4"><?php echo L('cash_close_locked_sub'); ?></p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- BLOQUE 3: ARQUEO DE CAJA Y CIERRE (Z) -->
        <div class="mb-48">
            <h3 class="mb-24 d-flex ai-center gap-12">
                <div class="w-40 h-40 br-12 bg-surface text-muted d-flex ai-center jc-center font-bold fs-20 shadow-sm border-2">Z</div>
                <span class="fs-20 fw-800" style="letter-spacing: -0.5px;"><?php echo L('cash_close_fiscal_title'); ?></span>
            </h3>


            <?php if (!empty($avCierreCaja['pendientesArqueo']) && !isset($avCierreCaja['modoEdicionPendiente'])): ?>
                <div class="p-48 border-2 br-16 bg-surface shadow-sm text-center" style="border-color: var(--accent); background: rgba(var(--accent-rgb), 0.02);">
                    <div class="w-64 h-64 br-50 bg-blue-light text-accent d-flex ai-center jc-center mx-auto mb-16 shadow-xs border">
                        <i class="fa-solid fa-circle-check fs-28"></i>
                    </div>
                    <h4 class="m-0 fs-18 font-bold"><?php echo L('cash_pending_vault_title'); ?></h4>
                    <p class="fs-14 text-muted mt-8 max-w-500 mx-auto"><?php echo L('cash_pending_vault_sub'); ?></p>
                </div>
            <?php else: ?>
                <div class="p-32 border br-20 bg-surface shadow-sm" style="border-color: var(--accent);">
                    <form method="post" id="formCierre">
                        <?php if (isset($avCierreCaja['modoEdicionPendiente']) && $avCierreCaja['modoEdicionPendiente'] && isset($avCierreCaja['turno']['id'])): ?>
                            <input type="hidden" name="idTurnoPendiente" value="<?php echo (int)$avCierreCaja['turno']['id']; ?>">
                        <?php endif; ?>
                        <!-- GRID SUPERIOR 1x2 PARA PASOS 1 Y 2 -->
                        <!-- GRID SUPERIOR 1x3 PARA PASOS 1, 2 Y DESCUADRE -->
                        <div class="grid-3 gap-24 mb-32">
                            <!-- PASO 1: Conteo real -->
                            <div class="d-flex flex-column gap-16 ai-center p-24 bg-surface2 br-20 border shadow-sm transition-all" style="min-height: 180px;">
                                <div class="d-flex ai-center jc-center gap-12">
                                    <div class="w-28 h-28 br-50 bg-accent text-white d-flex ai-center jc-center font-bold fs-14">1</div>
                                    <div class="fs-14 font-bold tt-uppercase text-muted"><?php echo L('cash_close_step_1'); ?></div>
                                </div>
                                <div class="d-flex ai-center gap-8 border br-16 bg-surface px-16 h-64 shadow-sm w-100">
                                    <i class="fa-solid fa-coins text-accent fs-32 opacity-40"></i>
                                    <input type="number" step="0.01" name="realEfectivo" id="realEfectivo" class="form-input border-0 bg-transparent h-100 p-0 font-mono fs-28 font-bold text-accent text-right w-100 outline-none" placeholder="0.00" oninput="calcularDiferencia()" required>
                                    <span class="fs-22 font-bold text-muted">€</span>
                                </div>
                            </div>

                            <!-- PASO 2: Fondo para mañana -->
                            <div class="d-flex flex-column gap-16 ai-center p-24 bg-surface2 br-20 border shadow-sm transition-all" style="min-height: 180px;">
                                <div class="d-flex ai-center jc-center gap-12">
                                    <div class="w-28 h-28 br-50 bg-accent text-white d-flex ai-center jc-center font-bold fs-14">2</div>
                                    <div class="fs-14 font-bold tt-uppercase text-muted"><?php echo L('cash_close_step_2'); ?></div>
                                </div>
                                <div class="d-flex ai-center gap-8 border br-16 bg-surface px-16 h-64 shadow-sm w-100">
                                    <i class="fa-solid fa-piggy-bank text-muted fs-32 opacity-40"></i>
                                    <input type="number" step="0.01" name="fondoSiguiente" id="fondoSiguiente" class="form-input border-0 bg-transparent h-100 p-0 font-mono fs-28 font-bold text-text text-right w-100 outline-none" placeholder="0.00" required>
                                    <span class="fs-22 font-bold text-muted">€</span>
                                </div>
                            </div>

                            <!-- DESCUADRE -->
                            <div class="d-flex flex-column gap-16 ai-center p-24 bg-surface2 br-20 border shadow-md transition-all" style="min-height: 180px; border-top: 4px solid var(--accent);">
                                <div class="fs-11 tt-uppercase text-muted font-bold"><?php echo L('cash_close_label_diff'); ?></div>
                                <div id="diffCaja" class="fs-40 font-mono font-bold text-muted" style="white-space: nowrap; line-height: 1;">0,00 €</div>
                                <div class="fs-13 text-muted mt-auto" style="white-space: nowrap;">
                                    <?php echo L('cash_close_label_was_expected'); ?> <span class="font-bold text-text"><?php echo number_format($avCierreCaja['esperadoTurno'], 2, ',', '.'); ?> €</span>
                                </div>
                            </div>
                        </div>


                        <!-- PASO 3: Cantidad a sacar (Calculado) -->
                        <div class="bg-surface2 p-32 br-20 border shadow-md" style="background: linear-gradient(145deg, var(--surface2) 0%, rgba(var(--accent-rgb), 0.05) 100%);">
                            <div class="d-flex ai-center jc-between flex-wrap gap-32">
                                <div class="d-flex ai-center gap-24">
                                    <div class="w-72 h-72 br-16 bg-surface shadow-sm d-flex ai-center jc-center border" style="flex-shrink: 0;"><i class="fa-solid fa-arrow-right-from-bracket text-accent fs-32"></i></div>
                                    <div>
                                        <div class="fs-14 tt-uppercase text-accent font-bold mb-4"><?php echo L('cash_close_step_3'); ?></div>
                                        <div id="importeRetiradaCierre" class="fs-48 font-mono font-bold" style="letter-spacing: -1px; line-height: 1;">0,00 €</div>
                                    </div>
                                </div>
                                
                                <div class="d-flex flex-column ai-end gap-16">
                                    <div class="d-flex gap-16">
                                        <?php if ($avCierreCaja['canCerrarTurno']): ?>
                                            <button type="submit" name="doCierreTurno" class="btn-cancel h-56 px-24 fs-14 border-2 shadow-sm hover-scale-sm d-flex ai-center gap-10 m-0" style="background: var(--surface); border-radius: 14px;">
                                                <i class="fa-solid fa-clock-rotate-left"></i> <?php echo L('cash_close_btn_only_close_shift'); ?>
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($avCierreCaja['canCerrarCaja']): ?>
                                            <button type="submit" name="doCierreZ" class="btn-save h-56 px-40 fs-16 shadow-lg hover-scale-sm d-flex ai-center gap-10 m-0" style="background: var(--green); border-color: var(--green); white-space: nowrap; border-radius: 14px;">
                                                <i class="fa-solid fa-check-double fs-18"></i> <span class="font-bold"><?php echo L('cash_close_btn_do_z'); ?></span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="fs-12 text-muted text-right max-w-500 opacity-70">
                                        <i class="fa-solid fa-circle-info mr-4"></i>
                                        <?php echo L('cash_close_help_close_shift'); ?> · <?php echo L('cash_close_help_do_z'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
            <?php endif; ?>
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
                    realInput.setCustomValidity("<?php echo L('cash_close_js_invalid_fund'); ?>");
                } else {
                    realInput.setCustomValidity('');
                }
            }

            document.getElementById('fondoSiguiente')?.addEventListener('input', calcularDiferencia);
        </script>


        <!-- TICKETS DEL DÍA -->
        <div class="p-24 border br-20 bg-surface mb-28 shadow-sm" style="background: var(--surface);">
            <h3 class="mb-16 d-flex ai-center gap-10">
                <i class="fa-solid fa-receipt text-muted"></i>
                <?php echo L('cash_close_tickets_title'); ?>
            </h3>
            <?php if (empty($avCierreCaja['ventas'])): ?>
                <div class="empty-state p-32">
                    <?php echo L('cash_close_no_tickets'); ?>
                </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="analitica-table mt-12">
                    <thead>
                        <tr>
                            <th class="pl-24 py-16"><?php echo L('cash_close_th_ticket'); ?></th>
                            <th class="py-16"><?php echo L('cash_close_th_time'); ?></th>
                            <th class="py-16"><?php echo L('cash_close_th_client'); ?></th>
                            <th class="py-16"><?php echo L('cash_close_th_payment'); ?></th>
                            <th class="text-right py-16"><?php echo L('cash_close_th_base'); ?></th>
                            <th class="text-right py-16"><?php echo L('cash_close_th_iva'); ?></th>
                            <th class="text-right pr-24 py-16"><?php echo L('cash_close_th_total'); ?></th>
                            <th class="text-center py-16"><?php echo L('cash_close_th_actions'); ?></th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($avCierreCaja['ventas'] as $i => $v): ?>
                            <tr class="transition-all hover-bg-surface2">
                                <td class="pl-24 py-12 ticket-num font-bold">
                                    #<?php echo $v['numero_ticket']; ?>
                                </td>
                                <td class="font-mono text-muted py-12">
                                    <?php echo date('H:i', strtotime($v['fecha'])); ?>
                                </td>
                                <td class="py-12">
                                    <?php if ($v['tipo_cliente'] === 'empresa'): ?>
                                        <span class="status-pill status-active p-6-10 fs-11 gap-6 border shadow-xs" style="background: rgba(var(--accent-rgb), 0.05); border-color: rgba(var(--accent-rgb), 0.1); color: var(--accent);">
                                            <i class="fa-solid fa-building"></i> <?php echo htmlspecialchars($v['nombre_cliente'] ?? L('client_company', true)); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="fs-12 text-muted d-inline-flex ai-center gap-6 fw-500">
                                            <i class="fa-solid fa-user opacity-50"></i> <?php echo L('client_particular'); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-12">
                                    <?php if ($v['metodo_pago'] === 'efectivo'): ?>
                                        <span class="status-pill status-active p-6-10 fs-11 gap-6 bg-green-light text-green border shadow-xs" style="border-color: rgba(46, 204, 113, 0.2);">
                                            <i class="fa-solid fa-money-bill-1-wave"></i> <?php echo L('tpv_method_cash'); ?>
                                        </span>
                                    <?php elseif ($v['metodo_pago'] === 'bizum'): ?>
                                        <span class="status-pill status-active p-6-10 fs-11 gap-6 bg-accent-light text-accent border shadow-xs" style="border-color: rgba(var(--accent-rgb), 0.2);">
                                            <i class="fa-solid fa-mobile-screen-button"></i> <?php echo L('tpv_method_bizum'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-pill status-active p-6-10 fs-11 gap-6 bg-blue-light text-blue border shadow-xs" style="border-color: rgba(52, 152, 219, 0.2);">
                                            <i class="fa-solid fa-credit-card"></i> <?php echo L('tpv_method_card'); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right font-mono py-12 fs-14">
                                    <?php echo number_format($v['base_imponible'], 2, ',', '.'); ?> €
                                </td>
                                <td class="text-right font-mono text-accent py-12 fs-14">
                                    <?php echo number_format($v['iva_amt'], 2, ',', '.'); ?> €
                                </td>
                                <td class="text-right font-mono font-bold pr-24 py-12 fs-15">
                                    <?php echo number_format($v['total'], 2, ',', '.'); ?> €
                                </td>
                                <td class="text-center py-12">
                                    <button type="button" title="Ver ticket/factura" class="btn-icon w-36 h-36 br-8 hover-scale-sm" onclick="verTicket(<?php echo $v['numero_ticket']; ?>)">
                                        <i class="fa-solid fa-receipt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>


        </div>
    </div>
</div>


<!-- Listener para actualizaciones de caja en tiempo real -->
<script>
    if (typeof BroadcastChannel !== 'undefined') {
        const cashChannel = new BroadcastChannel('tpv_cash_updates');
        cashChannel.onmessage = function(event) {
            if (event.data && event.data.action === 'refresh_cash') {
                console.log('Actualización de efectivo detectada. Recargando vista...');
                // Pequeño retraso visual para asegurar que la transacción ha guardado todo
                setTimeout(() => location.reload(), 300);
            }
        };
    }
</script>