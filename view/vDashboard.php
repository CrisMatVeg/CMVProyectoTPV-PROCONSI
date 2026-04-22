<?php
/**
 * Vista: vDashboard
 */
?>
<div class="main-full dashboard-wrapper">
    <div class="dashboard-card border-none shadow-lg">

        <div class="dashboard-header text-left ai-start pb-20 border-bottom mb-32">
            <div class="d-flex ai-center gap-12 mb-8">
                <i class="fa-solid fa-bolt-lightning text-accent fs-32"></i>
                <h1 class="m-0 fs-28"><?php echo L('dashboard_title'); ?></h1>
            </div>
            <p class="fs-14 m-0 text-muted"><?php echo L('dashboard_subtitle'); ?> · <span class="text-green font-bold"><?php echo L('dashboard_status_active'); ?></span></p>
        </div>

        <?php if ($_SESSION['usuarioActualTPV']->getRol() === 'admin' && $avDashboard['kpis']): ?>
            <!-- KPI CARDS (Only for Admin) -->
            <div class="d-grid gap-16 mb-32" style="grid-template-columns: repeat(5, 1fr);">
                <div class="kpi-card p-20 bg-blue-light br-20 border-2 border-blue d-flex flex-column shadow-sm hover-translate-y">
                    <div class="fs-12 text-muted tt-uppercase font-bold mb-8"><?php echo L('dashboard_kpi_total_sales'); ?></div>
                    <div class="fs-24 font-mono font-bold text-accent"><?php echo number_format($avDashboard['kpis']['total_ventas'] ?? 0, 2, ',', '.'); ?> €</div>
                    <div class="fs-11 text-muted mt-4"><?php echo L('dashboard_kpi_last_7_days'); ?></div>
                </div>
                <div class="kpi-card p-20 bg-green-light br-20 border-2 border-green d-flex flex-column shadow-sm hover-translate-y">
                    <div class="fs-12 text-muted tt-uppercase font-bold mb-8"><?php echo L('dashboard_kpi_margin'); ?></div>
                    <div class="fs-24 font-mono font-bold text-green"><?php echo number_format($avDashboard['kpis']['beneficio_estimado'] ?? 0, 2, ',', '.'); ?> €</div>
                    <div class="fs-11 text-muted mt-4"><?php echo L('dashboard_kpi_margin_sub'); ?></div>
                </div>
                <div class="kpi-card p-20 bg-surface2 br-20 border-2 d-flex flex-column shadow-sm hover-translate-y">
                    <div class="fs-12 text-muted tt-uppercase font-bold mb-8"><?php echo L('dashboard_kpi_operations'); ?></div>
                    <div class="fs-24 font-mono font-bold"><?php echo $avDashboard['kpis']['total_operaciones'] ?? 0; ?></div>
                    <div class="fs-11 text-muted mt-4"><?php echo L('dashboard_kpi_operations_sub'); ?></div>
                </div>
                <div class="kpi-card p-20 bg-surface2 br-20 border-2 d-flex flex-column shadow-sm hover-translate-y">
                    <div class="fs-12 text-muted tt-uppercase font-bold mb-8"><?php echo L('dashboard_kpi_catalog'); ?></div>
                    <div class="fs-24 font-mono font-bold"><?php echo $avDashboard['productos_count']; ?></div>
                    <div class="fs-11 text-muted mt-4"><?php echo L('dashboard_kpi_catalog_sub'); ?></div>
                </div>
                <div class="kpi-card p-20 <?php echo ($avDashboard['bajo_stock_count'] > 0) ? 'bg-red-light border-red' : 'bg-surface2'; ?> br-20 border-2 d-flex flex-column clickable shadow-sm hover-translate-y" onclick="irAProductosBajoStock()">
                    <div class="fs-12 text-muted tt-uppercase font-bold mb-8"><?php echo L('dashboard_kpi_low_stock'); ?></div>
                    <div class="fs-24 font-mono font-bold <?php echo ($avDashboard['bajo_stock_count'] > 0) ? 'text-red' : ''; ?>"><?php echo $avDashboard['bajo_stock_count']; ?></div>
                    <div class="fs-11 text-muted mt-4"><?php echo L('dashboard_kpi_low_stock_sub'); ?></div>
                </div>
            </div>
            <script>
                function irAProductosBajoStock() {
                    // Redirigir a productos con un parámetro para filtrar por bajo stock? 
                    // Por ahora solo redirigimos, y el usuario puede usar el filtro manual que añadí.
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'index.php';
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'irProductos';
                    input.value = '1';
                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                }
            </script>
        <?php endif; ?>

        <div class="d-grid grid-1-360 gap-16">
            <div class="dashboard-grid no-border p-0">
                <!-- Acceso al TPV -->
                <form method="post">
                    <?php if (isset($avDashboard['cajaAbierta']) && $avDashboard['cajaAbierta']): ?>
                        <button type="submit" name="irTPV" class="dashboard-btn">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <span class="btn-title"><?php echo L('dashboard_btn_tpv'); ?></span>
                            <span class="btn-desc"><?php echo L('dashboard_btn_tpv_sub'); ?></span>
                        </button>
                    <?php else: ?>
                        <button type="submit" name="irTPV" class="dashboard-btn" style="border-color: var(--orange); background: rgba(255,165,0,0.05);">
                            <i class="fa-solid fa-cash-register text-orange"></i>
                            <span class="btn-title text-orange"><?php echo L('dashboard_btn_open_cash'); ?></span>
                            <span class="btn-desc"><?php echo L('dashboard_btn_open_cash_sub'); ?></span>
                        </button>
                    <?php endif; ?>
                </form>

                <!-- Gestión de Personal -->
                <?php if ($_SESSION['usuarioActualTPV']->getRol() === 'admin'): ?>
                    <form method="post">
                        <button type="submit" name="irUsuarios" class="dashboard-btn">
                            <i class="fa-solid fa-users-gear"></i>
                            <span class="btn-title"><?php echo L('dashboard_btn_users'); ?></span>
                            <span class="btn-desc"><?php echo L('dashboard_btn_users_sub'); ?></span>
                        </button>
                    </form>

                    <!-- Gestión de Clientes/Socios -->
                    <form method="post">
                        <button type="submit" name="irClientes" class="dashboard-btn">
                            <i class="fa-solid fa-user-group"></i>
                            <span class="btn-title"><?php echo L('dashboard_btn_clients'); ?></span>
                            <span class="btn-desc"><?php echo L('dashboard_btn_clients_sub'); ?></span>
                        </button>
                    </form>

                    <!-- Gestión de Productos -->
                    <form method="post">
                        <button type="submit" name="irProductos" class="dashboard-btn">
                            <i class="fa-solid fa-box-archive"></i>
                            <span class="btn-title"><?php echo L('dashboard_btn_products'); ?></span>
                            <span class="btn-desc"><?php echo L('dashboard_btn_products_sub'); ?></span>
                        </button>
                    </form>

                    <!-- Compras / Entradas -->
                    <form method="post">
                        <button type="submit" name="irCompras" class="dashboard-btn">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                            <span class="btn-title"><?php echo L('dashboard_btn_purchases'); ?></span>
                            <span class="btn-desc"><?php echo L('dashboard_btn_purchases_sub'); ?></span>
                        </button>
                    </form>

                    <!-- Proveedores -->
                    <form method="post">
                        <button type="submit" name="irProveedores" class="dashboard-btn">
                            <i class="fa-solid fa-truck-field"></i>
                            <span class="btn-title"><?php echo L('dashboard_btn_providers'); ?></span>
                            <span class="btn-desc"><?php echo L('dashboard_btn_providers_sub'); ?></span>
                        </button>
                    </form>

                    <!-- Gestión de IVAs -->
                    <form method="post">
                        <button type="submit" name="irTiposIVA" class="dashboard-btn">
                            <i class="fa-solid fa-percent"></i>
                            <span class="btn-title"><?php echo L('dashboard_btn_iva'); ?></span>
                            <span class="btn-desc"><?php echo L('dashboard_btn_iva_sub'); ?></span>
                        </button>
                    </form>

                    <!-- Tarifas de precios -->
                    <form method="post">
                        <button type="submit" name="irTarifas" class="dashboard-btn">
                            <i class="fa-solid fa-arrow-up-wide-short"></i>
                            <span class="btn-title"><?php echo L('dashboard_btn_tariffs'); ?></span>
                            <span class="btn-desc"><?php echo L('dashboard_btn_tariffs_sub'); ?></span>
                        </button>
                    </form>

                    <!-- Gestión de Descuentos -->
                    <form method="post">
                        <button type="submit" name="irPromociones" class="dashboard-btn">
                            <i class="fa-solid fa-ticket-simple"></i>
                            <span class="btn-title"><?php echo L('dashboard_btn_discounts'); ?></span>
                            <span class="btn-desc"><?php echo L('dashboard_btn_discounts_sub'); ?></span>
                        </button>
                    </form>

                    <!-- Historial de Ventas -->
                    <form method="post">
                        <button type="submit" name="irHistorial" class="dashboard-btn">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <span class="btn-title"><?php echo L('dashboard_btn_history'); ?></span>
                            <span class="btn-desc"><?php echo L('dashboard_btn_history_sub'); ?></span>
                        </button>
                    </form>

                    <!-- Auditoría VeriFactu -->
                    <a href="verifactu_log.php" target="_blank" class="dashboard-btn" style="text-decoration: none;">
                        <i class="fa-solid fa-microchip text-accent"></i>
                        <span class="btn-title"><?php echo L('dashboard_fiscal_audit'); ?></span>
                        <span class="btn-desc"><?php echo L('dashboard_fiscal_audit_sub'); ?></span>
                    </a>

                    <!-- Ajustes del Sistema -->
                    <form method="post">
                        <button type="submit" name="irConfiguracion" class="dashboard-btn">
                            <i class="fa-solid fa-gears"></i>
                            <span class="btn-title"><?php echo L('dashboard_btn_settings'); ?></span>
                            <span class="btn-desc"><?php echo L('dashboard_btn_settings_sub'); ?></span>
                        </button>
                    </form>

                    <!-- Analítica -->
                    <form method="post">
                        <button type="submit" name="irAnalitica" class="dashboard-btn">
                            <i class="fa-solid fa-chart-line text-accent"></i>
                            <span class="btn-title"><?php echo L('dashboard_btn_analytics'); ?></span>
                            <span class="btn-desc"><?php echo L('dashboard_btn_analytics_sub'); ?></span>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="dashboard-btn-locked">
                        <i class="fa-solid fa-lock"></i>
                        <span class="btn-title"><?php echo L('dashboard_btn_admin_locked'); ?></span>
                        <span class="btn-desc"><?php echo L('dashboard_btn_admin_locked_sub'); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($_SESSION['usuarioActualTPV']->getRol() === 'admin' && !empty($avDashboard['metodos'])): ?>
                <!-- STATS PANEL (Right side on large screens) -->
                <div class="stats-panel bg-surface p-24 br-20 border-2 shadow-sm">
                    <div class="fs-13 font-bold tt-uppercase mb-16 pb-8 border-bottom"><?php echo L('dashboard_stats_payments'); ?></div>
                    <div class="d-flex flex-column gap-12">
                        <?php foreach ($avDashboard['metodos'] as $m): ?>
                            <?php if (in_array($m['metodo_pago'], ['financiado', 'mixto', 'a_cuenta'])) continue; ?>
                            <div class="d-flex jc-space-between ai-center">
                                <div class="d-flex ai-center gap-8">
                                    <?php
                                    $icon = 'money-bill-1-wave text-green';
                                    if ($m['metodo_pago'] === 'tarjeta') $icon = 'credit-card text-blue';
                                    if ($m['metodo_pago'] === 'bizum') $icon = 'mobile-screen-button text-accent';
                                    ?>
                                    <i class="fa-solid fa-<?php echo $icon; ?>"></i>
                                    <span class="fs-13 tt-capitalize"><?php echo L('tpv_method_' . $m['metodo_pago']); ?></span>
                                </div>
                                <div class="text-right">
                                    <div class="fs-14 font-mono font-bold"><?php echo number_format($m['total'], 2, ',', '.'); ?> €</div>
                                    <div class="fs-10 text-muted"><?php echo $m['cantidad']; ?> <?php echo L('dashboard_ops_short'); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="fs-13 font-bold tt-uppercase mb-16 pb-8 border-bottom mt-24"><?php echo L('dashboard_stats_cashiers'); ?></div>
                    <div class="d-flex flex-column gap-12">
                        <?php if (empty($avDashboard['cajeros'])): ?>
                            <div class="fs-12 text-muted"><?php echo L('dashboard_no_sales'); ?></div>
                        <?php else: ?>
                            <?php foreach ($avDashboard['cajeros'] as $c): ?>
                                <div class="d-flex jc-space-between ai-center">
                                    <div class="d-flex ai-center gap-8">
                                        <i class="fa-solid fa-user-tag text-muted"></i>
                                        <span class="fs-13"><?php echo $c['nombre']; ?></span>
                                    </div>
                                    <div class="text-right">
                                        <div class="fs-13 font-bold"><?php echo number_format($c['total'], 2, ',', '.'); ?> €</div>
                                        <div class="fs-10 text-muted"><?php echo $c['cantidad']; ?> <?php echo L('dashboard_ops_short'); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="mt-24 p-12 bg-surface2 br-8 fs-11 text-muted">
                        <i class="fa-solid fa-circle-info"></i> <?php echo L('dashboard_margin_info'); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>