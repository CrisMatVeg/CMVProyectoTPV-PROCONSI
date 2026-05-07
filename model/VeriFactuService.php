<?php

/**
 * Clase: VeriFactuService
 * Motor central de facturación VeriFactu.
 * Genera el XML reglamentario, calcula el encadenamiento y coordina la firma.
 */

require_once __DIR__ . '/ConfiguracionPDO.php';
require_once __DIR__ . '/FirmaService.php';
require_once dirname(__DIR__) . '/config/config.php';

class VeriFactuService
{
    private $config;
    private $firma;

    // Constantes de Namespaces para evitar redundancia
    const NS_LR = "https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroLR.xsd";
    const NS_INFO = "https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroInformacion.xsd";

    public function __construct()
    {
        $this->config = ConfiguracionPDO::obtenerConfiguracion();
        $this->firma = new FirmaService();
    }

    /**
     * Genera, firma y almacena el registro de alta de una factura/ticket.
     * @param array $datos Venta + Hash Anterior
     * @return array ['ok' => bool, 'path' => string, 'hash' => string, 'error' => string]
     */
    public function procesarAlta(array $datos)
    {
        try {
            // 1. Crear el árbol DOM inicial
            $doc = new DOMDocument('1.0', 'UTF-8');
            $doc->formatOutput = true;

            // Namespaces oficiales (Declarados solo en la raíz)
            $nsLR = "https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroLR.xsd";
            $nsInfo = "https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroInformacion.xsd";

            $root = $doc->createElementNS($nsLR, "sfLR:RegFactuSistemaFacturacion");
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:sf', $nsInfo);
            $doc->appendChild($root);

            // 2. Cabecera
            $this->addCabecera($doc, $root, ($datos['incidencia'] ?? 'N') === 'S');

            // 3. Registro Factura
            $registroNode = $doc->createElementNS($nsLR, "sfLR:RegistroFactura");
            $altaNode = $doc->createElementNS($nsInfo, "sf:RegistroAlta");
            $registroNode->appendChild($altaNode);
            $root->appendChild($registroNode);

            $this->buildRegistroAlta($doc, $altaNode, $datos);

            return $this->finalizarYGuardar($doc, $datos, "Alta");

        } catch (Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Genera, firma y almacena el registro de anulación de una factura/ticket.
     */
    public function procesarAnulacion(array $datos)
    {
        try {
            $doc = new DOMDocument('1.0', 'UTF-8');
            $doc->formatOutput = true;

            $nsLR = "https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroLR.xsd";
            $nsInfo = "https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroInformacion.xsd";

            $root = $doc->createElementNS($nsLR, "sfLR:RegFactuSistemaFacturacion");
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:sf', $nsInfo);
            $doc->appendChild($root);

            $this->addCabecera($doc, $root, ($datos['incidencia'] ?? 'N') === 'S');

            $registroNode = $doc->createElementNS($nsLR, "sfLR:RegistroFactura");
            $anulNode = $doc->createElementNS($nsInfo, "sf:RegistroAnulacion");
            $registroNode->appendChild($anulNode);
            $root->appendChild($registroNode);

            $this->buildRegistroAnulacion($doc, $anulNode, $datos);

            return $this->finalizarYGuardar($doc, $datos, "Anulacion");

        } catch (Exception $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Valida, firma y guarda el documento XML generado.
     */
    private function finalizarYGuardar(DOMDocument $doc, array $datos, string $prefijo)
    {
        // 4. Validación XSD (Pre-firma)
        $xsdPath = dirname(__DIR__) . '/schemas/SuministroLR.xsd';
        $validation = $this->firma->validarEsquema($doc, $xsdPath);
        if (!$validation['ok']) {
            $debugPath = dirname(__DIR__) . '/storage/debug/failed_' . time() . '.xml';
            if (!is_dir(dirname($debugPath))) mkdir(dirname($debugPath), 0777, true);
            file_put_contents($debugPath, $doc->saveXML());
            error_log("VeriFactu Validation Error. XML saved to $debugPath. Errors: " . implode(", ", $validation['errors']));
            throw new Exception("Error de validación XSD antes de firmar ($prefijo): " . implode(", ", $validation['errors']));
        }

        // 5. Firma Digital
        $certPath = defined('VERIFACTU_CERT_PATH') ? VERIFACTU_CERT_PATH : ($this->config['verifactu_cert_path'] ?? '');
        $certPass = defined('VERIFACTU_CERT_PASS') ? VERIFACTU_CERT_PASS : ($this->config['verifactu_cert_pass'] ?? '1234');

        // Normalizar ruta para evitar problemas con slashes en Windows
        $certPath = str_replace('\\', '/', $certPath);

        if (!empty($certPath) && file_exists($certPath)) {
            try {
                $xmlAntes = $doc->saveXML();
                $sizeAntes = strlen($xmlAntes);

                $this->firma->cargarCertificadoP12($certPath, $certPass);
                $doc = $this->firma->firmarXAdES($doc);
                
                $xmlDespues = $doc->saveXML();
                $sizeDespues = strlen($xmlDespues);

                // Verificación de seguridad: ¿Se ha añadido el nodo Signature?
                if ($doc->getElementsByTagNameNS('http://www.w3.org/2000/09/xmldsig#', 'Signature')->length === 0) {
                    throw new Exception("La firma XAdES falló: El documento no contiene el bloque <ds:Signature>.");
                }

                error_log("VeriFactu Audit: XML firmado correctamente. Tamaño antes: $sizeAntes, después: $sizeDespues (Diff: " . ($sizeDespues - $sizeAntes) . " bytes)");
            } catch (Exception $e) {
                error_log("Error crítico en firma VeriFactu: " . $e->getMessage());
                throw new Exception("Error al firmar el documento: " . $e->getMessage());
            }
        } else {
            error_log("VeriFactu: Saltando firma porque el certificado no es accesible ($certPath)");
        }

        // 6. Almacenamiento
        $fileName = $prefijo . "_" . str_replace(['/', '-'], '_', $datos['numero_serie']) . "_" . time() . ".xml";
        $savePath = dirname(__DIR__) . '/storage/verifactu/xml/' . $fileName;
        
        if (!is_dir(dirname($savePath))) {
            mkdir(dirname($savePath), 0777, true);
        }

        // Limpieza final de namespaces redundantes si PHP los inyectó
        $xmlOutput = $doc->saveXML();
        $xmlOutput = str_replace(' xmlns:sf="' . self::NS_INFO . '"', '', $xmlOutput);
        // Volver a poner el del nodo raíz que sí debe estar
        $xmlOutput = str_replace('<sfLR:RegFactuSistemaFacturacion', '<sfLR:RegFactuSistemaFacturacion xmlns:sf="' . self::NS_INFO . '"', $xmlOutput);

        file_put_contents($savePath, $xmlOutput);

        return [
            'ok' => true,
            'path' => $savePath,
            'hash' => $datos['hash_actual'],
            'nombre_archivo' => $fileName,
            'doc' => $doc // Retornar el objeto DOM por si se necesita procesar más
        ];
    }

    /**
     * Genera un XML de lote combinando varios XMLs individuales.
     * @param array $rutasXmls Lista de rutas a archivos XML individuales.
     * @return string Contenido del XML de lote firmado.
     */
    public function generarXmlLote(array $rutasXmls): string
    {
        if (empty($rutasXmls)) throw new Exception("No hay XMLs para procesar en el lote.");

        $docLote = new DOMDocument('1.0', 'UTF-8');
        $docLote->formatOutput = true;

        $nsLR = "https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroLR.xsd";
        $nsInfo = "https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroInformacion.xsd";

        $root = $docLote->createElementNS($nsLR, "sfLR:RegFactuSistemaFacturacion");
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:sf', $nsInfo);
        $docLote->appendChild($root);

        // 1. Añadir Cabecera (con incidencia si alguno de los registros falló antes)
        $this->addCabecera($docLote, $root, $datos['incidencia'] ?? false);

        // 2. Añadir Registros
        foreach ($rutasXmls as $path) {
            if (!file_exists($path)) continue;

            $tempDoc = new DOMDocument();
            $tempDoc->load($path);

            $registros = $tempDoc->getElementsByTagNameNS($nsLR, 'RegistroFactura');
            if ($registros->length > 0) {
                $node = $docLote->importNode($registros->item(0), true);
                
                // Limpieza: Si el registro individual ya venía con firma, la AEAT suele preferir 
                // una única firma para el lote entero. Eliminamos ds:Signature si existe en el nodo.
                $signatures = $node->getElementsByTagNameNS('http://www.w3.org/2000/09/xmldsig#', 'Signature');
                while ($signatures->length > 0) {
                    $node->removeChild($signatures->item(0));
                }

                $root->appendChild($node);
            }
        }

        // 3. Firma del Lote completo
        $certPath = defined('VERIFACTU_CERT_PATH') ? VERIFACTU_CERT_PATH : ($this->config['verifactu_cert_path'] ?? '');
        $certPass = defined('VERIFACTU_CERT_PASS') ? VERIFACTU_CERT_PASS : ($this->config['verifactu_cert_pass'] ?? '1234');
        $certPath = str_replace('\\', '/', $certPath);

        if (!empty($certPath) && file_exists($certPath)) {
            $this->firma->cargarCertificadoP12($certPath, $certPass);
            $docLote = $this->firma->firmarXAdES($docLote);
        }

        // 4. Limpieza de namespaces redundantes
        $xmlOutput = $docLote->saveXML();
        $xmlOutput = str_replace(' xmlns:sf="' . self::NS_INFO . '"', '', $xmlOutput);
        $xmlOutput = str_replace('<sfLR:RegFactuSistemaFacturacion', '<sfLR:RegFactuSistemaFacturacion xmlns:sf="' . self::NS_INFO . '"', $xmlOutput);

        return $xmlOutput;
    }



    private function addCabecera(DOMDocument $doc, DOMElement $parent, bool $hayIncidencia = false)
    {
        $nsLR = "https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroLR.xsd";
        $nsInfo = "https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroInformacion.xsd";

        $cabecera = $doc->createElementNS($nsLR, "sfLR:Cabecera");
        
        $obligado = $doc->createElementNS($nsInfo, "sf:ObligadoEmision");
        $obligado->appendChild($doc->createElementNS($nsInfo, "sf:NombreRazon", defined('EMPRESA_RAZON_SOCIAL') ? EMPRESA_RAZON_SOCIAL : ($this->config['empresa_nombre'] ?? 'Empresa Test')));
        $obligado->appendChild($doc->createElementNS($nsInfo, "sf:NIF", defined('EMPRESA_CIF') ? EMPRESA_CIF : ($this->config['empresa_nif'] ?? '00000000T')));
        $cabecera->appendChild($obligado);
        
        $remision = $doc->createElementNS($nsInfo, "sf:RemisionVoluntaria");
        $remision->appendChild($doc->createElementNS($nsInfo, "sf:Incidencia", $hayIncidencia ? "S" : "N"));
        $cabecera->appendChild($remision);
        
        $parent->appendChild($cabecera);
    }

    private function buildRegistroAlta(DOMDocument $doc, DOMElement $alta, array $datos)
    {
        $nsInfo = "https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroInformacion.xsd";

        $alta->appendChild($doc->createElementNS($nsInfo, "sf:IDVersion", "1.0"));
        
        $idFactura = $doc->createElementNS($nsInfo, "sf:IDFactura");
        $idFactura->appendChild($doc->createElementNS($nsInfo, "sf:IDEmisorFactura", defined('EMPRESA_CIF') ? EMPRESA_CIF : ($this->config['empresa_nif'] ?? '00000000T')));
        $idFactura->appendChild($doc->createElementNS($nsInfo, "sf:NumSerieFactura", $datos['numero_serie']));
        $idFactura->appendChild($doc->createElementNS($nsInfo, "sf:FechaExpedicionFactura", $datos['fecha_expedicion']));
        $alta->appendChild($idFactura);

        // NombreRazonEmisor (Obligatorio en XSD después de IDFactura)
        $razonEmisor = defined('EMPRESA_RAZON_SOCIAL') ? EMPRESA_RAZON_SOCIAL : ($this->config['empresa_nombre'] ?? 'Empresa S.L.');
        $alta->appendChild($doc->createElementNS($nsInfo, "sf:NombreRazonEmisor", $razonEmisor));
        // Bloque de Subsanación/Rechazo (Ajustado a normativa AEAT)
        if (($datos['subsanacion'] ?? 'N') === 'S') {
            $alta->appendChild($doc->createElementNS($nsInfo, "sf:Subsanacion", "S"));
            if (($datos['rechazo_previo'] ?? 'N') === 'S') {
                $alta->appendChild($doc->createElementNS($nsInfo, "sf:RechazoPrevio", "X"));
            } else {
                $alta->appendChild($doc->createElementNS($nsInfo, "sf:RechazoPrevio", "N"));
            }
        }

        $tipoFactura = $datos['tipo_factura'] ?? 'F1';
        $alta->appendChild($doc->createElementNS($nsInfo, "sf:TipoFactura", $tipoFactura));

        // Bloque de Rectificativa (obligatorio para R1, R2, R3, R4, R5)
        // XSD: TipoRectificativa y FacturasRectificadas van justo después de TipoFactura
        $esRectificativa = in_array($tipoFactura, ['R1', 'R2', 'R3', 'R4', 'R5']);
        if ($esRectificativa) {
            // 'S' = Por sustitución (la más habitual en TPV), 'I' = Por diferencias
            $alta->appendChild($doc->createElementNS($nsInfo, "sf:TipoRectificativa", $datos['tipo_rectificativa'] ?? 'S'));

            // Referencia a la factura original que se rectifica
            if (!empty($datos['factura_rectificada_serie']) && !empty($datos['factura_rectificada_fecha'])) {
                $facturasRect = $doc->createElementNS($nsInfo, "sf:FacturasRectificadas");
                $idFactRect = $doc->createElementNS($nsInfo, "sf:IDFacturaRectificada");
                $idFactRect->appendChild($doc->createElementNS($nsInfo, "sf:IDEmisorFactura", defined('EMPRESA_CIF') ? EMPRESA_CIF : ($this->config['empresa_nif'] ?? '00000000T')));
                $idFactRect->appendChild($doc->createElementNS($nsInfo, "sf:NumSerieFactura", $datos['factura_rectificada_serie']));
                $idFactRect->appendChild($doc->createElementNS($nsInfo, "sf:FechaExpedicionFactura", $datos['factura_rectificada_fecha']));
                $facturasRect->appendChild($idFactRect);
                $alta->appendChild($facturasRect);
            }

            // ImporteRectificacion: obligatorio para TipoRectificativa='S' (sustitución)
            // Según AEAT: Si la factura no es por sustitución, este bloque NO debe tener valor.
            if (($datos['tipo_rectificativa'] ?? 'S') === 'S') {
                $importeRect = $doc->createElementNS($nsInfo, "sf:ImporteRectificacion");
                $importeRect->appendChild($doc->createElementNS($nsInfo, "sf:BaseRectificada", $this->fmtAmt($datos['base_rectificada'] ?? '0')));
                $importeRect->appendChild($doc->createElementNS($nsInfo, "sf:CuotaRectificada", $this->fmtAmt($datos['cuota_rectificada'] ?? '0')));
                $alta->appendChild($importeRect);
            }
        }

        $descOp = $esRectificativa ? 'Abono/Rectificativa TPV' : 'Venta TPV';
        $alta->appendChild($doc->createElementNS($nsInfo, "sf:DescripcionOperacion", $datos['descripcion'] ?? 'Venta TPV'));

        // [ANTI-1150] Para tickets (F2) de importe elevado, activamos el indicador del Art. 6.1.d
        // En el XSD va después de DescripcionOperacion
        if ($tipoFactura === 'F2') {
            $alta->appendChild($doc->createElementNS($nsInfo, "sf:FacturaSinIdentifDestinatarioArt61d", "S"));
        }

        // Bloque Destinatarios (Obligatorio para F1, F3, R1, R2, R3, R4. Opcional para F2, R5)
        // Para evitar errores 1239, solo lo incluimos si es una factura ordinaria/rectificativa ordinaria
        // y tenemos los datos. En simplificadas (F2, R5) lo omitimos siempre.
        $omitirDestinatario = in_array($tipoFactura, ['F2', 'R5']);
        
        if (!$omitirDestinatario && !empty($datos['destinatario_nif']) && !empty($datos['destinatario_nombre'])) {
            $destinatarios = $doc->createElementNS($nsInfo, "sf:Destinatarios");
            $idDestinatario = $doc->createElementNS($nsInfo, "sf:IDDestinatario");
            $idDestinatario->appendChild($doc->createElementNS($nsInfo, "sf:NombreRazon", $datos['destinatario_nombre']));
            
            $idType = $datos['destinatario_id_type'] ?? '01';
            if ($idType === '01' || empty($idType)) {
                // NIF Español estándar
                $idDestinatario->appendChild($doc->createElementNS($nsInfo, "sf:NIF", $datos['destinatario_nif']));
            } else {
                // Otros tipos (Pasaporte, NIF-IVA, etc.)
                $idOtro = $doc->createElementNS($nsInfo, "sf:IDOtro");
                $idOtro->appendChild($doc->createElementNS($nsInfo, "sf:CodigoPais", $datos['destinatario_pais'] ?? 'ES'));
                $idOtro->appendChild($doc->createElementNS($nsInfo, "sf:IDType", $idType));
                $idOtro->appendChild($doc->createElementNS($nsInfo, "sf:ID", $datos['destinatario_nif']));
                $idDestinatario->appendChild($idOtro);
            }
            
            $destinatarios->appendChild($idDestinatario);
            $alta->appendChild($destinatarios);
        }

        // Desglose
        $desglose = $doc->createElementNS($nsInfo, "sf:Desglose");
        
        $desgloses = $datos['desgloses'] ?? [];
        if (empty($desgloses)) {
            // Fallback: Si no hay desglose detallado, intentamos calcular uno genérico
            $cuotaTotal = $datos['cuota_total'] ?? ($datos['importe_total'] - $datos['base_imponible']);
            $rawRate = 21.00;
            if ($datos['base_imponible'] > 0) {
                $rawRate = ($cuotaTotal / $datos['base_imponible']) * 100;
            }
            
            // Snapping a tipos impositivos estándar de España para evitar Error 1124 (20.98 -> 21.00)
            $standardRates = [21.00, 10.00, 4.00, 0.00];
            $rate = 21.00;
            $minDiff = 999;
            foreach ($standardRates as $r) {
                $diff = abs($rawRate - $r);
                if ($diff < $minDiff) {
                    $minDiff = $diff;
                    $rate = $r;
                }
            }

            $desgloses[] = [
                'base' => $datos['base_imponible'],
                'cuota' => $cuotaTotal,
                'tipo' => $rate
            ];
        }

        $cuotaTotalTotal = 0;
        foreach ($desgloses as $d) {
            $detalle = $doc->createElementNS($nsInfo, "sf:DetalleDesglose");
            $detalle->appendChild($doc->createElementNS($nsInfo, "sf:Impuesto", "01")); // 01 = IVA
            $detalle->appendChild($doc->createElementNS($nsInfo, "sf:ClaveRegimen", "01")); // 01 = General
            $detalle->appendChild($doc->createElementNS($nsInfo, "sf:CalificacionOperacion", "S1")); // S1 = Sujeta y No exenta
            $detalle->appendChild($doc->createElementNS($nsInfo, "sf:TipoImpositivo", $this->fmtAmt($d['tipo'])));
            $detalle->appendChild($doc->createElementNS($nsInfo, "sf:BaseImponibleOimporteNoSujeto", $this->fmtAmt($d['base'])));
            $detalle->appendChild($doc->createElementNS($nsInfo, "sf:CuotaRepercutida", $this->fmtAmt($d['cuota'])));
            
            if (isset($d['cuota_re']) && $d['cuota_re'] > 0) {
                $detalle->appendChild($doc->createElementNS($nsInfo, "sf:TipoRecargoEquivalencia", $this->fmtAmt($d['tipo_re'])));
                $detalle->appendChild($doc->createElementNS($nsInfo, "sf:CuotaRecargoEquivalencia", $this->fmtAmt($d['cuota_re'])));
            }

            $desglose->appendChild($detalle);
            $cuotaTotalTotal += $d['cuota'] + ($d['cuota_re'] ?? 0);
        }
        $alta->appendChild($desglose);

        $alta->appendChild($doc->createElementNS($nsInfo, "sf:CuotaTotal", $this->fmtAmt($cuotaTotalTotal)));
        $alta->appendChild($doc->createElementNS($nsInfo, "sf:ImporteTotal", $this->fmtAmt($datos['importe_total'])));

        // Encadenamiento
        $encadenamiento = $doc->createElementNS($nsInfo, "sf:Encadenamiento");
        if (empty($datos['hash_anterior'])) {
            $encadenamiento->appendChild($doc->createElementNS($nsInfo, "sf:PrimerRegistro", "S"));
        } else {
            $regAnt = $doc->createElementNS($nsInfo, "sf:RegistroAnterior");
            $regAnt->appendChild($doc->createElementNS($nsInfo, "sf:IDEmisorFactura", defined('EMPRESA_CIF') ? EMPRESA_CIF : ($this->config['empresa_nif'] ?? '00000000T')));
            $regAnt->appendChild($doc->createElementNS($nsInfo, "sf:NumSerieFactura", $datos['serie_anterior'] ?? ''));
            $regAnt->appendChild($doc->createElementNS($nsInfo, "sf:FechaExpedicionFactura", $datos['fecha_anterior'] ?? ''));
            $regAnt->appendChild($doc->createElementNS($nsInfo, "sf:Huella", strtoupper($datos['hash_anterior'])));
            $encadenamiento->appendChild($regAnt);
        }
        $alta->appendChild($encadenamiento);

        // Sistema Informático
        $this->addSistemaInformatico($doc, $alta);

        $alta->appendChild($doc->createElementNS($nsInfo, "sf:FechaHoraHusoGenRegistro", $datos['fecha_hora_gen']));

        $alta->appendChild($doc->createElementNS($nsInfo, "sf:TipoHuella", "01"));
        $alta->appendChild($doc->createElementNS($nsInfo, "sf:Huella", strtoupper($datos['hash_actual'])));
    }

    private function buildRegistroAnulacion(DOMDocument $doc, DOMElement $anul, array $datos)
    {
        $nsInfo = "https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroInformacion.xsd";

        $anul->appendChild($doc->createElementNS($nsInfo, "sf:IDVersion", "1.0"));
        
        // Identificación de la factura que se anula
        $idFactura = $doc->createElementNS($nsInfo, "sf:IDFactura");
        $idFactura->appendChild($doc->createElementNS($nsInfo, "sf:IDEmisorFacturaAnulada", defined('EMPRESA_CIF') ? EMPRESA_CIF : ($this->config['empresa_nif'] ?? '00000000T')));
        $idFactura->appendChild($doc->createElementNS($nsInfo, "sf:NumSerieFacturaAnulada", $datos['numero_serie']));
        $idFactura->appendChild($doc->createElementNS($nsInfo, "sf:FechaExpedicionFacturaAnulada", $datos['fecha_expedicion']));
        $anul->appendChild($idFactura);

        // Encadenamiento (continua la cadena global)
        $encadenamiento = $doc->createElementNS($nsInfo, "sf:Encadenamiento");
        if (empty($datos['hash_anterior'])) {
            $encadenamiento->appendChild($doc->createElementNS($nsInfo, "sf:PrimerRegistro", "S"));
        } else {
            $regAnt = $doc->createElementNS($nsInfo, "sf:RegistroAnterior");
            $regAnt->appendChild($doc->createElementNS($nsInfo, "sf:IDEmisorFactura", defined('EMPRESA_CIF') ? EMPRESA_CIF : ($this->config['empresa_nif'] ?? '00000000T')));
            $regAnt->appendChild($doc->createElementNS($nsInfo, "sf:NumSerieFactura", $datos['serie_anterior'] ?? ''));
            $regAnt->appendChild($doc->createElementNS($nsInfo, "sf:FechaExpedicionFactura", $datos['fecha_anterior'] ?? ''));
            $regAnt->appendChild($doc->createElementNS($nsInfo, "sf:Huella", strtoupper($datos['hash_anterior'])));
            $encadenamiento->appendChild($regAnt);
        }
        $anul->appendChild($encadenamiento);

        // Sistema Informático
        $this->addSistemaInformatico($doc, $anul);

        $anul->appendChild($doc->createElementNS($nsInfo, "sf:FechaHoraHusoGenRegistro", $datos['fecha_hora_gen']));
        $anul->appendChild($doc->createElementNS($nsInfo, "sf:TipoHuella", "01"));
        $anul->appendChild($doc->createElementNS($nsInfo, "sf:Huella", strtoupper($datos['hash_actual'])));
    }

    private function addSistemaInformatico(DOMDocument $doc, DOMElement $parent)
    {
        $nsInfo = "https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroInformacion.xsd";
        $sistema = $doc->createElementNS($nsInfo, "sf:SistemaInformatico");
        // Fallback a los datos del emisor si no hay productor específico (VeriFactu requiere NIFs válidos)
        $prodNombre = $this->config['verifactu_productor_nombre'] ?? (defined('EMPRESA_RAZON_SOCIAL') ? EMPRESA_RAZON_SOCIAL : 'CERTIFICADO FISICA PRUEBAS');
        $prodNif    = $this->config['verifactu_productor_nif'] ?? (defined('EMPRESA_CIF') ? EMPRESA_CIF : '99999910G');

        $sistema->appendChild($doc->createElementNS($nsInfo, "sf:NombreRazon", $prodNombre));
        $sistema->appendChild($doc->createElementNS($nsInfo, "sf:NIF", $prodNif));
        $sistema->appendChild($doc->createElementNS($nsInfo, "sf:NombreSistemaInformatico", $this->config['verifactu_nombre_sistema'] ?? 'ElectroBazar TPV'));
        $sistema->appendChild($doc->createElementNS($nsInfo, "sf:IdSistemaInformatico", $this->config['verifactu_id_sistema'] ?? '01'));
        $sistema->appendChild($doc->createElementNS($nsInfo, "sf:Version", $this->config['verifactu_version_sistema'] ?? '1.0.0'));
        $sistema->appendChild($doc->createElementNS($nsInfo, "sf:NumeroInstalacion", $this->config['verifactu_num_instalacion'] ?? 'INST001'));
        $sistema->appendChild($doc->createElementNS($nsInfo, "sf:TipoUsoPosibleSoloVerifactu", "S"));
        $sistema->appendChild($doc->createElementNS($nsInfo, "sf:TipoUsoPosibleMultiOT", "N"));
        $sistema->appendChild($doc->createElementNS($nsInfo, "sf:IndicadorMultiplesOT", "N"));
        $parent->appendChild($sistema);
    }

    /**
     * Formatea importes numéricos para el XML asegurando 2 decimales y punto.
     * Preserva el signo (importante para abonos).
     */
    private function fmtAmt($val): string
    {
        return number_format((float)$val, 2, '.', '');
    }
}
