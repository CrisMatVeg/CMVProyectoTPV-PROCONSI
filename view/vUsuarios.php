</header>
<div class="main-full p-24">
    <!-- Encabezado de la sección -->
    <div class="section-header container">
        <div class="section-title">
            <h1>Gestión de Personal</h1>
            <p>Administra los accesos y roles de los empleados</p>
        </div>
        <form method="post">
            <button type="submit" name="volver" class="btn-icon w-auto h-auto gap-8 fs-14 p-10-20">
                <i class="fa-solid fa-arrow-left"></i>
                Volver al Dashboard
            </button>
        </form>
    </div>

    <!-- Barra de Búsqueda y Acción -->
    <div class="filters-panel container">
        <div class="search-input-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="userSearch" class="search-input" placeholder="Escribe para buscar por nombre de usuario...">
        </div>
        <button onclick="document.getElementById('modalAddUser').classList.add('visible')" class="btn-add">
            <i class="fa-solid fa-user-plus"></i>
            Nuevo Usuario
        </button>
    </div>

    <!-- Tabla de Usuarios -->
    <div class="table-container container">
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
                        <span class="status-pill <?php echo $u->getRol()==='admin' ? 'status-active' : ''; ?> p-0 tt-uppercase">
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
                            <!-- Botón Cambiar Rol -->
                            <form method="post" class="d-inline">
                                <input type="hidden" name="idUsuario" value="<?php echo $u->getId(); ?>">
                                <input type="hidden" name="nuevoRol" value="<?php echo $u->getRol()==='admin' ? 'cajero' : 'admin'; ?>">
                                <button type="submit" name="cambiarRol" title="Cambiar Rol" class="btn-icon">
                                    <i class="fa-solid fa-arrows-rotate"></i>
                                </button>
                            </form>
                            <!-- Botón Dar Baja/Alta -->
                            <form method="post" class="d-inline">
                                <input type="hidden" name="idUsuario" value="<?php echo $u->getId(); ?>">
                                <button type="submit" name="toggleEstado" 
                                    class="status-pill <?php echo $u->getActivo() ? 'status-inactive' : 'status-active'; ?> border-none cursor-pointer gap-6 font-bold">
                                    <i class="fa-solid <?php echo $u->getActivo() ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                    <?php echo $u->getActivo() ? 'Baja' : 'Reactivar'; ?>
                                </button>
                            </form>
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
    <div class="modal-overlay" id="modalAddUser">
        <div class="modal modal-content w-400 gap-20">
            <div class="modal-header">
                <h2 class="modal-title fs-18">Nuevo Empleado</h2>
                <button onclick="document.getElementById('modalAddUser').classList.remove('visible')" class="btn-close-modal">×</button>
            </div>
            <form method="post" class="form-grid gap-15">
                <div class="form-group">
                    <label class="form-label">NOMBRE COMPLETO</label>
                    <input type="text" name="nombre" class="form-input" placeholder="Ej: Juan Pérez" required>
                </div>
                <div class="form-group">
                    <label class="form-label">NOMBRE DE USUARIO</label>
                    <input type="text" name="username" class="form-input font-mono" placeholder="ej: jperez" required>
                </div>
                <div class="form-group">
                    <label class="form-label">CONTRASEÑA TEMPORAL</label>
                    <input type="password" name="password" class="form-input" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label class="form-label">ROL INICIAL</label>
                    <select name="rol" class="form-input">
                        <option value="cajero">Cajero</option>
                        <option value="admin">Administrador</option>
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
