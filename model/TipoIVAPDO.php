<?php

/**
 * Clase: TipoIVAPDO
 * Gestiona los tipos de IVA y sus vigencias.
 */

require_once __DIR__ . '/DBPDO.php';

class TipoIVAPDO
{
    /**
     * Lista todos los tipos de IVA para administración.
     */
    public static function listarTodos(): array
    {
        $sql = "SELECT * FROM tipos_iva ORDER BY codigo ASC, fecha_inicio DESC";
        $q = DBPDO::ejecutarConsulta($sql);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista solo los tipos de IVA que están vigentes actualmente (uno por código).
     */
    public static function listarVigentesActuales(): array
    {
        $hoy = date('Y-m-d');
        $sql = "SELECT t1.*
                FROM tipos_iva t1
                WHERE t1.id = (
                    SELECT t2.id FROM tipos_iva t2
                    WHERE t2.codigo = t1.codigo
                      AND t2.activo = 1
                      AND t2.fecha_inicio <= :hoy
                      AND (t2.fecha_fin IS NULL OR t2.fecha_fin >= :hoy)
                    ORDER BY t2.fecha_inicio DESC
                    LIMIT 1
                )
                ORDER BY t1.codigo ASC";
        $q = DBPDO::ejecutarConsulta($sql, [':hoy' => $hoy]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve el tipo de IVA vigente para un código y fecha concretos.
     */
    public static function obtenerVigentePorCodigo(string $codigo, string $fecha): ?array
    {
        $sql = "SELECT *
                FROM tipos_iva
                WHERE codigo = :codigo
                  AND fecha_inicio <= :fecha
                  AND (fecha_fin IS NULL OR fecha_fin >= :fecha)
                  AND activo = 1
                ORDER BY fecha_inicio DESC
                LIMIT 1";
        $q = DBPDO::ejecutarConsulta($sql, [
            ':codigo' => $codigo,
            ':fecha'  => $fecha,
        ]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function agregar(array $d): void
    {
        $sql = "INSERT INTO tipos_iva
                (codigo, nombre, porcentaje, recargo_equivalencia, fecha_inicio, fecha_fin, activo)
                VALUES (:codigo, :nombre, :porcentaje, :re, :inicio, :fin, :activo)";
        DBPDO::ejecutarConsulta($sql, [
            ':codigo'     => mb_substr(trim($d['codigo']), 0, 20),
            ':nombre'     => mb_substr(trim($d['nombre']), 0, 50),
            ':porcentaje' => (float)$d['porcentaje'],
            ':re'         => (float)($d['recargo_equivalencia'] ?? 0),
            ':inicio'     => $d['fecha_inicio'],
            ':fin'        => $d['fecha_fin'] ?: null,
            ':activo'     => !empty($d['activo']) ? 1 : 0,
        ]);

        require_once __DIR__ . '/ProductoPDO.php';
        ProductoPDO::recalcularPreciosCosteGlobal();
    }

    public static function editar(int $id, array $d): void
    {
        $sql = "UPDATE tipos_iva SET
                    codigo = :codigo,
                    nombre = :nombre,
                    porcentaje = :porcentaje,
                    recargo_equivalencia = :re,
                    fecha_inicio = :inicio,
                    fecha_fin = :fin,
                    activo = :activo
                WHERE id = :id";
        DBPDO::ejecutarConsulta($sql, [
            ':codigo'     => mb_substr(trim($d['codigo']), 0, 20),
            ':nombre'     => mb_substr(trim($d['nombre']), 0, 50),
            ':porcentaje' => (float)$d['porcentaje'],
            ':re'         => (float)($d['recargo_equivalencia'] ?? 0),
            ':inicio'     => $d['fecha_inicio'],
            ':fin'        => $d['fecha_fin'] ?: null,
            ':activo'     => !empty($d['activo']) ? 1 : 0,
            ':id'         => $id,
        ]);

        require_once __DIR__ . '/ProductoPDO.php';
        ProductoPDO::recalcularPreciosCosteGlobal();
    }


    public static function obtenerPorId(int $id): ?array
    {
        $q = DBPDO::ejecutarConsulta("SELECT * FROM tipos_iva WHERE id = :id", [':id' => $id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function contarProductos(int $id): int
    {
        $q = DBPDO::ejecutarConsulta(
            "SELECT COUNT(*) FROM productos WHERE id_tipo_iva = :id",
            [':id' => $id]
        );
        return (int)$q->fetchColumn();
    }

    public static function eliminar(int $id): void
    {
        DBPDO::ejecutarConsulta("DELETE FROM tipos_iva WHERE id = :id", [':id' => $id]);
    }

    /**
     * Comprueba si hay solapamiento de fechas para un código de IVA.
     * Retorna el primer registro que causa el solapamiento o null.
     */
    public static function checkOverlappingDates(string $codigo, string $inicio, ?string $fin, ?int $idExclude = null): ?array
    {
        $sql = "SELECT * FROM tipos_iva 
                WHERE codigo = :codigo 
                AND fecha_inicio <= COALESCE(:fin, '9999-12-31') 
                AND COALESCE(fecha_fin, '9999-12-31') >= :inicio";
        
        $params = [
            ':codigo' => $codigo,
            ':inicio' => $inicio,
            ':fin'    => $fin ?: null
        ];

        if ($idExclude) {
            $sql .= " AND id <> :id";
            $params[':id'] = $idExclude;
        }

        $q = DBPDO::ejecutarConsulta($sql, $params);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
