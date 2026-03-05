<?php

/**
 * Clase: PromocionPDO
 * Gestiona la persistencia de las promociones de descuento.
 */

require_once __DIR__ . '/DBPDO.php';

class PromocionPDO
{
    /**
     * Devuelve todas las promociones (para administración).
     */
    public static function listarTodas(): array
    {
        $sql = "SELECT * FROM promociones ORDER BY activo DESC, codigo ASC";
        $q = DBPDO::ejecutarConsulta($sql);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve las promociones activas (para el TPV).
     * Formato adaptado a JavaScript.
     */
    public static function listarActivas(): array
    {
        $hoy = date('Y-m-d');
        $sql = "SELECT * 
                FROM promociones
                WHERE activo = 1
                  AND (fecha_inicio IS NULL OR fecha_inicio <= :hoy)
                  AND (fecha_fin IS NULL OR fecha_fin >= :hoy)";
        $q = DBPDO::ejecutarConsulta($sql, [':hoy' => $hoy]);
        $rows = $q->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id'             => (int)$r['id'],
                'codigo'         => $r['codigo'],          // JS uses p.codigo for coupon matching
                'tipo'           => $r['tipo'],
                'valor'          => (float)$r['valor'],
                'min_subtotal'   => (float)$r['min_subtotal'],
                'bundle_buy_qty' => $r['bundle_buy_qty'] ? (int)$r['bundle_buy_qty'] : null,
                'bundle_pay_qty' => $r['bundle_pay_qty'] ? (int)$r['bundle_pay_qty'] : null,
                'id_producto'    => $r['id_producto'] ? (int)$r['id_producto'] : null,
                'categoria_code' => $r['categoria_code'] ?? null,
                'label'          => $r['descripcion'],
                'solo_socios'    => (int)$r['solo_socios'] === 1,
            ];
        }
        return $out;
    }

    public static function obtenerPorId(int $id): ?array
    {
        $q = DBPDO::ejecutarConsulta("SELECT * FROM promociones WHERE id = :id", [':id' => $id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function añadir(array $datos): void
    {
        $sql = "INSERT INTO promociones
                (codigo, descripcion, tipo, valor, min_subtotal, bundle_buy_qty, bundle_pay_qty, id_producto, categoria_code, activo, solo_socios, fecha_inicio, fecha_fin)
                VALUES (:codigo, :descripcion, :tipo, :valor, :min_subtotal, :bundle_buy_qty, :bundle_pay_qty, :id_producto, :categoria_code, :activo, :solo_socios, :fecha_inicio, :fecha_fin)";
        DBPDO::ejecutarConsulta($sql, [
            ':codigo'          => !empty(trim($datos['codigo'] ?? '')) ? mb_substr(trim($datos['codigo']), 0, 50) : null,
            ':descripcion'     => mb_substr(trim($datos['descripcion']), 0, 255),
            ':tipo'            => in_array($datos['tipo'], ['percent', 'amount', 'bundle', 'fixed_bundle']) ? $datos['tipo'] : 'percent',
            ':valor'           => (float)$datos['valor'],
            ':min_subtotal'    => (float)($datos['min_subtotal'] ?? 0),
            ':bundle_buy_qty'  => isset($datos['bundle_buy_qty']) ? (int)$datos['bundle_buy_qty'] : null,
            ':bundle_pay_qty'  => isset($datos['bundle_pay_qty']) ? (int)$datos['bundle_pay_qty'] : null,
            ':id_producto'     => isset($datos['id_producto']) ? (int)$datos['id_producto'] : null,
            ':categoria_code'  => $datos['categoria_code'] ?? null, // Changed from id_categoria
            ':activo'          => !empty($datos['activo']) ? 1 : 0,
            ':solo_socios'     => !empty($datos['solo_socios']) ? 1 : 0,
            ':fecha_inicio'    => $datos['fecha_inicio'] ?: null,
            ':fecha_fin'       => $datos['fecha_fin'] ?: null,
        ]);
    }

    public static function editar(int $id, array $datos): void
    {
        $sql = "UPDATE promociones SET
                    codigo = :codigo,
                    descripcion = :descripcion,
                    tipo = :tipo,
                    valor = :valor,
                    min_subtotal = :min_subtotal,
                    bundle_buy_qty = :bundle_buy_qty,
                    bundle_pay_qty = :bundle_pay_qty,
                    id_producto = :id_producto,
                    categoria_code = :categoria_code,
                    activo = :activo,
                    solo_socios = :solo_socios,
                    fecha_inicio = :fecha_inicio,
                    fecha_fin = :fecha_fin
                WHERE id = :id";
        DBPDO::ejecutarConsulta($sql, [
            ':codigo'          => !empty(trim($datos['codigo'] ?? '')) ? mb_substr(trim($datos['codigo']), 0, 50) : null,
            ':descripcion'     => mb_substr(trim($datos['descripcion']), 0, 255),
            ':tipo'            => in_array($datos['tipo'], ['percent', 'amount', 'bundle', 'fixed_bundle']) ? $datos['tipo'] : 'percent',
            ':valor'           => (float)$datos['valor'],
            ':min_subtotal'    => (float)($datos['min_subtotal'] ?? 0),
            ':bundle_buy_qty'  => isset($datos['bundle_buy_qty']) ? (int)$datos['bundle_buy_qty'] : null,
            ':bundle_pay_qty'  => isset($datos['bundle_pay_qty']) ? (int)$datos['bundle_pay_qty'] : null,
            ':id_producto'     => isset($datos['id_producto']) ? (int)$datos['id_producto'] : null,
            ':categoria_code'  => $datos['categoria_code'] ?? null, // Changed from id_categoria
            ':activo'          => !empty($datos['activo']) ? 1 : 0,
            ':solo_socios'     => !empty($datos['solo_socios']) ? 1 : 0,
            ':fecha_inicio'    => $datos['fecha_inicio'] ?: null,
            ':fecha_fin'       => $datos['fecha_fin'] ?: null,
            ':id'              => $id,
        ]);
    }


    public static function eliminar(int $id): void
    {
        DBPDO::ejecutarConsulta("DELETE FROM promociones WHERE id = :id", [':id' => $id]);
    }

    public static function toggleActivo(int $id): bool
    {
        $row = self::obtenerPorId($id);
        if (!$row) return false;
        $nuevo = $row['activo'] ? 0 : 1;
        DBPDO::ejecutarConsulta(
            "UPDATE promociones SET activo = :a WHERE id = :id",
            [':a' => $nuevo, ':id' => $id]
        );
        return (bool)$nuevo;
    }
}
