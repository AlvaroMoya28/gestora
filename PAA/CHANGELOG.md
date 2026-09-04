# Bitácora de cambios

Todo cambio al sistema se anota aquí: esquema de base de datos, código PHP,
páginas, despliegues. La entrada más reciente va arriba.

**Formato de cada entrada:** fecha · qué cambió · por qué · qué archivos toca ·
si requiere migración o despliegue.

---

## 2026-08-25 — Ingreso de Datos: sin dato de la base, no se sugiere nada

**Qué:** cuando la sede no trae el total de folletos del coordinador, el
sistema dejó de proponer «uno por aula». Ahora no calcula ningún «hasta»: la
persona escribe el «desde» y el «hasta» a mano, sin ningún número inventado de
por medio.

**Por qué:** el cambio anterior mostraba «La base no trae el dato. Se asume
uno por aula: N» y de todos modos calculaba el «hasta» con ese número. Era una
regla observada en datos de otros años, ofrecida en el mismo lugar donde
segundos antes se mostraba un dato real de la sede — fácil de aceptar sin
mirar. Dos campos vacíos que hay que llenar es más trabajo que uno
autocompletado, pero no hay ambigüedad sobre qué es un dato y qué es una
costumbre.

La validación del guardado sigue igual en lo que importa: sin un número de la
base no hay nada contra qué comparar, así que no se avisa ninguna discrepancia
—no hay con qué justificarla—.

**Archivos:** `IngresoDatos/index.php` — `folletosCoordEsperados()` devuelve
`null` en vez de `aulas.length`; `sugerirCoordHasta()` no escribe nada sin
dato; el aviso de discrepancia en `revisarFolletos()` no dispara sin `esperados`.

**Requiere:** despliegue. No requiere migración.

---

## 2026-08-25 — Ingreso de Datos: se contrasta contra la base, no contra una suposición

**Qué:** la columna que decía «Estudiantes» ahora dice «Folletos», que es lo
que en realidad es —cuántos folletos lleva el aula, no cuánta gente hay
sentada—. Se agregó una columna «Cubre» que muestra cuántos folletos da el
rango escrito, para contrastar los dos números a simple vista. Y los folletos
del coordinador ahora muestran lo que dice la base, no una suposición de «uno
por aula» sin decirlo.

**Por qué:** «uno por aula» era una regla observada en datos de años
anteriores, no un dato guardado — y se ofrecía como si fuera lo segundo. La
base sí tiene el número: `aulas.total_folletos_coord`, cargado desde el Sheet
original en cada fila de cada aula de la sede. Se lee ese valor y se muestra
junto al campo, para que quien captura vea con qué está comparando en vez de
confiar en un cálculo silencioso.

Cuando la sede no trae el dato —queda en cero—, se sigue usando «uno por
aula», pero ahora se dice con esas palabras: «La base no trae el dato. Se
asume uno por aula: N». La discrepancia entre el rango escrito y lo esperado
pasa a la validación del guardado como aviso, no como error: el número de
referencia puede ser una suposición, y bloquear por una suposición sería peor
que avisar.

**Archivos:**

- `includes/sedes_datos.php` — `folletos_del_coordinador()`, que lee
  `total_folletos_coord` de las aulas de la sede; se agrega `folletosCoord` a
  lo que devuelve `datos_de_sede()`.
- `IngresoDatos/index.php` — columna «Folletos» (la base) y «Cubre» en la
  tabla de aulas; debajo de los campos del coordinador, lo que dice la base y
  si el rango escrito le calza.

**Requiere:** despliegue. No requiere migración (la columna ya existía).

---

## 2026-08-25 — Ingreso de Datos: menos tecleo, validación explícita y trabajo en paralelo

**Qué:** se rehízo el flujo de captura a partir de las recomendaciones que
quedaron pendientes. Son seis cambios y una tabla nueva.

**1. Se teclea un tercio.** La cantidad de estudiantes de cada aula ya está en la
base, así que de un solo número sale toda una tanda de folletos. Ahora se
escriben tres puntos de partida —aulas, adecuación y coordinador— y el resto se
completa solo. En una sede promedio (6,6 aulas) eran 9 números; ahora son 3. En
las 317 sedes: de 2.853 números tecleados a 951.

Lo autocompletado se ve marcado y **se puede corregir**. Al corregir el «desde»
de un aula, las que siguen se recalculan a partir de esa: un error se arregla en
un campo y no reescribiendo toda la sede. Un cálculo que no se puede tocar
obliga a inventar rodeos el día que hay una excepción, y siempre hay una.

**2. Validación que dice qué está mal, y se puede pasar por alto.** Al guardar se
comprueba lo comprobable: aulas con estudiantes y sin folletos, rangos que no
cuadran con la cantidad de estudiantes, aulas que se pisan entre sí, huecos, y
folletos del coordinador cruzados con los de un aula. Cada problema se explica
con sus números concretos, no con un «datos inválidos». Se separan **errores**
de **avisos**, y siempre existe «Guardar así de todos modos»: el día de la
aplicación aparecen excepciones que ninguna regla prevista de antemano cubre, y
un sistema que no las deja registrar obliga a anotarlas en un papel.

**3. Los marchamos se escanean de corrido.** El lector manda un Enter al
terminar, así que el foco salta solo al siguiente campo y los dos marchamos de
una tula se escanean sin tocar el mouse. Al escanear el último se guarda solo.
Los códigos repetidos se marcan en rojo al instante, en vez de al guardar
—enterarse al final significa rehacer la tanda—.

**4. Cuatro pasos en vez de cinco.** Los folletos del coordinador eran dos campos
con pantalla propia. Se escriben de corrido con los de las aulas, así que se
unieron: un «guardar y seguir» menos por sede, trescientas diecisiete veces.

**5. Se guarda solo.** Un segundo después de la última tecla, lo escrito ya está
en la base. Salir de una sede, cambiar de pantalla o cerrar el navegador dejó de
costar trabajo. Va marcado como borrador: escribe los datos pero no marca el
paso como terminado, o una sede a medio llenar aparecería como lista.

**6. Dos personas en la misma sede: ahora se avisa.** Es el cambio con más
fondo. Todo el personal extraordinario entra con **la misma cuenta**, así que el
usuario de la sesión no distingue a nadie: comparando por usuario, todos son
«la misma persona» y el aviso no salta nunca. Lo que separa a una de otra es la
sesión del navegador. Y como una sesión no tiene nombre, se le pide uno al
entrar —es lo que convierte «esta sede está ocupada» en «esta sede la tiene
Ana», que se resuelve hablando.

No es un candado duro: se puede entrar igual a una sede tomada, porque a veces
la persona ya se fue. Lo que no se puede es entrar sin enterarse. Si alguien
entra a la sede que ya tenés abierta, salta el aviso de los dos lados.

**7. El filtro de segmentos.** Las opciones de «me toca la parte» salen de en
cuántas partes se dividió: se podía dividir en dos y elegir la parte siete, que
no existe, y la lista quedaba vacía sin explicar por qué. Además el reparto se
calculaba sobre la lista ya filtrada por el buscador, así que cambiaba a cada
letra que alguien escribiera; ahora sale de la lista completa y son bloques
seguidos —«te tocan de la 401 a la 1030» es una instrucción que se puede
seguir; «te tocan la 401, la 404 y la 407» no—.

**Un arreglo de paso:** el código de bloqueos que ya estaba usaba una tabla
`sedes_bloqueos` que **no tenía migración**. Al no existir, tomar la sede fallaba
y el módulo no dejaba entrar a ninguna.

**Archivos:**

- `schema/020_bloqueos_sede.sql` — la tabla que faltaba, con sesión y nombre.
- `api/ingreso_datos.php` — bloqueos por sesión y no por usuario (el usuario ya
  no lo manda el cliente: sale de la sesión de PHP), guardado en borrador, y
  bitácora silenciada para los latidos y los autoguardados, que si no la
  llenarían de ruido y enterrarían lo que sí importa.
- `IngresoDatos/index.php` — todo lo anterior en pantalla.
- `includes/bitacora.php` — `bloqueos` pasa a ser acción de solo lectura.

**Requiere:** migración (`php scripts/migrar.php`) y despliegue.

---

## 2026-08-25 — Arreglo: la migración 018 se corrigió editándola, y eso no alcanza

**Qué:** se agregó `schema/019_estudiantes_identidad.sql`, que deja la tabla
`estudiantes` en la forma correcta, y una comprobación de esquema en
`api/traslados.php` que avisa cuando faltan migraciones en vez de responder 500.

**Por qué:** al descubrir que el número de fórmula no identifica a nadie, corregí
la migración `018` en su propio archivo. En una base donde la `018` todavía no se
había aplicado eso funciona; en una donde ya se había aplicado, no: `migrar.php`
lleva la cuenta **por nombre de archivo**, así que la versión corregida nunca
vuelve a correr. La tabla se quedó con el índice único sobre la columna
equivocada y sin `fecha_texto` ni `sede_nombre_texto`.

El síntoma fue el peor posible: el análisis del archivo funcionaba —solo lee— y
la carga devolvía **500 sin explicación**, con el detalle únicamente en el log
del servidor, que es donde nadie lo va a buscar cuando algo falla con prisa.

**La regla, para no repetirlo:** una migración ya aplicada no se edita, se
encadena. Editarla solo es válido mientras no haya corrido en ningún lado, y eso
deja de ser cierto en cuanto alguien despliega.

**Archivos:**

- `schema/019_estudiantes_identidad.sql` — repara la tabla. Es idempotente
  (`ADD COLUMN IF NOT EXISTS`, `DROP INDEX IF EXISTS`): deja lo mismo tanto si se
  aplicó la `018` vieja como la corregida, y no borra nada.
- `api/traslados.php` — `exigir_esquema_al_dia()` convierte el 500 en un mensaje
  que dice qué columnas faltan y qué comando correr.

**Requiere:** migración (`php scripts/migrar.php`) y despliegue.

---

## 2026-08-25 — Módulo nuevo: Traslado de Estudiantes

**Qué:** se agregó el módulo `Traslados/`, que mueve a los estudiantes de una
sede y un turno hacia otra sede y los redistribuye en aulas de otra capacidad.
Trae consigo la primera tabla de estudiantes del sistema (`estudiantes`), su
carga desde archivo (CSV, XLSX o el «.xls» que es HTML), la exportación a Excel
y el historial de traslados con opción de deshacerlos.

**Por qué:** una sede avisó que no presta las instalaciones para el turno del
domingo por la mañana, con las aulas ya asignadas y los estudiantes ya
distribuidos. Había que moverlos a otra sede que además tiene aulas de otra
capacidad —donde iban 30 ahora van 25—, lo que no es reasignar un aula sino
redistribuir a toda la población de la sede. Hasta ahora el sistema conocía las
sedes, las aulas y quién las coordina, pero no a la gente que se sienta a hacer
la prueba: ese dato vivía solo en el otro sistema.

**La regla de fondo:** el número de fórmula no se toca nunca. Es la versión del
cuadernillo que le tocó a cada quien y se va con la persona a la sede nueva. Por
eso `estudiantes.fila_original` guarda la fila del archivo completa en JSON: al
exportar se devuelven las mismas columnas que entraron, con solo los datos de
ubicación cambiados, y el archivo se puede volver a subir al otro sistema.

**Tres cosas que el archivo real de 2026 desmintió, y conviene tener presentes
antes de tocar este módulo:**

1. **La fórmula NO identifica a nadie.** Tiene diez valores —del 1 al 10— para
   56 737 estudiantes. Quien identifica es el número de cómputo (`compute_0005`),
   con un valor distinto por persona. El diseño inicial tomó la fórmula como
   identidad y su índice único habría colapsado a toda la población en diez
   filas. Se corrigió antes de aplicar la migración.
2. **La columna `NOMBRE` es el nombre de la sede**, no el de la persona; esa
   viene en `nombre_completo`. Las 317 sedes tienen un solo valor de `NOMBRE`
   cada una, que es lo que lo confirma.
3. **La columna `CONVOCATORIA` es el turno** («3-M-DOMINGO»), no el año; el año
   viene en `anno`. Son dos cosas distintas con nombres que se cruzan con los
   del sistema.

Por eso un traslado cambia **cinco** columnas y no dos: sede, aula, turno, el
nombre de la sede y la fecha (que trae la hora pegada al turno). El nombre y la
fecha se copian de quien ya esté en la sede o el turno de destino, para
conservar el formato del otro sistema en vez de inventarlo.

**Decisiones que conviene conocer antes de tocarlo:**

- **Nada se escribe sin simular.** El botón de aplicar no existe hasta que hay
  una simulación sin errores, y `trasladar` vuelve a calcular el plan por su
  cuenta en vez de confiar en lo que manda la pantalla: entre que alguien mira
  la simulación y aprieta el botón puede haber pasado otra carga.
- **Errores y avisos van separados.** Un error bloquea; un aviso solo hay que
  haberlo leído. Mezclados, se ignoran los dos.
- **Sincronizar las aulas de embalaje es opcional y se pregunta.** `aulas` es
  donde Ingreso de Datos guarda folletos, tulas y marchamos. Un traslado cambia
  cuánta gente hay en cada aula y eso hay que reflejarlo, pero hacerlo en
  silencio sobre una sede ya embalada sería peor. Las aulas que se vacían nunca
  se borran: se les pone 0 asignados.
- **Se puede deshacer.** `traslado_detalle` guarda el antes y el después de cada
  estudiante. Revertir se niega si alguno se movió otra vez después, porque
  dejaría a esa persona en un lugar que nadie decidió.
- **Las columnas del archivo se reconocen solas.** Da igual si dicen
  `NUMERO_AULA`, `numeroAula` o `Número de Aula`. El archivo lo genera otro
  sistema y cambia de un año a otro; exigir nombres exactos convertiría
  cualquier cambio menor de ese lado en una importación que falla el día que hay
  prisa. La pantalla muestra qué reconoció antes de escribir nada.

**Archivos:**

- `schema/018_estudiantes_traslados.sql` — tablas `estudiantes`, `traslados`,
  `traslado_detalle` y `cargas_estudiantes`.
- `includes/tabla_archivo.php` — lectura de CSV, XLSX (ZipArchive + XMLReader,
  en streaming), tablas HTML y cualquiera de los tres en `.gz`; reconocimiento
  de columnas por alias.
- `api/traslados.php` — endpoint del módulo.
- `Traslados/index.php` — la pantalla.
- `includes/modulos.php` — el módulo entra al catálogo, solo administración.
- `includes/bitacora.php` — se suman las acciones de solo lectura nuevas.
- `api/importacion.php` — el borrado de un año ahora incluye las cuatro tablas
  nuevas, en orden de dependencia y antes de `sedes`.

**Sobre el tamaño del archivo:** el padrón de 2026 son 3,2 MB en `.xlsx`, que se
pasa del tope de subida del servidor. Convertirlo a CSV lo empeora —sube a
6,9 MB, porque el `.xlsx` ya es un ZIP por dentro y el CSV es texto plano—. Por
eso el lector acepta `.gz`: el mismo CSV comprimido pesa 938 KB. Es la vía
recomendada para cargas grandes y no necesita tocar la configuración del
servidor.

**Requiere:** migración (`php scripts/migrar.php`) y despliegue.

---

## 2026-08-21 — Módulo Tulas portado al sistema nuevo

**Qué:** se creó la página nueva `Tulas/` en el sistema PHP y quedó enlazada
desde la portada principal. La vista ya consulta la base de datos real vía
`api/aulas.php`, permite cambiar el estado de las tulas, enviar comprobantes y
buscar por código de barras o marchamos usando los datos nuevos del esquema.

**Por qué:** Tulas era uno de los módulos principales del sistema anterior y
ya tenía backend listo en MariaDB. Faltaba la capa visual para que el personal
pueda usarlo desde el nuevo sitio, sin depender del Sheet ni del Apps Script.

**Archivos:**

- `Tulas/index.php` — nueva interfaz del módulo, conectada a `api/aulas.php`.
- `index.php` — Tulas pasa a aparecer como módulo disponible en la portada.

**Requiere:** despliegue. **No requiere migración** (usa tablas ya creadas).

---

## 2026-08-21 — Directorio: Coordinadores pasa a Funcionarios PAA (alta y eliminación)

**Qué:** el módulo `Coordinadores_Directorio` ahora se presenta como
**Funcionarios PAA** y deja de ser solo lectura: se agregó creación de
funcionarios con formulario completo (nombre, área, correo, extensión y
teléfonos) y eliminación con advertencia de confirmación desde la misma
interfaz.

**Por qué:** el catálogo `personas` ya es transversal para varios módulos; hacía
falta administrarlo sin entrar a phpMyAdmin y con validaciones mínimas en
backend para evitar datos incompletos o inconsistentes.

**Archivos:**

- `Coordinadores_Directorio/index.php` — nuevo encabezado "Funcionarios PAA",
  formulario de alta, botones de eliminar por tarjeta y confirmación previa.
- `api/coordinadores.php` — mantiene `GET` para listado y agrega `POST` con
  `accion=crear` y `accion=eliminar` (borrado lógico en `personas.activo=0`),
  incluyendo validación de correo, campos obligatorios, teléfonos y alta de área
  si no existe.

**Requiere:** despliegue. **No requiere migración** (usa tablas existentes).

---

## 2026-08-21 — Esquema y backend de los ocho módulos restantes

**Qué:** se diseñó y escribió de una vez la **capa de datos completa** del
sistema (migraciones `002` a `008`) y el **endpoint de cada módulo**, más el
importador que carga a la base el contenido de los Google Sheets. La página de
**Sedes** quedó migrada y funcionando contra la base.

**Por qué de una sola vez y no módulo por módulo:** al mapear las fuentes
apareció que cuatro herramientas —Tulas, Asignación de Marchamos, Revisión de
Aulas y Administración— leen **el mismo Sheet** (`1KEcA3…`), cada una
interpretándolo a su manera. Diseñar sus tablas por separado habría vuelto a
crear cuatro copias del mismo dato, que es justo lo que la migración viene a
resolver. El esquema se hizo completo; las páginas siguen migrándose de a una.

### Migraciones nuevas

| Archivo | Qué crea |
| --- | --- |
| `002_sedes.sql` | `sedes`, tabla núcleo referenciada por casi todos los módulos |
| `003_personas.sql` | **renombra** `coordinadores` → `personas` y `coordinador_telefonos` → `persona_telefonos` |
| `004_aulas_tulas.sql` | `convocatorias`, `aulas`, `marchamos`, `movimientos_tula`, `correos_enviados` |
| `005_activos.sql` | `activos`, `prestamos`, `secuencia_boletas` |
| `006_revision_aulas.sql` | `materiales`, `actas_revision`, `acta_materiales` |
| `007_sobresueldos.sql` | `sobresueldos`, `hospedajes` |
| `008_asignacion_coordinadores.sql` | `postulantes`, `asignaciones_sede` |

**El renombre a `personas` (003) es el cambio de fondo.** La hoja "Personal" de
Activos tiene exactamente la misma estructura que el directorio de
coordinadores, con las mismas personas cargadas dos veces. Darle tabla propia
habría repetido el problema. `RENAME TABLE` conserva los datos: las 19 personas
y sus 22 teléfonos siguen intactos. Se hizo ahora porque solo un endpoint
dependía de esa tabla; dentro de tres módulos habría costado mucho más.

### Reglas del sistema viejo que ahora impone la base

- **Estados de tula**: eran una lista blanca comprobada en código; ahora son un
  `ENUM`. Un valor inválido lo rechaza el motor.
- **Marchamos sin repetir**: el Apps Script leía la hoja entera y armaba un
  índice en memoria (`marchamosUsados_`, `choqueDeMarchamo_`) — que no protege
  de dos asignaciones simultáneas. Ahora es un índice `UNIQUE` por convocatoria.
- **Una sola fila de presupuesto por sede**: SobreSueldos escribía por número de
  fila (`getRange(rowIndex, 4, 1, 12)`), así que insertar una fila en el Sheet
  guardaba el presupuesto en la sede equivocada, en silencio. Ahora la clave es
  sede + convocatoria.
- **Número de boleta de préstamo**: salía de "la última fila de la hoja", que se
  rompe al borrar una fila o con dos préstamos a la vez. Ahora sale de
  `secuencia_boletas`, dentro de la misma transacción que marca el equipo.

### Lo que se preserva tal cual

**El destinatario del correo nunca viene del cliente.** Los endpoints reciben el
número de sede o el nombre de la persona y resuelven la dirección contra la
base (`includes/correo.php`). Es la regla que hacía seguros los Apps Script y la
razón sigue vigente: las páginas son públicas, y aceptar la dirección
convertiría el sistema en un relay de correo a nombre de la UCR. También se
mantienen el tope diario de envíos y la limpieza del HTML.

### Archivos nuevos

- **Endpoints:** `api/sedes.php`, `api/aulas.php`, `api/marchamos.php`,
  `api/activos.php`, `api/revision_aulas.php`, `api/sobresueldos.php`,
  `api/asignacion_coordinadores.php`, `api/administracion.php`
- **Compartido:** `includes/peticion.php` (entrada, método, convocatoria activa),
  `includes/correo.php` (envío con destinatario resuelto en el servidor)
- **Datos:** `scripts/importar_csv.php` — nueve destinos, idempotente por clave
  natural, todo dentro de una transacción
- **Página:** `Sedes/index.php` — migrada; ya no pasa por `opensheet.elk.sh` ni
  `opensheet.vercel.app`, dos servicios de terceros por los que viajaban los
  datos de contacto de cada centro educativo
- `DESPLIEGUE.md` — pasos ordenados para aplicar todo esto en el servidor

**Modificados:** `api/coordinadores.php` (usa `personas`), `index.php` (estado de
los módulos), `includes/config.example.php` (remitente de correo).

**Requiere migración y despliegue.** Ver `DESPLIEGUE.md`.

**Pendiente:** portar las páginas de Tulas, Marchamos, Revisión de Aulas,
Activos, SobreSueldos, Asignación de Coordinadores y Administración. El backend
de las siete ya está listo y probado contra el esquema; falta cambiarles la capa
de datos, que es lo que se hizo con Sedes en esta misma entrada.

---

## 2026-08-20 — Despliegue del módulo Coordinadores en el servidor

**Qué:** primer despliegue real. El sistema quedó en `~/public_html/web` con la
migración `001` aplicada y funcionando contra MariaDB 10.11.18 / PHP 8.4.24.

**Verificado en el servidor:** 11 áreas, 19 coordinadores y 22 teléfonos en la
base; `api/coordinadores.php` devuelve JSON con los acentos correctos y los
dobles números ya separados en lista.

**Lo que se aprendió del hosting** (todo anotado en `CONTEXTO.md`):

- La raíz web es `~/public_html/web`, no `~/public_html`.
- `acceso01` es solo el contenedor de acceso: **no corre el servidor web ni
  tiene DNS**. Las verificaciones por HTTP hay que correrlas desde una máquina
  con VPN, no desde ahí.
- El documento de credenciales trae **dos contraseñas distintas**, una de
  SSH/SFTP y otra de la base. Usar la de SSH en `config.php` da
  `Access denied ... 'gestionpaa'@'acceso01'` en PDO mientras el cliente
  `mysql` sí conecta — un síntoma que despista bastante.

**Bug corregido — `verificar_seguridad.php` daba falsos aprobados.**
Cuando una ruta no se podía consultar (por ejemplo, sin DNS), el script la
saltaba con `continue` sin contarla y al final imprimía "Todo correcto". Es
decir: reportaba éxito habiendo comprobado cero rutas. Eso es peor que no tener
el script, porque da luz verde para migrar creyendo que los respaldos están
protegidos. Ahora las rutas sin respuesta se cuentan aparte, el script dice
explícitamente que la verificación no se hizo y sale con código 2.

**Portada nueva — `index.php` en la raíz.** Reemplaza el "Hola mundo" del
Centro de Informática, que además imprimía el hostname y la IP interna del
contenedor. Cumple también una función de seguridad: sin un index en la raíz,
Apache puede listar el directorio y mostrar los nombres de `includes/`,
`schema/`, `scripts/` y `backups/`. Lista las 9 herramientas; solo las
migradas son clicables, el resto se ven apagadas para que no parezcan enlaces
rotos. Los módulos se declaran en un arreglo PHP, así que sumar uno es una
línea.

**Archivos:** `index.php` (nuevo), `scripts/verificar_seguridad.php`,
`CONTEXTO.md`.

**🚨 Pendiente crítico:** `gestionpaa.ucr.ac.cr` no existe en el DNS y el
contenedor web no responde ni por IP, así que **nunca se pudo comprobar si los
`.htaccess` bloquean de verdad `backups/`**. Hoy no hay riesgo porque nadie
alcanza el sitio, pero los dumps ya contienen los datos personales de las 19
personas. Antes de que el Centro de Informática publique el dominio hay que
correr `verificar_seguridad.php` desde una máquina con VPN. Ver el bloqueante
en `CONTEXTO.md` §3.

---

## 2026-08-20 — Verificación del módulo Coordinadores contra el Sheet original

**Qué:** se contrastó lo migrado contra las dos fuentes de verdad —el
`Coordinadores_Directorio.html` original y el contenido del Sheet— antes de
desplegar. No hubo que corregir nada.

**Datos:** se reconstruyó el Sheet a partir de `schema/001_coordinadores.sql` y
se comparó fila por fila. Coincidencia exacta en las 19 personas: área,
nombre, correo, teléfonos y extensión. Las 11 áreas conservan el orden de
aparición del Sheet en la columna `orden`. Los 3 casos de doble teléfono
(Mirania Astorga, Karol Jiménez, Andrei Fallas) quedaron como 2 filas cada uno
en `coordinador_telefonos` — 22 filas para 19 personas.

**Interfaz:** el CSS, el hero, el buscador, las tarjetas, el sello de frescura
y la lógica de agrupación por área son los mismos del original. Lo único
retirado fue la capa de Google Sheets (URL `gviz/tq?tqx=out:csv` y su parser)
y CSS muerto heredado de otra herramienta (`modal-result`). El banner rojo se
conservó, ahora para fallos del endpoint propio en vez de "Sin conexión con
Google Sheets"; el `noindex, nofollow` sigue puesto.

**Decisión — orden dentro de cada área.** El original respetaba el orden de
filas del Sheet; el endpoint ordena alfabético por nombre, lo que reacomoda a
las personas en Equipo de Verbal, Equipo de Matemática y Administrativos. Se
consultó y se confirmó que ese orden era arbitrario, así que **se deja
alfabético**: es más predecible para buscar a alguien y no obliga a mantener
un número de orden a mano cuando entre personal nuevo. Si algún día hace falta
el orden manual, es agregar una columna `orden` a `coordinadores` en una
migración nueva. Queda anotado en `CONTEXTO.md`.

**Archivos:** ninguno de código. Solo `CONTEXTO.md` (la decisión de orden) y
este `CHANGELOG.md`.

**Requiere:** nada. El módulo sigue pendiente de desplegar, sin cambios.

---

## 2026-08-20 — Correcciones encontradas al probar contra MariaDB real

Se levantó una MariaDB 10.11 de prueba y se corrió el sistema completo. Dos
bugs reales aparecieron ahí:

**1. `migrar.php` fallaba con "There is no active transaction".**
El script envolvía cada migración en una transacción. MySQL/MariaDB hace
`COMMIT` implícito en cada `CREATE`/`ALTER`/`DROP`, así que para cuando se
llamaba a `commit()` la transacción ya no existía. Se quitó la transacción: no
aportaba atomicidad con DDL, solo rompía. La red de seguridad real es el
respaldo previo.

**2. `respaldar_bd.php` sobrescribía el respaldo anterior.**
El nombre del archivo usa un sello por segundo, y `migrar.php` respalda dos
veces (antes y después) casi al mismo tiempo. El segundo pisaba al primero —
que era justo el que servía para revertir. Ahora agrega sufijo `-2`, `-3`...
si el nombre ya existe.

**3. Se agregó soporte de `db_port` y `db_socket`** en `includes/config.php`,
para poder correr una copia local de la base durante el desarrollo. El archivo
de opciones para `mysqldump`/`mysql` se movió a `includes/opciones_cliente.php`,
compartido por los scripts de respaldo y restauración.

**Verificado:** migración desde cero, idempotencia al re-correr, respaldo,
restauración tras borrado de datos, endpoint JSON con acentos correctos, y
renderizado de la página (19 personas, 11 áreas, buscador filtrando bien).

---

## 2026-08-20 — Estructura de respaldos y migraciones

**Qué:** se agregó el sistema de respaldos, la bitácora de migraciones y la
protección de carpetas privadas. La raíz del sitio pasa a ser `PAA/`.

**Por qué:** cada cambio al esquema o a los datos tiene que quedar respaldado y
poder revertirse. Antes no había forma de volver atrás si una migración salía
mal.

**Archivos:**
- `scripts/respaldar_bd.php` — dump con fecha + registro del motivo
- `scripts/restaurar_bd.php` — restaura, respaldando primero el estado actual
- `scripts/migrar.php` — aplica `schema/*.sql` pendientes, lleva la cuenta en
  la tabla `migraciones`, respalda antes y después
- `scripts/verificar_seguridad.php` — confirma que las carpetas privadas no se
  puedan descargar por HTTP
- `.htaccess` en `includes/`, `backups/`, `schema/`, `scripts/`

**Requiere:** correr `php scripts/verificar_seguridad.php <url>` después del
primer despliegue.

---

## 2026-08-20 — Módulo Coordinadores_Directorio

**Qué:** primer módulo migrado. Se reemplazó la lectura del CSV público de
Google Sheets por un endpoint PHP propio contra MariaDB.

**Por qué:** es el catálogo base que otros módulos van a reutilizar
(AsignacionCoordinadores, AsignacionMarchamos, RevisionAulas, CreacionTickets).
Migrarlo primero evita duplicar los datos de coordinadores en cada herramienta,
que es justo el problema del sistema viejo.

**Esquema nuevo:** `areas`, `coordinadores`, `coordinador_telefonos`.
Los teléfonos van en tabla aparte porque varias personas tienen dos números y
en el Sheet venían como una sola cadena (`"8874-0425 / 4701-0551"`) que el
frontend tenía que partir en cada render.

**Archivos:**
- `schema/001_coordinadores.sql` — DDL + 19 personas en 11 áreas
- `api/coordinadores.php` — endpoint JSON, solo lectura
- `Coordinadores_Directorio/index.php` — misma interfaz, sin el parser de CSV
- `includes/db.php`, `includes/config.example.php` — conexión compartida

**Requiere:** migración + despliegue.

**Pendiente:** panel de administración (por ahora los datos se editan por
phpMyAdmin).
