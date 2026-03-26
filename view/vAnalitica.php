<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="main-full p-24">
    <!-- CABECERA DE SECCIÓN -->
    <div class="section-header container-wider">
        <div class="section-title">
            <div class="d-flex ai-center gap-12 mb-4">
                <i class="fa-solid fa-chart-line text-accent fs-32"></i>
                <h1 class="m-0 fs-28"><?php echo L('analytics_title'); ?></h1>
            </div>
            <p class="text-muted fs-14"><?php echo L('analytics_subtitle'); ?></p>
        </div>
        <form method="post">
            <button type="submit" name="volver" class="btn-back">
                    <?php echo L('analytics_btn_back'); ?>
            </button>
        </form>
    </div>

    <!-- FILTROS -->
    <div class="container-wider mb-32">
        <div class="filters-panel p-20 bg-surface br-16 border">
            <form method="get" action="index.php" class="d-flex ai-center gap-20 flex-wrap" novalidate>
                <input type="hidden" name="Analitica" value="">
                <div class="filter-group flex-1" style="min-width: 160px;">
                    <label class="fs-11 font-bold mb-6 d-block text-muted tt-uppercase" style="letter-spacing: 0.5px;"><?php echo L('analytics_filter_start'); ?></label>
                    <input type="date" name="fechaDesde" value="<?php echo $avAnalitica['filtros']['desde']; ?>" class="form-input h-44">
                    <?php if (isset($avAnalitica['aErrores']['fechaDesde'])) { ?><span class="form-error"><?php echo $avAnalitica['aErrores']['fechaDesde']; ?></span><?php } ?>
                </div>
                <div class="filter-group flex-1" style="min-width: 160px;">
                    <label class="fs-11 font-bold mb-6 d-block text-muted tt-uppercase" style="letter-spacing: 0.5px;"><?php echo L('analytics_filter_end'); ?></label>
                    <input type="date" name="fechaHasta" value="<?php echo $avAnalitica['filtros']['hasta']; ?>" class="form-input h-44">
                    <?php if (isset($avAnalitica['aErrores']['fechaHasta'])) { ?><span class="form-error"><?php echo $avAnalitica['aErrores']['fechaHasta']; ?></span><?php } ?>
                </div>
                <button type="submit" class="btn-filter h-44 px-28 d-flex ai-center gap-8" style="align-self: flex-end;">
                    <i class="fa-solid fa-arrows-rotate"></i> <?php echo L('analytics_filter_update'); ?>
                </button>
            </form>
        </div>
    </div>

    <!-- KPI CARDS -->
    <div class="container-wider mb-32">
        <?php
        $kpis = $avAnalitica['kpis'];
        $totalVentas   = (float)($kpis['total_ventas'] ?? 0);
        $margen        = (float)($kpis['margen_estimado'] ?? 0);
        $totalTickets  = (int)($kpis['total_tickets'] ?? 0);
        $pctMargen     = $totalVentas > 0 ? round(($margen / $totalVentas) * 100, 1) : 0;
        $ticketMedio   = $totalTickets > 0 ? round($totalVentas / $totalTickets, 2) : 0;
        ?>
        <div class="d-grid gap-20" style="grid-template-columns: repeat(4, 1fr);">
            <!-- Ventas -->
            <div class="card-section p-24 bg-white shadow-sm border d-flex flex-column gap-12">
                <div class="d-flex ai-center jc-between">
                    <span class="fs-11 text-muted tt-uppercase font-bold" style="letter-spacing: 0.5px;"><?php echo L('analytics_kpis_sales'); ?></span>
                    <div class="w-40 h-40 br-12 d-flex ai-center jc-center" style="background: #eff6ff;">
                        <i class="fa-solid fa-euro-sign" style="color: #2563eb; font-size: 16px;"></i>
                    </div>
                </div>
                <div class="fs-30 font-mono font-bold" style="color: #0f172a; letter-spacing: -1px;"><?php echo number_format($totalVentas, 2, ',', '.'); ?> €</div>
                <div class="fs-12 text-muted"><?php echo L('tpv_tax_included'); ?></div>
            </div>

            <!-- Beneficio -->
            <div class="card-section p-24 bg-white shadow-sm border d-flex flex-column gap-12">
                <div class="d-flex ai-center jc-between">
                    <span class="fs-11 text-muted tt-uppercase font-bold" style="letter-spacing: 0.5px;"><?php echo L('analytics_kpis_profit'); ?></span>
                    <div class="w-40 h-40 br-12 d-flex ai-center jc-center" style="background: #f0fdf4;">
                        <i class="fa-solid fa-sack-dollar" style="color: #16a34a; font-size: 16px;"></i>
                    </div>
                </div>
                <div class="fs-30 font-mono font-bold" style="color: #16a34a; letter-spacing: -1px;"><?php echo number_format($margen, 2, ',', '.'); ?> €</div>
                <div class="fs-12 text-muted"><?php echo L('analytics_kpis_margin'); ?>: <strong><?php echo $pctMargen; ?>%</strong> <?php echo L('analytics_kpis_on_sales'); ?></div>
            </div>

            <!-- Tickets -->
            <div class="card-section p-24 bg-white shadow-sm border d-flex flex-column gap-12">
                <div class="d-flex ai-center jc-between">
                    <span class="fs-11 text-muted tt-uppercase font-bold" style="letter-spacing: 0.5px;"><?php echo L('analytics_kpis_ops'); ?></span>
                    <div class="w-40 h-40 br-12 d-flex ai-center jc-center" style="background: #fff7ed;">
                        <i class="fa-solid fa-ticket" style="color: #ea580c; font-size: 16px;"></i>
                    </div>
                </div>
                <div class="fs-30 font-mono font-bold" style="color: #0f172a; letter-spacing: -1px;"><?php echo number_format($totalTickets, 0, ',', '.'); ?></div>
                <div class="fs-12 text-muted"><?php echo L('analytics_kpis_sales_made'); ?></div>
            </div>

            <!-- Ticket Medio -->
            <div class="card-section p-24 bg-white shadow-sm border d-flex flex-column gap-12">
                <div class="d-flex ai-center jc-between">
                    <span class="fs-11 text-muted tt-uppercase font-bold" style="letter-spacing: 0.5px;"><?php echo L('analytics_kpis_average_ticket'); ?></span>
                    <div class="w-40 h-40 br-12 d-flex ai-center jc-center" style="background: #f5f3ff;">
                        <i class="fa-solid fa-chart-simple" style="color: #7c3aed; font-size: 16px;"></i>
                    </div>
                </div>
                <div class="fs-30 font-mono font-bold" style="color: #7c3aed; letter-spacing: -1px;"><?php echo number_format($ticketMedio, 2, ',', '.'); ?> €</div>
                <div class="fs-12 text-muted"><?php echo L('analytics_kpis_per_op'); ?></div>
            </div>
        </div>
    </div>

    <div class="container-wider d-flex flex-column gap-24" style="margin-top: 32px;">

        <!-- FILA 1: TOP PRODUCTOS + VENTAS POR CATEGORÍA -->
        <div class="d-grid gap-24" style="grid-template-columns: 2fr 1fr; align-items: start;">

            <!-- TOP 10 PRODUCTOS -->
            <div class="card-section bg-white shadow-sm border">
                <div class="p-20 border-bottom d-flex jc-between ai-center" style="background: #fffbeb;">
                    <div class="d-flex ai-center gap-10">
                        <div class="w-36 h-36 br-10 d-flex ai-center jc-center" style="background: #fef3c7;">
                            <i class="fa-solid fa-crown" style="color: #d97706; font-size: 15px;"></i>
                        </div>
                        <div>
                            <h3 class="m-0 fs-15 font-bold"><?php printf(L('analytics_top_products_title', true), count($avAnalitica['topProductos'])); ?></h3>
                            <span class="fs-11 text-muted"><?php echo L('analytics_top_products_sub'); ?></span>
                        </div>
                    </div>
                </div>
                <table class="analitica-table mb-0">
                    <thead>
                        <tr>
                            <th class="pl-20" style="width: 40px;"><?php echo L('analytics_th_rank'); ?></th>
                            <th><?php echo L('analytics_th_product'); ?></th>
                            <th class="text-right"><?php echo L('analytics_th_units'); ?></th>
                            <th class="text-right pr-20"><?php echo L('analytics_th_revenue'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($avAnalitica['topProductos'])): ?>
                            <tr>
                                <td colspan="4" class="empty-state p-40"><?php echo L('analytics_no_data'); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($avAnalitica['topProductos'] as $idx => $p): ?>
                            <tr>
                                <td class="pl-20">
                                    <?php if ($idx === 0): ?>
                                        <span class="rank-medal" style="background: linear-gradient(135deg, #f59e0b, #fbbf24); color: white; width: 28px; height: 28px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800;">1</span>
                                    <?php elseif ($idx === 1): ?>
                                        <span class="rank-medal" style="background: linear-gradient(135deg, #94a3b8, #cbd5e1); color: white; width: 28px; height: 28px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800;">2</span>
                                    <?php elseif ($idx === 2): ?>
                                        <span class="rank-medal" style="background: linear-gradient(135deg, #b45309, #d97706); color: white; width: 28px; height: 28px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800;">3</span>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-size: 13px; font-weight: 600; padding-left: 4px;"><?php echo $idx + 1; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="font-bold fs-13"><?php echo htmlspecialchars($p['nombre_producto_limpio'] ?? L('tpv_unknown', true)); ?></div>
                                    <div class="fs-11 text-muted"><?php echo htmlspecialchars($p['codigo_producto'] ?? ''); ?></div>
                                </td>
                                <td class="text-right font-mono font-bold fs-14"><?php echo $p['unidades']; ?></td>
                                <td class="text-right pr-20 font-mono text-accent fs-13"><?php echo number_format($p['total_recaudado'], 2, ',', '.'); ?> €</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- VENTAS POR CATEGORÍA -->
            <div class="card-section bg-white shadow-sm border d-flex flex-column">
                <div class="p-20 border-bottom" style="background: #f0f9ff;">
                    <div class="d-flex ai-center gap-10">
                        <div class="w-36 h-36 br-10 d-flex ai-center jc-center" style="background: #e0f2fe;">
                            <i class="fa-solid fa-chart-pie" style="color: #0284c7; font-size: 15px;"></i>
                        </div>
                        <div>
                            <h3 class="m-0 fs-15 font-bold"><?php echo L('analytics_cat_sales_title'); ?></h3>
                            <span class="fs-11 text-muted"><?php echo L('analytics_cat_sales_sub'); ?></span>
                        </div>
                    </div>
                </div>
                <div class="p-24 flex-1 d-flex ai-center jc-center position-relative">
                    <?php if (empty($avAnalitica['porCategoria'])): ?>
                        <div class="empty-state w-100"><?php echo L('analytics_no_cat_data'); ?></div>
                    <?php else: ?>
                        <div style="width: 100%; max-width: 300px; margin: auto;">
                            <canvas id="catChart"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- FILA 2: DESGLOSE IVA + RENTABILIDAD DIARIA -->
        <div class="d-grid gap-24" style="grid-template-columns: 1fr 2fr; align-items: start;">

            <!-- DESGLOSE IVA -->
            <div class="card-section bg-white shadow-sm border">
                <div class="p-20 border-bottom" style="background: #fdf4ff;">
                    <div class="d-flex ai-center gap-10">
                        <div class="w-36 h-36 br-10 d-flex ai-center jc-center" style="background: #fae8ff;">
                            <i class="fa-solid fa-percent" style="color: #9333ea; font-size: 15px;"></i>
                        </div>
                        <div>
                            <h3 class="m-0 fs-15 font-bold"><?php echo L('analytics_iva_title'); ?></h3>
                            <span class="fs-11 text-muted"><?php echo L('analytics_iva_sub'); ?></span>
                        </div>
                    </div>
                </div>
                <table class="analitica-table mb-0">
                    <thead>
                        <tr>
                            <th class="pl-20"><?php echo L('analytics_th_iva_type'); ?></th>
                            <th class="text-right"><?php echo L('analytics_th_base_amount'); ?><br><span style="font-weight:400;font-size:10px;"><?php echo L('analytics_th_base_sub'); ?></span></th>
                            <th class="text-right pr-20"><?php echo L('analytics_th_iva_quota'); ?><br><span style="font-weight:400;font-size:10px;"><?php echo L('analytics_th_iva_sub'); ?></span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($avAnalitica['desgloseIva'])): ?>
                            <tr>
                                <td colspan="3" class="empty-state p-32"><?php echo L('analytics_no_data'); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($avAnalitica['desgloseIva'] as $iva):
                            $base = round($iva['total'] - $iva['cuota'], 2);
                        ?>
                            <tr>
                                <td class="pl-20">
                                    <span class="fs-13 px-10 py-4 br-6 font-bold" style="background: #fae8ff; color: #9333ea;"><?php echo $iva['porcentaje']; ?>%</span>
                                </td>
                                <td class="text-right font-mono fs-13"><?php echo number_format($base, 2, ',', '.'); ?> €</td>
                                <td class="text-right pr-20 font-mono font-bold fs-13" style="color: #9333ea;"><?php echo number_format($iva['cuota'], 2, ',', '.'); ?> €</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- RENTABILIDAD DIARIA -->
            <div class="card-section bg-white shadow-sm border d-flex flex-column" style="min-width: 0;">
                <div class="p-20 border-bottom d-flex jc-between ai-center" style="background: #f0fdf4;">
                    <div class="d-flex ai-center gap-10">
                        <div class="w-36 h-36 br-10 d-flex ai-center jc-center" style="background: #dcfce7;">
                            <i class="fa-solid fa-chart-line" style="color: #16a34a; font-size: 15px;"></i>
                        </div>
                        <div>
                            <h3 class="m-0 fs-15 font-bold"><?php echo L('analytics_evo_title'); ?></h3>
                            <span class="fs-11 text-muted"><?php echo L('analytics_evo_sub'); ?></span>
                        </div>
                    </div>
                </div>
                <div class="p-20" style="position: relative; height: 320px; width: 100%; box-sizing: border-box;">
                    <?php if (empty($avAnalitica['margenes'])): ?>
                        <div class="empty-state"><?php echo L('analytics_no_history'); ?></div>
                    <?php else: ?>
                        <canvas id="rentabilidadChart"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- FILA 3: RANKING COMPLETO -->
        <div class="card-section bg-white shadow-sm border overflow-hidden mb-24">
            <div class="p-20 border-bottom d-flex jc-between ai-center" style="background: #f8fafc;">
                <div class="d-flex ai-center gap-10">
                    <div class="w-36 h-36 br-10 d-flex ai-center jc-center" style="background: #f1f5f9;">
                        <i class="fa-solid fa-list-ol text-accent" style="font-size: 15px;"></i>
                    </div>
                    <div>
                        <h3 class="m-0 fs-15 font-bold"><?php echo L('analytics_ranking_title'); ?></h3>
                        <span class="fs-11 text-muted"><?php echo L('analytics_ranking_sub'); ?></span>
                    </div>
                </div>
                <button onclick="exportRankingToExcel()" class="btn-icon p-4-12 fs-12" style="background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;">
                    <i class="fa-solid fa-file-excel"></i> <?php echo L('analytics_btn_export'); ?>
                </button>
            </div>
            <div style="max-height: 480px; overflow-y: auto;">
                <table class="analitica-table mb-0" id="rankingTable">
                    <thead style="position: sticky; top: 0; z-index: 10; background: var(--bg);">
                        <tr>
                            <th class="pl-20" style="width: 50px;"><?php echo L('analytics_th_pos'); ?></th>
                            <th><?php echo L('analytics_th_product'); ?></th>
                            <th class="text-right"><?php echo L('analytics_th_category'); ?></th>
                            <th class="text-right"><?php echo L('analytics_th_units'); ?></th>
                            <th class="text-right pr-20"><?php echo L('analytics_th_revenue'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($avAnalitica['rankingProductos'] as $idx => $rp): ?>
                            <tr class="<?php echo $rp['unidades'] == 0 ? 'opacity-50' : ''; ?>">
                                <td class="pl-20 font-mono text-muted fs-12"><?php echo $idx + 1; ?></td>
                                <td>
                                    <div class="font-bold fs-13"><?php echo htmlspecialchars($rp['nombre_producto_limpio']); ?></div>
                                    <div class="fs-11 text-muted"><?php echo htmlspecialchars($rp['codigo_producto']); ?></div>
                                </td>
                                <td class="text-right">
                                    <span class="fs-10 px-6 py-2 br-4 font-bold tt-uppercase" style="background: #f1f5f9; color: #64748b;"><?php echo htmlspecialchars($rp['categoria'] ?: 'N/A'); ?></span>
                                </td>
                                <td class="text-right font-mono <?php echo $rp['unidades'] > 0 ? 'font-bold' : 'text-muted'; ?>"><?php echo $rp['unidades']; ?></td>
                                <td class="text-right pr-20 font-mono <?php echo $rp['unidades'] > 0 ? 'text-accent font-bold' : 'text-muted'; ?> fs-13">
                                    <?php echo number_format($rp['total_recaudado'], 2, ',', '.'); ?> €
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- end container-wider -->

    <script>
        // Data extraction for JS charts
        <?php
        $catLabels = [];
        $catData = [];
        if(!empty($avAnalitica['porCategoria'])) {
            foreach($avAnalitica['porCategoria'] as $c) {
                $catLabels[] = $c['categoria'] ?: L('tpv_no_category', true);
                $catData[] = (float)$c['total'];
            }
        }

        $diaLabels = [];
        $diaIngresos = [];
        $diaCostes = [];
        $diaBeneficios = [];
        if(!empty($avAnalitica['margenes'])) {
            // Sort margins by date ASC (oldest to newest) to display correctly left-to-right
            $margenes = $avAnalitica['margenes'];
            usort($margenes, function($a, $b) {
                return strtotime($a['fecha']) - strtotime($b['fecha']);
            });

            foreach($margenes as $m) {
                $diaLabels[] = date('d/m/Y', strtotime($m['fecha']));
                $diaIngresos[] = (float)$m['ingresos'];
                $diaCostes[] = (float)$m['costes'];
                $diaBeneficios[] = (float)$m['beneficio'];
            }
        }
        ?>

        document.addEventListener('DOMContentLoaded', () => {
            // Initialize Doughnut Category Chart
            const ctxCat = document.getElementById('catChart');
            if (ctxCat) {
                new Chart(ctxCat, {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo json_encode($catLabels); ?>,
                        datasets: [{
                            data: <?php echo json_encode($catData); ?>,
                            backgroundColor: [
                                '#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#64748b', '#06b6d4'
                            ],
                            borderWidth: 2,
                            borderColor: '#ffffff',
                            hoverOffset: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    font: { family: "'Inter', sans-serif" },
                                    boxWidth: 12,
                                    padding: 15
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let val = context.raw || 0;
                                        return ' ' + val.toLocaleString('<?php echo $_SESSION['lang'] === 'en' ? 'en-GB' : 'es-ES'; ?>', {minimumFractionDigits: 2}) + ' €';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Initialize Line Evolution Chart
            const ctxEvo = document.getElementById('rentabilidadChart');
            if (ctxEvo) {
                new Chart(ctxEvo, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($diaLabels); ?>,
                        datasets: [
                            {
                                label: '<?php echo L('analytics_chart_income'); ?>',
                                data: <?php echo json_encode($diaIngresos); ?>,
                                borderColor: '#3b82f6',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                borderWidth: 3,
                                tension: 0.4,
                                fill: true,
                                pointRadius: 4,
                                pointBackgroundColor: '#ffffff'
                            },
                            {
                                label: '<?php echo L('analytics_chart_cost'); ?>',
                                data: <?php echo json_encode($diaCostes); ?>,
                                borderColor: '#64748b',
                                borderWidth: 2,
                                borderDash: [5, 5],
                                tension: 0.4,
                                fill: false,
                                pointRadius: 2
                            },
                            {
                                label: '<?php echo L('analytics_chart_profit'); ?>',
                                data: <?php echo json_encode($diaBeneficios); ?>,
                                borderColor: '#10b981',
                                borderWidth: 2,
                                tension: 0.4,
                                fill: false,
                                pointRadius: 3
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: { font: { family: "'Inter', sans-serif" } }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let val = context.raw || 0;
                                        return context.dataset.label + ': ' + val.toLocaleString('<?php echo $_SESSION['lang'] === 'en' ? 'en-GB' : 'es-ES'; ?>', {minimumFractionDigits: 2}) + ' €';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { borderDash: [4, 4], color: '#e2e8f0' },
                                ticks: {
                                    callback: function(val) { return val + ' €'; }
                                }
                            },
                            x: {
                                grid: { display: false }
                            }
                        }
                    }
                });
            }
        });

        function exportRankingToExcel() {
            const table = document.getElementById("rankingTable");
            let csv = [];
            const rows = table.querySelectorAll("tr");
            for (let i = 0; i < rows.length; i++) {
                const row = [],
                    cols = rows[i].querySelectorAll("td, th");
                for (let j = 0; j < cols.length; j++) {
                    let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").replace(/,/g, ".");
                    row.push('"' + text.trim() + '"');
                }
                csv.push(row.join(","));
            }
            const csvContent = "data:text/csv;charset=utf-8,\uFEFF" + csv.join("\n");
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "<?php echo L('analytics_filename_export'); ?>");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>

    <style>
        .card-section {
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0px 4px 16px rgba(0, 0, 0, 0.06) !important;
        }

        /* ── Analytics Table Borders & Clarity ── */
        .analitica-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
        }

        .analitica-table thead tr {
            background: #f1f5f9;
        }

        .analitica-table thead th {
            padding: 12px 16px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            border-bottom: 2px solid #cbd5e1;
            border-right: 1px solid #cbd5e1;
        }

        .analitica-table thead th:last-child {
            border-right: none;
        }

        .analitica-table tbody tr {
            border-bottom: 1px solid #e9ecef;
            transition: background 0.15s;
        }

        .analitica-table tbody tr:last-child {
            border-bottom: none;
        }

        .analitica-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .analitica-table tbody tr:hover {
            background: #eff6ff !important;
        }

        .analitica-table tbody tr.opacity-50 {
            opacity: 0.45;
        }

        .analitica-table td {
            padding: 10px 16px;
            border-right: 1px solid #e9ecef;
            vertical-align: middle;
        }

        .analitica-table td:last-child {
            border-right: none;
        }

        .rank-medal {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-weight: 800;
        }

        .text-orange {
            color: #ea580c;
        }

        .text-blue {
            color: #2563eb;
        }

        .text-green {
            color: #16a34a;
        }

        .bg-orange-light {
            background: #fff7ed;
        }

        .bg-blue-light {
            background: #eff6ff;
        }

        .bg-green-light {
            background: #f0fdf4;
        }

        .bg-accent {
            background-color: var(--accent);
        }

        .opacity-50 {
            opacity: 0.45;
        }

        .h-44 {
            height: 44px;
        }
    </style>
</div>