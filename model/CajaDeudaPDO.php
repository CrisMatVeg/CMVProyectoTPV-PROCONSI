<?php

/**
 * Clase: CajaDeudaPDO
 * Gestiona las deudas generadas por faltantes de caja.
 */

require_once __DIR__ . '/DBPDO.php';

class CajaDeudaPDO
{
    public static function crearDeuda(int $idCierre, int $idUsuario, float $importe, string $concepto = ''): void
    {
        if ($importe <= 0) return;
        DBPDO::ejecutarConsulta(
            "INSERT INTO caja_deudas (id_cierre_fiscal, id_usuario, importe, concepto)
             VALUES (:cierre, :usuario, :importe, :concepto)",
            [
                ':cierre'   => $idCierre,
                ':usuario'  => $idUsuario,
                ':importe'  => $importe,
                ':concepto' => $concepto ?: 'Faltante de caja en cierre',
            ]
        );
    }

    public static function listarPorCierre(int $idCierre): array
    {
        $sql = "SELECT d.*, u.nombre as nombre_usuario
                FROM caja_deudas d
                JOIN usuarios u ON d.id_usuario = u.id
                WHERE d.id_cierre_fiscal = :c";
        $q = DBPDO::ejecutarConsulta($sql, [':c' => $idCierre]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function listarPendientes(?int $idUsuario = null): array
    {
        $where = "WHERE d.saldada = 0";
        $params = [];
        if ($idUsuario !== null) {
            $where .= " AND d.id_usuario = :idUsuario";
            $params[':idUsuario'] = $idUsuario;
        }
        $sql = "SELECT d.*, u.nombre as nombre_usuario
            FROM caja_deudas d
            JOIN usuarios u ON d.id_usuario = u.id
            $where
            ORDER BY d.fecha_creacion DESC";
        $q = DBPDO::ejecutarConsulta($sql, $params);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }
}
