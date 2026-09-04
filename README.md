# Gestora

Plataforma web de gestión empresarial para pymes que compran, almacenan, fabrican y
venden. Centraliza clientes, proveedores, productos, inventario, compras, ventas,
producción, reparaciones y cuentas por cobrar y pagar, de modo que una empresa pueda
pasar de registros manuales y dispersos a un flujo trazable y consultable.

Está construida como **monolito modular** y con `CompanyId` en todo el dominio desde
el primer día, para poder evolucionar a SaaS multiempresa sin rehacer el modelo.

---

## Estado actual — fase 1

| Implementado | Diseñado, pendiente |
|---|---|
| Autenticación JWT con refresh token y rotación | Compras y cuentas por pagar |
| Usuarios, roles y permisos por módulo | Ventas y cuentas por cobrar |
| Layout, navegación por permisos y panel | Producción y recetas (BOM) |
| Clientes y proveedores | Reparaciones |
| Productos, categorías y unidades | Ingresos, gastos y pagos |
| Inventario con movimientos y ajustes | Reportes y exportación |
| Auditoría de operaciones | Configuración de la empresa |

Los módulos pendientes ya aparecen en el menú con su marcador correspondiente: su
modelo de datos y sus permisos están definidos.

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
    "CompanyName": "Mi Empresa",
    "Currency": "CRC",
    "AdminEmail": "admin@gestora.local",
    "AdminPassword": "Gestora2026!"
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

Abra **http://localhost:8080** e ingrese con las credenciales de `Seed` de su
`appsettings.Development.json`. **Cambie la contraseña desde «Mi cuenta» al primer ingreso.**

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
│       ├── Modules/    Auth · Users · Catalog · Inventory · Dashboard · Audit
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
| [`docs/MODELO-DATOS.md`](docs/MODELO-DATOS.md) | Entidades, reglas del modelo y lo que viene después. |

---

## Convenciones que sostienen el sistema

1. **El inventario nunca se edita, se mueve.** `Product.Stock` es un saldo derivado que solo escribe `InventoryService` dentro de una transacción.
2. **Nada financiero se borra.** Clientes, proveedores y productos se inactivan; el historial se conserva.
3. **`decimal` para dinero**, nunca `float` ni `double`.
4. **Un único catálogo de permisos** (`Common/ModuleCatalog.cs`) alimenta el menú del frontend y la autorización del backend.
5. **`CompanyId` en todo el dominio**, con filtro global: ninguna consulta puede ver datos de otra empresa.
6. **Ninguna URL del backend escrita en el código** del frontend: todo pasa por `VITE_API_URL`.

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
