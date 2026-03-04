<?php
// Simular sesión para que pase el check de seguridad si es posible, 
// o al menos ver el error de "No autorizado" limpiamente.
session_start();
$_SESSION['usuarioActualTPV'] = true;

ob_start();
include 'api/listarFinancieras.php';
$output = ob_get_clean();

echo "RAW OUTPUT LENGTH: " . strlen($output) . "\n";
echo "RAW OUTPUT HEX: " . bin2hex(substr($output, 0, 50)) . "...\n";

if (str_starts_with($output, "\xef\xbb\xbf")) {
    echo "DETECTED BOM (UTF-8)!!!\n";
}

$firstChar = substr(ltrim($output), 0, 1);
echo "First non-whitespace char: '$firstChar'\n";

$decoded = json_decode($output);
if ($decoded === null) {
    echo "JSON DECODE ERROR: " . json_last_error_msg() . "\n";
    echo "FULL OUTPUT:\n" . $output . "\n";
} else {
    echo "JSON OK\n";
    print_r($decoded);
}
