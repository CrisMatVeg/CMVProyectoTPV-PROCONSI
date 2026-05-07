<?php

/**
 * Clase: VeriFactuErrorService
 * Clasifica los errores devueltos por la AEAT para determinar la acción a seguir.
 */
class VeriFactuErrorService
{
    const CAT_CONEXION      = 'conexion';      // Reintentar automáticamente
    const CAT_RECHAZO       = 'rechazo';       // Error crítico, detener envío, requiere intervención
    const CAT_SUBSANAR      = 'subsanacion';   // Aceptado con errores, requiere corrección y nuevo envío
    const CAT_EXITO         = 'exito';         // El registro ya consta como correcto (ej. duplicado 3000)
    const CAT_ERROR_TECNICO = 'conexion';      // Error interno de parsing → reintento automático

    /** @var array<string, string> */
    private static $mapaErrores = [
        // ── Aceptado con Errores (2xxx) ──────────────────────────────────────
        '2000' => 'El cálculo de la huella suministrada es incorrecta.',
        '2001' => 'El NIF del bloque Destinatarios no está identificado en el censo de la AEAT.',
        '2002' => 'La longitud de huella del registro anterior no cumple con las especificaciones.',
        '2003' => 'El contenido de la huella del registro anterior no cumple con las especificaciones.',
        '2004' => 'El valor del campo FechaHoraHusoGenRegistro debe ser la fecha actual del sistema de la AEAT.',
        '2005' => 'El campo ImporteTotal tiene un valor incorrecto para el desglose suministrado.',
        '2006' => 'El campo CuotaTotal tiene un valor incorrecto para el desglose suministrado.',
        '2007' => 'No debe informarse como primer registro (existen facturas previas).',
        '2008' => 'El valor de la huella del registro anterior debe ser diferente a la huella del actual.',
        '2009' => 'Si el campo Impuesto tiene valor IPSI(02) el campo ClaveRegimen debe cumplimentarse.',

        // ── Rechazo de factura (1xxx) ─────────────────────────────────────────
        '1100' => 'Valor o tipo incorrecto del campo.',
        '1108' => 'El NIF del emisor debe coincidir con el del certificado.',
        '1112' => 'Fecha de expedición superior a la actual.',
        '1124' => 'Tipo impositivo no válido o no reconocido.',
        '1142' => 'Error en el desglose de IVA.',
        '1189' => 'Falta bloque de destinatarios para factura completa (F1).',
        '1239' => 'Error en el bloque Destinatario: NIF inválido o estructura incorrecta.',
        '1262' => 'La longitud de huella no cumple con las especificaciones.',

        // ── Rechazo total del envío (4xxx) ────────────────────────────────────
        '4102' => 'El XML no cumple el esquema. Falta campo obligatorio.',
        '4103' => 'Error inesperado al parsear el XML.',
        '4104' => 'NIF del emisor no identificado.',
        '4107' => 'El NIF no está identificado en el censo de la AEAT.',
        '4108' => 'Error técnico al obtener el certificado.',
        '4112' => 'El titular del certificado no tiene permisos suficientes.',
        '4115' => 'El valor del campo NIF del emisor es incorrecto.',
        '4136' => 'No se ha enviado el nodo RegistroAlta o el anterior no es correcto.',
        '4141' => 'Acceso al sistema VERIFACTU suspendido temporalmente.',

        // ── Duplicados / Permisos (3xxx) ──────────────────────────────────────
        '3000' => 'Registro de facturación duplicado.',
        '3002' => 'No existe el registro de facturación.',
    ];

    /**
     * Clasifica un código de error de la AEAT.
     */
    public static function clasificar(string $codigo): string
    {
        $codigoInt = (int)$codigo;

        // Código 3000 es "Duplicado", lo tratamos como éxito para que no reintente
        if ($codigo === '3000') {
            return self::CAT_EXITO;
        }

        if ($codigoInt >= 2000 && $codigoInt <= 2999) {
            return self::CAT_SUBSANAR;
        }

        // Errores de red/HTTP o fallos técnicos (manejar fuera por código de estado cURL/HTTP)
        // cURL errors (1-99) o HTTP errors (400-599) o errores técnicos (>5000)
        if ($codigo === 'CURL_ERROR' || ($codigoInt >= 1 && $codigoInt <= 99) || ($codigoInt >= 400 && $codigoInt <= 599) || $codigoInt >= 5000) {
            return self::CAT_CONEXION;
        }

        // Bloques 1000-1999 y 4000-4999 son RECHAZO
        return self::CAT_RECHAZO;
    }

    public static function obtenerMensaje(string $codigo): string
    {
        return self::$mapaErrores[$codigo] ?? "Error desconocido ($codigo)";
    }
}
