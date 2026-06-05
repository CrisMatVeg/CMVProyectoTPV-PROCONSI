<?php // Vista de Tipos de IVA 
?>
<div class="main-full p-24">
    <div class="section-header container-wider">
        <div class="d-flex ai-center gap-16">
            <a href="index.php?irDashboard=1" class="btn-prominent-back compact" title="<?php echo L('login_back'); ?>">
                <i class="fa-solid fa-chevron-left"></i>
                <span><?php echo L('login_back'); ?></span>
            </a>
            <div class="vr" style="height: 32px; width: 1px; background: var(--border); opacity: 0.5;"></div>
            <div class="section-title">
                <h1><?php echo L('tax_title'); ?></h1>
                <p><?php echo L('tax_subtitle'); ?></p>
            </div>
        </div>
        <div class="d-flex gap-12">
            <button onclick="abrirModalIva()" class="btn-add">
                <i class="fa-solid fa-plus"></i> <?php echo L('tax_btn_add'); ?>
            </button>
        </div>
    </div>

    <div class="table-container container-wider br-20">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="pl-20"><?php echo L('tax_th_code'); ?></th>
                    <th><?php echo L('tax_th_name'); ?></th>
                    <th class="text-right"><?php echo L('tax_th_percent'); ?></th>
                    <th class="text-center"><?php echo L('tax_th_validity'); ?></th>
                    <th class="text-center"><?php echo L('tax_th_status'); ?></th>
                    <th class="text-center pr-20"><?php echo L('tax_th_actions'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($avTiposIva['lista'] as $t): ?>
                    <tr data-id="<?php echo $t['id']; ?>">
                        <td class="pl-20 font-mono"><?php echo htmlspecialchars($t['codigo']); ?></td>
                        <td><?php echo htmlspecialchars($t['nombre']); ?></td>
                        <td class="text-right font-mono font-bold">
                            <?php echo number_format($t['porcentaje'], 2, ',', '.'); ?> %
                        </td>
                        <td class="text-center fs-12">
                            <?php
                            $fi = $t['fecha_inicio'] ? date('d/m/Y', strtotime($t['fecha_inicio'])) : '—';
                            $ff = $t['fecha_fin'] ? date('d/m/Y', strtotime($t['fecha_fin'])) : L('tax_indefinite', true);
                            ?>
                            <?php echo $fi . ' → ' . $ff; ?>
                        </td>
                        <td class="text-center">
                            <?php
                            $esVigente = ($t['activo'] &&
                                strtotime($t['fecha_inicio']) <= time() &&
                                (!$t['fecha_fin'] || strtotime($t['fecha_fin']) >= strtotime('today')));

                            if ($esVigente): ?>
                                <span class="status-pill status-active">
                                    <i class="fa-solid fa-circle-check"></i> <?php echo L('status_active'); ?>
                                </span>
                            <?php else: ?>
                                <span class="status-pill status-inactive">
                                    <i class="fa-solid fa-circle-xmark"></i> <?php echo L('status_inactive'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex jc-center gap-8 pr-20">
                                <button onclick='abrirModalIva(<?php echo json_encode($t); ?>)' title="Editar" class="btn-icon">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button onclick="eliminarIva(<?php echo $t['id']; ?>)" title="Eliminar" class="btn-icon btn-icon-danger">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($avTiposIva['lista'])): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fa-solid fa-percent"></i>
                                <?php echo L('tax_no_taxes'); ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL NUEVO/EDITAR IVA -->
<div class="modal-overlay" id="ivaModal">
    <div class="modal modal-content gap-16 ai-stretch w-modal-md" style="max-width: 700px; border-radius: 20px; overflow: hidden;">
        <div class="modal-header mb-0">
            <h2 id="ivaModalTitle" class="m-0 fs-18"><?php echo L('tax_modal_new'); ?></h2>
            <button onclick="cerrarModalIva()" class="btn-close-modal">&times;</button>
        </div>
        <form id="ivaForm" class="modal-body p-20">
            <input type="hidden" id="ivaId">

            <div class="d-grid grid-2 gap-20">
                <div class="form-group">
                    <label class="form-label"><?php echo L('tax_label_code'); ?></label>
                    <input type="text" id="ivaCodigo" class="form-input font-mono" placeholder="<?php echo L('tax_code_placeholder'); ?>">
                    <span class="form-error" id="err-codigo"></span>
                </div>

                <div class="form-group">
                    <label class="form-label"><?php echo L('tax_label_name'); ?></label>
                    <input type="text" id="ivaNombre" class="form-input" placeholder="<?php echo L('tax_name_placeholder'); ?>">
                    <span class="form-error" id="err-nombre"></span>
                </div>
            </div>

            <div class="d-grid grid-3 gap-20">
                <div class="form-group">
                    <label class="form-label fs-11 tt-uppercase"><?php echo L('tax_label_percent'); ?></label>
                    <input type="number" id="ivaPorcentaje" class="form-input text-right" step="0.01" min="0">
                    <span class="form-error" id="err-porcentaje"></span>
                </div>
                <div class="form-group">
                    <label class="form-label fs-11 tt-uppercase"><?php echo L('tax_label_start'); ?></label>
                    <input type="date" id="ivaFechaInicio" class="form-input">
                    <span class="form-error" id="err-fecha_inicio"></span>
                </div>
                <div class="form-group">
                    <label class="form-label fs-11 tt-uppercase"><?php echo L('tax_label_end'); ?></label>
                    <input type="date" id="ivaFechaFin" class="form-input">
                    <span class="form-error" id="err-fecha_fin"></span>
                </div>
            </div>

            <div class="mt-12">
                <label class="d-flex ai-center gap-8 cursor-pointer fs-13">
                    <input type="checkbox" id="ivaActivo" checked>
                    <span><?php echo L('status_active'); ?></span>
                </label>
            </div>
        </form>
        <div class="modal-footer full-width">
            <button onclick="cerrarModalIva()" class="btn-cancel"><?php echo L('modal_cancel'); ?></button>
            <button onclick="guardarIva()" class="btn-save"><?php echo L('tax_btn_save'); ?></button>
        </div>
    </div>
</div>

<script>
    function limpiarErroresIva() {
        document.querySelectorAll('#ivaModal .form-error').forEach(el => el.innerText = '');
    }

    function abrirModalIva(iva = null) {
        limpiarErroresIva();
        const modal = document.getElementById('ivaModal');
        const title = document.getElementById('ivaModalTitle');

        if (iva) {
            title.innerText = "<?php echo L('tax_modal_edit'); ?>";
            document.getElementById('ivaId').value = iva.id;
            document.getElementById('ivaCodigo').value = iva.codigo;
            document.getElementById('ivaNombre').value = iva.nombre;
            document.getElementById('ivaPorcentaje').value = iva.porcentaje;
            document.getElementById('ivaFechaInicio').value = iva.fecha_inicio;
            document.getElementById('ivaFechaFin').value = iva.fecha_fin || '';
            document.getElementById('ivaActivo').checked = !!parseInt(iva.activo);
        } else {
            title.innerText = "<?php echo L('tax_modal_new'); ?>";
            document.getElementById('ivaId').value = '';
            document.getElementById('ivaCodigo').value = '';
            document.getElementById('ivaNombre').value = '';
            document.getElementById('ivaPorcentaje').value = '';
            document.getElementById('ivaFechaInicio').value = '';
            document.getElementById('ivaFechaFin').value = '';
            document.getElementById('ivaActivo').checked = true;
        }

        modal.classList.add('visible');
    }

    function cerrarModalIva() {
        document.getElementById('ivaModal').classList.remove('visible');
        limpiarErroresIva();
    }

    async function guardarIva() {
        limpiarErroresIva();
        const btn = document.querySelector('#ivaModal .btn-save');
        const oldHtml = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>'; }

        const id = document.getElementById('ivaId').value;
        const payload = {
            accion: id ? 'editar' : 'añadir',
            id: id || null,
            codigo: document.getElementById('ivaCodigo').value.trim(),
            nombre: document.getElementById('ivaNombre').value.trim(),
            porcentaje: document.getElementById('ivaPorcentaje').value,
            fecha_inicio: document.getElementById('ivaFechaInicio').value,
            fecha_fin: document.getElementById('ivaFechaFin').value,
            activo: document.getElementById('ivaActivo').checked ? 1 : 0,
        };

        if (payload.fecha_inicio && payload.fecha_fin && payload.fecha_fin < payload.fecha_inicio) {
            document.getElementById('err-fecha_fin').innerText = 'La fecha de fin no puede ser anterior a la de inicio';
            if (btn) { btn.disabled = false; btn.innerHTML = oldHtml; }
            return;
        }

        try {
            const resp = await fetch('api/gestionTipoIva.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload),
            });
            const r = await resp.json();
            if (r.ok) {
                location.reload();
            } else if (r.aErrores) {
                for (const [field, msg] of Object.entries(r.aErrores)) {
                    const el = document.getElementById('err-' + field);
                    if (el && msg) el.innerText = msg;
                }
            } else {
                showCustomAlert("<?php echo L('error'); ?>", r.error || "<?php echo L('tax_error_save'); ?>", 'error');
            }
        } catch (e) {
            console.error(e);
            showCustomAlert('Error', 'Error de conexión con el servidor', 'error');
        } finally {
            if (btn) { btn.disabled = false; btn.innerHTML = oldHtml; }
        }
    }

    async function eliminarIva(id) {
        if (!id) return;
        showCustomConfirm(
            "<?php echo L('tax_confirm_del_title'); ?>",
            "<?php echo L('tax_confirm_del_msg'); ?>",
            async () => {
                    try {
                        const resp = await fetch('api/gestionTipoIva.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                accion: 'eliminar',
                                id
                            }),
                        });
                        const r = await resp.json();
                        if (r.ok) {
                            location.reload();
                        } else {
                            showCustomAlert("<?php echo L('error'); ?>", r.error || "<?php echo L('tax_error_delete'); ?>", 'error');
                        }
                    } catch (e) {
                        console.error(e);
                        showCustomAlert("<?php echo L('error'); ?>", "<?php echo L('error_server_connection'); ?>", 'error');
                    }
                },
                "<?php echo L('modal_delete'); ?>",
                'danger'
        );
    }
</script>