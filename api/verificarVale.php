<?php
require_once __DIR__ . '/csrf_check.php';
require_once __DIR__ . '/../config/confAPP.php';
require_once __DIR__ . '/../model/DBPDO.php';
require_once __DIR__ . '/../model/ValePDO.php';

// session_start(); // Handled by csrf_check.php
header('Content-Type: application/json');

if (!isset($_SESSION['usuarioActualTPV'])) {
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['codigo'])) {
    echo json_encode(['ok' => false, 'error' => 'Código de vale no proporcionado']);
    exit;
}

$codigo = trim($input['codigo']);

try {
    // Buscar vale y hacer el join con cliente para dar más info útil al frontend
    $sql = "SELECT v.id, v.codigo, v.importe_original, v.importe_restante, v.estado, v.fecha_creacion,
                   c.nombre as cliente_nombre, c.apellidos as cliente_apellidos, c.id as id_cliente
            FROM vales v
            LEFT JOIN clientes c ON v.id_cliente = c.id
            WHERE v.codigo = :codigo";

    $stmt = DBPDO::ejecutarConsulta($sql, [':codigo' => $codigo]);
    $vale = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vale) {
        throw new Exception('El vale introducido no existe.');
    }

    if ($vale['estado'] !== 'activo') {
        throw new Exception("El vale no está activo (Estado actual: {$vale['estado']}).");
    }

    if ((float)$vale['importe_restante'] <= 0) {
        throw new Exception("El vale no tiene saldo disponible.");
    }

    echo json_encode(['ok' => true, 'vale' => $vale]);

} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
