<?php
require_once __DIR__ . '/../model/DBPDO.php';
$stmt = DBPDO::ejecutarConsulta('SELECT id, id_venta, estado, intentos, ultimo_error, creado_en FROM cola_envios ORDER BY id DESC LIMIT 10');
echo "ID | Venta | Estado | Intentos | Error | Fecha\n";
echo "---|-------|--------|----------|-------|------\n";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "{$row['id']} | {$row['id_venta']} | {$row['estado']} | {$row['intentos']} | " . substr($row['ultimo_error'], 0, 50) . " | {$row['creado_en']}\n";
}
