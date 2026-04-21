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
            $this->addCabecera($doc, $root);

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

            $this->addCabecera($doc, $root);

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
            'nombre_archivo' => $fileName
        ];
    }



    private function addCabecera(DOMDocument $doc, DOMElement $parent)
    {
        $nsLR = "https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroLR.xsd";
        $nsInfo = "https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroInformacion.xsd";

        $cabecera = $doc->createElementNS($nsLR, "sfLR:Cabecera");
        
        $obligado = $doc->createElementNS($nsInfo, "sf:ObligadoEmision");
        $obligado->appendChild($doc->createElementNS($nsInfo, "sf:NombreRazon", defined('EMPRESA_RAZON_SOCIAL') ? EMPRESA_RAZON_SOCIAL : ($this->config['empresa_nombre'] ?? 'Empresa Test')));
        $obligado->appendChild($doc->createElementNS($nsInfo, "sf:NIF", defined('EMPRESA_CIF') ? EMPRESA_CIF : ($this->config['empresa_nif'] ?? '00000000T')));
        $cabecera->appendChild($obligado);
        
        $remision = $doc->createElementNS($nsInfo, "sf:RemisionVoluntaria");
        $remision->appendChild($doc->createElementNS($nsInfo, "sf:Incidencia", "N"));
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
        
        $alta->appendChild($doc->createElementNS($nsInfo, "sf:NombreRazonEmisor", defined('EMPRESA_RAZON_SOCIAL') ? EMPRESA_RAZON_SOCIAL : ($this->config['empresa_nombre'] ?? 'Empresa Test')));
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
            // Contiene los importes de la factura ORIGINAL que se rectifica
            $importeRect = $doc->createElementNS($nsInfo, "sf:ImporteRectificacion");
            $importeRect->appendChild($doc->createElementNS($nsInfo, "sf:BaseRectificada", $datos['base_rectificada'] ?? '0'));
            $importeRect->appendChild($doc->createElementNS($nsInfo, "sf:CuotaRectificada", $datos['cuota_rectificada'] ?? '0'));
            $alta->appendChild($importeRect);
        }

        $descOp = $esRectificativa ? 'Abono/Rectificativa TPV' : 'Venta TPV';
        $alta->appendChild($doc->createElementNS($nsInfo, "sf:DescripcionOperacion", $descOp));

        // Bloque Destinatarios (Obligatorio para F1, F3, R1, R2, R3, R4)
        // Según XSD debe ir después de DescripcionOperacion y antes de Desglose
        if (!empty($datos['destinatario_nif']) && !empty($datos['destinatario_nombre'])) {
            $destinatarios = $doc->createElementNS($nsInfo, "sf:Destinatarios");
            $idDestinatario = $doc->createElementNS($nsInfo, "sf:IDDestinatario");
            $idDestinatario->appendChild($doc->createElementNS($nsInfo, "sf:NombreRazon", $datos['destinatario_nombre']));
            $idDestinatario->appendChild($doc->createElementNS($nsInfo, "sf:NIF", $datos['destinatario_nif']));
            $destinatarios->appendChild($idDestinatario);
            $alta->appendChild($destinatarios);
        }

        // Desglose
        $desglose = $doc->createElementNS($nsInfo, "sf:Desglose");
        $detalle = $doc->createElementNS($nsInfo, "sf:DetalleDesglose");
        $detalle->appendChild($doc->createElementNS($nsInfo, "sf:Impuesto", "01"));
        $detalle->appendChild($doc->createElementNS($nsInfo, "sf:ClaveRegimen", "01"));
        $detalle->appendChild($doc->createElementNS($nsInfo, "sf:CalificacionOperacion", "S1")); // S1 = Sujeta y No exenta
        $detalle->appendChild($doc->createElementNS($nsInfo, "sf:TipoImpositivo", "21.00"));
        $detalle->appendChild($doc->createElementNS($nsInfo, "sf:BaseImponibleOimporteNoSujeto", $datos['base_imponible']));
        $detalle->appendChild($doc->createElementNS($nsInfo, "sf:CuotaRepercutida", $datos['cuota_total']));
        $desglose->appendChild($detalle);
        $alta->appendChild($desglose);

        $alta->appendChild($doc->createElementNS($nsInfo, "sf:CuotaTotal", $datos['cuota_total']));
        $alta->appendChild($doc->createElementNS($nsInfo, "sf:ImporteTotal", $datos['importe_total']));

        // Encadenamiento
        $encadenamiento = $doc->createElementNS($nsInfo, "sf:Encadenamiento");
        if (empty($datos['hash_anterior'])) {
            $encadenamiento->appendChild($doc->createElementNS($nsInfo, "sf:PrimerRegistro", "S"));
        } else {
            $regAnt = $doc->createElementNS($nsInfo, "sf:RegistroAnterior");
            $regAnt->appendChild($doc->createElementNS($nsInfo, "sf:IDEmisorFactura", defined('EMPRESA_CIF') ? EMPRESA_CIF : ($this->config['empresa_nif'] ?? '00000000T')));
            $regAnt->appendChild($doc->createElementNS($nsInfo, "sf:NumSerieFactura", $datos['serie_anterior'] ?? ''));
            $regAnt->appendChild($doc->createElementNS($nsInfo, "sf:FechaExpedicionFactura", $datos['fecha_anterior'] ?? ''));
            $regAnt->appendChild($doc->createElementNS($nsInfo, "sf:Huella", $datos['hash_anterior']));
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
            $regAnt->appendChild($doc->createElementNS($nsInfo, "sf:Huella", $datos['hash_anterior']));
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
        $prodNombre = $this->config['verifactu_productor_nombre'] ?? (defined('EMPRESA_RAZON_SOCIAL') ? EMPRESA_RAZON_SOCIAL : 'ElectroBazar Software S.L.');
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
}
