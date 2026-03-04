<?php

/**
 * Clase: ClientePDO
 * Gestiona clientes (particulares y empresas) y el flag de socio.
 */

require_once __DIR__ . '/DBPDO.php';

class ClientePDO
{
    public static function listarTodos(): array
    {
        $sql = "SELECT * FROM clientes ORDER BY nombre, apellidos";
        $q = DBPDO::ejecutarConsulta($sql);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function obtenerPorId(int $id): ?array
    {
        $q = DBPDO::ejecutarConsulta(
            "SELECT * FROM clientes WHERE id = :id",
            [':id' => $id]
        );
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function obtenerPorNif(string $nif): ?array
    {
        $nif = trim($nif);
        if ($nif === '') return null;
        $q = DBPDO::ejecutarConsulta(
            "SELECT * FROM clientes WHERE nif = :nif",
            [':nif' => $nif]
        );
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function crear(array $d): int
    {
        $sql = "INSERT INTO clientes
                (tipo, nombre, apellidos, nif, email, telefono, direccion, cp, poblacion, provincia, es_socio, notas)
                VALUES (:tipo, :nombre, :apellidos, :nif, :email, :tel, :dir, :cp, :pob, :prov, :socio, :notas)";
        DBPDO::ejecutarConsulta($sql, [
            ':tipo'      => in_array($d['tipo'] ?? 'particular', ['particular', 'empresa'], true) ? $d['tipo'] : 'particular',
            ':nombre'    => mb_substr(trim($d['nombre'] ?? ''), 0, 100),
            ':apellidos' => mb_substr(trim($d['apellidos'] ?? ''), 0, 150),
            ':nif'       => $d['nif'] ?? null,
            ':email'     => $d['email'] ?? null,
            ':tel'       => $d['telefono'] ?? null,
            ':dir'       => $d['direccion'] ?? null,
            ':cp'        => $d['cp'] ?? null,
            ':pob'       => $d['poblacion'] ?? null,
            ':prov'      => $d['provincia'] ?? null,
            ':socio'     => !empty($d['es_socio']) ? 1 : 0,
            ':notas'     => $d['notas'] ?? null,
        ]);

        $q = DBPDO::ejecutarConsulta("SELECT id FROM clientes ORDER BY id DESC LIMIT 1");
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return (int)$row['id'];
    }

    public static function actualizar(int $id, array $d): void
    {
        $sql = "UPDATE clientes
                SET tipo = :tipo,
                    nombre = :nombre,
                    apellidos = :apellidos,
                    nif = :nif,
                    email = :email,
                    telefono = :tel,
                    direccion = :dir,
                    cp = :cp,
                    poblacion = :pob,
                    provincia = :prov,
                    es_socio = :socio,
                    notas = :notas
                WHERE id = :id";
        DBPDO::ejecutarConsulta($sql, [
            ':id'        => $id,
            ':tipo'      => in_array($d['tipo'] ?? 'particular', ['particular', 'empresa'], true) ? $d['tipo'] : 'particular',
            ':nombre'    => mb_substr(trim($d['nombre'] ?? ''), 0, 100),
            ':apellidos' => mb_substr(trim($d['apellidos'] ?? ''), 0, 150),
            ':nif'       => $d['nif'] ?? null,
            ':email'     => $d['email'] ?? null,
            ':tel'       => $d['telefono'] ?? null,
            ':dir'       => $d['direccion'] ?? null,
            ':cp'        => $d['cp'] ?? null,
            ':pob'       => $d['poblacion'] ?? null,
            ':prov'      => $d['provincia'] ?? null,
            ':socio'     => !empty($d['es_socio']) ? 1 : 0,
            ':notas'     => $d['notas'] ?? null,
        ]);
    }

    public static function marcarBaja(int $id): void
    {
        DBPDO::ejecutarConsulta(
            "UPDATE clientes SET fecha_baja = NOW() WHERE id = :id AND fecha_baja IS NULL",
            [':id' => $id]
        );
    }

    public static function listarSocios(): array
    {
        $q = DBPDO::ejecutarConsulta(
            "SELECT * FROM clientes WHERE es_socio = 1 AND (fecha_baja IS NULL) ORDER BY nombre, apellidos"
        );
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }
}
