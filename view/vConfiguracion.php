</header>
<div class="main-full p-24">
    <div class="section-header container-wider ai-center jc-space-between">
        <div class="section-title">
            <h1 class="d-flex ai-center gap-12"><i class="fa-solid fa-gears text-accent"></i> Ajustes del Sistema</h1>
            <p>Configuración global de datos fiscales, contacto, redes sociales y tickets.</p>
        </div>
        <a href="index.php?irDashboard=1" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="container-wider">
        <?php if ($showSuccess): ?>
            <div class="p-16 mb-24 bg-green-light text-green font-bold br-12 border-2 border-green d-flex ai-center gap-12">
                <i class="fa-solid fa-circle-check fs-20"></i>
                La configuración se ha guardado correctamente. Los cambios ya están activos.
            </div>
        <?php endif; ?>

        <?php if (!empty($aErrores)): ?>
            <div class="p-16 mb-24 bg-red-light text-red font-bold br-12 border-2 border-red mt-16">
                <i class="fa-solid fa-triangle-exclamation"></i> Revisa los errores del formulario.
            </div>
        <?php endif; ?>

        <form method="post" action="index.php?irConfiguracion=1" class="bg-surface br-16 border-1 shadow-lg p-32">

            <div class="d-grid grid-2 gap-32">

                <!-- COLUMNA 1: Datos Fiscales y Contacto -->
                <div class="d-flex flex-column gap-24">
                    <div class="mb-8">
                        <h3 class="fs-16 font-bold text-accent mb-8"><i class="fa-solid fa-building mr-8"></i> Datos de Facturación</h3>
                        <div class="border-bottom opacity-50"></div>
                    </div>

                    <div class="form-group mb-0">
                        <label class="form-label">Nombre Comercial</label>
                        <input type="text" name="empresa_nombre" class="form-input" value="<?php echo htmlspecialchars($avConfig['empresa_nombre']); ?>" required>
                        <?php if (isset($aErrores['empresa_nombre'])): ?>
                            <span class="form-error"><?php echo $aErrores['empresa_nombre']; ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="d-grid grid-2 gap-16">
                        <div class="form-group mb-0">
                            <label class="form-label">Razón Social</label>
                            <input type="text" name="empresa_razon_social" class="form-input" value="<?php echo htmlspecialchars($avConfig['empresa_razon_social']); ?>">
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">CIF / NIF</label>
                            <input type="text" name="empresa_nif" class="form-input font-mono" value="<?php echo htmlspecialchars($avConfig['empresa_nif']); ?>">
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label class="form-label">Dirección Completa</label>
                        <input type="text" name="empresa_direccion" class="form-input" value="<?php echo htmlspecialchars($avConfig['empresa_direccion']); ?>" placeholder="C/ Calle 123, 28000 Madrid">
                    </div>

                    <div class="form-group mb-0">
                        <label class="form-label">Datos del Registro Mercantil <span class="text-muted fs-11 fw-normal">(opcional, útil para facturas)</span></label>
                        <textarea name="empresa_registro" class="form-input" rows="2" placeholder="Reg. Mercantil de..."><?php echo htmlspecialchars($avConfig['empresa_registro']); ?></textarea>
                    </div>

                    <div class="mt-8 mb-8">
                        <h3 class="fs-16 font-bold text-accent mb-8"><i class="fa-solid fa-address-book mr-8"></i> Contacto y Web</h3>
                        <div class="border-bottom opacity-50"></div>
                    </div>

                    <div class="d-grid grid-2 gap-16">
                        <div class="form-group mb-0">
                            <label class="form-label">Teléfono</label>
                            <div class="search-input-wrap">
                                <i class="fa-solid fa-phone"></i>
                                <input type="text" name="empresa_telefono" class="form-input pl-36" value="<?php echo htmlspecialchars($avConfig['empresa_telefono']); ?>">
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Email Principal</label>
                            <div class="search-input-wrap">
                                <i class="fa-solid fa-envelope"></i>
                                <input type="email" name="empresa_email" class="form-input pl-36" value="<?php echo htmlspecialchars($avConfig['empresa_email']); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label class="form-label">Sitio Web</label>
                        <div class="search-input-wrap">
                            <i class="fa-solid fa-globe"></i>
                            <input type="text" name="empresa_web" class="form-input pl-36" value="<?php echo htmlspecialchars($avConfig['empresa_web']); ?>">
                        </div>
                    </div>
                </div>

                <!-- COLUMNA 2: Tickets y Redes Sociales -->
                <div class="d-flex flex-column gap-24">

                    <div class="mb-8">
                        <h3 class="fs-16 font-bold text-accent mb-8"><i class="fa-solid fa-receipt mr-8"></i> Personalización de Tickets</h3>
                        <div class="border-bottom opacity-50"></div>
                    </div>

                    <div class="form-group mb-0">
                        <label class="form-label">Mensaje de Agradecimiento <span class="text-muted fs-11 fw-normal">(Aparece al final del ticket)</span></label>
                        <textarea name="ticket_pie_pagina" class="form-input" rows="3" placeholder="Gracias por su compra..."><?php echo htmlspecialchars($avConfig['ticket_pie_pagina']); ?></textarea>
                    </div>

                    <div class="form-group mb-0">
                        <label class="form-label">Política de Devoluciones / Aviso Legal</label>
                        <textarea name="ticket_politica" class="form-input" rows="4" placeholder="Conserve este ticket..."><?php echo htmlspecialchars($avConfig['ticket_politica']); ?></textarea>
                    </div>

                    <div class="mt-8 mb-8">
                        <h3 class="fs-16 font-bold text-accent mb-8"><i class="fa-solid fa-hashtag mr-8"></i> Redes Sociales</h3>
                        <div class="border-bottom opacity-50"></div>
                        <p class="fs-12 text-muted mt-8">Estos datos se incluirán en los correos electrónicos y PDFs enviados a los clientes si no están vacíos.</p>
                    </div>

                    <div class="d-grid grid-2 gap-16">
                        <div class="form-group mb-0">
                            <label class="form-label">Instagram (@usuario)</label>
                            <div class="search-input-wrap">
                                <i class="fa-brands fa-instagram text-purple"></i>
                                <input type="text" name="social_instagram" class="form-input pl-36" value="<?php echo htmlspecialchars($avConfig['social_instagram']); ?>">
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Facebook (enlace o /usuario)</label>
                            <div class="search-input-wrap">
                                <i class="fa-brands fa-facebook text-blue"></i>
                                <input type="text" name="social_facebook" class="form-input pl-36" value="<?php echo htmlspecialchars($avConfig['social_facebook']); ?>">
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="mt-64 border-top pt-24 d-flex jc-end gap-16">
                <!-- <a href="index.php?irDashboard=1" class="btn-cancel w-auto text-decoration-none d-flex ai-center">Descartar Cambios</a> -->
                <button type="submit" name="guardarConfiguracion" class="btn-save w-auto px-32 fs-14">
                    <i class="fa-solid fa-floppy-disk mr-8"></i> Guardar Configuración
                </button>
            </div>

        </form>
    </div>
</div>