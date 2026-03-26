</header>
<div class="main container-full d-flex ai-center jc-center min-h-80vh">
    <div class="modal modal-content auth-card w-400 p-40">
        <div class="topbar-brand-dot mb-24"></div>
        <div class="modal-title text-center fs-24 font-bold text-accent"><?php echo L('login_reset_title'); ?></div>
        <div class="modal-sub mb-32 text-center text-muted fs-14">
            <?php echo L('login_reset_sub'); ?>
        </div>

        <?php if (!$tokenValido): ?>
            <div class="p-20 mb-32 bg-red-light text-red br-12 border-1 border-red d-flex ai-center gap-12 anim-shake">
                <i class="fa-solid fa-triangle-exclamation fs-20"></i>
                <p class="m-0 fs-13"><?php echo $aErrores['general'] ?? L('login_reset_error_invalid', true); ?></p>
            </div>
            <div class="text-center">
                <a href="index.php?menu=RecuperarPassword" class="btn-save w-full p-14 text-decoration-none d-inline-block"><?php echo L('login_reset_btn_new_link'); ?></a>
            </div>
        <?php else: ?>
            <form method="post" action="index.php?menu=RecuperarPassword&token=<?php echo htmlspecialchars($_GET['token']); ?>" class="form-grid d-flex flex-column gap-24">
                
                <?php if (isset($aErrores['general'])): ?>
                    <div class="p-12 bg-red-light text-red br-8 fs-13 italic">
                        <i class="fa-solid fa-circle-exclamation mr-8"></i> <?php echo $aErrores['general']; ?>
                    </div>
                <?php endif; ?>

                <div class="form-group mb-0">
                    <label class="form-label fs-13 font-bold mb-8 text-accent"><?php echo L('login_label_new_pass'); ?></label>
                    <div class="search-input-wrap">
                        <i class="fa-solid fa-lock text-accent"></i>
                        <input type="password" name="password" class="form-input" style="padding-left: 44px !important;" placeholder="••••••••" required autofocus>
                    </div>
                    <?php if (isset($aErrores['password'])): ?>
                        <span class="form-error mt-8 d-block"><?php echo $aErrores['password']; ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group mb-0">
                    <label class="form-label fs-13 font-bold mb-8 text-accent"><?php echo L('login_label_confirm_pass'); ?></label>
                    <div class="search-input-wrap">
                        <i class="fa-solid fa-shield-halved text-accent"></i>
                        <input type="password" name="password_confirm" class="form-input" style="padding-left: 44px !important;" placeholder="••••••••" required>
                    </div>
                    <?php if (isset($aErrores['password_confirm'])): ?>
                        <span class="form-error mt-8 d-block"><?php echo $aErrores['password_confirm']; ?></span>
                    <?php endif; ?>
                </div>

                <div class="mt-8 d-flex flex-column gap-12">
                    <button type="submit" name="cambiarPassword" class="btn-save p-14 fs-14 fw-700 shadow-lg hover-translate-y">
                        <i class="fa-solid fa-rotate mr-10"></i> <?php echo L('login_btn_update_pass'); ?>
                    </button>
                    
                    <a href="index.php?menu=Login" class="btn-cancel border-none bg-none text-muted p-12 cursor-pointer text-center text-decoration-none fs-13 hover-text-accent transition-all">
                        <?php echo L('modal_cancel'); ?>
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
