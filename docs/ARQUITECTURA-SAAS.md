# Arquitectura SaaS: los tres modos de Gestora

Gestora no es un sistema con usuarios de distintos niveles: son **dos productos
distintos** que comparten base de datos y despliegue.

| | **Plataforma** | **Empresa** |
|---|---|---|
| Qué es | Gestora como negocio | El sistema que usa el cliente |
| Quién entra | Desarrollador y administración de Gestora | Los usuarios de una empresa suscrita |
| Qué ve | Empresas, suscripciones, planes, cobros | Clientes, inventario, compras, finanzas |
| Datos | Ficha comercial de cada cuenta | Operación diaria de **una** empresa |

Un usuario **nunca** ve los dos mundos a la vez. La única excepción es el
desarrollador, que puede cambiar de uno al otro de forma explícita y auditada.

---

## 1. Los cuatro roles

El catálogo es fijo (`Common/RoleKeys.cs`). No se crean roles por empresa.

| Rol | Alcance | Para qué |
|---|---|---|
| `developer` | Plataforma | Yo. Ve todo y puede entrar a ver cualquier empresa. |
| `platform_admin` | Plataforma | Administra empresas, suscripciones y cobros. **No** entra a los datos de ninguna empresa. |
| `company_admin` | Empresa | Administrador del cliente: registra, edita y da de baja. |
| `company_viewer` | Empresa | Consulta del cliente: solo lectura. |

Cada empresa recibe **dos accesos**: uno `company_admin` (el correo de la cuenta que
se entrega al suscribirse) y, si lo pide, uno `company_viewer` con otro correo. El
límite real de cuentas lo pone el plan contratado (`Plan.MaxUsers`).

**Por qué `platform_admin` no puede ver datos de empresas.** Administrar cobros no
requiere ver el inventario ni las ventas de un cliente. Separar esas dos cosas
significa que mañana se puede contratar a alguien para facturación sin darle acceso
a la información de los clientes. El desarrollador sí puede, porque para dar soporte
hace falta, pero queda registrado.

---

## 2. Cómo se separan los mundos

Todo se apoya en una sola idea: **los módulos tienen alcance**.

```csharp
// Common/ModuleCatalog.cs
new("platform_companies", "Empresas", "Gestora", "building", true, RoleScope.Platform),
new("customers",          "Clientes", "Comercial", "users",   true, RoleScope.Company),
```

De ahí se derivan las tres cosas que importan, sin listas paralelas:

1. **El menú**: el backend devuelve en `/api/auth/me` solo los módulos del alcance del
   usuario; el frontend dibuja lo que recibe.
2. **La autorización**: `[RequireModule("platform_companies")]` en el controlador. Un
   usuario de empresa no tiene ese permiso en su token, así que recibe 403.
3. **Las rutas**: la guarda del router compara `meta.module` con los permisos de la
   sesión. No hace falta una guarda especial "solo plataforma".

Un usuario de empresa que escriba a mano `/gestora/empresas` es devuelto a su panel,
y si llama a la API directamente recibe 403.

---

## 3. Aislamiento de datos

`GestoraDbContext.TenantId` sale del claim `company_id` del token.

```csharp
b.Entity<Customer>().HasQueryFilter(e => e.CompanyId == TenantId);
```

- Usuario de empresa → `TenantId` = su empresa → ve lo suyo.
- Usuario de plataforma → `TenantId` = 0 → **no ve datos de ninguna empresa**, ni
  aunque una consulta olvide el `Where`.
- Desarrollador viendo una empresa → `TenantId` = esa empresa → ve exactamente lo mismo
  que el cliente.

`User.CompanyId` es **nullable**: los usuarios de plataforma no pertenecen a ninguna
empresa. Su filtro lo contempla:

```csharp
b.Entity<User>().HasQueryFilter(e =>
    e.CompanyId == TenantId || (TenantId == 0 && e.CompanyId == null));
```

Así, en «Usuarios» una empresa ve solo sus cuentas, y nunca las de Gestora.

---

## 4. "Ver como empresa" (modo dev)

Inspirado en `dev_simular.php` de PAA, pero resuelto con tokens en vez de sesión.

```
POST /api/auth/ver-como/{companyId}     → token nuevo con company_id = esa empresa
POST /api/auth/volver-a-plataforma      → token de vuelta al alcance de plataforma
```

Lo que ocurre al entrar a una empresa:

1. Se emite un **token nuevo** con `company_id` de esa empresa y el claim `impersonating`.
2. Los permisos del token se **recortan a los módulos de empresa**: mientras ve al
   cliente, el desarrollador no tiene el menú de plataforma. La vista es fiel.
3. Se registra en la **bitácora de esa empresa**: si alguien de Gestora entró a mirar
   sus datos, la empresa puede verlo.
4. El frontend muestra una **banda ámbar permanente** con el nombre de la empresa y el
   botón para volver. Es imposible olvidar en qué cuenta se está trabajando.

La empresa suplantada se guarda en la fila del refresh token, así que renovar la sesión
no saca al desarrollador de donde estaba trabajando.

> **Decisión consciente:** al ver como empresa, el desarrollador tiene permisos de
> escritura reales. Es lo que permite dar soporte de verdad ("no me deja registrar la
> compra"), pero implica que puede modificar datos del cliente. Por eso el acceso queda
> en la bitácora de la empresa y el aviso visual es imposible de ignorar.

---

## 5. Ciclo de vida de un cliente

```
Se suscribe
    ↓
POST /api/plataforma/empresas        ← alta completa, en una transacción
    ├── Company        (ficha + correo de la cuenta)
    ├── Subscription   (plan, precio pactado, vencimiento)
    ├── User           (company_admin con el correo de la cuenta)
    └── Catálogo base  (unidades y categorías, lista para operar)
    ↓
Se le entregan las credenciales  ← la contraseña temporal se muestra UNA vez
    ↓
Entra y opera
    ↓
Se le cobra     → POST /suscripciones/{id}/cobros   (corre el vencimiento)
Deja de pagar   → POST /empresas/{id}/suspender     (cierra sus sesiones)
Vuelve a pagar  → el cobro la reactiva automáticamente
```

**Cómo corre el vencimiento.** Un cobro suma `BillingPeriodMonths × períodos` a la
fecha de vencimiento actual, no a la fecha de hoy: si el cliente paga con atraso no
pierde días, y si paga adelantado se le acumulan. La excepción es cuando la
suscripción venció hace tiempo: ahí se renueva desde hoy, porque no tiene sentido
cobrar meses en los que no usó el sistema.

**Vencer no bloquea el acceso.** Una suscripción vencida se marca como tal y aparece
en el panel de cobros, pero la empresa sigue entrando. Cortarle el servicio es una
decisión comercial que se toma con el botón de suspender, no un automatismo que
sorprenda a un cliente que paga tarde una vez.

---

## 6. Lo que la plataforma NO ve

Deliberadamente, `/api/plataforma/*` no expone nada de la operación de una empresa:
ni clientes, ni productos, ni ventas, ni saldos. La ficha de empresa muestra nombre,
contacto, estado, plan, vencimiento y **cuántos usuarios tiene** — nada más.

Para ver datos internos hay exactamente un camino: entrar como la empresa, siendo
desarrollador, y quedar registrado en su bitácora.

---

## 7. Estado y lo que falta

**Implementado**

- Cuatro roles con alcance, permisos por módulo recalculados en cada arranque
- Alta de empresa con suscripción y credenciales generadas
- Suspender / reactivar / dar de baja, con cierre de sesiones
- Planes con precio, período y límite de usuarios (aplicado al crear cuentas)
- Cobros con historial y corrimiento de vencimiento
- Cambio de plan conservando el precio pactado
- Resumen de plataforma: empresas, ingreso mensual recurrente, próximos vencimientos
- "Ver como empresa" con banda de aviso y auditoría

**Pendiente**

| Qué | Por qué todavía no |
|---|---|
| Auto-registro del cliente | Hoy Gestora crea la cuenta y entrega credenciales, que es como opera un SaaS con venta asistida. |
| Pasarela de pago | Los cobros se registran a mano. Automatizarlos exige decidir proveedor. |
| Correo de bienvenida | Requiere definir el proveedor de correo. |
| Facturación electrónica del SaaS | Depende del mismo trámite que la del cliente. |
| Métricas históricas | Hoy el resumen es una foto del momento, no una serie. |
