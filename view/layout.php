<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>" />
    <title>TPV · ElectroBazar</title>
    <link rel="icon" type="image/x-icon" href="./favicon.ico">
    <link rel="stylesheet" href="./webroot/css/estilos.css?v=1" />
    <link rel="stylesheet" href="./webroot/css/components.css?v=1" />
    <link rel="stylesheet" href="./webroot/css/app.css?v=1" />
    <!-- Generación de PDF en cliente -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link rel="stylesheet" href="./webroot/css/fonts.css" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?php
    // require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/ConfiguracionPDO.php';
    require_once __DIR__ . '/../model/CajaTurnoPDO.php';

    // Cargar configuración de empresa
    $appConfig = ConfiguracionPDO::obtenerConfiguracion();
    $empresaNombre = $appConfig['empresa_nombre'] ?? 'ElectroBazar';
    $empresaDireccion = $appConfig['empresa_direccion'] ?? '';
    $empresaNif = $appConfig['empresa_nif'] ?? '';

    // Cargar tema del usuario actual (si existe)
    $themeMode = 'light';
    $themeAccent = 'blue';
    $themeFont = 'dm-mono';
    if (isset($_SESSION['usuarioActualTPV'])) {
        try {
            $qTheme = DBPDO::ejecutarConsulta(
                "SELECT theme_mode, theme_accent, theme_font FROM usuarios WHERE id = :id",
                [':id' => $_SESSION['usuarioActualTPV']->getId()]
            );
            $rowTheme = $qTheme->fetch(PDO::FETCH_ASSOC);
            if ($rowTheme) {
                if (!empty($rowTheme['theme_mode'])) {
                    $themeMode = $rowTheme['theme_mode'];
                }
                if (!empty($rowTheme['theme_accent'])) {
                    $themeAccent = $rowTheme['theme_accent'];
                }
                if (!empty($rowTheme['theme_font'])) {
                    $themeFont = $rowTheme['theme_font'];
                }
            }
        } catch (\Throwable $e) {
            // Ignorar errores de tema y usar valores por defecto
        }
    }
    ?>
    <script>
        const IS_ADMIN_BACKEND = <?php echo json_encode(isset($avInicioPrivado['esAdmin']) && $avInicioPrivado['esAdmin']); ?>;
        const DB_PRODUCTS = <?php echo json_encode($avInicioPrivado['productos'] ?? []); ?>;
        const DB_PROMOS = <?php echo json_encode($avInicioPrivado['promos'] ?? []); ?>;
        const CAJERO_NOMBRE = <?php echo json_encode($avInicioPrivado['nombre_completo'] ?? (isset($_SESSION['usuarioActualTPV']) ? $_SESSION['usuarioActualTPV']->getNombreCompleto() : '')); ?>;
        const IS_TPV = <?php echo json_encode(isset($_SESSION['paginaEnCurso']) && $_SESSION['paginaEnCurso'] === 'inicioPrivado'); ?>;
        const CAJA_ABIERTA = <?php echo json_encode($_SESSION['cajaAbierta'] ?? false); ?>;
        const ESC_POS_ENABLED = true;
        const USER_THEME_MODE = <?php echo json_encode($themeMode); ?>;
        const USER_THEME_ACCENT = <?php echo json_encode($themeAccent); ?>;
        const USER_THEME_FONT = <?php echo json_encode($themeFont); ?>;
        const I18N = {
            outOfStock: "<?php echo L('tpv_js_out_of_stock', true); ?>",
            lowStockLimit: "<?php echo L('tpv_js_low_stock_limit', true); ?>",
            limitReached: "<?php echo L('tpv_js_limit_reached', true); ?>",
            cartEmpty: "<?php echo L('tpv_js_cart_empty', true); ?>",
            salePostponed: "<?php echo L('tpv_js_sale_postponed', true); ?>",
            saleResumed: "<?php echo L('tpv_js_sale_resumed', true); ?>",
            confirmResume: "<?php echo L('tpv_js_confirm_resume', true); ?>",
            errorSave: "<?php echo L('tpv_js_error_save', true); ?>",
            successSave: "<?php echo L('tpv_js_success_save', true); ?>",
            clientNotFound: "<?php echo L('tpv_js_client_not_found', true); ?>",
            enterAmount: "<?php echo L('tpv_js_enter_amount', true); ?>",
            connError: "<?php echo L('tpv_js_conn_error', true); ?>",
            loading: "<?php echo L('loading', true); ?>",
            saving: "<?php echo L('prod_js_saving', true); ?>",
            confirmDelete: "<?php echo L('prod_js_delete_confirm_title', true); ?>",
            confirmBaja: "<?php echo L('prod_modal_confirm_exclude', true); ?>",
            edit: "<?php echo L('user_tip_edit', true); ?>",
            delete: "<?php echo L('modal_delete_btn', true); ?>",
            active: "<?php echo L('prod_pill_active', true); ?>",
            inactive: "<?php echo L('prod_pill_inactive', true); ?>",
            promoApplied: "<?php echo L('tpv_js_promo_applied', true); ?>",
            promoInvalid: "<?php echo L('tpv_js_promo_invalid', true); ?>",
            promoMinAmount: "<?php echo L('tpv_js_promo_min_amount', true); ?>",
            searching: "<?php echo L('tpv_js_searching', true); ?>",
            socioFound: "<?php echo L('tpv_js_socio_found', true); ?>",
            socioNotFound: "<?php echo L('tpv_js_socio_not_found', true); ?>",
            vouchersSearching: "<?php echo L('tpv_js_vouchers_searching', true); ?>",
            vouchersNone: "<?php echo L('tpv_js_vouchers_none', true); ?>",
            voucherApplied: "<?php echo L('tpv_js_voucher_applied', true); ?>",
            fieldRequired: "<?php echo L('tpv_js_field_required', true); ?>",
            nameNifRequired: "<?php echo L('tpv_js_name_nif_required', true); ?>",
            clientRegistered: "<?php echo L('tpv_js_client_registered', true); ?>",
            newLabel: "<?php echo L('tpv_js_new_label', true); ?>",
            selectionRequired: "<?php echo L('tpv_js_selection_required', true); ?>",
            noResults: "<?php echo L('tpv_js_no_results', true); ?>",
            amountToAdd: "<?php echo L('tpv_js_amount_to_add', true); ?>",
            cashReceived: "<?php echo L('tpv_js_cash_received', true); ?>",
            amountOwed: "<?php echo L('tpv_js_amount_owed', true); ?>",
            paymentIdentified: "<?php echo L('tpv_js_payment_identified', true); ?>",
            clientIdentified: "<?php echo L('tpv_js_client_identified', true); ?>",
            apply: "<?php echo L('tpv_apply', true); ?>",
            particular: "<?php echo L('client_particular', true); ?>",
            empresa: "<?php echo L('client_empresa', true); ?>",
            socio: "<?php echo L('client_socio', true); ?>",
            cash: "<?php echo L('tpv_method_cash', true); ?>",
            card: "<?php echo L('tpv_method_card', true); ?>",
            bizum: "<?php echo L('tpv_method_bizum', true); ?>",
            account: "<?php echo L('tpv_method_account', true); ?>",
            mixed: "<?php echo L('tpv_method_mixed', true); ?>",
            enterDate: "<?php echo L('tpv_js_enter_date', true); ?>",
            cashTotalRequired: "<?php echo L('tpv_js_cash_total_required', true); ?>",
            dateRequired: "<?php echo L('tpv_js_date_required', true); ?>",
            facturaRequired: "<?php echo L('tpv_js_factura_required', true); ?>",
            noPayments: "<?php echo L('tpv_js_no_payments', true); ?>",
            invoice: "<?php echo L('ticket_type_invoice', true); ?>",
            ticket: "<?php echo L('ticket_type_sale', true); ?>",
            ticket_type_abono: "<?php echo L('ticket_type_abono', true); ?>",
            charge: "<?php echo L('tpv_charge', true); ?>",
            pointsApplied: "<?php echo L('points_applied', true); ?>",
            pointsInvalidAmount: "<?php echo L('points_invalid_amount', true); ?>",
            pointsRedeemedLabel: "<?php echo L('points_redeemed_label', true); ?>",
            customDescError: "<?php echo L('tpv_custom_desc_error', true); ?>",
            customPriceError: "<?php echo L('tpv_custom_price_error', true); ?>"
        };

        // Global Fetch Wrapper for CSRF Protection
        (function() {
            const originalFetch = window.fetch;
            window.fetch = function(url, options = {}) {
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : null;
                if (csrfToken && options.method && !['GET', 'HEAD', 'OPTIONS'].includes(options.method.toUpperCase())) {
                    options.headers = options.headers || {};
                    if (options.headers instanceof Headers) {
                        if (!options.headers.has('x-csrf-token')) options.headers.set('x-csrf-token', csrfToken);
                    } else {
                        const hasCsrf = Object.keys(options.headers).some(k => k.toLowerCase() === 'x-csrf-token');
                        if (!hasCsrf) options.headers['x-csrf-token'] = csrfToken;
                    }
                }
                return originalFetch(url, options);
            };
        })();
    </script>
    <style>
        /* Language Dropdown Styles */
        .lang-dropdown {
            position: relative;
            display: inline-block;
        }

        .lang-trigger {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 16px;
        }

        .lang-trigger:hover {
            background: var(--accent);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(var(--accent-rgb), 0.3);
        }

        .lang-menu {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            min-width: 140px;
            padding: 6px;
            display: flex;
            flex-direction: column;
            gap: 2px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
        }

        .lang-dropdown:hover .lang-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .lang-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            text-decoration: none;
            color: #1a1a1a;
            font-size: 13px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.15s ease;
        }

        .lang-link:hover {
            background: rgba(var(--accent-rgb), 0.08);
            color: var(--accent);
        }

        .lang-link.active {
            background: rgba(var(--accent-rgb), 0.1);
            color: var(--accent);
            font-weight: 600;
        }

        .lang-link img {
            width: 18px;
            border-radius: 2px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }

        /* Dark mode adjustments */
        [data-theme-mode="dark"] .lang-menu {
            background: rgba(30, 30, 35, 0.98);
            border-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.6);
        }
        [data-theme-mode="dark"] .lang-link {
            color: #eeeeee;
        }
        [data-theme-mode="dark"] .lang-link.active {
            color: var(--accent);
        }
    </style>
</head>

<body data-page="<?php echo $_SESSION['paginaEnCurso'] ?? ''; ?>"
    data-theme-mode="<?php echo htmlspecialchars($themeMode, ENT_QUOTES, 'UTF-8'); ?>"
    data-theme-accent="<?php echo htmlspecialchars($themeAccent, ENT_QUOTES, 'UTF-8'); ?>"
    data-theme-font="<?php echo htmlspecialchars($themeFont, ENT_QUOTES, 'UTF-8'); ?>">
    <!-- TOPBAR -->
    <header class="topbar">
        <div class="topbar-brand">
            <div class="topbar-brand-dot"></div>
            TPV · ElectroBazar
        </div>
        <div class="topbar-info">
            <?php
            $pendientesArqueo = CajaTurnoPDO::obtenerTurnosPendientesArqueo();
            if (!empty($pendientesArqueo)): ?>
                <a href="index.php?irCierreCaja" class="status-indicator" style="background: var(--orange); color: white; padding: 2px 10px; border-radius: 20px; font-size: 10px; font-weight: bold; text-transform: uppercase; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;" title="Hay cierres de días anteriores sin contar">
                    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo L('label_arqueo_pending'); ?>
                </a>
            <?php endif; ?>

            <?php if (isset($_SESSION['cajaAbierta']) && $_SESSION['cajaAbierta']): ?>
                <span class="status-indicator" style="background: var(--green); color: white; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: bold; text-transform: uppercase;">
                    <i class="fa-solid fa-circle-check"></i> <?php echo L('label_abierta'); ?>
                </span>
            <?php else: ?>
                <span class="status-indicator" style="background: var(--red); color: white; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: bold; text-transform: uppercase;">
                    <i class="fa-solid fa-circle-xmark"></i> <?php echo L('label_cerrada'); ?>
                </span>
            <?php endif; ?>
            &nbsp;·&nbsp; <?php echo L('label_register'); ?> #1 &nbsp;·&nbsp; <span id="clock">--:--</span> &nbsp;·&nbsp;
            <span id="datestr">--</span>
        </div>

        <?php if (isset($_SESSION['usuarioActualTPV']) && ($_SESSION['paginaEnCurso'] ?? '') !== 'login'): ?>
            <div class="topbar-actions">
                <div class="topbar-user-mini">
                    <div class="avatar-mini">
                        <?php
                        $nombreComp = $_SESSION['usuarioActualTPV']->getNombreCompleto() ?? '';
                        $nombres = explode(" ", $nombreComp);
                        $inicial1 = substr($nombres[0] ?? '', 0, 1);
                        $inicial2 = isset($nombres[1]) ? substr($nombres[1], 0, 1) : "";
                        echo strtoupper($inicial1 . $inicial2);
                        ?>
                    </div>
                    <div class="user-details">
                        <span class="u-name"><?php echo htmlspecialchars($nombreComp); ?></span>
                        <span class="u-rol"><?php echo strtoupper($_SESSION['usuarioActualTPV']->getRol() ?? ''); ?></span>
                    </div>
                </div>

                <div class="v-divider"></div>

                <nav class="topbar-nav">
                    <!-- Selector de Idioma (Dropdown) -->
                    <div class="lang-dropdown mr-12">
                        <div class="lang-trigger" title="<?php echo L('menu_idioma') ?? 'Idioma'; ?>">
                            <i class="fa-solid fa-globe"></i>
                        </div>
                        <div class="lang-menu">
                            <a href="index.php?lang=es" class="lang-link <?php echo $lang === 'es' ? 'active' : ''; ?>">
                                <img src="https://flagcdn.com/16x12/es.png" alt="ES">
                                <span>Español</span>
                            </a>
                            <a href="index.php?lang=en" class="lang-link <?php echo $lang === 'en' ? 'active' : ''; ?>">
                                <img src="https://flagcdn.com/16x12/us.png" alt="EN">
                                <span>English</span>
                            </a>
                            <a href="index.php?lang=fr" class="lang-link <?php echo $lang === 'fr' ? 'active' : ''; ?>">
                                <img src="https://flagcdn.com/16x12/fr.png" alt="FR">
                                <span>Français</span>
                            </a>
                            <a href="index.php?lang=it" class="lang-link <?php echo $lang === 'it' ? 'active' : ''; ?>">
                                <img src="https://flagcdn.com/16x12/it.png" alt="IT">
                                <span>Italiano</span>
                            </a>
                            <a href="index.php?lang=de" class="lang-link <?php echo $lang === 'de' ? 'active' : ''; ?>">
                                <img src="https://flagcdn.com/16x12/de.png" alt="DE">
                                <span>Deutsch</span>
                            </a>
                        </div>
                    </div>

                    <form method="post" action="index.php">
                        <button type="submit" name="irDashboard" class="topbar-btn" title="Panel de Control">
                            <i class="fa-solid fa-gauge-high"></i>
                            <span><?php echo L('menu_inicio'); ?></span>
                        </button>
                    </form>

                    <form method="post" action="index.php">
                        <button type="submit" name="irTPV" class="topbar-btn" title="Terminal Punto de Venta">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <span><?php echo L('menu_tpv'); ?></span>
                        </button>
                    </form>

                    <form method="post" action="index.php">
                        <button type="submit" name="irMiPerfil" class="topbar-btn" title="Mi Perfil">
                            <i class="fa-solid fa-user-gear"></i>
                            <span><?php echo L('menu_perfil'); ?></span>
                        </button>
                    </form>

                    <?php if ($_SESSION['usuarioActualTPV']->tienePermiso('cerrar_caja') || $_SESSION['usuarioActualTPV']->tienePermiso('cerrar_turno')): ?>
                        <form method="post" action="index.php">
                            <button type="submit" name="irCierreCaja" class="topbar-btn" title="Cierre de caja">
                                <i class="fa-solid fa-vault"></i>
                                <span><?php echo L('menu_caja'); ?></span>
                            </button>
                        </form>
                    <?php endif; ?>

                    <form method="post" action="index.php">
                        <button type="submit" name="salir" class="topbar-btn btn-exit" title="Cerrar sesión">
                            <i class="fa-solid fa-power-off"></i>
                            <span><?php echo L('menu_salir'); ?></span>
                        </button>
                    </form>
                </nav>
            </div>
        <?php endif; ?>
    </header>
    <?php
    require_once $view[$_SESSION['paginaEnCurso']];
    ?>

    <div class="modal-overlay" id="ticketModal">
        <div class="modal ticket-wrapper" id="ticketContenido">
            <div class="ticket-brand-header">
                <div class="title"><i class="fa-solid fa-bolt-lightning"></i> <?= $empresaNombre ?></div>
                <div class="info"><?= $empresaDireccion ?> · NIF: <?= $empresaNif ?></div>
                <button onclick="nuevaVenta()" class="btn-close-modal" title="Cerrar">×</button>
            </div>

            <!-- TABS POR PUNTOS -->
            <div id="tkTabsContainer" class="ticket-tabs border-bottom">
                <button class="ticket-tab active flex-1 py-12 fw-700 fs-12 tt-uppercase ls-1 cursor-pointer transition-all" onclick="switchTicketTab('summary')">
                    <i class="fa-solid fa-receipt mr-6 opacity-70"></i> <?php echo L('ticket_tab_summary'); ?>
                </button>
                <button id="tkTabPoints" class="ticket-tab flex-1 py-12 fw-700 fs-12 tt-uppercase ls-1 cursor-pointer transition-all" onclick="switchTicketTab('points')">
                    <i class="fa-solid fa-star mr-6 opacity-70"></i> <?php echo L('ticket_tab_points'); ?>
                </button>
            </div>

            <div id="tkSummaryTab" class="ticket-tab-content active p-20">

            <!-- SECCIÓN CLIENTE (Removida por integración) -->

            <div class="ticket-meta">
                <span id="tkTipoDoc" class="doc-type"><?php echo L('ticket_type_sale'); ?></span>
                
                <span class="label"><?php echo L('ticket_label_number'); ?></span>
                <div class="d-flex ai-center">
                    <span id="tkNumero" class="value">—</span>
                    <span id="tkBadgeAbono" style="display:none;background:var(--red);color:#fff;font-size:9px;font-weight:800;padding:2px 7px;border-radius:4px;letter-spacing:.5px;margin-left:8px;vertical-align:middle;">ABONO</span>
                </div>

                <span id="tkLabelNumOrig" class="label d-none"><?php echo L('ticket_label_origin'); ?></span>
                <span id="tkNumOrig" class="value d-none">—</span>

                <span class="label"><?php echo L('ticket_label_date'); ?></span> <span id="tkFecha">—</span>
                <span id="tkLabelClienteMeta" class="label d-none"><?php echo L('client_label_client'); ?></span>
                <span id="tkClienteMeta" class="value d-none">—</span>
                <span id="tkLabelNifMeta" class="label d-none"><?php echo L('client_label_cif'); ?></span>
                <span id="tkNifMeta" class="value d-none" style="font-family: inherit;">—</span>
                <span class="label"><?php echo L('ticket_label_cashier'); ?></span> <span id="tkCajero">—</span>
                <span class="label"><?php echo L('tpv_payment_method'); ?></span> <span id="tkMetodo">—</span>
            </div>

            <div id="tkLineas" class="ticket-items mt-16 mb-16"></div>


            <div class="ticket-totals pt-16 border-top">
                <div class="ticket-total-row label text-muted" id="tkSubtotalRow">
                    <span><?php echo L('tpv_subtotal'); ?></span><span id="tkSubtotal">—</span>
                </div>
                <div id="tkDescRow" class="ticket-total-row d-none text-green">
                    <span id="tkDescLabel"><?php echo L('tpv_discount'); ?></span><span id="tkDescAmt">—</span>
                </div>
                <!-- Desglose IVA dinámico por tipo -->
                <div id="tkIvaDesglose">
                    <!-- Se rellena dinámicamente por JS: una fila por tipo de IVA -->
                </div>
                <div id="tkValesRow" class="ticket-total-row d-none text-accent">
                    <span><?php echo L('ticket_label_voucher_paid'); ?></span><span id="tkValesAmt">—</span>
                </div>
                <div class="ticket-total-row ticket-total-main">
                    <span><?php echo L('tk_label_total'); ?></span><span id="tkTotal" class="font-mono">—</span>
                </div>
                <!-- Efectivo -->
                <div id="tkEfectivoRow" class="d-none flex-column gap-4 mt-8 pt-8 border-top text-muted fs-12">
                    <div class="ticket-total-row"><span><?php echo L('ticket_label_received'); ?></span><span id="tkEntregado">—</span></div>
                    <div class="ticket-total-row"><span><?php echo L('ticket_label_change'); ?></span><span id="tkCambio">—</span></div>
                </div>
            </div>

            <!-- SECCIÓN PAGOS PARCIALES -->
            <div id="tkPagosSection" class="d-none" style="margin-top: 16px; padding: 12px; border: 1px solid var(--surface2); border-radius: 8px; background: rgba(0,0,0,0.02);">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px;"><?php echo L('ticket_label_payment_breakdown'); ?></div>
                <div id="tkPagosLista" style="display: flex; flex-direction: column; gap: 4px;"></div>

                <div id="tkAbonarParteContainer" class="d-none" style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--surface2);">
                    <button id="btnAbonarParte" onclick="abrirModalAbonoParcial()" class="btn-cancel w-100" style="background: var(--blue-light); color: var(--blue); border-color: var(--blue);">
                        <i class="fa-solid fa-hand-holding-dollar"></i> <?php echo L('ticket_btn_register_abono'); ?>
                    </button>
                </div>
            </div>

            <!-- SECCIÓN COMENTARIOS -->
            <div id="tkCommentsSection" class="d-none mt-16 p-12 br-8 border-2 bg-surface2 italic fs-12 text-muted" style="border-style: dashed;">
                <i class="fa-solid fa-quote-left mr-4 opacity-50"></i>
                <span id="tkCommentsText"></span>
            </div> <!-- End tkCommentsSection -->

            <!-- SECCIÓN ABONOS / DEVOLUCIONES (se rellena desde JS) -->
            <div id="tkAbonosSection" class="d-none"></div>

            </div> <!-- End tkSummaryTab -->

            <!-- SECCIÓN PUNTOS FIDELIDAD -->
            <div id="tkPointsTab" class="ticket-tab-content d-none p-24">
                <div class="points-hero d-flex flex-column ai-center jc-center py-32 bg-accent-light br-20 border-2 mb-24" style="border-color: rgba(var(--accent-rgb), 0.1);">
                    <div class="points-icon-wrap mb-16 shadow-lg">
                        <i class="fa-solid fa-star text-accent fs-32"></i>
                    </div>
                    <div class="fs-14 fw-700 text-accent tt-uppercase ls-1 mb-4"><?php echo L('ticket_tab_points'); ?></div>
                    <div class="fs-48 font-mono fw-900 text-accent mb-4" id="tkPointsEarnedTotal">0</div>
                    <div class="fs-12 text-muted fw-600"><?php echo L('tpv_points_earned'); ?></div>
                </div>

                <div class="grid-2 gap-16">
                    <div class="points-card p-16 br-16 border-2 bg-surface shadow-sm">
                        <div class="fs-11 text-muted tt-uppercase fw-700 mb-8 opacity-70"><?php echo L('tpv_points_redeemed'); ?></div>
                        <div class="fs-20 font-mono fw-800 text-red" id="tkPointsRedeemed">0</div>
                    </div>
                    <div class="points-card p-16 br-16 border-2 bg-surface shadow-sm highlighted" style="border-color: var(--accent);">
                        <div class="fs-11 text-muted tt-uppercase fw-700 mb-8 opacity-70"><?php echo L('tpv_points_total'); ?></div>
                        <div class="fs-20 font-mono fw-800 text-accent" id="tkPointsTotalBalance">0</div>
                    </div>
                </div>

                <div class="mt-24 p-16 br-16 bg-blue-light border-2 d-flex ai-center gap-12" style="border-color: rgba(var(--accent-rgb), 0.1);">
                    <i class="fa-solid fa-circle-info text-accent fs-20"></i>
                    <p class="fs-12 text-muted m-0 line-height-md">
                        <strong>Recuerda:</strong> 100 puntos equivalen a 5€ de descuento. ¡Sigue acumulando para ahorrar en tus próximas compras!
                    </p>
                </div>
            </div>

            <div class="ticket-email-section" id="ticketEmailSection">
                <label class="form-label fs-11"><?php echo L('ticket_label_send_email'); ?></label>
                <div class="d-flex gap-8">
                    <input type="text" id="tkEmailInput" placeholder="cliente@ejemplo.com" class="form-input font-mono fs-13">
                    <button onclick="enviarTicketEmail()" id="btnSendEmail" class="btn-filter h-40 p-0-20 bg-blue">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </div>
                <span id="err-email" class="form-error"></span>
            </div>

            <div class="modal-footer p-24 bg-surface2 d-flex ai-center gap-12">
                <button onclick="imprimirTicket()" class="btn-secondary flex-1 ai-center jc-center gap-8 px-16">
                    <i class="fa-solid fa-print"></i> <?php echo L('ticket_btn_print'); ?>
                </button>
                <button onclick="descargarPDFTicket()" class="btn-secondary flex-1 ai-center jc-center gap-8 px-16" style="background: var(--blue-light); color: var(--blue); border-color: var(--blue);">
                    <i class="fa-solid fa-file-pdf"></i> <?php echo L('tk_btn_pdf'); ?>
                </button>
                <button id="btnAnularTicket" class="btn-secondary flex-1 ai-center jc-center gap-8 px-16" style="background: var(--red-light); color: var(--red); border-color: var(--red); display: none;">
                    <i class="fa-solid fa-ban"></i> <?php echo L('ticket_btn_void'); ?>
                </button>
                
                <button id="btnNuevaVenta" onclick="nuevaVenta()" class="btn-save flex-1 ai-center jc-center px-16"><?php echo L('ticket_btn_new_sale'); ?></button>
            </div>
        </div>
    </div>

    <!-- MODAL: REGISTRAR ABONO PARCIAL -->
    <div class="modal-overlay" id="abonoParcialModal">
        <div class="modal modal-content gap-16 ai-stretch w-400">
            <div class="modal-header mb-0">
                <h2 class="m-0 fs-18"><?php echo L('abono_modal_title'); ?></h2>
                <button onclick="document.getElementById('abonoParcialModal').classList.remove('visible')" class="btn-close-modal">&times;</button>
            </div>
            <div class="modal-body p-20">
                <div class="form-group mb-0">
                    <label class="form-label"><?php echo L('tpv_amount_to_add'); ?> (€)</label>
                    <input type="number" id="abonoImporte" class="form-input font-mono fs-16" step="0.01" min="0.01" placeholder="0.00">
                </div>
                <div class="form-group mb-0 mt-16">
                    <label class="form-label"><?php echo L('tpv_payment_method'); ?></label>
                    <select id="abonoMetodo" class="form-input">
                        <option value="efectivo"><?php echo L('tpv_method_cash'); ?></option>
                        <option value="tarjeta"><?php echo L('tpv_method_card'); ?></option>
                        <option value="bizum"><?php echo L('tpv_method_bizum'); ?></option>
                        <option value="transferencia"><?php echo L('abono_method_transfer'); ?></option>
                        <option value="otro"><?php echo L('abono_method_other'); ?></option>
                    </select>
                </div>
                <!-- Para evitar que el formulario se envíe con ENTER lo dejamos sin form -->
            </div>
            <div class="modal-footer full-width jc-end">
                <button onclick="document.getElementById('abonoParcialModal').classList.remove('visible')" class="btn-cancel"><?php echo L('modal_cancel'); ?></button>
                <button onclick="procesarAbonoParcial()" class="btn-save bg-blue"><?php echo L('abono_btn_register'); ?></button>
            </div>
        </div>
    </div>

    <!-- MODAL: GESTIÓN DE DEVOLUCIONES Y GARANTÍAS (MEJORADO) -->
    <div class="modal-overlay" id="returnModal">
        <div class="modal modal-content gap-24 ai-stretch w-450 p-32">
            <div class="d-flex ai-center gap-16 pb-16 border-bottom">
                <div class="modal-icon text-red bg-red-light m-0">
                    <i class="fa-solid fa-arrow-rotate-left"></i>
                </div>
                <div>
                    <div class="fs-18 fw-700"><?php echo L('return_modal_title'); ?></div>
                    <div class="fs-12 text-muted" id="returnModalSubtitle"><?php echo L('return_modal_checking_deadlines'); ?></div>
                </div>
            </div>

            <!-- Indicadores de Plazo -->
            <div class="d-grid grid-2 gap-12 w-100">
                <div id="statusCommercial" class="p-16 br-12 border d-flex flex-column ai-center jc-center gap-8 transition shadow-sm">
                    <i class="fa-solid fa-calendar-check fs-20"></i>
                    <span class="fs-12 fw-700"><?php echo L('return_label_refund'); ?></span>
                    <span class="fs-11 opacity-80 border-top pt-8 mt-4 w-100 text-center" id="textCommercial">30 días</span>
                </div>
                <div id="statusWarranty" class="p-16 br-12 border d-flex flex-column ai-center jc-center gap-8 transition shadow-sm">
                    <i class="fa-solid fa-shield-halved fs-20"></i>
                    <span class="fs-12 fw-700"><?php echo L('modal_label_warranty'); ?></span>
                    <span class="fs-11 opacity-80 border-top pt-8 mt-4 w-100 text-center" id="textWarranty">3 años</span>
                </div>
            </div>

            <div class="form-group mb-0">
                <label class="form-label fw-600 mb-12 fs-13 text-muted tt-uppercase ls-1"><?php echo L('return_label_method'); ?>:</label>
                <div class="d-flex flex-column gap-12" id="refundMethods">
                    <label class="method-option border br-12 p-16 d-flex ai-center gap-16 cp transition hover-bg-surface2" id="optCash">
                        <div class="radio-custom d-flex ai-center jc-center">
                            <input type="radio" name="metodoReembolso" value="efectivo" checked>
                            <div class="radio-dot"></div>
                        </div>
                        <i class="fa-solid fa-money-bill-1-wave fs-20 text-green"></i>
                        <div class="flex-1">
                            <div class="fs-14 fw-700"><?php echo L('tpv_method_cash'); ?></div>
                            <div class="fs-11 text-muted"><?php echo L('return_cash_sub'); ?></div>
                        </div>
                    </label>
                    <label class="method-option border br-12 p-16 d-flex ai-center gap-16 cp transition hover-bg-surface2" id="optBalance">
                        <div class="radio-custom d-flex ai-center jc-center">
                            <input type="radio" name="metodoReembolso" value="vale">
                            <div class="radio-dot"></div>
                        </div>
                        <i class="fa-solid fa-ticket fs-20 text-blue"></i>
                        <div class="flex-1">
                            <div class="fs-14 fw-700"><?php echo L('return_voucher_desc'); ?></div>
                            <div class="fs-11 text-muted"><?php echo L('return_voucher_sub'); ?></div>
                        </div>
                    </label>
                    <label class="method-option border br-12 p-16 d-flex ai-center gap-16 cp transition hover-bg-surface2" id="optExchange">
                        <div class="radio-custom d-flex ai-center jc-center">
                            <input type="radio" name="metodoReembolso" value="reemplazo">
                            <div class="radio-dot"></div>
                        </div>
                        <i class="fa-solid fa-box-open fs-20 text-orange"></i>
                        <div class="flex-1">
                            <div class="fs-14 fw-700"><?php echo L('return_warranty_desc'); ?></div>
                            <div class="fs-11 text-muted"><?php echo L('return_warranty_sub'); ?></div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="form-group mb-12 d-none" id="qtyReturnRow">
                <label class="form-label fw-600 mb-8"><?php echo L('return_label_qty'); ?>:</label>
                <div class="d-flex ai-center gap-12">
                    <input type="number" id="qtyReturnInput" class="form-input text-center fs-16 fw-700" style="max-width: 100px;" min="1" step="1">
                    <span class="fs-12 text-muted" id="qtyReturnMaxLabel"><?php echo L('return_label_qty_available', true, ['{count}' => '<span id="qtyReturnMaxLabelBody"></span>']); ?></span>
                </div>
            </div>

            <!-- BLOQUE: SELECCIÓN DE CLIENTE PARA VALE (Solo visible si es anónimo + vale) -->
            <div id="returnClientSection" class="d-none bg-orange-light p-16 br-12 mb-12 border border-orange">
                <div class="d-flex ai-center gap-8 mb-12 text-orange fw-700 fs-13">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?php echo L('return_client_required'); ?>
                </div>

                <!-- Buscador de cliente -->
                <div class="d-flex gap-8 mb-16">
                    <input type="text" id="returnClientSearch" class="form-input fs-12 flex-1" placeholder="<?php echo L('client_placeholder_search_existing', true); ?>">
                    <button type="button" class="btn-save w-auto p-4-12 bg-orange border-orange" onclick="buscarClienteParaVale()">
                        <i class="fa-solid fa-search"></i>
                    </button>
                </div>
                <div id="returnClientResults" class="d-flex flex-column gap-6 mb-16"></div>

                <div class="text-center text-muted fs-11 mb-16 fw-700"><?php echo L('client_or_create_new'); ?></div>

                <!-- Crear nuevo cliente -->
                <div class="grid-2 gap-8">
                    <div>
                        <input type="text" id="returnNewClientName" class="form-input fs-12" placeholder="<?php echo L('client_placeholder_name'); ?>">
                    </div>
                    <div>
                        <input type="text" id="returnNewClientNif" class="form-input fs-12 font-mono" placeholder="DNI / NIE">
                    </div>
                </div>
                <button type="button" class="btn-cancel full-width mt-8 pt-6 pb-6" onclick="crearClienteParaVale()">
                    <i class="fa-solid fa-user-plus mr-8"></i> <?php echo L('client_btn_save_use'); ?>
                </button>
                <input type="hidden" id="returnSelectedClientId" value="">
                <div id="returnSelectedClientLabel" class="mt-12 text-center text-green fw-700 fs-13 d-none"></div>
            </div>


            <div class="form-group mb-0">
                <label class="form-label fw-600 mb-8"><?php echo L('client_label_notes'); ?>:</label>
                <select id="returnReason" class="form-input mb-8">
                    <option value="Defectuoso"><?php echo L('return_reason_defective'); ?></option>
                    <option value="Garantía"><?php echo L('return_reason_warranty'); ?></option>
                    <option value="Error Cliente"><?php echo L('return_reason_error'); ?></option>
                    <option value="Otro"><?php echo L('return_reason_other'); ?></option>
                </select>
                <textarea id="returnNote" class="form-input fs-12" placeholder="<?php echo L('client_comments_placeholder', true); ?>" rows="2"></textarea>
            </div>

            <div class="modal-footer full-width gap-12 pt-16 border-top">
                <button onclick="document.getElementById('returnModal').classList.remove('visible')" class="btn-cancel m-0"><?php echo L('modal_close'); ?></button>
                <button id="confirmReturnBtn" class="btn-save bg-red flex-1 m-0 shadow-sm">
                    <i class="fa-solid fa-check mr-8"></i><?php echo L('return_btn_confirm'); ?>
                </button>
            </div>
        </div>
    </div>

    <style>
        .grid-2 {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
        }

        .method-option {
            background: var(--surface);
            border: 1px solid var(--border);
            cursor: pointer;
        }

        .method-option:has(input:checked) {
            border-color: var(--primary) !important;
            background-color: var(--primary-light) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05), inset 0 0 0 1px var(--primary);
        }

        .radio-custom {
            width: 20px;
            height: 20px;
            border: 2px solid var(--border);
            border-radius: 50%;
            background: white;
            transition: all 0.2s ease;
            position: relative;
            flex-shrink: 0;
        }

        .method-option:has(input:checked) .radio-custom {
            border-color: var(--primary);
        }

        .radio-dot {
            width: 10px;
            height: 10px;
            background: var(--primary);
            border-radius: 50%;
            transform: scale(0);
            transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .method-option:has(input:checked) .radio-dot {
            transform: scale(1);
        }

        .method-option.disabled {
            opacity: 0.4;
            cursor: not-allowed !important;
            background: #f1f3f5 !important;
            filter: grayscale(1);
        }

        .method-option input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .status-ok {
            background: #e6fffa;
            border-color: #38b2ac !important;
            color: #234e52;
        }

        .status-warn {
            background: #fffaf0;
            border-color: #ed8936 !important;
            color: #7b341e;
        }

        .status-err {
            background: #fff5f5;
            border-color: #f56565 !important;
            color: #742a2a;
        }

        .transition {
            transition: all 0.2s ease;
        }

        .modal-content {
            background-color: var(--surface);
            border-radius: 20px;
            width: 100%;
            max-width: 900px;
            position: relative;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-height: 90vh;
            overflow-y: auto;
            border: none;
        }

        .br-8 {
            border-radius: 8px;
        }

        /* TICKET TABS */
        .ticket-tabs {
            background: var(--surface2);
            border-bottom: 2px solid var(--border-color);
        }
        .ticket-tab {
            background: none;
            border: none;
            color: var(--text-muted);
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
        }
        .ticket-tab.active {
            color: var(--accent);
            border-bottom-color: var(--accent);
            background: var(--surface);
        }
        .ticket-tab:hover:not(.active) {
            background: rgba(var(--accent-rgb), 0.05);
            color: var(--text);
        }

        .points-icon-wrap {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .points-card.highlighted {
            background: var(--accent-light);
        }

        .p-32 {
            padding: 32px;
        }

        .p-12 {
            padding: 12px;
        }

        .gap-24 {
            gap: 24px;
        }

        .status-ok {
            background: var(--green-light);
            color: var(--green);
            border-color: var(--green);
        }

        .status-warn {
            background: var(--orange-light);
            color: var(--orange);
            border-color: var(--orange);
        }

        .status-err {
            background: var(--red-light);
            color: var(--red);
            border-color: var(--red);
        }
    </style>
    <script src="./webroot/js/validaciones.js?v=2"></script>
    <script src="./webroot/js/utils_global.js?v=16"></script>
    <script src="./webroot/js/main.js?v=22"></script>
    <script src="./webroot/js/pagination.js?v=1"></script>
    <!-- SISTEMA DE MODALES GLOBALES (ALERTAS Y CONFIRMACIONES) -->
    <div class="modal-overlay" id="globalAlertModal" style="z-index: 15000;">
        <div class="modal modal-content gap-16 ai-center w-400 text-center p-32">
            <div id="globalAlertIcon" class="modal-icon fs-32 mb-8 mt-0">
                <i class="fa-solid fa-circle-info"></i>
            </div>
            <div id="globalAlertTitle" class="fs-18 fw-700"><?php echo L('modal_alert_title'); ?></div>
            <div id="globalAlertMessage" class="fs-14 text-muted"></div>
            <div class="modal-footer full-width mt-16 pb-0">
                <button onclick="cerrarAlert()" class="btn-save w-full m-0"><?php echo L('modal_btn_ok'); ?></button>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="globalConfirmModal" style="z-index: 15000;">
        <div class="modal modal-content gap-16 ai-center w-400 text-center p-32">
            <div id="globalConfirmIcon" class="modal-icon fs-32 mb-8 mt-0 text-orange bg-orange-light">
                <i class="fa-solid fa-circle-question"></i>
            </div>
            <div id="globalConfirmTitle" class="fs-18 fw-700"><?php echo L('modal_confirm_title'); ?></div>
            <div id="globalConfirmMessage" class="fs-14 text-muted"><?php echo L('modal_confirm_msg'); ?></div>
            <div class="modal-footer full-width mt-16 pb-0 gap-12">
                <button onclick="cerrarConfirm(false)" class="btn-cancel w-full m-0"><?php echo L('modal_cancel'); ?></button>
                <button id="globalConfirmBtn" onclick="cerrarConfirm(true)" class="btn-save w-full m-0"><?php echo L('modal_btn_confirm'); ?></button>
            </div>
        </div>
    </div>

    <!-- MODAL PROMPT GLOBAL -->
    <div class="modal-overlay" id="globalPromptModal" style="z-index: 15000;">
        <div class="modal modal-content gap-16 ai-center w-450 text-center p-32">
            <div id="globalPromptIcon" class="modal-icon fs-32 mb-8 mt-0 text-accent bg-blue-light">
                <i class="fa-solid fa-pen-to-square"></i>
            </div>
            <div id="globalPromptTitle" class="fs-18 fw-700"><?php echo L('modal_prompt_title'); ?></div>
            <div id="globalPromptMessage" class="fs-14 text-muted mb-8"></div>
            <input type="text" id="globalPromptInput" class="form-input w-full" style="text-align: center;">
            <div class="modal-footer full-width mt-16 pb-0 gap-12">
                <button onclick="cerrarPrompt(null)" class="btn-cancel w-full m-0"><?php echo L('modal_cancel'); ?></button>
                <button id="globalPromptBtn" onclick="cerrarPrompt(document.getElementById('globalPromptInput').value)" class="btn-save w-full m-0"><?php echo L('modal_btn_accept'); ?></button>
            </div>
        </div>
    </div>

    <script>
        let confirmCallback = null;

        /**
         * Muestra un modal de alerta personalizado
         * @param {string} title - Título del modal
         * @param {string} message - Mensaje a mostrar
         * @param {string} type - 'info', 'success', 'warning', 'error'
         */
        function showCustomAlert(title, message, type = 'info') {
            const modal = document.getElementById('globalAlertModal');
            const iconWrap = document.getElementById('globalAlertIcon');
            const titleEl = document.getElementById('globalAlertTitle');
            const msgEl = document.getElementById('globalAlertMessage');

            titleEl.innerText = title;
            msgEl.innerText = message;

            // Resetear clases de icono
            iconWrap.className = 'modal-icon fs-32 mb-8 mt-0';
            let iconHtml = '<i class="fa-solid fa-circle-info"></i>';

            switch (type) {
                case 'success':
                    iconWrap.classList.add('text-green', 'bg-green-light');
                    iconHtml = '<i class="fa-solid fa-circle-check"></i>';
                    break;
                case 'warning':
                    iconWrap.classList.add('text-orange', 'bg-orange-light');
                    iconHtml = '<i class="fa-solid fa-triangle-exclamation"></i>';
                    break;
                case 'error':
                    iconWrap.classList.add('text-red', 'bg-red-light');
                    iconHtml = '<i class="fa-solid fa-circle-xmark"></i>';
                    break;
                default:
                    iconWrap.classList.add('text-accent', 'bg-blue-light');
            }

            iconWrap.innerHTML = iconHtml;
            modal.classList.add('visible');
        }

        function cerrarAlert() {
            document.getElementById('globalAlertModal').classList.remove('visible');
        }

        /**
         * Muestra un modal de confirmación personalizado
         * @param {string} title - Título del modal
         * @param {string} message - Mensaje explicativo
         * @param {function} callback - Función a ejecutar si se confirma
         * @param {string} confirmText - Texto del botón de confirmar
         * @param {string} type - 'danger', 'warning', 'info'
         */
        function showCustomConfirm(title, message, callback, confirmText = 'Confirmar', type = 'warning') {
            const modal = document.getElementById('globalConfirmModal');
            const btnConfirm = document.getElementById('globalConfirmBtn');
            const iconWrap = document.getElementById('globalConfirmIcon');

            document.getElementById('globalConfirmTitle').innerText = title;
            document.getElementById('globalConfirmMessage').innerText = message;
            btnConfirm.innerText = confirmText;
            confirmCallback = callback;

            // Estilos según criticidad
            btnConfirm.className = 'btn-save w-full m-0';
            iconWrap.className = 'modal-icon fs-32 mb-8 mt-0';

            if (type === 'danger') {
                btnConfirm.classList.add('bg-red');
                iconWrap.classList.add('text-red', 'bg-red-light');
                iconWrap.innerHTML = '<i class="fa-solid fa-trash-can"></i>';
            } else {
                iconWrap.classList.add('text-orange', 'bg-orange-light');
                iconWrap.innerHTML = '<i class="fa-solid fa-circle-question"></i>';
            }

            modal.classList.add('visible');
        }

        function cerrarConfirm(result) {
            const cb = confirmCallback;
            confirmCallback = null;
            document.getElementById('globalConfirmModal').classList.remove('visible');
            if (result && typeof cb === 'function') {
                cb();
            }
        }

        let promptCallback = null;

        function showCustomPrompt(title, message, callback, defaultValue = '', placeholder = '') {
            const modal = document.getElementById('globalPromptModal');
            const input = document.getElementById('globalPromptInput');
            document.getElementById('globalPromptTitle').innerText = title || "<?php echo L('modal_prompt_input_title', true); ?>";
            document.getElementById('globalPromptMessage').innerText = message || '';
            input.value = defaultValue || '';
            input.placeholder = placeholder || '';
            promptCallback = callback;
            modal.classList.add('visible');
            setTimeout(() => input.focus(), 100);

            // Allow ENTER key
            input.onkeyup = (e) => {
                if (e.key === 'Enter') cerrarPrompt(input.value);
                if (e.key === 'Escape') cerrarPrompt(null);
            };
        }

        function cerrarPrompt(val) {
            const cb = promptCallback;
            promptCallback = null;
            document.getElementById('globalPromptModal').classList.remove('visible');
            if (typeof cb === 'function') {
                cb(val);
            }
        }

        // Reemplazar window.alert y window.confirm para que el código existente lo use automáticamente
        // NOTA: Esto no es perfecto porque showCustomAlert no bloquea el hilo, pero ayuda.
        // Se recomienda llamar directamente a showCustomAlert si se requiere control de flujo.
        window.nativeAlert = window.alert;
        window.alert = function(msg) {
            showCustomAlert("<?php echo L('modal_alert_system_notice'); ?>", msg);
        };

        /**
         * Alterna entre las pestañas del ticket (Resumen / Puntos)
         */
        function switchTicketTab(tab) {
            const summaryBtn = document.querySelector('.ticket-tab:nth-child(1)');
            const pointsBtn = document.querySelector('.ticket-tab:nth-child(2)');
            const summaryContent = document.getElementById('tkSummaryTab');
            const pointsContent = document.getElementById('tkPointsTab');

            if (tab === 'summary') {
                summaryBtn.classList.add('active');
                pointsBtn.classList.remove('active');
                summaryContent.classList.add('active');
                summaryContent.classList.remove('d-none');
                pointsContent.classList.add('d-none');
                pointsContent.classList.remove('active');
            } else {
                summaryBtn.classList.remove('active');
                pointsBtn.classList.add('active');
                summaryContent.classList.remove('active');
                summaryContent.classList.add('d-none');
                pointsContent.classList.remove('d-none');
                pointsContent.classList.add('active');
            }
        }
    </script>
</body>

</html>