<?php

/**
 * Clase: TarifaPrecioPDO
 * Gestiona las subidas/bajadas globales de precios.
 */

require_once __DIR__ . '/DBPDO.php';

class TarifaPrecioPDO
{
    public static function listarTodas(): array
    {
        $sql = "SELECT * FROM tarifas_precios ORDER BY fecha_aplicacion DESC, id DESC";
        $q = DBPDO::ejecutarConsulta($sql);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function añadir(array $d, int $idUsuario): void
    {
        $sql = "INSERT INTO tarifas_precios
                (nombre, tipo, valor, fecha_aplicacion, scope, categoria, producto_ids, creado_por)
                VALUES (:nombre, :tipo, :valor, :fecha, :scope, :categoria, :producto_ids, :usuario)";
        $scope = in_array($d['scope'] ?? 'todos', ['todos', 'categoria', 'productos'], true)
            ? $d['scope']
            : 'todos';
        $productoIds = null;
        if ($scope === 'productos' && !empty($d['producto_ids']) && is_array($d['producto_ids'])) {
            $productoIds = json_encode(array_map('intval', $d['producto_ids']));
        }
        DBPDO::ejecutarConsulta($sql, [
            ':nombre'       => mb_substr(trim($d['nombre']), 0, 100),
            ':tipo'         => in_array($d['tipo'], ['percent', 'amount'], true) ? $d['tipo'] : 'percent',
            ':valor'        => (float)$d['valor'],
            ':fecha'        => $d['fecha_aplicacion'],
            ':scope'        => $scope,
            ':categoria'    => $scope === 'categoria' ? ($d['categoria'] ?? null) : null,
            ':producto_ids' => $productoIds,
            ':usuario'      => $idUsuario,
        ]);
    }

    public static function aplicar(int $id): void
    {
        $q = DBPDO::ejecutarConsulta("SELECT * FROM tarifas_precios WHERE id = :id", [':id' => $id]);
        $t = $q->fetch(PDO::FETCH_ASSOC);
        if (!$t) {
            throw new Exception('Tarifa no encontrada');
        }
        if ((int)$t['aplicada'] === 1) {
            return; // ya aplicada
        }

        $tipo  = $t['tipo'];
        $valor = (float)$t['valor'];
        $scope = $t['scope'] ?? 'todos';

        // Construir filtro según el ámbito
        $where  = '1=1';
        $params = [];

        if ($scope === 'categoria' && !empty($t['categoria'])) {
            $where = 'categoria = :cat';
            $params[':cat'] = $t['categoria'];
        } elseif ($scope === 'productos' && !empty($t['producto_ids'])) {
            $ids = json_decode($t['producto_ids'], true) ?: [];
            $ids = array_map('intval', array_filter($ids));
            if ($ids) {
                $in = implode(',', $ids);
                $where = "id IN ($in)";
            }
        }

        if ($tipo === 'percent') {
            // Subida/bajada porcentual sobre el precio actual
            $factor = 1 + ($valor / 100.0);
            DBPDO::ejecutarConsulta(
                "UPDATE productos SET precio_venta = ROUND(precio_venta * :factor, 2) WHERE $where",
                array_merge([':factor' => $factor], $params)
            );
        } else {
            // Importe fijo: sumar (o restar si es negativo)
            DBPDO::ejecutarConsulta(
                "UPDATE productos SET precio_venta = ROUND(precio_venta + :importe, 2) WHERE $where",
                array_merge([':importe' => $valor], $params)
            );
        }

        DBPDO::ejecutarConsulta(
            "UPDATE tarifas_precios SET aplicada = 1 WHERE id = :id",
            [':id' => $id]
        );
    }
}
