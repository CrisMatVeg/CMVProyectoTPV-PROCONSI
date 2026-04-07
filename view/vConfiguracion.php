</header>
<div class="main-full p-24">
    <div class="section-header container-wider ai-center jc-space-between">
        <div class="section-title">
            <h1 class="d-flex ai-center gap-12"><i class="fa-solid fa-gears text-accent"></i> <?php echo L('dashboard_btn_config'); ?></h1>
            <p><?php echo L('dashboard_btn_config_sub'); ?></p>
        </div>
        <a href="index.php?irDashboard=1" class="btn-back">
            <?php echo L('login_back'); ?>
        </a>
    </div>

    <div class="container-wider">
        <?php if ($showSuccess): ?>
            <div class="p-16 mb-24 bg-green-light text-green font-bold br-12 border-2 border-green d-flex ai-center gap-12">
                <i class="fa-solid fa-circle-check fs-20"></i>
                <?php echo L('config_save_success'); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($aErrores)): ?>
            <div class="p-16 mb-24 bg-red-light text-red font-bold br-12 border-2 border-red mt-16">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo L('config_save_error'); ?>
            </div>
        <?php endif; ?>

        <!-- Formulario de Configuración Unificado -->
        <form id="formConfig" method="post" action="index.php?irConfiguracion=1" class="bg-surface br-20 border-1 shadow-lg p-32" onsubmit="return validarConfiguracion(event)">
            <input type="hidden" name="seccion_activa" id="seccion_activa" value="">

            <script>
                function validarConfiguracion(e) {
                    const seccion = document.getElementById('seccion_activa').value;
                    
                    if (seccion === 'FACTURACION') {
                        const nifEl = document.getElementById('empresa_nif');
                        if (nifEl) {
                            const nif = nifEl.value.trim();
                            if (nif !== '' && !validarDocumento(nif)) {
                                showCustomAlert("<?php echo L('client_js_invalid_doc'); ?>", "<?php echo L('config_js_invalid_nif_body'); ?>", 'warning');
                                e.preventDefault();
                                return false;
                            }
                        }
                    }
                    
                    if (seccion === 'CONTACTO') {
                        const telEl = document.getElementById('empresa_telefono');
                        if (telEl) {
                            const tel = telEl.value.trim();
                            if (tel !== '' && !validarTelefono(tel)) {
                                showCustomAlert("<?php echo L('client_js_invalid_phone'); ?>", "<?php echo L('config_js_invalid_phone_body'); ?>", 'warning');
                                e.preventDefault();
                                return false;
                            }
                        }
                    }
                    
                    return true;
                }
            </script>

            <div class="d-flex flex-column gap-32">

                <!-- FILA 1: Facturación y Tickets -->
                <div class="config-grid-2">
                    <div class="config-section">
                        <div class="mb-8">
                            <h3 class="fs-16 font-bold text-accent mb-4 d-flex ai-center gap-8"><i class="fa-solid fa-building"></i> <?php echo L('config_section_billing'); ?></h3>
                            <p class="fs-12 text-muted m-0"><?php echo L('config_section_billing_sub'); ?></p>
                        </div>

                        <div class="sub-card">
                            <div class="d-flex flex-column gap-20">
                                <div class="form-group mb-0">
                                    <label class="form-label"><?php echo L('prov_label_name'); ?></label>
                                    <input type="text" name="empresa_nombre" class="form-input" value="<?php echo htmlspecialchars($avConfig['empresa_nombre']); ?>" required>
                                    <?php if (isset($aErrores['empresa_nombre'])): ?>
                                        <span class="form-error"><?php echo $aErrores['empresa_nombre']; ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="config-grid-2">
                                    <div class="form-group mb-0">
                                        <label class="form-label"><?php echo L('config_label_business_name'); ?></label>
                                        <input type="text" name="empresa_razon_social" class="form-input" value="<?php echo htmlspecialchars($avConfig['empresa_razon_social']); ?>">
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="form-label"><?php echo L('client_label_nif'); ?></label>
                                        <input type="text" id="empresa_nif" name="empresa_nif" class="form-input font-mono" value="<?php echo htmlspecialchars($avConfig['empresa_nif']); ?>" autocomplete="off">
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label"><?php echo L('config_label_full_address'); ?></label>
                                    <input type="text" name="empresa_direccion" class="form-input" value="<?php echo htmlspecialchars($avConfig['empresa_direccion']); ?>" placeholder="<?php echo L('config_placeholder_address'); ?>">
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label"><?php echo L('config_label_registry'); ?> <span class="text-muted fs-11 fw-normal">(<?php echo L('optional'); ?>)</span></label>
                                    <textarea name="empresa_registro" class="form-input" rows="2" placeholder="<?php echo L('config_placeholder_registry'); ?>"><?php echo htmlspecialchars($avConfig['empresa_registro']); ?></textarea>
                                </div>

                                <div class="mt-8 pt-24 border-top border-dashed">
                                    <h4 class="fs-14 font-bold text-accent mb-12 d-flex ai-center gap-8"><i class="fa-solid fa-scale-balanced"></i> <?php echo L('config_section_fiscal'); ?></h4>
                                    <div class="d-flex ai-center jc-space-between p-16 br-12 bg-surface2 mb-16 shadow-sm border-1">
                                        <div>
                                            <div class="fs-13 font-bold"><?php echo L('config_label_re'); ?></div>
                                            <div class="fs-11 text-muted"><?php echo L('config_label_re_sub'); ?></div>
                                        </div>
                                        <label class="switch">
                                            <input type="hidden" name="empresa_aplica_re" value="0">
                                            <input type="checkbox" name="empresa_aplica_re" value="1" <?php echo ($avConfig['empresa_aplica_re'] ?? 0) == 1 ? 'checked' : ''; ?>>
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                    
                                    <div class="table-container border-1 br-12 overflow-hidden shadow-sm">
                                        <table class="w-full fs-12 border-collapse" style="background: white;">
                                            <thead class="bg-surface2">
                                                <tr class="text-accent tt-uppercase ls-1">
                                                    <th class="p-12 text-left"><?php echo L('config_th_iva_type'); ?></th>
                                                    <th class="p-12 text-center font-bold">IVA (%)</th>
                                                    <th class="p-12 text-center text-orange font-bold"><?php echo L('tax_th_re_percent'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($tiposIva as $tiva): ?>
                                                    <tr class="border-top hover-bg-surface2 transition-all">
                                                        <td class="p-10 pl-12"><?php echo htmlspecialchars($tiva['nombre']); ?></td>
                                                        <td class="p-10 text-center font-mono fw-700"><?php echo number_format($tiva['porcentaje'], 2); ?>%</td>
                                                        <td class="p-10 text-center font-mono text-orange font-bold"><?php echo number_format($tiva['recargo_equivalencia'], 2); ?>%</td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="mt-12 p-8 br-8 bg-blue-light border-1 border-blue d-flex ai-start gap-8">
                                        <i class="fa-solid fa-circle-info text-accent fs-14 mt-2"></i>
                                        <p class="fs-11 text-muted m-0 lh-1-6">
                                            <?php echo L('config_re_info'); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="config-section">
                        <div class="mb-8">
                            <h3 class="fs-16 font-bold text-accent mb-4 d-flex ai-center gap-8"><i class="fa-solid fa-receipt"></i> <?php echo L('config_section_tickets'); ?></h3>
                            <p class="fs-12 text-muted m-0"><?php echo L('config_section_tickets_sub'); ?></p>
                        </div>

                        <div class="sub-card">
                            <div class="d-flex flex-column gap-20">
                                <div class="form-group mb-0">
                                    <label class="form-label"><?php echo L('config_label_thanks'); ?> <span class="text-muted fs-11 fw-normal">(<?php echo L('config_label_thanks_sub'); ?>)</span></label>
                                    <textarea name="ticket_pie_pagina" class="form-input" rows="3" placeholder="<?php echo L('config_placeholder_thanks'); ?>"><?php echo htmlspecialchars($avConfig['ticket_pie_pagina']); ?></textarea>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label"><?php echo L('config_label_policy'); ?></label>
                                    <textarea name="ticket_politica" class="form-input" rows="4" placeholder="<?php echo L('config_placeholder_policy'); ?>"><?php echo htmlspecialchars($avConfig['ticket_politica']); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="config-footer mt-24 pb-32 border-bottom border-dashed mb-32">
                    <button type="submit" name="guardarConfiguracion" class="btn-save w-auto px-32 fs-14" onclick="document.getElementById('seccion_activa').value='FACTURACION'">
                        <i class="fa-solid fa-floppy-disk mr-10"></i> <?php echo L('config_btn_save_billing'); ?>
                    </button>
                </div>

                <!-- SECCIÓN 2: Contacto y Redes -->
                <div class="config-grid-2">
                    <div class="config-section">
                        <div class="mb-8">
                            <h3 class="fs-16 font-bold text-accent mb-4 d-flex ai-center gap-8"><i class="fa-solid fa-address-book"></i> <?php echo L('config_section_contact'); ?></h3>
                            <p class="fs-12 text-muted m-0"><?php echo L('config_section_contact_sub'); ?></p>
                        </div>

                        <div class="sub-card">
                            <div class="d-flex flex-column gap-20">
                                <div class="config-grid-2">
                                    <div class="form-group mb-0">
                                        <label class="form-label"><?php echo L('client_label_phone'); ?></label>
                                        <div class="search-input-wrap">
                                            <i class="fa-solid fa-phone"></i>
                                            <input type="text" id="empresa_telefono" name="empresa_telefono" class="form-input pl-36" value="<?php echo htmlspecialchars($avConfig['empresa_telefono']); ?>">
                                        </div>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="form-label"><?php echo L('config_label_main_email'); ?></label>
                                        <div class="search-input-wrap">
                                            <i class="fa-solid fa-envelope"></i>
                                            <input type="email" name="empresa_email" class="form-input pl-36" value="<?php echo htmlspecialchars($avConfig['empresa_email']); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label"><?php echo L('config_label_website'); ?></label>
                                    <div class="search-input-wrap">
                                        <i class="fa-solid fa-globe"></i>
                                        <input type="text" name="empresa_web" class="form-input pl-36" value="<?php echo htmlspecialchars($avConfig['empresa_web']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="config-section">
                        <div class="mb-8">
                            <h3 class="fs-16 font-bold text-accent mb-4 d-flex ai-center gap-8"><i class="fa-solid fa-hashtag"></i> <?php echo L('config_section_social'); ?></h3>
                            <p class="fs-12 text-muted m-0"><?php echo L('config_section_social_sub'); ?></p>
                        </div>

                        <div class="sub-card">
                            <div class="d-flex flex-column gap-20">
                                <div class="form-group mb-0">
                                    <label class="form-label"><?php echo L('config_label_instagram'); ?></label>
                                    <div class="search-input-wrap">
                                        <i class="fa-brands fa-instagram text-purple"></i>
                                        <input type="text" name="social_instagram" class="form-input pl-36" value="<?php echo htmlspecialchars($avConfig['social_instagram']); ?>">
                                    </div>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="form-label"><?php echo L('config_label_facebook'); ?></label>
                                    <div class="search-input-wrap">
                                        <i class="fa-brands fa-facebook text-blue"></i>
                                        <input type="text" name="social_facebook" class="form-input pl-36" value="<?php echo htmlspecialchars($avConfig['social_facebook']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="config-footer mt-24 pb-32 border-bottom border-dashed mb-32">
                    <button type="submit" name="guardarConfiguracion" class="btn-save w-auto px-32 fs-14" onclick="document.getElementById('seccion_activa').value='CONTACTO'">
                        <i class="fa-solid fa-floppy-disk mr-10"></i> <?php echo L('config_btn_save_contact'); ?>
                    </button>
                </div>

                <!-- SECCIÓN 3: SMTP -->
                <div class="mt-48 config-section">
                <div class="d-flex ai-center gap-12">
                    <div class="p-8 bg-accent-soft text-accent br-8">
                        <i class="fa-solid fa-paper-plane fs-18"></i>
                    </div>
                    <h3 class="fs-18 font-bold text-accent m-0"><?php echo L('config_section_smtp'); ?></h3>
                </div>

                <div class="guide-banner">
                    <i class="fa-solid fa-circle-info"></i>
                    <div>
                        <?php echo L('config_smtp_gmail_info', true, ['{link}' => '<a href="https://support.google.com/accounts/answer/185833" target="_blank" class="text-accent font-bold">' . L('config_smtp_app_pass', true) . '</a>']); ?>
                    </div>
                </div>

                <div class="sub-card">
                    <div class="config-grid-3">
                        <div class="form-group mb-0">
                            <label class="form-label"><?php echo L('config_label_smtp_host'); ?></label>
                            <div class="search-input-wrap">
                                <i class="fa-solid fa-server"></i>
                                <input type="text" name="smtp_host" class="form-input pl-36" value="<?php echo htmlspecialchars($avConfig['smtp_host'] ?? ''); ?>" placeholder="smtp.gmail.com">
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label"><?php echo L('config_label_smtp_port'); ?></label>
                            <div class="search-input-wrap">
                                <i class="fa-solid fa-hashtag"></i>
                                <input type="text" name="smtp_port" class="form-input pl-36" value="<?php echo htmlspecialchars($avConfig['smtp_port'] ?? ''); ?>" placeholder="587">
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label"><?php echo L('config_label_smtp_secure'); ?></label>
                            <div class="search-input-wrap">
                                <i class="fa-solid fa-shield-halved"></i>
                                <select name="smtp_secure" class="form-input pl-36">
                                    <option value="tls" <?php echo ($avConfig['smtp_secure'] ?? '') == 'tls' ? 'selected' : ''; ?>><?php echo L('config_smtp_secure_tls'); ?></option>
                                    <option value="ssl" <?php echo ($avConfig['smtp_secure'] ?? '') == 'ssl' ? 'selected' : ''; ?>><?php echo L('config_smtp_secure_ssl'); ?></option>
                                    <option value="none" <?php echo ($avConfig['smtp_secure'] ?? '') == 'none' ? 'selected' : ''; ?>><?php echo L('config_smtp_secure_none'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="config-grid-2 mt-32 pt-32 border-top border-dashed">
                        <div class="form-group mb-0">
                            <label class="form-label"><?php echo L('login_user', true); ?> / <?php echo L('email', true); ?></label>
                            <div class="search-input-wrap">
                                <i class="fa-solid fa-envelope"></i>
                                <input type="text" name="smtp_user" class="form-input pl-36" value="<?php echo htmlspecialchars($avConfig['smtp_user'] ?? ''); ?>" placeholder="tu@empresa.com">
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label"><?php echo L('user_label_pass'); ?></label>
                                <div class="search-input-wrap">
                                    <i class="fa-solid fa-lock"></i>
                                    <input type="password" id="smtp_pass" name="smtp_pass" class="form-input pl-36" value="<?php echo htmlspecialchars($avConfig['smtp_pass'] ?? ''); ?>" placeholder="••••••••••••">
                                </div>
                        </div>
                    </div>
                </div>
            </div> <!-- Cierre de d-flex flex-column gap-32 -->

                <div class="config-footer mt-24">
                    <button type="submit" name="guardarConfiguracion" class="btn-save w-auto px-32 fs-14" onclick="document.getElementById('seccion_activa').value='SMTP'">
                        <i class="fa-solid fa-paper-plane mr-10"></i> <?php echo L('config_btn_save_smtp'); ?>
                    </button>
                </div>

            </form>

            <div class="config-footer mt-48">
                <a href="index.php?irDashboard=1" class="btn-cancel w-auto text-decoration-none d-flex ai-center"><?php echo L('config_btn_back_dash'); ?></a>
            </div>

        <!-- SECCIÓN: MANTENIMIENTO Y SEGURIDAD -->
        <div class="mt-48 config-section">
            <div class="bg-surface br-20 border-1 shadow-lg p-32" style="border-left: 5px solid var(--red);">
                <div class="section-header ai-center jc-space-between mb-24 pr-0">
                    <div class="section-title">
                        <div class="d-flex ai-center gap-12 mb-8">
                            <div class="p-8 bg-red-light text-red br-8">
                                <i class="fa-solid fa-cloud-arrow-down fs-18"></i>
                            </div>
                            <h3 class="fs-18 font-bold text-accent m-0"><?php echo L('config_section_backup'); ?></h3>
                        </div>
                        <p class="fs-13 text-muted"><?php echo L('config_section_backup_sub'); ?></p>
                    </div>
                    <div class="d-flex gap-12 ai-center">
                        <a href="index.php?irConfiguracion=1&descargarBackup=1" class="btn-save w-auto px-24 fs-14 bg-red border-red d-flex ai-center text-decoration-none shadow-sm h-48 hover-translate-y" style="margin-left: 20px;">
                            <i class="fa-solid fa-shield-halved mr-12 fs-18"></i> <?php echo L('config_btn_download_backup'); ?>
                        </a>
                    </div>
                </div>

                <div class="bg-blue-light p-20 br-12 d-flex ai-center gap-20 border-1 border-blue">
                    <div class="fs-32 text-accent">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>
                    <div class="fs-13 lh-1-6">
                        <strong class="text-accent d-block mb-4"><?php echo L('config_backup_recommendation'); ?></strong>
                        <?php echo L('config_backup_info'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN: LOGS DEL SISTEMA -->
        <div class="mt-48 bg-surface br-20 border-1 shadow-lg p-32">
            <div class="section-header ai-center jc-space-between mb-24 pr-0">
                <div class="section-title">
                    <h3 class="fs-18 font-bold text-accent d-flex ai-center gap-12">
                        <i class="fa-solid fa-list-check"></i> <?php echo L('config_section_logs'); ?>
                    </h3>
                    <p class="fs-13 text-muted"><?php echo L('config_section_logs_sub'); ?></p>
                </div>
                <div class="d-flex gap-12 ai-center">
                    <a href="index.php?irConfiguracion=1&descargarLogs=1&fechaInicio=<?php echo $fechaInicio; ?>&fechaFin=<?php echo $fechaFin; ?>" class="btn-save w-auto px-20 fs-13 bg-green border-green text-decoration-none d-flex ai-center">
                        <i class="fa-solid fa-download mr-8"></i> <?php echo L('config_btn_download_csv'); ?>
                    </a>
                </div>
            </div>

            <!-- Filtros de Logs -->
            <form method="get" action="index.php" class="filter-toolbar mb-24">
                <input type="hidden" name="irConfiguracion" value="1">

                <div class="filter-group">
                    <span class="filter-label"><?php echo L('config_label_period'); ?>:</span>
                    <div class="d-flex ai-center gap-8">
                        <input type="date" name="fechaInicio" class="input-filter" value="<?php echo $fechaInicio; ?>">
                        <span class="text-muted">→</span>
                        <input type="date" name="fechaFin" class="input-filter" value="<?php echo $fechaFin; ?>">
                    </div>
                </div>

                <div class="d-flex gap-8 ml-auto">
                    <button type="submit" class="btn-save w-auto px-20 fs-13 d-flex ai-center" style="height: 36px; border-radius: 8px;">
                        <i class="fa-solid fa-filter mr-8"></i> <?php echo L('config_btn_apply_filters'); ?>
                    </button>
                    <a href="index.php?irConfiguracion=1" class="btn-cancel w-auto px-12 fs-13 text-decoration-none d-flex ai-center jc-center" style="height: 36px; border-radius: 8px;">
                        <i class="fa-solid fa-eraser"></i>
                    </a>
                </div>
            </form>

            <div class="table-container border-1 br-16 overflow-hidden bg-surface">
                <table class="w-full border-collapse table-premium">
                    <thead>
                        <tr>
                            <th><?php echo L('config_th_datetime'); ?></th>
                            <th><?php echo L('user_label_user'); ?></th>
                            <th><?php echo L('prod_th_actions'); ?></th>
                            <th><?php echo L('client_label_notes'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="4" class="p-32 text-center text-muted fs-14 italic">
                                    <?php echo L('config_no_logs'); ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log):
                                $badgeClass = 'bg-surface2 text-text';
                                $iconAction = 'fa-circle-dot';
                                switch ($log['accion']) {
                                    case 'LOGIN':
                                        $badgeClass = 'bg-blue-light text-blue';
                                        $iconAction = 'fa-right-to-bracket';
                                        break;
                                    case 'LOGOUT':
                                        $badgeClass = 'bg-surface2 text-muted';
                                        $iconAction = 'fa-right-from-bracket';
                                        break;
                                    case 'VENTA':
                                        $badgeClass = 'bg-green-light text-green';
                                        $iconAction = 'fa-cart-shopping';
                                        break;
                                    case 'DESCUENTO':
                                        $badgeClass = 'bg-purple-light text-purple';
                                        $iconAction = 'fa-tag';
                                        break;
                                    case 'ANULACION_TICKET':
                                    case 'ANULACION_LINEA':
                                        $badgeClass = 'bg-red-light text-red';
                                        $iconAction = 'fa-trash-can';
                                        break;
                                    case 'MOVIMIENTO_DINERO':
                                        $badgeClass = 'bg-amber-light text-amber';
                                        $iconAction = 'fa-money-bill-transfer';
                                        break;
                                    case 'APERTURA_CAJA':
                                        $badgeClass = 'bg-accent-soft text-accent';
                                        $iconAction = 'fa-key';
                                        break;
                                    case 'CIERRE_CAJA':
                                        $badgeClass = 'bg-red-light text-red';
                                        $iconAction = 'fa-lock';
                                        break;
                                }
                            ?>
                                <tr class="hover-bg-surface2 transition-all">
                                    <td class="fs-13 font-mono"><?php echo date('d/m/Y H:i:s', strtotime($log['fecha_hora'])); ?></td>
                                    <td class="fs-14 font-bold text-accent"><?php echo htmlspecialchars($log['nombre_usuario']); ?></td>
                                    <td>
                                        <span class="badge-log <?php echo $badgeClass; ?>">
                                            <i class="fa-solid <?php echo $iconAction; ?>"></i>
                                            <?php echo L('log_action_' . strtolower($log['accion'])); ?>
                                        </span>
                                    </td>
                                    <td class="fs-13 lh-1-5"><?php echo htmlspecialchars($log['descripcion']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-16 text-right text-muted fs-11">
                <?php echo L('config_logs_limit'); ?>
            </div>
        </div>
    </div>
</div>