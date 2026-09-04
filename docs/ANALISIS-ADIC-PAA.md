# Análisis comparativo: ADIC, PAA y Gestora

Documento de la fase de análisis previa a escribir código. Registra qué se revisó de
cada proyecto existente, qué se reutiliza, qué se mejora y qué se descarta.

---

## 1. Qué es cada proyecto

| | ADIC | PAA |
|---|---|---|
| Dominio | Banco de ítems y ensamblaje de pruebas | Gestión operativa de un programa de admisión |
| Frontend | Vue 3 con Vue CLI (Webpack), JavaScript, Bootstrap 5 | PHP renderizado en servidor, una carpeta por módulo |
| Backend | ASP.NET Core 8 Web API, C# | PHP plano, un archivo `api/*.php` por módulo |
| Base de datos | MySQL 8 (Pomelo + EF Core 8) | MySQL, migraciones SQL numeradas a mano |
| Autenticación | JWT + refresh token, PBKDF2, MFA por correo | Sesión PHP |
| Extras | SignalR (presencia), QuestPDF, ClosedXML, Electron | Exportación a Excel, notificaciones, chat de soporte |

**ADIC es la referencia principal** (arquitectura y stack). **PAA es referencia
secundaria** (ideas de interfaz y de organización operativa); su stack PHP no se traslada.

---

## 2. Qué se reutiliza de ADIC

| Decisión de ADIC | Por qué se conserva |
|---|---|
| ASP.NET Core 8 Web API + EF Core + MySQL (Pomelo) | Stack probado por el equipo, LTS, ya instalado en la máquina de desarrollo. Cambiar de motor no aportaría nada al problema de Gestora. |
| Vue 3 + Pinia + Vue Router + axios | Mismo modelo mental de componentes y stores; la curva de aprendizaje ya está pagada. |
| Controlador delgado → servicio con la lógica | Evita que la regla de negocio quede atrapada en el controlador. Se mantiene tal cual. |
| PBKDF2-HMAC-SHA256 con sal por usuario y comparación en tiempo constante | Es la parte mejor resuelta de la seguridad de ADIC. Se adopta casi literalmente (subiendo las iteraciones de 100 000 a 150 000). |
| JWT + refresh token con rotación | Patrón correcto para una SPA. Se conserva la idea y se corrige dónde se guarda el token (ver §3). |
| Interceptor de axios que renueva el token y reintenta la petición | Resuelve bien el problema de la sesión que expira a media operación. |
| Bitácora de accesos y operaciones | Gestora maneja dinero e inventario: la auditoría pasa de accesorio a requisito. |
| Bloqueo por intentos fallidos de login | Se conserva, simplificado: contador en el usuario y bloqueo temporal. |
| Separación `frontend/` `backend/` `database/` `docs/` `scripts/` | Estructura clara; se mantiene. |
| `appsettings.example.json` versionado y `appsettings.Development.json` ignorado | Buena práctica ya establecida. |
| Arranque con dos terminales (backend y frontend) | Es como el equipo ya trabaja. Se replica en `scripts/start_project.bat`. |

## 3. Qué se mejora respecto a ADIC

| Problema observado en ADIC | Cómo lo resuelve Gestora |
|---|---|
| **Autorización por número de rol.** `AddPolicy("SoloAdmin", … new RolRequirement(1))` con los ids de rol repartidos por los controladores. Agregar un rol obliga a tocar muchos archivos. | Permisos **por módulo**, no por rol. `Common/ModuleCatalog.cs` es la única fuente de verdad; los controladores declaran `[RequireModule("customers", write: true)]` y los roles se configuran en datos, no en código. |
| **Menú del frontend separado de la autorización del backend.** Dos listas que podían discrepar (idea que PAA ya había resuelto mejor). | El backend devuelve en `/api/auth/me` los módulos permitidos y el frontend dibuja el menú *solo* con esa lista. Menú, guardas de ruta y autorización comparten origen. |
| **`Models/` mezcla entidades y DTOs** (≈60 archivos en una carpeta). | `Domain/` para entidades y un archivo de DTOs por módulo, dentro de `Modules/<Módulo>/`. |
| **Organización por capa técnica** (`Controllers/`, `Services/`, `Models/`): un cambio funcional toca tres carpetas lejanas. | **Monolito modular**: `Modules/Auth`, `Modules/Catalog`, `Modules/Inventory`… Cada módulo tiene junto su controlador, su servicio y sus DTOs. |
| **Refresh token como columna del usuario.** Solo admite una sesión; entrar desde otra máquina cierra la anterior. | Tabla `refreshtokens`: varias sesiones simultáneas, revocación individual y limpieza de tokens vencidos. |
| **Manejo de errores con un `UseExceptionHandler` inline**, sin distinguir error de negocio de fallo técnico. | `ErrorHandlingMiddleware` con `ApiException` para reglas de negocio (mensaje apto para el usuario) y 500 genérico con `traceId` para lo demás. Las validaciones de modelo usan el mismo contrato `{ message, errors }`. |
| **`GetCsrfToken` devuelve `HttpContext.GetHashCode()`**: no es un token CSRF. Además el antiforgery no aporta con JWT en cabecera. | Se elimina. Con token en cabecera `Authorization` (no en cookie) el vector CSRF no aplica; quedan las cabeceras de seguridad y un CORS con lista blanca explícita. |
| **CORS que acepta cualquier IP privada y `Origin: null`** en desarrollo. | Lista blanca desde configuración (`AllowedOrigins`), igual en todos los entornos. |
| **Detección de la URL del backend por `window.location` con puertos escritos en el código** (`https://${hostname}:7059/api`). | Una sola variable: `VITE_API_URL`. Ninguna URL de backend en el código. |
| **JavaScript sin tipos en el frontend.** | TypeScript en modo estricto, con los contratos de la API declarados en `src/types/`. |
| **Vue CLI (Webpack), en mantenimiento.** | Vite: arranque en menos de un segundo y recarga instantánea. |
| **Bootstrap + SCSS + Tailwind + `sweetalert2` + `vue-toastification` conviviendo.** | CSS propio con variables de diseño. Sin framework de UI: menos peso y una identidad visual propia. |
| **Componentes de 1 000+ líneas** que mezclan tabla, formulario y modal. | Componentes pequeños (`DataTable`, `BaseModal`, `BaseField`, `BaseButton`) y un composable `useResourceList` con la lógica repetida de los listados. |
| **`ItemsService_backup.js`** versionado. | Nada de archivos `_backup`; para eso está el control de versiones. |

## 4. Qué se toma de PAA

| Idea de PAA | Cómo se adapta |
|---|---|
| **`includes/modulos.php` como única fuente de verdad de los permisos**, usada a la vez para dibujar el portal y para cortar el acceso a cada página. Es la mejor decisión de arquitectura de los dos proyectos. | Es exactamente el modelo de `ModuleCatalog` + `RolePermission`. Se adopta como columna vertebral de la autorización. |
| Módulos agrupados por categoría en el menú | El catálogo lleva un campo `Group` y la barra lateral agrupa por él. |
| Migraciones SQL numeradas (`001_`, `002_`…), fáciles de leer y auditar | Se conserva el espíritu con migraciones de EF Core (ordenadas y versionadas) y un script SQL consolidado en `database/schema/`. |
| Bitácora con usuario, acción, módulo y fecha | Ampliada con entidad afectada, valor anterior y valor nuevo, imprescindible para inventario y dinero. |
| Exportación a Excel para el trabajo administrativo | Anotado para el módulo de Reportes; no forma parte de esta fase. |

## 5. Qué se descarta

| Elemento | Motivo |
|---|---|
| Todo el dominio de ADIC (ítems, temas, ensamblajes, idoneidad, dificultad) | Específico de su negocio. Solo se reutilizan patrones, nunca tablas ni pantallas. |
| Todo el dominio de PAA (sedes, tulas, carátulas, coordinadores) | Ídem. |
| SignalR y presencia en tiempo real | Gestora no lo necesita: una o dos personas usando el sistema a la vez. Se puede añadir después sin rehacer nada. |
| Electron / aplicación de escritorio | El requisito es web responsive. |
| MFA por correo, SendGrid y SMTP | Fuera del alcance de la fase 1. El modelo no impide agregarlo. |
| QuestPDF y ClosedXML | Entran cuando exista el módulo de Reportes, no antes. |
| `AutoMapper` | Con DTOs como `record` y un método `Map` estático, el mapeo es explícito y se lee mejor. Una dependencia menos. |
| Interfaz por cada servicio (`IBitacoraService`, `IAnalyticsService`…) | Solo se declara interfaz donde hay una razón real (`IAuditService`, usado desde todos los módulos). El resto son clases concretas. |
| PHP como tecnología | Decisión del stack; PAA aporta ideas, no código. |
| Redirección forzada a HTTPS y certificados en desarrollo | Complica el arranque local sin beneficio. En producción se activa (`UseHsts` + `UseHttpsRedirection` fuera de Development). |

## 6. Decisiones que conviene dejar por escrito

1. **MySQL, no SQL Server.** ADIC ya usa MySQL con Pomelo, la máquina lo tiene instalado y el equipo sabe operarlo. No hay ningún requisito de Gestora que justifique cambiar.
2. **Monolito modular, no microservicios.** Una pyme con un puñado de usuarios; los microservicios solo agregarían despliegue y latencia.
3. **`CompanyId` desde el primer día, con filtro global en el `DbContext`.** Agregarlo después obligaría a revisar cada consulta del sistema. El costo hoy es una columna y una línea por entidad; mañana sería una migración de datos con riesgo real.
4. **El inventario solo se mueve por asientos.** `Product.Stock` es un saldo derivado que únicamente escribe `InventoryService` dentro de una transacción. Es la regla que evita que el inventario deje de cuadrar.
5. **Nada financiero se borra.** Clientes, proveedores y productos se inactivan; los documentos futuros se anularán. El historial se conserva íntegro.
6. **`decimal` para dinero, nunca `float` ni `double`.** `decimal(18,2)` para importes y `decimal(18,4)` para cantidades, configurado de forma global en el `DbContext`.
7. **Los permisos viajan en el JWT.** Evita consultar la base en cada petición. El precio es que un cambio de permisos surte efecto al renovar el token (máximo una hora); aceptable para este caso y revisable si deja de serlo.
