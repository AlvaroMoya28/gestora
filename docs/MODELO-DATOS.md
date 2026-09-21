# Modelo de datos

Motor: **MySQL 8** · ORM: **EF Core 8 (Pomelo)** · Instalación: [`database/README.md`](../database/README.md)

## 1. El mapa completo

```
PLATAFORMA (sin CompanyId: es Gestora como negocio)
  Role ──── RolePermission            catálogo fijo de roles y sus módulos
  Plan
  Company ──── Subscription ──── SubscriptionPayment
  User ──── RefreshToken              CompanyId nulo en los usuarios de Gestora

EMPRESA (todo lleva CompanyId y filtro global)
  Category   Unit   Customer   Supplier
  Product ──────────────┐ (CategoryId, UnitId)
                        ▼
                 InventoryMovement    libro mayor de existencias
                        ▲
      ┌─────────────────┼────────────────┬──────────────────┐
      │                 │                │                  │
  Purchase          Sale            ProductionOrder     RepairOrder
   └ PurchaseItem    └ SaleItem      └ ProductionMaterial └ RepairMaterial
      │                 │                ▲                  │
      ▼                 ▼             Recipe ── RecipeItem  ▼
  AccountPayable    AccountReceivable ◄───────────────────────┘
   └ Payment         └ Receipt
      │                 │
      └────────►  FinanceEntry ──── FinanceCategory
                  (asiento de caja: ingreso o gasto)

  AuditLog                            bitácora de operaciones sensibles
```

### Campos comunes

Toda entidad tiene `Id`, `CreatedAt`, `CreatedByUserId`, `UpdatedAt`, `UpdatedByUserId`,
rellenados por el `DbContext`. Las entidades de empresa tienen además `CompanyId` con
filtro global: ninguna consulta puede ver datos de otra empresa aunque olvide el `Where`.

---

## 2. Identidad y plataforma

**`companies`** — empresa suscrita. `AccountEmail` es el correo de la cuenta que se
entrega al contratar y es único en toda la plataforma. `Currency` y `DefaultTaxRate`
definen los valores por defecto de sus documentos. `Status` (activa / suspendida /
dada de baja) decide si sus usuarios pueden entrar.

**`roles` / `rolepermissions`** — catálogo **fijo y global**, no por empresa. Cuatro
roles con `Scope`: `developer` y `platform_admin` en la plataforma, `company_admin` y
`company_viewer` dentro de una empresa. Cada rol tiene una fila por módulo con
`CanRead` y `CanWrite`; `ModuleKey` se valida contra `Common/ModuleCatalog.cs` y los
permisos se recalculan en cada arranque.

**`users`** — `PasswordHash` + `PasswordSalt` en formato `PBKDF2$iteraciones$sal`.
`FailedLoginAttempts` y `LockedUntil` implementan el bloqueo temporal. **`CompanyId` es
nulo** en los usuarios de Gestora. El correo es único globalmente, porque el login no
sabe todavía a qué empresa pertenece quien entra. `TourCompletedAt` guarda cuándo la
persona terminó o saltó el recorrido guiado de bienvenida; mientras es nulo, se le
muestra al entrar. Va en el usuario y no en el navegador para que salga una sola vez
por persona, aunque cambie de computadora.

**`refreshtokens`** — tabla propia, no columna del usuario: permite varias sesiones
simultáneas y revocarlas individualmente. Se rotan en cada uso.
`ImpersonatedCompanyId` conserva la empresa que el desarrollador está viendo, para que
renovar el token no lo saque de ahí.

**`plans` / `subscriptions` / `subscriptionpayments`** — lo comercial de Gestora.
`Subscription.Price` es el precio pactado, que puede diferir del precio de lista del
plan. Un cobro corre `EndDate` tantos meses como `Plan.BillingPeriodMonths` × períodos.
Detalle en [`ARQUITECTURA-SAAS.md`](ARQUITECTURA-SAAS.md).

---

## 3. Catálogo e inventario

**`categories` / `units`** — catálogos por empresa. `Unit.DecimalPlaces` indica cuántos
decimales admite la cantidad (0 para piezas enteras, 3 para kilogramos).

**`customers` / `suppliers`** — `Code` único por empresa, autogenerado (`CLI-0001`,
`PRV-0001`) si no se indica. `PaymentTerm` (contado/crédito) y `CreditDays` gobiernan
cómo se registran sus documentos. Baja lógica con `IsActive`.

**`products`** — catálogo único con `Type`: materia prima, producto terminado o
servicio. Que sean una sola tabla es lo que permite a producción consumir unos y
generar otros sin duplicar el modelo, y que un servicio se facture sin tocar existencias.

- `Cost`, `Price`, `TaxRate` → `decimal(18,2)`
- `Stock`, `MinStock` → `decimal(18,4)`
- `Stock` es un **saldo derivado**: solo lo escribe `InventoryService`.
- Índice por `(CompanyId, Barcode)` para la lectura con pistola.

**`inventorymovements`** — el libro mayor del inventario:

| Campo | Para qué |
|---|---|
| `Type` | Compra, venta, consumo de producción, producción terminada, ajuste, consumo en reparación, devolución… |
| `Direction` | +1 entrada, −1 salida |
| `Quantity` | Siempre positiva; el signo lo da `Direction` |
| `StockAfter` | Existencia resultante, para auditar sin recalcular |
| `UnitCost` | Costo del movimiento cuando aplica |
| `ReferenceType` / `ReferenceId` | Documento que lo originó (`Sale`, `Purchase`, `ProductionOrder`, `RepairOrder`) |
| `UserId`, `OccurredAt` | Quién y cuándo |

Índice por `(CompanyId, ProductId, OccurredAt)`: la consulta habitual es el historial
de un producto en orden cronológico.

---

## 4. Documentos de operación

Los cuatro documentos comparten una misma idea: **un borrador se edita libremente y
hay un punto sin retorno** donde, en una sola transacción, se mueve el inventario y
nace la obligación de dinero. Si algo falla, no queda nada a medias.

| Documento | Punto sin retorno | Qué ocurre ahí |
|---|---|---|
| `purchases` | Confirmar | Entra inventario · nace `AccountPayable` |
| `sales` | Confirmar | Sale inventario · nace `AccountReceivable` |
| `productionorders` | Terminar | Sale materia prima · entra producto fabricado · se fija su costo |
| `repairorders` | Terminar y entregar | Al terminar sale el material; al entregar nace `AccountReceivable` |

**`sales` / `saleitems`** — `SaleItem.UnitCost` congela el costo del producto al
confirmar: sin él, la utilidad de una venta cambiaría cada vez que el producto se
encarece. `DiscountRate` es por línea; los totales de la cabecera se recalculan al
guardar, nunca se reciben del cliente.

**`recipes` / `recipeitems`** — la lista de materiales de un producto fabricado.
`OutputQuantity` es lo que rinden las cantidades declaradas, de modo que una orden se
arma escalando la receta a lo que se quiere producir.

**`productionorders` / `productionmaterials`** — `PlannedQuantity` es lo previsto y
`ConsumedQuantity` lo realmente usado, que casi nunca coincide. El costo unitario
resultante —(materiales + mano de obra) ÷ producido— se escribe en `Product.Cost`:
lo fabricado se valora por lo que costó hacerlo.

**`repairorders` / `repairmaterials`** — trabajo sobre un artículo del cliente, que no
es del catálogo: por eso `ItemDescription` es texto libre. Se cobra mano de obra más
materiales, y esos materiales sí salen del inventario de la empresa.

---

## 5. Dinero

**`accountspayable` / `payments`** y **`accountsreceivable` / `receipts`** son espejo
uno del otro: `Total`, saldo cobrado o pagado, `Balance` recalculado en cada abono, y
los abonos como **entidad propia** —nunca un campo `paid = true`—, lo único que permite
pagos parciales con su fecha, método, referencia y responsable.

Una cuenta nace incluso en las operaciones de contado, con vencimiento el mismo día.
Registrar la deuda y su pago por separado es lo que hace que una venta de contado que
nadie llegó a cobrar aparezca como pendiente en lugar de perderse.

**`financecategories` / `financeentries`** — el libro de caja. `Kind` separa ingresos de
gastos y `Source` dice de dónde salió el asiento:

| `Source` | Origen | Editable |
|---|---|---|
| `Manual` | Cargado a mano en ingresos o gastos | Sí |
| `Receipt` | Un cobro de una cuenta por cobrar | No |
| `Payment` | Un pago de una cuenta por pagar | No |

Los automáticos se crean en la **misma transacción** que el cobro o pago que los
origina, y no se editan desde finanzas: su verdad vive en la cuenta que los generó.
Así el flujo de caja está completo sin digitar nada dos veces y sin arriesgar dos
saldos distintos para el mismo dinero.

Las categorías con `IsSystem` respaldan esos asientos automáticos y no se editan ni
se inactivan.

**`auditlogs`** — usuario, acción, módulo, entidad, id, descripción, **valor anterior
y valor nuevo**, IP y fecha. Se escribe en la misma transacción que la operación auditada.

---

## 6. Reglas que el modelo hace cumplir

1. Ninguna consulta cruza empresas: filtro global sobre `CompanyId`.
2. Las existencias no se editan: se mueven.
3. No hay existencias negativas salvo ajuste explícitamente autorizado.
4. Los importes son `decimal`, nunca coma flotante.
5. Los documentos y las cuentas no se borran: cambian de estado.
6. Los códigos y números de documento son únicos por empresa, no globalmente.
7. No se puede inactivar una unidad de medida que usan productos activos.
8. Un pago o cobro nunca supera el saldo pendiente.
9. Una empresa no puede asignarse un rol de plataforma.
10. El número de usuarios activos no supera el `MaxUsers` del plan contratado.

La única excepción a la regla 5 es un asiento **manual** de caja: no tiene documento ni
saldo asociado que preservar, y su eliminación queda registrada en la bitácora con su
importe.

---

## 7. Lo que falta

```
Facturación electrónica   Hacienda CR: firma, envío y respuesta
Devoluciones              ReturnIn / ReturnOut ya existen como MovementType
Órdenes previas           CustomerOrder / PurchaseOrder antes del documento firme
Costeo promedio           hoy el costo del producto es el del último ingreso
Conciliación bancaria     sobre financeentries
```

`InventoryMovement.ReferenceType` / `ReferenceId` y los tipos de movimiento ya
contemplan devoluciones, así que entran sin rehacer nada de lo existente.
