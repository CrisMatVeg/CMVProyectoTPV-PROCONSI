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
                (tipo, rol, nombre, apellidos, nif, email, telefono, direccion, cp, poblacion, provincia, notas)
                VALUES (:tipo, :rol, :nombre, :apellidos, :nif, :email, :tel, :dir, :cp, :pob, :prov, :notas)";
        DBPDO::ejecutarConsulta($sql, [
            ':tipo'      => in_array($d['tipo'] ?? 'particular', ['particular', 'empresa'], true) ? $d['tipo'] : 'particular',
            ':rol'       => $d['rol'] ?? 'general',
            ':nombre'    => mb_substr(trim($d['nombre'] ?? ''), 0, 100),
            ':apellidos' => mb_substr(trim($d['apellidos'] ?? ''), 0, 150),
            ':nif'       => $d['nif'] ?? null,
            ':email'     => $d['email'] ?? null,
            ':tel'       => $d['telefono'] ?? null,
            ':dir'       => $d['direccion'] ?? null,
            ':cp'        => $d['cp'] ?? null,
            ':pob'       => $d['poblacion'] ?? null,
            ':prov'      => $d['provincia'] ?? null,
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
                    rol = :rol,
                    nombre = :nombre,
                    apellidos = :apellidos,
                    nif = :nif,
                    email = :email,
                    telefono = :tel,
                    direccion = :dir,
                    cp = :cp,
                    poblacion = :pob,
                    provincia = :prov,
                    notas = :notas
                WHERE id = :id";
        DBPDO::ejecutarConsulta($sql, [
            ':id'        => $id,
            ':tipo'      => in_array($d['tipo'] ?? 'particular', ['particular', 'empresa'], true) ? $d['tipo'] : 'particular',
            ':rol'       => $d['rol'] ?? 'general',
            ':nombre'    => mb_substr(trim($d['nombre'] ?? ''), 0, 100),
            ':apellidos' => mb_substr(trim($d['apellidos'] ?? ''), 0, 150),
            ':nif'       => $d['nif'] ?? null,
            ':email'     => $d['email'] ?? null,
            ':tel'       => $d['telefono'] ?? null,
            ':dir'       => $d['direccion'] ?? null,
            ':cp'        => $d['cp'] ?? null,
            ':pob'       => $d['poblacion'] ?? null,
            ':prov'      => $d['provincia'] ?? null,
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

    public static function listarPorRol(string $rol): array
    {
        $q = DBPDO::ejecutarConsulta(
            "SELECT * FROM clientes WHERE rol = :rol AND fecha_baja IS NULL ORDER BY nombre, apellidos",
            [':rol' => $rol]
        );
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function listarSocios(): array
    {
        return self::listarPorRol('socio');
    }

    public static function listarMayoristas(): array
    {
        return self::listarPorRol('mayorista');
    }
    public static function sumarPuntos(int $id, int $puntos): void
    {
        $sql = "UPDATE clientes SET puntos = puntos + :puntos, ultima_compra = NOW() WHERE id = :id";
        DBPDO::ejecutarConsulta($sql, [':id' => $id, ':puntos' => $puntos]);
    }

    public static function restarPuntos(int $id, int $puntos): void
    {
        $sql = "UPDATE clientes SET puntos = puntos - :puntos WHERE id = :id AND puntos >= :puntos";
        DBPDO::ejecutarConsulta($sql, [':id' => $id, ':puntos' => $puntos]);
    }

    public static function checkExpiracion(int $id): void
    {
        // Si ha pasado más de 1 año desde la última compra, los puntos caducan (se ponen a 0)
        $sql = "UPDATE clientes SET puntos = 0 WHERE id = :id AND ultima_compra < DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        DBPDO::ejecutarConsulta($sql, [':id' => $id]);
    }
}
