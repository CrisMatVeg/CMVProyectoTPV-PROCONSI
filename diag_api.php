<?php
ob_start();
include 'api/listarFinancieras.php';
$output = ob_get_clean();
echo "--- RAW OUTPUT START ---\n";
echo $output;
echo "\n--- RAW OUTPUT END ---\n";
echo "JSON valid: " . (json_decode($output) ? "YES" : "NO") . "\n";
if (!json_decode($output)) {
    echo "First 10 chars hex: " . bin2hex(substr($output, 0, 10)) . "\n";
}
