<?php
require_once 'model/DBPDO.php';
require_once 'model/CategoriaPDO.php';

// 1. Detectar el código exacto de la categoría Stress_test en productos
$res = DBPDO::ejecutarConsulta("SELECT DISTINCT categoria FROM productos WHERE categoria LIKE '%Stress%' LIMIT 1");
$row = $res->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo "No se encontraron productos con categoría Stress. ¿Seguro que existen?\n";
    exit;
}

$catCode = $row['categoria'];
echo "Código detectado en productos: '$catCode'\n";

// 2. Insertar en la tabla categorias si no existe
$check = DBPDO::ejecutarConsulta("SELECT id FROM categorias WHERE codigo = :code", [':code' => $catCode]);
if (!$check->fetch()) {
    // Usamos SQL directo para evitar que CategoriaPDO::añadir lo pase a minúsculas forzosamente si fuese necesario manteter el case
    DBPDO::ejecutarConsulta("INSERT INTO categorias (codigo, nombre) VALUES (:code, :name)", [
        ':code' => $catCode,
        ':name' => 'Stress Test'
    ]);
    echo "Categoría '$catCode' creada exitosamente.\n";
} else {
    echo "La categoría '$catCode' ya existía en la tabla categorias.\n";
}
?>
