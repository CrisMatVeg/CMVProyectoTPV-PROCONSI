</header>
<div class="main-full p-24">
    
    <!-- CABECERA DE SECCIÓN -->
    <div class="section-header container">
        <div class="section-title">
            <h1>Gestión de Productos</h1>
            <p>Administra el catálogo de productos, precios y categorías.</p>
        </div>
        <div class="d-flex gap-12">
            <button onclick="abrirModalProducto()" class="btn-add">
                <i class="fa-solid fa-plus"></i> Nuevo Producto
            </button>
            <form method="post">
                <button type="submit" name="irDashboard" class="btn-icon w-auto h-auto gap-8 fs-14 p-10-20">
                    <i class="fa-solid fa-house"></i> Volver al Inicio
                </button>
            </form>
        </div>
    </div>

    <!-- TABLA DE PRODUCTOS -->
    <div class="table-container container">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-60 pl-20">ID</th>
                    <th class="w-80 text-center">Icono</th>
                    <th>Producto</th>
                    <th>Código</th>
                    <th>Categoría</th>
                    <th class="text-right">Stock</th>
                    <th class="text-right">Precio</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center pr-20">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($avProductos['productos'] as $p): ?>
                <tr>
                    <td class="font-mono text-muted"><?php echo $p['id']; ?></td>
                    <td class="text-center fs-24"><?php echo $p['icono']; ?></td>
                    <td class="font-bold"><?php echo htmlspecialchars($p['nombre']); ?></td>
                    <td class="text-muted"><?php echo htmlspecialchars($p['codigo']); ?></td>
                    <td>
                        <span class="cat-pill">
                            <?php echo htmlspecialchars($p['categoria']); ?>
                        </span>
                    </td>
                    <td class="text-right font-bold font-mono <?php echo $p['stock'] <= 5 ? 'text-red' : ''; ?>">
                        <?php echo $p['stock']; ?>
                    </td>
                    <td class="text-right font-bold font-mono">
                        <?php echo number_format($p['precio'], 2, ',', '.'); ?> €
                    </td>
                    <td class="text-center">
                        <?php if ($p['activo']): ?>
                            <span class="status-pill status-active">
                                <i class="fa-solid fa-circle-check"></i> Activo
                            </span>
                        <?php else: ?>
                            <span class="status-pill status-inactive">
                                <i class="fa-solid fa-circle-xmark"></i> Inactivo
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="d-flex jc-center gap-8 pr-20">
                            <button onclick='abrirModalProducto(<?php echo json_encode($p); ?>)' title="Editar" class="btn-icon">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button onclick="toggleEstadoProducto(<?php echo $p['id']; ?>)" title="<?php echo $p['activo'] ? 'Dar de baja' : 'Activar'; ?>" class="btn-icon <?php echo $p['activo'] ? 'text-red' : 'text-green'; ?>">
                                <i class="fa-solid fa-<?php echo $p['activo'] ? 'arrow-down' : 'arrow-up'; ?>"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- MODAL PARA AÑADIR/EDITAR PRODUCTO -->
<div id="modalProducto" class="modal-overlay-bg">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Nuevo Producto</h2>
            <button onclick="cerrarModalProducto()" class="btn-close-modal">&times;</button>
        </div>

        <form id="formProducto" class="form-grid">
            <input type="hidden" id="prodId">
            
            <div class="form-group-wrap grid-1-2">
                <div class="form-group">
                    <label class="form-label">Icono / Emoji</label>
                    <input type="text" id="prodIcono" placeholder="🎧" maxlength="10" required class="form-input text-center fs-20 p-6">
                </div>
                <div class="form-group">
                    <label class="form-label">Código de Referencia</label>
                    <input type="text" id="prodCodigo" placeholder="AUD-001" required class="form-input font-mono">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Nombre del Producto</label>
                <input type="text" id="prodNombre" placeholder="Ej: Auriculares Pro Max" required class="form-input">
            </div>

            <div class="form-group-wrap grid-1-1-1">
                <div class="form-group">
                    <label class="form-label">Categoría</label>
                    <select id="prodCat" required class="form-input">
                        <option value="audio">Audio</option>
                        <option value="movil">Móvil</option>
                        <option value="gaming">Gaming</option>
                        <option value="informatica">Informática</option>
                        <option value="cables">Cables</option>
                        <option value="foto">Foto</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Stock Actual</label>
                    <input type="number" id="prodStock" placeholder="0" required class="form-input text-center font-bold">
                </div>
                <div class="form-group">
                    <label class="form-label">Precio (€)</label>
                    <input type="number" id="prodPrecio" step="0.01" placeholder="0.00" required class="form-input text-right font-bold">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="cerrarModalProducto()" class="btn-cancel">Cancelar</button>
                <button type="submit" id="btnGuardarProd" class="btn-save">Guardar Producto</button>
            </div>
        </form>
    </div>
</div>

<script>
/** 
 * Lógica de gestión de productos (JS integrado por ahora)
 */

function abrirModalProducto(producto = null) {
    const modal = document.getElementById('modalProducto');
    const title = document.getElementById('modalTitle');
    const form = document.getElementById('formProducto');

    if (producto) {
        title.innerText = 'Editar Producto';
        document.getElementById('prodId').value = producto.id;
        document.getElementById('prodIcono').value = producto.icono;
        document.getElementById('prodCodigo').value = producto.codigo;
        document.getElementById('prodNombre').value = producto.nombre;
        document.getElementById('prodCat').value = producto.categoria;
        document.getElementById('prodPrecio').value = producto.precio;
        document.getElementById('prodStock').value = producto.stock;
    } else {
        title.innerText = 'Nuevo Producto';
        form.reset();
        document.getElementById('prodId').value = '';
    }

    modal.style.display = 'flex';
}

function cerrarModalProducto() {
    document.getElementById('modalProducto').style.display = 'none';
}

document.getElementById('formProducto').onsubmit = async (e) => {
    e.preventDefault();
    
    const id = document.getElementById('prodId').value;
    const datos = {
        accion: id ? 'editar' : 'añadir',
        id: id,
        icono: document.getElementById('prodIcono').value,
        codigo: document.getElementById('prodCodigo').value,
        nombre: document.getElementById('prodNombre').value,
        categoria: document.getElementById('prodCat').value,
        precio: document.getElementById('prodPrecio').value,
        stock: document.getElementById('prodStock').value
    };

    try {
        const resp = await fetch('api/gestionProducto.php', {
            method: 'POST',
            body: JSON.stringify(datos)
        });
        const r = await resp.json();

        if (r.ok) {
            location.reload(); // Recarga simple para esta versión
        } else {
            alert('Error: ' + r.error);
        }
    } catch (err) {
        console.error(err);
        alert('Error de conexión con el servidor');
    }
};

async function toggleEstadoProducto(id) {
    if (!confirm('¿Deseas cambiar el estado de este producto?')) return;

    try {
        const resp = await fetch('api/gestionProducto.php', {
            method: 'POST',
            body: JSON.stringify({ accion: 'baja', id: id })
        });
        const r = await resp.json();
        if (r.ok) location.reload();
    } catch (err) { alert('Error al cambiar estado'); }
}
</script>
