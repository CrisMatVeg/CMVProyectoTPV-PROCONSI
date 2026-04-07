<div class="main-full d-flex jc-center ai-center" style="background: var(--bg);">
    <div class="modal-content p-60" style="border-radius: 24px; box-shadow: var(--shadow-lg); background: var(--surface); width: 100%; max-width: 1200px; max-height: 87vh; overflow-y: auto;">
        <div class="text-center mb-30">
            <h1 class="fs-22 m-0"><?php echo L('profile_title'); ?></h1>
            <p class="text-muted fs-14"><?php echo L('profile_subtitle'); ?></p>
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

        <form method="post" class="form-grid gap-15 d-flex flex-column" novalidate>
            <input type="hidden" name="theme_mode" id="theme_mode_input">
            <input type="hidden" name="theme_accent" id="theme_accent_input">
            <input type="hidden" name="theme_font" id="theme_font_input">
            <div class="form-group">
                <label class="form-label"><?php echo L('profile_label_name'); ?></label>
                <input type="text" name="nombre_completo" class="form-input" value="<?php echo htmlspecialchars($_REQUEST['nombre_completo'] ?? $avMiPerfil['usuario']->getNombreCompleto()); ?>">
                <?php if (isset($avMiPerfil['aErrores']['nombre_completo']) && $avMiPerfil['aErrores']['nombre_completo'] != null) { ?>
                    <span class="form-error"><?php echo $avMiPerfil['aErrores']['nombre_completo']; ?></span>
                <?php } ?>
            </div>

            <div class="form-group">
                <label class="form-label"><?php echo L('profile_label_user'); ?></label>
                <input type="text" class="form-input font-mono bg-disabled text-muted cursor-not-allowed" disabled value="<?php echo $avMiPerfil['usuario']->getUsername(); ?>">
                <small class="text-muted fs-11"><?php echo L('profile_user_help'); ?></small>
            </div>

            <hr class="border-none border-top m-10-0">
            <div class="form-label"><?php echo L('profile_themes_title'); ?></div>
            <p class="fs-12 text-muted mt-neg-15"><?php echo L('profile_themes_sub'); ?></p>

            <div class="form-group-wrap gap-15">
                <div class="form-group mb-0">
                    <label class="form-label fs-12"><?php echo L('profile_theme_mode'); ?></label>
                    <div class="d-flex gap-8">
                        <button type="button" id="themeModeLight" class="cat-tab" onclick="setThemeMode('light'); document.getElementById('theme_mode_input').value='light';">
                            <i class="fa-solid fa-sun"></i> <?php echo L('profile_mode_light'); ?>
                        </button>
                        <button type="button" id="themeModeDark" class="cat-tab" onclick="setThemeMode('dark'); document.getElementById('theme_mode_input').value='dark';">
                            <i class="fa-solid fa-moon"></i> <?php echo L('profile_mode_dark'); ?>
                        </button>
                        <button type="button" id="themeModeBlack" class="cat-tab" onclick="setThemeMode('black'); document.getElementById('theme_mode_input').value='black';">
                            <i class="fa-solid fa-moon"></i> <?php echo L('profile_mode_black'); ?>
                        </button>
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label class="form-label fs-12"><?php echo L('profile_theme_accent'); ?></label>
                    <div class="d-flex gap-8">
                        <button type="button" id="themeAccentBlue" class="cat-tab" style="border-color:#1d4ed8" onclick="setThemeAccent('blue'); document.getElementById('theme_accent_input').value='blue';">
                            <span style="width:10px;height:10px;border-radius:999px;background:#1d4ed8;display:inline-block;"></span> <?php echo L('profile_accent_blue'); ?>
                        </button>
                        <button type="button" id="themeAccentGreen" class="cat-tab" style="border-color:#16a34a" onclick="setThemeAccent('green'); document.getElementById('theme_accent_input').value='green';">
                            <span style="width:10px;height:10px;border-radius:999px;background:#16a34a;display:inline-block;"></span> <?php echo L('profile_accent_green'); ?>
                        </button>
                        <button type="button" id="themeAccentRed" class="cat-tab" style="border-color:#dc2626" onclick="setThemeAccent('red'); document.getElementById('theme_accent_input').value='red';">
                            <span style="width:10px;height:10px;border-radius:999px;background:#dc2626;display:inline-block;"></span> <?php echo L('profile_accent_red'); ?>
                        </button>
                        <button type="button" id="themeAccentPurple" class="cat-tab" style="border-color:#7c3aed" onclick="setThemeAccent('purple'); document.getElementById('theme_accent_input').value='purple';">
                            <span style="width:10px;height:10px;border-radius:999px;background:#7c3aed;display:inline-block;"></span> <?php echo L('profile_accent_purple'); ?>
                        </button>
                        <button type="button" id="themeAccentAmber" class="cat-tab" style="border-color:#d97706" onclick="setThemeAccent('amber'); document.getElementById('theme_accent_input').value='amber';">
                            <span style="width:10px;height:10px;border-radius:999px;background:#d97706;display:inline-block;"></span> <?php echo L('profile_accent_amber'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-group mb-0 mt-15">
                <label class="form-label fs-12"><?php echo L('profile_theme_font'); ?></label>
                <div class="d-flex gap-8 fw-wrap">
                    <button type="button" id="themeFontMono" class="cat-tab d-flex ai-center gap-8" style="font-family: 'DM Mono', monospace;" onclick="setThemeFont('dm-mono')">
                        <i class="fa-solid fa-font"></i> DM Mono
                    </button>
                    <button type="button" id="themeFontInter" class="cat-tab d-flex ai-center gap-8" style="font-family: 'Inter', sans-serif;" onclick="setThemeFont('inter')">
                        <i class="fa-solid fa-font"></i> Inter
                    </button>
                    <button type="button" id="themeFontOutfit" class="cat-tab d-flex ai-center gap-8" style="font-family: 'Outfit', sans-serif;" onclick="setThemeFont('outfit')">
                        <i class="fa-solid fa-font"></i> Outfit
                    </button>
                    <button type="button" id="themeFontRoboto" class="cat-tab d-flex ai-center gap-8" style="font-family: 'Roboto', sans-serif;" onclick="setThemeFont('roboto')">
                        <i class="fa-solid fa-font"></i> Roboto
                    </button>
                    <button type="button" id="themeFontSystem" class="cat-tab d-flex ai-center gap-8" style="font-family: system-ui, sans-serif;" onclick="setThemeFont('system')">
                        <i class="fa-solid fa-font"></i> System Sans
                    </button>
                </div>
            </div>

            <hr class="border-none border-top m-10-0">
            <div class="form-label"><?php echo L('profile_pass_title'); ?></div>
            <p class="fs-12 text-muted mt-neg-15"><?php echo L('profile_pass_sub'); ?></p>

            <div class="form-group-wrap gap-15 grid-3">
                <div class="form-group">
                    <label class="form-label"><?php echo L('profile_pass_current'); ?></label>
                    <input type="password" name="pass_actual" class="form-input" placeholder="••••••••">
                    <?php if (isset($avMiPerfil['aErrores']['pass_actual']) && $avMiPerfil['aErrores']['pass_actual'] != null) { ?>
                        <span class="form-error"><?php echo $avMiPerfil['aErrores']['pass_actual']; ?></span>
                    <?php } ?>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo L('profile_pass_new'); ?></label>
                    <input type="password" name="pass1" class="form-input" placeholder="••••••••">
                    <?php if (isset($avMiPerfil['aErrores']['pass1']) && $avMiPerfil['aErrores']['pass1'] != null) { ?>
                        <span class="form-error"><?php echo $avMiPerfil['aErrores']['pass1']; ?></span>
                    <?php } ?>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo L('profile_pass_repeat'); ?></label>
                    <input type="password" name="pass2" class="form-input" placeholder="••••••••">
                    <?php if (isset($avMiPerfil['aErrores']['pass2']) && $avMiPerfil['aErrores']['pass2'] != null) { ?>
                        <span class="form-error"><?php echo $avMiPerfil['aErrores']['pass2']; ?></span>
                    <?php } ?>
                </div>
            </div>

            <div class="modal-footer p-0 mt-10">
                <button type="submit" name="volver" class="btn-back">
                    <?php echo L('profile_btn_back'); ?>
                </button>
                <button type="submit" name="guardarCambios" class="btn-save flex-2">
                    <?php echo L('profile_btn_save'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function setThemeMode(mode) {
        document.body.dataset.themeMode = mode;
        localStorage.setItem('tpv_theme_mode', mode);
        updateActiveSelectors();
    }

    function setThemeAccent(accent) {
        document.body.dataset.themeAccent = accent;
        localStorage.setItem('tpv_theme_accent', accent);
        updateActiveSelectors();
    }

    function setThemeFont(font) {
        document.body.dataset.themeFont = font;
        localStorage.setItem('tpv_theme_font', font);
        const input = document.getElementById('theme_font_input');
        if (input) input.value = font;
        updateActiveSelectors();
    }

    function updateActiveSelectors() {
        const mode = document.body.dataset.themeMode || 'light';
        const accent = document.body.dataset.themeAccent || 'blue';
        const font = document.body.dataset.themeFont || 'dm-mono';
        
        const modeIds = {
            light: 'themeModeLight',
            dark: 'themeModeDark',
            black: 'themeModeBlack'
        };
        const accentIds = {
            blue: 'themeAccentBlue',
            green: 'themeAccentGreen',
            red: 'themeAccentRed',
            purple: 'themeAccentPurple',
            amber: 'themeAccentAmber'
        };
        const fontIds = {
            'dm-mono': 'themeFontMono',
            'inter': 'themeFontInter',
            'outfit': 'themeFontOutfit',
            'roboto': 'themeFontRoboto',
            'system': 'themeFontSystem'
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

        Object.values(fontIds).forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.remove('active');
        });
        if (fontIds[font] && document.getElementById(fontIds[font])) {
            document.getElementById(fontIds[font]).classList.add('active');
        }
    }

    (function initThemeSelectors() {
        updateActiveSelectors();
        const modeInput = document.getElementById('theme_mode_input');
        const accentInput = document.getElementById('theme_accent_input');
        const fontInput = document.getElementById('theme_font_input');
        if (modeInput) modeInput.value = document.body.dataset.themeMode || 'light';
        if (accentInput) accentInput.value = document.body.dataset.themeAccent || 'blue';
        if (fontInput) fontInput.value = document.body.dataset.themeFont || 'dm-mono';
    })();
</script>