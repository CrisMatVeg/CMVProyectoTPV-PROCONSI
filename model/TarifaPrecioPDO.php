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
        $sql = "SELECT * FROM tarifas_precios ORDER BY prioridad DESC, id DESC";
        $q = DBPDO::ejecutarConsulta($sql);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function añadir(array $d, int $idUsuario): void
    {
        $sql = "INSERT INTO tarifas_precios
                (nombre, tipo, valor, fecha_aplicacion, fecha_fin, dias_semana, hora_inicio, hora_fin, tipo_cliente, es_solo_socios, id_cliente, prioridad, scope, categoria, producto_ids, creado_por)
                VALUES (:nombre, :tipo, :valor, :fecha, :f_fin, :dias, :h_ini, :h_fin, :t_cli, :solo_soc, :id_cli, :prio, :scope, :categoria, :producto_ids, :usuario)";

        $scope = in_array($d['scope'] ?? 'todos', ['todos', 'categoria', 'productos'], true)
            ? $d['scope']
            : 'todos';

        $productoIds = null;
        if ($scope === 'productos' && !empty($d['producto_ids']) && is_array($d['producto_ids'])) {
            $productoIds = json_encode(array_map('intval', $d['producto_ids']));
        }

        $diasSemana = !empty($d['dias_semana']) && is_array($d['dias_semana'])
            ? implode(',', array_map('intval', $d['dias_semana']))
            : null;

        DBPDO::ejecutarConsulta($sql, [
            ':nombre'       => mb_substr(trim($d['nombre']), 0, 100),
            ':tipo'         => in_array($d['tipo'], ['percent'], true) ? $d['tipo'] : 'percent',
            ':valor'        => (float)$d['valor'],
            ':fecha'        => $d['fecha_aplicacion'] ?: date('Y-m-d'),
            ':f_fin'        => !empty($d['fecha_fin']) ? $d['fecha_fin'] : null,
            ':dias'         => $diasSemana,
            ':h_ini'        => !empty($d['hora_inicio']) ? $d['hora_inicio'] : null,
            ':h_fin'        => !empty($d['hora_fin']) ? $d['hora_fin'] : null,
            ':t_cli'        => in_array($d['tipo_cliente'] ?? 'todos', ['todos', 'particular', 'empresa', 'mayorista']) ? $d['tipo_cliente'] : 'todos',
            ':solo_soc'     => !empty($d['es_solo_socios']) ? 1 : 0,
            ':id_cli'       => !empty($d['id_cliente']) ? (int)$d['id_cliente'] : null,
            ':prio'         => (int)($d['prioridad'] ?? 0),
            ':scope'        => $scope,
            ':categoria'    => $scope === 'categoria' ? ($d['categoria'] ?? null) : null,
            ':producto_ids' => $productoIds,
            ':usuario'      => $idUsuario,
        ]);
    }

    public static function listarActivas(): array
    {
        $sql = "SELECT * FROM tarifas_precios WHERE activo = 1 ORDER BY prioridad DESC, id DESC";
        $q = DBPDO::ejecutarConsulta($sql);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function editar(int $id, array $d): void
    {
        $sql = "UPDATE tarifas_precios SET
                    nombre = :nombre,
                    tipo = :tipo,
                    valor = :valor,
                    fecha_aplicacion = :fecha,
                    fecha_fin = :f_fin,
                    dias_semana = :dias,
                    hora_inicio = :h_ini,
                    hora_fin = :h_fin,
                    tipo_cliente = :t_cli,
                    es_solo_socios = :solo_soc,
                    id_cliente = :id_cli,
                    prioridad = :prio,
                    scope = :scope,
                    categoria = :categoria,
                    producto_ids = :producto_ids
                WHERE id = :id";

        $scope = in_array($d['scope'] ?? 'todos', ['todos', 'categoria', 'productos'], true)
            ? $d['scope']
            : 'todos';

        $productoIds = null;
        if ($scope === 'productos' && !empty($d['producto_ids']) && is_array($d['producto_ids'])) {
            $productoIds = json_encode($d['producto_ids']);
        }

        $diasSemana = !empty($d['dias_semana']) && is_array($d['dias_semana'])
            ? implode(',', array_map('intval', $d['dias_semana']))
            : null;

        DBPDO::ejecutarConsulta($sql, [
            ':id'           => $id,
            ':nombre'       => mb_substr(trim($d['nombre']), 0, 100),
            ':tipo'         => in_array($d['tipo'], ['percent'], true) ? $d['tipo'] : 'percent',
            ':valor'        => (float)$d['valor'],
            ':fecha'        => $d['fecha_aplicacion'] ?: date('Y-m-d'),
            ':f_fin'        => !empty($d['fecha_fin']) ? $d['fecha_fin'] : null,
            ':dias'         => $diasSemana,
            ':h_ini'        => !empty($d['hora_inicio']) ? $d['hora_inicio'] : null,
            ':h_fin'        => !empty($d['hora_fin']) ? $d['hora_fin'] : null,
            ':t_cli'        => in_array($d['tipo_cliente'] ?? 'todos', ['todos', 'particular', 'empresa', 'mayorista']) ? $d['tipo_cliente'] : 'todos',
            ':solo_soc'     => !empty($d['es_solo_socios']) ? 1 : 0,
            ':id_cli'       => !empty($d['id_cliente']) ? (int)$d['id_cliente'] : null,
            ':prio'         => (int)($d['prioridad'] ?? 0),
            ':scope'        => $scope,
            ':categoria'    => $scope === 'categoria' ? ($d['categoria'] ?? null) : null,
            ':producto_ids' => $productoIds,
        ]);
    }

    public static function eliminar(int $id): void
    {
        DBPDO::ejecutarConsulta("DELETE FROM tarifas_precios WHERE id = :id", [':id' => $id]);
    }

    public static function toggleActivo(int $id): int
    {
        $q = DBPDO::ejecutarConsulta("SELECT activo FROM tarifas_precios WHERE id = :id", [':id' => $id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new Exception("Tarifa no encontrada");

        $nuevoEstado = $row['activo'] ? 0 : 1;
        DBPDO::ejecutarConsulta("UPDATE tarifas_precios SET activo = :estado WHERE id = :id", [
            ':estado' => $nuevoEstado,
            ':id'     => $id
        ]);
        return $nuevoEstado;
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

    public static function actualizarOrden(array $ids): void
    {
        $total = count($ids);
        foreach ($ids as $index => $id) {
            $prioridad = $total - $index;
            DBPDO::ejecutarConsulta(
                "UPDATE tarifas_precios SET prioridad = :p WHERE id = :id",
                [':p' => $prioridad, ':id' => (int)$id]
            );
        }
    }
}
