<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TPV · ElectroBazar</title>
    <link rel="stylesheet" href="./webroot/css/estilos.css?v=20" />
    <link rel="stylesheet" href="./webroot/css/components.css?v=12" />
    <!-- Generación de PDF en cliente -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link rel="stylesheet" href="./webroot/css/fonts.css" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?php
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/ConfiguracionPDO.php';

    // Cargar configuración de empresa
    $appConfig = ConfiguracionPDO::obtenerConfiguracion();
    $empresaNombre = $appConfig['empresa_nombre'] ?? 'ElectroBazar';
    $empresaDireccion = $appConfig['empresa_direccion'] ?? '';
    $empresaNif = $appConfig['empresa_nif'] ?? '';

    // Cargar tema del usuario actual (si existe)
    $themeMode = 'light';
    $themeAccent = 'blue';
    if (isset($_SESSION['usuarioActualTPV'])) {
        try {
            $qTheme = DBPDO::ejecutarConsulta(
                "SELECT theme_mode, theme_accent FROM usuarios WHERE id = :id",
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
        const CAJA_ABIERTA = <?php echo json_encode($avInicioPrivado['cajaAbierta'] ?? false); ?>;
        const ESC_POS_ENABLED = true;
        const USER_THEME_MODE = <?php echo json_encode($themeMode); ?>;
        const USER_THEME_ACCENT = <?php echo json_encode($themeAccent); ?>;
    </script>
</head>

<body data-page="<?php echo $_SESSION['paginaEnCurso'] ?? ''; ?>"
    data-theme-mode="<?php echo htmlspecialchars($themeMode, ENT_QUOTES, 'UTF-8'); ?>"
    data-theme-accent="<?php echo htmlspecialchars($themeAccent, ENT_QUOTES, 'UTF-8'); ?>">
    <!-- TOPBAR -->
    <header class="topbar">
        <div class="topbar-brand">
            <div class="topbar-brand-dot"></div>
            TPV · ElectroBazar
        </div>
        <div class="topbar-info">
            <?php if (isset($avInicioPrivado['cajaAbierta']) && $avInicioPrivado['cajaAbierta']): ?>
                <span class="status-indicator" style="background: var(--green); color: white; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: bold; text-transform: uppercase;">
                    <i class="fa-solid fa-circle-check"></i> ABIERTA
                </span>
            <?php else: ?>
                <span class="status-indicator" style="background: var(--red); color: white; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: bold; text-transform: uppercase;">
                    <i class="fa-solid fa-circle-xmark"></i> CERRADA
                </span>
            <?php endif; ?>
            &nbsp;·&nbsp; Caja #1 &nbsp;·&nbsp; <span id="clock">--:--</span> &nbsp;·&nbsp;
            <span id="datestr">--</span>
        </div>

        <?php if (isset($_SESSION['usuarioActualTPV']) && ($_SESSION['paginaEnCurso'] ?? '') !== 'login'): ?>
            <div class="topbar-actions">
                <div class="topbar-user-mini">
                    <div class="avatar-mini">
                        <?php
                        $nombreComp = $_SESSION['usuarioActualTPV']->getNombreCompleto();
                        $nombres = explode(" ", $nombreComp);
                        echo strtoupper(substr($nombres[0], 0, 1) . (isset($nombres[1]) ? substr($nombres[1], 0, 1) : ""));
                        ?>
                    </div>
                    <div class="user-details">
                        <span class="u-name"><?php echo $nombreComp; ?></span>
                        <span class="u-rol"><?php echo strtoupper($_SESSION['usuarioActualTPV']->getRol()); ?></span>
                    </div>
                </div>

                <div class="v-divider"></div>

                <nav class="topbar-nav">
                    <form method="post" action="index.php">
                        <button type="submit" name="irDashboard" class="topbar-btn" title="Panel de Control">
                            <i class="fa-solid fa-gauge-high"></i>
                            <span>Inicio</span>
                        </button>
                    </form>

                    <form method="post" action="index.php">
                        <button type="submit" name="irTPV" class="topbar-btn" title="Terminal Punto de Venta">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <span>TPV</span>
                        </button>
                    </form>

                    <form method="post" action="index.php">
                        <button type="submit" name="irMiPerfil" class="topbar-btn" title="Mi Perfil">
                            <i class="fa-solid fa-user-gear"></i>
                            <span>Perfil</span>
                        </button>
                    </form>

                    <?php if ($_SESSION['usuarioActualTPV']->getRol() === 'admin'): ?>
                        <form method="post" action="index.php">
                            <button type="submit" name="irCierreCaja" class="topbar-btn" title="Cierre de caja">
                                <i class="fa-solid fa-vault"></i>
                                <span>Caja</span>
                            </button>
                        </form>
                    <?php endif; ?>

                    <form method="post" action="index.php">
                        <button type="submit" name="salir" class="topbar-btn btn-exit" title="Cerrar sesión">
                            <i class="fa-solid fa-power-off"></i>
                            <span>Salir</span>
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

            <!-- SECCIÓN CLIENTE (solo para facturas) -->
            <div id="tkClienteSection" class="d-none" style="background: var(--surface2); padding: 24px; border-radius: 12px; margin-bottom: 24px; border-left: 4px solid var(--accent);">
                <div style="font-weight: 700; font-size: 13px; margin-bottom: 8px; color: var(--accent);">DATOS DEL CLIENTE</div>
                <div style="font-size: 14px; font-weight: 600;" id="tkNombreClienteProminente">—</div>
                <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">CIF/NIF: <span id="tkNifClienteProminente" style="font-family: monospace; font-weight: 600;">—</span></div>
            </div>

            <div class="ticket-meta">
                <span id="tkTipoDoc" class="doc-type">TICKET DE VENTA</span>
                <span class="label">Nº Documento</span> <span id="tkNumero" class="value">#—</span>
                <span class="label">Fecha</span> <span id="tkFecha">—</span>
                <span class="label">Cajero</span> <span id="tkCajero">—</span>
                <span class="label">Pago</span> <span id="tkMetodo">—</span>
                <!-- Cliente (solo facturas) -->
                <span id="tkLabelClienteMeta" class="label d-none">Cliente</span>
                <span id="tkClienteMeta" class="d-none">—</span>
                <span id="tkLabelNifMeta" class="label d-none">CIF/NIF</span>
                <span id="tkNifMeta" class="d-none" style="font-family: monospace;">—</span>
            </div>

            <div id="tkLineas" class="ticket-items mt-16 mb-16"></div>

            <!-- SECCIÓN FINANCIACIÓN -->
            <div id="tkFinancingSection" class="d-none" style="margin-top: 10px; padding: 10px; border: 1px dashed var(--accent); border-radius: 6px; background: rgba(52,152,219,0.05);">
                <div style="font-size: 11px; font-weight: 700; color: var(--accent); text-transform: uppercase; margin-bottom: 4px;">Detalles de Financiación</div>
                <div style="display: flex; justify-content: space-between; font-size: 12px;">
                    <span class="text-muted">Entidad:</span>
                    <span id="tkFinEntity" class="font-bold">—</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 12px;">
                    <span class="text-muted">Plazo:</span>
                    <span id="tkFinCuotas">—</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 12px;">
                    <span class="text-muted">Cuota:</span>
                    <span id="tkFinImporteCuota" class="font-bold text-accent">—</span>
                </div>
            </div>

            <div class="ticket-totals pt-16 border-top">
                <div class="ticket-total-row label text-muted">
                    <span>Subtotal</span><span id="tkSubtotal">—</span>
                </div>
                <div id="tkDescRow" class="ticket-total-row d-none text-green">
                    <span id="tkDescLabel">Descuento</span><span id="tkDescAmt">—</span>
                </div>
                <div class="ticket-total-row label text-muted">
                    <span>Base imponible</span><span id="tkBase">—</span>
                </div>
                <div class="ticket-total-row label text-muted">
                    <span>IVA (21%)</span><span id="tkIva">—</span>
                </div>
                <div class="ticket-total-row ticket-total-main">
                    <span>TOTAL</span><span id="tkTotal" class="font-mono">—</span>
                </div>
                <!-- Efectivo -->
                <div id="tkEfectivoRow" class="d-none flex-column gap-4 mt-8 pt-8 border-top text-muted fs-12">
                    <div class="ticket-total-row"><span>Entregado</span><span id="tkEntregado">—</span></div>
                    <div class="ticket-total-row"><span>Cambio</span><span id="tkCambio">—</span></div>
                </div>
            </div>

            <!-- SECCIÓN PAGOS PARCIALES -->
            <div id="tkPagosSection" class="d-none" style="margin-top: 16px; padding: 12px; border: 1px solid var(--surface2); border-radius: 8px; background: rgba(0,0,0,0.02);">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">Desglose de Pagos</div>
                <div id="tkPagosLista" style="display: flex; flex-direction: column; gap: 4px;"></div>

                <div id="tkAbonarParteContainer" class="d-none" style="margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--surface2);">
                    <button id="btnAbonarParte" onclick="abrirModalAbonoParcial()" class="btn-cancel w-100" style="background: var(--blue-light); color: var(--blue); border-color: var(--blue);">
                        <i class="fa-solid fa-hand-holding-dollar"></i> Registrar nuevo Abono
                    </button>
                </div>
            </div>

            <div class="ticket-email-section" id="ticketEmailSection">
                <label class="form-label fs-11">Enviar por email</label>
                <div class="d-flex gap-8">
                    <input type="text" id="tkEmailInput" placeholder="cliente@ejemplo.com" class="form-input font-mono fs-13">
                    <button onclick="enviarTicketEmail()" id="btnSendEmail" class="btn-filter h-40 p-0-20 bg-blue">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </div>
                <span id="err-email" class="form-error"></span>
            </div>

            <div class="modal-footer p-0-24-20">
                <button onclick="imprimirTicket()" class="btn-cancel d-flex ai-center jc-center gap-8">
                    <i class="fa-solid fa-print"></i> Thermal
                </button>
                <button onclick="descargarPDFTicket()" class="btn-cancel d-flex ai-center jc-center gap-8" style="background: var(--blue-light); color: var(--blue); border-color: var(--blue);">
                    <i class="fa-solid fa-file-pdf"></i> PDF
                </button>

                <!-- Botón dinámico Conversión -->
                <button id="btnToggleFactura" class="btn-cancel btn-toggle-factura d-flex ai-center jc-center gap-8" style="background: var(--surface2); color: var(--accent); border-color: var(--accent); display:none;">
                    <i class="fa-solid fa-rotate"></i> <span id="btnToggleFacturaText">Convertir</span>
                </button>

                <button id="btnNuevaVenta" onclick="nuevaVenta()" class="btn-save">Nueva venta</button>
                <button id="btnAnularTicket" class="btn-cancel" style="background: var(--red); color: white; border-color: var(--red); display: none;">
                    <i class="fa-solid fa-ban"></i> Anular Ticket
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL: REGISTRAR ABONO PARCIAL -->
    <div class="modal-overlay" id="abonoParcialModal">
        <div class="modal modal-content gap-16 ai-stretch w-400">
            <div class="modal-header mb-0">
                <h2 class="m-0 fs-18">Registrar Abono a Cuenta</h2>
                <button onclick="document.getElementById('abonoParcialModal').classList.remove('visible')" class="btn-close-modal">&times;</button>
            </div>
            <div class="modal-body p-20">
                <div class="form-group mb-0">
                    <label class="form-label">Importe a abonar (€)</label>
                    <input type="number" id="abonoImporte" class="form-input font-mono fs-16" step="0.01" min="0.01" placeholder="0.00">
                </div>
                <div class="form-group mb-0 mt-16">
                    <label class="form-label">Método de pago</label>
                    <select id="abonoMetodo" class="form-input">
                        <option value="efectivo">Efectivo</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="bizum">Bizum</option>
                        <option value="transferencia">Transferencia Bancaria</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <!-- Para evitar que el formulario se envíe con ENTER lo dejamos sin form -->
            </div>
            <div class="modal-footer full-width jc-end">
                <button onclick="document.getElementById('abonoParcialModal').classList.remove('visible')" class="btn-cancel">Cancelar</button>
                <button onclick="procesarAbonoParcial()" class="btn-save bg-blue">Registrar Abono</button>
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
                    <div class="fs-18 fw-700">Gestión de Devolución</div>
                    <div class="fs-12 text-muted" id="returnModalSubtitle">Verificando plazos...</div>
                </div>
            </div>

            <!-- Indicadores de Plazo -->
            <div class="d-flex gap-12">
                <div id="statusCommercial" class="flex-1 p-12 br-8 border d-flex flex-column ai-center gap-4">
                    <i class="fa-solid fa-calendar-check mb-4"></i>
                    <span class="fs-11 fw-600">Reembolso</span>
                    <span class="fs-10 opacity-70 border-top pt-4 mt-4 w-100 text-center" id="textCommercial">30 días</span>
                </div>
                <div id="statusWarranty" class="flex-1 p-12 br-8 border d-flex flex-column ai-center gap-4">
                    <i class="fa-solid fa-shield-halved mb-4"></i>
                    <span class="fs-11 fw-600">Garantía</span>
                    <span class="fs-10 opacity-70 border-top pt-4 mt-4 w-100 text-center" id="textWarranty">3 años</span>
                </div>
            </div>

            <div class="form-group mb-0">
                <label class="form-label fw-600 mb-8">Método de Reembolso / Solución:</label>
                <div class="d-grid gap-8" id="refundMethods">
                    <label class="method-option border br-8 p-12 d-flex ai-center gap-12 cp transition" id="optCash">
                        <input type="radio" name="metodoReembolso" value="efectivo" checked>
                        <i class="fa-solid fa-money-bill-1-wave text-green"></i>
                        <div class="flex-1">
                            <div class="fs-13 fw-600">Efectivo</div>
                            <div class="fs-10 text-muted">Devolución inmediata en metálico</div>
                        </div>
                    </label>
                    <label class="method-option border br-8 p-12 d-flex ai-center gap-12 cp transition" id="optBalance">
                        <input type="radio" name="metodoReembolso" value="vale">
                        <i class="fa-solid fa-ticket text-blue"></i>
                        <div class="flex-1">
                            <div class="fs-13 fw-600">Vale / Cupón</div>
                            <div class="fs-10 text-muted">Devolución para futura compra</div>
                        </div>
                    </label>
                    <label class="method-option border br-8 p-12 d-flex ai-center gap-12 cp transition" id="optExchange">
                        <input type="radio" name="metodoReembolso" value="reemplazo">
                        <i class="fa-solid fa-box-open text-orange"></i>
                        <div class="flex-1">
                            <div class="fs-13 fw-600">Cambio por Garantía</div>
                            <div class="fs-10 text-muted">Sustitución por una unidad nueva</div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="form-group mb-0">
                <label class="form-label fw-600 mb-8">Información Adicional:</label>
                <select id="returnReason" class="form-input mb-8">
                    <option value="Defectuoso">Producto Defectuoso</option>
                    <option value="Garantía">Tramitación de Garantía</option>
                    <option value="Error Cliente">Error en la compra</option>
                    <option value="Otro">Otro motivo</option>
                </select>
                <textarea id="returnNote" class="form-input fs-12" placeholder="Describe el problema o notas adicionales..." rows="2"></textarea>
            </div>

            <div class="modal-footer full-width gap-12 pt-16 border-top">
                <button onclick="document.getElementById('returnModal').classList.remove('visible')" class="btn-cancel m-0">Cerrar</button>
                <button id="confirmReturnBtn" class="btn-save bg-red flex-1 m-0 shadow-sm">
                    <i class="fa-solid fa-check mr-8"></i>Confirmar Devolución
                </button>
            </div>
        </div>
    </div>

    <style>
        .method-option:has(input:checked) {
            border-color: var(--primary);
            background-color: var(--primary-light);
            box-shadow: 0 0 0 1px var(--primary);
        }

        .method-option.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f8f9fa;
        }

        .method-option input {
            display: none;
        }

        .transition {
            transition: all 0.2s ease;
        }

        .br-8 {
            border-radius: 8px;
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

    <script src="./webroot/js/main.js?v=12"></script>
</body>

</html>