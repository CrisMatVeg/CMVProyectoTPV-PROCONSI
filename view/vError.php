<div class="main" style="display: flex; align-items: center; justify-content: center; min-height: 80vh;">
    <div class="modal" style="width: 500px; padding: 30px; text-align: center; border: 2px solid var(--red-light); background: var(--surface);">
        <div style="font-size: 50px; margin-bottom: 20px;">⚠️</div>
        <h2 style="color: var(--red); margin-bottom: 10px;">Error del Sistema</h2>
        <div style="text-align: left; background: var(--surface2); padding: 15px; border-radius: 8px; margin-bottom: 20px; font-family: 'DM Mono', monospace; font-size: 13px;">
            <p><strong>Código:</strong> <?php echo $error->getCodError(); ?></p>
            <p><strong>Descripción:</strong> <?php echo $error->getDescError(); ?></p>
            <p><strong>Archivo:</strong> <?php echo $error->getArchivoError(); ?></p>
            <p><strong>Línea:</strong> <?php echo $error->getLineaError(); ?></p>
        </div>
        <a href="index.php?pagina=inicioPublico" class="charge-btn" style="text-decoration: none; display: inline-block;">
            Volver al Inicio
        </a>
    </div>
</div>
