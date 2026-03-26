<?php

/**
 * Clase: ValePDO
 * Gestiona los vales generados por devoluciones.
 */
require_once __DIR__ . '/DBPDO.php';

class ValePDO
{
    /**
     * Crea un nuevo vale en la base de datos.
     */
    public static function crearVale(?int $idCliente, ?int $idVentaOrigen, float $importe): string
    {
        $codigo = self::generarCodigoUnico();
        $sql = "INSERT INTO vales (codigo, id_cliente, id_venta_origen, importe, importe_restante, estado)
                VALUES (:codigo, :cliente, :venta, :importe, :restante, 'activo')";
        
        DBPDO::ejecutarConsulta($sql, [
            ':codigo'   => $codigo,
            ':cliente'  => $idCliente,
            ':venta'    => $idVentaOrigen,
            ':importe'  => $importe,
            ':restante' => $importe
        ]);

        return $codigo;
    }

    /**
     * Obtiene los vales de un cliente.
     */
    public static function obtenerValesPorCliente(int $idCliente): array
    {
        $sql = "SELECT * FROM vales WHERE id_cliente = :id ORDER BY fecha_creacion DESC";
        $q = DBPDO::ejecutarConsulta($sql, [':id' => $idCliente]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Genera un código de vale único de 10 caracteres (EJ: V-ABC123XY).
     */
    private static function generarCodigoUnico(): string
    {
        do {
            $chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $res = "V-";
            for ($i = 0; $i < 8; $i++) {
                $res .= $chars[mt_rand(0, strlen($chars) - 1)];
            }
            // Verificar unicidad
            $q = DBPDO::ejecutarConsulta("SELECT id FROM vales WHERE codigo = :c", [':c' => $res]);
        } while ($q->fetch());

        return $res;
    }

    public static function obtenerPorCodigo(string $codigo): ?array
    {
        $sql = "SELECT * FROM vales WHERE codigo = :c";
        $q = DBPDO::ejecutarConsulta($sql, [':c' => $codigo]);
        $res = $q->fetch(PDO::FETCH_ASSOC);
        return $res ? $res : null;
    }

    /**
     * Consume el importe especificado del vale, reduciendo su importe restante.
     * Si el importe restante llega a 0, cambia el estado a 'consumido'.
     * Retorna verdadero si se pudo realizar con éxito.
     */
    public static function consumirVale(string $codigo, float $importeAConsumir): bool
    {
        $vale = self::obtenerPorCodigo($codigo);
        if (!$vale || $vale['estado'] !== 'activo') {
            return false;
        }

        $nuevoRestante = round($vale['importe_restante'] - $importeAConsumir, 2);
        
        if ($nuevoRestante < -0.01) { // Pequeño margen por float
            return false; // No hay saldo suficiente
        }

        $nuevoEstado = ($nuevoRestante <= 0.01) ? 'consumido' : 'activo';

        $sql = "UPDATE vales SET importe_restante = :restante, estado = :estado WHERE id = :id";
        DBPDO::ejecutarConsulta($sql, [
            ':restante' => max(0, $nuevoRestante),
            ':estado' => $nuevoEstado,
            ':id' => $vale['id']
        ]);

        return true;
    }
}
