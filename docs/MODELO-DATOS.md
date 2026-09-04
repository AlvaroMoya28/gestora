# Modelo de datos

Motor: **MySQL 8** · ORM: **EF Core 8 (Pomelo)** · Instalación: [`database/README.md`](../database/README.md)

## 1. Implementado en la fase 1

```
Company
  ├── Role ──── RolePermission        (permisos por módulo)
  ├── User ──── RefreshToken          (sesiones abiertas)
  ├── Category
  ├── Unit
  ├── Customer
  ├── Supplier
  ├── Product ──┐ (CategoryId, UnitId)
  │             ▼
  │        InventoryMovement          (asientos de existencias)
  └── AuditLog                        (bitácora)
```

### Campos comunes

Toda entidad tiene `Id`, `CreatedAt`, `CreatedByUserId`, `UpdatedAt`, `UpdatedByUserId`,
rellenados por el `DbContext`. Las entidades de negocio tienen además `CompanyId`.

### Tablas

**`companies`** — empresa inquilina. `Currency` y `DefaultTaxRate` definen los valores
por defecto de sus documentos.

**`roles` / `rolepermissions`** — un rol tiene una fila por módulo con `CanRead` y
`CanWrite`. `ModuleKey` se valida contra `Common/ModuleCatalog.cs`. Los roles de
sistema (`IsSystem`) no pueden quedarse sin la gestión de usuarios.

**`users`** — `PasswordHash` + `PasswordSalt` en formato `PBKDF2$iteraciones$sal`.
`FailedLoginAttempts` y `LockedUntil` implementan el bloqueo temporal.
Único por `(CompanyId, Email)`.

**`refreshtokens`** — tabla propia, no columna del usuario: permite varias sesiones
simultáneas y revocarlas individualmente. Se rotan en cada uso y se purgan al vencer.

**`categories` / `units`** — catálogos por empresa. `Unit.DecimalPlaces` indica cuántos
decimales admite la cantidad (0 para piezas enteras, 3 para kilogramos).

**`customers` / `suppliers`** — `Code` único por empresa, autogenerado (`CLI-0001`,
`PRV-0001`) si no se indica. `PaymentTerm` (contado/crédito) y `CreditDays` gobiernan
cómo se registrarán sus documentos. Baja lógica con `IsActive`.

**`products`** — catálogo único con `Type`: materia prima, producto terminado o
servicio. Que sean una sola tabla es lo que permitirá a producción consumir unos y
generar otros sin duplicar el modelo.

- `Cost`, `Price`, `TaxRate` → `decimal(18,2)`
- `Stock`, `MinStock` → `decimal(18,4)`
- `Stock` es un **saldo derivado**: solo lo escribe `InventoryService`.
- Índice por `(CompanyId, Barcode)` para la lectura con pistola.

**`inventorymovements`** — el libro mayor del inventario:

| Campo | Para qué |
|---|---|
| `Type` | Compra, venta, consumo de producción, ajuste, devolución… |
| `Direction` | +1 entrada, −1 salida |
| `Quantity` | Siempre positiva; el signo lo da `Direction` |
| `StockAfter` | Existencia resultante, para auditar sin recalcular |
| `UnitCost` | Costo del movimiento cuando aplica |
| `Reason` | Texto libre del motivo |
| `ReferenceType` / `ReferenceId` | Documento que lo originó (`Sale`, `Purchase`…) |
| `UserId`, `OccurredAt` | Quién y cuándo |

Índice por `(CompanyId, ProductId, OccurredAt)`: la consulta habitual es el historial
de un producto en orden cronológico.

**`auditlogs`** — usuario, acción, módulo, entidad, id, descripción, **valor anterior
y valor nuevo**, IP y fecha. Se escribe en la misma transacción que la operación auditada.

## 2. Reglas que el modelo hace cumplir

1. Ninguna consulta cruza empresas: filtro global sobre `CompanyId`.
2. Las existencias no se editan: se mueven.
3. No hay existencias negativas salvo ajuste explícitamente autorizado.
4. Los importes son `decimal`, nunca coma flotante.
5. Los registros con historial no se borran: se inactivan.
6. Los códigos son únicos por empresa, no globalmente.
7. No se puede inactivar una unidad de medida que usan productos activos.

## 3. Diseñado, pendiente de implementar

El modelo está pensado para que estas entidades entren sin rehacer lo existente:

```
Compras     PurchaseOrder → PurchaseReceipt → SupplierInvoice
                                 ↓                  ↓
                          InventoryMovement   AccountPayable → Payment

Ventas      CustomerOrder → Sale → ExternalInvoice
                             ↓            ↓
                    InventoryMovement  AccountReceivable → Payment

Producción  Product → BillOfMaterial → BomItem
                             ↓
                     ProductionOrder → InventoryMovement (salida de MP, entrada de PT)

Servicio    RepairOrder → RepairMaterial → Sale

Finanzas    Expense, Income, Payment
```

Puntos ya resueltos que lo permiten:

- `InventoryMovement.ReferenceType` / `ReferenceId` esperan a los documentos.
- `MovementType` ya contempla producción, reparación y devoluciones.
- `ApplyMovementAsync` se suma a una transacción abierta, así una venta con cinco
  líneas es una sola operación atómica.
- `PaymentTerm` y `CreditDays` ya están en clientes y proveedores.
- El catálogo de módulos ya declara compras, ventas, producción, reparaciones y finanzas.

**Los pagos serán una entidad**, nunca un campo `paid = true`: solo así se manejan
abonos parciales, fechas, métodos y saldos reales.
