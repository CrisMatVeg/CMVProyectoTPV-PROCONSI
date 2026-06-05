<?php

/**
 * EJECUTOR DE TESTS VeriFactu
 * Lanza los tres suites y muestra un resumen agregado.
 * Ejecución: php tests/run_all_tests.php
 *
 * Para ejecutar individualmente:
 *   php tests/test_error_classifier.php
 *   php tests/test_queue_error_scenarios.php
 *   php tests/test_document_types_validation.php
 */

$suites = [
    'test_error_classifier.php'        => 'VeriFactuErrorService — clasificación de errores',
    'test_queue_error_scenarios.php'   => 'AeatQueueService — escenarios de error y cola',
    'test_document_types_validation.php' => 'Documentos legales — IDs de destinatario y tipos de factura',
];

$linea = str_repeat('=', 70);
$sep   = str_repeat('-', 70);

echo "$linea\n";
echo "  SUITE DE TESTS VERIFACTU\n";
echo "  " . date('Y-m-d H:i:s') . "\n";
echo "$linea\n\n";

$todoOk = true;

foreach ($suites as $file => $desc) {
    $path = __DIR__ . '/' . $file;

    if (!file_exists($path)) {
        echo "[ERROR] Script no encontrado: tests/$file\n\n";
        $todoOk = false;
        continue;
    }

    echo ">>> $desc\n";
    echo "    tests/$file\n";
    echo "$sep\n";

    passthru(PHP_BINARY . ' ' . escapeshellarg($path), $exit);

    echo "$sep\n";
    if ($exit === 0) {
        echo "    [OK] Suite pasada\n\n";
    } else {
        echo "    [FALLO] Suite con errores (exit=$exit)\n\n";
        $todoOk = false;
    }
}

echo "$linea\n";
if ($todoOk) {
    echo "  TODAS LAS SUITES PASARON\n";
} else {
    echo "  UNA O MAS SUITES FALLARON\n";
}
echo "$linea\n";

exit($todoOk ? 0 : 1);
