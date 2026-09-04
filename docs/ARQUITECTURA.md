# Arquitectura de Gestora

## 1. Visión general

```
Navegador
   │  http://localhost:8080
   ▼
Frontend — Vue 3 + TypeScript + Vite
   │  HTTP/REST + JWT   (VITE_API_URL)
   ▼
Backend — ASP.NET Core 8 Web API
   │  Controllers (delgados)
   │      ▼
   │  Services   (reglas de negocio, transacciones)
   │      ▼
   │  EF Core    (DbContext, filtro multiempresa, auditoría)
   ▼
MySQL 8
```

Un solo despliegue de backend: **monolito modular**. No hay microservicios ni varias APIs.

## 2. Estructura

```
gestora/
├── backend/
│   ├── Gestora.sln
│   └── Gestora.API/
│       ├── Common/           # infraestructura transversal
│       │   ├── ModuleCatalog.cs    ← catálogo de módulos y permisos
│       │   ├── ApiResults.cs       ← ApiException, PagedResult, middleware de errores
│       │   └── CurrentUser.cs      ← usuario del JWT + [RequireModule]
│       ├── Data/
│       │   ├── GestoraDbContext.cs
│       │   └── DatabaseSeeder.cs
│       ├── Domain/           # entidades (una carpeta, sin DTOs mezclados)
│       ├── Modules/          # un módulo = controlador + servicio + DTOs juntos
│       │   ├── Auth/
│       │   ├── Users/
│       │   ├── Catalog/      # clientes, proveedores, productos, categorías, unidades
│       │   ├── Inventory/
│       │   ├── Dashboard/
│       │   └── Audit/
│       ├── Migrations/
│       └── Program.cs
│
├── frontend/
│   └── src/
│       ├── components/ui/    # DataTable, BaseModal, BaseField, BaseButton…
│       ├── composables/      # useFormat, useResourceList
│       ├── layouts/          # AppLayout (barra lateral + encabezado)
│       ├── router/
│       ├── services/         # http.ts (axios) + api.ts (un método por endpoint)
│       ├── stores/           # auth, notifications (Pinia)
│       ├── types/            # contratos de la API
│       └── views/            # una vista por pantalla
│
├── database/    # manual de instalación + script de esquema
├── docs/
└── scripts/     # start_project.bat
```

**Por qué módulos y no capas.** En ADIC, agregar un campo obligaba a abrir
`Controllers/`, `Services/` y `Models/`, tres carpetas distantes con decenas de
archivos. Aquí un cambio funcional vive en una sola carpeta. La separación
controlador / servicio / datos se mantiene *dentro* de cada módulo.

## 3. Flujo de una petición

```
POST /api/clientes
   ↓ ErrorHandlingMiddleware        (envuelve todo)
   ↓ CORS                           (lista blanca de AllowedOrigins)
   ↓ Authentication                 (valida el JWT)
   ↓ [RequireModule("customers", write: true)]
   ↓ Validación de DataAnnotations  → 400 { message, errors }
   ↓ CustomersController            (sin lógica: delega)
   ↓ CustomerService                (reglas, unicidad de código, auditoría)
   ↓ GestoraDbContext.SaveChanges   (rellena CompanyId y campos de auditoría)
   ↓ MySQL
```

## 4. Decisiones estructurales

### 4.1 Multiempresa desde el inicio

Las entidades de negocio implementan `ITenantEntity` y el `DbContext` aplica un
filtro global:

```csharp
b.Entity<Customer>().HasQueryFilter(e => e.CompanyId == TenantId);
```

`TenantId` sale del claim `company_id` del JWT. Aunque un servicio olvide filtrar,
la consulta no puede devolver datos de otra empresa. Al insertar, `SaveChanges`
asigna `CompanyId` automáticamente.

Lo que **no** se implementa todavía: planes, suscripciones y facturación del SaaS.

### 4.2 Permisos por módulo

`Common/ModuleCatalog.cs` lista los módulos del sistema. Un rol tiene filas
`RolePermission(ModuleKey, CanRead, CanWrite)`.

- **Backend:** `[RequireModule("inventory", write: true)]` sobre la acción.
- **Frontend:** `/api/auth/me` devuelve los módulos permitidos; el menú y las guardas
  de ruta se construyen con esa lista.

Los permisos se emiten dentro del JWT como claims `perm: "inventory:w"`, de modo que
autorizar no requiere consultar la base. Contrapartida asumida: un cambio de permisos
surte efecto cuando el token se renueva (una hora como máximo).

### 4.3 El inventario solo se mueve por asientos

`Product.Stock` es un saldo derivado. La única puerta de escritura es
`InventoryService.ApplyMovementAsync`, que dentro de una transacción:

1. Bloquea y lee el producto.
2. Calcula el nuevo saldo y rechaza existencias negativas (salvo ajuste autorizado).
3. Inserta el `InventoryMovement` con `StockAfter`, motivo, documento de origen y usuario.
4. Actualiza `Product.Stock`.
5. Registra la auditoría.

Si algo falla, no queda ni el movimiento ni el saldo cambiado. Cuando entren compras,
ventas y producción, cada línea llamará a este mismo método reutilizando la
transacción abierta por el documento.

> Detalle de implementación: con reintentos de EF activados, la transacción se abre
> *dentro* de `Database.CreateExecutionStrategy()`. Sin eso, EF rechaza las
> transacciones iniciadas a mano.

### 4.4 Manejo de errores

| Situación | Respuesta |
|---|---|
| Regla de negocio (`ApiException`) | Su código (400/403/404/409) y `{ message }` legible |
| Validación del modelo | 400 con `{ message, errors: { campo: [...] } }` |
| Excepción no controlada | 500 con mensaje genérico y `traceId`; el detalle va al log |

El frontend siempre lee `message` (`errorMessage()`) y opcionalmente `errors`
(`fieldErrors()`) para resaltar campos. Nunca ve un stack trace.

### 4.5 Auditoría transaccional

`IAuditService.Track(...)` **no** llama a `SaveChanges`: encola el registro en la
misma unidad de trabajo que la operación auditada. Si la operación se revierte, su
rastro también. No quedan bitácoras de cosas que nunca ocurrieron.

### 4.6 Sesión

- Access token JWT de 60 minutos, firmado con HMAC-SHA256.
- Refresh token opaco de 7 días en tabla propia, **con rotación**: cada uso revoca el anterior.
- Varias sesiones simultáneas por usuario, revocables por separado.
- Cambiar la contraseña o desactivar al usuario revoca todas sus sesiones.
- Cinco intentos fallidos bloquean la cuenta 15 minutos.
- Contraseñas con PBKDF2-HMAC-SHA256, 150 000 iteraciones, sal de 16 bytes por usuario.

## 5. Convenciones

**Backend**

- El controlador no contiene lógica: valida el modelo, llama al servicio y devuelve.
- Un servicio por agregado, registrado como `Scoped`.
- DTOs `record` de entrada y salida; las entidades no salen por la API.
- Interfaz solo cuando hay una razón concreta (`IAuditService`); si no, clase concreta.
- Rutas en español (`/api/clientes`), código en inglés. Las URL las lee el usuario.
- Las bajas son lógicas: `POST /{id}/inactivar`, nunca `DELETE`.

**Frontend**

- Ninguna URL de backend en el código: todo pasa por `VITE_API_URL`.
- Las vistas no usan `axios` directamente, solo `services/api.ts`.
- Estado compartido en Pinia; estado de pantalla en la propia vista.
- El color y el espaciado salen de las variables de `assets/styles/base.css`.
- Componentes pequeños; lo que se repite en tres pantallas se vuelve composable.

## 6. Puertos

| Servicio | Desarrollo |
|---|---|
| Frontend (Vite) | `http://localhost:8080` (puerto fijo) |
| Backend (Kestrel) | `http://localhost:5240` |
| Swagger | `http://localhost:5240/swagger` |
| MySQL | `localhost:3306` |

En producción el backend activa `UseHsts` y `UseHttpsRedirection`; en desarrollo se
trabaja por HTTP para no depender de certificados locales.

## 7. Qué falta y por qué

| Pendiente | Cuándo |
|---|---|
| Compras, ventas, producción, reparaciones | Etapas 4 a 7 del plan. Modelo y navegación ya previstos. |
| Cuentas por cobrar/pagar y pagos parciales | Junto con compras y ventas: no tienen sentido antes. |
| Reportes y exportación a Excel/PDF | Etapa 8. |
| Recuperación de contraseña por correo | Requiere decidir el proveedor de correo. |
| Pruebas automatizadas | Se agregan con los módulos financieros, donde una regresión cuesta dinero. |
| Facturación del SaaS | Solo cuando haya una segunda empresa real. |
