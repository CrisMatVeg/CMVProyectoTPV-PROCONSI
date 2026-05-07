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
        $sql = "SELECT * FROM promociones ORDER BY prioridad DESC, id DESC";
        $q = DBPDO::ejecutarConsulta($sql);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve las promociones activas (para el TPV).
     * Formato adaptado a JavaScript.
     */
    public static function listarActivas(): array
    {
        $ahora = date('Y-m-d H:i:s');
        $sql = "SELECT * 
                FROM promociones
                WHERE activo = 1
                  AND (fecha_inicio IS NULL OR fecha_inicio <= :ahora)
                  AND (fecha_fin IS NULL OR fecha_fin >= :ahora)
                ORDER BY prioridad DESC, id DESC";
        $q = DBPDO::ejecutarConsulta($sql, [':ahora' => $ahora]);
        return self::formatRows($q->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Devuelve TODAS las promociones marcadas como activas, 
     * ignorando restricciones de fecha/hora (para administración/edición).
     */
    public static function listarTodasActivas(): array
    {
        $sql = "SELECT * FROM promociones WHERE activo = 1 ORDER BY prioridad DESC, id DESC";
        $q = DBPDO::ejecutarConsulta($sql);
        return self::formatRows($q->fetchAll(PDO::FETCH_ASSOC));
    }

    private static function formatRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id'             => (int)$r['id'],
                'codigo'         => $r['codigo'],
                'tipo'           => $r['tipo'],
                'valor'          => (float)$r['valor'],
                'min_subtotal'   => (float)$r['min_subtotal'],
                'bundle_buy_qty' => $r['bundle_buy_qty'] ? (int)$r['bundle_buy_qty'] : null,
                'bundle_pay_qty' => $r['bundle_pay_qty'] ? (int)$r['bundle_pay_qty'] : null,
                'id_producto'    => $r['id_producto'] ? (int)$r['id_producto'] : null,
                'producto_ids'   => $r['producto_ids'] ?? null,
                'categoria_code' => $r['categoria_code'] ?? null,
                'label'          => $r['descripcion'],
                'solo_socios'    => (int)$r['solo_socios'] === 1,
                'prioridad'      => (int)$r['prioridad'],
                'roles_segmento' => $r['roles_segmento'],
                'dias_semana'    => $r['dias_semana'],
                'hora_inicio'    => $r['hora_inicio'],
                'hora_fin'       => $r['hora_fin'],
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
                (codigo, descripcion, tipo, valor, min_subtotal, bundle_buy_qty, bundle_pay_qty, id_producto, producto_ids, categoria_code, activo, solo_socios, roles_segmento, dias_semana, hora_inicio, hora_fin, fecha_inicio, fecha_fin, prioridad, excluidos)
                VALUES (:codigo, :descripcion, :tipo, :valor, :min_subtotal, :bundle_buy_qty, :bundle_pay_qty, :id_producto, :producto_ids, :categoria_code, :activo, :solo_socios, :roles, :dias, :h_ini, :h_fin, :fecha_inicio, :fecha_fin, :prioridad, :excluidos)";
        
        $productoIdsArr = !empty($datos['producto_ids']) && is_array($datos['producto_ids']) ? $datos['producto_ids'] : null;
        $productoIdsJson = $productoIdsArr ? json_encode(array_map('intval', $productoIdsArr)) : null;

        DBPDO::ejecutarConsulta($sql, [
            ':codigo'          => !empty(trim($datos['codigo'] ?? '')) ? mb_substr(trim($datos['codigo']), 0, 50) : null,
            ':descripcion'     => mb_substr(trim($datos['descripcion']), 0, 255),
            ':tipo'            => in_array($datos['tipo'], ['percent', 'amount', 'bundle', 'fixed_bundle']) ? $datos['tipo'] : 'percent',
            ':valor'           => (float)$datos['valor'],
            ':min_subtotal'    => (float)($datos['min_subtotal'] ?? 0),
            ':bundle_buy_qty'  => isset($datos['bundle_buy_qty']) ? (int)$datos['bundle_buy_qty'] : null,
            ':bundle_pay_qty'  => isset($datos['bundle_pay_qty']) ? (int)$datos['bundle_pay_qty'] : null,
            ':id_producto'     => isset($datos['id_producto']) ? (int)$datos['id_producto'] : null,
            ':producto_ids'    => $productoIdsJson,
            ':categoria_code'  => $datos['categoria_code'] ?? null,
            ':activo'          => !empty($datos['activo']) ? 1 : 0,
            ':solo_socios'     => !empty($datos['solo_socios']) ? 1 : 0,
            ':roles'           => !empty($datos['roles_segmento']) ? (is_array($datos['roles_segmento']) ? implode(',', $datos['roles_segmento']) : $datos['roles_segmento']) : null,
            ':dias'            => !empty($datos['dias_semana']) ? (is_array($datos['dias_semana']) ? implode(',', $datos['dias_semana']) : $datos['dias_semana']) : null,
            ':h_ini'           => !empty($datos['hora_inicio']) ? $datos['hora_inicio'] : null,
            ':h_fin'           => !empty($datos['hora_fin']) ? $datos['hora_fin'] : null,
            ':fecha_inicio'    => !empty($datos['fecha_inicio']) ? $datos['fecha_inicio'] : date('Y-m-d'),
            ':fecha_fin'       => !empty($datos['fecha_fin']) ? $datos['fecha_fin'] : null,
            ':prioridad'       => (int)($datos['prioridad'] ?? 0),
            ':excluidos'       => !empty($datos['excluidos']) ? (is_array($datos['excluidos']) ? json_encode(array_map('intval', $datos['excluidos'])) : $datos['excluidos']) : null,
        ]);

        $idPromo = DBPDO::getPDO()->lastInsertId();
        if ($productoIdsArr) {
            foreach ($productoIdsArr as $pid) {
                DBPDO::ejecutarConsulta("INSERT IGNORE INTO promocion_productos (id_promocion, id_producto) VALUES (:pr, :pd)", [
                    ':pr' => $idPromo,
                    ':pd' => (int)$pid
                ]);
            }
        }
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
                    producto_ids = :producto_ids,
                    categoria_code = :categoria_code,
                    activo = :activo,
                    solo_socios = :solo_socios,
                    roles_segmento = :roles,
                    dias_semana = :dias,
                    hora_inicio = :h_ini,
                    hora_fin = :h_fin,
                    fecha_inicio = :fecha_inicio,
                    fecha_fin = :fecha_fin,
                    prioridad = :prioridad,
                    excluidos = :excluidos
                WHERE id = :id";
        
        $productoIdsArr = !empty($datos['producto_ids']) && is_array($datos['producto_ids']) ? $datos['producto_ids'] : null;
        $productoIdsJson = $productoIdsArr ? json_encode(array_map('intval', $productoIdsArr)) : null;

        DBPDO::ejecutarConsulta($sql, [
            ':codigo'          => !empty(trim($datos['codigo'] ?? '')) ? mb_substr(trim($datos['codigo']), 0, 50) : null,
            ':descripcion'     => mb_substr(trim($datos['descripcion']), 0, 255),
            ':tipo'            => in_array($datos['tipo'], ['percent', 'amount', 'bundle', 'fixed_bundle']) ? $datos['tipo'] : 'percent',
            ':valor'           => (float)$datos['valor'],
            ':min_subtotal'    => (float)($datos['min_subtotal'] ?? 0),
            ':bundle_buy_qty'  => isset($datos['bundle_buy_qty']) ? (int)$datos['bundle_buy_qty'] : null,
            ':bundle_pay_qty'  => isset($datos['bundle_pay_qty']) ? (int)$datos['bundle_pay_qty'] : null,
            ':id_producto'     => isset($datos['id_producto']) ? (int)$datos['id_producto'] : null,
            ':producto_ids'    => $productoIdsJson,
            ':categoria_code'  => $datos['categoria_code'] ?? null,
            ':activo'          => !empty($datos['activo']) ? 1 : 0,
            ':solo_socios'     => !empty($datos['solo_socios']) ? 1 : 0,
            ':roles'           => !empty($datos['roles_segmento']) ? (is_array($datos['roles_segmento']) ? implode(',', $datos['roles_segmento']) : $datos['roles_segmento']) : null,
            ':dias'            => !empty($datos['dias_semana']) ? (is_array($datos['dias_semana']) ? implode(',', $datos['dias_semana']) : $datos['dias_semana']) : null,
            ':h_ini'           => !empty($datos['hora_inicio']) ? $datos['hora_inicio'] : null,
            ':h_fin'           => !empty($datos['hora_fin']) ? $datos['hora_fin'] : null,
            ':fecha_inicio'    => !empty($datos['fecha_inicio']) ? $datos['fecha_inicio'] : date('Y-m-d'),
            ':fecha_fin'       => !empty($datos['fecha_fin']) ? $datos['fecha_fin'] : null,
            ':prioridad'       => (int)($datos['prioridad'] ?? 0),
            ':excluidos'       => !empty($datos['excluidos']) ? (is_array($datos['excluidos']) ? json_encode(array_map('intval', $datos['excluidos'])) : $datos['excluidos']) : null,
            ':id'              => $id,
        ]);

        // Sincronizar tabla relacional
        DBPDO::ejecutarConsulta("DELETE FROM promocion_productos WHERE id_promocion = :id", [':id' => $id]);
        if ($productoIdsArr) {
            foreach ($productoIdsArr as $pid) {
                DBPDO::ejecutarConsulta("INSERT IGNORE INTO promocion_productos (id_promocion, id_producto) VALUES (:pr, :pd)", [
                    ':pr' => $id,
                    ':pd' => (int)$pid
                ]);
            }
        }
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

    public static function actualizarOrden(array $ids): void
    {
        $total = count($ids);
        foreach ($ids as $index => $id) {
            $prioridad = $total - $index;
            DBPDO::ejecutarConsulta(
                "UPDATE promociones SET prioridad = :p WHERE id = :id",
                [':p' => $prioridad, ':id' => (int)$id]
            );
        }
    }

    public static function excluirProducto(int $idPromo, int $idProducto): void
    {
        $p = self::obtenerPorId($idPromo);
        if (!$p) throw new Exception("Promoción no encontrada");

        if ($p['id_producto'] == $idProducto) {
            // Es una promo directa, la desactivamos para este producto o limpiamos el ID
            DBPDO::ejecutarConsulta("UPDATE promociones SET id_producto = NULL WHERE id = :id", [':id' => $idPromo]);
        } else {
            // Añadir a excluidos
            $excluidos = json_decode($p['excluidos'] ?? '[]', true) ?: [];
            if (!in_array($idProducto, $excluidos)) {
                $excluidos[] = (int)$idProducto;
                DBPDO::ejecutarConsulta("UPDATE promociones SET excluidos = :e WHERE id = :id", [
                    ':e'  => json_encode(array_values($excluidos)),
                    ':id' => $idPromo
                ]);
            }
        }
    }
}
