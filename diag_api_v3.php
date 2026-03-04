<?php
// NO llamamos a session_start() aquí para ver si el script lo hace bien.
ob_start();
include 'api/listarFinancieras.php';
$output = ob_get_clean();

echo "--- START ---\n";
echo $output;
echo "\n--- END ---\n";
echo "Length: " . strlen($output) . "\n";
echo "Hex: " . bin2hex(substr($output, 0, 20)) . "\n";
