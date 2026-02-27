</header>
<div class="main-full dashboard-wrapper">
    <div class="dashboard-card border-none shadow-lg">
        
        <div class="dashboard-header text-left ai-start pb-20 border-bottom mb-32">
            <div class="d-flex ai-center gap-12 mb-8">
                <i class="fa-solid fa-bolt-lightning text-accent fs-32"></i>
                <h1 class="m-0 fs-28">Panel de Control</h1>
            </div>
            <p class="fs-14 m-0 text-muted">Gestión integral del sistema · <span class="text-green font-bold">Terminal Activo</span></p>
        </div>

        <?php if ($_SESSION['usuarioActualTPV']->getRol() === 'admin' && $avDashboard['kpis']): ?>
        <!-- KPI CARDS (Only for Admin) -->
        <div class="d-grid grid-4 gap-16 mb-32">
            <div class="kpi-card p-20 bg-blue-light br-16 border-2 border-blue d-flex flex-column">
                <div class="fs-12 text-muted tt-uppercase font-bold mb-8">Ventas Totales</div>
                <div class="fs-24 font-mono font-bold text-accent"><?php echo number_format($avDashboard['kpis']['total_ventas'] ?? 0, 2, ',', '.'); ?> €</div>
                <div class="fs-11 text-muted mt-4">Últimos 7 días</div>
            </div>
            <div class="kpi-card p-20 bg-green-light br-16 border-2 border-green d-flex flex-column">
                <div class="fs-12 text-muted tt-uppercase font-bold mb-8">Margen Estimado</div>
                <div class="fs-24 font-mono font-bold text-green"><?php echo number_format($avDashboard['kpis']['margen_estimado'] ?? 0, 2, ',', '.'); ?> €</div>
                <div class="fs-11 text-muted mt-4">Beneficio bruto aprox.</div>
            </div>
            <div class="kpi-card p-20 bg-surface2 br-16 border-2 d-flex flex-column">
                <div class="fs-12 text-muted tt-uppercase font-bold mb-8">Operaciones</div>
                <div class="fs-24 font-mono font-bold"><?php echo $avDashboard['kpis']['total_tickets'] ?? 0; ?></div>
                <div class="fs-11 text-muted mt-4">Tickets realizados</div>
            </div>
            <div class="kpi-card p-20 bg-surface2 br-16 border-2 d-flex flex-column">
                <div class="fs-12 text-muted tt-uppercase font-bold mb-8">Catálogo</div>
                <div class="fs-24 font-mono font-bold"><?php echo $avDashboard['productos_count']; ?></div>
                <div class="fs-11 text-muted mt-4">Productos activos</div>
            </div>
        </div>
        <?php endif; ?>

        <div class="d-grid grid-1-360 gap-16">
            <div class="dashboard-grid no-border p-0">
                <!-- Acceso al TPV -->
            <form method="post">
                <button type="submit" name="irTPV" class="dashboard-btn">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span class="btn-title">Ventas TPV</span>
                    <span class="btn-desc">Acceder al panel de ventas y cobro</span>
                </button>
            </form>

            <!-- Gestión de Personal -->
            <?php if ($_SESSION['usuarioActualTPV']->getRol() === 'admin'): ?>
            <form method="post">
                <button type="submit" name="irUsuarios" class="dashboard-btn">
                    <i class="fa-solid fa-users-gear"></i>
                    <span class="btn-title">Gestión de Personal</span>
                    <span class="btn-desc">Administrar usuarios y permisos</span>
                </button>
            </form>

            <!-- Gestión de Productos -->
            <form method="post">
                <button type="submit" name="irProductos" class="dashboard-btn">
                    <i class="fa-solid fa-box-archive"></i>
                    <span class="btn-title">Gestión de Productos</span>
                    <span class="btn-desc">Editar catálogo, precios e iconos</span>
                </button>
            </form>

            <!-- Historial de Ventas -->
            <form method="post">
                <button type="submit" name="irHistorial" class="dashboard-btn">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span class="btn-title">Historial de Ventas</span>
                    <span class="btn-desc">Buscador de tickets y auditoría</span>
                </button>
            </form>
            <?php else: ?>
            <div class="dashboard-btn-locked">
                <i class="fa-solid fa-lock"></i>
                <span class="btn-title">Administración</span>
                <span class="btn-desc">Acceso restringido a administradores</span>
            </div>
            <?php endif; ?>
            </div>

            <?php if ($_SESSION['usuarioActualTPV']->getRol() === 'admin' && !empty($avDashboard['metodos'])): ?>
            <!-- STATS PANEL (Right side on large screens) -->
            <div class="stats-panel bg-surface p-20 br-16 border-2">
                <div class="fs-13 font-bold tt-uppercase mb-16 pb-8 border-bottom">Distribución de Cobros (7d)</div>
                <div class="d-flex flex-column gap-12">
                    <?php foreach ($avDashboard['metodos'] as $m): ?>
                    <div class="d-flex jc-space-between ai-center">
                        <div class="d-flex ai-center gap-8">
                            <i class="fa-solid fa-<?php echo $m['metodo_pago'] === 'tarjeta' ? 'credit-card text-accent' : 'money-bill-1-wave text-green'; ?>"></i>
                            <span class="fs-13 tt-capitalize"><?php echo $m['metodo_pago']; ?></span>
                        </div>
                        <div class="text-right">
                            <div class="fs-14 font-mono font-bold"><?php echo number_format($m['total'], 2, ',', '.'); ?> €</div>
                            <div class="fs-10 text-muted"><?php echo $m['cantidad']; ?> tickets</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="fs-13 font-bold tt-uppercase mb-16 pb-8 border-bottom mt-24">Ventas por Cajero (7d)</div>
                <div class="d-flex flex-column gap-12">
                    <?php if (empty($avDashboard['cajeros'])): ?>
                        <div class="fs-12 text-muted">No hay ventas registradas.</div>
                    <?php else: ?>
                        <?php foreach ($avDashboard['cajeros'] as $c): ?>
                        <div class="d-flex jc-space-between ai-center">
                            <div class="d-flex ai-center gap-8">
                                <i class="fa-solid fa-user-tag text-muted"></i>
                                <span class="fs-13"><?php echo $c['nombre']; ?></span>
                            </div>
                            <div class="text-right">
                                <div class="fs-13 font-bold"><?php echo number_format($c['total'], 2, ',', '.'); ?> €</div>
                                <div class="fs-10 text-muted"><?php echo $c['cantidad']; ?> ops.</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="mt-24 p-12 bg-surface2 br-8 fs-11 text-muted">
                    <i class="fa-solid fa-circle-info"></i> El margen se calcula restando el coste unitario histórico del precio cobrado en cada venta.
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>
