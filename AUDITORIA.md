# Auditoría de Funcionalidades — ElectroBazar TPV

## Contexto
Proyecto PHP MVC (TPV para tienda electrónica). Tiene 40+ archivos modificados sin commit, integración VeriFactu activa, y migración reciente a permisos granulares. El objetivo es identificar qué áreas revisar antes de continuar.

---

## Mapa de Módulos y Estado

### 🔴 CRÍTICO — Revisar Primero

#### 1. VeriFactu / AEAT
- **Archivos clave:** `model/VeriFactuService.php` (486 líneas) · `model/AeatQueueService.php` · `model/FirmaService.php` · `model/AeatApi.php` · `verifactu_log.php`
- **Estado:** `VeriFactuService.php` modificado sin commit
- **Qué hace:** Genera XML reglamentario, encadena hashes criptográficos FIFO, firma XAdES-BES con certificado P12, envía a AEAT por SOAP
- **Revisar:**
  - El `storage/debug_hash.txt` existe como archivo no rastreado — posible artefacto de pruebas que debería eliminarse
  - Certificado P12 con contraseña `1234` referenciado en `config/config.php` — sólo sirve para entorno de pruebas, verificar que el entorno de producción tiene el real
  - Cola de envío: ¿se procesan automáticamente los pendientes o requiere intervención manual?
  - Test suite (`3c306ea`) añadida recientemente — verificar si está activa o es código temporal

#### 2. Generación de PDF y Ticket
- **Archivos clave:** `core/PDFServiceV2.php` (545 líneas) · `api/generarPDFTicket.php`
- **Estado:** Ambos modificados sin commit
- **Qué hace:** Genera tickets (80mm) y facturas (A4) con QR VeriFactu; incrusta desglose de IVA, descuentos, métodos de pago
- **Revisar:**
  - Encoding UTF-8 → windows-1252 para FPDF puede romper caracteres (ñ, acentos) en algunos idiomas — probar ticket con texto en catalán/euskera
  - ¿El QR de verificación funciona en entorno de pruebas AEAT?

---

### 🟡 IMPORTANTE — Revisar Esta Semana

#### 3. Motor de Precios (PriceEngine)
- **Archivos clave:** `model/PriceEngine.php` · `api/recalcularPreciosCarrito.php` · `webroot/js/modules/CartManager.js`
- **Estado:** `CartManager.js` modificado sin commit
- **Qué hace:** Aplica tarifas dinámicas (por fecha/hora/cliente/rol), descuentos %, importe fijo, bundles (2x1, precio fijo), cupones, puntos de fidelidad
- **Revisar:**
  - Doble cálculo: PriceEngine en backend Y CartManager.js en frontend — ¿están sincronizados? Una discrepancia puede hacer que el precio mostrado no coincida con el guardado
  - Tarifas por horario: ¿se evalúan en zona horaria correcta (Madrid) o UTC?
  - Puntos de fidelidad: fix reciente en `6d6f649` (cálculo puntos ganados) — verificar que el cálculo es correcto con ventas de importe fraccionado

#### 4. Flujo de Pago
- **Archivos clave:** `webroot/js/modules/PaymentManager.js` · `api/guardarVenta.php` · `model/VentaPDO.php`
- **Estado:** `PaymentManager.js` modificado sin commit
- **Qué hace:** Gestiona efectivo, tarjeta, Bizum, a cuenta, pago mixto; calcula cambio; registra pago en BD
- **Revisar:**
  - Pago mixto (efectivo + tarjeta): ¿se valida que la suma de métodos = total? Un redondeo puede crear discrepancias en el arqueo
  - Pago "a cuenta": ¿queda registrado como deuda pendiente del cliente? Revisar `model/CajaDeudaPDO.php`
  - Bizum requiere teléfono — ¿se valida formato antes de guardar?

#### 5. Permisos / RBAC
- **Archivos clave:** `model/UsuarioPDO.php` · `api/rolPermisos.php` · `view/vUsuarios.php` · `api/gestionUsuario.php` (nuevo, sin rastrear)
- **Estado:** `UsuarioPDO.php` modificado; `api/gestionUsuario.php` es nuevo archivo no rastreado
- **Qué hace:** Sistema RBAC granular migrado recientemente desde roles simples
- **Revisar:**
  - `api/gestionUsuario.php` es nuevo y no está en git — revisar si está completo y seguro (validación de permisos, CSRF)
  - Permiso `vender` en TPV: ¿está protegido explícitamente o se da por implícito?
  - Probar que un usuario con rol "cajero" no puede acceder a `/view/vProductos.php` ni a endpoints de gestión

#### 6. Analítica y Dashboard
- **Archivos clave:** `controller/cAnalitica.php` · `model/AnaliticaPDO.php` · `view/vAnalitica.php` · `view/vDashboard.php`
- **Estado:** Todos modificados sin commit
- **Qué hace:** KPIs de ventas (total, margen, operaciones), reportes históricos, exportación CSV/JSON
- **Revisar:**
  - KPI "margen": ¿usa precio coste del CMP o precio coste del producto? Puede dar valores erróneos si no se actualiza el CMP
  - Exportación CSV: ¿los importes con decimales usan coma o punto? Puede romper la apertura en Excel español

---

### 🟢 ESTABLE — Revisar si hay tiempo

#### 7. Gestión de Inventario y Stock
- **Archivos clave:** `model/ProductoPDO.php` · `api/entradaStock.php` · `model/EntradaStockPDO.php` · `model/MovimientoStockPDO.php`
- **Estado:** `ProductoPDO.php` modificado sin commit
- **Qué hace:** CMP (Coste Medio Ponderado), historial de movimientos, bajo stock
- **Revisar:**
  - Precisión decimal (`v10_product_precision.sql`): ¿los productos con precio fraccionado (0.0001) funcionan bien en el ticket PDF?
  - Packs (productos compuestos): al vender un pack, ¿descuenta el stock de los componentes?

#### 8. Proveedores y Compras
- **Archivos clave:** `model/Proveedor.php` · `model/ProveedorPDO.php` · `api/proveedores.php` · `view/vProveedores.php`
- **Estado:** Todos modificados sin commit
- **Qué hace:** CRUD de proveedores, albaranes, facturas de compra, pedido automático de reposición
- **Revisar:**
  - Campo NIF-IVA intracomunitario añadido en v6 — ¿se valida formato correcto (ES/FR/DE + número)?
  - Pedido automático (`api/generarPedidoAuto.php`): ¿qué umbral de stock dispara el pedido? ¿es configurable?

#### 9. Clientes y Fidelización
- **Archivos clave:** `api/gestionCliente.php` · `model/ClientePDO.php` · `model/ValePDO.php`
- **Estado:** `gestionCliente.php` modificado sin commit
- **Qué hace:** CRUD clientes, NIF/CIF, roles dinámicos, puntos/vales
- **Revisar:**
  - Validación de NIF/CIF: ¿usa `231018libreriaValidacion.php`? Probar con NIF inválido
  - Vales de compra: ¿caduca el vale en algún momento?

#### 10. Tipos de IVA
- **Archivos clave:** `model/TipoIVAPDO.php` · `api/gestionTipoIva.php` · `view/vTiposIVA.php`
- **Estado:** Todos modificados sin commit
- **Qué hace:** IVA con vigencia histórica por fechas
- **Revisar:**
  - ¿Qué pasa si se crea un producto con IVA que ya no está vigente? ¿lo bloquea o guarda el tipo igualmente?

#### 11. Configuración y Multiidioma
- **Archivos clave:** `view/vConfiguracion.php` · `lang/*.php` (11 idiomas, todos modificados)
- **Estado:** Todos modificados sin commit
- **Qué hace:** Parámetros empresa, logo, idioma, tema visual
- **Revisar:**
  - Se añadieron claves nuevas (`c74cc2d`) — verificar que **todos los idiomas** tienen las nuevas claves (si falta una, el TPV puede mostrar clave cruda en lugar de texto)

#### 12. Cierre de Caja
- **Archivos clave:** `controller/cCierreCaja.php` · `api/cajaInfoCierre.php` · `model/CierreFiscalPDO.php`
- **Estado:** No modificados — estable
- **Qué hace:** Arqueo por método de pago, cierre Z fiscal, turno
- **Revisar:**
  - Después de migrar a `pagos_venta` como fuente de verdad (`f35ca51`), verificar que el cierre Z refleja importes correctos
  - ¿El documento "Declaración Responsable" del RD 1007/2023 se genera y descarga correctamente desde `PDFServiceV2`?

---

## Archivos Sueltos a Limpiar

| Archivo | Problema |
|---------|----------|
| `storage/debug_hash.txt` | Artefacto de testing — eliminar |
| `api/gestionUsuario.php` | Nuevo, sin rastrear — revisar y añadir a git |
| `CLAUDE.md` | Sin rastrear — añadir a git |

---

## Cambios Pendientes de Commit

**40+ archivos modificados** en rama `developer`. Antes de continuar:
1. Revisar cada módulo según esta auditoría
2. Agrupar cambios relacionados en commits por módulo
3. Eliminar `storage/debug_hash.txt`

---

## Orden de revisión recomendado

1. VeriFactu (crítico, compliance fiscal)
2. Flujo de pago + PriceEngine (dinero)
3. Permisos RBAC + `gestionUsuario.php` nuevo (seguridad)
4. Idiomas (todas las claves nuevas presentes en los 11 archivos)
5. Analítica (KPIs correctos)
6. Resto por orden de uso habitual
