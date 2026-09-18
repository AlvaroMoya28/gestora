# Gestora

Plataforma web de gestión empresarial para pymes que compran, almacenan, fabrican y
venden. Centraliza clientes, proveedores, productos, inventario, compras, ventas,
producción, reparaciones y cuentas por cobrar y pagar, de modo que una empresa pueda
pasar de registros manuales y dispersos a un flujo trazable y consultable.

Está construida como **monolito modular** y con `CompanyId` en todo el dominio desde
el primer día, para poder evolucionar a SaaS multiempresa sin rehacer el modelo.

---

## Los dos mundos de Gestora

Gestora son **dos productos** sobre la misma base: la **plataforma** (Gestora como
negocio: empresas, suscripciones y cobros) y la **empresa** (el sistema que usa el
cliente). Un usuario nunca ve los dos, salvo el desarrollador, que puede entrar a ver
cualquier empresa de forma explícita y auditada.

| Rol | Alcance | Qué hace |
|---|---|---|
| Desarrollador | Plataforma | Ve todo y puede entrar a ver cualquier empresa |
| Administración Gestora | Plataforma | Empresas, suscripciones y cobros. No ve datos de los clientes |
| Administrador | Empresa | Administra su empresa: registra, edita, da de baja |
| Consulta | Empresa | Solo lectura dentro de su empresa |

Detalle completo en [`docs/ARQUITECTURA-SAAS.md`](docs/ARQUITECTURA-SAAS.md).

## Estado actual

Todos los módulos del menú están implementados y operativos.

| Área | Qué hace |
|---|---|
| **Plataforma** | Empresas, planes, suscripciones y cobros · alta de cliente con credenciales · «ver como empresa» auditado |
| **Acceso** | JWT con refresh token y rotación · cuatro roles con alcance · permisos por módulo |
| **Comercial** | Clientes y proveedores con condiciones de crédito |
| **Catálogo** | Productos, categorías y unidades · inventario por movimientos con ajuste por conteo |
| **Operación** | Compras · ventas · producción con recetas · reparaciones |
| **Finanzas** | Cuentas por cobrar y por pagar con abonos · libro de ingresos y gastos |
| **Análisis** | Ocho reportes con exportación a CSV · panel con alertas |
| **Administración** | Usuarios · auditoría · configuración de la empresa |

Pendiente: facturación electrónica de Hacienda, devoluciones, auto-registro con
pasarela de pago y costeo promedio. Ver [`docs/MODELO-DATOS.md`](docs/MODELO-DATOS.md).

---

## Tecnologías

| Capa | Stack |
|---|---|
| Frontend | Vue 3, TypeScript, Vite, Pinia, Vue Router, axios |
| Backend | ASP.NET Core 8 Web API, C#, EF Core 8 |
| Base de datos | MySQL 8 (Pomelo) |
| Autenticación | JWT + refresh token; contraseñas con PBKDF2-HMAC-SHA256 |
| Documentación de la API | Swagger / OpenAPI |

Sin framework de CSS: el sistema visual son variables propias en
`frontend/src/assets/styles/base.css`.

---

## Requisitos

- [.NET SDK 8.0](https://dotnet.microsoft.com/download/dotnet/8.0)
- [Node.js 20 o superior](https://nodejs.org)
- [MySQL 8](https://dev.mysql.com/downloads/installer/)

---

## Instalación

### 1. Base de datos

Cree el esquema vacío (las tablas las genera el backend):

```sql
CREATE DATABASE IF NOT EXISTS gestoradb
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

El procedimiento completo, con MySQL Workbench, usuario de aplicación, respaldos y
solución de problemas, está en **[`database/README.md`](database/README.md)**.

### 2. Backend

```powershell
Copy-Item backend\Gestora.API\appsettings.example.json backend\Gestora.API\appsettings.Development.json
```

Edite ese archivo con su cadena de conexión y una clave JWT de al menos 32 caracteres:

```json
{
  "ConnectionStrings": {
    "DefaultConnection": "server=localhost;port=3306;database=gestoradb;user=SU_USUARIO;password=SU_CONTRASENA"
  },
  "Jwt": { "Key": "una-clave-aleatoria-larga-de-al-menos-32-caracteres" },
  "Seed": {
    "DeveloperEmail": "dev@gestora.local",
    "DeveloperPassword": "Gestora2026!",
    "CompanyName": "Empresa Demo",
    "CompanyEmail": "empresa@gestora.local",
    "CompanyPassword": "Empresa2026!",
    "Currency": "CRC"
  }
}
```

```powershell
cd backend\Gestora.API
dotnet restore
dotnet run
```

En el primer arranque se aplican las migraciones y se crean la empresa, los roles, el
usuario administrador, las unidades de medida y las categorías iniciales.

### 3. Frontend

```powershell
cd frontend
Copy-Item .env.example .env.local     # opcional: .env.development ya trae los valores locales
npm install
npm run dev
```

---

## Ejecución

Gestora se levanta con **dos terminales**, una por proyecto:

| Terminal | Comando | Resultado |
|---|---|---|
| Backend | `cd backend\Gestora.API` → `dotnet run` | http://localhost:5240 |
| Frontend | `cd frontend` → `npm run dev` | http://localhost:8080 |

O ambas de una vez:

```powershell
.\scripts\start_project.bat
```

Abra **http://localhost:8080**. El primer arranque crea dos cuentas, definidas en la
sección `Seed` de su `appsettings.Development.json`:

| Cuenta | Correo por defecto | Entra a |
|---|---|---|
| Desarrollador | `dev@gestora.local` | La plataforma: empresas, suscripciones, planes |
| Empresa de ejemplo | `empresa@gestora.local` | El sistema como lo ve un cliente |

Desde la cuenta de desarrollador, el botón **«Ver como»** en Empresas cambia la sesión
a la vista de ese cliente; una banda ámbar avisa mientras dure y permite volver.

**Cambie ambas contraseñas desde «Mi cuenta» al primer ingreso.**

| Recurso | URL |
|---|---|
| Aplicación | http://localhost:8080 |
| API | http://localhost:5240/api |
| Swagger | http://localhost:5240/swagger |
| Salud del backend | http://localhost:5240/api/health |

---

## Variables de entorno

**Frontend** (`frontend/.env.local`, plantilla en `.env.example`)

| Variable | Descripción |
|---|---|
| `VITE_API_URL` | URL base de la API. Es la **única** definición de dónde vive el backend. |
| `VITE_APP_NAME` | Nombre mostrado en la aplicación. |

**Backend** (`appsettings.Development.json`, plantilla en `appsettings.example.json`)

| Clave | Descripción |
|---|---|
| `ConnectionStrings:DefaultConnection` | Cadena de conexión a MySQL. |
| `Jwt:Key` | Clave de firma. Mínimo 32 caracteres, aleatoria. |
| `Jwt:AccessTokenMinutes` / `RefreshTokenDays` | Vigencia de los tokens. |
| `AllowedOrigins` | Orígenes permitidos por CORS. |
| `Seed:*` | Empresa, moneda y administrador del primer arranque. |

Estas claves también pueden pasarse por entorno:
`ConnectionStrings__DefaultConnection`, `Jwt__Key`.

**En el repositorio no se guardan contraseñas, cadenas de conexión reales ni claves JWT.**
`appsettings.Development.json` y `.env.local` están en `.gitignore`.

---

## Estructura del proyecto

```
gestora/
├── backend/            ASP.NET Core Web API
│   └── Gestora.API/
│       ├── Common/     catálogo de módulos, errores, usuario actual
│       ├── Data/       DbContext y datos iniciales
│       ├── Domain/     entidades
│       ├── Modules/    Auth · Users · Platform · Catalog · Inventory · Purchasing
│       │               Sales · Production · Repairs · Finance · Reports
│       │               Settings · Dashboard · Audit
│       └── Migrations/
├── frontend/           Vue 3 + TypeScript
│   └── src/
│       ├── components/ui/  componentes reutilizables
│       ├── composables/    lógica compartida de vistas
│       ├── layouts/        estructura de la aplicación
│       ├── router/         rutas y guardas por permiso
│       ├── services/       cliente HTTP y llamadas a la API
│       ├── stores/         sesión y avisos (Pinia)
│       ├── types/          contratos de la API
│       └── views/          pantallas
├── database/           manual de instalación y script de esquema
├── docs/               análisis, arquitectura y modelo de datos
└── scripts/            arranque de desarrollo
```

---

## Documentación

| Documento | Contenido |
|---|---|
| [`database/README.md`](database/README.md) | Manual de MySQL: crear, conectar, migrar y respaldar. |
| [`docs/ANALISIS-ADIC-PAA.md`](docs/ANALISIS-ADIC-PAA.md) | Qué se reutiliza, mejora y descarta de ADIC y PAA, y por qué. |
| [`docs/ARQUITECTURA.md`](docs/ARQUITECTURA.md) | Estructura, flujo de una petición y decisiones estructurales. |
| [`docs/ARQUITECTURA-SAAS.md`](docs/ARQUITECTURA-SAAS.md) | Los dos mundos, los cuatro roles y el «ver como empresa». |
| [`docs/MODELO-DATOS.md`](docs/MODELO-DATOS.md) | Entidades, reglas del modelo y lo que viene después. |

---

## Convenciones que sostienen el sistema

1. **El inventario nunca se edita, se mueve.** `Product.Stock` es un saldo derivado que solo escribe `InventoryService` dentro de una transacción.
2. **Cada documento tiene un punto sin retorno.** Confirmar una venta, terminar una orden de producción o entregar una reparación mueve el inventario y crea la deuda en una sola transacción: nunca queda media operación.
3. **El dinero se registra una vez.** Los cobros y pagos dejan su asiento en ingresos y gastos automáticamente; esos asientos no se editan desde finanzas, porque su verdad vive en la cuenta que los originó.
4. **Nada financiero se borra.** Clientes, proveedores y productos se inactivan; el historial se conserva.
5. **`decimal` para dinero**, nunca `float` ni `double`.
6. **Un único catálogo de permisos** (`Common/ModuleCatalog.cs`) alimenta el menú del frontend y la autorización del backend.
7. **`CompanyId` en todo el dominio**, con filtro global: ninguna consulta puede ver datos de otra empresa.
8. **Ninguna URL del backend escrita en el código** del frontend: todo pasa por `VITE_API_URL`.

---

## Comandos útiles

```powershell
# Backend
cd backend\Gestora.API
dotnet run                                  # arrancar
dotnet build                                # compilar
dotnet ef migrations add NombreDelCambio    # nueva migración
dotnet ef database update                   # aplicar migraciones
dotnet ef database drop --force             # empezar de cero

# Frontend
cd frontend
npm run dev        # servidor de desarrollo en :8080
npm run build      # compilar para producción
npm run typecheck  # verificar tipos
```
