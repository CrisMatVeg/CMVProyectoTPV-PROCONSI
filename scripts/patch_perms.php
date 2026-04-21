<?php
require_once __DIR__ . '/../config/confDBPDO.php';
require_once __DIR__ . '/../model/DBPDO.php';

try {
    // 1. Añadir el permiso si no existe
    $sql = "INSERT IGNORE INTO permisos (clave, descripcion) VALUES ('cerrar_turno', 'Permitir cerrar turnos de caja (arqueos parciales)')";
    DBPDO::ejecutarConsulta($sql);
    
    // 2. Obtener el ID del permiso
    $q = DBPDO::ejecutarConsulta("SELECT id FROM permisos WHERE clave = 'cerrar_turno'");
    $res = $q->fetch(PDO::FETCH_ASSOC);
    $idPermiso = $res['id'];
    
    if ($idPermiso) {
        // 3. Asignar al rol Cajero (ID 2)
        $sqlAsignar = "INSERT IGNORE INTO rol_permisos (id_rol, id_permiso) VALUES (2, :idp)";
        DBPDO::ejecutarConsulta($sqlAsignar, [':idp' => $idPermiso]);
        echo "Permiso 'cerrar_turno' añadido y asignado correctamente al rol cajero.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
