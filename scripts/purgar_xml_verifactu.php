<?php
/**
 * purgar_xml_verifactu.php
 * Elimina los archivos XML de VeriFactu con más de 90 días de antigüedad.
 * Ejecutar periódicamente desde el cron o manualmente.
 *
 * Uso: php scripts/purgar_xml_verifactu.php
 */

$dir        = __DIR__ . '/../storage/verifactu/xml/';
$diasMaximo = 90;
$limite     = time() - ($diasMaximo * 86400);
$eliminados = 0;
$errores    = 0;

if (!is_dir($dir)) {
    echo "Directorio no existe: $dir\n";
    exit(1);
}

foreach (new DirectoryIterator($dir) as $file) {
    if ($file->isDot() || !$file->isFile()) continue;
    if (strtolower($file->getExtension()) !== 'xml') continue;

    if ($file->getMTime() < $limite) {
        if (unlink($file->getPathname())) {
            $eliminados++;
        } else {
            $errores++;
            error_log("purgar_xml_verifactu: no se pudo eliminar " . $file->getPathname());
        }
    }
}

echo "Purga completada: $eliminados XML eliminados, $errores errores.\n";
