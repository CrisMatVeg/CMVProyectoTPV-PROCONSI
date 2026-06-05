</header>
<div class="main-full p-24">
    <div class="section-header container-wider ai-center jc-space-between">
        <div class="d-flex ai-center gap-16">
            <a href="index.php?irDashboard=1" class="btn-prominent-back compact" title="<?php echo L('login_back'); ?>">
                <i class="fa-solid fa-chevron-left"></i>
                <span><?php echo L('login_back'); ?></span>
            </a>
            <div class="vr" style="height: 32px; width: 1px; background: var(--border); opacity: 0.5;"></div>
            <div class="section-title">
                <h1 class="d-flex ai-center gap-12"><i class="fa-solid fa-gears text-accent"></i> <?php echo L('dashboard_btn_config'); ?></h1>
                <p><?php echo L('dashboard_btn_config_sub'); ?></p>
            </div>
        </div>
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

        <div class="config-layout">
            <!-- Sidebar Izquierda -->
            <aside class="config-sidebar">
                <div class="config-nav">
                    <div class="config-nav-item active" onclick="switchConfigTab('facturacion')" id="nav-facturacion">
                        <i class="fa-solid fa-building"></i> <span><?php echo L('config_section_billing'); ?></span>
                    </div>
                    <div class="config-nav-item" onclick="switchConfigTab('tickets')" id="nav-tickets">
                        <i class="fa-solid fa-receipt"></i> <span><?php echo L('config_section_tickets'); ?></span>
                    </div>
                    <div class="config-nav-item" onclick="switchConfigTab('contacto')" id="nav-contacto">
                        <i class="fa-solid fa-address-book"></i> <span><?php echo L('config_section_contact'); ?></span>
                    </div>
                    <div class="config-nav-item" onclick="switchConfigTab('smtp')" id="nav-smtp">
                        <i class="fa-solid fa-paper-plane"></i> <span><?php echo L('config_section_smtp'); ?></span>
                    </div>
                    <div class="config-nav-item" onclick="switchConfigTab('mantenimiento')" id="nav-mantenimiento">
                        <i class="fa-solid fa-shield-halved"></i> <span><?php echo L('config_section_backup'); ?></span>
                    </div>
                    <div class="config-nav-item" onclick="switchConfigTab('logs')" id="nav-logs">
                        <i class="fa-solid fa-list-check"></i> <span><?php echo L('config_section_logs'); ?></span>
                    </div>
                    <div class="config-nav-item" onclick="switchConfigTab('verifactu')" id="nav-verifactu" style="border-left: 3px solid var(--accent);">
                        <i class="fa-solid fa-cloud-arrow-up"></i> <span class="font-bold"><?php echo L('config_vf_title'); ?></span>
                    </div>
                </div>
            </aside>

            <!-- Contenido Derecha -->
            <div class="config-panels-container">
                <form id="formConfig" method="post" action="index.php?irConfiguracion=1" onsubmit="return validarConfiguracion(event)">
                    <input type="hidden" name="seccion_activa" id="seccion_activa" value="">

                    <!-- PANEL 1: Facturación y Fiscal -->
                    <div id="panel-facturacion" class="config-panel active">
                        <div class="bg-surface br-20 border-1 shadow-lg p-32">
                            <div class="mb-24 pb-16 border-bottom">
                                <h3 class="fs-20 font-bold text-accent mb-4 d-flex ai-center gap-12"><i class="fa-solid fa-building"></i> <?php echo L('config_section_billing'); ?></h3>
                                <p class="fs-14 text-muted m-0"><?php echo L('config_section_billing_sub'); ?></p>
                            </div>

                            <div class="d-flex flex-column gap-24">
                                <div class="form-group mb-0">
                                    <label class="form-label"><?php echo L('prov_label_name'); ?></label>
                                    <input type="text" name="empresa_nombre" class="form-input p-12" value="<?php echo htmlspecialchars($avConfig['empresa_nombre']); ?>" required>
                                    <?php if (isset($aErrores['empresa_nombre'])): ?>
                                        <span class="form-error"><?php echo $aErrores['empresa_nombre']; ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="config-grid-2">
                                    <div class="form-group mb-0">
                                        <label class="form-label"><?php echo L('config_label_business_name'); ?></label>
                                        <input type="text" name="empresa_razon_social" class="form-input p-12" value="<?php echo htmlspecialchars($avConfig['empresa_razon_social']); ?>">
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="form-label"><?php echo L('client_label_nif'); ?></label>
                                        <input type="text" id="empresa_nif" name="empresa_nif" class="form-input font-mono p-12" value="<?php echo htmlspecialchars($avConfig['empresa_nif']); ?>" autocomplete="off">
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label"><?php echo L('config_label_full_address'); ?></label>
                                    <input type="text" name="empresa_direccion" class="form-input p-12" value="<?php echo htmlspecialchars($avConfig['empresa_direccion']); ?>" placeholder="<?php echo L('config_placeholder_address'); ?>">
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label"><?php echo L('config_label_registry'); ?> <span class="text-muted fs-11 fw-normal">(<?php echo L('optional'); ?>)</span></label>
                                    <textarea name="empresa_registro" class="form-input p-12" rows="2" placeholder="<?php echo L('config_placeholder_registry'); ?>"><?php echo htmlspecialchars($avConfig['empresa_registro']); ?></textarea>
                                </div>

                                <div class="mt-8 pt-24 border-top border-dashed">
                                    <h4 class="fs-16 font-bold text-accent mb-16 d-flex ai-center gap-8"><i class="fa-solid fa-scale-balanced"></i> <?php echo L('config_section_fiscal'); ?></h4>
                                    <div class="d-flex ai-center jc-space-between p-16 br-12 bg-surface2 mb-20 shadow-sm border-1" style="max-width: 600px;">
                                        <div>
                                            <div class="fs-14 font-bold text-accent"><?php echo L('config_label_re'); ?></div>
                                            <div class="fs-12 text-muted"><?php echo L('config_label_re_sub'); ?></div>
                                        </div>
                                        <div class="d-flex ai-center gap-12">
                                            <input type="hidden" name="empresa_aplica_re" value="1">
                                        </div>
                                    </div>
                                    
                                    <div class="table-container border-1 br-12 overflow-hidden shadow-sm mb-16" style="max-width: 500px;">
                                        <table class="w-full fs-13 border-collapse" style="background: white;">
                                            <thead class="bg-surface2">
                                                <tr class="text-accent tt-uppercase ls-1">
                                                    <th class="p-12 text-left"><?php echo L('config_th_iva_type'); ?></th>
                                                    <th class="p-12 text-center font-bold" style="width: 80px;">IVA</th>
                                                    <th class="p-12 text-center text-orange font-bold" style="width: 80px;">RE</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($tiposIva as $tiva): ?>
                                                    <tr class="border-top hover-bg-surface2 transition-all">
                                                        <td class="p-12 pl-16"><?php echo htmlspecialchars($tiva['nombre']); ?></td>
                                                        <td class="p-12 text-center font-mono fw-700"><?php echo number_format($tiva['porcentaje'], 0); ?>%</td>
                                                        <td class="p-12 text-center font-mono text-orange font-bold"><?php echo number_format($tiva['recargo_equivalencia'], 1); ?>%</td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="mt-12 p-12 br-12 bg-blue-light border-1 border-blue d-flex ai-start gap-12">
                                        <i class="fa-solid fa-circle-info text-accent fs-16 mt-2"></i>
                                        <p class="fs-12 text-muted m-0 lh-1-6">
                                            <?php echo L('config_re_info'); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="config-footer mt-40 pt-24 border-top">
                                <button type="submit" name="guardarConfiguracion" class="btn-save" onclick="document.getElementById('seccion_activa').value='FACTURACION'">
                                    <i class="fa-solid fa-floppy-disk"></i> <?php echo L('config_btn_save_billing'); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- PANEL 2: Tickets y Recibos -->
                    <div id="panel-tickets" class="config-panel">
                        <div class="bg-surface br-20 border-1 shadow-lg p-32">
                            <div class="mb-24 pb-16 border-bottom">
                                <h3 class="fs-20 font-bold text-accent mb-4 d-flex ai-center gap-12"><i class="fa-solid fa-receipt"></i> <?php echo L('config_section_tickets'); ?></h3>
                                <p class="fs-14 text-muted m-0"><?php echo L('config_section_tickets_sub'); ?></p>
                            </div>

                            <div class="d-flex flex-column gap-24">
                                <div class="form-group mb-0">
                                    <label class="form-label"><?php echo L('config_label_thanks'); ?> <span class="text-muted fs-12 fw-normal">(<?php echo L('config_label_thanks_sub'); ?>)</span></label>
                                    <textarea name="ticket_pie_pagina" class="form-input p-12" rows="3" placeholder="<?php echo L('config_placeholder_thanks'); ?>"><?php echo htmlspecialchars($avConfig['ticket_pie_pagina']); ?></textarea>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label"><?php echo L('config_label_policy'); ?></label>
                                    <textarea name="ticket_politica" class="form-input p-12" rows="6" placeholder="<?php echo L('config_placeholder_policy'); ?>"><?php echo htmlspecialchars($avConfig['ticket_politica']); ?></textarea>
                                </div>
                            </div>

                            <div class="config-footer mt-40 pt-24 border-top">
                                <button type="submit" name="guardarConfiguracion" class="btn-save" onclick="document.getElementById('seccion_activa').value='FACTURACION'">
                                    <i class="fa-solid fa-floppy-disk"></i> <?php echo L('config_btn_save_billing'); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- PANEL 3: Contacto y Redes -->
                    <div id="panel-contacto" class="config-panel">
                        <div class="bg-surface br-20 border-1 shadow-lg p-32">
                            <div class="mb-24 pb-16 border-bottom">
                                <h3 class="fs-20 font-bold text-accent mb-4 d-flex ai-center gap-12"><i class="fa-solid fa-address-book"></i> <?php echo L('config_section_contact'); ?></h3>
                                <p class="fs-14 text-muted m-0"><?php echo L('config_section_contact_sub'); ?></p>
                            </div>

                            <div class="d-flex flex-column gap-32">
                                <div class="config-grid-2">
                                    <div class="form-group mb-0">
                                        <label class="form-label"><?php echo L('client_label_phone'); ?></label>
                                        <div class="search-input-wrap h-48">
                                            <i class="fa-solid fa-phone"></i>
                                            <input type="text" id="empresa_telefono" name="empresa_telefono" class="form-input pl-36" value="<?php echo htmlspecialchars($avConfig['empresa_telefono']); ?>">
                                        </div>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="form-label"><?php echo L('config_label_main_email'); ?></label>
                                        <div class="search-input-wrap h-48">
                                            <i class="fa-solid fa-envelope"></i>
                                            <input type="email" name="empresa_email" class="form-input pl-36" value="<?php echo htmlspecialchars($avConfig['empresa_email']); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <label class="form-label"><?php echo L('config_label_website'); ?></label>
                                    <div class="search-input-wrap h-48">
                                        <i class="fa-solid fa-globe"></i>
                                        <input type="text" name="empresa_web" class="form-input pl-36" value="<?php echo htmlspecialchars($avConfig['empresa_web']); ?>">
                                    </div>
                                </div>

                                <div class="mt-8 pt-24 border-top border-dashed">
                                    <h4 class="fs-16 font-bold text-accent mb-20 d-flex ai-center gap-8"><i class="fa-solid fa-hashtag"></i> <?php echo L('config_section_social'); ?></h4>
                                    
                                    <div class="config-grid-2">
                                        <div class="form-group mb-0">
                                            <label class="form-label"><?php echo L('config_label_instagram'); ?></label>
                                            <div class="search-input-wrap h-48">
                                                <i class="fa-brands fa-instagram text-purple"></i>
                                                <input type="text" name="social_instagram" class="form-input pl-36" value="<?php echo htmlspecialchars($avConfig['social_instagram']); ?>">
                                            </div>
                                        </div>
                                        <div class="form-group mb-0">
                                            <label class="form-label"><?php echo L('config_label_facebook'); ?></label>
                                            <div class="search-input-wrap h-48">
                                                <i class="fa-brands fa-facebook text-blue"></i>
                                                <input type="text" name="social_facebook" class="form-input pl-36" value="<?php echo htmlspecialchars($avConfig['social_facebook']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="config-footer mt-40 pt-24 border-top">
                                <button type="submit" name="guardarConfiguracion" class="btn-save" onclick="document.getElementById('seccion_activa').value='CONTACTO'">
                                    <i class="fa-solid fa-floppy-disk"></i> <?php echo L('config_btn_save_contact'); ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- PANEL 4: SMTP -->
                    <div id="panel-smtp" class="config-panel">
                        <div class="bg-surface br-20 border-1 shadow-lg p-32">
                            <div class="mb-24 pb-16 border-bottom">
                                <h3 class="fs-20 font-bold text-accent mb-4 d-flex ai-center gap-12"><i class="fa-solid fa-paper-plane"></i> <?php echo L('config_section_smtp'); ?></h3>
                            </div>

                            <div class="guide-banner mb-24">
                                <i class="fa-solid fa-circle-info"></i>
                                <div>
                                    <?php echo L('config_smtp_gmail_info', true, ['{link}' => '<a href="https://support.google.com/accounts/answer/185833" target="_blank" class="text-accent font-bold">' . L('config_smtp_app_pass', true) . '</a>']); ?>
                                </div>
                            </div>

                            <div class="d-flex flex-column gap-24">
                                <div class="config-grid-3">
                                    <div class="form-group mb-0">
                                        <label class="form-label"><?php echo L('config_label_smtp_host'); ?></label>
                                        <div class="search-input-wrap h-44">
                                            <i class="fa-solid fa-server"></i>
                                            <input type="text" name="smtp_host" class="form-input pl-36" value="<?php echo htmlspecialchars($avConfig['smtp_host'] ?? ''); ?>" placeholder="smtp.gmail.com">
                                        </div>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="form-label"><?php echo L('config_label_smtp_port'); ?></label>
                                        <div class="search-input-wrap h-44">
                                            <i class="fa-solid fa-hashtag"></i>
                                            <input type="text" name="smtp_port" class="form-input pl-36" value="<?php echo htmlspecialchars($avConfig['smtp_port'] ?? ''); ?>" placeholder="587">
                                        </div>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="form-label"><?php echo L('config_label_smtp_secure'); ?></label>
                                        <div class="search-input-wrap h-44">
                                            <i class="fa-solid fa-shield-halved"></i>
                                            <select name="smtp_secure" class="form-input pl-36">
                                                <option value="tls" <?php echo ($avConfig['smtp_secure'] ?? '') == 'tls' ? 'selected' : ''; ?>><?php echo L('config_smtp_secure_tls'); ?></option>
                                                <option value="ssl" <?php echo ($avConfig['smtp_secure'] ?? '') == 'ssl' ? 'selected' : ''; ?>><?php echo L('config_smtp_secure_ssl'); ?></option>
                                                <option value="none" <?php echo ($avConfig['smtp_secure'] ?? '') == 'none' ? 'selected' : ''; ?>><?php echo L('config_smtp_secure_none'); ?></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="config-grid-2 pt-24 border-top border-dashed">
                                    <div class="form-group mb-0">
                                        <label class="form-label"><?php echo L('login_user', true); ?> / <?php echo L('email', true); ?></label>
                                        <div class="search-input-wrap h-44">
                                            <i class="fa-solid fa-envelope"></i>
                                            <input type="text" name="smtp_user" class="form-input pl-36" value="<?php echo htmlspecialchars($avConfig['smtp_user'] ?? ''); ?>" placeholder="tu@empresa.com">
                                        </div>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="form-label"><?php echo L('user_label_pass'); ?></label>
                                            <div class="search-input-wrap h-44">
                                                <i class="fa-solid fa-lock"></i>
                                                <input type="password" id="smtp_pass" name="smtp_pass" class="form-input pl-36" value="<?php echo htmlspecialchars($avConfig['smtp_pass'] ?? ''); ?>" placeholder="••••••••••••">
                                            </div>
                                    </div>
                                </div>
                            </div>

                            <div class="config-footer mt-40 pt-24 border-top">
                                <button type="submit" name="guardarConfiguracion" class="btn-save" onclick="document.getElementById('seccion_activa').value='SMTP'">
                                    <i class="fa-solid fa-paper-plane"></i> <?php echo L('config_btn_save_smtp'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- PANEL 5: VeriFactu Monitoring & Configuration -->
                    <div id="panel-verifactu" class="config-panel">
                        <div class="bg-surface br-20 border-1 shadow-lg p-32">
                            <div class="section-header ai-center jc-space-between mb-24 pr-0 pb-16 border-bottom">
                                <div class="section-title">
                                    <h3 class="fs-20 font-bold text-accent d-flex ai-center gap-12 m-0">
                                        <i class="fa-solid fa-cloud-arrow-up"></i> <?php echo L('config_vf_title'); ?>
                                    </h3>
                                    <p class="fs-14 text-muted m-0 mt-4"><?php echo L('config_vf_sub'); ?></p>
                                </div>
                                <div class="d-flex ai-center gap-12">
                                    <a href="verifactu_log.php" target="_blank" class="btn-save compact d-flex ai-center gap-8 no-print" style="text-decoration: none; min-width: auto; padding: 8px 16px; height: 38px; border-radius: 8px; background: var(--accent); color: white;" title="<?php echo L('config_vf_audit_title'); ?>">
                                        <i class="fa-solid fa-layer-group"></i> <span><?php echo L('config_vf_audit'); ?></span>
                                    </a>
                                    <button type="button" onclick="cargarStatsVerifactu()" class="btn-save compact no-print" style="border: 1px solid var(--border); min-width: auto; padding: 8px 12px; height: 38px; border-radius: 8px; background: var(--surface2); color: var(--accent);" title="<?php echo L('config_vf_update'); ?>">
                                        <i class="fa-solid fa-sync"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Stats Cards -->
                            <div class="grid-3 gap-24 mb-32">
                                <div class="p-24 br-16 border bg-surface d-flex ai-center gap-16 shadow-sm">
                                    <div class="w-56 h-56 br-12 bg-green-light text-green d-flex ai-center jc-center">
                                        <i class="fa-solid fa-check-double fs-24"></i>
                                    </div>
                                    <div>
                                        <div class="fs-12 text-muted tt-uppercase fw-bold ls-1 mb-4"><?php echo L('config_vf_sent'); ?></div>
                                        <div class="fs-28 font-bold font-mono" id="vf-stats-enviados">0</div>
                                    </div>
                                </div>
                                <div class="p-24 br-16 border bg-surface d-flex ai-center gap-16 shadow-sm">
                                    <div class="w-56 h-56 br-12 bg-amber-light text-amber d-flex ai-center jc-center">
                                        <i class="fa-solid fa-clock-rotate-left fs-24"></i>
                                    </div>
                                    <div>
                                        <div class="fs-12 text-muted tt-uppercase fw-bold ls-1 mb-4"><?php echo L('config_vf_pending'); ?></div>
                                        <div class="fs-28 font-bold font-mono" id="vf-stats-pendientes">0</div>
                                    </div>
                                </div>
                                <div class="p-24 br-16 border bg-surface d-flex ai-center gap-16 shadow-sm">
                                    <div class="w-56 h-56 br-12 bg-red-light text-red d-flex ai-center jc-center">
                                        <i class="fa-solid fa-triangle-exclamation fs-24"></i>
                                    </div>
                                    <div>
                                        <div class="fs-12 text-muted tt-uppercase fw-bold ls-1 mb-4"><?php echo L('config_vf_errors'); ?></div>
                                        <div class="fs-28 font-bold font-mono" id="vf-stats-errores">0</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Config Editor & Legal Block -->
                            <div class="config-grid-2 mb-32">
                                <!-- Columna Izq: Datos del Productor (Editables) -->
                                <div class="p-24 br-16 border-1 bg-surface2 d-flex-column jc-between">
                                    <div>
                                        <h4 class="fs-14 font-bold text-accent mb-20 tt-uppercase d-flex ai-center gap-8">
                                            <i class="fa-solid fa-user-gear"></i> <?php echo L('config_vf_prod_data'); ?>
                                        </h4>
                                        <div class="d-flex flex-column gap-20">
                                            <div class="form-group mb-0">
                                                <label class="form-label fs-11 font-bold text-muted mb-8 tt-uppercase ls-1"><?php echo L('verifactu_label_productor'); ?></label>
                                                <input type="text" name="verifactu_productor_nombre" class="form-input p-12 bg-white br-8 border-1" value="<?php echo htmlspecialchars($avConfig['verifactu_productor_nombre'] ?? 'ElectroBazar Software S.L.'); ?>">
                                            </div>
                                            <div class="form-group mb-0">
                                                <label class="form-label fs-11 font-bold text-muted mb-8 tt-uppercase ls-1"><?php echo L('verifactu_label_productor_nif'); ?></label>
                                                <input type="text" name="verifactu_productor_nif" class="form-input p-12 font-mono bg-white br-8 border-1" value="<?php echo htmlspecialchars($avConfig['verifactu_productor_nif'] ?? '99999910G'); ?>">
                                            </div>
                                            <div class="d-flex gap-16">
                                                <div class="form-group mb-0 flex-1">
                                                    <label class="form-label fs-11 font-bold text-muted mb-8 tt-uppercase ls-1"><?php echo L('config_vf_sys_id'); ?></label>
                                                    <input type="text" name="verifactu_id_sistema" class="form-input p-12 bg-white br-8 border-1" value="<?php echo htmlspecialchars($avConfig['verifactu_id_sistema'] ?? '01'); ?>">
                                                </div>
                                                <div class="form-group mb-0 flex-1">
                                                    <label class="form-label fs-11 font-bold text-muted mb-8 tt-uppercase ls-1"><?php echo L('config_vf_version'); ?></label>
                                                    <input type="text" name="verifactu_version_sistema" class="form-input p-12 bg-white br-8 border-1" value="<?php echo htmlspecialchars($avConfig['verifactu_version_sistema'] ?? '1.0.0'); ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-32">
                                        <button type="submit" name="guardarConfiguracion" class="btn-save w-full d-flex ai-center jc-center gap-12 py-14 shadow-sm" style="height: 48px;" onclick="document.getElementById('seccion_activa').value='VERIFACTU'">
                                            <i class="fa-solid fa-floppy-disk"></i> <?php echo L('config_vf_btn_save'); ?>
                                        </button>
                                    </div>
                                </div>

                                <!-- Columna Der: Cuadro de Certificación -->
                                <div class="p-24 br-16 border-1 bg-blue-light d-flex-column jc-between" style="border-color: var(--accent); background: rgba(26, 47, 191, 0.03);">
                                    <div>
                                        <h4 class="fs-14 font-bold text-accent mb-20 tt-uppercase d-flex ai-center gap-8">
                                            <i class="fa-solid fa-file-contract"></i> <?php echo L('verifactu_decl_titulo'); ?>
                                        </h4>
                                        <div class="fs-13 lh-1-6 text-muted mb-20">
                                            <p class="mb-12"><?php echo L('verifactu_decl_legal_intro'); ?></p>
                                            <p><?php echo L('verifactu_decl_legal_body'); ?></p>
                                        </div>
                                        <div class="p-16 br-12 bg-white border-1 fs-12 mb-20 shadow-sm" style="border-left: 4px solid var(--accent);">
                                            <div class="d-flex ai-center gap-12 mb-8">
                                                <div class="w-8 h-8 br-full bg-accent"></div>
                                                <span class="text-muted tt-uppercase fw-bold ls-1" style="font-size: 10px;"><?php echo L('config_vf_certified'); ?></span>
                                            </div>
                                            <div class="font-bold text-accent mb-4 fs-14">
                                                <?php echo htmlspecialchars($avConfig['verifactu_nombre_sistema'] ?? 'ElectroBazar TPV'); ?> v<?php echo htmlspecialchars($avConfig['verifactu_version_sistema'] ?? '1.0.0'); ?>
                                            </div>
                                            <div class="text-muted italic">
                                                <?php echo L('config_vf_requested_by'); ?> <span class="font-bold text-text not-italic"><?php echo htmlspecialchars($avConfig['verifactu_productor_nombre'] ?? 'ElectroBazar Software S.L.'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-auto">
                                        <a href="api/descargarDeclaracion.php" class="btn-save text-decoration-none d-flex ai-center jc-center gap-12 py-14 shadow-sm" style="background: var(--accent); border-color: var(--accent); height: 48px;">
                                            <i class="fa-solid fa-file-pdf fs-18"></i> <?php echo L('verifactu_descargar_decl'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </form>

                <!-- PANEL 6: Mantenimiento y Seguridad -->
                <div id="panel-mantenimiento" class="config-panel">
                    <div class="bg-surface br-20 border-1 shadow-lg p-32" style="border-left: 5px solid var(--accent);">
                        <div class="section-header ai-center jc-space-between mb-32 pr-0 pb-16 border-bottom">
                            <div class="section-title">
                                <div class="d-flex ai-center gap-12 mb-8">
                                    <div class="p-8 bg-blue-light text-accent br-8">
                                        <i class="fa-solid fa-cloud-arrow-down fs-20"></i>
                                    </div>
                                    <h3 class="fs-20 font-bold text-accent m-0"><?php echo L('config_section_backup'); ?></h3>
                                </div>
                                <p class="fs-14 text-muted m-0"><?php echo L('config_section_backup_sub'); ?></p>
                            </div>
                        </div>

                        <div class="bg-blue-light p-24 br-16 d-flex ai-center gap-24 border-1 border-blue mb-32">
                            <div class="fs-40 text-accent">
                                <i class="fa-solid fa-circle-info"></i>
                            </div>
                            <div class="fs-14 lh-1-6">
                                <strong class="text-accent d-block mb-4 fs-16"><?php echo L('config_backup_recommendation'); ?></strong>
                                <?php echo L('config_backup_info'); ?>
                            </div>
                        </div>

                        <div class="d-flex jc-center pt-8">
                            <a href="index.php?irConfiguracion=1&descargarBackup=1" class="btn-save text-decoration-none shadow-md hover-translate-y" style="background: #7c3aed; box-shadow: 0 4px 15px rgba(124, 58, 237, 0.2); min-width: 260px;">
                                <i class="fa-solid fa-shield-halved"></i> <?php echo L('config_btn_download_backup'); ?>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- PANEL 6: Logs del Sistema -->
                <div id="panel-logs" class="config-panel">
                    <div class="bg-surface br-20 border-1 shadow-lg p-32">
                        <div class="section-header ai-center jc-space-between mb-24 pr-0 pb-16 border-bottom">
                            <div class="section-title">
                                <h3 class="fs-20 font-bold text-accent d-flex ai-center gap-12 m-0">
                                    <i class="fa-solid fa-list-check"></i> <?php echo L('config_section_logs'); ?>
                                </h3>
                                <p class="fs-14 text-muted m-0 mt-4"><?php echo L('config_section_logs_sub'); ?></p>
                            </div>
                            <div class="d-flex gap-12 ai-center">
                                <a href="index.php?irConfiguracion=1&descargarLogs=1&fechaInicio=<?php echo $fechaInicio; ?>&fechaFin=<?php echo $fechaFin; ?>" class="btn-save text-decoration-none d-flex ai-center" style="background: #0f8060; border-color: #0f8060; min-width: 180px;">
                                    <i class="fa-solid fa-download"></i> <?php echo L('config_btn_download_csv'); ?>
                                </a>
                            </div>
                        </div>

                        <!-- Filtros de Logs -->
                        <form method="get" action="index.php" class="filter-toolbar mb-24 bg-surface2">
                            <input type="hidden" name="irConfiguracion" value="1">

                            <div class="filter-group">
                                <span class="filter-label font-bold text-accent"><?php echo L('config_label_period'); ?>:</span>
                                <div class="d-flex ai-center gap-12">
                                    <input type="date" name="fechaInicio" class="input-filter h-40 px-12" value="<?php echo $fechaInicio; ?>">
                                    <span class="text-muted font-bold">→</span>
                                    <input type="date" name="fechaFin" class="input-filter h-40 px-12" value="<?php echo $fechaFin; ?>">
                                </div>
                            </div>

                            <div class="d-flex gap-12 ml-auto">
                                <button type="submit" class="btn-save shadow-sm" style="min-width: 140px;">
                                    <i class="fa-solid fa-filter"></i> <?php echo L('config_btn_apply_filters'); ?>
                                </button>
                                <a href="index.php?irConfiguracion=1" class="btn-cancel text-decoration-none jc-center" title="Limpiar filtros" style="min-width: 50px; padding: 14px;">
                                    <i class="fa-solid fa-eraser"></i>
                                </a>
                            </div>
                        </form>

                        <div class="table-container border-1 br-16 overflow-hidden bg-surface shadow-sm">
                            <table class="w-full border-collapse table-premium">
                                <thead class="bg-surface2">
                                    <tr>
                                        <th class="p-16 text-left"><?php echo L('config_th_datetime'); ?></th>
                                        <th class="p-16 text-left"><?php echo L('user_label_user'); ?></th>
                                        <th class="p-16 text-left"><?php echo L('prod_th_actions'); ?></th>
                                        <th class="p-16 text-left"><?php echo L('client_label_notes'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($logs)): ?>
                                        <tr>
                                            <td colspan="4" class="p-48 text-center text-muted fs-15 italic">
                                                <i class="fa-solid fa-magnifying-glass fs-24 d-block mb-12 opacity-30"></i>
                                                <?php echo L('config_no_logs'); ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($logs as $log):
                                            $badgeClass = 'bg-surface2 text-text';
                                            $iconAction = 'fa-circle-dot';
                                            switch ($log['accion']) {
                                                case 'LOGIN': $badgeClass = 'bg-blue-light text-blue'; $iconAction = 'fa-right-to-bracket'; break;
                                                case 'LOGOUT': $badgeClass = 'bg-surface2 text-muted'; $iconAction = 'fa-right-from-bracket'; break;
                                                case 'VENTA': $badgeClass = 'bg-green-light text-green'; $iconAction = 'fa-cart-shopping'; break;
                                                case 'DESCUENTO': $badgeClass = 'bg-purple-light text-purple'; $iconAction = 'fa-tag'; break;
                                                case 'ANULACION_TICKET':
                                                case 'ANULACION_LINEA': $badgeClass = 'bg-red-light text-red'; $iconAction = 'fa-trash-can'; break;
                                                case 'MOVIMIENTO_DINERO': $badgeClass = 'bg-amber-light text-amber'; $iconAction = 'fa-money-bill-transfer'; break;
                                                case 'APERTURA_CAJA': $badgeClass = 'bg-accent-soft text-accent'; $iconAction = 'fa-key'; break;
                                                case 'CIERRE_CAJA': $badgeClass = 'bg-red-light text-red'; $iconAction = 'fa-lock'; break;
                                                // VeriFactu Specific
                                                case 'VF_ENVIO_EXITO': $badgeClass = 'bg-green-light text-green'; $iconAction = 'fa-cloud-arrow-up'; break;
                                                case 'VF_ENVIO_ERROR': $badgeClass = 'bg-red-light text-red'; $iconAction = 'fa-triangle-exclamation'; break;
                                                case 'VF_ENVIO_REINTENTO': $badgeClass = 'bg-surface2 text-amber'; $iconAction = 'fa-clock-rotate-left'; break;
                                                case 'VF_SUBSANACION': $badgeClass = 'bg-amber-light text-orange'; $iconAction = 'fa-wrench'; break;
                                            }
                                        ?>
                                            <tr class="hover-bg-surface2 transition-all border-top">
                                                <td class="p-16 fs-13 font-mono"><?php echo date('d/m/Y H:i:s', strtotime($log['fecha_hora'])); ?></td>
                                                <td class="p-16 fs-14 font-bold text-accent"><?php echo htmlspecialchars($log['nombre_usuario']); ?></td>
                                                <td class="p-16">
                                                    <span class="badge-log <?php echo $badgeClass; ?> p-8 br-8 fs-11">
                                                        <i class="fa-solid <?php echo $iconAction; ?> mr-6"></i>
                                                        <?php echo L('log_action_' . strtolower($log['accion'])); ?>
                                                    </span>
                                                </td>
                                                <td class="p-16 fs-13 lh-1-5"><?php echo htmlspecialchars($log['descripcion']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-20 text-right text-muted fs-12 italic">
                            <i class="fa-solid fa-circle-exclamation mr-6"></i> <?php echo L('config_logs_limit'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    /**
     * Alterna la visibilidad de los paneles de configuración
     */
    function switchConfigTab(panelId) {
        // Guardar estado en localStorage
        localStorage.setItem('config_active_tab', panelId);

        // Actualizar Items del Sidebar
        document.querySelectorAll('.config-nav-item').forEach(item => {
            item.classList.remove('active');
        });
        document.getElementById('nav-' + panelId).classList.add('active');

        // Actualizar Paneles de Contenido
        document.querySelectorAll('.config-panel').forEach(panel => {
            panel.classList.remove('active');
        });
        document.getElementById('panel-' + panelId).classList.add('active');
        
        // [NUEVO] Si entramos en verifactu, cargar datos
        if (panelId === 'verifactu') {
            cargarStatsVerifactu();
        }

        // Scroll al inicio del panel
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    /**
     * Carga las estadísticas y cola de VeriFactu vía AJAX
     */
    function cargarStatsVerifactu() {
        fetch('api/verifactu_stats.php?accion=stats')
            .then(response => {
                return response.json().then(data => {
                    if (!response.ok) throw new Error(data.error || 'Error HTTP ' + response.status);
                    return data;
                }).catch(err => {
                    if (!response.ok) throw new Error('Error HTTP ' + response.status);
                    throw err;
                });
            })
            .then(data => {
                if (data.ok) {
                    document.getElementById('vf-stats-enviados').textContent = data.stats.enviados || 0;
                    document.getElementById('vf-stats-pendientes').textContent = data.stats.pendientes || 0;
                    document.getElementById('vf-stats-errores').textContent = data.stats.errores || 0;
                } else {
                    throw new Error(data.error || 'Error desconocido');
                }
            })
            .catch(err => {
                console.error('Error cargando VeriFactu:', err);
            });
    }


    // Restaurar pestaña activa al cargar la página
    document.addEventListener('DOMContentLoaded', () => {
        const savedTab = localStorage.getItem('config_active_tab');
        
        // Si hay parámetros de filtros de logs, forzar la pestaña de logs
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('fechaInicio') || urlParams.has('fechaFin')) {
            switchConfigTab('logs');
        } else if (savedTab && document.getElementById('panel-' + savedTab)) {
            switchConfigTab(savedTab);
        }
    });

    /**
     * Validación del formulario unificado
     */
    function validarConfiguracion(e) {
        const seccion = document.getElementById('seccion_activa').value;
        
        if (seccion === 'FACTURACION') {
            const nifEl = document.getElementById('empresa_nif');
            if (nifEl) {
                const nif = nifEl.value.trim();
                if (nif !== '' && typeof validarDocumento === 'function' && !validarDocumento(nif)) {
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
                if (tel !== '' && typeof validarTelefono === 'function' && !validarTelefono(tel)) {
                    showCustomAlert("<?php echo L('client_js_invalid_phone'); ?>", "<?php echo L('config_js_invalid_phone_body'); ?>", 'warning');
                    e.preventDefault();
                    return false;
                }
            }
        }
        
        if (seccion === 'VERIFACTU') {
            // Validaciones específicas para verifactu si fuera necesario
        }
        
        return true;
    }
</script>