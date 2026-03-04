<?php

/**
 * Clase: FinancieraPDO
 * Gestiona las entidades financieras.
 */
require_once __DIR__ . '/DBPDO.php';

class FinancieraPDO
{
    public static function listarActivas(): array
    {
        $sql = "SELECT id, nombre, descripcion, comision_cero_interes as comision, comision_con_interes, min_importe 
                FROM financieras 
                WHERE activo = 1 
                ORDER BY nombre ASC";
        $q = DBPDO::ejecutarConsulta($sql);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Devuelve las financieras con sus tramos de comisión por plazo (comisiones_plazo).
     */
    public static function listarConComisiones(): array
    {
        $financieras = self::listarActivas();
        foreach ($financieras as &$f) {
            $sql = "SELECT meses, comision_sin_interes, comision_con_interes 
                    FROM comisiones_plazo 
                    WHERE id_financiera = :id 
                    ORDER BY meses ASC";
            $q = DBPDO::ejecutarConsulta($sql, [':id' => $f['id']]);
            $f['tiers'] = $q->fetchAll(PDO::FETCH_ASSOC);
        }
        return $financieras;
    }

    public static function obtenerPorId(int $id): ?array
    {
        $sql = "SELECT * FROM financieras WHERE id = :id";
        $q = DBPDO::ejecutarConsulta($sql, [':id' => $id]);
        $res = $q->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public static function obtenerComisionesPorPlazo(int $idFinanciera, int $meses): ?array
    {
        $sql = "SELECT id, id_financiera, meses, comision_sin_interes, comision_con_interes 
                FROM comisiones_plazo 
                WHERE id_financiera = :id AND meses = :meses";
        $q = DBPDO::ejecutarConsulta($sql, [':id' => $idFinanciera, ':meses' => $meses]);
        $res = $q->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public static function obtenerTodasLasComisiones(): array
    {
        $sql = "SELECT cp.id, cp.id_financiera, f.nombre, cp.meses, 
                       cp.comision_sin_interes, cp.comision_con_interes,
                       f.min_importe
                FROM comisiones_plazo cp
                JOIN financieras f ON cp.id_financiera = f.id
                WHERE f.activo = 1
                ORDER BY f.nombre ASC, cp.meses ASC";
        $q = DBPDO::ejecutarConsulta($sql);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }
}
