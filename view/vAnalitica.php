<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
    /* CSS para Skeleton Loaders y Estética Premium */
    .skeleton-text {
        height: 1.5rem;
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: skeleton-loading 1.5s infinite;
        border-radius: 4px;
        width: 100%;
    }
    .skeleton-kpi { height: 2.5rem; width: 80%; margin: 4px 0; }
    .skeleton-chart { height: 300px; width: 100%; }
    .skeleton-table-row { height: 40px; width: 100%; margin: 8px 0; }
    
    @keyframes skeleton-loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    
    .card-section { 
        transition: all 0.3s ease; 
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid var(--border) !important;
        box-shadow: 0px 4px 16px rgba(0, 0, 0, 0.05) !important;
        background: var(--bg-surface);
    }
    .card-section:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1) !important; }
    
    .fade-in { animation: fadeIn 0.5s ease-out forwards; opacity: 0; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

    .analitica-table { width: 100%; border-collapse: collapse; }
    .analitica-table thead th { 
        padding: 12px 16px; font-size: 11px; font-weight: 700; text-transform: uppercase; 
        color: #64748b; background: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left;
    }
    .analitica-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .analitica-table tr:last-child td { border-bottom: none; }
    .analitica-table tr:hover { background: #f8fafc; }
    
    .opacity-50 { opacity: 0.5; }
</style>

<div class="main-full p-24">
    <!-- CABECERA -->
    <div class="section-header container-wider">
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
            <button id="btnOptimize" onclick="ejecutarOptimizacion()" class="btn-primary shadow-sm" style="background: var(--accent); border: none; padding: 10px 20px; border-radius: 10px; cursor: pointer; color: white; display: flex; ai-center gap-8;">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Optimizar ahora
            </button>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="filters-panel container-wider mb-32">
        <form id="formFiltros" method="get" action="index.php" class="filters-form" style="display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap; background: var(--bg-surface); padding: 20px; border-radius: 16px; border: 1px solid var(--border);">
            <input type="hidden" name="menu" value="Analitica">
            
            <div class="filter-group">
                <label class="fs-11 font-bold mb-6 d-block text-muted tt-uppercase"><?php echo L('history_filter_period'); ?></label>
                <select name="periodo" id="filterPeriodo" class="filter-input form-input" style="height: 44px; min-width: 140px;">
                    <option value="hoy" <?php echo $avAnalitica['filtros']['periodo'] === 'hoy' ? 'selected' : ''; ?>><?php echo L('history_period_today'); ?></option>
                    <option value="semana" <?php echo $avAnalitica['filtros']['periodo'] === 'semana' ? 'selected' : ''; ?>><?php echo L('history_period_week'); ?></option>
                    <option value="mes" <?php echo $avAnalitica['filtros']['periodo'] === 'mes' ? 'selected' : ''; ?>><?php echo L('history_period_month'); ?></option>
                    <option value="todo" <?php echo $avAnalitica['filtros']['periodo'] === 'todo' ? 'selected' : ''; ?>><?php echo L('history_period_all'); ?></option>
                    <option value="personalizado" <?php echo $avAnalitica['filtros']['periodo'] === 'personalizado' ? 'selected' : ''; ?>><?php echo L('history_period_custom'); ?></option>
                </select>
            </div>

            <div id="customDates" class="d-flex gap-20" style="<?php echo $avAnalitica['filtros']['periodo'] !== 'personalizado' ? 'display:none !important' : 'display:flex !important'; ?>">
                <div class="filter-group">
                    <label class="fs-11 font-bold mb-6 d-block text-muted tt-uppercase"><?php echo L('history_filter_since'); ?></label>
                    <input type="date" name="fechaDesde" value="<?php echo $avAnalitica['filtros']['desde']; ?>" class="filter-input form-input" style="height: 44px;">
                </div>
                <div class="filter-group">
                    <label class="fs-11 font-bold mb-6 d-block text-muted tt-uppercase"><?php echo L('history_filter_until'); ?></label>
                    <input type="date" name="fechaHasta" value="<?php echo $avAnalitica['filtros']['hasta']; ?>" class="filter-input form-input" style="height: 44px;">
                </div>
            </div>

            <div class="filter-group">
                <button type="submit" class="btn-filter h-44 px-28 d-flex ai-center gap-8">
                    <i class="fa-solid fa-arrows-rotate"></i> <?php echo L('analytics_btn_filter'); ?>
                </button>
            </div>
        </form>
    </div>

    <!-- KPI CARDS -->
    <div class="container-wider" style="margin-bottom: 25px;">
        <div class="d-grid gap-24" style="grid-template-columns: repeat(4, 1fr);">
            <div class="card-section p-24 d-flex flex-column gap-12" id="kpiSalesContainer">
                <div class="d-flex ai-center jc-between">
                    <span class="fs-11 text-muted tt-uppercase font-bold"><?php echo L('analytics_kpis_sales'); ?></span>
                    <i class="fa-solid fa-cart-shopping text-blue fs-16" style="margin-left: 12px;"></i>
                </div>
                <div id="val_kpiSales" class="fs-30 font-mono font-bold"><div class="skeleton-text skeleton-kpi"></div></div>
                <div id="sub_kpiSales" class="fs-12 text-muted"><div class="skeleton-text" style="width:60%"></div></div>
            </div>
            <div class="card-section p-24 d-flex flex-column gap-12" id="kpiProfitContainer">
                <div class="d-flex ai-center jc-between">
                    <span class="fs-11 text-muted tt-uppercase font-bold"><?php echo L('analytics_kpis_profit'); ?></span>
                    <i class="fa-solid fa-sack-dollar text-green fs-16" style="margin-left: 12px;"></i>
                </div>
                <div id="val_kpiProfit" class="fs-30 font-mono font-bold"><div class="skeleton-text skeleton-kpi"></div></div>
                <div id="sub_kpiProfit" class="fs-12 text-muted"><div class="skeleton-text" style="width:60%"></div></div>
            </div>
            <div class="card-section p-24 d-flex flex-column gap-12" id="kpiOpsContainer">
                <div class="d-flex ai-center jc-between">
                    <span class="fs-11 text-muted tt-uppercase font-bold"><?php echo L('analytics_kpis_ops'); ?></span>
                    <i class="fa-solid fa-ticket text-orange fs-16" style="margin-left: 12px;"></i>
                </div>
                <div id="val_kpiOps" class="fs-30 font-mono font-bold"><div class="skeleton-text skeleton-kpi"></div></div>
                <div id="sub_kpiOps" class="fs-12 text-muted"><div class="skeleton-text" style="width:60%"></div></div>
            </div>
            <div class="card-section p-24 d-flex flex-column gap-12" id="kpiAverageContainer">
                <div class="d-flex ai-center jc-between">
                    <span class="fs-11 text-muted tt-uppercase font-bold"><?php echo L('analytics_kpis_average_ticket'); ?></span>
                    <i class="fa-solid fa-chart-simple text-accent fs-16" style="margin-left: 12px;"></i>
                </div>
                <div id="val_kpiAverage" class="fs-30 font-mono font-bold"><div class="skeleton-text skeleton-kpi"></div></div>
                <div id="sub_kpiAverage" class="fs-12 text-muted"><div class="skeleton-text" style="width:60%"></div></div>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="container-wider d-flex flex-column gap-24">
        <!-- TOP + CATEGORIAS -->
        <div class="d-grid gap-24" style="grid-template-columns: 2fr 1fr;">
            <div class="card-section">
                <div class="p-20 border-bottom d-flex ai-center gap-10" style="background: #fffbeb;">
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
                <div class="p-20 border-bottom d-flex ai-center gap-10" style="background: #f0f9ff;">
                    <i class="fa-solid fa-chart-pie text-blue"></i>
                    <h3 class="m-0 fs-15 font-bold"><?php echo L('analytics_cat_sales_title'); ?></h3>
                </div>
                <div id="catChartContainer" class="p-24 d-flex ai-center jc-center" style="min-height: 300px;">
                    <div class="skeleton-text" style="height: 200px; width: 200px; border-radius: 50%;"></div>
                </div>
            </div>
        </div>

        <!-- IVA + EVOLUCION -->
        <div class="d-grid gap-24" style="grid-template-columns: 1fr 2fr;">
            <div class="card-section">
                <div class="p-20 border-bottom d-flex ai-center gap-10" style="background: #fdf4ff;">
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
                <div class="p-20 border-bottom d-flex ai-center gap-10" style="background: #f0fdf4;">
                    <i class="fa-solid fa-chart-line text-green"></i>
                    <h3 class="m-0 fs-15 font-bold"><?php echo L('analytics_evo_title'); ?></h3>
                </div>
                <div id="evolutionChartContainer" class="p-20" style="height: 300px;">
                    <div class="skeleton-text skeleton-chart"></div>
                </div>
            </div>
        </div>

        <!-- RANKING COMPLETO -->
        <div class="card-section">
            <div class="p-20 border-bottom d-flex jc-between ai-center gap-20" style="background: #f8fafc;">
                <div class="d-flex ai-center gap-10">
                    <i class="fa-solid fa-list-ol text-accent"></i>
                    <h3 class="m-0 fs-15 font-bold"><?php echo L('analytics_ranking_title'); ?></h3>
                </div>
                <button onclick="exportRanking()" class="btn-icon p-4-12 fs-12" style="background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; white-space: nowrap; flex-shrink: 0;">
                    <i class="fa-solid fa-file-excel mr-4"></i> <?php echo L('analytics_btn_export'); ?>
                </button>
            </div>
            <div style="max-height: 500px; overflow-y: auto;">
                <table class="analitica-table" id="rankingTable">
                    <thead style="position: sticky; top: 0; z-index: 5;">
                        <tr>
                            <th class="pl-20" style="width: 50px;"><?php echo L('analytics_th_pos'); ?></th>
                            <th><?php echo L('analytics_th_product'); ?></th>
                            <th class="text-right"><?php echo L('analytics_th_category'); ?></th>
                            <th class="text-right"><?php echo L('analytics_th_units'); ?></th>
                            <th class="text-right pr-20"><?php echo L('analytics_th_revenue'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="rankingTableBody">
                        <tr><td colspan="5" class="p-40"><div class="skeleton-text skeleton-table-row"></div><div class="skeleton-text skeleton-table-row"></div></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="p-16 border-top d-flex jc-center" id="loadMoreRankingContainer" style="display: none;">
                <button type="button" id="btnLoadMoreRanking" onclick="loadMoreRanking()" class="btn-filter" style="background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; padding: 8px 24px;">
                    <i class="fa-solid fa-plus-circle mr-8"></i> <?php echo L('analytics_ranking_load_more'); ?>
                </button>
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
                <div class="card-section p-20 d-flex ai-center gap-16" style="background: #f0fdf4; border-color: #22c55e !important;">
                    <i class="fa-solid fa-circle-check text-green fs-24"></i>
                    <div>
                        <h3 class="m-0 fs-16 text-green">¡Optimización completada!</h3>
                        <p class="m-0 fs-13 text-muted">La base de datos ya está lista. El historial completo cargará mucho más rápido.</p>
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
            alert("Error al optimizar. Es posible que el servidor haya cortado la conexión por el tamaño de la tabla. Por favor, vuelve a intentarlo; el proceso continuará donde se quedó.");
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

        function fetchRanking(offset, callback, errorCallback) {
            const params = new URLSearchParams({ menu: 'Analitica', ajax: 'loadRanking', limit: 50, offset: offset, ...searchParams });
            fetch('index.php?' + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(r => r.ok ? r.json() : Promise.reject('Error ' + r.status))
                .then(callback)
                .catch(err => {
                    console.error('Error loading ranking:', err);
                    if (errorCallback) errorCallback(err);
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
            // 4. Ranking (Diferido para dar prioridad a los KPIs y Gráficos)
            setTimeout(loadRankingInitial, 500);
        });

        // Helpers
        function updateKPI(id, val, suffix, colorize = false) {
            const valEl = document.getElementById('val_' + id);
            const subEl = document.getElementById('sub_' + id);
            if (!valEl) return;
            
            let num = parseFloat(val);
            if (isNaN(num)) num = 0;

            const formatted = num.toLocaleString(activeLocale, { minimumFractionDigits: 2 });
            valEl.innerHTML = `<span class="fade-in">${formatted}${suffix ? ' ' + suffix : ''}</span>`;
            if (colorize) valEl.style.color = num >= 0 ? '#16a34a' : '#dc2626';
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

        window.rankingOffset = 0;

        function loadRankingInitial() {
            loadMoreRanking(true);
        }

        window.loadMoreRanking = function(reset = false) {
            const btn = document.getElementById('btnLoadMoreRanking');
            const tbody = document.getElementById('rankingTableBody');
            const container = document.getElementById('loadMoreRankingContainer');
            
            if (reset) {
                window.rankingOffset = 0;
                tbody.innerHTML = '';
            }

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-8"></i> ' + (reset ? 'Cargando...' : 'Buscando más...');
            }

            fetchRanking(window.rankingOffset, data => {
                // Función auxiliar para resetear el botón
                const resetBtn = () => {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-plus-circle mr-8"></i> <?php echo L('analytics_ranking_load_more'); ?>';
                    }
                };

                if (!data || data.length === 0) {
                    if (reset) tbody.innerHTML = '<tr><td colspan="5" class="text-center p-40 text-muted italic"><?php echo L('analytics_no_data'); ?></td></tr>';
                    if (container) container.style.display = 'none';
                    resetBtn();
                    return;
                }

                appendRankingRows(data);
                window.rankingOffset += data.length;

                resetBtn();
                
                // Si devolvemos menos del límite, es que ya no hay más
                if (data.length < 50) {
                    if (container) container.style.display = 'none';
                } else {
                    if (container) container.style.display = 'flex';
                }
            }, (err) => {
                // Manejo de error en la llamada
                if (btn) {
                    btn.disabled = false;
                    btn.className = 'btn btn-outline border-red text-red';
                    btn.innerHTML = '<i class="fa-solid fa-triangle-exclamation mr-8"></i> Error al cargar. Reintentar';
                }
            });
        };

        function appendRankingRows(data) {
            const tbody = document.getElementById('rankingTableBody');
            data.forEach((p, i) => {
                const tr = document.createElement('tr');
                tr.className = 'fade-in ' + (p.unidades == 0 ? 'opacity-50' : '');
                tr.innerHTML = `
                    <td class="pl-20 font-mono text-muted">${window.rankingOffset + i + 1}</td>
                    <td><div class="font-bold">${escapeHTML(p.nombre_producto_limpio)}</div><div class="fs-11 text-muted">${p.codigo_producto}</div></td>
                    <td class="text-right"><span class="fs-10 px-6 py-2 br-4 bg-surface2 text-muted">${escapeHTML(p.categoria || 'N/A')}</span></td>
                    <td class="text-right font-bold">${p.unidades}</td>
                    <td class="text-right pr-20 text-accent font-bold">${parseFloat(p.total_recaudado).toLocaleString(activeLocale, {minimumFractionDigits:2})}€</td>
                `;
                tbody.appendChild(tr);
            });
        }

        function renderCategoryChart(data) {
            const canvas = document.createElement('canvas');
            canvas.id = 'catChart';
            const container = document.getElementById('catChartContainer');
            container.innerHTML = '';
            container.appendChild(canvas);
            
            new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: data.map(c => c.categoria || 'Sin categoría'),
                    datasets: [{
                        data: data.map(c => parseFloat(c.total)),
                        backgroundColor: ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#64748b']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom' } } }
            });
        }

        function renderEvolutionChart(data, agrupacion = 'dia') {
            const canvas = document.createElement('canvas');
            const container = document.getElementById('evolutionChartContainer');
            container.innerHTML = '';
            container.appendChild(canvas);
            
            const labels = data.map(d => {
                const date = new Date(d.fecha);
                if (agrupacion === 'mes') {
                    return date.toLocaleDateString(activeLocale, { month: 'short', year: 'numeric' });
                } else if (agrupacion === 'año') {
                    return date.getFullYear();
                }
                return date.toLocaleDateString(activeLocale, { day: '2-digit', month: '2-digit' });
            });

            new Chart(canvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        { 
                            label: '<?php echo L('analytics_chart_income'); ?>', 
                            data: data.map(d => d.ingresos), 
                            borderColor: '#3b82f6', 
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.4 
                        },
                        { 
                            label: '<?php echo L('analytics_chart_profit'); ?>', 
                            data: data.map(d => d.beneficio), 
                            borderColor: '#10b981', 
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: true,
                            tension: 0.4 
                        }
                    ]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false, 
                    interaction: { intersect: false, mode: 'index' },
                    scales: { 
                        y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
                        x: { grid: { display: false } }
                    },
                    plugins: {
                        legend: { position: 'top', align: 'end' }
                    }
                }
            });
        }

        window.exportRanking = function() {
            const params = new URLSearchParams({ ...searchParams });
            window.location.href = 'api/exportarAnalitica.php?' + params.toString();
        };

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