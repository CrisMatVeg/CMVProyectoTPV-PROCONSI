</header>
<div class="main container-full d-flex ai-center jc-center min-h-80vh">
    <div class="modal modal-content auth-card w-400 p-40">
        <div class="topbar-brand-dot mb-24"></div>
        <div class="modal-title text-center">Identificación</div>
        <div class="modal-sub mb-24 text-center">Acceso restringido a personal autorizado</div>

        <form method="post" action="index.php" class="form-grid d-flex flex-column gap-20" novalidate>
            <div class="form-group">
                <label class="form-label">Nombre de Usuario</label>
                <input type="text" name="username" class="form-input font-mono" placeholder="username" value="<?php echo $aRespuestas['username'] ?? ''; ?>">
                <?php if (isset($aErrores['username']) && $aErrores['username'] != null) { ?>
                    <span class="form-error"><?php echo $aErrores['username']; ?></span>
                <?php } ?>
            </div>

            <div class="form-group">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-input" placeholder="••••••••">
                <?php if (isset($aErrores['password']) && $aErrores['password'] != null) { ?>
                    <span class="form-error"><?php echo $aErrores['password']; ?></span>
                <?php } ?>
            </div>

            <button type="submit" name="acceder" class="btn-save mt-10 p-14">
                Iniciar sesión
            </button>
        </form>

        <form method="post" action="index.php" class="mt-30 text-center">
            <input type="submit" name="atras" value="Volver al inicio" class="btn-cancel border-none bg-none text-muted p-0 cursor-pointer">
        </form>
    </div>
</div>