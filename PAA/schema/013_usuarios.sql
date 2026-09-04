-- ============================================================
-- Autenticación: usuarios, roles y contraseñas
-- Base de datos destino: gestionpaa (MariaDB)
--
-- POR QUÉ
-- Hasta ahora el sistema no tenía ningún control de acceso: cualquiera que
-- alcanzara la dirección veía y modificaba todo. Era la deuda técnica más
-- grande heredada del sistema viejo, donde la única protección era la
-- contraseña de WordPress al entrar al sitio.
--
-- TRES ROLES
--   funcionario   → solo el módulo de tickets, para reportar averías
--   administrador → el centro de herramientas completo
--   desarrollador → todo, y además NO aparece en el directorio ni en los
--                   selectores del sistema: es una cuenta de trabajo, no una
--                   persona del programa
--
-- SOBRE LAS CONTRASEÑAS
-- No se guardan nunca en claro. Se usa password_hash() de PHP con bcrypt, que
-- genera un **salt aleatorio distinto para cada contraseña** y lo guarda
-- dentro del propio hash. Por eso la columna es de 255 caracteres y no de la
-- longitud de un hash "pelado": el valor incluye algoritmo, costo y salt.
--
-- Eso es lo que hay que hacer, y es mejor que hashear dos veces por cuenta
-- propia: encadenar hashes a mano no agrega seguridad real y en algunos casos
-- la reduce. Lo que protege de verdad contra un robo de la base es que bcrypt
-- es deliberadamente lento y que dos personas con la misma contraseña tengan
-- hashes distintos, y las dos cosas ya las hace.
-- ============================================================

CREATE TABLE usuarios (
    id                  INT AUTO_INCREMENT PRIMARY KEY,

    -- Cada usuario es una persona del directorio. Si alguien se va, se
    -- desactiva la cuenta pero se conserva: los tickets que reportó siguen
    -- apuntando a ella.
    persona_id          INT NOT NULL UNIQUE,

    -- Lo que se escribe al entrar: el correo institucional hasta la arroba.
    -- "karenalejandra.calvo@ucr.ac.cr" → "karenalejandra.calvo"
    usuario             VARCHAR(100) NOT NULL UNIQUE,

    -- bcrypt: 60 caracteres hoy, pero PHP puede cambiar de algoritmo por
    -- defecto y los nuevos son más largos. 255 deja margen para eso.
    contrasena_hash     VARCHAR(255) NOT NULL,

    rol                 ENUM('funcionario','administrador','desarrollador')
                        NOT NULL DEFAULT 'funcionario',

    -- La primera vez que entra, el sistema le obliga a poner una contraseña
    -- propia. Todos arrancan con la misma clave inicial, así que hasta que la
    -- cambien cualquiera que la sepa podría entrar como ellos.
    debe_cambiar_contrasena TINYINT(1) NOT NULL DEFAULT 1,

    activo              TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_ingreso      TIMESTAMP NULL DEFAULT NULL,

    -- Para frenar la fuerza bruta sin bloquear a nadie de forma permanente.
    intentos_fallidos   SMALLINT NOT NULL DEFAULT 0,
    bloqueado_hasta     TIMESTAMP NULL DEFAULT NULL,

    creado_en           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (persona_id) REFERENCES personas(id),
    INDEX idx_usuarios_rol (rol, activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Personas que no deben verse en el sistema
-- ------------------------------------------------------------
-- La cuenta de desarrollo necesita existir como persona (para poder tener
-- usuario y para que los registros que cree apunten a alguien), pero no es
-- personal del programa: no va en el directorio ni en los selectores de
-- coordinador, de préstamo de equipo o de asignación de sedes.
ALTER TABLE personas
    ADD COLUMN oculto TINYINT(1) NOT NULL DEFAULT 0 AFTER activo;

-- ------------------------------------------------------------
-- Bitácora de ingresos
-- ------------------------------------------------------------
-- Sin esto, si algo se borra o se cambia no hay forma de saber quién entró ni
-- cuándo. Es lo mínimo para poder revisar después.
CREATE TABLE ingresos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT NULL,
    -- Se guarda también lo que se escribió, para ver los intentos con
    -- usuarios que no existen.
    usuario_texto   VARCHAR(100) NULL,
    exitoso         TINYINT(1) NOT NULL,
    ip              VARCHAR(45) NULL,
    ocurrido_en     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_ingresos_fecha (ocurrido_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Cómo se crean las cuentas
-- ============================================================
-- Los hashes no se pueden generar desde SQL, así que las cuentas se crean con:
--
--     php scripts/crear_usuarios.php
--
-- Ese script recorre `personas`, arma el usuario a partir del correo y les
-- pone la contraseña inicial con debe_cambiar_contrasena = 1.
