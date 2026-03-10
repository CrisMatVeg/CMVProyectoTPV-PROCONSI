<?php

/**
 * API: generarPedidoAuto.php
 * Genera albaranes de compra automáticos para productos bajo stock mínimo.
 */
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../config/confDBPDO.php';
    require_once __DIR__ . '/../model/DBPDO.php';
    require_once __DIR__ . '/../model/ProductoPDO.php';
    require_once __DIR__ . '/../model/Usuario.php';

    session_start();
    if (!isset($_SESSION['usuarioActualTPV']) || $_SESSION['usuarioActualTPV']->getRol() !== 'admin') {
        throw new Exception('No autorizado');
    }

    $idUsuario = $_SESSION['usuarioActualTPV']->getId();

    // 1. Buscar productos bajo stock (excluyendo packs)
    $sql = "SELECT id, id_proveedor, nombre, stock_actual, stock_minimo 
            FROM productos 
            WHERE es_pack = 0 AND activo = 1 AND stock_actual <= stock_minimo";
    $stmt = DBPDO::ejecutarConsulta($sql);
    $bajoStock = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($bajoStock)) {
        echo json_encode(['ok' => true, 'mensaje' => 'No hay productos bajo stock mínimo.', 'pedidos' => 0]);
        exit;
    }

    // 2. Agrupar por proveedor
    $porProveedor = [];
    foreach ($bajoStock as $p) {
        $provId = $p['id_proveedor'] ?: 0; // 0 para genérico/sin proveedor
        if (!isset($porProveedor[$provId])) {
            $porProveedor[$provId] = [];
        }
        $porProveedor[$provId][] = $p;
    }

    $pedidosGenerados = 0;
    $db = DBPDO::getPDO();
    if (!$db) throw new Exception("Error al obtener conexión PDO");

    $db->beginTransaction();

    foreach ($porProveedor as $provId => $productos) {
        if ($provId === 0) continue; // Por ahora no generamos albaranes sin proveedor definido

        // Crear Albarán (Estado: pendiente)
        $sqlAlbaran = "INSERT INTO albaranes_compra (id_proveedor, fecha, estado, total) VALUES (:prov, NOW(), 'pendiente', 0)";
        $stmtAlb = $db->prepare($sqlAlbaran);
        $stmtAlb->execute([':prov' => $provId]);
        $idAlbaran = $db->lastInsertId();

        $totalAlbaran = 0;

        foreach ($productos as $p) {
            // Sugerir cantidad: stock_minimo * 2 o similar
            $cantidadSugerida = ($p['stock_minimo'] * 2);
            if ($cantidadSugerida <= 0) $cantidadSugerida = 10;

            // Buscar precio de proveedor actual
            $prodInfo = ProductoPDO::obtenerProductoPorId($p['id']);
            $costeRef = $prodInfo['precio_proveedor'] ?: $prodInfo['precio_coste'];

            $sqlLinea = "INSERT INTO lineas_compra (id_albaran, id_producto, cantidad, precio_unitario) 
                         VALUES (:alb, :prod, :qty, :price)";
            $stmtLin = $db->prepare($sqlLinea);
            $stmtLin->execute([
                ':alb'   => $idAlbaran,
                ':prod'  => $p['id'],
                ':qty'   => $cantidadSugerida,
                ':price' => $costeRef
            ]);

            $totalAlbaran += ($cantidadSugerida * $costeRef);
        }

        // Actualizar total albarán
        $stmtUpd = $db->prepare("UPDATE albaranes_compra SET total = :total WHERE id = :id");
        $stmtUpd->execute([':total' => $totalAlbaran, ':id' => $idAlbaran]);

        $pedidosGenerados++;
    }

    $db->commit();

    echo json_encode([
        'ok' => true,
        'mensaje' => "Se han generado {$pedidosGenerados} pedidos de compra en estado pendiente.",
        'pedidos' => $pedidosGenerados
    ]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
