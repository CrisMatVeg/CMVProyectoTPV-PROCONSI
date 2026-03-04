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
                'id'          => (int)$r['id'],
                'code'        => $r['codigo'],
                'type'        => $r['tipo'],
                'value'       => (float)$r['valor'],
                'minSubtotal' => (float)$r['min_subtotal'],
                'label'       => $r['descripcion'],
                'soloSocios'  => (int)$r['solo_socios'] === 1,
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
                (codigo, descripcion, tipo, valor, min_subtotal, activo, solo_socios, fecha_inicio, fecha_fin)
                VALUES (:codigo, :descripcion, :tipo, :valor, :min_subtotal, :activo, :solo_socios, :fecha_inicio, :fecha_fin)";
        DBPDO::ejecutarConsulta($sql, [
            ':codigo'       => mb_substr(trim($datos['codigo']), 0, 50),
            ':descripcion'  => mb_substr(trim($datos['descripcion']), 0, 255),
            ':tipo'         => in_array($datos['tipo'], ['percent', 'amount']) ? $datos['tipo'] : 'percent',
            ':valor'        => (float)$datos['valor'],
            ':min_subtotal' => (float)($datos['min_subtotal'] ?? 0),
            ':activo'       => !empty($datos['activo']) ? 1 : 0,
            ':solo_socios'  => !empty($datos['solo_socios']) ? 1 : 0,
            ':fecha_inicio' => $datos['fecha_inicio'] ?: null,
            ':fecha_fin'    => $datos['fecha_fin'] ?: null,
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
                    activo = :activo,
                    solo_socios = :solo_socios,
                    fecha_inicio = :fecha_inicio,
                    fecha_fin = :fecha_fin
                WHERE id = :id";
        DBPDO::ejecutarConsulta($sql, [
            ':codigo'       => mb_substr(trim($datos['codigo']), 0, 50),
            ':descripcion'  => mb_substr(trim($datos['descripcion']), 0, 255),
            ':tipo'         => in_array($datos['tipo'], ['percent', 'amount']) ? $datos['tipo'] : 'percent',
            ':valor'        => (float)$datos['valor'],
            ':min_subtotal' => (float)($datos['min_subtotal'] ?? 0),
            ':activo'       => !empty($datos['activo']) ? 1 : 0,
            ':solo_socios'  => !empty($datos['solo_socios']) ? 1 : 0,
            ':fecha_inicio' => $datos['fecha_inicio'] ?: null,
            ':fecha_fin'    => $datos['fecha_fin'] ?: null,
            ':id'           => $id,
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
