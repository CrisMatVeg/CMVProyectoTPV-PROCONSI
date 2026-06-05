<?php

/**
 * Clase: PriceEngine
 * Centraliza el cálculo de precios, tarifas y promociones con fines de auditoría.
 */

require_once __DIR__ . '/DBPDO.php';
require_once __DIR__ . '/ProductoPDO.php';
require_once __DIR__ . '/TarifaPrecioPDO.php';
require_once __DIR__ . '/PromocionPDO.php';

class PriceEngine
{

    /**
     * Calcula el precio final de un producto aplicando la cadena de descuentos.
     * 
     * @param int $idProducto
     * @param int|null $idCliente
     * @param int $cantidad
     * @param string|null $codigoCupon
     * @return array Breakdown del precio y descuentos aplicados.
     */
    public static function calculate(int $idProducto, ?int $idCliente = null, int $cantidad = 1, ?string $codigoCupon = null): array
    {
        $producto = ProductoPDO::obtenerProductoPorId($idProducto);
        if (!$producto) {
            throw new Exception("Producto no encontrado");
        }

        $precioBase = (float)$producto['precio_venta'];
        $precioActual = $precioBase;
        $descuentos = [];

        // 1. Aplicar la tarifa de mayor prioridad (solo una)
        $tarifas = self::obtenerTarifasAplicables($idProducto, $idCliente);
        $tarifas = array_slice($tarifas, 0, 1);
        foreach ($tarifas as $t) {
            $importeVariacion = 0;
            if ($t['tipo'] === 'percent') {
                $importeVariacion = round($precioActual * ($t['valor'] / 100), 2);
            } else {
                $importeVariacion = (float)$t['valor'];
            }

            if ($importeVariacion != 0) {
                $precioActual += $importeVariacion;
                $descuentos[] = [
                    'id_origen' => $t['id'],
                    'tipo_descuento' => 'tarifa',
                    'nombre' => $t['nombre'],
                    'valor_descontado' => $importeVariacion // Subida: +, Bajada: -
                ];
            }
        }

        // [MODIFICADO] Guardamos el precio tras tarifas como el precio "autoritativo" de la línea.
        // Las promociones y cupones NO deben restar de este precio unitario para evitar dobles descuentos 
        // y para cumplir con el requerimiento de que el precio del producto no cambie visualmente.
        $precioTrasTarifas = $precioActual;

        // 2. Aplicar Promociones de volumen / Bundle
        $promos = self::obtenerPromocionesAplicables($idProducto, $idCliente, $cantidad);
        foreach ($promos as $p) {
            $importePromo = 0;
            if ($p['tipo'] === 'percent') {
                $importePromo = round($precioTrasTarifas * ($p['valor'] / 100), 2);
            } elseif ($p['tipo'] === 'amount') {
                $importePromo = (float)$p['valor'];
            } elseif ($p['tipo'] === 'bundle' && $p['bundle_buy_qty'] > 0) {
                // Ejemplo 3x2: compras 3, pagas 2. 
                $unidadesGratis = floor($cantidad / $p['bundle_buy_qty']) * ($p['bundle_buy_qty'] - $p['bundle_pay_qty']);
                if ($unidadesGratis > 0) {
                    $importeTotalPromo = $precioTrasTarifas * $unidadesGratis;
                    $importePromo = round($importeTotalPromo / $cantidad, 2);
                }
            }

            if ($importePromo > 0) {
                // [IMPORTANTE] NO restamos de $precioActual (que se convertirá en precio_unitario_final)
                $descuentos[] = [
                    'id_origen' => $p['id'],
                    'tipo_descuento' => 'promocion',
                    'nombre' => $p['label'],
                    'valor_descontado' => -$importePromo
                ];
            }
        }

        // 3. Aplicar Cupones / Socio
        if ($codigoCupon) {
            $cupon = self::obtenerCuponAplicable($codigoCupon, $idProducto, $idCliente);
            if ($cupon) {
                $importeCupon = 0;
                if ($cupon['tipo'] === 'percent') {
                    $importeCupon = round($precioTrasTarifas * ($cupon['valor'] / 100), 2);
                } else {
                    $importeCupon = (float)$cupon['valor'];
                }

                if ($importeCupon > 0) {
                    // [IMPORTANTE] NO restamos de $precioActual
                    $descuentos[] = [
                        'id_origen' => $cupon['id'],
                        'tipo_descuento' => 'cupon',
                        'nombre' => "Cupón: " . $codigoCupon,
                        'valor_descontado' => -$importeCupon
                    ];
                }
            }
        }

        return [
            'precio_base' => $precioBase,
            'precio_unitario_final' => round($precioTrasTarifas, 2),
            'total_descuento_unitario' => round($precioBase - $precioTrasTarifas, 2),
            'descuentos' => $descuentos
        ];
    }

    private static function obtenerTarifasAplicables(int $idProducto, ?int $idCliente, bool $ignoreContextFilters = false): array
    {
        // En la nueva estructura, usamos tarifa_productos para scope='productos'
        $ahora = date('Y-m-d');
        $pro = ProductoPDO::obtenerProductoPorId($idProducto);
        $cat = $pro['categoria'] ?? '';

        // Obtenemos todas las activas hoy basándose solo en el estado 'activo' y fechas globales
        // Si ignoramos filtros de contexto, al menos deben seguir siendo vigentes por fecha
        $sql = "SELECT t.* FROM tarifas_precios t 
                WHERE t.activo = 1";
        
        if (!$ignoreContextFilters) {
            $sql .= " AND t.aplicada = 0";
        }
        
        $params = [];
        if (!$ignoreContextFilters) {
            $sql .= " AND t.fecha_aplicacion <= :hoy 
                      AND (t.fecha_fin IS NULL OR t.fecha_fin >= :hoy)";
            $params[':hoy'] = $ahora;
        }

        $q = DBPDO::ejecutarConsulta($sql, $params);
        $todas = $q->fetchAll(PDO::FETCH_ASSOC);

        // Carga en una sola query todas las tarifas que incluyen este producto (evita N+1)
        $qTp = DBPDO::ejecutarConsulta(
            "SELECT id_tarifa FROM tarifa_productos WHERE id_producto = :p",
            [':p' => (int)$idProducto]
        );
        $tarifasDelProducto = array_flip(array_column($qTp->fetchAll(PDO::FETCH_ASSOC), 'id_tarifa'));

        $aplicables = [];
        foreach ($todas as $t) {
            $aplica = false;

            // Defensive: ensure product exists
            if (!$pro) return [];

            // 0. Verificar exclusión explícita
            $excluidos = json_decode($t['excluidos'] ?? '[]', true) ?: [];
            if (in_array((int)$idProducto, array_map('intval', $excluidos))) {
                continue;
            }

            // Verificar Scope
            if ($t['scope'] === 'todos') {
                $aplica = true;
            } elseif ($t['scope'] === 'categoria' && $t['categoria'] === $cat) {
                $aplica = true;
            } elseif ($t['scope'] === 'productos') {
                if (isset($tarifasDelProducto[(int)$t['id']])) $aplica = true;
            }

            if ($aplica && !$ignoreContextFilters && !self::esValidoPorFiltros($t, $idCliente)) {
                $aplica = false;
            }

            if ($aplica) {
                $aplicables[] = $t;
            }
        }

        // Ordenar por prioridad DESC
        usort($aplicables, function ($a, $b) {
            return $b['prioridad'] <=> $a['prioridad'];
        });

        return $aplicables;
    }

    private static function obtenerPromocionesAplicables(int $idProducto, ?int $idCliente, int $cantidad, bool $ignoreContextFilters = false): array
    {
        $todas = $ignoreContextFilters ? PromocionPDO::listarTodasActivas() : PromocionPDO::listarActivas();
        $pro = ProductoPDO::obtenerProductoPorId($idProducto);
        $cat = $pro['categoria'] ?? '';

        $aplicables = [];
        foreach ($todas as $p) {
            // Si es un cupón (tiene código), no se aplica automáticamente aquí
            if (!empty($p['codigo'])) continue;

            // 0. Verificar exclusión explícita
            $excluidos = json_decode($p['excluidos'] ?? '[]', true) ?: [];
            if (in_array($idProducto, $excluidos)) {
                continue;
            }

            $aplica = false;
            if (!$p['id_producto'] && !$p['producto_ids'] && !$p['categoria_code']) {
                $aplica = true; // General
            } elseif ((int)$p['id_producto'] === (int)$idProducto) {
                $aplica = true;
            } elseif ($p['producto_ids']) {
                $ids = json_decode($p['producto_ids'], true) ?: [];
                if (in_array((int)$idProducto, array_map('intval', $ids))) $aplica = true;
            } elseif ($p['categoria_code'] == $cat) {
                $aplica = true;
            }

            if ($aplica && !$ignoreContextFilters && !self::esValidoPorFiltros($p, $idCliente)) {
                $aplica = false;
            }

            if ($aplica) {
                $aplicables[] = $p;
            }
        }
        return $aplicables;
    }

    private static function obtenerCuponAplicable(string $codigo, int $idProducto, ?int $idCliente): ?array
    {
        $ahora = date('Y-m-d H:i:s');
        $sql = "SELECT * FROM promociones 
                WHERE codigo = :codigo 
                AND activo = 1 
                AND (fecha_inicio IS NULL OR fecha_inicio <= :ahora) 
                AND (fecha_fin IS NULL OR fecha_fin >= :ahora)
                LIMIT 1";
        $q = DBPDO::ejecutarConsulta($sql, [':codigo' => $codigo, ':ahora' => $ahora]);
        $c = $q->fetch(PDO::FETCH_ASSOC);

        if (!$c) return null;

        // 0. Verificar exclusión explícita
        $excluidos = json_decode($c['excluidos'] ?? '[]', true) ?: [];
        if (in_array($idProducto, $excluidos)) {
            return null;
        }

        // Verificar si aplica al producto
        $pro = ProductoPDO::obtenerProductoPorId($idProducto);
        $cat = $pro['categoria'] ?? '';

        if ($c['id_producto'] && $c['id_producto'] != $idProducto) {
            // Check if it's in producto_ids before rejecting
            $inIds = false;
            if ($c['producto_ids']) {
                $ids = json_decode($c['producto_ids'], true) ?: [];
                if (in_array((int)$idProducto, $ids)) $inIds = true;
            }
            if (!$inIds) return null;
        } elseif (!$c['id_producto'] && $c['producto_ids']) {
            $ids = json_decode($c['producto_ids'], true) ?: [];
            if (!in_array((int)$idProducto, $ids)) return null;
        }

        if ($c['categoria_code'] && $c['categoria_code'] != $cat) return null;

        if (!self::esValidoPorFiltros($c, $idCliente)) return null;

        return $c;
    }

    private static function esValidoPorFiltros(array $item, ?int $idCliente): bool
    {
        $isSpecificClient = false;

        // 1. Validar por Cliente Específico (Solo para Tarifas si tienen id_cliente o cliente_ids)
        if (!empty($item['id_cliente'])) {
            $isSpecificClient = true;
            if ((int)$item['id_cliente'] !== (int)$idCliente) {
                return false;
            }
        }
        
        if (!empty($item['cliente_ids'])) {
            $isSpecificClient = true;
            if (!$idCliente) return false;
            $ids = json_decode($item['cliente_ids'], true) ?: [];
            if (!in_array((int)$idCliente, $ids)) {
                return false;
            }
        }

        // 2. Validar Día de la Semana
        if (!empty($item['dias_semana'])) {
            $hoySemana = date('w'); // 0 (Dom) a 6 (Sab)
            $mapaDias = ['dom', 'lun', 'mar', 'mie', 'jue', 'vie', 'sab'];
            $diaActual = $mapaDias[$hoySemana];
            $diasPermitidos = array_map('trim', explode(',', strtolower($item['dias_semana'])));
            if (!in_array($diaActual, $diasPermitidos)) return false;
        }

        // 3. Validar Franja Horaria (Hora Actual)
        if (!empty($item['hora_inicio']) || !empty($item['hora_fin'])) {
            $ahoraHora = date('H:i:s');
            $hInicio = $item['hora_inicio'] ?: '00:00:00';
            $hFin = $item['hora_fin'] ?: '23:59:59';
            if ($ahoraHora < $hInicio || $ahoraHora > $hFin) return false;
        }

        // 4. Validar Segmento de Clientes (Roles / tipo_cliente)
        if (!$isSpecificClient) {
            $hasRoles = !empty($item['roles_segmento']);
            $hasLegacyType = (!empty($item['tipo_cliente']) && $item['tipo_cliente'] !== 'todos');

            if ($hasRoles || $hasLegacyType) {
                if (!$idCliente) return false;

                // Obtener rol del cliente
                $c = DBPDO::ejecutarConsulta("SELECT rol FROM clientes WHERE id = :id", [':id' => $idCliente])->fetch();
                $rolCliente = strtolower($c['rol'] ?? 'general');

                // Validar contra roles_segmento (nuevo)
                if ($hasRoles) {
                    $segmentosPermitidos = array_map('trim', explode(',', strtolower($item['roles_segmento'])));
                    if (!in_array($rolCliente, $segmentosPermitidos)) return false;
                }

                // Validar contra tipo_cliente (legacy)
                if ($hasLegacyType) {
                    $tipoReq = strtolower($item['tipo_cliente']);
                    if ($tipoReq === 'mayorista') {
                        // El mayorista es un ROL ahora
                        if ($rolCliente !== 'mayorista') return false;
                    } elseif ($tipoReq !== $rolCliente) {
                        return false;
                    }
                }
            }
        }

        // 5. Soporte Legacy "Solo Socios" - Solo si no es cliente específico
        if (!$isSpecificClient && !empty($item['es_solo_socios'])) {
            if (!$idCliente) return false;
            $c = DBPDO::ejecutarConsulta("SELECT rol FROM clientes WHERE id = :id", [':id' => $idCliente])->fetch();
            if (strtolower($c['rol'] ?? '') !== 'socio') return false;
        }

        return true;
    }

    public static function getRulesForProduct(int $idProducto): array
    {
        // Reutilizamos la lógica de obtención pero devolviendo objetos descriptivos
        // Para el panel de administración (gestión de exclusiones), ignoramos filtros de contexto
        // para que se vean todas las reglas que *podrían* aplicar al producto.
        $debug_totals = [
            'tarifas_activas_global' => (int)DBPDO::ejecutarConsulta("SELECT COUNT(*) FROM tarifas_precios WHERE activo = 1")->fetchColumn(),
            'promos_activas_global' => (int)DBPDO::ejecutarConsulta("SELECT COUNT(*) FROM promociones WHERE activo = 1")->fetchColumn()
        ];
        $tarifas = self::obtenerTarifasAplicables($idProducto, null, true);
        $promos  = self::obtenerPromocionesAplicables($idProducto, null, 1, true);

        $res = [];
        foreach ($tarifas as $t) {
            $res[] = [
                'id'         => $t['id'],
                'nombre'     => $t['nombre'],
                'tipo_regla' => 'tarifa',
                'tipo'       => $t['tipo'],
                'valor'      => (float)$t['valor'],
                'prioridad'  => (int)$t['prioridad'],
                'aplicada'   => (int)($t['aplicada'] ?? 0),
                'is_permanent' => ((int)($t['aplicada'] ?? 0) === 1 && 
                                   empty($t['tipo_cliente']) && 
                                   empty($t['roles_segmento']) && 
                                   empty($t['dias_semana']) && 
                                   empty($t['hora_inicio']) && 
                                   empty($t['hora_fin']))
            ];
        }
        foreach ($promos as $p) {
            $res[] = [
                'id'         => $p['id'],
                'nombre'     => $p['label'],
                'tipo_regla' => 'promocion',
                'tipo'       => $p['tipo'],
                'valor'      => (float)$p['valor'],
                'prioridad'  => (int)$p['prioridad'],
                'aplicada'   => 0,
                'is_permanent' => false // Promociones are dynamic
            ];
        }

        return [
            'reglas' => $res,
            'debug_stats' => $debug_totals
        ];
    }
}
