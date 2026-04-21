<?php
/**
 * Archivo de configuración personalizada para VeriFactu y Datos de Empresa.
 * Este archivo contiene credenciales sensibles y URLs de los WebServices.
 */

// Datos del Emisor (Empresa)
define('EMPRESA_CIF', '99999910G');
define('EMPRESA_RAZON_SOCIAL', 'CERTIFICADO FISICA PRUEBAS');
define('EMPRESA_NOMBRE_COMERCIAL', '(VERI*FACTU) CERTIFICADO FISICA PRUEBAS');

// VeriFactu - Certificado Digital
// Nota: Se usa la ruta absoluta detectada en el sistema
define('VERIFACTU_CERT_PATH', 'C:/Users/PracticasSoftware4/Desktop/xampp-nuevo/xampp/htdocs/certs/99999910G_prueba.pfx');
define('VERIFACTU_CERT_PASS', '1234');

// VeriFactu - URLs AEAT
define('VERIFACTU_URL_ALTA_PRUEBAS', 'https://prewww1.aeat.es/wlpl/TIKE-CONT/ws/SistemaFacturacion/VerifactuSOAP');
define('VERIFACTU_URL_QR_PRUEBAS', 'https://prewww2.aeat.es/wlpl/TIKE-CONT/ValidarQR');

// Forzar uso de URL de pruebas proporcionada por el usuario
define('VERIFACTU_MODO_PRUEBAS', true);
