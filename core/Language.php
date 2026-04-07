<?php
class Language
{
    private static $lang = 'es';
    private static $translations = [];

    /**
     * Inicializa el sistema de idiomas.
     */
    public static function init($lang = 'es')
    {
        self::$lang = $lang;
        $file = __DIR__ . "/../lang/{$lang}.php";
        if (file_exists($file)) {
            require $file;
            self::$translations = $translations ?? [];
        } else {
            // Revertir a español si no existe
            $fileEs = __DIR__ . '/../lang/es.php';
            if (file_exists($fileEs)) {
                require $fileEs;
                self::$translations = $translations ?? [];
            }
        }
    }

    /**
     * Devuelve la traducción de una clave.
     */
    public static function get($key, $placeholders = [])
    {
        $text = self::$translations[$key] ?? $key;
        if (!empty($placeholders)) {
            $text = str_replace(array_keys($placeholders), array_values($placeholders), $text);
        }
        return $text;
    }
}

/**
 * Función helper global para traducciones.
 * @param string $key Clave de traducción
 * @param bool $isPrint Si es true, devuelve el texto en lugar de imprimirlo
 * @param array $placeholders Array asociativo de marcadores y sus valores (ej: ['{amount}' => '10.00'])
 */
function L($key, $return = false, $placeholders = [])
{
    $text = Language::get($key, $placeholders);
    // Aunque se pida imprimir (isPrint/return=false), devolvemos siempre para que echo L() funcione sin 'null'
    return $text;
}
?>
