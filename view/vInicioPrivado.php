    <!-- NOTIFICACIONES VERIFACTU -->
    <?php
    // $resumenAEAT es calculado en el controlador (con caché de 2 min)
    if ($resumenAEAT['criticos'] > 0 || $resumenAEAT['pendientes_24h'] > 0 || $resumenAEAT['subsanaciones'] > 0): ?>
        <div class="verifactu-alert-banner">
            <div class="d-flex ai-center gap-12">
                <div class="alert-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="alert-content">
                    <div class="alert-title">Atención: Incidencias VeriFactu</div>
                    <div class="alert-msg">
                        <?php if ($resumenAEAT['criticos'] > 0): ?>
                            <span class="mr-12"><i class="fa-solid fa-circle-xmark text-red mr-4"></i> <strong><?php echo $resumenAEAT['criticos']; ?></strong> rechazos críticos</span>
                        <?php endif; ?>
                        <?php if ($resumenAEAT['pendientes_24h'] > 0): ?>
                            <span class="mr-12"><i class="fa-solid fa-clock text-amber mr-4"></i> Sin conexión hace >24h</span>
                        <?php endif; ?>
                        <?php if ($resumenAEAT['subsanaciones'] > 0): ?>
                            <span><i class="fa-solid fa-wrench text-orange mr-4"></i> <strong><?php echo $resumenAEAT['subsanaciones']; ?></strong> correcciones pendientes</span>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="index.php?irHistorial&soloIncidencias=1&periodo=todo" class="btn-alert-action">Gestionar Incidencias</a>
            </div>
        </div>
        <style>
            .verifactu-alert-banner {
                background: linear-gradient(90deg, #fff5f5 0%, #fff 100%);
                border-left: 5px solid var(--red);
                padding: 12px 24px;
                margin: 10px 20px 0 20px;
                border-radius: 12px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.05);
                border: 1px solid rgba(231, 76, 60, 0.1);
                border-left-width: 5px;
            }
            .alert-icon { font-size: 24px; color: var(--red); }
            .alert-title { font-weight: 800; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; color: #2c3e50; }
            .alert-msg { font-size: 12px; color: #5f6c7b; margin-top: 2px; }
            .btn-alert-action {
                margin-left: auto;
                padding: 6px 16px;
                background: var(--red);
                color: white;
                border-radius: 8px;
                font-size: 11px;
                font-weight: 700;
                text-decoration: none;
                transition: all 0.2s;
            }
            .btn-alert-action:hover { transform: translateY(-1px); box-shadow: 0 4px 8px rgba(231, 76, 60, 0.3); }
        </style>
    <?php endif; ?>

    <div class="main">
        <!-- CATALOG PANEL -->
        <div class="catalog-panel">
        <div class="search-bar">
            <!-- SEMÁFORO VERIFACTU -->
            <?php
            $colorSemaforo = 'text-green';
            $iconSemaforo = 'fa-circle-check';
            $tituloSemaforo = 'VeriFactu: Sistema Operativo';
            $msgSemaforo = 'Todos los registros han sido enviados y aceptados.';

            if ($resumenAEAT['criticos'] > 0 || $resumenAEAT['subsanaciones'] > 0) {
                $colorSemaforo = 'text-red animate-pulse';
                $iconSemaforo = 'fa-circle-xmark';
                $tituloSemaforo = 'VeriFactu: ATENCIÓN REQUERIDA';
                $msgSemaforo = "Existen {$resumenAEAT['criticos']} rechazos y {$resumenAEAT['subsanaciones']} subsanaciones pendientes.";
            } elseif ($resumenAEAT['pendientes_total'] > 0) {
                $colorSemaforo = 'text-amber';
                $iconSemaforo = 'fa-circle-dot';
                $tituloSemaforo = 'VeriFactu: Envíos Pendientes';
                $msgSemaforo = "Hay {$resumenAEAT['pendientes_total']} registros en cola esperando conexión.";
            }
            ?>
            <div class="verifactu-status-lite <?php echo $colorSemaforo; ?>" title="<?php echo $tituloSemaforo; ?>&#10;<?php echo $msgSemaforo; ?>" onclick="location.href='index.php?irHistorial&soloIncidencias=1&periodo=todo'">
                <i class="fa-solid <?php echo $iconSemaforo; ?>"></i>
                <span class="fs-9 fw-900 ml-4">AEAT</span>
            </div>
            <div class="verifactu-queue-timer" title="Próximo reintento de la cola VeriFactu">
                <i class="fa-solid fa-clock-rotate-left fs-10"></i>
                <span class="queue-countdown fs-10 fw-700" style="font-variant-numeric: tabular-nums; min-width: 22px; display: inline-block;">--</span>
            </div>
            <style>
                .verifactu-queue-timer {
                    display: flex; align-items: center; gap: 4px;
                    padding: 6px 9px; margin-right: 8px;
                    background: var(--surface); border: 1.5px solid var(--border);
                    border-radius: 8px; color: var(--text-muted); cursor: default;
                    font-size: 10px;
                }
            </style>
            <script>
                (function () {
                    // Datos iniciales desde el servidor
                    let nextTs = 0;
                    let isBlocked = false;
                    let lastResumen = null;

                    function updateDisplay(resumen) {
                        lastResumen = resumen;
                        isBlocked = (parseInt(resumen.criticos) > 0 || parseInt(resumen.subsanaciones) > 0);
                        const rawDelivery = resumen.proximo_envio;
                        // Normalizar a ISO 8601 (reemplazar espacio por T si MySQL lo devuelve así)
                        const nextDelivery = rawDelivery ? rawDelivery.replace(' ', 'T') : null;
                        nextTs = nextDelivery ? Math.floor(new Date(nextDelivery).getTime() / 1000) : 0;

                        // Actualizar semáforo (opcional pero recomendado)
                        const semaforo = document.querySelector('.verifactu-status-lite');
                        if (semaforo) {
                            if (isBlocked) {
                                semaforo.className = 'verifactu-status-lite text-red animate-pulse';
                                semaforo.querySelector('i').className = 'fa-solid fa-circle-xmark';
                            } else if (parseInt(resumen.pendientes_total) > 0) {
                                semaforo.className = 'verifactu-status-lite text-amber';
                                semaforo.querySelector('i').className = 'fa-solid fa-circle-dot';
                            } else {
                                semaforo.className = 'verifactu-status-lite text-green';
                                semaforo.querySelector('i').className = 'fa-solid fa-circle-check';
                            }
                        }
                    }

                    let heartbeatInFlight = false;
                    let _pollTimer = null;

                    // Reprograma el próximo fetch: 3 s si hay actividad, 30 s en reposo
                    // (el reposo de 30 s evita parar por completo: detecta ventas nuevas)
                    function schedulePoll() {
                        clearTimeout(_pollTimer);
                        const isActive = isBlocked
                            || nextTs > 0
                            || parseInt(lastResumen?.pendientes_total || 0) > 0;
                        _pollTimer = setTimeout(fetchStatus, isActive ? 3000 : 30000);
                    }

                    function fetchStatus() {
                        const now = Math.floor(Date.now() / 1000);
                        const hasPending = parseInt(lastResumen?.pendientes_total || 0) > 0;
                        const timerExpired = (nextTs > 0 && now >= nextTs) || (hasPending && nextTs === 0);

                        // Si el timer ya expiró y hay pendientes, disparar el heartbeat
                        if (timerExpired && !heartbeatInFlight && !isBlocked) {
                            heartbeatInFlight = true;
                            fetch('api/verifactu_stats.php?accion=heartbeat')
                                .then(r => r.json())
                                .then(data => {
                                    if (data.ok && data.resumen) updateDisplay(data.resumen);
                                })
                                .catch(e => console.error("Error heartbeat VF:", e))
                                .finally(() => { heartbeatInFlight = false; schedulePoll(); });
                            return; // No hace falta la llamada extra de resumen
                        }

                        // Si no ha expirado, solo actualizamos los stats
                        fetch('api/verifactu_stats.php?accion=resumen&_t=' + Date.now())
                            .then(r => r.json())
                            .then(data => {
                                if (data.ok) {
                                    lastResumen = data.resumen;
                                    updateDisplay(data.resumen);
                                }
                            })
                            .catch(e => console.error("Error polling VF:", e))
                            .finally(() => schedulePoll());
                    }

                    function tickCountdown() {
                        let text = '--';
                        let color = 'var(--text-muted)';

                        if (isBlocked) {
                            text = 'STOP';
                            color = '#ef4444';
                        } else if (nextTs > 0) {
                            const secs = Math.max(0, nextTs - Math.floor(Date.now() / 1000));
                            text = secs + 's';
                            color = (secs > 0 && secs <= 10) ? '#f57c00' : 'var(--text)';
                        }

                        const hasCriticos = parseInt(lastResumen?.criticos || 0) > 0;
                        const titleBlocked = hasCriticos
                            ? "Cola detenida — rechazos críticos pendientes"
                            : "Cola detenida — subsanaciones pendientes";

                        document.querySelectorAll('.queue-countdown').forEach(function(el) {
                            el.textContent = text;
                            el.style.color = color;
                            if (isBlocked) {
                                el.classList.add('animate-pulse');
                                el.title = titleBlocked;
                            } else {
                                el.classList.remove('animate-pulse');
                                el.title = nextTs > 0 ? "Próximo envío en " + text : "Sin envíos pendientes";
                            }
                        });
                    }

                    // Carga inicial
                    updateDisplay(<?php echo json_encode($resumenAEAT); ?>);
                    
                    tickCountdown();
                    setInterval(tickCountdown, 1000); // Tick visual cada segundo (siempre)
                    schedulePoll();                   // Inicia el bucle de polling adaptativo
                })();
            </script>
            <style>
                .verifactu-status-lite {
                    display: flex;
                    align-items: center;
                    padding: 8px 12px;
                    background: var(--surface);
                    border: 2px solid currentColor;
                    border-radius: 10px;
                    cursor: pointer;
                    transition: all 0.2s;
                    margin-right: 8px;
                }
                .verifactu-status-lite:hover { transform: scale(1.05); filter: brightness(1.1); }
                .animate-pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
                @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
            </style>

            <div class="search-input-fancy">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input
                    type="text"
                    id="searchInput"
                    oninput="app.handleSearch(this.value)"
                    placeholder="<?php echo L('tpv_search_placeholder'); ?>" />
            </div>
            <button onclick="app.toggleAdvancedFilters()" class="btn-filter-toggle p-10 br-10 bg-surface border-2 cursor-pointer transition-all" title="<?php echo L('tpv_filters_advanced'); ?>" aria-label="<?php echo L('tpv_filters_advanced'); ?>" aria-expanded="false" aria-controls="advancedFilters" id="btnAdvancedFilters">
                <i class="fa-solid fa-filter" aria-hidden="true"></i>
            </button>
            <button onclick="app.abrirModalComodin()" class="btn-filter-toggle p-10 br-10 bg-accent text-white border-2 border-accent cursor-pointer transition-all" title="<?php echo L('tpv_add_custom_product'); ?>" aria-label="<?php echo L('tpv_add_custom_product'); ?>" style="margin-left: 8px;">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> <i class="fa-solid fa-box-open" aria-hidden="true"></i>
            </button>
        </div>

        <!-- ADVANCED FILTERS PANEL -->
        <div id="advancedFilters" class="advanced-filters-panel d-none bg-surface p-16 br-12 border-2 mb-10 shadow-sm">
            <?php
            // Extraer todos los atributos únicos usados en los productos activos para generar los filtros
            $atributosDisponibles = [];
            if (isset($avInicioPrivado) && is_array($avInicioPrivado) && isset($avInicioPrivado['productos']) && is_array($avInicioPrivado['productos'])) {
                foreach ($avInicioPrivado['productos'] as $prod) {
                    if ($prod['activo'] && !empty($prod['atributos'])) {
                        $attrArr = json_decode($prod['atributos'], true);
                        if (is_array($attrArr)) {
                            foreach ($attrArr as $attr) {
                                if (!in_array($attr, $atributosDisponibles)) {
                                    $atributosDisponibles[] = $attr;
                                }
                            }
                        }
                    }
                }
                sort($atributosDisponibles);
            }
            ?>
            <div class="grid-4 gap-12 ai-end">
                <div class="form-group">
                    <label class="form-label fs-11 tt-uppercase opacity-70"><?php echo L('tpv_price_min'); ?></label>
                    <input type="number" id="filterPriceMin" class="form-input fs-13" placeholder="0.00" oninput="app.applyAdvancedFilters()">
                </div>
                <div class="form-group">
                    <label class="form-label fs-11 tt-uppercase opacity-70"><?php echo L('tpv_price_max'); ?></label>
                    <input type="number" id="filterPriceMax" class="form-input fs-13" placeholder="999.99" oninput="app.applyAdvancedFilters()">
                </div>
                <div class="form-group">
                    <label class="form-label fs-11 tt-uppercase opacity-70"><?php echo L('tpv_stock'); ?></label>
                    <select id="filterStock" class="form-input fs-13" onchange="app.applyAdvancedFilters()">
                        <option value="all"><?php echo L('tpv_all'); ?></option>
                        <option value="in-stock"><?php echo L('tpv_in_stock'); ?></option>
                        <option value="low-stock"><?php echo L('tpv_low_stock'); ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label fs-11 tt-uppercase opacity-70"><?php echo L('tpv_sort_by'); ?></label>
                    <select id="filterSort" class="form-input fs-13" onchange="app.applyAdvancedFilters()">
                        <option value="name-asc"><?php echo L('tpv_sort_name_asc'); ?></option>
                        <option value="name-desc"><?php echo L('tpv_sort_name_desc'); ?></option>
                        <option value="price-asc"><?php echo L('tpv_sort_price_asc'); ?></option>
                        <option value="price-desc"><?php echo L('tpv_sort_price_desc'); ?></option>
                        <option value="stock-asc"><?php echo L('tpv_sort_stock_asc'); ?></option>
                        <option value="stock-desc"><?php echo L('tpv_sort_stock_desc'); ?></option>
                    </select>
                </div>

                <?php if (!empty($atributosDisponibles)): ?>
                    <div style="grid-column: span 4;">
                        <label class="form-label fs-11 tt-uppercase opacity-70 mb-8"><i class="fa-solid fa-tags"></i> <?php echo L('tpv_additional_tags'); ?></label>
                        <div class="attr-tabs" id="attrTabs" style="display:flex; flex-wrap:wrap; gap:8px;">
                            <?php foreach ($atributosDisponibles as $attr): ?>
                                <button class="attr-tab attr-tab-btn d-inline-flex ai-center gap-6"
                                    data-attr="<?php echo htmlspecialchars($attr); ?>"
                                    onclick="app.selectTag('<?php echo htmlspecialchars($attr); ?>')"
                                    ondblclick="event.preventDefault(); event.stopPropagation();"
                                    draggable="false"
                                    style="font-size: 11px; padding: 6px 14px;">
                                    <i class="fa-solid fa-tag" style="opacity: 0.5;"></i> <?php echo htmlspecialchars($attr); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>


        <div class="cat-tabs-wrapper">
            <div class="scroll-shadow shadow-left" id="shadowLeft"></div>
            <div class="cat-tabs-container">
                <div class="cat-tabs" id="catTabs">
                    <button class="cat-tab active" data-cat="all" onclick="app.selectCategory('all')">
                        <i class="fa-solid fa-border-all"></i>
                        <span><?php echo L('tpv_cat_all'); ?></span>
                    </button>
                    <?php if (isset($avInicioPrivado) && is_array($avInicioPrivado) && isset($avInicioPrivado['categorias']) && is_array($avInicioPrivado['categorias'])): ?>
                        <?php foreach ($avInicioPrivado['categorias'] as $c): ?>
                            <button class="cat-tab" data-cat="<?php echo htmlspecialchars($c['codigo']); ?>" onclick="app.selectCategory('<?php echo htmlspecialchars($c['codigo']); ?>')">
                                <i class="fa-solid fa-layer-group"></i>
                                <span><?php echo htmlspecialchars($c['nombre']); ?></span>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <button class="cat-tab" data-cat="packs" style="color: var(--accent);" onclick="app.selectCategory('packs')">
                        <i class="fa-solid fa-cubes"></i>
                        <span>Packs</span>
                    </button>
                    <button class="cat-tab" data-cat="baja" style="color: var(--red); border-color: rgba(192, 57, 43, 0.2);" onclick="app.selectCategory('baja')">
                        <i class="fa-solid fa-arrow-trend-down"></i>
                        <span><?php echo L('tpv_cat_discontinued'); ?></span>
                    </button>
                </div>
            </div>
            <div class="scroll-shadow shadow-right" id="shadowRight"></div>
        </div>





        <div class="products-grid" id="productsGrid"></div>
    </div>

    <script>
        const DB_TARIFAS = <?php echo json_encode(TarifaPrecioPDO::listarActivas()); ?>;
        window.TPV_LANG = {
            'insufficient_cash': '<?php echo L('tpv_error_insufficient_cash'); ?>'
        };

        // Gestión de sombras en el scroll de categorías
        document.addEventListener('DOMContentLoaded', () => {
            const tabs = document.getElementById('catTabs');
            const shadowL = document.getElementById('shadowLeft');
            const shadowR = document.getElementById('shadowRight');
            
            if (tabs && shadowL && shadowR) {
                const updateShadows = () => {
                    const scrollLeft = tabs.scrollLeft;
                    const maxScroll = tabs.scrollWidth - tabs.clientWidth;
                    
                    shadowL.style.opacity = scrollLeft > 10 ? '1' : '0';
                    shadowR.style.opacity = scrollLeft < maxScroll - 10 ? '1' : '0';
                };
                
                tabs.addEventListener('scroll', updateShadows);
                window.addEventListener('resize', updateShadows);
                // Pequeño delay para que el renderizado se complete
                setTimeout(updateShadows, 500);
                
                // Observador por si cambian las categorías dinámicamente
                const observer = new MutationObserver(updateShadows);
                observer.observe(tabs, { childList: true });
            }
        });
    </script>

    <script>
        // ── Modal DNI Rápido ─────────────────────────────────────────────────────
        window._dniRapidoClienteId = null;

        window.abrirModalDniRapido = function(clienteId, nombreCliente) {
            window._dniRapidoClienteId = clienteId;
            const el = document.getElementById('dniRapidoNombreCliente');
            if (el) el.textContent = nombreCliente || '';
            const inp = document.getElementById('dniRapidoValor');
            if (inp) { inp.value = ''; setTimeout(() => inp.focus(), 150); }
            const errEl = document.getElementById('dniRapidoError');
            if (errEl) errEl.classList.add('d-none');
            const modal = document.getElementById('modalDniRapido');
            if (modal) modal.classList.add('visible');
        };

        window.cerrarModalDniRapido = function() {
            const modal = document.getElementById('modalDniRapido');
            if (modal) modal.classList.remove('visible');
            window._dniRapidoClienteId = null;
        };

        window.guardarDniRapido = async function() {
            const id = window._dniRapidoClienteId;
            if (!id) return;
            const nif = (document.getElementById('dniRapidoValor')?.value || '').trim().toUpperCase();
            const tipo = document.getElementById('dniRapidoTipo')?.value || '01';
            const pais = (document.getElementById('dniRapidoPais')?.value || 'ES').trim().toUpperCase();
            const errEl = document.getElementById('dniRapidoError');
            if (errEl) errEl.classList.add('d-none');

            if (!nif) {
                if (errEl) { errEl.textContent = 'Introduce el DNI/NIF.'; errEl.classList.remove('d-none'); }
                return;
            }
            try {
                const r = await fetch('api/gestionCliente.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ accion: 'actualizarNif', id, nif, aeat_id_type: tipo, aeat_codigo_pais: pais }),
                });
                const data = await r.json();
                if (data.ok) {
                    // Actualizar AppState para que la venta actual ya tenga el NIF
                    if (window.AppState) {
                        if (AppState.socioActual && String(AppState.socioActual.id) === String(id)) AppState.socioActual.nif = data.nif;
                        if (AppState.clienteSeleccionado && String(AppState.clienteSeleccionado.id) === String(id)) AppState.clienteSeleccionado.nif = data.nif;
                    }
                    cerrarModalDniRapido();
                    if (window.Utils) Utils.showToast('DNI guardado correctamente', 'success');
                } else {
                    if (errEl) { errEl.textContent = data.error || 'Error al guardar'; errEl.classList.remove('d-none'); }
                }
            } catch (e) {
                if (errEl) { errEl.textContent = 'Error de conexión'; errEl.classList.remove('d-none'); }
            }
        };
    </script>

    <!-- ORDER PANEL -->
    <div class="order-panel">
        <div class="order-header">
            <div class="d-flex ai-center gap-10">
                <span class="order-title"><?php echo L('tpv_order'); ?></span>
                <span class="order-count" id="orderCount">0</span>
            </div>
            <div class="d-flex ai-center gap-8">
                <button id="btnResumeSale" class="btn-tpv-icon active-accent" style="display: none;" onclick="app.resumeSale()" title="<?php echo L('tpv_resume'); ?>" aria-label="<?php echo L('tpv_resume'); ?>"><i class="fa-solid fa-play" aria-hidden="true"></i></button>
                <button id="btnPostponeSale" class="btn-tpv-icon" style="display: none;" onclick="app.postponeSale()" title="<?php echo L('tpv_postpone'); ?>" aria-label="<?php echo L('tpv_postpone'); ?>"><i class="fa-solid fa-pause" aria-hidden="true"></i></button>
                <button class="btn-clear p-0 bg-transparent border-0 opacity-60 hover-opacity-100 fs-11 ml-8" onclick="app.clearCart()"><?php echo L('tpv_clear'); ?></button>
            </div>
        </div>

        <div class="order-items" id="orderItems">
            <div class="empty-cart" id="emptyCart">
                <div class="empty-cart-icon">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
                <div><?php echo L('tpv_empty_cart'); ?><br /><?php echo L('tpv_empty_cart_sub'); ?></div>
            </div>
        </div>

        <div class="order-totals">
            <div class="total-row" id="rowSubtotal">
                <span><?php echo L('tpv_subtotal'); ?></span>
                <span id="subtotal">0,00 €</span>
            </div>
            <div id="ivaBreakdown" class="iva-breakdown">
                <!-- Dinámico -->
            </div>
            <div
                class="total-row d-none text-green"
                id="discountRow">
                <span id="discountLabel"><?php echo L('tpv_discount'); ?></span>
                <span id="discountAmt">-0,00 €</span>
            </div>
            <div class="total-row main">
                <span><?php echo L('tpv_total'); ?></span>
                <span id="totalAmt">0,00 €</span>
            </div>
        </div>



        <div class="payment-section">
            <div class="payment-label"><?php echo L('tpv_payment_method'); ?></div>
            <div class="payment-methods">
                <button
                    class="pay-btn selected"
                    data-method="efectivo"
                    onclick="selectSidebarPayment(this)">
                    <i class="fa-solid fa-money-bill-1-wave"></i>
                    <?php echo L('tpv_method_cash'); ?>
                </button>
                <button
                    class="pay-btn"
                    data-method="tarjeta"
                    onclick="selectSidebarPayment(this)">
                    <i class="fa-solid fa-credit-card"></i>
                    <?php echo L('tpv_method_card'); ?>
                </button>
                <button
                    class="pay-btn"
                    data-method="bizum"
                    onclick="selectSidebarPayment(this)">
                    <i class="fa-solid fa-mobile-screen-button"></i>
                    <?php echo L('tpv_method_bizum'); ?>
                </button>
                <!-- Botón Vale eliminado y movido dentro del modal de cliente -->
                <button
                    class="pay-btn"
                    data-method="mixto"
                    id="btnMixtoSidebar"
                    onclick="selectSidebarPayment(this)">
                    <i class="fa-solid fa-layer-group"></i>
                    <?php echo L('tpv_method_mixed'); ?>
                </button>
            </div>

            <div class="discount-row">
                <input
                    class="discount-input"
                    type="text"
                    id="discountCode"
                    placeholder="<?php echo L('tpv_discount_placeholder'); ?>" />
                <button class="discount-apply" onclick="app.applyDiscount()">
                    <?php echo L('tpv_apply'); ?>
                </button>
            </div>
            <span id="err-discount" class="form-error"></span>

            <div class="toggle-row">
                <div class="toggle-label text-accent">
                    <i class="fa-solid fa-file-invoice"></i> <?php echo L('tpv_require_invoice'); ?>
                </div>
                <label class="switch">
                    <input type="checkbox" id="facturaToggle" onchange="app.handleFacturaToggle(this.checked)">
                    <span class="slider"></span>
                </label>
            </div>

            <button
                class="charge-btn"
                id="chargeBtn"
                onclick="app.processPayment()"
                disabled>
                <?php echo L('tpv_charge'); ?> <span id="chargeTotal">0,00 €</span>
            </button>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
    <div class="modal modal-content gap-14 ai-stretch w-800" style="max-width: 800px; border-radius: 20px; overflow: hidden;">
        <div class="modal-header mb-0">
            <div class="modal-title fs-16" id="editModalTitle"><?php echo L('modal_edit_title'); ?></div>
            <button onclick="document.getElementById('editModal').classList.remove('visible')" class="btn-close-modal" aria-label="<?php echo L('modal_cancel'); ?>">×</button>
        </div>
        <input type="hidden" id="editId" />
        <input type="hidden" id="editCost" />
        <input type="hidden" id="editAtributos" />
        <div class="form-grid">
                <div class="form-group">
                <label class="form-label"><?php echo L('modal_label_img'); ?></label>
                <div class="d-flex ai-center gap-12">
                    <div id="editImgPreview" class="prod-img-preview border-none bg-surface2" style="width: 80px; height: 80px; flex-shrink: 0;">
                        <i class="fa-solid fa-image"></i>
                    </div>
                    <div class="flex-1">
                        <input type="file" id="editFile" accept="image/*" class="d-none" onchange="previewImageTPV(this, 'edit')">
                        <button type="button" onclick="document.getElementById('editFile').click()" class="btn-icon w-auto h-auto p-12-20 fs-13 gap-8 full-width">
                            <i class="fa-solid fa-upload"></i> <?php echo L('modal_btn_change_img'); ?>
                        </button>
                        <input type="hidden" id="editEmoji" />
                    </div>
                </div>
            </div>
            <div class="form-group flex-1">
                <label class="form-label"><?php echo L('modal_label_name'); ?></label>
                <input id="editName" class="form-input" />
                <span class="form-error" id="err-editNombre"></span>
            </div>
            <div class="form-group-wrap grid-2">
                <div class="form-group">
                    <label class="form-label"><?php echo L('modal_label_ref'); ?></label>
                    <input id="editSku" class="form-input font-mono" />
                </div>
                <div class="form-group grid-col-span-2">
                    <div class="grid-2 gap-12">
                        <div class="form-group mb-0">
                            <input id="editPrice" class="form-input font-mono text-right fs-16" type="text" oninput="updateEditMargin()" placeholder="0.00" />
                            
                            <!-- Alta Precisión Checkbox TPV -->
                            <div class="mt-8 d-flex ai-center gap-8 px-4 py-2 br-6 bg-surface2 border-1 transition-all hover-border-accent" style="width: fit-content;">
                                <input type="checkbox" id="editMantenerPrecision" class="form-checkbox cursor-pointer" onchange="toggleEditPrecisionUI()">
                                <label for="editMantenerPrecision" class="fs-11 fw-700 text-muted cursor-pointer" style="text-transform: uppercase; letter-spacing: 0.5px;">
                                    <i class="fa-solid fa-bullseye mr-4 opacity-50"></i> <?php echo L('prod_label_precision_price'); ?>
                                </label>
                            </div>
                            
                            <span class="form-error" id="err-editPrecio"></span>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label"><?php echo L('modal_label_margin'); ?></label>
                            <div class="form-input d-flex ai-center jc-space-between font-mono font-bold bg-surface2 border-none" style="height: 44px; color: var(--green);">
                                <span id="editMarginPercent" class="fs-12 opacity-80 pl-8">0%</span>
                                <span id="editMarginDisplay" class="pr-8">0,00 €</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo L('modal_label_warranty'); ?></label>
                    <input id="editMesesGarantia" class="form-input font-mono text-right" type="number" min="0" max="120" step="1" placeholder="24" />
                </div>
            </div>

            <button class="btn-save mt-4 full-width" onclick="saveEdit()">
                <?php echo L('modal_save'); ?>
            </button>
        </div>
    </div>

    <!-- ADD MODAL -->
    <div class="modal-overlay" id="addModal" role="dialog" aria-modal="true" aria-labelledby="addModalTitle">
        <div class="modal modal-content gap-14 ai-stretch w-800" style="max-width: 800px; border-radius: 20px; overflow: hidden;">
            <div class="modal-header mb-0">
                <div class="modal-title fs-16" id="addModalTitle"><?php echo L('modal_new_title'); ?></div>
                <button onclick="document.getElementById('addModal').classList.remove('visible')" class="btn-close-modal" aria-label="<?php echo L('modal_cancel'); ?>">×</button>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label"><?php echo L('modal_label_img'); ?></label>
                    <div class="d-flex ai-center gap-12">
                        <div id="addImgPreview" class="prod-img-preview border-none bg-surface2" style="width: 80px; height: 80px; flex-shrink: 0;">
                            <i class="fa-solid fa-image"></i>
                        </div>
                        <div class="flex-1">
                            <input type="file" id="addFile" accept="image/*" class="d-none" onchange="previewImageTPV(this, 'add')">
                            <button type="button" onclick="document.getElementById('addFile').click()" class="btn-icon w-auto h-auto p-12-20 fs-13 gap-8 full-width">
                                <i class="fa-solid fa-upload"></i> <?php echo L('modal_btn_upload_img'); ?>
                            </button>
                            <input type="hidden" id="addEmoji" />
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo L('modal_label_name'); ?></label>
                    <input id="addName" class="form-input" placeholder="<?php echo L('client_placeholder_name'); ?>" />
                    <span class="form-error" id="err-addNombre"></span>
                </div>
                <div class="form-group-wrap grid-3">
                    <div class="form-group">
                        <label class="form-label"><?php echo L('modal_label_ref'); ?></label>
                        <input id="addSku" class="form-input font-mono" placeholder="PRO-001" />
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php echo L('modal_label_price'); ?></label>
                        <input id="addPrice" class="form-input font-mono text-right" type="text" placeholder="0.00" />
                        <span class="form-error" id="err-addPrecio"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?php echo L('modal_label_iva'); ?></label>
                        <input id="addIva" class="form-input font-mono text-right" type="text" value="21" />
                    </div>
                </div>

                <label class="form-label"><?php echo L('modal_label_cat'); ?></label>
                <select id="addCat" class="form-input">
                    <?php if (isset($avInicioPrivado['categorias']) && is_array($avInicioPrivado['categorias'])): ?>
                        <?php foreach ($avInicioPrivado['categorias'] as $c): ?>
                            <option value="<?php echo htmlspecialchars($c['codigo']); ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

        </div>
        <button class="btn-save mt-4 full-width" onclick="guardarNuevoProducto()">
            <?php echo L('modal_create'); ?>
        </button>
    </div>
</div>

<!-- DELETE CONFIRM MODAL -->
<div class="modal-overlay" id="deleteModal" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle" style="display: flex !important; align-items: center !important; justify-content: center !important;">
    <div class="modal modal-content gap-16" style="max-width: 420px; margin: 0 auto; align-self: center;">
        <div class="modal-title" id="deleteModalTitle"><?php echo L('modal_delete_title'); ?></div>
        <div class="modal-sub">
            <?php echo L('modal_delete_confirm'); ?> <strong id="delName"></strong><?php echo L('modal_delete_warning'); ?>
        </div>
        <div class="modal-footer d-flex jc-center gap-8 mt-24">
            <button onclick="document.getElementById('deleteModal').classList.remove('visible')" class="btn-cancel br-12"><?php echo L('modal_cancel'); ?></button>
            <button id="delConfirmBtn" class="btn-danger br-12"><?php echo L('modal_delete_btn'); ?></button>
        </div>
    </div>
</div>


<!-- MODAL APERTURA DE CAJA (antes de poder vender) -->
<div class="modal-overlay <?= isset($_SESSION['mensajeErrorCaja']) ? 'visible' : '' ?>" id="aperturaCajaModal" role="dialog" aria-modal="true" aria-labelledby="aperturaCajaModalTitle">
    <div class="modal modal-content gap-16 ai-stretch w-360" style="border-radius: 20px; overflow: hidden;">
        <div class="modal-title text-center" id="aperturaCajaModalTitle"><?php echo L('cash_open_title'); ?></div>
        <p class="text-muted fs-13 text-center">
            <?php echo L('cash_open_sub'); ?>
        </p>
        <form method="post" action="index.php" class="d-flex flex-column gap-12">
            <?php 
            $pendientes = CajaTurnoPDO::obtenerTurnosPendientesArqueo();
            if (!empty($pendientes)): 
            ?>
                <div class="bg-red-light text-red p-20 br-16 border-2 d-flex flex-column ai-center text-center gap-12" style="border-color: rgba(231, 76, 60, 0.3); background: rgba(231, 76, 60, 0.05);">
                    <div class="w-48 h-48 br-50 bg-red text-white d-flex ai-center jc-center shadow-md">
                        <i class="fa-solid fa-lock fs-20"></i>
                    </div>
                    <div>
                        <div class="fs-15 font-bold"><?php echo L('cash_open_locked'); ?></div>
                        <p class="fs-12 opacity-80 mt-4"><?php echo L('cash_open_locked_msg'); ?></p>
                    </div>
                    <a href="index.php?irCierreCaja" class="btn-save bg-red border-0 px-20 py-10 br-10 fs-12 fw-700 shadow-sm" style="text-decoration: none;">
                        <i class="fa-solid fa-vault mr-6"></i> <?php echo L('cash_open_btn_resolve'); ?>
                    </a>
                </div>
            <?php else: ?>
                <?php if (isset($_SESSION['mensajeErrorCaja'])): ?>
                    <div class="bg-red-light text-red p-12 br-8 fs-12 mb-10 border font-bold">
                        <?= htmlspecialchars($_SESSION['mensajeErrorCaja']); unset($_SESSION['mensajeErrorCaja']); ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label fs-13"><?php echo L('cash_open_label_initial'); ?></label>
                    <input type="number" step="0.01" min="0.01" name="fondoInicial" class="form-input font-mono fs-16 text-right" placeholder="0.00" value="<?= htmlspecialchars(number_format($avInicioPrivado['fondoSugerido'], 2, '.', '')) ?>" required autofocus>
                </div>
                <div class="modal-footer full-width mt-10">
                    <button type="submit" name="abrirCaja" class="btn-save w-full h-48 fs-15 font-bold br-12 shadow-md">
                        <i class="fa-solid fa-unlock-keyhole mr-8"></i> <?php echo L('cash_open_btn_open'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- MODAL 1: TIPO DE CLIENTE (aparece al pulsar Cobrar) -->
<div class="modal-overlay" id="clienteModal" role="dialog" aria-modal="true" aria-labelledby="clienteModalTitle">
    <div class="modal modal-content ai-stretch" style="max-width: 960px; width: 96vw; border-radius: 20px; overflow: hidden; padding: 0; gap: 0;">
        <!-- Header -->
        <div class="d-flex jc-space-between ai-center" style="padding: 18px 28px; border-bottom: 1px solid var(--border);">
            <div class="modal-title fs-18 font-bold m-0" id="clienteModalTitle"><?php echo L('client_type_title'); ?></div>
            <button onclick="cerrarModalCliente()" class="btn-close-modal" style="background:none; border:none; font-size:24px; cursor:pointer; color:var(--text-muted);" aria-label="<?php echo L('modal_cancel'); ?>">&times;</button>
        </div>

        <!-- 2-column body -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0; max-height:78vh; min-height:0;">

            <!-- ── LEFT: Cliente ── -->
            <div style="padding: 24px 28px; border-right: 1px solid var(--border); overflow-y: auto; display: flex; flex-direction: column; gap: 14px; background: var(--surface2);"><label style="font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:var(--text-muted); margin-bottom:-6px;"><?php echo L('client_type_title'); ?></label>

                <!-- Tipo de cliente -->
                <div class="grid-3 gap-8">
                    <button id="btnParticular" onclick="app.seleccionarTipoCliente('particular')"
                        class="p-12-8 br-12 border-2 bg-surface2 cursor-pointer fs-12 d-flex flex-column ai-center gap-8 active-scale h-auto">
                        <i class="fa-solid fa-user fs-22"></i>
                        <span class="font-bold"><?php echo L('client_particular'); ?></span>
                    </button>
                    <button id="btnSocio" onclick="app.seleccionarTipoCliente('socio')"
                        class="p-12-8 br-12 border-2 bg-surface2 cursor-pointer fs-12 d-flex flex-column ai-center gap-8 active-scale h-auto">
                        <i class="fa-solid fa-id-card fs-22 text-accent"></i>
                        <span class="font-bold"><?php echo L('client_socio'); ?></span>
                    </button>
                    <button id="btnEmpresa" onclick="app.seleccionarTipoCliente('empresa')"
                        class="p-12-8 br-12 border-2 bg-surface2 cursor-pointer fs-12 d-flex flex-column ai-center gap-8 active-scale h-auto">
                        <i class="fa-solid fa-building fs-22"></i>
                        <span class="font-bold"><?php echo L('client_empresa'); ?></span>
                    </button>
                </div>

                <!-- Buscador genérico -->
                <div id="clienteBusquedaGenerica" class="d-none flex-column gap-8 p-12 bg-surface2 br-8 border-2">
                    <div class="d-flex gap-8">
                        <input id="clienteSearch" class="form-input fs-13 flex-1" placeholder="<?php echo L('client_search_placeholder'); ?>" aria-label="<?php echo L('client_search_placeholder'); ?>" />
                        <button type="button" onclick="app.buscarClienteGuardado()" class="btn-save w-auto p-4-12" aria-label="Buscar cliente">
                            <i class="fa-solid fa-search" aria-hidden="true"></i>
                        </button>
                        <button type="button" onclick="app.mostrarRegistroCliente()" title="<?php echo L('client_new_quick_btn'); ?>" aria-label="<?php echo L('client_new_quick_btn'); ?>" style="width:32px;height:32px;min-width:32px;border-radius:50%;border:1px solid var(--border);background:var(--surface3);color:var(--text);cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fa-solid fa-plus fs-11" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div id="clienteResultados" class="fs-12 mt-4 text-accent font-bold"></div>
                    <button id="btnAddCliente" onclick="app.mostrarRegistroCliente()" class="cat-tab p-4-8 fs-11 d-none"><?php echo L('client_new_btn'); ?></button>
                </div>

                <!-- Registro de Cliente -->
                <div id="clienteRegistro" class="d-none flex-column gap-8 p-12 bg-surface2 br-8 border-2">
                    <div id="clienteRegistroTitulo" class="form-label fs-12 font-bold"><?php echo L('client_new_title'); ?></div>
                    <input id="newClienteNombre" class="form-input fs-12" placeholder="<?php echo L('client_placeholder_name'); ?>" />
                    <div class="d-flex gap-4">
                        <select id="newClienteIdType" class="form-input fs-11 p-2-4" style="width: 100px;">
                            <option value="01">01 - NIF ES</option>
                            <option value="02">02 - NIF IVA</option>
                            <option value="03">03 - PASAP.</option>
                            <option value="04">04 - ID OFIC.</option>
                            <option value="05">05 - CERTIF.</option>
                            <option value="06">06 - OTRO</option>
                        </select>
                        <input id="newClienteNif" class="form-input fs-12 font-mono flex-1" placeholder="Documento" />
                        <input id="newClientePais" class="form-input fs-12 font-mono" style="width: 45px;" placeholder="ES" maxlength="2" />
                    </div>
                    <div class="d-flex gap-8">
                        <button onclick="app.cancelarRegistroCliente()" class="btn-cancel fs-11 p-4"><?php echo L('modal_cancel'); ?></button>
                        <button onclick="app.guardarNuevoCliente()" class="btn-save fs-11 p-4"><?php echo L('client_btn_save_use'); ?></button>
                    </div>
                </div>

                <!-- Buscador de Socio -->
                <div id="socioBusqueda" class="d-none flex-column gap-8 p-12 bg-surface2 br-8 border-2">
                    <div class="d-flex gap-8">
                        <input id="socioSearch" class="form-input fs-13 flex-1" placeholder="<?php echo L('client_search_socio_placeholder'); ?>" aria-label="<?php echo L('client_search_socio_placeholder'); ?>" />
                        <button onclick="app.buscarSocio()" class="btn-save w-auto p-4-12" aria-label="Buscar socio"><i class="fa-solid fa-search" aria-hidden="true"></i></button>
                    </div>
                    <div id="socioInfo" class="fs-12 mt-4 text-accent font-bold"></div>
                    <button id="btnAddSocio" onclick="app.mostrarRegistroSocio()" class="cat-tab p-4-8 fs-11 d-none"><?php echo L('client_new_socio_btn'); ?></button>
                </div>

                <!-- Registro de Socio -->
                <div id="socioRegistro" class="d-none flex-column gap-8 p-12 bg-surface2 br-8 border-2">
                    <div class="form-label fs-12 font-bold"><?php echo L('client_new_socio_title'); ?></div>
                    <input id="newSocioNombre" class="form-input fs-12" placeholder="Nombre completo" />
                    <div class="d-flex gap-4">
                        <select id="newSocioIdType" class="form-input fs-11 p-2-4" style="width: 100px;">
                            <option value="01">01 - NIF ES</option>
                            <option value="02">02 - NIF IVA</option>
                            <option value="03">03 - PASAP.</option>
                            <option value="04">04 - ID OFIC.</option>
                            <option value="05">05 - CERTIF.</option>
                            <option value="06">06 - OTRO</option>
                        </select>
                        <input id="newSocioNif" class="form-input fs-12 font-mono flex-1" placeholder="Documento" />
                        <input id="newSocioPais" class="form-input fs-12 font-mono" style="width: 45px;" placeholder="ES" maxlength="2" />
                    </div>
                    <div class="d-flex gap-8">
                        <button onclick="app.cancelarRegistroSocio()" class="btn-cancel fs-11 p-4"><?php echo L('modal_cancel'); ?></button>
                        <button onclick="app.guardarNuevoSocio()" class="btn-save fs-11 p-4"><?php echo L('client_btn_save_use'); ?></button>
                    </div>
                </div>

                <!-- Datos de empresa -->
                <div id="empresaDatos" class="d-none flex-column gap-8">
                    <div class="form-group">
                        <label class="form-label" id="labelEmpresaNombre"><?php echo L('client_label_razon'); ?></label>
                        <input id="empresaNombre" class="form-input" placeholder="<?php echo L('client_label_razon'); ?>" />
                        <span class="form-error" id="err-empresaNombre"></span>
                    </div>
                    <div class="form-group">
                        <label class="form-label" id="labelEmpresaNif"><?php echo L('client_label_nif'); ?></label>
                        <div class="d-flex gap-4">
                            <select id="empresaIdType" class="form-input fs-11 p-2-4" style="width: 100px;">
                                <option value="01">01 - NIF ES</option>
                                <option value="02">02 - NIF IVA</option>
                                <option value="03">03 - PASAP.</option>
                                <option value="04">04 - ID OFIC.</option>
                                <option value="05">05 - CERTIF.</option>
                                <option value="06">06 - OTRO</option>
                            </select>
                            <input id="empresaNif" class="form-input font-mono flex-1" placeholder="Documento" />
                            <input id="empresaPais" class="form-input font-mono" style="width: 45px;" placeholder="ES" maxlength="2" />
                        </div>
                        <span class="form-error" id="err-empresaNif"></span>
                    </div>
                </div>

                <!-- Vales del cliente -->
                <div id="clienteValesContainer" class="d-none flex-column gap-8 p-12 bg-surface2 br-8 border-2">
                    <div class="form-label fs-13 font-bold d-flex ai-center gap-8">
                        <i class="fa-solid fa-ticket text-accent"></i> <?php echo L('tpv_available_vouchers'); ?>
                    </div>
                    <div id="listadoValesCliente" class="d-flex flex-column gap-4"></div>
                    <div id="valeAplicadoResumen" class="d-none mt-4 p-8 br-6 fs-12 bg-accent-light text-accent border-2">
                        <div class="d-flex jc-space-between ai-center">
                            <span><?php echo L('tpv_voucher_applied'); ?>:</span>
                            <span id="valeAplicadoTotal" class="font-bold">0,00 €</span>
                        </div>
                    </div>
                </div>

                <!-- Puntos de fidelidad -->
                <div id="clientePuntosContainer" class="d-none flex-column gap-8 p-12 bg-surface2 br-8 border-2" style="border-color: var(--accent);">
                    <div class="form-label fs-13 font-bold d-flex ai-center jc-space-between gap-8">
                        <div class="d-flex ai-center gap-8">
                            <i class="fa-solid fa-star text-accent"></i> <?php echo L('tpv_loyalty_points'); ?>
                        </div>
                        <span class="fs-14 font-mono font-bold text-accent"><span id="labelPuntosDisponibles">0</span> pts</span>
                    </div>
                    <div id="puntosCanjeArea" class="d-flex flex-column gap-8 mt-4 p-8 br-6 bg-surface border-1">
                        <div class="fs-11 text-muted" id="puntosCanjeMsg">Cargando puntos...</div>
                        <div class="d-flex ai-center gap-8 d-none" id="controlesCanjePuntos">
                            <select id="puntosAcanjearSelect" class="form-input fs-11 p-2-4 flex-1" style="height: auto;" onchange="updatePuntosDiscountPreview()"></select>
                            <button type="button" id="btnCanjearPuntos" onclick="app.canjearPuntos()" class="btn-save fs-10 p-4-12 w-auto" style="background: var(--accent); white-space: nowrap;"><?php echo L('tpv_redeem_points'); ?></button>
                        </div>
                    </div>
                    <div id="puntosAplicadosResumen" class="d-none mt-4 p-8 br-6 fs-12 bg-accent text-white d-flex jc-space-between ai-center">
                        <div class="d-flex ai-center gap-6">
                            <i class="fa-solid fa-check"></i>
                            <span><?php echo L('tpv_points_discount'); ?>: <strong id="puntosDiscountVal">-0,00 €</strong> (<span id="puntosRedeemedVal">0</span> pts)</span>
                        </div>
                        <button onclick="app.quitarPuntosCanjeados()" class="btn-close-modal text-white" style="font-size:16px;" aria-label="Quitar puntos canjeados">×</button>
                    </div>
                </div>

                <!-- Panel NIF para factura: aparece si el cliente seleccionado no tiene NIF registrado -->
                <div id="panelNifFactura" class="d-none flex-column gap-8 p-12 br-8 border-2" style="border-color: var(--accent); background: var(--surface2);">
                    <div class="fs-12 font-bold text-accent d-flex ai-center gap-6">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <?php echo L('tpv_nif_required_for_invoice'); ?>
                    </div>
                    <div class="d-flex gap-4">
                        <select id="facturaClienteIdType" class="form-input fs-11 p-2-4" style="width: 100px;">
                            <option value="01">01 - NIF ES</option>
                            <option value="02">02 - NIF IVA</option>
                            <option value="03">03 - PASAP.</option>
                            <option value="04">04 - ID OFIC.</option>
                            <option value="05">05 - CERTIF.</option>
                            <option value="06">06 - OTRO</option>
                        </select>
                        <input id="facturaClienteNif" class="form-input fs-12 font-mono flex-1" placeholder="NIF / Documento" autocomplete="off" style="text-transform: uppercase;" />
                        <input id="facturaClientePais" class="form-input fs-12 font-mono" style="width: 45px;" placeholder="ES" maxlength="2" value="ES" />
                    </div>
                    <div class="fs-11 text-muted d-flex ai-center gap-6">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <?php echo L('tpv_nif_will_be_saved'); ?>
                    </div>
                </div>

            </div><!-- /left col -->

            <!-- ── RIGHT: Pago ── -->
            <div style="padding: 24px 28px; overflow-y: auto; display: flex; flex-direction: column; gap: 16px;">

                <!-- Total -->
                <div id="pagosMixResumen" class="flex-column gap-8 br-12 border-2" style="border-color: var(--accent); padding: 16px 20px;">
                    <div class="d-flex jc-space-between ai-center">
                        <span class="fs-13 font-bold"><?php echo L('tpv_total_sale'); ?></span>
                        <span id="mixTotalVenta" class="font-bold font-mono fs-20 text-accent">0,00 €</span>
                    </div>
                    <div id="tariffNotice" class="d-none mt-4 p-8 br-8 bg-accent-soft text-accent fs-11 fw-700 animate-fade-in">
                        <i class="fa-solid fa-tag mr-6 opacity-70"></i> <span id="tariffNoticeText">Tarifa aplicada</span>
                    </div>

                    <div id="promoNoticeSummary" class="d-none mt-4 p-8 br-8 bg-green-light text-green fs-11 fw-700 animate-fade-in">
                        <i class="fa-solid fa-percent mr-6 opacity-70"></i> <span id="promoNoticeText">Promo aplicada</span>
                    </div>
                    <div id="mixListaPagos" class="d-flex flex-column gap-4 mt-8 pb-8 border-bottom border-dashed">
                        <div class="text-center opacity-50 fs-11 italic"><?php echo L('tpv_no_payments'); ?></div>
                    </div>
                    <div class="d-flex jc-space-between ai-center pt-4">
                        <span class="fs-13 font-bold"><?php echo L('tpv_pending'); ?></span>
                        <span id="mixTotalPendiente" class="font-bold font-mono fs-18 text-red">0,00 €</span>
                    </div>
                    <div id="mixTotalPagadoRow" class="d-none jc-space-between ai-center fs-11 opacity-70">
                        <span><?php echo L('tpv_total_paid'); ?>:</span>
                        <span id="mixTotalPagado">0,00 €</span>
                    </div>
                </div>

                <!-- Gestión de pago -->
                <div id="cobroMixtoGestion" class="flex-column gap-8 br-8 border-2" style="padding: 16px 20px; background: var(--surface2);">
                    <div id="labelAddPago" class="form-label font-bold text-accent mb-4"><?php echo L('tpv_add_payment'); ?></div>

                    <div class="d-flex gap-8 fw-wrap mb-8" id="selectorMetodoPago">
                        <button id="btnEfectivo" onclick="app.selectModalPayment(this)" class="btn-tpv-method flex-1 py-10 px-8 br-8 border-2 transition d-flex flex-column ai-center gap-4 bg-surface" style="min-width:70px;">
                            <i class="fa-solid fa-money-bill-1 fs-16"></i>
                            <span class="fs-11 font-bold"><?php echo L('tpv_method_cash'); ?></span>
                        </button>
                        <button id="btnTarjeta" onclick="app.selectModalPayment(this)" class="btn-tpv-method flex-1 py-10 px-8 br-8 border-2 transition d-flex flex-column ai-center gap-4 bg-surface" style="min-width:70px;">
                            <i class="fa-solid fa-credit-card fs-16"></i>
                            <span class="fs-11 font-bold"><?php echo L('tpv_method_card'); ?></span>
                        </button>
                        <button id="btnBizum" onclick="app.selectModalPayment(this)" class="btn-tpv-method flex-1 py-10 px-8 br-8 border-2 transition d-flex flex-column ai-center gap-4 bg-surface" style="min-width:70px;">
                            <i class="fa-solid fa-mobile-screen fs-16"></i>
                            <span class="fs-11 font-bold"><?php echo L('tpv_method_bizum'); ?></span>
                        </button>
                    </div>

                    <div id="pagoMontoArea" class="d-none animate-fade-in">
                        <div class="form-group mb-8">
                            <label class="fs-13 font-bold mb-4 opacity-80" id="labelMontoPago"><?php echo L('tpv_amount_to_add'); ?> (€)</label>
                            <div class="d-flex gap-8 ai-center">
                                <input id="mixPagoMonto" type="number" step="0.01" class="form-input font-mono fs-20 text-right flex-1 bg-surface1 border-0 br-8" placeholder="0,00" oninput="app.calcularCambioMix()" />
                                <button id="mixBtnAddPago" onclick="app.addPagoMixto()" class="btn-save p-12-24 br-8 font-bold" style="height:unset; font-size:14px;"><?php echo L('tpv_add'); ?></button>
                            </div>
                            <div id="mixPagoStatusFeedback" class="mt-8 p-10 br-8 text-center font-bold fs-13 animate-fade-in" style="background: rgba(0,0,0,0.05);">
                                <!-- Dinámico vía JS -->
                            </div>
                        </div>
                        <div id="extraEfectivo" class="d-none mt-12 p-12 br-8 bg-surface1 border-1 d-flex flex-column gap-8">
                            <div class="d-flex jc-space-between ai-center">
                                <span class="fs-13 font-bold opacity-70"><?php echo L('tpv_change_to_return'); ?>:</span>
                                <span id="efectivoCambio" class="fs-22 font-mono font-bold text-green">0,00 €</span>
                            </div>
                            <div class="d-flex jc-space-between ai-center opacity-60 fs-11 border-top pt-6">
                                <span><i class="fa-solid fa-vault"></i> Efectivo en caja (según sistema):</span>
                                <span id="cajaEfectivoLabel" class="font-mono font-bold">—</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Comentarios -->
                <div class="form-group br-8 border-2" style="padding: 14px 18px; background: var(--surface2);">
                    <label class="form-label fs-13 font-bold d-flex ai-center gap-8">
                        <i class="fa-solid fa-comment-dots text-accent"></i> <?php echo L('client_comments_label'); ?>
                    </label>
                    <textarea id="ticketComentarios" class="form-input fs-12" placeholder="<?php echo L('client_comments_placeholder'); ?>" rows="2" style="resize:none;"></textarea>
                </div>

                <!-- Spacer push footer to bottom -->
                <div style="flex:1;"></div>

            </div><!-- /right col -->

        </div><!-- /grid -->

        <!-- Footer -->
        <div class="modal-footer" style="border-top: 1px solid var(--border); padding: 16px 28px;">
            <button onclick="cerrarModalCliente()" class="btn-cancel"><?php echo L('modal_cancel'); ?></button>
            <button id="confirmarClienteBtn" onclick="app.confirmarCliente()" class="btn-save"><?php echo L('tpv_charge'); ?></button>
        </div>
    </div>
</div>
</div>


<!-- MODAL TELÉFONO BIZUM -->
<div id="bizumPhoneModal" class="modal-overlay" style="z-index: 20000 !important;" role="dialog" aria-modal="true" aria-labelledby="bizumPhoneModalTitle">
    <div class="modal modal-content glass-effect ai-stretch w-420 p-24">
        <div class="d-flex ai-center gap-16 pb-16 border-bottom">
            <div class="modal-icon text-accent bg-accent-soft m-0">
                <i class="fa-solid fa-mobile-screen"></i>
            </div>
            <div>
                <div class="fs-18 fw-700" id="bizumPhoneModalTitle"><?php echo L('bizum_phone_required_title') ?></div>
                <div class="fs-12 text-muted" id="bizumPhoneClientLabel"></div>
            </div>
            <button onclick="app.cancelarTelefonoBizum()" class="btn-close-modal" style="margin-left: auto;" aria-label="<?php echo L('modal_cancel') ?>">&times;</button>
        </div>

        <div class="d-flex flex-column gap-16 mt-20">
            <div class="d-flex ai-start gap-10 p-12 br-8 fs-13" style="background: var(--orange-light); border: 1px solid var(--orange);">
                <i class="fa-solid fa-circle-info mt-2" style="color: var(--orange); flex-shrink: 0;"></i>
                <span><?php echo L('bizum_phone_required_warning') ?></span>
            </div>
            <div class="form-group mb-0">
                <label class="form-label fw-600 mb-4 fs-13 text-muted tt-uppercase ls-1"><?php echo L('bizum_phone_label') ?></label>
                <input type="tel" id="bizumPhoneInput"
                       class="form-input font-mono fs-22 text-center"
                       placeholder="600 000 000"
                       maxlength="20"
                       onkeydown="if(event.key==='Enter') app.confirmarTelefonoBizum()" />
                <div id="bizumPhoneError" class="d-none text-red fs-12 font-bold mt-6"></div>
            </div>
        </div>

        <div class="modal-footer full-width jc-end mt-20 pt-16 border-top">
            <button onclick="app.cancelarTelefonoBizum()" class="btn-cancel"><?php echo L('modal_cancel') ?></button>
            <button onclick="app.confirmarTelefonoBizum()" class="btn-save d-flex ai-center gap-8">
                <i class="fa-solid fa-mobile-screen"></i> <?php echo L('bizum_phone_confirm') ?>
            </button>
        </div>
    </div>
</div>

<!-- MODAL PRODUCTO COMODÍN -->
<?php
require_once __DIR__ . '/../model/TipoIVAPDO.php';
$ivasVigentes = TipoIVAPDO::listarVigentesActuales();
?>
<div id="modalComodin" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalComodinTitle">
    <div class="modal modal-content glass-effect ai-stretch w-380 p-24">
        <div class="d-flex ai-center gap-16 pb-16 border-bottom">
            <div class="modal-icon text-accent bg-accent-light m-0">
                <i class="fa-solid fa-box-open"></i>
            </div>
            <div>
                <div class="fs-18 fw-700" id="modalComodinTitle"><?php echo L('tpv_custom_product_title'); ?></div>
                <div class="fs-12 text-muted"><?php echo L('tpv_add_custom_product'); ?></div>
            </div>
            <button onclick="app.cerrarModalComodin()" class="btn-close-modal" style="margin-left: auto;" aria-label="<?php echo L('modal_cancel'); ?>">&times;</button>
        </div>

        <div class="modal-body p-0 mt-24 grid gap-20">
            <div class="form-group mb-0">
                <label class="form-label fw-600 mb-4 fs-13 text-muted tt-uppercase ls-1"><?php echo L('tpv_custom_desc'); ?></label>
                <input id="comodinDesc" type="text" class="form-input" placeholder="<?php echo L('tpv_custom_desc_placeholder'); ?>" autocomplete="off" />
            </div>
            
            <div class="grid-2 gap-24">
                <div class="form-group mb-0">
                    <label class="form-label fw-600 mb-4 fs-13 text-muted tt-uppercase ls-1"><?php echo L('tpv_custom_price'); ?></label>
                    <input id="comodinPrice" type="number" step="0.01" min="0" class="form-input font-mono text-right" placeholder="0.00" />
                </div>
                <div class="form-group mb-0">
                    <label class="form-label fw-600 mb-4 fs-13 text-muted tt-uppercase ls-1"><?php echo L('modal_label_iva'); ?></label>
                    <select id="comodinIva" class="form-input font-mono text-right">
                        <?php foreach ($ivasVigentes as $iv): ?>
                            <option value="<?php echo $iv['porcentaje']; ?>"><?php echo $iv['nombre']; ?> (<?php echo (float)$iv['porcentaje']; ?>%)</option>
                        <?php endforeach; ?>
                        <?php if (empty($ivasVigentes)): ?>
                            <option value="21">21%</option>
                            <option value="10">10%</option>
                            <option value="4">4%</option>
                            <option value="0">0%</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
            
            <div id="comodinError" class="d-none text-red fs-12 font-bold text-center"></div>
        </div>

        <div class="modal-footer full-width jc-end mt-24 pt-16 border-top">
            <button onclick="app.cerrarModalComodin()" class="btn-cancel"><?php echo L('modal_cancel'); ?></button>
            <button onclick="app.agregarComodin()" class="btn-save d-flex ai-center gap-8">
                <i class="fa-solid fa-plus"></i> <?php echo L('modal_add_item'); ?>
            </button>
        </div>
    </div>
</div>


<!-- MODAL DNI RÁPIDO: aparece al seleccionar cliente sin DNI -->
<div id="modalDniRapido" class="modal-overlay" role="dialog" aria-modal="true">
    <div class="modal modal-content ai-stretch w-380 p-0" style="border-radius: 16px; overflow: hidden;">
        <div class="d-flex ai-center gap-16 p-20 border-bottom">
            <div class="modal-icon text-accent bg-accent-light m-0">
                <i class="fa-solid fa-id-card"></i>
            </div>
            <div>
                <div class="fs-16 fw-700">DNI / NIF del cliente</div>
                <div class="fs-12 text-muted" id="dniRapidoNombreCliente"></div>
            </div>
            <button onclick="cerrarModalDniRapido()" class="btn-close-modal" style="margin-left: auto;">&times;</button>
        </div>
        <div class="p-20 grid gap-16">
            <p class="fs-13 text-muted m-0">Este cliente no tiene DNI/NIF registrado. Puedes añadirlo ahora y quedará guardado en su ficha.</p>
            <div class="d-flex gap-8">
                <select id="dniRapidoTipo" class="form-input fs-12" style="width: 110px;">
                    <option value="01">NIF (ES)</option>
                    <option value="02">NIE</option>
                    <option value="03">Pasaporte</option>
                    <option value="05">ID UE</option>
                    <option value="06">Otro</option>
                </select>
                <input id="dniRapidoValor" type="text" class="form-input font-mono fw-700 flex-1"
                    placeholder="NIF / Documento" autocomplete="off" style="text-transform: uppercase;"
                    onkeydown="if(event.key==='Enter') guardarDniRapido()">
                <input id="dniRapidoPais" type="text" class="form-input font-mono" style="width: 48px;"
                    placeholder="ES" maxlength="2" value="ES">
            </div>
            <div id="dniRapidoError" class="d-none text-red fs-12 font-bold"></div>
        </div>
        <div class="modal-footer full-width jc-end p-16 border-top">
            <button onclick="cerrarModalDniRapido()" class="btn-cancel">Ahora no</button>
            <button onclick="guardarDniRapido()" class="btn-save d-flex ai-center gap-8">
                <i class="fa-solid fa-floppy-disk"></i> Guardar DNI
            </button>
        </div>
    </div>
</div>

<style>
    .qty-input {
        width: 40px;
        background: none;
        border: none;
        text-align: center;
        font-weight: 700;
        font-family: inherit;
        font-size: 14px;
        color: var(--text);
        padding: 0;
        margin: 0;
    }
    /* Quitar flechas en Chrome/Safari/Edge */
    .qty-input::-webkit-outer-spin-button,
    .qty-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    /* Quitar flechas en Firefox */
    .qty-input[type=number] {
        -moz-appearance: textfield;
        appearance: textfield;
    }
    .qty-input:focus {
        outline: none;
        background: var(--surface2);
        border-radius: 4px;
    }
</style>