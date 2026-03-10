</header>
<div class="main-full container w-700 m-0-auto pt-50">

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
            <input type="hidden" name="theme_mode" id="theme_mode_input">
            <input type="hidden" name="theme_accent" id="theme_accent_input">
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
            <div class="form-label">Temas</div>
            <p class="fs-12 text-muted mt-neg-15">Personaliza el modo y el color de acento de la interfaz. Se aplican en tiempo real y se guardan para tu usuario.</p>

            <div class="form-group-wrap gap-15">
                <div class="form-group mb-0">
                    <label class="form-label fs-12">Modo</label>
                    <div class="d-flex gap-8">
                        <button type="button" id="themeModeLight" class="cat-tab" onclick="setThemeMode('light')">
                            <i class="fa-solid fa-sun"></i> Claro
                        </button>
                        <button type="button" id="themeModeDark" class="cat-tab" onclick="setThemeMode('dark')">
                            <i class="fa-solid fa-moon"></i> Oscuro azul/gris
                        </button>
                        <button type="button" id="themeModeBlack" class="cat-tab" onclick="setThemeMode('black')">
                            <i class="fa-solid fa-moon"></i> Negro puro
                        </button>
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label class="form-label fs-12">Color de acento</label>
                    <div class="d-flex gap-8">
                        <button type="button" id="themeAccentBlue" class="cat-tab" style="border-color:#1d4ed8" onclick="setThemeAccent('blue')">
                            <span style="width:10px;height:10px;border-radius:999px;background:#1d4ed8;display:inline-block;"></span> Azul
                        </button>
                        <button type="button" id="themeAccentGreen" class="cat-tab" style="border-color:#16a34a" onclick="setThemeAccent('green')">
                            <span style="width:10px;height:10px;border-radius:999px;background:#16a34a;display:inline-block;"></span> Verde
                        </button>
                        <button type="button" id="themeAccentRed" class="cat-tab" style="border-color:#dc2626" onclick="setThemeAccent('red')">
                            <span style="width:10px;height:10px;border-radius:999px;background:#dc2626;display:inline-block;"></span> Rojo
                        </button>
                        <button type="button" id="themeAccentPurple" class="cat-tab" style="border-color:#7c3aed" onclick="setThemeAccent('purple')">
                            <span style="width:10px;height:10px;border-radius:999px;background:#7c3aed;display:inline-block;"></span> Morado
                        </button>
                        <button type="button" id="themeAccentAmber" class="cat-tab" style="border-color:#d97706" onclick="setThemeAccent('amber')">
                            <span style="width:10px;height:10px;border-radius:999px;background:#d97706;display:inline-block;"></span> Ámbar
                        </button>
                    </div>
                </div>
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

<script>
    (function initThemeSelectors() {
        const mode = document.body.dataset.themeMode || 'light';
        const accent = document.body.dataset.themeAccent || 'blue';
        const modeIds = {
            light: 'themeModeLight',
            dark: 'themeModeDark',
            black: 'themeModeBlack',
        };
        const accentIds = {
            blue: 'themeAccentBlue',
            green: 'themeAccentGreen',
            red: 'themeAccentRed',
            purple: 'themeAccentPurple',
            amber: 'themeAccentAmber',
        };

        Object.values(modeIds).forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.remove('active');
        });
        if (modeIds[mode] && document.getElementById(modeIds[mode])) {
            document.getElementById(modeIds[mode]).classList.add('active');
        }

        Object.values(accentIds).forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.remove('active');
        });
        if (accentIds[accent] && document.getElementById(accentIds[accent])) {
            document.getElementById(accentIds[accent]).classList.add('active');
        }

        const modeInput = document.getElementById('theme_mode_input');
        const accentInput = document.getElementById('theme_accent_input');
        if (modeInput) modeInput.value = mode;
        if (accentInput) accentInput.value = accent;
    })();
</script>

</div>