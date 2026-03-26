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
}
?>
