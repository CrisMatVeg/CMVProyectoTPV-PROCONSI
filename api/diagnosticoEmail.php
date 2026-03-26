<?php
/**
 * Herramienta de Diagnóstico de Correo para XAMPP
 */
header('Content-Type: text/plain; charset=utf-8');

echo "--- DIAGNÓSTICO DE CORREO ELECTRÓNICO ---\n\n";

// 1. Verificar configuración en php.ini
echo "1. Ajustes en php.ini:\n";
echo "   SMTP: " . ini_get('SMTP') . "\n";
echo "   smtp_port: " . ini_get('smtp_port') . "\n";
echo "   sendmail_from: " . ini_get('sendmail_from') . "\n";
echo "   sendmail_path: " . ini_get('sendmail_path') . "\n\n";

// 2. Probar puertos locales comunes
echo "2. Comprobando puertos locales (Relays):\n";
$ports = [25 => 'SMTP/Mercury', 465 => 'SMTPS', 587 => 'MSA'];
foreach ($ports as $port => $name) {
    echo "   Puerto $port ($name): ";
    $connection = @fsockopen('localhost', $port, $errno, $errstr, 2);
    if (is_resource($connection)) {
        echo "¡ABIERTO! (El servidor RESPONDE)\n";
        fclose($connection);
    } else {
        echo "Cerrado / No responde\n";
    }
}
echo "\n";

// 3. Resultado final y consejo
echo "3. Conclusión:\n";
$smtp = ini_get('SMTP');
if ($smtp === 'localhost') {
    $connection = @fsockopen('localhost', 25, $errno, $errstr, 2);
    if (!is_resource($connection)) {
        echo "   ERROR CRÍTICO: PHP está intentando enviar via 'localhost:25' pero NO hay ningún servidor allí.\n";
        echo "   SOLUCIÓN: Abre el panel de XAMPP y pulsa 'START' en el módulo MERCURY.\n";
    } else {
        echo "   El puerto 25 está abierto. Si falla, puede ser un bloqueo del Firewall de Windows o de tu Antivirus.\n";
    }
} else {
    echo "   PHP está configurado para un servidor externo ($smtp). Si este no es correcto, fallará.\n";
}

echo "\n--- Fin del diagnóstico ---\n";
