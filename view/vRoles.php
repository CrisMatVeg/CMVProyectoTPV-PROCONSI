<div class="main-full p-24">
    <!-- CABECERA DE SECCIÓN -->
    <div class="section-header container">
        <div class="section-title">
            <div class="d-flex ai-center gap-12 mb-4">
                <i class="fa-solid fa-user-shield text-accent fs-32"></i>
                <h1 class="m-0 fs-28"><?php echo L('roles_title'); ?></h1>
            </div>
            <p class="text-muted fs-14"><?php echo L('roles_subtitle'); ?></p>
        </div>
        <div class="d-flex gap-12 ai-center">
            <a href="index.php?irDashboard=1" class="btn-back">
                <i class="fa-solid fa-house"></i> <?php echo L('roles_btn_dashboard'); ?>
            </a>
            <a href="index.php?irUsuarios=1" class="btn-back">
                <i class="fa-solid fa-users"></i> <?php echo L('login_back'); ?>
            </a>
        </div>
    </div>

    <?php if ($avRoles['mensajeOk']): ?>
        <div class="container mb-16">
            <div class="status-pill status-active w-full p-16 jc-start gap-12 br-12">
                <i class="fa-solid fa-circle-check fs-18"></i>
                <span><?php echo $avRoles['mensajeOk']; ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($avRoles['error']): ?>
        <div class="container mb-16">
            <div class="status-pill w-full p-16 jc-start gap-12 br-12" style="background:var(--red-light); color:var(--red); border-color:var(--red);">
                <i class="fa-solid fa-circle-exclamation fs-18"></i>
                <span><?php echo $avRoles['error']; ?></span>
            </div>
        </div>
    <?php endif; ?>

    <div class="container d-grid grid-2-1 gap-32">
        <!-- LISTADO DE ROLES -->
        <div class="card p-0 br-20 bg-white shadow-sm border-2 overflow-hidden">
            <div class="p-20 border-bottom bg-surface">
                <h3 class="m-0 fs-16"><i class="fa-solid fa-list-ul mr-8"></i> <?php echo L('roles_card_list'); ?></h3>
            </div>
            <table class="data-table mb-0">
                <thead>
                    <tr>
                        <th class="pl-20"><?php echo L('roles_th_name'); ?></th>
                        <th><?php echo L('roles_th_desc'); ?></th>
                        <th class="text-right pr-20"><?php echo L('roles_th_actions'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($avRoles['roles'] as $r): ?>
                        <tr>
                            <td class="pl-20 font-bold"><?php echo L('role_' . strtolower($r['nombre'])); ?></td>
                            <td class="text-muted fs-13"><?php echo L('role_' . strtolower($r['nombre']) . '_desc'); ?></td>
                            <td class="text-right pr-20">
                                <button class="btn-icon" onclick='cargarRolParaEditar(<?php echo htmlspecialchars(json_encode($r), ENT_QUOTES, "UTF-8"); ?>)' title="<?php echo L('roles_tip_edit'); ?>">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- FORMULARIO DE GESTIÓN -->
        <div class="card p-24 br-20 bg-white shadow-sm border-2">
            <h3 class="m-0 fs-18 mb-20" id="formTitle"><i class="fa-solid fa-plus-circle text-accent mr-8"></i> <?php echo L('roles_card_manage'); ?></h3>

            <form method="post" id="roleForm">
                <input type="hidden" name="Analitica" value=""> <!-- Fake para Router si fuera necesario -->
                <input type="hidden" name="accion" value="guardarRol">
                <input type="hidden" name="idRol" id="idRol" value="0">

                <div class="form-group">
                    <label class="fs-12 font-bold mb-8 d-block text-muted tt-uppercase"><?php echo L('roles_label_name'); ?></label>
                    <input type="text" name="nombreRol" id="roleName" class="form-input" placeholder="<?php echo L('roles_name_placeholder'); ?>" required>
                </div>
 
                <div class="form-group">
                    <label class="fs-12 font-bold mb-8 d-block text-muted tt-uppercase"><?php echo L('roles_th_desc'); ?></label>
                    <textarea name="descRol" id="roleDesc" class="form-input" style="height: 80px; resize: none;" placeholder="<?php echo L('roles_desc_placeholder'); ?>"></textarea>
                </div>

                <div class="mb-12">
                    <label class="fs-12 font-bold mb-12 d-block text-muted tt-uppercase border-bottom pb-4"><i class="fa-solid fa-key mr-8"></i> <?php echo L('roles_label_perms'); ?></label>
                    <div class="d-flex flex-column gap-12 mt-12" id="permissionsList">
                        <?php foreach ($avRoles['permisos'] as $p): ?>
                            <label class="checkbox-item d-flex ai-center gap-12 p-8 br-8 cursor-pointer hover-bg-surface">
                                <input type="checkbox" name="permisos[]" value="<?php echo $p['id']; ?>" class="perm-check" data-clave="<?php echo $p['clave']; ?>">
                                <div>
                                    <div class="fs-14 font-bold"><?php echo L('perm_' . strtolower($p['clave'])); ?></div>
                                    <div class="fs-11 text-muted"><?php echo L('roles_perm_key'); ?>: <?php echo $p['clave']; ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="modal-footer p-0 mt-32 border-top pt-20 d-flex gap-12">
                    <button type="button" class="btn-cancel flex-1" onclick="limpiarForm()"><?php echo L('modal_cancel'); ?></button>
                    <button type="submit" class="btn-save flex-2 jc-center">
                        <i class="fa-solid fa-floppy-disk mr-8"></i> <?php echo L('roles_btn_save'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let lastRequestId = 0;

    async function cargarRolParaEditar(rol) {
        const requestId = ++lastRequestId;
        const roleNames = {
            'admin': "<?php echo L('role_admin'); ?>",
            'cajero': "<?php echo L('role_cajero'); ?>"
        };
        const editLabel = "<?php echo L('roles_js_edit_title'); ?>";
        const localizedName = roleNames[rol.nombre.toLowerCase()] || rol.nombre;
        document.getElementById('formTitle').innerHTML = '<i class="fa-solid fa-shield-halved text-accent mr-8"></i> ' + editLabel + ': ' + localizedName;
        document.getElementById('idRol').value = rol.id;
        document.getElementById('roleName').value = rol.nombre;
        document.getElementById('roleDesc').value = rol.descripcion || '';

        // Resetear checkboxes inmediatamente
        const checks = document.querySelectorAll('.perm-check');
        checks.forEach(c => c.checked = false);

        // Cargar permisos del rol vía API
        try {
            const resp = await fetch('api/rolPermisos.php?idRol=' + rol.id);
            const data = await resp.json();

            // Solo aplicar si esta sigue siendo la solicitud más reciente
            if (requestId === lastRequestId && data.ok && data.claves) {
                checks.forEach(c => {
                    if (data.claves.includes(c.dataset.clave)) {
                        c.checked = true;
                    }
                });
            } else {
            }
        } catch (e) {
            console.error("Error cargando permisos del rol", e);
        }
    }

    function limpiarForm() {
        lastRequestId++; // Cancelar cualquier petición de carga en curso
        const newLabel = "<?php echo L('roles_card_manage'); ?>";
        document.getElementById('formTitle').innerHTML = '<i class="fa-solid fa-plus-circle text-accent mr-8"></i> ' + newLabel;
        document.getElementById('idRol').value = "0";
        document.getElementById('roleForm').reset();

        const checks = document.querySelectorAll('.perm-check');
        checks.forEach(c => c.checked = false);
    }
</script>

<style>
    .section-header a {
        text-decoration: none !important;
        transition: all 0.2s ease;
    }

    .section-header a:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow);
        background: var(--surface2);
    }

    .checkbox-item:hover {
        background: var(--surface);
    }

    .hover-bg-surface:hover {
        background: var(--surface2);
    }

    input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: var(--accent);
    }
</style>