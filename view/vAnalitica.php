<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
    /* Analytics-specific micro-animations */
    .fade-in { animation: fadeIn 0.5s ease-out forwards; opacity: 0; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .kpi-icon {
        width: 42px; height: 42px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        transition: all 0.3s ease;
    }
    .card-section:hover .kpi-icon { transform: scale(1.1) rotate(5deg); }
    
    .opacity-50 { opacity: 0.5; }
    .vr { width: 1px; background: var(--border); }
</style>


<div class="main-full p-24">
    <!-- CABECERA -->
    <div class="section-header container-wider mb-24">
        <div class="d-flex ai-center gap-16">
            <a href="index.php?irDashboard=1" class="btn-prominent-back compact" title="<?php echo L('login_back'); ?>">
                <i class="fa-solid fa-chevron-left"></i>
                <span><?php echo L('login_back'); ?></span>
            </a>
            <div class="vr" style="height: 32px; width: 1px; background: var(--border); opacity: 0.5;"></div>
            <div class="section-title">
                <div class="d-flex ai-center gap-12 mb-4">
                    <i class="fa-solid fa-chart-line text-accent fs-32"></i>
                    <h1 class="m-0 fs-28"><?php echo L('analytics_title'); ?></h1>
                </div>
                <p class="text-muted fs-14"><?php echo L('analytics_subtitle'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- BANNER DE OPTIMIZACIÓN -->
    <div id="optimizerBanner" class="container-wider mb-24 fade-in" style="display: none;">
        <div class="card-section p-20 d-flex ai-center jc-space-between" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-color: #3b82f6 !important;">
            <div class="d-flex ai-center gap-16">
                <div class="bg-blue p-12 br-12 shadow-sm">
                    <i class="fa-solid fa-gauge-high text-white fs-24"></i>
                </div>
                <div>
                    <h3 class="m-0 fs-16 text-blue-dark">Base de datos no optimizada</h3>
                    <p class="m-0 fs-13 text-muted">Las consultas históricas pueden tardar. Aplica índices de rendimiento para cargar "Todo el historial" al instante.</p>
                </div>
            </div>
            <button id="btnOptimize" onclick="ejecutarOptimizacion()" class="btn-primary shadow-sm" style="background: var(--accent); border: none; padding: 10px 20px; border-radius: 10px; cursor: pointer; color: white; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Optimizar ahora
            </button>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="filters-panel container-wider" style="margin-bottom: 24px;">
        <form id="formFiltros" method="get" action="index.php" class="filter-toolbar">
            <input type="hidden" name="menu" value="Analitica">
            
            <div class="filter-group">
                <label class="filter-label"><?php echo L('history_filter_period'); ?></label>
                <select name="periodo" id="filterPeriodo" class="input-filter" style="min-width: 140px;">
                    <option value="hoy" <?php echo $avAnalitica['filtros']['periodo'] === 'hoy' ? 'selected' : ''; ?>><?php echo L('history_period_today'); ?></option>
                    <option value="semana" <?php echo $avAnalitica['filtros']['periodo'] === 'semana' ? 'selected' : ''; ?>><?php echo L('history_period_week'); ?></option>
                    <option value="mes" <?php echo $avAnalitica['filtros']['periodo'] === 'mes' ? 'selected' : ''; ?>><?php echo L('history_period_month'); ?></option>
                    <option value="todo" <?php echo $avAnalitica['filtros']['periodo'] === 'todo' ? 'selected' : ''; ?>><?php echo L('history_period_all'); ?></option>
                    <option value="personalizado" <?php echo $avAnalitica['filtros']['periodo'] === 'personalizado' ? 'selected' : ''; ?>><?php echo L('history_period_custom'); ?></option>
                </select>
            </div>

            <div id="customDates" class="d-flex gap-20" style="<?php echo $avAnalitica['filtros']['periodo'] !== 'personalizado' ? 'display:none !important' : 'display:flex !important'; ?>">
                <div class="filter-group">
                    <label class="filter-label"><?php echo L('history_filter_since'); ?></label>
                    <input type="date" name="fechaDesde" value="<?php echo $avAnalitica['filtros']['desde']; ?>" class="input-filter">
                </div>
                <div class="filter-group">
                    <label class="filter-label"><?php echo L('history_filter_until'); ?></label>
                    <input type="date" name="fechaHasta" value="<?php echo $avAnalitica['filtros']['hasta']; ?>" class="input-filter">
                </div>
            </div>

            <div class="filter-group">
                <button type="submit" class="btn-filter" style="height: 36px; padding: 0 16px; border-radius: 8px;">
                    <i class="fa-solid fa-arrows-rotate"></i> <?php echo L('analytics_btn_filter'); ?>
                </button>
            </div>
        </form>
    </div>


    <!-- KPI CARDS -->
    <div class="container-wider" style="margin-bottom: 25px;">
        <div class="grid-4 gap-24">
            <div class="card-section p-24 d-flex flex-column gap-12" id="kpiSalesContainer">
                <div class="d-flex ai-center jc-between">
                    <span class="fs-11 text-muted tt-uppercase font-bold"><?php echo L('analytics_kpis_sales'); ?></span>
                    <div class="kpi-icon bg-blue-soft"><i class="fa-solid fa-cart-shopping fs-18"></i></div>
                </div>
                <div id="val_kpiSales" class="fs-30 font-mono font-bold"><div class="skeleton-text skeleton-kpi"></div></div>
                <div id="sub_kpiSales" class="fs-12 text-muted"><div class="skeleton-text" style="width:60%"></div></div>
            </div>
            <div class="card-section p-24 d-flex flex-column gap-12" id="kpiProfitContainer">
                <div class="d-flex ai-center jc-between">
                    <span class="fs-11 text-muted tt-uppercase font-bold"><?php echo L('analytics_kpis_profit'); ?></span>
                    <div class="kpi-icon bg-green-soft"><i class="fa-solid fa-sack-dollar fs-18"></i></div>
                </div>
                <div id="val_kpiProfit" class="fs-30 font-mono font-bold"><div class="skeleton-text skeleton-kpi"></div></div>
                <div id="sub_kpiProfit" class="fs-12 text-muted"><div class="skeleton-text" style="width:60%"></div></div>
            </div>
            <div class="card-section p-24 d-flex flex-column gap-12" id="kpiOpsContainer">
                <div class="d-flex ai-center jc-between">
                    <span class="fs-11 text-muted tt-uppercase font-bold"><?php echo L('analytics_kpis_ops'); ?></span>
                    <div class="kpi-icon bg-orange-soft"><i class="fa-solid fa-ticket fs-18"></i></div>
                </div>
                <div id="val_kpiOps" class="fs-30 font-mono font-bold"><div class="skeleton-text skeleton-kpi"></div></div>
                <div id="sub_kpiOps" class="fs-12 text-muted"><div class="skeleton-text" style="width:60%"></div></div>
            </div>
            <div class="card-section p-24 d-flex flex-column gap-12" id="kpiAverageContainer">
                <div class="d-flex ai-center jc-between">
                    <span class="fs-11 text-muted tt-uppercase font-bold"><?php echo L('analytics_kpis_average_ticket'); ?></span>
                    <div class="kpi-icon bg-purple-soft"><i class="fa-solid fa-chart-simple fs-18"></i></div>
                </div>
                <div id="val_kpiAverage" class="fs-30 font-mono font-bold"><div class="skeleton-text skeleton-kpi"></div></div>
                <div id="sub_kpiAverage" class="fs-12 text-muted"><div class="skeleton-text" style="width:60%"></div></div>
            </div>
        </div>
    </div>


    <!-- MAIN CONTENT -->
    <div class="container-wider d-flex flex-column gap-24">
        <!-- TOP + CATEGORIAS -->
        <div class="grid-2 gap-24">
            <div class="card-section">
                <div class="p-20 border-bottom d-flex ai-center gap-10 bg-surface2">
                    <i class="fa-solid fa-crown text-orange"></i>
                    <h3 class="m-0 fs-15 font-bold"><?php echo L('analytics_top_products_title_simple'); ?></h3>
                </div>
                <div id="topProductsTableContainer" class="p-0">
                    <div class="p-20">
                        <div class="skeleton-text skeleton-table-row"></div>
                        <div class="skeleton-text skeleton-table-row"></div>
                        <div class="skeleton-text skeleton-table-row"></div>
                    </div>
                </div>
            </div>
            <div class="card-section">
                <div class="p-20 border-bottom d-flex ai-center gap-10 bg-surface2">
                    <i class="fa-solid fa-chart-pie text-blue"></i>
                    <h3 class="m-0 fs-15 font-bold"><?php echo L('analytics_cat_sales_title'); ?></h3>
                </div>
                <div id="catChartContainer" class="p-24 d-flex ai-center jc-center" style="min-height: 300px;">
                    <div class="skeleton-text" style="height: 200px; width: 200px; border-radius: 50%;"></div>
                </div>
            </div>
        </div>

        <!-- IVA + EVOLUCION -->
        <div class="grid-2 gap-24">
            <div class="card-section">
                <div class="p-20 border-bottom d-flex ai-center gap-10 bg-surface2">
                    <i class="fa-solid fa-percent text-purple"></i>
                    <h3 class="m-0 fs-15 font-bold"><?php echo L('analytics_iva_title'); ?></h3>
                </div>
                <div id="ivaTableContainer" class="p-0">
                    <div class="p-20">
                        <div class="skeleton-text skeleton-table-row"></div>
                        <div class="skeleton-text skeleton-table-row"></div>
                    </div>
                </div>
            </div>
            <div class="card-section">
                <div class="p-20 border-bottom d-flex ai-center gap-10 bg-surface2">
                    <i class="fa-solid fa-chart-line text-green"></i>
                    <h3 class="m-0 fs-15 font-bold"><?php echo L('analytics_evo_title'); ?></h3>
                </div>
                <div id="evolutionChartContainer" class="p-20" style="height: 300px;">
                    <div class="skeleton-text skeleton-chart"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const activeLocale = '<?php echo $_SESSION['lang'] ?? 'es'; ?>';

    async function checkDatabaseHealth() {
        try {
            const response = await fetch(`index.php?menu=Analitica&ajax=checkHealth`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            if (data.optimized === false) {
                document.getElementById('optimizerBanner').style.display = 'block';
            }
        } catch (e) { console.error("Error checking health", e); }
    }

    async function ejecutarOptimizacion() {
        const btn = document.getElementById('btnOptimize');
        if (!btn) return;
        const oldHtml = btn.innerHTML;
        btn.disabled = true;
        
        try {
            const commonHeaders = { 'X-Requested-With': 'XMLHttpRequest' };
            // Paso 1
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Optimizando Fechas (1/3)...';
            await fetch(`index.php?menu=Analitica&ajax=runOptimization&step=step1`, { headers: commonHeaders }).then(r => r.json());
            
            // Paso 2
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Optimizando Rendimiento (2/3)...';
            await fetch(`index.php?menu=Analitica&ajax=runOptimization&step=step2`, { headers: commonHeaders }).then(r => r.json());
            
            // Paso 3
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Optimizando Catálogo (3/3)...';
            await fetch(`index.php?menu=Analitica&ajax=runOptimization&step=step3`, { headers: commonHeaders }).then(r => r.json());

            document.getElementById('optimizerBanner').innerHTML = `
                <div class="alert-premium premium-info ai-center fade-in" style="border-color: var(--green) !important;">
                    <div class="alert-icon-wrap" style="background: var(--green) !important;">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div class="alert-content">
                        <h3 class="alert-title" style="color: var(--green) !important;">¡Optimización completada!</h3>
                        <p class="alert-desc">La base de datos ya está lista. El historial completo cargará mucho más rápido.</p>
                    </div>
                </div>
            `;

            setTimeout(() => {
                document.getElementById('optimizerBanner').style.display = 'none';
                window.location.reload(); 
            }, 3000);
        } catch (e) {
            btn.disabled = false;
            btn.innerHTML = oldHtml;
            showCustomAlert("Error de optimización", "Error al optimizar. Es posible que el servidor haya cortado la conexión por el tamaño de la tabla. Por favor, vuelve a intentarlo; el proceso continuará donde se quedó.", "error");
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const activeLocale = '<?php echo $_SESSION['lang'] ?? 'es'; ?>';
        const searchParams = {
            fechaDesde: '<?php echo $avAnalitica['filtros']['desde']; ?>',
            fechaHasta: '<?php echo $avAnalitica['filtros']['hasta']; ?>',
            idCajero: '',
            tipoDocumento: 'todos'
        };

        // Router Auxiliar
        function fetchAnalytics(action, callback) {
            const params = new URLSearchParams({ menu: 'Analitica', ajax: action, ...searchParams });
            fetch('index.php?' + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(r => {
                    if (!r.ok) return r.json().then(err => Promise.reject(err.message || 'Error ' + r.status));
                    return r.json();
                })
                .then(callback)
                .catch(err => {
                    console.error(`Error loading ${action}:`, err);
                    showErrorInContainer(action, err);
                });
        }


        // --- Cargas Paralelas ---
        
        // Ejecutar al inicio
    checkDatabaseHealth();

    // 2. KPIs e IVA
        fetchAnalytics('loadKPIs', (data) => {
            updateKPI('kpiSales', data.total_ventas, '€');
            updateKPI('kpiProfit', data.beneficio_estimado, '€', true);
            updateKPI('kpiOps', data.total_operaciones, '');
            updateKPI('kpiAverage', data.ticket_medio, '€');
        });

        // 2. Charts (Top y Categorias)
        fetchAnalytics('loadCharts', (data) => {
            renderTopProductsTable(data.topProductos);
            renderCategoryChart(data.categorias);
            renderEvolutionChart(data.evolucion, data.agrupacion);
        });

        // 3. IVA
        fetchAnalytics('loadIVA', data => {
            renderIVATable(data);
        });

        // Helpers
        function updateKPI(id, val, suffix, colorize = false) {
            const valEl = document.getElementById('val_' + id);
            const subEl = document.getElementById('sub_' + id);
            if (!valEl) return;

            let num = parseFloat(val);
            if (isNaN(num)) num = 0;

            const decOpts = suffix ? { minimumFractionDigits: 2, maximumFractionDigits: 2 } : { maximumFractionDigits: 0 };
            const formatted = num.toLocaleString(activeLocale, decOpts);
            valEl.innerHTML = `<span class="fade-in">${formatted}${suffix ? ' ' + suffix : ''}</span>`;
            if (colorize) {
                valEl.style.color = num >= 0 ? 'var(--green)' : 'var(--red)';
            }
            if (subEl) subEl.innerHTML = `<span class="fade-in"><?php echo L('analytics_kpis_on_sales'); ?></span>`;
        }


        function renderTopProductsTable(data) {
            const container = document.getElementById('topProductsTableContainer');
            if (!data || data.length === 0) {
                container.innerHTML = `<div class="p-40 text-center text-muted italic"><?php echo L('analytics_no_data'); ?></div>`;
                return;
            }
            let html = `<table class="analitica-table"><thead><tr><th>#</th><th><?php echo L('analytics_th_product'); ?></th><th class="text-right"><?php echo L('analytics_th_units'); ?></th><th class="text-right"><?php echo L('analytics_th_revenue'); ?></th></tr></thead><tbody>`;
            data.forEach((p, i) => {
                html += `<tr class="fade-in"><td>${i+1}</td><td><div class="font-bold">${escapeHTML(p.nombre_producto_limpio)}</div><div class="fs-11 text-muted">${p.codigo_producto}</div></td><td class="text-right">${p.unidades}</td><td class="text-right text-accent font-bold">${parseFloat(p.total_recaudado).toLocaleString(activeLocale, {minimumFractionDigits:2})}€</td></tr>`;
            });
            container.innerHTML = html + `</tbody></table>`;
        }

        function renderIVATable(data) {
            const container = document.getElementById('ivaTableContainer');
            if (!data || data.length === 0) {
                container.innerHTML = `<div class="p-40 text-center text-muted italic"><?php echo L('analytics_no_data'); ?></div>`;
                return;
            }
            let html = `<table class="analitica-table"><thead><tr><th><?php echo L('analytics_th_iva_type'); ?></th><th class="text-right"><?php echo L('analytics_th_base_amount'); ?></th><th class="text-right"><?php echo L('analytics_th_iva_quota'); ?></th></tr></thead><tbody>`;
            data.forEach(iva => {
                const base = parseFloat(iva.total) - parseFloat(iva.cuota);
                html += `<tr class="fade-in"><td><span class="badge bg-green-light text-green">${iva.porcentaje}%</span></td><td class="text-right">${base.toLocaleString(activeLocale, {minimumFractionDigits:2})}€</td><td class="text-right font-bold text-purple">${parseFloat(iva.cuota).toLocaleString(activeLocale, {minimumFractionDigits:2})}€</td></tr>`;
            });
            container.innerHTML = html + `</tbody></table>`;
        }

        function renderCategoryChart(data) {
            const canvas = document.createElement('canvas');
            canvas.id = 'catChart';
            const container = document.getElementById('catChartContainer');
            container.innerHTML = '';
            container.appendChild(canvas);
            
            const accentColor = getComputedStyle(document.body).getPropertyValue('--accent').trim() || '#3b82f6';
            
            new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: data.map(c => c.categoria || 'Sin categoría'),
                    datasets: [{
                        data: data.map(c => parseFloat(c.total)),
                        backgroundColor: [accentColor, '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#64748b']
                    }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false, 
                    cutout: '75%', 
                    plugins: { 
                        legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20, font: { size: 11 } } } 
                    } 
                }
            });

        }

        function renderEvolutionChart(data, agrupacion = 'dia') {
            const canvas = document.createElement('canvas');
            const container = document.getElementById('evolutionChartContainer');
            container.innerHTML = '';
            container.appendChild(canvas);
            
            const labels = data.map(d => {
                // T12:00:00 evita desfase UTC en timezones +X
                const date = new Date(d.fecha + 'T12:00:00');
                if (agrupacion === 'mes') {
                    return date.toLocaleDateString(activeLocale, { month: 'short', year: 'numeric' });
                } else if (agrupacion === 'año') {
                    return date.getFullYear();
                }
                return date.toLocaleDateString(activeLocale, { day: '2-digit', month: '2-digit' });
            });

            const accentColor = getComputedStyle(document.body).getPropertyValue('--accent').trim() || '#3b82f6';
            const greenColor = getComputedStyle(document.body).getPropertyValue('--green').trim() || '#10b981';

            new Chart(canvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        { 
                            label: '<?php echo L('analytics_chart_income'); ?>', 
                            data: data.map(d => d.ingresos), 
                            borderColor: accentColor, 
                            backgroundColor: accentColor + '1A', // 10% opacity
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        },
                        { 
                            label: '<?php echo L('analytics_chart_profit'); ?>', 
                            data: data.map(d => d.beneficio), 
                            borderColor: greenColor, 
                            backgroundColor: greenColor + '1A',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }
                    ]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false, 
                    interaction: { intersect: false, mode: 'index' },
                    scales: { 
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                        x: { grid: { display: false } }
                    },
                    plugins: {
                        legend: { position: 'top', align: 'end', labels: { usePointStyle: true } }
                    }
                }
            });

        }

        function escapeHTML(str) {
            if (!str) return '';
            const p = document.createElement('p');
            p.textContent = str;
            return p.innerHTML;
        }

        function showErrorInContainer(action, errorMsg) {
            const mappings = {
                'loadKPIs': ['val_kpiSales', 'val_kpiProfit', 'val_kpiOps', 'val_kpiAverage'],
                'loadCharts': ['topProductsTableContainer', 'catChartContainer', 'evolutionChartContainer'],
                'loadIVA': ['ivaTableContainer']
            };
            
            const targets = mappings[action] || [];
            targets.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.innerHTML = `<div class="fs-12 text-red py-10" title="${escapeHTML(errorMsg)}"><i class="fa-solid fa-triangle-exclamation mr-4"></i> Error</div>`;
            });
        }

        // Toggle fechas
        document.getElementById('filterPeriodo').addEventListener('change', function() {
            const custom = document.getElementById('customDates');
            if (this.value === 'personalizado') {
                custom.style.setProperty('display', 'flex', 'important');
            } else {
                custom.style.setProperty('display', 'none', 'important');
                document.getElementById('formFiltros').submit();
            }
        });
    });
</script>