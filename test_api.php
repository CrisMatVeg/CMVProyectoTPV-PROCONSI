<?php
// Mock session
session_start();
$_SESSION['usuarioActualTPV'] = (object) ['id' => 1];

// Include the API
try {
    include 'api/listarFinancieras.php';
} catch (Throwable $e) {
    echo "\nCaught in wrapper: " . $e->getMessage() . "\n";
}
