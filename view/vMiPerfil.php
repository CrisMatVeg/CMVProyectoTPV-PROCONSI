</header>
<div class="main-full container w-600 m-0-auto pt-50">
    
    <div class="modal-content p-40">
        <div class="text-center mb-30">
            <div class="avatar w-80 h-80 fs-32 m-0-auto-15 d-flex ai-center jc-center bg-blue-light text-accent br-50">
                <?php 
                    $nombres = explode(" ", $avMiPerfil['usuario']->getNombreCompleto());
                    echo strtoupper(substr($nombres[0], 0, 1) . (isset($nombres[1]) ? substr($nombres[1], 0, 1) : ""));
                ?>
            </div>
            <h1 class="fs-22 m-0">Mi Perfil</h1>
            <p class="text-muted fs-14">Gestiona tu información personal y seguridad.</p>
        </div>

        <?php if ($avMiPerfil['error']): ?>
            <div class="status-pill status-inactive full-width p-12 mb-20 br-8">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo $avMiPerfil['error']; ?>
            </div>
        <?php endif; ?>

        <?php if ($avMiPerfil['success']): ?>
            <div class="status-pill status-active full-width p-12 mb-20 br-8">
                <i class="fa-solid fa-circle-check"></i> <?php echo $avMiPerfil['success']; ?>
            </div>
        <?php endif; ?>

        <form method="post" class="form-grid gap-20 d-flex flex-column" novalidate>
            <div class="form-group">
                <label class="form-label">Nombre Completo</label>
                <input type="text" name="nombre_completo" class="form-input" value="<?php echo htmlspecialchars($_REQUEST['nombre_completo'] ?? $avMiPerfil['usuario']->getNombreCompleto()); ?>">
                <?php if (isset($avMiPerfil['aErrores']['nombre_completo']) && $avMiPerfil['aErrores']['nombre_completo'] != null) { ?>
                    <span class="form-error"><?php echo $avMiPerfil['aErrores']['nombre_completo']; ?></span>
                <?php } ?>
            </div>

            <div class="form-group">
                <label class="form-label">Nombre de Usuario</label>
                <input type="text" class="form-input font-mono bg-disabled text-muted cursor-not-allowed" disabled value="<?php echo $avMiPerfil['usuario']->getUsername(); ?>">
                <small class="text-muted fs-11">El nombre de usuario no puede cambiarse.</small>
            </div>

            <hr class="border-none border-top m-10-0">
            <div class="form-label">Cambiar Contraseña</div>
            <p class="fs-12 text-muted mt-neg-15">Deja en blanco si no quieres cambiarla.</p>

            <div class="form-group-wrap gap-15 grid-2">
                <div class="form-group">
                    <label class="form-label">Nueva Contraseña</label>
                    <input type="password" name="pass1" class="form-input" placeholder="••••••••">
                    <?php if (isset($avMiPerfil['aErrores']['pass1']) && $avMiPerfil['aErrores']['pass1'] != null) { ?>
                        <span class="form-error"><?php echo $avMiPerfil['aErrores']['pass1']; ?></span>
                    <?php } ?>
                </div>
                <div class="form-group">
                    <label class="form-label">Repetir Contraseña</label>
                    <input type="password" name="pass2" class="form-input" placeholder="••••••••">
                    <?php if (isset($avMiPerfil['aErrores']['pass2']) && $avMiPerfil['aErrores']['pass2'] != null) { ?>
                        <span class="form-error"><?php echo $avMiPerfil['aErrores']['pass2']; ?></span>
                    <?php } ?>
                </div>
            </div>

            <div class="modal-footer p-0 mt-10">
                <button type="submit" name="volver" class="btn-cancel flex-1">
                    Cancelar
                </button>
                <button type="submit" name="guardarCambios" class="btn-save flex-2">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>

</div>

</div>
