</header>
<div class="main-full p-24">
    
    <!-- CABECERA DE SECCIÓN -->
    <div class="section-header container">
        <div class="section-title">
            <h1>Historial de Ventas</h1>
            <p>Consulta y audita tickets expedidos anteriormente.</p>
        </div>
        <form method="post">
            <button type="submit" name="irDashboard" class="btn-icon w-auto h-auto gap-8 fs-14 p-10-20">
                <i class="fa-solid fa-house"></i> Volver al Dashboard
            </button>
        </form>
    </div>

    <!-- PANEL DE FILTROS -->
    <div class="filters-panel container">
        <form method="get" action="index.php" class="filters-form">
            <div class="filter-group">
                <label>Desde</label>
                <input type="date" name="fechaDesde" value="<?php echo $avHistorial['filtros']['desde']; ?>" class="filter-input">
            </div>
            <div class="filter-group">
                <label>Hasta</label>
                <input type="date" name="fechaHasta" value="<?php echo $avHistorial['filtros']['hasta']; ?>" class="filter-input">
            </div>
            <div class="filter-group w-200">
                <label>Cajero</label>
                <select name="idCajero" class="filter-input">
                    <option value="">Todos los cajeros</option>
                    <?php foreach ($avHistorial['cajeros'] as $c): ?>
                        <option value="<?php echo $c->getId(); ?>" <?php echo $avHistorial['filtros']['cajero'] == $c->getId() ? 'selected' : ''; ?>>
                            <?php echo $c->getNombreCompleto(); ?> (<?php echo $c->getUsername(); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-filter">
                <i class="fa-solid fa-magnifying-glass"></i> Filtrar
            </button>
        </form>
    </div>

    <!-- RESULTADOS -->
    <div class="table-container container">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-100">Ticket</th>
                    <th class="w-180">Fecha y Hora</th>
                    <th>Cajero</th>
                    <th>Pago</th>
                    <th class="text-right">Base</th>
                    <th class="text-right">IVA</th>
                    <th class="text-right">TOTAL</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($avHistorial['ventas'])): ?>
                <tr>
                    <td colspan="8" class="empty-state">
                        <i class="fa-solid fa-folder-open"></i>
                        No se han encontrado ventas para los filtros seleccionados.
                    </td>
                </tr>
                <?php endif; ?>
                
                <?php foreach ($avHistorial['ventas'] as $v): ?>
                <tr>
                    <td class="ticket-num">#<?php echo str_pad($v['numero_ticket'], 4, '0', STR_PAD_LEFT); ?></td>
                    <td class="text-muted fs-13">
                        <?php echo date('d/m/Y H:i', strtotime($v['creado_en'])); ?>
                    </td>
                    <td>
                        <span class="d-flex ai-center gap-8">
                            <div class="avatar-sm">
                                <?php echo strtoupper(substr($v['nombre_cajero'] ?? '?', 0, 1)); ?>
                            </div>
                            <?php echo htmlspecialchars($v['nombre_cajero'] ?? 'Desconocido'); ?>
                        </span>
                    </td>
                    <td>
                        <span class="status-pill <?php echo $v['metodo_pago'] === 'tarjeta' ? 'status-card' : 'status-cash'; ?>">
                            <i class="fa-solid fa-<?php echo $v['metodo_pago'] === 'tarjeta' ? 'credit-card' : 'money-bill-1-wave'; ?>"></i>
                            <?php echo $v['metodo_pago']; ?>
                        </span>
                    </td>
                    <td class="text-right font-mono">
                        <?php echo number_format($v['base_imponible'], 2, ',', '.'); ?> €
                    </td>
                    <td class="text-right font-mono text-muted">
                        <?php echo number_format($v['iva_amt'], 2, ',', '.'); ?> €
                    </td>
                    <td class="text-right font-bold font-mono">
                        <?php echo number_format($v['total'], 2, ',', '.'); ?> €
                    </td>
                    <td>
                        <div class="d-flex gap-8 jc-center">
                            <button title="Ver Detalle / Re-imprimir" class="btn-icon" onclick="alert('Funcionalidad de re-impresión próximamente')">
                                <i class="fa-solid fa-receipt"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>
