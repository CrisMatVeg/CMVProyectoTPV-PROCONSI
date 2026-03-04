<div class="main-full p-24">
    <!-- CABECERA DE SECCIÓN -->
    <div class="section-header container">
        <div class="section-title">
            <div class="d-flex ai-center gap-12 mb-4">
                <i class="fa-solid fa-user-shield text-accent fs-32"></i>
                <h1 class="m-0 fs-28">Roles y Permisos</h1>
            </div>
            <p class="text-muted fs-14">Define las capacidades de cada perfil de usuario en el sistema.</p>
        </div>
        <form method="post">
            <button type="submit" name="volver" class="btn-icon w-auto h-auto gap-8 fs-14 p-10-20">
                <i class="fa-solid fa-house"></i> Dashboard
            </button>
        </form>
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
                <h3 class="m-0 fs-16"><i class="fa-solid fa-list-ul mr-8"></i> Roles Definidos</h3>
            </div>
            <table class="data-table mb-0">
                <thead>
                    <tr>
                        <th class="pl-20">Nombre del Rol</th>
                        <th>Descripción</th>
                        <th class="text-right pr-20">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($avRoles['roles'] as $r): ?>
                        <tr>
                            <td class="pl-20 font-bold"><?php echo htmlspecialchars($r['nombre']); ?></td>
                            <td class="text-muted fs-13"><?php echo htmlspecialchars($r['descripcion']); ?></td>
                            <td class="text-right pr-20">
                                <button class="btn-icon" onclick='cargarRolParaEditar(<?php echo json_encode($r); ?>)' title="Editar rol y permisos">
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
            <h3 class="m-0 fs-18 mb-20" id="formTitle"><i class="fa-solid fa-plus-circle text-accent mr-8"></i> Crear Nuevo Rol</h3>

            <form method="post" id="roleForm">
                <input type="hidden" name="Analitica" value=""> <!-- Fake para Router si fuera necesario -->
                <input type="hidden" name="accion" value="guardarRol">
                <input type="hidden" name="idRol" id="idRol" value="0">

                <div class="form-group mb-16">
                    <label class="fs-12 font-bold mb-8 d-block text-muted tt-uppercase">Nombre del Rol</label>
                    <input type="text" name="nombreRol" id="roleName" class="form-input" placeholder="Ej: Supervisor, Almacén..." required>
                </div>

                <div class="form-group mb-20">
                    <label class="fs-12 font-bold mb-8 d-block text-muted tt-uppercase">Descripción</label>
                    <textarea name="descRol" id="roleDesc" class="form-input" style="height: 80px; resize: none;" placeholder="Breve nota sobre qué personal usa este rol..."></textarea>
                </div>

                <div class="mb-12">
                    <label class="fs-12 font-bold mb-12 d-block text-muted tt-uppercase border-bottom pb-4"><i class="fa-solid fa-key mr-8"></i> Permisos Habilitados</label>
                    <div class="d-flex flex-column gap-12 mt-12" id="permissionsList">
                        <?php foreach ($avRoles['permisos'] as $p): ?>
                            <label class="checkbox-item d-flex ai-center gap-12 p-8 br-8 cursor-pointer hover-bg-surface">
                                <input type="checkbox" name="permisos[]" value="<?php echo $p['id']; ?>" class="perm-check" data-clave="<?php echo $p['clave']; ?>">
                                <div>
                                    <div class="fs-14 font-bold"><?php echo htmlspecialchars($p['descripcion']); ?></div>
                                    <div class="fs-11 text-muted">Clave: <?php echo $p['clave']; ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="d-flex gap-12 mt-32 border-top pt-20">
                    <button type="submit" class="btn-filter flex-1 jc-center">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Configuración
                    </button>
                    <button type="button" class="btn-cancel w-auto" onclick="limpiarForm()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    async function cargarRolParaEditar(rol) {
        document.getElementById('formTitle').innerHTML = '<i class="fa-solid fa-shield-halved text-accent mr-8"></i> Editar Rol: ' + rol.nombre;
        document.getElementById('idRol').value = rol.id;
        document.getElementById('roleName').value = rol.nombre;
        document.getElementById('roleDesc').value = rol.descripcion || '';

        // Resetear checkboxes
        const checks = document.querySelectorAll('.perm-check');
        checks.forEach(c => c.checked = false);

        // Cargar permisos del rol vía API (o inyectados si pesaran poco)
        try {
            const resp = await fetch('api/rolPermisos.php?idRol=' + rol.id);
            const data = await resp.json();
            if (data.ok && data.claves) {
                checks.forEach(c => {
                    if (data.claves.includes(c.dataset.clave)) {
                        c.checked = true;
                    }
                });
            }
        } catch (e) {
            console.error("Error cargando permisos del rol", e);
        }
    }

    function limpiarForm() {
        document.getElementById('formTitle').innerHTML = '<i class="fa-solid fa-plus-circle text-accent mr-8"></i> Crear Nuevo Rol';
        document.getElementById('idRol').value = "0";
        document.getElementById('roleForm').reset();
    }
</script>

<style>
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