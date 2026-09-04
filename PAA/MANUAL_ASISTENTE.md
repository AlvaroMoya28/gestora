# Manual del módulo de Tickets

Bienvenido. Este documento es para vos: la persona que va a desarrollar el
módulo de **Creación de Tickets** del sistema PAA.

Está escrito asumiendo que **nunca trabajaste con este proyecto**. Se puede
seguir de principio a fin sin saber nada de antemano. Si algo no calza con lo
que ves en pantalla, pará y preguntá antes de seguir: es preferible a adivinar.

---

## Tus datos de trabajo

| | |
| --- | --- |
| **Repositorio** | <https://git.ucr.ac.cr/paa/paa> |
| **Tu rama** | `feature/tickets-detalle` — **ya está creada, no la crees vos** |
| **Rama de la que sale y a la que vuelve** | `main` |
| **Tu módulo** | `PAA/CreacionTickets/` + `PAA/api/tickets.php` |
| **Tu primera tarea** | Ver el detalle de un ticket con su historial de comentarios |

### Los primeros pasos, en orden

Cada uno está explicado en detalle más abajo. Calculá una mañana para llegar
hasta el 5.

1. Instalar Git, PHP y una base de datos → [sección 4](#4-preparar-tu-computadora)
2. Bajar el proyecto y pararte en tu rama → [sección 5](#5-bajar-el-proyecto)
3. Levantar el sitio en tu máquina → [sección 6](#6-levantar-el-sitio-en-tu-máquina)
4. Abrir <http://localhost:8000/CreacionTickets/> y **reportar una avería de
   prueba**. Si eso funciona, el ambiente quedó listo.
5. Leer los tres archivos de tu módulo sin cambiar nada todavía →
   [sección 7](#7-cómo-está-hecho-el-módulo)
6. Recién ahí, empezar a programar → [sección 8](#8-tu-día-a-día-de-trabajo)

> **Antes de escribir tu primera línea de código**, leé la
> [sección 3](#3-reglas-que-no-se-rompen). Son cinco reglas y las cinco son de
> seguridad: el sistema maneja datos personales de funcionarios de la UCR.

---

## Índice

1. [Qué es este proyecto](#1-qué-es-este-proyecto)
2. [Qué te toca hacer a vos](#2-qué-te-toca-hacer-a-vos)
3. [Reglas que no se rompen](#3-reglas-que-no-se-rompen)
4. [Preparar tu computadora](#4-preparar-tu-computadora)
5. [Bajar el proyecto](#5-bajar-el-proyecto)
6. [Levantar el sitio en tu máquina](#6-levantar-el-sitio-en-tu-máquina)
7. [Cómo está hecho el módulo](#7-cómo-está-hecho-el-módulo)
8. [Tu día a día de trabajo](#8-tu-día-a-día-de-trabajo)
9. [Entregar tu trabajo](#9-entregar-tu-trabajo)
10. [Cuando algo se rompe](#10-cuando-algo-se-rompe)
11. [Glosario](#11-glosario)

---

## 1. Qué es este proyecto

El **PAA** es el Programa Permanente de la Prueba de Aptitud Académica de la
Universidad de Costa Rica. Cada año aplica la prueba de admisión en cientos de
sedes por todo el país.

Para organizar esa operación hay un sistema de herramientas internas: control de
sedes, de coordinadores, del material que va a cada aula, etc. Ese sistema
**está siendo migrado**: antes vivía en Google Sheets con Apps Script, y ahora
se está pasando a un sitio propio en PHP con base de datos MariaDB.

Lo que tenés que saber:

* **PHP** genera las páginas y responde a las peticiones (el "backend").
* **MariaDB** guarda los datos. Es igual que MySQL.
* **JavaScript** en el navegador dibuja la pantalla y llama al backend.
* No hay frameworks, ni React, ni Composer, ni `npm`. Es PHP y JavaScript
  a secas, a propósito: el sistema tiene que poder mantenerlo alguien que
  entre dentro de tres años.

---

## 2. Qué te toca hacer a vos

Desarrollar el módulo **Creación de Tickets**: la herramienta donde se reportan
las **averías** que ocurren en las sedes el día de la prueba (un aula sin luz,
un proyector dañado, falta de material) y se les da seguimiento hasta que se
resuelven.

Ya está hecha la primera versión, que funciona. **Tu trabajo es mejorarla y
completarla**, no empezar de cero.

### Los únicos tres archivos que vas a tocar

```text
paa/                                  ← el repositorio
├── PAA/                              ← el sistema nuevo (acá trabajás)
│   ├── CreacionTickets/index.php     ← 👈 la pantalla (HTML + CSS + JavaScript)
│   ├── api/tickets.php               ← 👈 el backend del módulo (PHP)
│   ├── schema/010_tickets.sql        ← 👈 las tablas de la base de datos
│   ├── includes/                     ← compartido: NO tocar
│   ├── scripts/                      ← compartido: NO tocar
│   └── <otros módulos>/              ← de otras personas: NO tocar
└── sistema_anterior/                 ← el sistema viejo, solo de referencia
```

Si necesitás cambiar algo **fuera** de esos tres archivos, no lo hagas por tu
cuenta: escribile a la persona que coordina el proyecto y explicá qué
necesitás. No es burocracia — es que esos archivos los comparten otros nueve
módulos y un cambio ahí puede romper cosas que no ves.

> **Sobre `sistema_anterior/`:** son las páginas viejas, escritas en Google Apps
> Script, que se están reemplazando una por una. Están ahí como referencia. No
> las edites: no forman parte del sitio.

### Qué falta por hacer

Lo que ya funciona:

* Reportar una avería (formulario completo).
* Ver la lista, con filtros por estado, prioridad y texto.
* Contadores por estado.

Lo que falta, más o menos en orden de importancia:

1. **Ver el detalle de un ticket** al hacerle clic, con su historial de
   comentarios. El backend ya lo soporta: `accion=ver`.
2. **Agregar comentarios** y **cambiar el estado** desde la pantalla. El backend
   ya lo soporta: `accion=comentar` y `accion=cambiarEstado`.
3. **Asignar el ticket a una persona** del catálogo. Falta hacerlo en el
   backend y en la pantalla.
4. Que al elegir la sede, el campo de aula **ofrezca las aulas que existen** en
   esa sede, en vez de escribirlas a mano.
5. Que se pueda **adjuntar una foto** de la avería. Esto hay que conversarlo
   antes: implica decidir dónde se guardan los archivos.

---

## 3. Reglas que no se rompen

Estas cinco no son estilo, son seguridad. El sistema maneja datos personales de
funcionarios de la UCR y va a estar publicado en internet.

**1. Nunca metas datos del usuario directo en una consulta SQL.**

```php
// MAL — permite que alguien borre la base escribiendo en un formulario
$sql = "SELECT * FROM tickets WHERE folio = '$folio'";

// BIEN — así se hace en todo el proyecto
$stmt = db()->prepare('SELECT * FROM tickets WHERE folio = ?');
$stmt->execute([$folio]);
```

**2. Nunca metas texto de la base directo en el HTML.** Usá siempre la función
`escapar()` que ya está en la página:

```javascript
// MAL — si alguien escribe <script> en la descripción, se ejecuta
elemento.innerHTML = ticket.descripcion;

// BIEN
elemento.innerHTML = escapar(ticket.descripcion);
```

**3. Nunca subas contraseñas al repositorio.** El archivo `includes/config.php`
tiene las claves de la base y **está excluido a propósito**. Si lo ves aparecer
cuando hacés `git status`, avisá antes de subir nada.

**4. Los errores van al log, no a la pantalla.** Un mensaje de error de la base
puede revelar nombres de tablas o rutas del servidor:

```php
} catch (Throwable $e) {
    fallo('tickets', $e);   // esto ya lo hace bien: loguea y responde genérico
}
```

**5. Nunca trabajes sobre las ramas `main` ni `develop`.** Siempre
en tu propia rama. En la sección 8 se explica cómo.

---

## 4. Preparar tu computadora

Necesitás tres cosas. Todo es gratis y de instalación estándar.

### 4.1 Git

Es lo que permite trabajar en equipo sin pisarse el trabajo.

* Descargalo de <https://git-scm.com/downloads>.
* Instalalo con todas las opciones por defecto (siguiente, siguiente, siguiente).
* Al terminar, abrí **Git Bash** (te lo instala junto) y escribí:

```bash
git --version
```

Si responde algo como `git version 2.44.0`, quedó bien.

Configurá tu identidad (aparece en cada cambio que hagas):

```bash
git config --global user.name "Tu Nombre Apellido"
git config --global user.email "tu.correo@ucr.ac.cr"
```

### 4.2 PHP

Es lo que hace funcionar el sitio.

* **Windows:** bajá "PHP 8.4 VS17 x64 Thread Safe" de <https://windows.php.net/download/>,
  descomprimí el ZIP en `C:\php`, y agregá `C:\php` al PATH del sistema
  (Buscar → "variables de entorno" → Path → Editar → Nuevo → `C:\php`).
* **Mac:** `brew install php`
* **Linux:** `sudo apt install php php-mysql`

Comprobalo abriendo una terminal **nueva**:

```bash
php --version
```

Tiene que decir 8.1 o superior.

Además, PHP necesita poder hablar con MySQL. Comprobalo con:

```bash
php -m
```

En la lista tiene que aparecer **pdo_mysql**. En Windows, si no aparece: abrí
`C:\php\php.ini` (si no existe, copiá `php.ini-development` con ese nombre) y
quitale el `;` del inicio a estas dos líneas:

```ini
extension=pdo_mysql
extension=mbstring
```

### 4.3 Base de datos

Necesitás MariaDB o MySQL corriendo en tu máquina.

Lo más simple en Windows y Mac es instalar **XAMPP**
(<https://www.apachefriends.org/>), que trae MariaDB y phpMyAdmin juntos.
Después de instalarlo, abrí el panel de XAMPP y arrancá **MySQL**.

> Si instalás XAMPP, **no** uses su Apache ni pongas ahí los archivos del
> proyecto. Solo lo vas a usar por la base de datos. El sitio se levanta como se
> explica en la sección 6.

### 4.4 Un editor

Cualquiera sirve, pero **Visual Studio Code** (<https://code.visualstudio.com/>)
es el más común y gratis. Instalale la extensión "PHP Intelephense" para que te
avise de errores mientras escribís.

---

## 5. Bajar el proyecto

El código vive en el GitLab de la UCR. Necesitás que te den acceso primero
(la persona que coordina el proyecto te va a mandar una invitación por correo:
aceptala y creá tu contraseña).

Cuando tengas la cuenta, abrí **Git Bash** y escribí, uno por uno:

```bash
cd ~/Documents
```

```bash
git clone https://git.ucr.ac.cr/paa/paa.git
```

Te va a pedir tu usuario y contraseña del GitLab de la UCR.

```bash
cd paa
```

```bash
git switch feature/tickets-detalle
```

**Esa última línea te pone en tu rama de trabajo, que ya está creada para vos.**
No tenés que crearla: existe y sale de `main`, que es la rama donde vive el
sistema nuevo.

Para comprobar que estás donde toca:

```bash
git branch --show-current
```

Tiene que responder `feature/tickets-detalle`.

> **Qué es una rama, en una frase:** es tu copia de trabajo del proyecto. Todo
> lo que hagas ahí queda aislado hasta que se revise y se integre, así que
> podés equivocarte tranquilo — no hay forma de que rompas el sistema real
> desde tu rama.
>
> **Si el `git clone` falla** con un error de conexión, puede ser que el GitLab
> de la UCR solo se alcance desde la red universitaria. En ese caso necesitás la
> VPN institucional (GlobalProtect, portal `acceso.ucr.ac.cr`, con tus
> credenciales UCR sin el `@ucr.ac.cr`). Pediles ayuda si nunca la instalaste.

---

## 6. Levantar el sitio en tu máquina

### 6.1 Crear tu base de datos

Abrí phpMyAdmin (con XAMPP: <http://localhost/phpmyadmin>) y creá una base
llamada `gestionpaa`, con cotejamiento **utf8mb4_general_ci**.

> El cotejamiento importa: es lo que hace que las tildes y las ñ se guarden
> bien. Si lo dejás en otro, vas a ver "PruÃ©ba" en vez de "Prueba".

### 6.2 Configurar la conexión

En la carpeta del proyecto, entrá a `PAA/includes/` y copiá el archivo
de ejemplo:

```bash
cd ~/Documents/paa/PAA
cp includes/config.example.php includes/config.php
```

Abrí `includes/config.php` en el editor y dejalo así (los valores de XAMPP por
defecto):

```php
return [
    'db_host' => '127.0.0.1',
    'db_name' => 'gestionpaa',
    'db_user' => 'root',
    'db_pass' => '',          // en XAMPP la clave de root viene vacía

    'db_port'   => null,
    'db_socket' => null,

    'correo_remitente' => 'PAA UCR <no-responder@localhost>',
];
```

> Este archivo **nunca se sube al repositorio**. Cada quien tiene el suyo con
> sus propios datos. Ya está en la lista de exclusiones, no tenés que hacer nada.

### 6.3 Crear las tablas

```bash
php scripts/migrar.php
```

Eso crea todas las tablas del sistema, incluidas las de tickets. Al terminar
tiene que decir `Listo.`

> Si dice que no se puede conectar, revisá que MySQL esté arrancado en XAMPP y
> que los datos de `config.php` sean correctos.

### 6.4 Cargar datos de prueba

El módulo de tickets necesita que existan sedes, porque cada avería se reporta
contra una. Para trabajar te alcanza con unas pocas inventadas — **no uses los
datos reales del personal de la UCR en tu máquina**.

Creá un archivo `sedes_prueba.csv` con este contenido:

```csv
idSede,NombreSede,Direccion,Provincia,Cantón,Distrito
1,LICEO DE PRUEBA UNO,100m norte de la iglesia,San José,San José,Carmen
2,COLEGIO DE PRUEBA DOS,Frente al parque,Cartago,Cartago,Oriental
3,LICEO DE PRUEBA TRES,Costado sur de la plaza,Heredia,Heredia,Heredia
```

Y cargalo:

```bash
php scripts/importar_csv.php sedes sedes_prueba.csv
```

### 6.5 Arrancar el sitio

```bash
php -S localhost:8000
```

Dejá esa ventana abierta (mientras esté abierta, el sitio funciona) y abrí en el
navegador:

**<http://localhost:8000/CreacionTickets/>**

Deberías ver la pantalla de tickets con las tres sedes de prueba en el selector.
Probá reportar una avería.

Para apagarlo: volvé a la terminal y apretá `Ctrl + C`.

---

## 7. Cómo está hecho el módulo

Vale la pena entender el recorrido completo de un dato antes de tocar nada.

```
Navegador                    Servidor                     Base de datos
─────────                    ────────                     ─────────────
CreacionTickets/index.php
  formulario
      │
      │  fetch() con JSON
      ▼
                       api/tickets.php
                         valida los datos
                         arma el folio
                              │
                              │  consulta preparada
                              ▼
                                                    tabla `tickets`
                              ┌───────────────────────────┘
                              │  respuesta
      ┌───────────────────────┘
      ▼
  muestra el folio
```

### `CreacionTickets/index.php` — la pantalla

Es un archivo solo: HTML, CSS y JavaScript juntos. Así es todo el proyecto.
Tiene tres partes, en este orden:

1. `<style>` — los colores y el diseño. Las variables de arriba (`--primary`,
   `--gray-100`…) son las mismas de todo el sistema: usalas en vez de escribir
   colores a mano, para que la pantalla combine con las demás.
2. `<body>` — el formulario y la lista.
3. `<script>` — la lógica. Ahí está `cargarTickets()`, `enviarTicket()`, etc.

### `api/tickets.php` — el backend

Recibe peticiones y responde JSON. Todo entra por el parámetro `accion`:

| Acción | Método | Qué hace |
| --- | --- | --- |
| `listar` | GET | Devuelve los tickets, con filtros |
| `catalogos` | GET | Sedes, categorías y personal para los selectores |
| `ver` | GET | Un ticket con su historial |
| `crear` | POST | Registra una avería nueva |
| `comentar` | POST | Agrega comentario, opcionalmente cambia el estado |
| `cambiarEstado` | POST | Cambia el estado |

Podés probar cualquiera desde el navegador (las de GET):

<http://localhost:8000/api/tickets.php?accion=listar>

Vas a ver el JSON crudo. **Es la mejor forma de saber si un problema está en el
backend o en la pantalla**: si acá los datos salen bien, el problema está en el
JavaScript.

### `schema/010_tickets.sql` — las tablas

Tres tablas: `tickets`, `ticket_categorias` y `ticket_comentarios`. Abrí el
archivo y leelo: los comentarios explican por qué cada campo es como es.

> **Ojo con las migraciones.** Un archivo de `schema/` que ya se aplicó **no se
> edita nunca más**, ni siquiera para arreglar algo. El sistema lleva la cuenta
> de cuáles corrió y no los vuelve a correr, así que tu cambio no le llegaría a
> nadie. Si necesitás modificar una tabla, creá un archivo nuevo:
> `schema/011_tickets_algo.sql` con el `ALTER TABLE` correspondiente.

---

## 8. Tu día a día de trabajo

### 8.1 Antes de empezar a trabajar cada día

```bash
cd ~/Documents/paa
```

```bash
git switch feature/tickets-detalle
```

```bash
git pull
```

Eso te deja en tu rama y trae lo último que se haya subido.

Y una vez por semana (o cuando te avisen que hubo cambios en el sistema), traé
también lo que hicieron los demás:

```bash
git fetch origin
git merge origin/main
```

### 8.2 Cuando termines esta tarea: crear la siguiente rama

Tu primera rama (`feature/tickets-detalle`) ya está creada. Cuando esa
tarea se integre y arranques otra, creá una rama nueva **siempre saliendo de
`main` actualizada**:

```bash
git switch main
git pull origin main
git switch -c feature/tickets-comentarios
```

El nombre se arma así: `feature/` + qué estás haciendo, en minúsculas, sin
tildes ni espacios. Ejemplos:

* `feature/tickets-comentarios`
* `feature/tickets-asignar`
* `fix/tickets-filtro-fecha`

**Una rama por tarea.** No mezcles dos cosas distintas en la misma: hace que la
revisión sea más difícil y que, si una tiene un problema, se atrase también la
otra.

### 8.3 Trabajar

Editá los archivos, recargá el navegador, probá. Cuando tengas algo que funcione
(no hace falta que esté terminado, sí que no esté roto), guardalo:

```bash
git status
```

Te muestra qué cambiaste. Después:

```bash
git add CreacionTickets/index.php
```

```bash
git commit -m "feat(tickets): muestra el detalle del ticket al hacer clic"
```

El mensaje va con este formato:

```
tipo(tickets): qué hiciste, en presente y en minúscula
```

Donde `tipo` es `feat` (algo nuevo), `fix` (arreglaste un error), `style`
(solo diseño), `refactor` (ordenaste código sin cambiar lo que hace) o `docs`
(documentación).

Ejemplos buenos:

```
feat(tickets): agrega el panel de comentarios
fix(tickets): corrige el filtro de prioridad, que ignoraba "crítica"
style(tickets): ajusta el espaciado de las tarjetas en móvil
```

Ejemplos malos: `cambios`, `avance`, `wip`, `arreglo`, `.`

Hacé commits seguido, de a poco. Es mucho mejor tener diez commits chicos que
uno gigante al final de la semana.

### 8.4 Subir tu trabajo

Como tu rama ya existe en GitLab, alcanza con:

```bash
git push
```

Subí tu trabajo **todos los días**, aunque no esté terminado. Si tu computadora
falla, lo que no subiste se perdió.

---

## 9. Entregar tu trabajo

Cuando terminaste una tarea y la probaste:

### 9.1 Traé los cambios de los demás y resolvé los choques

```bash
git fetch origin
git merge origin/main
```

Si dice `Already up to date` o `Merge made by...`, listo, seguí.

Si dice **CONFLICT**, significa que alguien tocó las mismas líneas que vos. No
es grave. Abrí el archivo que menciona y buscá esto:

```
<<<<<<< HEAD
   lo que escribiste vos
=======
   lo que escribió la otra persona
>>>>>>> origin/main
```

Dejá la versión correcta (a veces es una mezcla de las dos), **borrá esas tres
líneas de marcadores**, y después:

```bash
git add el-archivo-que-arreglaste
git commit
git push
```

Probá que todo siga funcionando después de resolver un conflicto. Un conflicto
mal resuelto no da error: simplemente hace algo distinto de lo que esperabas.

Si te enredaste y querés empezar de nuevo:

```bash
git merge --abort
```

Eso deja todo como estaba antes, sin perder tu trabajo.

### 9.2 Pedí que revisen tu trabajo

Entrá a <https://git.ucr.ac.cr/paa/paa> y vas a ver un botón que dice
**Create merge request** con el nombre de tu rama. Hacele clic y llená:

* **Target branch:** `main` (muy importante — **no** `develop`, que es la rama
  de producción).
* **Título:** lo mismo que pusiste en el commit.
* **Descripción:** qué hiciste y cómo lo probaste.
* **Assignee:** la persona que coordina el proyecto.

Y listo. Ella lo revisa, te comenta si hay algo que cambiar, y cuando esté bien
lo integra.

---

## 10. Cuando algo se rompe

### La página se ve en blanco

Casi siempre es un error de PHP. Mirá la terminal donde corriste
`php -S localhost:8000`: ahí sale el error con el número de línea.

### "No se pudo procesar la solicitud"

Es el mensaje genérico del backend (a propósito: no muestra detalles al
navegador). El detalle real está en la terminal del servidor.

### Los datos no aparecen en la pantalla

Probá el endpoint directo en el navegador:
<http://localhost:8000/api/tickets.php?accion=listar>

* **Si ahí los datos salen bien** → el problema está en tu JavaScript. Abrí la
  consola del navegador con `F12`, pestaña *Console*.
* **Si ahí también falla** → el problema está en el PHP o en la base.

### "Table 'gestionpaa.tickets' doesn't exist"

No corriste las migraciones. `php scripts/migrar.php`

### Rompí todo y quiero volver atrás

Para descartar los cambios de un archivo que **todavía no commiteaste**:

```bash
git checkout -- CreacionTickets/index.php
```

Para ver en qué estado quedó todo:

```bash
git status
```

Y si de verdad te perdiste: **preguntá**. Nada de lo que hagas en tu rama puede
romper el sistema de producción, así que no hay motivo para entrar en pánico.

---

## 11. Glosario

| Palabra | Qué significa |
| --- | --- |
| **Repositorio** | La carpeta del proyecto con todo su historial de cambios. |
| **Rama** (branch) | Una línea de trabajo paralela. La tuya no afecta a las demás hasta que se integre. |
| **Commit** | Un punto guardado en el historial, con un mensaje que dice qué cambiaste. |
| **Push** | Subir tus commits al servidor (GitLab). |
| **Pull** | Bajar lo que otros subieron. |
| **Merge request (MR)** | Pedido formal de que revisen tu trabajo e integren tu rama. En GitHub se llama *pull request*. |
| **Conflicto** | Dos personas cambiaron las mismas líneas; Git pide que decidas cuál queda. |
| **Endpoint** | Una dirección del backend que responde datos, como `api/tickets.php`. |
| **JSON** | El formato en que el backend manda los datos al navegador. |
| **Migración** | Un archivo `.sql` que crea o modifica tablas. Se corre una sola vez. |
| **PDO** | La forma en que PHP habla con la base de datos en este proyecto. |
| **Consulta preparada** | Una consulta SQL donde los datos van aparte, para que nadie pueda inyectar comandos. |
| **XSS** | Ataque que consiste en meter código en un campo de texto para que se ejecute en el navegador de otra persona. Se evita con `escapar()`. |

---

## Contactos

* Dudas del módulo o del proyecto → la persona que coordina la migración.
* Problemas de acceso al GitLab de la UCR o a la VPN → Centro de Informática,
  2511-5000, ci5000@ucr.ac.cr

**Última recomendación:** preguntá temprano y seguido. Media hora de preguntas
al principio ahorra días de trabajo en la dirección equivocada.
