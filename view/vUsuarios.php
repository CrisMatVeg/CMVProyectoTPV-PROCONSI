</header>
<div class="main-full p-24">
    <!-- Encabezado de la sección -->
    <div class="section-header container-wider">
        <div class="section-title">
            <h1>Gestión de Personal</h1>
            <p>Administra los accesos y roles de los empleados</p>
        </div>
        <!-- Acciones se gestionan desde la topbar o el administrador de abajo -->
    </div>

    <!-- Barra de Búsqueda y Acción -->
    <div class="filters-panel container-wider">
        <div class="search-input-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="userSearch" class="search-input" placeholder="Escribe para buscar por nombre de usuario...">
        </div>
        <div class="d-flex gap-12">
            <button onclick="window.location.href='index.php?irRoles'" class="btn-filter w-auto h-auto gap-8 fs-14 p-10-20 shadow-none" style="background:var(--surface2); color:var(--text-muted); border-color:var(--surface3);">
                <i class="fa-solid fa-user-shield"></i>
                Roles y Permisos
            </button>
            <button onclick="document.getElementById('modalAddUser').classList.add('visible')" class="btn-add">
                <i class="fa-solid fa-user-plus"></i>
                Nuevo Usuario
            </button>
        </div>
    </div>

    <!-- Tabla de Usuarios -->
    <div class="table-container container-wider">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nombre Completo</th>
                    <th>Usuario</th>
                    <th>Rol Actual</th>
                    <th>Estado</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <?php foreach ($listaUsuarios as $u): ?>
                    <tr class="user-row">
                        <td><?php echo $u->getNombreCompleto(); ?></td>
                        <td class="username-cell font-mono text-muted"><?php echo $u->getUsername(); ?></td>
                        <td>
                            <span class="status-pill <?php echo strtolower($u->getRol()) === 'admin' ? 'status-active' : 'bg-surface2 text-muted'; ?> p-4-12 tt-uppercase fs-10 font-bold border-none">
                                <i class="fa-solid <?php echo strtolower($u->getRol()) === 'admin' ? 'fa-shield-halved' : 'fa-user'; ?> mr-4"></i>
                                <?php echo $u->getRol(); ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-pill <?php echo $u->getActivo() ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $u->getActivo() ? 'Activo' : 'Baja (Inactivo)'; ?>
                            </span>
                        </td>
                        <td class="text-right">
                            <div class="d-flex gap-8 jc-flex-end">
                                <!-- Botón Cambiar Rol (Solo si no es el usuario actual) -->
                                <?php if ($u->getId() !== $_SESSION['usuarioActualTPV']->getId()): ?>
                                    <form method="post" class="d-inline d-flex ai-center gap-4">
                                        <input type="hidden" name="idUsuario" value="<?php echo $u->getId(); ?>">
                                        <select name="idRol" class="form-input p-4-8 fs-11 w-auto br-8" onchange="this.form.submit()">
                                            <option value="" disabled selected>Cambiar Rol...</option>
                                            <?php foreach ($listaRoles as $r): ?>
                                                <option value="<?php echo $r['id']; ?>" <?php echo $u->getIdRol() == $r['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($r['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="cambiarRol" value="1">
                                    </form>
                                <?php endif; ?>
                                <!-- Botón Dar Baja/Alta (Solo si no es el usuario actual) -->
                                <?php if ($u->getId() !== $_SESSION['usuarioActualTPV']->getId()): ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="idUsuario" value="<?php echo $u->getId(); ?>">
                                        <button type="submit" name="toggleEstado"
                                            class="status-pill <?php echo $u->getActivo() ? 'status-inactive' : 'status-active'; ?> border-none cursor-pointer gap-6 font-bold">
                                            <i class="fa-solid <?php echo $u->getActivo() ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                            <?php echo $u->getActivo() ? 'Baja' : 'Reactivar'; ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="status-pill bg-blue-light text-accent border-none gap-6 font-bold">
                                        <i class="fa-solid fa-user-check"></i>
                                        Eres tú
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
                            No se han encontrado usuarios que coincidan con la búsqueda.
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- MODAL AÑADIR USUARIO -->
    <div class="modal-overlay <?php echo $showModal ? 'visible' : ''; ?>" id="modalAddUser">
        <div class="modal modal-content w-400 gap-20">
            <div class="modal-header">
                <h2 class="modal-title fs-18">Nuevo Empleado</h2>
                <button onclick="document.getElementById('modalAddUser').classList.remove('visible')" class="btn-close-modal">×</button>
            </div>
            <form method="post" class="form-grid gap-15" novalidate>
                <div class="form-group">
                    <label class="form-label">NOMBRE COMPLETO</label>
                    <input type="text" name="nombre" class="form-input" placeholder="Ej: Juan Pérez" value="<?php echo $_REQUEST['nombre'] ?? ''; ?>">
                    <?php if (isset($aErrores['nombre']) && $aErrores['nombre'] != null) { ?>
                        <span class="form-error"><?php echo $aErrores['nombre']; ?></span>
                    <?php } ?>
                </div>
                <div class="form-group">
                    <label class="form-label">NOMBRE DE USUARIO</label>
                    <input type="text" name="username" class="form-input font-mono" placeholder="ej: jperez" value="<?php echo $_REQUEST['username'] ?? ''; ?>">
                    <?php if (isset($aErrores['username']) && $aErrores['username'] != null) { ?>
                        <span class="form-error"><?php echo $aErrores['username']; ?></span>
                    <?php } ?>
                </div>
                <div class="form-group">
                    <label class="form-label">CONTRASEÑA TEMPORAL</label>
                    <input type="password" name="password" class="form-input" placeholder="••••••••">
                    <?php if (isset($aErrores['password']) && $aErrores['password'] != null) { ?>
                        <span class="form-error"><?php echo $aErrores['password']; ?></span>
                    <?php } ?>
                </div>
                <div class="form-group">
                    <label class="form-label">ROL INICIAL</label>
                    <select name="idRol" class="form-input">
                        <?php foreach ($listaRoles as $r): ?>
                            <option value="<?php echo $r['id']; ?>" <?php echo (isset($_REQUEST['idRol']) && $_REQUEST['idRol'] == $r['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($r['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-footer p-0 mt-10">
                    <button type="submit" name="addUsuario" class="btn-save full-width">
                        Crear Cuenta de Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
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