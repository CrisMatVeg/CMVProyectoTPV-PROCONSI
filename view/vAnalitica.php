<div class="main-full p-24">
    <!-- CABECERA DE SECCIÓN -->
    <div class="section-header container">
        <div class="section-title">
            <div class="d-flex ai-center gap-12 mb-4">
                <i class="fa-solid fa-chart-line text-accent fs-32"></i>
                <h1 class="m-0 fs-28">Analítica de Ventas</h1>
            </div>
            <p class="text-muted fs-14">Informes detallados de rendimiento y márgenes de beneficio.</p>
        </div>
        <form method="post">
            <button type="submit" name="volver" class="btn-icon w-auto h-auto gap-8 fs-14 p-10-20">
                <i class="fa-solid fa-house"></i> Dashboard
            </button>
        </form>
    </div>

    <div class="container mb-32">
        <div class="filters-panel p-24 bg-surface br-20 border-2">
            <form method="get" action="index.php" class="d-flex ai-end gap-24" novalidate>
                <input type="hidden" name="Analitica" value="">
                <div class="filter-group flex-1">
                    <label class="fs-12 font-bold mb-8 d-block text-muted tt-uppercase">Fecha Inicial</label>
                    <input type="date" name="fechaDesde" value="<?php echo $avAnalitica['filtros']['desde']; ?>" class="form-input">
                    <?php if (isset($avAnalitica['aErrores']['fechaDesde'])) { ?><span class="form-error"><?php echo $avAnalitica['aErrores']['fechaDesde']; ?></span><?php } ?>
                </div>
                <div class="filter-group flex-1">
                    <label class="fs-12 font-bold mb-8 d-block text-muted tt-uppercase">Fecha Final</label>
                    <input type="date" name="fechaHasta" value="<?php echo $avAnalitica['filtros']['hasta']; ?>" class="form-input">
                    <?php if (isset($avAnalitica['aErrores']['fechaHasta'])) { ?><span class="form-error"><?php echo $avAnalitica['aErrores']['fechaHasta']; ?></span><?php } ?>
                </div>
                <button type="submit" class="btn-filter h-52 px-32">
                    <i class="fa-solid fa-arrows-rotate"></i> Actualizar Informes
                </button>
            </form>
        </div>
    </div>

    <!-- KPI CARDS -->
    <div class="container d-grid grid-3 gap-24 mb-32">
        <div class="kpi-card p-24 bg-white br-20 shadow-sm border-2 d-flex ai-center gap-20">
            <div class="kpi-icon w-64 h-64 br-16 bg-blue-light d-flex jc-center ai-center text-blue fs-24">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <div>
                <div class="fs-12 text-muted tt-uppercase font-bold mb-4">Ventas Gravadas</div>
                <div class="fs-28 font-mono font-bold text-accent"><?php echo number_format($avAnalitica['kpis']['total_ventas'] ?? 0, 2, ',', '.'); ?> €</div>
                <div class="fs-11 text-muted">Total recaudado (Iva incl.)</div>
            </div>
        </div>
        <div class="kpi-card p-24 bg-white br-20 shadow-sm border-2 d-flex ai-center gap-20">
            <div class="kpi-icon w-64 h-64 br-16 bg-green-light d-flex jc-center ai-center text-green fs-24">
                <i class="fa-solid fa-sack-dollar"></i>
            </div>
            <div>
                <div class="fs-12 text-muted tt-uppercase font-bold mb-4">Beneficio Neto</div>
                <div class="fs-28 font-mono font-bold text-green"><?php echo number_format($avAnalitica['kpis']['margen_estimado'] ?? 0, 2, ',', '.'); ?> €</div>
                <div class="fs-11 text-muted">Ingresos − Costes</div>
            </div>
        </div>
        <div class="kpi-card p-24 bg-white br-20 shadow-sm border-2 d-flex ai-center gap-20">
            <div class="kpi-icon w-64 h-64 br-16 bg-orange-light d-flex jc-center ai-center text-orange fs-24">
                <i class="fa-solid fa-ticket"></i>
            </div>
            <div>
                <div class="fs-12 text-muted tt-uppercase font-bold mb-4">Volumen</div>
                <div class="fs-28 font-mono font-bold text-orange"><?php echo $avAnalitica['kpis']['total_tickets'] ?? 0; ?></div>
                <div class="fs-11 text-muted">Operaciones realizadas</div>
            </div>
        </div>
    </div>

    <div class="container d-flex flex-column gap-24">
        <div class="d-grid grid-2-1 gap-24 ai-start">
        <!-- TOP PRODUCTOS -->
        <div class="card p-0 br-20 bg-white shadow-sm border-2 overflow-hidden" style="min-height: 400px;">
            <div class="p-20 border-bottom d-flex jc-between ai-center">
                <h3 class="m-0 fs-16"><i class="fa-solid fa-crown text-orange mr-8"></i> Top 10 Productos más vendidos</h3>
                <span class="fs-11 text-muted tt-uppercase">Por unidades</span>
            </div>
            <table class="data-table mb-0">
                <thead>
                    <tr>
                        <th class="pl-20">Producto</th>
                        <th class="text-right">Unidades</th>
                        <th class="text-right pr-20">Ingresos Totales</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($avAnalitica['topProductos'])): ?>
                        <tr><td colspan="3" class="empty-state p-40">Sin datos en este rango.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($avAnalitica['topProductos'] as $idx => $p): ?>
                        <tr>
                            <td class="pl-20">
                                <div class="d-flex ai-center gap-12">
                                    <div class="avatar-sm <?php echo $idx < 3 ? 'bg-orange-light text-orange' : ''; ?>">
                                        <?php echo $idx + 1; ?>
                                    </div>
                                    <div>
                                        <div class="font-bold">
                                            <?php echo htmlspecialchars($p['nombre_producto'] ?? 'Producto sin nombre'); ?>
                                        </div>
                                        <div class="fs-11 text-muted">
                                            <?php echo htmlspecialchars($p['codigo_producto'] ?? ''); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-right font-mono font-bold"><?php echo $p['unidades']; ?></td>
                            <td class="text-right pr-20 font-mono text-accent"><?php echo number_format($p['total_recaudado'], 2, ',', '.'); ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- VENTAS POR CATEGORÍA -->
        <div class="card p-0 br-20 bg-white shadow-sm border-2 overflow-hidden">
            <div class="p-20 border-bottom">
                <h3 class="m-0 fs-16"><i class="fa-solid fa-tags text-blue mr-8"></i> Ventas por Categoría</h3>
            </div>
            <div class="p-20">
                <div class="d-flex flex-column gap-16">
                    <?php if (empty($avAnalitica['porCategoria'])): ?>
                        <div class="empty-state">No hay datos por categoría.</div>
                    <?php endif; ?>
                    <?php 
                    $maxTotal = !empty($avAnalitica['porCategoria']) ? $avAnalitica['porCategoria'][0]['total'] : 1;
                    foreach ($avAnalitica['porCategoria'] as $cat): 
                        $pct = ($cat['total'] / $maxTotal) * 100;
                    ?>
                        <div class="category-stat">
                            <div class="d-flex jc-between ai-end mb-8">
                                <span class="fs-13 font-bold tt-uppercase"><?php echo htmlspecialchars($cat['categoria'] ?: 'Sin categoría'); ?></span>
                                <span class="fs-14 font-mono"><?php echo number_format($cat['total'], 2, ',', '.'); ?> €</span>
                            </div>
                            <div class="progress-bar w-full h-8 bg-surface2 br-10 overflow-hidden">
                                <div class="h-full bg-accent" style="width: <?php echo $pct; ?>%; border-radius: 10px;"></div>
                            </div>
                            <div class="text-right fs-10 text-muted mt-4"><?php echo $cat['cantidad']; ?> ventas</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ANÁLISIS DE MÁRGENES POR DÍA -->
    <div class="container mt-24">
        <div class="card p-0 br-20 bg-white shadow-sm border-2 overflow-hidden">
            <div class="p-20 border-bottom bg-surface">
                <h3 class="m-0 fs-16"><i class="fa-solid fa-chart-area text-green mr-8"></i> Desglose de Rentabilidad Diaria</h3>
            </div>
            <table class="data-table mb-0">
                <thead>
                    <tr>
                        <th class="pl-20">Fecha</th>
                        <th class="text-right">Ingresos (Venta)</th>
                        <th class="text-right">Coste de Artículos</th>
                        <th class="text-right pr-20 text-green">Beneficio Neto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($avAnalitica['margenes'])): ?>
                        <tr><td colspan="4" class="empty-state p-40">Sin datos históricos.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($avAnalitica['margenes'] as $m): ?>
                        <tr>
                            <td class="pl-20 font-bold"><?php echo date('d/m/Y', strtotime($m['fecha'])); ?></td>
                            <td class="text-right font-mono"><?php echo number_format($m['ingresos'], 2, ',', '.'); ?> €</td>
                            <td class="text-right font-mono text-muted"><?php echo number_format($m['costes'], 2, ',', '.'); ?> €</td>
                            <td class="text-right pr-20 font-mono font-bold text-green"><?php echo number_format($m['beneficio'], 2, ',', '.'); ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.avatar-sm { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; background: var(--surface2); color: var(--text-muted); }
.bg-orange-light { background: #fff7ed; color: #ea580c !important; }
.bg-blue-light { background: #eff6ff; color: #2563eb !important; }
.bg-green-light { background: #f0fdf4; color: #16a34a !important; }
.bg-orange-light { background: #fff7ed; color: #ea580c !important; }
.text-orange { color: #ea580c; }
.text-blue { color: #2563eb; }
.text-green { color: #16a34a; }
.progress-bar { background-color: #f1f5f9; }
.bg-accent { background-color: var(--accent); }
</style>
