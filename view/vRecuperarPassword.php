</header>
<div class="main container-full d-flex ai-center jc-center min-h-80vh">
    <div class="modal modal-content auth-card w-400 p-40 shadow-2xl">
        <div class="topbar-brand-dot mb-24 anim-pulse"></div>
        <div class="modal-title text-center fs-24 font-bold text-accent"><?php echo L('login_forgot_title'); ?></div>
        <div class="modal-sub mb-32 text-center text-muted fs-14">
            <?php echo L('login_forgot_sub'); ?>
        </div>

        <?php if ($showSuccess): ?>
            <div class="p-20 mb-32 bg-green-light text-green br-12 border-1 border-green d-flex ai-center gap-12 anim-fade-in">
                <i class="fa-solid fa-circle-check fs-20"></i>
                <p class="m-0 fs-13"><?php echo L('login_forgot_success_msg'); ?></p>
            </div>
            <div class="text-center">
                <a href="index.php?menu=Login" class="btn-save w-full p-14 text-decoration-none d-inline-block"><?php echo L('login_forgot_btn_back'); ?></a>
            </div>
        <?php else: ?>
            <form method="post" action="index.php?menu=RecuperarPassword" class="form-grid d-flex flex-column gap-24">
                <div class="form-group mb-0">
                    <label class="form-label fs-13 font-bold mb-8"><?php echo L('login_forgot_label_email'); ?></label>
                    <div class="search-input-wrap shadow-sm">
                        <i class="fa-solid fa-envelope text-accent"></i>
                        <input type="email" name="email" class="form-input" style="padding-left: 44px !important;" placeholder="ejemplo@empresa.com" required autofocus>
                    </div>
                    <?php if (isset($aErrores['email'])): ?>
                        <span class="form-error mt-8 d-block"><?php echo $aErrores['email']; ?></span>
                    <?php endif; ?>
                </div>

                <div class="d-flex flex-column gap-12 mt-8">
                    <button type="submit" name="enviarSolicitud" class="btn-save p-14 fs-14 shadow-lg hover-translate-y">
                        <i class="fa-solid fa-paper-plane mr-10"></i> <?php echo L('login_forgot_btn_send'); ?>
                    </button>
                    
                    <a href="index.php?menu=Login" class="btn-cancel border-none bg-none text-muted p-12 cursor-pointer text-center text-decoration-none fs-13 hover-text-accent transition-all">
                         <?php echo L('login_forgot_btn_cancel'); ?>
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
