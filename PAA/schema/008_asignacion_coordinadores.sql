-- ============================================================
-- Módulo: Asignación de Coordinadores
-- Tablas: postulantes, asignaciones_sede
-- Base de datos destino: gestionpaa (MariaDB)
--
-- Origen: este módulo NO leía ningún Google Sheet. La usuaria subía dos
-- archivos XLSX (postulantes y sedes) desde la página, se procesaban en el
-- navegador con SheetJS y el resultado se exportaba a otro XLSX.
--
-- POR QUÉ MIGRARLO IGUAL
-- Al vivir todo en el navegador, la asignación se perdía al cerrar la pestaña:
-- no quedaba registro de con qué criterio se asignó a quién, ni forma de
-- retomar el trabajo al día siguiente. Las sedes, además, se volvían a subir
-- en un archivo cuando ya están en la base (002_sedes.sql).
--
-- El algoritmo de afinidad (GAM, provincia/cantón, grado académico, años de
-- experiencia, balanceo de carga) se conserva tal cual está en el JS: es la
-- lógica que la usuaria ya validó. Lo que cambia es de dónde salen los datos
-- y dónde queda el resultado.
-- ============================================================

CREATE TABLE postulantes (
    id                      INT AUTO_INCREMENT PRIMARY KEY,

    nombre                  VARCHAR(200) NOT NULL,
    identificacion          VARCHAR(50)  NULL UNIQUE,
    correo                  VARCHAR(150) NULL,
    telefono                VARCHAR(50)  NULL,
    telefono_oficina        VARCHAR(50)  NULL,

    -- Residencia. El XLSX traía una sola cadena "provincia, cantón, distrito"
    -- que el JS partía en cada render (parseResidenceParts).
    provincia               VARCHAR(100) NULL,
    canton                  VARCHAR(100) NULL,
    distrito                VARCHAR(100) NULL,
    direccion               TEXT NULL,
    -- Si reside en la Gran Área Metropolitana. Lo calcula el importador con la
    -- misma tabla GAM_CANTONES que tenía el JS, y se guarda porque es un
    -- criterio de asignación: recalcularlo en cada consulta sería repetir
    -- trabajo sobre un dato que no cambia.
    en_gam                  TINYINT(1) NOT NULL DEFAULT 0,

    grado_academico         VARCHAR(100) NULL,
    lugar_trabajo           VARCHAR(200) NULL,
    tipo_nombramiento       VARCHAR(100) NULL,
    anios_experiencia       SMALLINT NULL,

    -- Respuestas del formulario que el algoritmo usa como filtro duro
    disponible              TINYINT(1) NOT NULL DEFAULT 1,
    tiene_vehiculo          TINYINT(1) NULL,
    acepta_viajar           TINYINT(1) NULL,
    preferencia_colaboracion VARCHAR(100) NULL,

    -- Elegibilidad calculada (isGradoElegible en el JS)
    elegible                TINYINT(1) NOT NULL DEFAULT 1,

    convocatoria_id         INT NULL,
    creado_en               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id),
    INDEX idx_postulantes_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE asignaciones_sede (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    convocatoria_id     INT NOT NULL,
    sede_id             INT NOT NULL,

    -- El asignado puede ser alguien del personal ya registrado (`personas`) o
    -- un postulante externo. Se permite cualquiera de los dos, nunca ambos.
    postulante_id       INT NULL,
    persona_id          INT NULL,

    puntaje             DECIMAL(8,3) NULL,      -- score de afinidad calculado
    criterio            VARCHAR(100) NULL,      -- prioridad usada al calcular
    -- Distingue lo que puso el algoritmo de lo que la usuaria cambió a mano.
    -- Sin esto, recalcular la asignación pisaría los ajustes manuales.
    manual              TINYINT(1) NOT NULL DEFAULT 0,
    observacion         TEXT NULL,

    creado_en           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Una sede tiene un coordinador por convocatoria.
    UNIQUE KEY uq_asignacion_sede (convocatoria_id, sede_id),
    CONSTRAINT ck_asignado_uno CHECK (
        (postulante_id IS NULL) <> (persona_id IS NULL)
        OR (postulante_id IS NULL AND persona_id IS NULL)
    ),

    FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id),
    FOREIGN KEY (sede_id)         REFERENCES sedes(id),
    FOREIGN KEY (postulante_id)   REFERENCES postulantes(id),
    FOREIGN KEY (persona_id)      REFERENCES personas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Datos
-- ============================================================
-- Los postulantes se cargan del mismo XLSX que hoy se sube a la página,
-- exportado a CSV:
--
--     php scripts/importar_csv.php postulantes ruta/al/postulantes.csv
--
-- Las sedes ya no se suben: salen de la tabla `sedes`.
