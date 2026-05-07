<?php

class Validador {
    public static function validarDNI_NIE($valor) {
        $valor = strtoupper(trim($valor));
        if (preg_match('/^[0-9]{8}[A-Z]$/i', $valor)) {
            $numeros = substr($valor, 0, 8);
            $letra = substr($valor, -1);
            $valido = "TRWAGMYFPDXBNJZSQVHLCKE";
            return substr($valido, $numeros % 23, 1) == $letra;
        } elseif (preg_match('/^[XYZ][0-9]{7}[A-Z]$/i', $valor)) {
            $letraInicial = substr($valor, 0, 1);
            $numeros = substr($valor, 1, 7);
            $letraFin = substr($valor, -1);
            $reemplazo = ['X' => 0, 'Y' => 1, 'Z' => 2];
            $comprobacion = $reemplazo[$letraInicial] . $numeros;
            $valido = "TRWAGMYFPDXBNJZSQVHLCKE";
            return substr($valido, $comprobacion % 23, 1) == $letraFin;
        }
        return false;
    }

    public static function validarCIF($cif) {
        $cif = strtoupper(trim($cif));
        if (!preg_match('/^[ABCDEFGHJKLMNPQRSUVW][0-9]{7}[A-Z0-9]$/i', $cif)) {
            return false;
        }
        $letras = ['J', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
        $digitos = substr($cif, 1, 7);
        $control = substr($cif, 8, 1);
        $suma = 0;

        for ($i = 0; $i < strlen($digitos); $i++) {
            $digito = (int)$digitos[$i];
            if ($i % 2 === 0) {
                $digito *= 2;
                if ($digito > 9) {
                    $digito = (int)($digito / 10) + ($digito % 10);
                }
            }
            $suma += $digito;
        }

        $dec = ceil($suma / 10) * 10;
        $res = $dec - $suma;
        $digitoControl = ($res == 10) ? 0 : $res;
        $letraControl = $letras[$digitoControl];

        if (is_numeric($control)) {
            return (int)$control === $digitoControl;
        } else {
            return $control === $letraControl;
        }
    }

    public static function validarDocumento($doc) {
        if (empty(trim($doc))) return true; // Let required rules handle empty
        return self::validarDNI_NIE($doc) || self::validarCIF($doc);
    }

    public static function validarTelefono($tel) {
        if (empty(trim($tel))) return true;
        $tel = preg_replace('/\s+/', '', $tel);
        return preg_match('/^(\+34|0034|34)?[6789]\d{8}$|^(\+[1-9]\d{6,14})$/', $tel);
    }

    /**
     * Valida el formato del NIF-IVA (VIES) para operadores intracomunitarios.
     * Debe comenzar por el código ISO del país y tener una longitud razonable.
     */
    public static function validarNifIva($nif, $pais) {
        $nif = strtoupper(trim($nif));
        $pais = strtoupper(trim($pais));
        if (empty($nif) || empty($pais)) return false;

        // Regla básica VeriFactu: El ID debe comenzar por el código de país
        if (!str_starts_with($nif, $pais)) return false;

        $longitudes = [
            'AT' => 11, 'BE' => 12, 'BG' => 11, 'CY' => 11, 'CZ' => 12, 'DE' => 11, 'DK' => 10,
            'EE' => 11, 'EL' => 11, 'ES' => 11, 'FI' => 10, 'FR' => 13, 'HR' => 13, 'HU' => 10,
            'IE' => 10, 'IT' => 13, 'LT' => 14, 'LU' => 10, 'LV' => 13, 'MT' => 10, 'NL' => 14,
            'PL' => 12, 'PT' => 11, 'RO' => 12, 'SE' => 14, 'SI' => 10, 'SK' => 12
        ];

        $len = strlen($nif);
        if (isset($longitudes[$pais])) {
            // Permitimos un margen de +-1 por si incluyen caracteres extra o formatos antiguos
            return ($len >= $longitudes[$pais] - 1 && $len <= $longitudes[$pais] + 1);
        }

        return ($len >= 5 && $len <= 20);
    }
}
?>
