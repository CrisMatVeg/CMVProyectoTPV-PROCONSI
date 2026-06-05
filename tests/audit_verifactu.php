<?php

/**
 * SCRIPT: audit_verifactu.php
 * Propósito: Verificar la integridad de la cadena de hashes (huellas) de VeriFactu.
 * Detecta si algún registro ha sido modificado, eliminado o si el encadenamiento se ha roto.
 */

require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/../model/DBPDO.php';
require_once __DIR__ . '/../model/VentaPDO.php';

echo "--- INICIO DE AUDITORÍA VERIFACTU ---\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

try {
    $sql = "SELECT * FROM ventas WHERE hash_actual IS NOT NULL ORDER BY id ASC";
    $stmt = DBPDO::ejecutarConsulta($sql);
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Total de registros encontrados con huella fiscal: " . count($ventas) . "\n";
    
    $hashEsperadoAnterior = null;
    $errores = 0;
    $corrupciones = [];

    foreach ($ventas as $index => $venta) {
        $id = $venta['id'];
        $ticket = $venta['numero_ticket'];
        $hashGuardadoAnterior = $venta['hash_anterior'];
        $hashGuardadoActual = $venta['hash_actual'];

        // 1. Verificar encadenamiento (¿El hash_anterior coincide con el hash_actual del previo?)
        if ($hashEsperadoAnterior !== null && $hashGuardadoAnterior !== $hashEsperadoAnterior) {
            $errores++;
            $msg = "❌ [ID $id] Error de Encadenamiento: El hash_anterior no coincide con el registro previo.";
            echo "$msg\n";
            $corrupciones[] = $msg;
        }

        // 2. Recalcular el hash del registro actual para verificar integridad
        $numFormated = VentaPDO::formatTicketNumber($venta['numero_ticket'], $venta['fecha'], $venta['es_factura']);
        $fechaExpedicion = date('d-m-Y', strtotime($venta['fecha']));
        $tipoFactura = $venta['es_factura'] ? 'F1' : 'F2';

        $datosHash = [
            'nif_emisor'       => ConfiguracionPDO::obtenerValor('empresa_nif') ?? '00000000T',
            'numero_serie'     => $numFormated,
            'fecha_expedicion' => $fechaExpedicion,
            'tipo_factura'     => $tipoFactura,
            'cuota_total'      => abs($venta['iva_amt']),
            'importe_total'    => abs($venta['total']),
            'fecha_hora_gen'   => $venta['fecha_hora_gen_fiscal']
        ];
        
        // Recalculamos el hash siguiendo la lógica oficial
        $hashRecalculado = VentaPDO::generarHashVeriFactu($datosHash, $hashGuardadoAnterior);

        if ($hashRecalculado !== $hashGuardadoActual) {
            $errores++;
            $msg = "❌ [ID $id] Corrupción de Datos: El hash_actual guardado no coincide con el cálculo de los datos actuales.";
            echo "$msg\n";
            echo "   -> Guardado:    $hashGuardadoActual\n";
            echo "   -> Recalculado: $hashRecalculado\n";
            $corrupciones[] = $msg;
        }

        // 3. Verificar que el hash_actual tiene el formato correcto antes de pasar al siguiente
        if (!preg_match('/^[A-F0-9]{64}$/', $hashGuardadoActual)) {
            $errores++;
            $msg = "❌ [ID $id] Formato Inválido: El hash_actual no es un SHA256 válido.";
            echo "$msg\n";
            $corrupciones[] = $msg;
        }

        $hashEsperadoAnterior = $hashGuardadoActual;
    }

    if ($errores === 0) {
        echo "\n✅ INTEGRIDAD CONFIRMADA: La cadena de hashes está intacta y bien encadenada.\n";
    } else {
        echo "\n⚠️ AUDITORÍA FALLIDA: Se han encontrado $errores inconsistencias.\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR DURANTE LA AUDITORÍA: " . $e->getMessage() . "\n";
}

echo "\n--- FIN DE AUDITORÍA ---\n";
