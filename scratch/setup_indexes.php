<?php
require_once '../config/confDBPDO.php';
require_once '../model/DBPDO.php';

echo "<h1>Optimizador de Base de Datos</h1>";
echo "<p>Aplicando índices de rendimiento...</p>";

try {
    echo "<li>Añadiendo índice de fecha... ";
    try {
        DBPDO::ejecutarConsulta("ALTER TABLE ventas ADD INDEX idx_ventas_fecha (fecha)", []);
        echo "<span style='color:green'>OK</span>";
    } catch (Exception $e) {
        echo "<span style='color:orange'>Ya existe o no necesario</span>";
    }
    echo "</li>";

    echo "<li>Añadiendo índice de rendimiento compuesto... ";
    try {
        DBPDO::ejecutarConsulta("ALTER TABLE ventas ADD INDEX idx_ventas_perf (estado, metodo_pago, fecha)", []);
        echo "<span style='color:green'>OK</span>";
    } catch (Exception $e) {
        echo "<span style='color:orange'>Ya existe o no necesario</span>";
    }
    echo "</li>";

    echo "<li>Añadiendo índice de productos... ";
    try {
        DBPDO::ejecutarConsulta("ALTER TABLE productos ADD INDEX idx_prod_cat_ref (categoria, referencia)", []);
        echo "<span style='color:green'>OK</span>";
    } catch (Exception $e) {
        echo "<span style='color:orange'>Ya existe o no necesario</span>";
    }
    echo "</li>";

    echo "<h2>¡Optimización Completada!</h2>";
    echo "<p>Ya puedes cerrar esta pestaña y volver al panel de analítica.</p>";

} catch (Exception $e) {
    echo "<h2 style='color:red'>Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
