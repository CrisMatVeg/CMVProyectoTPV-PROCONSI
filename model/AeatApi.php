<?php

/**
 * Clase: AeatApi
 * Cliente cURL para comunicación con los WebServices de la Agencia Tributaria.
 * Gestiona la autenticación por certificado digital y el envío de XMLs.
 */

require_once __DIR__ . '/ConfiguracionPDO.php';
require_once dirname(__DIR__) . '/config/config.php';

class AeatApi
{
    private $config;
    private $endpoint;
    private $timeout = 10;

    public function __construct()
    {
        $this->config = ConfiguracionPDO::obtenerConfiguracion();
        
        // Endpoint VeriFactu (Prioridad a la constante de configuración)
        if (defined('VERIFACTU_URL_ALTA_PRUEBAS')) {
            $this->endpoint = VERIFACTU_URL_ALTA_PRUEBAS;
        } else {
            // Fallback (Evitar https://www1 o https://www2 según petición del usuario)
            $this->endpoint = 'https://prewww1.aeat.es/wlpl/TIKE-CONT/ws/SistemaFacturacion/VerifactuSOAP';
        }
    }

    /**
     * Envía un registro de facturación firmado a la AEAT.
     * @param string $xmlContent Contenido del XML firmado
     * @return array [success => bool, message => string, code => int, raw_response => string]
     */
    public function enviarFactura(string $xmlContent)
    {
        /*
        // Control de Flujo: Verificar si estamos en periodo de espera
        $esperaHasta = (int)ConfiguracionPDO::obtenerValor('verifactu_espera_hasta');
        if (time() < $esperaHasta) {
            $segundosRestantes = $esperaHasta - time();
            return [
                'success' => false,
                'message' => "Control de flujo AEAT: Se debe esperar $segundosRestantes segundos más.",
                'code' => 429
            ];
        }
        */

        $ch = curl_init();

        // Asegurar rutas absolutas para el certificado
        $certPath = defined('VERIFACTU_CERT_PATH') ? VERIFACTU_CERT_PATH : ($this->config['verifactu_cert_path'] ?? '');
        $certPass = defined('VERIFACTU_CERT_PASS') ? VERIFACTU_CERT_PASS : ($this->config['verifactu_cert_pass'] ?? '');

        if (!empty($certPath)) {
            $certPath = str_replace('\\', '/', $certPath); // Normalizar separadores
            if (!file_exists($certPath)) {
                return ['success' => false, 'message' => "Certificado no encontrado en la ruta absoluta: $certPath", 'code' => 404];
            }
        }

        // Prepara el Sobre SOAP reglamentario "TODO EN UNO" (Sugerido por usuario)
        if (strpos($this->endpoint, 'VerifactuSOAP') !== false) {
            // Eliminar declaración XML si ya existe para evitar duplicados en el Body
            $cleanXml = preg_replace('/<\?xml.*?\?>/is', '', trim($xmlContent));
            
            $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Body>' . $cleanXml . '</soapenv:Body>
</soapenv:Envelope>';
        }

        curl_setopt($ch, CURLOPT_URL, $this->endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlContent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        
        $contentType = 'text/xml; charset=utf-8';
        $headers = [
            'Content-Type: ' . $contentType,
            'Accept: text/xml',
            'SOAPAction: ""'
        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (!empty($certPath)) {
            curl_setopt($ch, CURLOPT_SSLCERT, $certPath);
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $certPass);
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, "P12"); // Obligatorio para .pfx o .p12
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($response === false) {
            $error_mensaje = curl_error($ch);
            $error_codigo = curl_errno($ch);
            curl_close($ch);
            
            error_log("Error cURL ($error_codigo): $error_mensaje para endpoint $this->endpoint");
            return [
                'success' => false, 
                'message' => "Error de conexión cURL ($error_codigo): $error_mensaje", 
                'code' => $error_codigo
            ];
        }

        curl_close($ch);

        // Detectar fallos SOAP incluso con HTTP 200
        if ($httpCode === 200 && strpos($response, '<env:Fault>') !== false) {
            return [
                'success' => false,
                'message' => "Error SOAP detectado: " . $this->extraerMensajeErrorSOAP($response),
                'code' => 500,
                'raw_response' => $response
            ];
        }

        // Eliminar bloque redundante y erróneo

        return $this->procesarRespuesta($response, $httpCode);
    }

    /**
     * Extrae el mensaje de error de un XML de fallo de la AEAT.
     */
    private function extraerMensajeErrorSOAP(string $xml)
    {
        if (preg_match('/<faultstring>(.*)<\/faultstring>/s', $xml, $matches)) {
            return htmlspecialchars_decode($matches[1]);
        }
        return "Fallo SOAP desconocido.";
    }

    /**
     * Procesa la respuesta XML de la AEAT.
     */
    private function procesarRespuesta($response, $httpCode)
    {
        if ($httpCode >= 400) {
            return [
                'success' => false,
                'message' => "Servidor AEAT devolvió error HTTP $httpCode",
                'code' => $httpCode,
                'raw_response' => $response
            ];
        }

        // Parsear XML de respuesta
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($response);
        
        if ($xml === false) {
            return [
                'success' => false,
                'message' => "No se pudo parsear la respuesta XML de la AEAT",
                'code' => $httpCode,
                'raw_response' => $response
            ];
        }

        // Buscar el estado en el XML (SuministroLRResponse -> ResumenData -> EstadoEnvio)
        // O dependiendo del esquema: RespuestaLinea -> EstadoRegistro
        $namespaces = $xml->getNamespaces(true);
        $xml->registerXPathNamespace('r', 'https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/RespuestaSuministro.xsd');
        
        // 1. Extraer Control de Flujo (TiempoEsperaEnvio)
        $tiempos = $xml->xpath('//r:TiempoEsperaEnvio');
        if (!empty($tiempos)) {
            $esperaSegundos = (int)$tiempos[0];
            if ($esperaSegundos > 0) {
                // Limitamos la espera en desarrollo para no bloquear al usuario (Max 10 seg)
                $segundosReales = min($esperaSegundos, 10);
                $hasta = time() + $segundosReales;
                ConfiguracionPDO::actualizarValor('verifactu_espera_hasta', (string)$hasta);
            }
        }

        // 2. Extraer estado de la operación
        $estados = $xml->xpath('//r:EstadoRegistro');
        if (!empty($estados)) {
            $estado = (string)$estados[0];
            if ($estado === 'Correcto') {
                return [
                    'success' => true,
                    'message' => "Recibido satisfactoriamente por la AEAT",
                    'code' => $httpCode
                ];
            } else {
                // Buscar errores detallados
                $errores = $xml->xpath('//r:DescripcionErrorRegistro');
                $msgError = !empty($errores) ? (string)$errores[0] : $estado;
                
                return [
                    'success' => false,
                    'message' => "La AEAT rechazó el registro: $msgError",
                    'code' => $httpCode,
                    'raw_response' => $response
                ];
            }
        }

        // Si no encontramos el campo esperado pero el código es 200, asumimos revisión manual necesaria
        return [
            'success' => true,
            'message' => "Respuesta recibida (Código $httpCode). Verificar logs de respuesta.",
            'code' => $httpCode,
            'raw_response' => $response
        ];
    }
}
