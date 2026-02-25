</header>
<div class="main container-full">
    <div class="modal auth-card">
        <div class="topbar-brand-dot mb-24"></div>
        <div class="modal-title">Identificación</div>
        <div class="modal-sub mb-24">Acceso restringido a personal autorizado</div>

        <form method="post" action="index.php" class="full-width">
            <div class="form-group">
                <label class="payment-label">Nombre de Usuario</label>
                <input type="text" name="username" class="edit-field" placeholder="username" required>
            </div>

            <div class="form-group">
                <label class="payment-label">Contraseña</label>
                <input type="password" name="password" class="edit-field" placeholder="••••••••" required>
            </div>

            <input type="submit" name="acceder" value="Iniciar sesión" class="charge-btn mt-10">
        </form>

        <form method="post" action="index.php" class="mt-30">
            <input type="submit" name="atras" value="Volver al inicio" class="btn-clear">
        </form>
    </div>
</div>