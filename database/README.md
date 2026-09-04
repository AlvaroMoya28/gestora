# Base de datos de Gestora — manual de instalación local

Gestora usa **MySQL 8**. Este documento explica cómo dejar la base lista en una
máquina de desarrollo y cómo conectarla con el backend.

> **Resumen rápido:** cree el esquema vacío `gestoradb`, ponga la cadena de conexión
> en `backend/Gestora.API/appsettings.Development.json` y arranque el backend. Las
> tablas y los datos iniciales se crean solos en el primer arranque.

---

## 1. Requisitos

| Componente | Versión | Cómo verificar |
|---|---|---|
| MySQL Server | 8.0 o superior | `mysql --version` o el servicio `MySQL80` en Windows |
| MySQL Workbench | opcional, recomendado | interfaz gráfica para administrar la base |
| .NET SDK | 8.0 | `dotnet --version` |

Si todavía no tiene MySQL, instale **MySQL Community Server 8.0** desde
<https://dev.mysql.com/downloads/installer/>. Durante la instalación:

1. Elija el tipo **Developer Default** (incluye Server + Workbench).
2. Deje el puerto en **3306**.
3. Escoja **Use Strong Password Encryption**.
4. Defina la contraseña de `root` y **anótela**: la necesitará en el paso 3.

Al terminar, en Windows verifique que el servicio esté corriendo:

```powershell
Get-Service MySQL80
```

Debe aparecer `Status: Running`. Si no, inícielo:

```powershell
Start-Service MySQL80
```

---

## 2. Crear el esquema

Solo hay que crear el esquema **vacío**. Las tablas las genera el backend.

### Opción A — MySQL Workbench (interfaz gráfica)

1. Abra MySQL Workbench.
2. Haga clic en la conexión **Local instance MySQL80** e ingrese la contraseña de `root`.
3. Abra una pestaña de consulta (el icono de hoja con un `+`, o `Ctrl+T`).
4. Pegue y ejecute (rayo amarillo o `Ctrl+Enter`):

   ```sql
   CREATE DATABASE IF NOT EXISTS gestoradb
     CHARACTER SET utf8mb4
     COLLATE utf8mb4_unicode_ci;
   ```

5. En el panel izquierdo, clic derecho sobre **Schemas → Refresh All**. Debe aparecer `gestoradb`.

> El juego de caracteres `utf8mb4` es importante: permite guardar tildes, ñ y símbolos
> de moneda sin corromper el texto.

### Opción B — línea de comandos

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS gestoradb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Opción C — script SQL completo (sin ejecutar el backend)

Si necesita las tablas sin arrancar la API (por ejemplo, para revisar el modelo o
preparar un servidor), ejecute el script generado a partir de las migraciones:

```bash
mysql -u root -p gestoradb < database/schema/gestora_schema.sql
```

O desde Workbench: **File → Open SQL Script…**, seleccione
`database/schema/gestora_schema.sql`, elija `gestoradb` como esquema activo
(doble clic sobre su nombre) y ejecute.

El script es *idempotente*: se puede volver a ejecutar sin duplicar objetos.
Aun así **no crea los datos iniciales** (empresa, roles, usuario administrador,
unidades y categorías); eso lo hace el backend al arrancar.

---

## 3. Crear un usuario de aplicación (recomendado)

Trabajar con `root` es cómodo pero no es buena práctica. Cree un usuario con permisos
solo sobre `gestoradb`:

```sql
CREATE USER 'gestora_app'@'localhost' IDENTIFIED BY 'ponga_una_contrasena_fuerte';
GRANT ALL PRIVILEGES ON gestoradb.* TO 'gestora_app'@'localhost';
FLUSH PRIVILEGES;
```

`ALL PRIVILEGES` sobre ese esquema es necesario porque el backend aplica migraciones
(crea y altera tablas). En un servidor de producción conviene separar el usuario que
migra del usuario que solo lee y escribe filas.

---

## 4. Conectar el backend

1. Copie la plantilla de configuración:

   ```powershell
   Copy-Item backend\Gestora.API\appsettings.example.json backend\Gestora.API\appsettings.Development.json
   ```

2. Edite `appsettings.Development.json` y complete la cadena de conexión:

   ```json
   {
     "ConnectionStrings": {
       "DefaultConnection": "server=localhost;port=3306;database=gestoradb;user=gestora_app;password=SU_CONTRASENA"
     },
     "Jwt": {
       "Key": "una-clave-aleatoria-de-al-menos-32-caracteres"
     },
     "Seed": {
       "CompanyName": "Mi Empresa",
       "Currency": "CRC",
       "AdminEmail": "admin@gestora.local",
       "AdminPassword": "Gestora2026!"
     }
   }
   ```

   Partes de la cadena de conexión:

   | Clave | Significado |
   |---|---|
   | `server` | host de MySQL (`localhost` en desarrollo) |
   | `port` | puerto, `3306` por defecto |
   | `database` | esquema creado en el paso 2 |
   | `user` / `password` | credenciales del paso 3 |

3. Arranque el backend:

   ```powershell
   cd backend\Gestora.API
   dotnet run
   ```

En el primer arranque verá en la consola algo como:

```
info: Seed[0] Empresa inicial creada: Mi Empresa
warn: Seed[0] Usuario administrador creado: admin@gestora.local. Cambie la contraseña al primer ingreso.
info: Seed[0] Unidades de medida iniciales creadas.
info: Seed[0] Categorías iniciales creadas.
```

Eso significa que las migraciones se aplicaron y los datos base quedaron creados.

**`appsettings.Development.json` no se versiona** (está en `.gitignore`): contiene
credenciales reales. Lo que sí se versiona es `appsettings.example.json`.

---

## 5. Verificar que quedó bien

Con el backend corriendo:

```powershell
Invoke-RestMethod http://localhost:5240/api/health
```

Debe responder `status: ok`.

Y en Workbench, sobre `gestoradb`:

```sql
SHOW TABLES;
SELECT Id, Name, Currency FROM companies;
SELECT Email, RoleId, IsActive FROM users;
SELECT Name, Abbreviation FROM units;
```

Debe ver 11 tablas más `__efmigrationshistory`, una empresa, el usuario administrador
y las ocho unidades de medida iniciales.

---

## 6. Tablas del modelo

| Tabla | Para qué sirve |
|---|---|
| `companies` | Empresa inquilina. Toda la información cuelga de aquí. |
| `roles`, `rolepermissions` | Roles y qué módulo puede leer o editar cada uno. |
| `users`, `refreshtokens` | Cuentas de acceso y sesiones abiertas. |
| `categories`, `units` | Catálogos auxiliares de productos. |
| `customers`, `suppliers` | Clientes y proveedores. |
| `products` | Productos terminados, materias primas y servicios. |
| `inventorymovements` | Historial de existencias: cada entrada, salida y ajuste. |
| `auditlogs` | Bitácora de operaciones sensibles. |

Detalle del modelo y decisiones de diseño: [`docs/MODELO-DATOS.md`](../docs/MODELO-DATOS.md).

---

## 7. Cambiar el modelo (migraciones)

El esquema **no se modifica a mano en Workbench**. Se cambia la entidad en C# y se
genera una migración; así todos los entornos aplican el mismo cambio en el mismo orden.

```powershell
cd backend\Gestora.API

# 1. Crear la migración después de editar las entidades
dotnet ef migrations add DescripcionDelCambio

# 2. Aplicarla (también ocurre solo al arrancar el backend)
dotnet ef database update

# 3. Regenerar el script versionado
dotnet ef migrations script --idempotent --output ..\..\database\schema\gestora_schema.sql
```

Si `dotnet ef` no está instalado:

```powershell
dotnet tool install --global dotnet-ef
```

---

## 8. Respaldos

Antes de cualquier cambio importante, y de forma periódica en producción:

```bash
# Respaldar
mysqldump -u root -p --databases gestoradb --single-transaction --routines > gestoradb_backup.sql

# Restaurar
mysql -u root -p < gestoradb_backup.sql
```

`--single-transaction` evita bloquear las tablas durante el respaldo.
Guarde copias diarias, semanales y mensuales fuera del servidor, y **pruebe restaurarlas**:
un respaldo que nunca se restauró no es un respaldo.

---

## 9. Problemas frecuentes

| Síntoma | Causa probable | Solución |
|---|---|---|
| `Unable to connect to any of the specified MySQL hosts` | El servicio no está corriendo | `Start-Service MySQL80` |
| `Access denied for user ...` | Usuario o contraseña incorrectos | Revise la cadena de conexión del paso 4 |
| `Unknown database 'gestoradb'` | Falta crear el esquema | Repita el paso 2 |
| `Falta ConnectionStrings:DefaultConnection` al arrancar | No existe `appsettings.Development.json` | Cópielo de `appsettings.example.json` |
| `Jwt:Key debe existir y tener al menos 32 caracteres` | Clave JWT vacía o corta | Ponga una clave larga en el paso 4 |
| Tildes que se ven como `Ã¡` | Esquema creado sin `utf8mb4` | Recree la base con el `CHARACTER SET` del paso 2 |
| Quiere empezar de cero | — | `dotnet ef database drop --force` y vuelva a arrancar el backend |
