</header>
<div class="main container-full d-flex ai-center jc-center min-h-80vh">
    <div class="modal modal-content auth-card w-400 p-40">
        <div class="topbar-brand-dot mb-24"></div>
        <div class="modal-title text-center"><?php echo L('login_ident'); ?></div>
        <div class="modal-sub mb-24 text-center"><?php echo L('login_sub'); ?></div>

        <?php if (isset($_SESSION['pass_changed']) && $_SESSION['pass_changed']): ?>
            <div class="p-16 mb-24 bg-green-light text-green br-12 border-1 border-green d-flex ai-center gap-12 anim-fade-in shadow-sm">
                <i class="fa-solid fa-circle-check fs-18"></i>
                <p class="m-0 fs-13 font-bold"><?php echo L('login_msg_pass_updated'); ?></p>
            </div>
            <?php unset($_SESSION['pass_changed']); ?>
        <?php endif; ?>

        <form method="post" action="index.php" class="form-grid d-flex flex-column gap-20" novalidate>
            <div class="form-group">
                <label class="form-label"><?php echo L('login_user'); ?></label>
                <input type="text" name="username" class="form-input font-mono" placeholder="username" value="<?php echo $aRespuestas['username'] ?? ''; ?>">
                <?php if (isset($aErrores['username']) && $aErrores['username'] != null) { ?>
                    <span class="form-error"><?php echo $aErrores['username']; ?></span>
                <?php } ?>
            </div>

            <div class="form-group">
                <div class="d-flex ai-center jc-space-between mb-8">
                    <label class="form-label mb-0"><?php echo L('login_pass'); ?></label>
                    <a href="index.php?menu=RecuperarPassword" class="text-accent fs-12 text-decoration-none hover-underline"><?php echo L('login_forgot'); ?></a>
                </div>
                <input type="password" name="password" class="form-input" placeholder="••••••••">
                <?php if (isset($aErrores['password']) && $aErrores['password'] != null) { ?>
                    <span class="form-error"><?php echo $aErrores['password']; ?></span>
                <?php } ?>
            </div>

            <button type="submit" name="acceder" class="btn-save mt-10 p-14 as-center">
                <?php echo L('login_btn'); ?>
            </button>
        </form>

        <form method="post" action="index.php" class="mt-30 text-center">
            <input type="submit" name="atras" value="<?php echo L('login_back'); ?>" class="btn-cancel border-none bg-none text-muted p-0 cursor-pointer">
        </form>
    </div>
</div>