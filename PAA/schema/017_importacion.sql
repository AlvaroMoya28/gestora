-- ============================================================
-- Importación anual desde el sistema de coordinaciones
-- Base de datos destino: gestionpaa (MariaDB)
--
-- QUÉ RESUELVE
-- Cada año cambian las sedes, los coordinadores y el padrón de gente que
-- aplica la prueba. Hasta ahora eso entraba a mano: un CSV para las sedes, un
-- .xls para los coordinadores, y lo demás no entraba del todo. El otro sistema
-- exporta un JSON con todo junto, así que conviene tener un solo camino de
-- entrada y que quede registro de cada carga.
--
-- LO QUE EL JSON TRAE Y LA BASE NO GUARDABA
--   · el padrón completo de personas aplicadoras (875 en 2026), con qué
--     pruebas aprobó cada una
--   · quién quedó nombrado en cada puesto de cada sede
--   · el turno y la cantidad de apoyo que requiere cada sede
--
-- POR QUÉ UN PADRÓN APARTE Y NO EN `personas`
-- `personas` es el directorio del personal del PAA: es lo que sale en
-- Funcionarios PAA y en los selectores de préstamo de equipo. El padrón son
-- cientos de personas de la UCR y la UNA contratadas para un fin de semana.
-- Mezclarlos volvería inútil el directorio.
-- ============================================================

-- ------------------------------------------------------------
-- Datos de sede que venían del otro sistema
-- ------------------------------------------------------------
-- El coordinador ya se guardaba por aula (así venía del Sheet original) y eso
-- se conserva para no romper el acta. Pero es un dato DE LA SEDE, y hay sedes
-- que todavía no tienen aulas cargadas: sin estas columnas, el coordinador de
-- esas sedes no tendría dónde guardarse.
ALTER TABLE sedes
    ADD COLUMN codigo_externo      INT          NULL AFTER codigo,
    ADD COLUMN turno               VARCHAR(50)  NULL AFTER nombre,
    ADD COLUMN numero_aplicacion   VARCHAR(10)  NULL AFTER turno,
    ADD COLUMN fecha_examen        DATE         NULL AFTER numero_aplicacion,
    ADD COLUMN aulas_previstas     SMALLINT     NOT NULL DEFAULT 0 AFTER fecha_examen,
    ADD COLUMN apoyo_requerido     SMALLINT     NOT NULL DEFAULT 0 AFTER aulas_previstas,
    ADD COLUMN coordinador_nombre  VARCHAR(150) NULL AFTER apoyo_requerido,
    ADD COLUMN coordinador_correo  VARCHAR(150) NULL AFTER coordinador_nombre;

-- El id del otro sistema se guarda para poder reconocer una sede aunque le
-- cambien el código de un año a otro. No es único por sí solo: los códigos se
-- reciclan entre convocatorias (ver schema/011).
ALTER TABLE sedes
    ADD INDEX idx_sedes_externo (convocatoria_id, codigo_externo);

-- ------------------------------------------------------------
-- Padrón de personas que aplican la prueba
-- ------------------------------------------------------------
CREATE TABLE padron_personas (
    id                  INT AUTO_INCREMENT PRIMARY KEY,

    -- El correo es la identidad en el otro sistema: no hay cédula (viene
    -- vacía en todas las filas) y los nombres se repiten.
    correo              VARCHAR(150) NOT NULL UNIQUE,
    nombre              VARCHAR(150) NOT NULL,
    cedula              VARCHAR(30)  NULL,
    universidad         VARCHAR(20)  NULL,   -- UCR / UNA

    -- Qué pruebas de habilitación pasó. Se guardan las tres por separado
    -- porque alguien puede estar habilitado para aula y no para coordinar.
    aprobo_coordinacion TINYINT(1) NOT NULL DEFAULT 0,
    aprobo_apoyo        TINYINT(1) NOT NULL DEFAULT 0,
    aprobo_aula         TINYINT(1) NOT NULL DEFAULT 0,

    -- Lo que el otro sistema dice que está haciendo hoy. Es informativo: la
    -- verdad de quién quedó dónde está en `asignaciones_aplicadores`.
    rol_actual          VARCHAR(40)  NULL,

    actualizado_en      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_padron_nombre (nombre),
    INDEX idx_padron_universidad (universidad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Quién quedó nombrado en cada puesto
-- ------------------------------------------------------------
CREATE TABLE asignaciones_aplicadores (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    convocatoria_id INT NOT NULL,
    sede_id         INT NOT NULL,
    padron_id       INT NOT NULL,

    tipo            ENUM('aula','apoyo') NOT NULL,
    numero_puesto   SMALLINT NOT NULL,

    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Un puesto es uno solo: la sede 1081 no puede tener dos «aula 3».
    UNIQUE KEY uq_puesto (convocatoria_id, sede_id, tipo, numero_puesto),

    FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id),
    FOREIGN KEY (sede_id)   REFERENCES sedes(id) ON DELETE CASCADE,
    FOREIGN KEY (padron_id) REFERENCES padron_personas(id),

    INDEX idx_asig_sede (sede_id),
    INDEX idx_asig_persona (padron_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Bitácora de importaciones
-- ------------------------------------------------------------
-- Sin esto, cuando un dato aparezca raro no habría forma de saber de qué
-- archivo salió ni cuándo entró. Se guarda el resumen que el propio archivo
-- declara, para poder contrastarlo después contra lo que quedó en la base.
CREATE TABLE importaciones (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    convocatoria_id INT NOT NULL,
    archivo         VARCHAR(255) NOT NULL,
    generado_en     VARCHAR(40)  NULL,   -- lo que dice el archivo, como venga

    sedes_nuevas        SMALLINT NOT NULL DEFAULT 0,
    sedes_actualizadas  SMALLINT NOT NULL DEFAULT 0,
    aulas_creadas       SMALLINT NOT NULL DEFAULT 0,
    padron_nuevas       SMALLINT NOT NULL DEFAULT 0,
    padron_actualizadas SMALLINT NOT NULL DEFAULT 0,
    asignaciones        SMALLINT NOT NULL DEFAULT 0,

    resumen_archivo TEXT NULL,           -- el bloque "resumen" del JSON
    importado_por   VARCHAR(100) NOT NULL,
    importado_en    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id),
    INDEX idx_importaciones_fecha (importado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SOBRE BORRAR UN AÑO
-- ============================================================
-- El módulo permite eliminar una convocatoria completa. No se hace con
-- ON DELETE CASCADE desde `convocatorias` a propósito: un borrado en cascada
-- silencioso sobre datos de años anteriores es demasiado fácil de disparar sin
-- querer. El borrado va por pasos explícitos en api/importacion.php, en orden
-- de dependencia, y exige escribir el nombre de la convocatoria.
--
-- Tampoco se puede borrar la convocatoria activa: primero hay que activar otra.
-- Si no, el sistema entero se quedaría sin punto de referencia y todos los
-- módulos empezarían a responder «no hay ninguna convocatoria activa».
