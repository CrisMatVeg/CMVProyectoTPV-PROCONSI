</header>
<div class="main-full container-full d-flex ai-center jc-center min-h-80vh">
    <div class="modal modal-content w-500 p-40 text-center border-2 border-red-light">
        <div class="fs-56 mb-24 text-red">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h2 class="text-red mb-12 font-bold">Error del Sistema</h2>
        <div class="empty-state text-left p-20 br-12 mb-24 font-mono fs-13 bg-surface2">
            <p><strong>Código:</strong> <?php echo $error->getCodError(); ?></p>
            <p><strong>Descripción:</strong> <?php echo $error->getDescError(); ?></p>
            <p><strong>Archivo:</strong> <?php echo $error->getArchivoError(); ?></p>
            <p><strong>Línea:</strong> <?php echo $error->getLineaError(); ?></p>
        </div>
        <a href="index.php?pagina=inicioPublico" class="btn-save no-deco d-inline-block w-auto p-12-32">
            Volver al Inicio
        </a>
    </div>
</div>
