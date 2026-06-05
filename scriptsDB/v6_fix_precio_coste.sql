-- =====================================================================
-- v6_fix_precio_coste.sql
-- Migración: corregir precio_coste (CMP) y precio_venta de todos
-- los productos existentes tras el cambio de fórmula:
--
--   ANTES: precio_coste = precio_proveedor × (1 + IVA% + RE%)
--   AHORA: precio_coste = precio_proveedor × (1 + RE%)
--          precio_venta = precio_coste × (1 + margen%) × (1 + IVA%)
--
-- El IVA soportado NO es un coste: queda neutralizado por el IVA
-- repercutido al cliente. Solo el RE es un coste real no recuperable.
-- =====================================================================

-- Paso 1: Productos con proveedor con recargo de equivalencia
--         precio_coste = precio_proveedor × (1 + RE/100)
UPDATE productos p
  JOIN proveedores pv ON p.id_proveedor = pv.id AND pv.aplica_re = 1
  JOIN tipos_iva t ON p.id_tipo_iva = t.id
SET p.precio_coste = ROUND(p.precio_proveedor * (1 + t.recargo_equivalencia / 100), 4)
WHERE p.es_pack = 0 AND p.precio_proveedor > 0;

-- Paso 2: Productos con proveedor sin recargo de equivalencia
--         precio_coste = precio_proveedor (sin ningún recargo)
UPDATE productos p
  JOIN proveedores pv ON p.id_proveedor = pv.id AND pv.aplica_re = 0
SET p.precio_coste = ROUND(p.precio_proveedor, 4)
WHERE p.es_pack = 0 AND p.precio_proveedor > 0;

-- Paso 3: Recalcular PVP para productos con margen definido
--         precio_venta = precio_coste × (1 + margen/100) × (1 + IVA/100)
UPDATE productos p
  JOIN tipos_iva t ON p.id_tipo_iva = t.id
SET p.precio_venta = ROUND(p.precio_coste * (1 + p.margen / 100) * (1 + t.porcentaje / 100), 2)
WHERE p.es_pack = 0 AND p.margen > 0 AND p.precio_coste > 0;
