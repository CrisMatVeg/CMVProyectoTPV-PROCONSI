<?php
require_once './config/confDBPDO.php';
try {
    $conexion = new PDO(DSN, USERNAME, PASSWORD);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create Table
    $sql = "CREATE TABLE IF NOT EXISTS categorias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(50) UNIQUE NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        activo TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $conexion->exec($sql);

    // Seed Table
    $seeds = [
        ['audio', 'Audio'],
        ['movil', 'Móvil'],
        ['gaming', 'Gaming'],
        ['informatica', 'Informática'],
        ['cables', 'Cables'],
        ['foto', 'Foto']
    ];

    $stmt = $conexion->prepare("INSERT IGNORE INTO categorias (codigo, nombre) VALUES (?, ?)");
    foreach ($seeds as $seed) {
        $stmt->execute($seed);
    }

    echo "SUCCESS: Table 'categorias' created and seeded.";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
