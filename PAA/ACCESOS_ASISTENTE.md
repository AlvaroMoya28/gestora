# Dar acceso al asistente — guía para quien coordina

Cómo dejar que otra persona desarrolle el módulo de Tickets **sin** que pueda
tocar el resto del sistema, la base de datos real ni los datos del personal.

El manual que le tenés que pasar a esa persona es [`MANUAL_ASISTENTE.md`](MANUAL_ASISTENTE.md).

---

## Lo primero: qué se puede y qué no

| Lo que pediste | ¿Se puede? |
| --- | --- |
| Que tenga su propio acceso para programar | ✅ Sí, por GitLab. Lo hacés vos, hoy, en 5 minutos |
| Que solo pueda modificar el módulo de Tickets | ✅ Sí, con ramas protegidas + revisión de cada cambio |
| Que tenga su propio usuario en el servidor de la UCR | ⚠️ No lo podés crear vos: hace falta `root`. Hay que pedírselo al Centro de Informática |

**Por qué no podés crear el usuario del servidor:** crear cuentas en Linux
requiere permisos de administrador del sistema, y `gestionpaa` es una cuenta de
hosting, no de administración. Podés confirmarlo entrando por SSH y escribiendo:

```bash
sudo -l
```

Si responde que el usuario no está autorizado (o pide una clave que no tenés),
es que no se puede. Es lo normal en ese tipo de hosting.

**La buena noticia:** no lo necesita para trabajar. Con GitLab alcanza, y de
hecho es mejor — más abajo se explica por qué.

---

## Paso 1 — Subir la rama del asistente a GitLab

El sistema nuevo ya está en GitLab, en la rama `main`. Lo que falta es publicar
**la rama donde va a trabajar el asistente**, para que pueda descargarla:

```bash
cd C:\Users\PAA-ADIC\Desktop\paa
git push -u origin feature/tickets-detalle
```

Comprobalo entrando a <https://git.ucr.ac.cr/paa/paa/-/branches>: la rama tiene
que aparecer en la lista.

> Si hiciste cambios en `main` que todavía no subiste, subilos también
> (`git push origin main`), o el asistente va a clonar una versión incompleta.

---

## Paso 2 — Darle cuenta y agregarlo al proyecto

1. Entrá a <https://git.ucr.ac.cr/paa/paa>.
2. Menú de la izquierda → **Manage** → **Members**.
3. Botón **Invite members**.
4. Escribí su correo institucional.
5. En **Select a role**, elegí **Developer**.
6. En **Access expiration date**, poné una fecha (por ejemplo, cuando termine
   el semestre). Es sano: si se va, el acceso caduca solo.
7. **Invite**.

> Si la persona no tiene cuenta en el GitLab de la UCR, la invitación le llega
> por correo y la crea ahí mismo con sus credenciales institucionales. Si no
> le llega, que escriba al Centro de Informática (2511-5000, ci5000@ucr.ac.cr).

### Qué puede y qué no puede hacer un Developer

| Puede | No puede |
| --- | --- |
| Ver todo el código | Subir directo a las ramas protegidas |
| Crear sus propias ramas | Aprobar sus propios merge requests |
| Subir cambios a sus ramas | Borrar ramas protegidas |
| Abrir merge requests | Cambiar la configuración del proyecto |
| Comentar y abrir issues | Agregar o quitar miembros |

---

## Paso 3 — Proteger las ramas (esto es lo que de verdad lo limita)

Sin esto, un Developer puede escribir directo en la rama principal.

1. En el proyecto → **Settings** → **Repository**.
2. Desplegá **Protected branches**.
3. Asegurate de que estas tres estén protegidas así:

| Rama | Allowed to merge | Allowed to push |
| --- | --- | --- |
| `develop` | Maintainers | **No one** |
| `main` | Maintainers | **No one** |
| `main` | Maintainers | **No one** |

Con `Allowed to push: No one`, **nadie** —ni vos— puede escribir directo en esas
ramas. Todo tiene que entrar por un merge request, y solo un Maintainer (vos)
puede aceptarlo.

Ese es el control real: el asistente puede escribir lo que quiera **en su rama**,
pero para que llegue al sistema tenés que aprobarlo vos, viendo exactamente qué
líneas cambió.

### Además, pedile aprobación a cada merge request

1. **Settings** → **Merge requests**.
2. En **Merge request approvals**, poné **1** aprobación requerida.
3. Marcá **Prevent approval by the author** (que no se pueda aprobar solo).

---

## Paso 4 — Marcar qué archivos son suyos

El archivo `.gitlab/CODEOWNERS` (ya está en el repositorio) dice qué partes del
código son de quién. Cuando el asistente abra un merge request que toque algo
fuera del módulo de tickets, GitLab te va a poner como revisora automáticamente.

```
# Todo el sistema es tuyo por defecto
*                                   @tu-usuario-gitlab

# El módulo de tickets lo desarrolla el asistente
/PAA/CreacionTickets/     @usuario-del-asistente
/PAA/api/tickets.php      @usuario-del-asistente
```

**Editá ese archivo y poné los nombres de usuario reales de GitLab** (los que
aparecen con `@` en el perfil de cada quien).

> **Importante, para que no te confíes de más:** en la edición gratuita de
> GitLab, CODEOWNERS **sugiere** revisores pero no bloquea nada por sí solo. Lo
> que realmente impide un cambio indebido es la combinación del paso 3
> (ramas protegidas) y que vos leas el cambio antes de aceptarlo. CODEOWNERS es
> una ayuda para que no se te pase, no una cerradura.

---

## Paso 5 — Pasarle el manual

Mandale el enlace a [`MANUAL_ASISTENTE.md`](MANUAL_ASISTENTE.md), que está en el
repositorio y cubre todo: qué instalar, cómo bajar el proyecto, cómo levantar el
sitio en su máquina, cómo trabajar y cómo entregar.

Está escrito para alguien que nunca usó Git ni PHP.

---

## Sobre el acceso al servidor

### Por qué es mejor que NO lo tenga (al menos al principio)

En el servidor están:

* La base de datos **real**, con los datos de contacto de las 19 personas del
  programa y de los 1.008 centros educativos (nombres de directores, teléfonos,
  correos).
* Los **respaldos** en `backups/`, que son copias completas de todo eso.
* El archivo `includes/config.php` con las **contraseñas** de la base.

Nada de eso le hace falta para programar el módulo de tickets. El manual le
explica cómo levantar el sitio completo en su propia máquina con sedes
inventadas. Trabaja igual de cómodo, y un error suyo no puede tocar los datos
reales ni tumbar el sitio.

### Si aun así querés dárselo

**No le pases las credenciales de `gestionpaa`.** Esa cuenta es la dueña de todo:
quien la tiene puede borrar la base, leer los respaldos y cambiar el sitio sin
que quede registro de quién fue.

Pedí un usuario aparte al Centro de Informática. Podés escribirles algo así:

> Asunto: Solicitud de usuario adicional para el hosting gestionpaa
>
> Buen día. Escribo del Programa Permanente de la PAA (Instituto de
> Investigaciones Psicológicas). Tenemos el hosting `gestionpaa`
> (`172.16.49.116`, ruta `~/public_html/web`) y necesitamos que una persona
> asistente colabore en el desarrollo de un módulo.
>
> ¿Es posible crear un usuario adicional con acceso SSH/SFTP limitado a ese
> hosting, sin permisos sobre la base de datos de producción? Si no fuera
> posible, agradecería saber qué alternativa recomiendan para dar acceso a una
> persona colaboradora.
>
> Nombre y correo institucional de la persona: [completar]
>
> Muchas gracias.
>
> [tu nombre] — [tu correo] — [tu teléfono]

Contacto: **2511-5000** · **ci5000@ucr.ac.cr**

---

## Cuando te llegue su primer merge request

1. Abrilo y andá a la pestaña **Changes**: ahí ves exactamente qué líneas cambió.
2. Revisá tres cosas:
   * ¿Tocó **solo** `CreacionTickets/`, `api/tickets.php` o una migración nueva?
     Si tocó otra cosa, preguntale por qué antes de aceptar.
   * ¿Aparece `includes/config.php` en la lista? **No debería nunca.** Si
     aparece, no lo aceptes: son las contraseñas.
   * ¿Las consultas usan `prepare()` con `?` o `:nombre`, en vez de pegar
     variables dentro del texto SQL?
3. Si algo no te cuadra, dejá un comentario en la línea misma y pedile el
   cambio. Es lo normal, no es un reproche.
4. Cuando esté bien: **Approve** y después **Merge**.

Si querés una revisión más a fondo antes de aceptar, en tu máquina podés correr:

```bash
git fetch origin
git switch <nombre-de-su-rama>
```

y probar su trabajo localmente antes de integrarlo.

---

## Resumen: qué hacer ahora

* [ ] Subir la rama `main` a GitLab (paso 1)
* [ ] Invitarlo como **Developer** con fecha de vencimiento (paso 2)
* [ ] Proteger `main` y `develop` con push **No one** (paso 3)
* [ ] Poner los usuarios reales en `.gitlab/CODEOWNERS` (paso 4)
* [ ] Pasarle `MANUAL_ASISTENTE.md` (paso 5)
* [ ] Decidir si pedís o no el usuario del servidor al Centro de Informática
