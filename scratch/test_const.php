<?php
require_once __DIR__ . '/../model/AeatApi.php';
$api = new AeatApi();
echo "Constante VERIFACTU_CERT_PATH: " . (defined('VERIFACTU_CERT_PATH') ? VERIFACTU_CERT_PATH : 'NO DEFINIDA') . "\n";
echo "Archivo existe: " . (file_exists(VERIFACTU_CERT_PATH) ? 'SI' : 'NO') . "\n";
?>
