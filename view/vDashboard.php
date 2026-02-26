</header>
<div class="main-full dashboard-wrapper">
    <div class="dashboard-card">
        
        <div class="dashboard-header">
            <div>
                <i class="fa-solid fa-bolt-lightning"></i>
            </div>
            <h1>Bienvenido, <?php echo $_SESSION['usuarioActualTPV']->getNombreCompleto(); ?></h1>
            <p>¿Qué deseas hacer hoy?</p>
        </div>

        <div class="dashboard-grid">
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

        <div class="dashboard-footer">
            <form method="post">
                <button type="submit" name="irMiPerfil" class="btn-profile">
                    <i class="fa-solid fa-user-gear"></i>
                    Configurar Mi Perfil
                </button>
            </form>

            <form method="post">
                <button type="submit" name="salir" class="btn-logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Cerrar Sesión Segura
                </button>
            </form>
        </div>

    </div>
</div>
