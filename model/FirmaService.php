<?php

/**
 * Clase: FirmaService
 * Gestiona la firma digital XAdES-BES para documentos VeriFactu.
 * 
 * Requiere la librería xmlseclibs (robrichards/xmlseclibs).
 */

class FirmaService
{
    private $privateKey;
    private $publicKey;
    private $certChain = [];
    private $certificateBase64;

    /**
     * Carga un certificado en formato PKCS#12 (.p12)
     * @param string $path Ruta al archivo del certificado
     * @param string $password Contraseña del certificado
     * @return bool True si se cargó correctamente
     * @throws Exception Si el archivo no existe o la contraseña es incorrecta
     */
    public function cargarCertificadoP12($path, $password)
    {
        if (!file_exists($path)) {
            throw new Exception("El archivo de certificado no existe: $path");
        }

        $pkcs12 = file_get_contents($path);
        $certs = [];

        if (openssl_pkcs12_read($pkcs12, $certs, $password)) {
            $this->privateKey = $certs['pkey'];
            $this->publicKey = $certs['cert'];
            
            if (isset($certs['extracerts'])) {
                $this->certChain = $certs['extracerts'];
            }

            // Almacenamos el certificado base64 limpio (sin cabeceras) para XAdES
            $cleanCert = str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\r", "\n"], '', $this->publicKey);
            $this->certificateBase64 = $cleanCert;
            
            return true;
        } else {
            throw new Exception("No se pudo leer el certificado P12. Verifique la contraseña.");
        }
    }

    /**
     * Firma un objeto DOMDocument siguiendo el estándar XAdES-BES.
     * @param DOMDocument $doc Documento a firmar
     * @return DOMDocument Documento firmado
     * @throws Exception Si no se ha cargado un certificado o hay errores en la firma
     */
    public function firmarXAdES(DOMDocument $doc)
    {
        if (!$this->privateKey || !$this->publicKey) {
            throw new Exception("Debe cargar un certificado antes de firmar.");
        }

        // Cargar autoloader si no se ha cargado (Prio: vendor, Fallback: lib)
        if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
            require_once dirname(__DIR__) . '/vendor/autoload.php';
        }
        
        if (!class_exists('RobRichards\XMLSecLibs\XMLSecurityDSig')) {
            $manualPath = dirname(__DIR__) . '/lib/xmlseclibs/xmlseclibs.php';
            if (file_exists($manualPath)) {
                require_once $manualPath;
            }
        }

        $objDSig = new RobRichards\XMLSecLibs\XMLSecurityDSig();
        $objDSig->setCanonicalMethod(RobRichards\XMLSecLibs\XMLSecurityDSig::C14N);
        $objDSig->addReference(
            $doc->documentElement,
            RobRichards\XMLSecLibs\XMLSecurityDSig::SHA256,
            ['http://www.w3.org/2000/09/xmldsig#enveloped-signature'],
            ['force_uri' => true]
        );

        $objKey = new RobRichards\XMLSecLibs\XMLSecurityKey(RobRichards\XMLSecLibs\XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $objKey->loadKey($this->privateKey);

        // Generar la firma básica (DSig)
        $objDSig->sign($objKey);

        // ── Lógica XAdES-BES ────────────────────────────────────────────────
        // XAdES requiere la estructura QualifyingProperties -> SignedProperties
        
        // Obtener nodo de firma e importarlo al documento actual para evitar WRONG_DOCUMENT_ERR
        $signatureNode = $doc->importNode($objDSig->sigNode, true);
        
        $nsXades = "http://uri.etsi.org/01903/v1.3.2#";
        $idSignature = "Signature-" . bin2hex(random_bytes(4));
        $signatureNode->setAttribute("Id", $idSignature);

        $objectNode = $doc->createElementNS("http://www.w3.org/2000/09/xmldsig#", "ds:Object");
        $qualProperties = $doc->createElementNS($nsXades, "xades:QualifyingProperties");
        $qualProperties->setAttribute("Target", "#" . $idSignature);
        
        $signedProperties = $doc->createElementNS($nsXades, "xades:SignedProperties");
        $signedProperties->setAttribute("Id", "SignedProperties-" . $idSignature);
        
        $signedSigProps = $doc->createElementNS($nsXades, "xades:SignedSignatureProperties");
        
        // SigningTime
        $signingTime = $doc->createElementNS($nsXades, "xades:SigningTime", date('Y-m-d\TH:i:sP'));
        $signedSigProps->appendChild($signingTime);
        
        // SigningCertificate
        $signingCert = $doc->createElementNS($nsXades, "xades:SigningCertificate");
        $certNode = $doc->createElementNS($nsXades, "xades:Cert");
        $certDigest = $doc->createElementNS($nsXades, "xades:CertDigest");
        $digestMethod = $doc->createElementNS("http://www.w3.org/2000/09/xmldsig#", "ds:DigestMethod");
        $digestMethod->setAttribute("Algorithm", "http://www.w3.org/2001/04/xmlenc#sha256");
        $digestValue = $doc->createElementNS("http://www.w3.org/2000/09/xmldsig#", "ds:DigestValue", base64_encode(hash('sha256', base64_decode($this->certificateBase64), true)));
        
        $certDigest->appendChild($digestMethod);
        $certDigest->appendChild($digestValue);
        
        $issuerSerial = $doc->createElementNS($nsXades, "xades:IssuerSerial");
        $x509IssuerName = $doc->createElementNS("http://www.w3.org/2000/09/xmldsig#", "ds:X509IssuerName", $this->getCertIssuerName());
        $x509SerialNumber = $doc->createElementNS("http://www.w3.org/2000/09/xmldsig#", "ds:X509SerialNumber", $this->getCertSerialNumber());
        $issuerSerial->appendChild($x509IssuerName);
        $issuerSerial->appendChild($x509SerialNumber);
        
        $certNode->appendChild($certDigest);
        $certNode->appendChild($issuerSerial);
        $signingCert->appendChild($certNode);
        $signedSigProps->appendChild($signingCert);
        
        $signedProperties->appendChild($signedSigProps);
        $qualProperties->appendChild($signedProperties);
        $objectNode->appendChild($qualProperties);
        $signatureNode->appendChild($objectNode);
        
        // Finalmente añadir al documento
        $doc->documentElement->appendChild($signatureNode);

        // Añadir información del certificado (X509Data)
        $objDSig->add509Cert($this->publicKey, true, false, ['issuerSerial' => true]);

        return $doc;
    }

    /**
     * Valida el documento XML contra un esquema XSD.
     * @param DOMDocument $doc Documento a validar
     * @param string $xsdPath Ruta completa al archivo .xsd
     * @return array ['ok' => bool, 'errors' => array]
     */
    public function validarEsquema(DOMDocument $doc, $xsdPath)
    {
        if (!file_exists($xsdPath)) {
            return ['ok' => false, 'errors' => ["No se encontró el esquema XSD: $xsdPath"]];
        }

        libxml_use_internal_errors(true);
        $isValid = $doc->schemaValidate($xsdPath);
        $errors = [];

        if (!$isValid) {
            foreach (libxml_get_errors() as $error) {
                $errors[] = sprintf("Error %d: %s en línea %d", $error->code, trim($error->message), $error->line);
            }
            libxml_clear_errors();
        }

        return ['ok' => $isValid, 'errors' => $errors];
    }

    /**
     * Obtiene el nombre del emisor del certificado cargado.
     */
    private function getCertIssuerName()
    {
        $certData = openssl_x509_parse($this->publicKey);
        $issuer = $certData['issuer'];
        $parts = [];
        foreach ($issuer as $key => $value) {
            if (is_array($value)) $value = implode(', ', $value);
            $parts[] = "$key=$value";
        }
        return implode(', ', array_reverse($parts));
    }

    /**
     * Obtiene el número de serie del certificado cargado.
     */
    private function getCertSerialNumber()
    {
        $certData = openssl_x509_parse($this->publicKey);
        return $certData['serialNumber'];
    }
}
