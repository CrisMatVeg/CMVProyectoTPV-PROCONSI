<?php
/**
 * Script: poblar_analitica.php
 * Propósito: Generar el resumen histórico para la nueva tabla de analítica optimizada.
 */
require_once __DIR__ . '/../model/DBPDO.php';
require_once __DIR__ . '/../model/AnaliticaPDO.php';

// Asegurar que la sesión existe para evitar warnings de DBPDO
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Aumentar límites para procesos pesados
set_time_limit(0);
ini_set('memory_limit', '512M');

echo "<html><body style='font-family:sans-serif; padding: 20px;'>";
echo "<h2 style='color: #2563eb;'>Iniciando población de histórico de analítica...</h2>";
echo "<p>Por favor, no cierres esta pestaña hasta que termine.</p>";
echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px; background: #f9f9f9;'>";
flush();

try {
    AnaliticaPDO::poblarHistorico();
    echo "<p style='color: #16a34a; font-weight: bold;'>✅ Listo. Histórico precalculado correctamente.</p>";
} catch (Exception $e) {
    echo "<p style='color: #dc2626; font-weight: bold;'>❌ Error durante el proceso: " . $e->getMessage() . "</p>";
}

echo "</div>";
echo "<p><a href='../index.php?Analitica=1' style='display:inline-block; padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px;'>Volver a Analítica</a></p>";
echo "</body></html>";
