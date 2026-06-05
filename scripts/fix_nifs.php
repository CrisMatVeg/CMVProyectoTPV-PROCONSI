<?php
require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/../model/DBPDO.php';
require_once __DIR__ . '/../model/Validador.php';

/**
 * Script: fix_nifs.php
 * Proposito: Corregir NIFs invalidos recalculando la letra de control correcta.
 */

function calcularLetraDNI($dni) {
    if (!preg_match('/^[0-9]+$/', $dni)) return null;
    $letras = "TRWAGMYFPDXBNJZSQVHLCKE";
    return $letras[intval($dni) % 23];
}

try {
    $db = DBPDO::getPDO();
    $db->beginTransaction();

    $stmt = $db->query("SELECT id, nombre, nif FROM clientes WHERE nif IS NOT NULL AND nif != '' AND fecha_baja IS NULL");
    $actualizados = 0;
    $errores = 0;

    echo "Analizando NIFs...\n";

    while ($c = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!Validador::validarDocumento($c['nif'])) {
            $nifOriginal = strtoupper(trim($c['nif']));
            $nuevoNif = $nifOriginal;

            // Caso 1: DNI (8 numeros + 1 letra mal)
            if (preg_match('/^([0-9]{1,8})[A-Z]$/i', $nifOriginal, $matches)) {
                $numeros = str_pad($matches[1], 8, "0", STR_PAD_LEFT);
                $letraCorrecta = calcularLetraDNI($numeros);
                if ($letraCorrecta) {
                    $nuevoNif = $numeros . $letraCorrecta;
                }
            } 
            // Caso 2: Solo numeros (añadir letra)
            elseif (preg_match('/^[0-9]{1,8}$/', $nifOriginal)) {
                $numeros = str_pad($nifOriginal, 8, "0", STR_PAD_LEFT);
                $letraCorrecta = calcularLetraDNI($numeros);
                if ($letraCorrecta) {
                    $nuevoNif = $numeros . $letraCorrecta;
                }
            }

            if ($nuevoNif !== $nifOriginal && Validador::validarDocumento($nuevoNif)) {
                $upd = $db->prepare("UPDATE clientes SET nif = :nif WHERE id = :id");
                $upd->execute([':nif' => $nuevoNif, ':id' => $c['id']]);
                echo "CORREGIDO: [{$c['id']}] {$c['nombre']} | {$nifOriginal} -> {$nuevoNif}\n";
                $actualizados++;
            } else {
                echo "SKIPPED: [{$c['id']}] {$c['nombre']} | {$nifOriginal} (No se pudo auto-corregir con seguridad)\n";
                $errores++;
            }
        }
    }

    $db->commit();
    echo "\nRESUMEN: $actualizados corregidos, $errores requieren atencion manual.\n";

} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
