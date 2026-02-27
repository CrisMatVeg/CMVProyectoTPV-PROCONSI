<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TPV · ElectroBazar</title>
    <link rel="stylesheet" href="./webroot/css/estilos.css" />
    <link rel="stylesheet" href="./webroot/css/components.css" />
    <link rel="stylesheet" href="./webroot/css/fonts.css" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>
        const IS_ADMIN_BACKEND = <?php echo json_encode(isset($avInicioPrivado['esAdmin']) && $avInicioPrivado['esAdmin']); ?>;
        const DB_PRODUCTS = <?php echo json_encode($avInicioPrivado['productos'] ?? []); ?>;
        const CAJERO_NOMBRE = <?php echo json_encode($avInicioPrivado['nombre_completo'] ?? (isset($_SESSION['usuarioActualTPV']) ? $_SESSION['usuarioActualTPV']->getNombreCompleto() : '')); ?>;
        const IS_TPV = <?php echo json_encode(isset($_SESSION['paginaEnCurso']) && $_SESSION['paginaEnCurso'] === 'inicioPrivado'); ?>;
    </script>
</head>

<body data-page="<?php echo $_SESSION['paginaEnCurso'] ?? ''; ?>">
    <!-- TOPBAR -->
    <header class="topbar">
        <div class="topbar-brand">
            <div class="topbar-brand-dot"></div>
            TPV · ElectroBazar
        </div>
        <div class="topbar-info">
            Caja #1 &nbsp;·&nbsp; <span id="clock">--:--</span> &nbsp;·&nbsp;
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
                <div class="title"><i class="fa-solid fa-bolt-lightning"></i> ElectroBazar</div>
                <div class="info">C/ Tecnología 24, 28001 Madrid · NIF: B87654321</div>
                <button onclick="nuevaVenta()" class="btn-close-modal" title="Cerrar">×</button>
            </div>

            <!-- SECCIÓN CLIENTE (solo para facturas) -->
            <div id="tkClienteSection" class="d-none" style="background: var(--surface2); padding: 16px; border-radius: 8px; margin-bottom: 16px; border-left: 4px solid var(--accent);">
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

            <div id="tkLineas" class="ticket-items"></div>

            <div class="ticket-totals">
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
                    <i class="fa-solid fa-print"></i> Ticket
                </button>
                <button id="btnNuevaVenta" onclick="nuevaVenta()" class="btn-save">Nueva venta</button>
                <button id="btnAnularTicket" class="btn-cancel" style="background: var(--red); color: white; border-color: var(--red); display: none;">
                    <i class="fa-solid fa-ban"></i> Anular Ticket
                </button>
            </div>
        </div>
    </div>
    <script src="./webroot/js/main.js?v=4"></script>
</body>

</html>