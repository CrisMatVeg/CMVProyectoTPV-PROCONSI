</header>
<div class="main-full p-24">
    <!-- Encabezado de la sección -->
    <div class="section-header container-wider">
        <div class="section-title">
            <h1><?php echo L('dashboard_btn_users'); ?></h1>
            <p><?php echo L('dashboard_btn_users_sub'); ?></p>
        </div>
        <div class="d-flex gap-12 ai-center">
            <a href="index.php?irDashboard=1" class="btn-back">
                <?php echo L('login_back'); ?>
            </a>
        </div>
    </div>

    <!-- Barra de Búsqueda y Acción -->
    <div class="filters-bar-new container-wider mb-24 br-20 shadow-sm" style="background: var(--surface2); padding: 16px; border: 1px solid var(--border);">
        <div class="filter-item flex-1">
            <div class="search-input-fancy" style="border-radius: 12px; background: var(--surface); padding-left: 15px;">
                <i class="fa-solid fa-magnifying-glass opacity-50"></i>
                <input type="text" id="userSearch" placeholder="<?php echo L('user_search_placeholder'); ?>" style="height: 48px; border: none; background: transparent; width: 100%; padding-left: 10px;">
            </div>
        </div>
        
        <div class="filter-item">
            <button onclick="window.location.href='index.php?irRoles'" class="btn-filter-new secondary h-48 br-12 px-20">
                <i class="fa-solid fa-user-shield"></i>
                <span><?php echo L('user_btn_roles'); ?></span>
            </button>
        </div>

        <div class="filter-item">
            <button onclick="document.getElementById('modalAddUser').classList.add('visible')" class="btn-filter-new primary h-48 br-12 px-20">
                <i class="fa-solid fa-user-plus"></i>
                <span><?php echo L('user_btn_new'); ?></span>
            </button>
        </div>
    </div>

    <!-- Tabla de Usuarios -->
    <div class="table-container container-wider">
        <table class="data-table">
            <thead>
                <tr>
                    <th><?php echo L('user_th_name'); ?></th>
                    <th><?php echo L('login_user'); ?></th>
                    <th><?php echo L('user_th_email'); ?></th>
                    <th><?php echo L('user_th_rol'); ?></th>
                    <th><?php echo L('prod_th_status'); ?></th>
                    <th class="text-right"><?php echo L('prod_th_actions'); ?></th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <?php foreach ($listaUsuarios as $u): ?>
                    <tr class="user-row">
                        <td><?php echo $u->getNombreCompleto(); ?></td>
                        <td class="username-cell font-mono text-muted"><?php echo $u->getUsername(); ?></td>
                        <td class="email-cell fs-12 text-muted italic"><?php echo $u->getEmail() ?? '<span class="text-red-light">'.L('user_no_email', true).'</span>'; ?></td>
                        <td>
                            <span class="status-pill <?php echo strtolower($u->getRol()) === 'admin' ? 'status-active' : 'bg-surface2 text-muted'; ?> p-4-12 tt-uppercase fs-10 font-bold border-none">
                                <i class="fa-solid <?php echo strtolower($u->getRol()) === 'admin' ? 'fa-shield-halved' : 'fa-user'; ?> mr-4"></i>
                                <?php echo L('role_' . strtolower($u->getRol())); ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-pill <?php echo $u->getActivo() ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $u->getActivo() ? L('user_status_active', true) : L('user_status_inactive', true); ?>
                            </span>
                        </td>
                        <td class="text-right">
                            <div class="d-flex gap-8 jc-end ai-center">
                                <!-- Botón Editar (Nombre/Email) -->
                                <button onclick="abrirModalEditar(<?php echo $u->getId(); ?>, <?php echo htmlspecialchars(json_encode($u->getNombre())); ?>, <?php echo htmlspecialchars(json_encode($u->getEmail())); ?>)" 
                                        class="btn-icon" title="<?php echo L('user_tip_edit'); ?>">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                
                                <!-- Botón Cambiar Rol (Solo si no es el usuario actual) -->
                                <?php if ($u->getId() !== $_SESSION['usuarioActualTPV']->getId()): ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="idUsuario" value="<?php echo $u->getId(); ?>">
                                        <input type="hidden" name="cambiarRol" value="1">
                                        <select name="idRol" class="filter-control w-auto h-38 px-8 fs-12 br-10" onchange="this.form.submit()">
                                            <option value="" disabled selected><?php echo L('user_select_rol'); ?></option>
                                            <?php foreach ($listaRoles as $r): ?>
                                                <option value="<?php echo $r['id']; ?>" <?php echo $u->getIdRol() == $r['id'] ? 'selected' : ''; ?>>
                                                    <?php echo L('role_' . strtolower($r['nombre'])); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                <?php endif; ?>

                                <!-- Botón Dar Baja/Alta (Solo si no es el usuario actual) -->
                                <?php if ($u->getId() !== $_SESSION['usuarioActualTPV']->getId()): ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="idUsuario" value="<?php echo $u->getId(); ?>">
                                        <button type="submit" name="toggleEstado"
                                            class="status-pill <?php echo $u->getActivo() ? 'status-inactive' : 'status-active'; ?> border-none cursor-pointer gap-6 font-bold h-38 px-16">
                                            <i class="fa-solid <?php echo $u->getActivo() ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                            <?php echo $u->getActivo() ? L('user_btn_deactivate', true) : L('user_btn_activate', true); ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="status-pill bg-blue-light text-accent border-none gap-6 font-bold h-38 px-16">
                                        <i class="fa-solid fa-user-check"></i>
                                        <?php echo L('user_is_you'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr id="noResults" class="d-none">
                    <td colspan="5">
                        <div class="empty-state">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <?php echo L('user_no_results'); ?>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- MODAL AÑADIR USUARIO -->
    <div class="modal-overlay <?php echo $showModal ? 'visible' : ''; ?>" id="modalAddUser">
        <div class="modal modal-content w-modal-md gap-20" style="max-width: 600px; border-radius: 20px; overflow: hidden; border: 1px solid var(--border); box-shadow: var(--shadow-lg);">
            <div class="modal-header">
                <h2 class="modal-title fs-18"><?php echo L('user_modal_new_title'); ?></h2>
                <button onclick="cerrarModalUsuario()" class="btn-close-modal">×</button>
            </div>
            <form method="post" class="form-grid gap-15" novalidate>
                <div class="form-group">
                    <label class="form-label"><?php echo L('user_label_name'); ?></label>
                    <input type="text" name="nombre" class="form-input" placeholder="<?php echo L('user_name_placeholder'); ?>" value="<?php echo $_REQUEST['nombre'] ?? ''; ?>">
                    <?php if (isset($aErrores['nombre']) && $aErrores['nombre'] != null) { ?>
                        <span class="form-error"><?php echo $aErrores['nombre']; ?></span>
                    <?php } ?>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo mb_strtoupper(L('login_user', true)); ?></label>
                    <input type="text" name="username" class="form-input font-mono" placeholder="<?php echo L('user_username_placeholder'); ?>" value="<?php echo $_REQUEST['username'] ?? ''; ?>">
                    <?php if (isset($aErrores['username']) && $aErrores['username'] != null) { ?>
                        <span class="form-error"><?php echo $aErrores['username']; ?></span>
                    <?php } ?>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo L('user_label_pass'); ?></label>
                    <input type="password" name="password" class="form-input" placeholder="<?php echo L('password_placeholder'); ?>">
                    <?php if (isset($aErrores['password']) && $aErrores['password'] != null) { ?>
                        <span class="form-error"><?php echo $aErrores['password']; ?></span>
                    <?php } ?>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo L('user_label_email'); ?></label>
                    <input type="email" name="email" class="form-input" placeholder="<?php echo L('user_email_placeholder'); ?>" value="<?php echo $_REQUEST['email'] ?? ''; ?>">
                    <?php if (isset($aErrores['email']) && $aErrores['email'] != null) { ?>
                        <span class="form-error"><?php echo $aErrores['email']; ?></span>
                    <?php } ?>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo L('user_label_rol'); ?></label>
                    <select name="idRol" class="form-input">
                        <?php foreach ($listaRoles as $r): ?>
                            <option value="<?php echo $r['id']; ?>" <?php echo (isset($_REQUEST['idRol']) && $_REQUEST['idRol'] == $r['id']) ? 'selected' : ''; ?>>
                                <?php echo L('role_' . strtolower($r['nombre'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-footer p-0 mt-10 d-flex gap-12">
                    <button type="button" onclick="cerrarModalUsuario()" class="btn-cancel flex-1">
                        <?php echo L('modal_cancel'); ?>
                    </button>
                    <button type="submit" name="addUsuario" class="btn-save flex-2">
                        <?php echo L('user_btn_create'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDITAR USUARIO -->
    <div class="modal-overlay" id="modalEditUser">
        <div class="modal modal-content w-modal-md gap-20" style="max-width: 500px; border-radius: 20px;">
            <div class="modal-header">
                <h2 class="modal-title fs-18"><?php echo L('user_modal_edit_title'); ?></h2>
                <button onclick="cerrarModalEditar()" class="btn-close-modal">×</button>
            </div>
            <form method="post" action="index.php?irUsuarios=1" class="form-grid gap-20">
                <input type="hidden" name="idUsuario" id="edit_idUsuario">
                <div class="form-group">
                    <label class="form-label font-bold text-accent fs-11"><?php echo L('user_label_name'); ?></label>
                    <input type="text" name="nombre_edit" id="edit_nombre" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label font-bold text-accent fs-11"><?php echo L('user_label_email'); ?></label>
                    <input type="email" name="email_edit" id="edit_email" class="form-input" placeholder="<?php echo L('user_email_placeholder'); ?>">
                    <p class="fs-11 text-muted mt-4 italic"><?php echo L('user_email_hint'); ?></p>
                </div>
                <div class="modal-footer p-0 mt-10 d-flex gap-12">
                    <button type="button" onclick="cerrarModalEditar()" class="btn-cancel flex-1"><?php echo L('modal_cancel'); ?></button>
                    <button type="submit" name="editUsuario" class="btn-save flex-2"><?php echo L('modal_save'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function abrirModalEditar(id, nombre, email) {
        document.getElementById('edit_idUsuario').value = id;
        document.getElementById('edit_nombre').value = nombre;
        document.getElementById('edit_email').value = email || '';
        document.getElementById('modalEditUser').classList.add('visible');
    }

    function cerrarModalEditar() {
        document.getElementById('modalEditUser').classList.remove('visible');
    }

    function cerrarModalUsuario() {
        const modal = document.getElementById('modalAddUser');
        modal.classList.remove('visible');
        // Limpiar formulario para que al abrirlo de nuevo esté vacío
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
            // Limpiar errores si los hubiera (visualmente)
            form.querySelectorAll('.form-error').forEach(e => e.remove());
        }
    }

    document.getElementById('userSearch').addEventListener('input', function(e) {
        const term = e.target.value.toLowerCase().trim();
        const rows = document.querySelectorAll('.user-row');
        const noResults = document.getElementById('noResults');
        let hasResults = false;

        rows.forEach(row => {
            const username = row.querySelector('.username-cell').textContent.toLowerCase();
            if (username.includes(term)) {
                row.style.display = '';
                hasResults = true;
            } else {
                row.style.display = 'none';
            }
        });

        noResults.classList.toggle('d-none', hasResults);
    });
</script>