-- ============================================================
-- Módulo: Revisión de Aulas
-- Tablas: materiales, actas_revision, acta_materiales
-- Base de datos destino: gestionpaa (MariaDB)
--
-- Origen:
--   · Lista de aulas: el Sheet de tulas (ya migrado en 004_aulas_tulas.sql)
--   · Materiales: Sheet 15ccOHrLEHLYM2rQAgJ72Eupfv4S3-_2jdm4zenlGE1k,
--     pestaña "Materiales"
--   · El acta se armaba en el navegador y se mandaba por correo con
--     RevisionAulas_AppScript.gs. NO se guardaba en ningún lado.
--
-- POR QUÉ SE GUARDA EL ACTA
-- Hoy el acta existe únicamente en el buzón de quien la recibió. Si el correo
-- se borra o el coordinador cambia de cuenta, no queda constancia de qué se
-- revisó. Acá el correo pasa a ser una copia, no el original.
-- ============================================================

CREATE TABLE materiales (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(150) NOT NULL UNIQUE,
    unidad          VARCHAR(50)  NULL,          -- "unidades", "cajas", "paquetes"
    orden           SMALLINT     NOT NULL DEFAULT 0,
    activo          TINYINT(1)   NOT NULL DEFAULT 1,
    creado_en       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE actas_revision (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    -- Folio visible en el acta. Lo genera el servidor, no el cliente: si lo
    -- generara el navegador, dos personas revisando a la vez producirían el
    -- mismo folio.
    folio               VARCHAR(30) NOT NULL UNIQUE,

    convocatoria_id     INT NOT NULL,
    sede_id             INT NOT NULL,
    -- Un acta cubre la revisión de una sede completa; el detalle por aula va
    -- en el HTML del acta y en los materiales. Si más adelante se quiere el
    -- desglose por aula, se agrega una tabla acta_aulas sin tocar esta.
    aulas_revisadas     INT NOT NULL DEFAULT 0,

    revisado_por        VARCHAR(150) NULL,
    observaciones       TEXT NULL,

    -- El cuerpo del acta tal como se envió, para poder reimprimirla idéntica.
    acta_html           MEDIUMTEXT NULL,

    -- A quién se le envió. Se guarda el correo resuelto por el servidor, no el
    -- que pidió el cliente (ver includes/correo.php).
    enviado_a           VARCHAR(150) NULL,
    enviado_en          TIMESTAMP NULL DEFAULT NULL,

    creado_en           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id),
    FOREIGN KEY (sede_id)         REFERENCES sedes(id),

    INDEX idx_actas_sede (convocatoria_id, sede_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Materiales contabilizados en cada acta.
CREATE TABLE acta_materiales (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    acta_id         INT NOT NULL,
    material_id     INT NOT NULL,
    cantidad        INT NOT NULL DEFAULT 0,
    observacion     VARCHAR(255) NULL,

    UNIQUE KEY uq_acta_material (acta_id, material_id),
    FOREIGN KEY (acta_id)     REFERENCES actas_revision(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materiales(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Datos
-- ============================================================
--     php scripts/importar_csv.php materiales ruta/al/Materiales.csv
--
-- Las actas no se importan: son el registro nuevo que empieza a llenarse
-- cuando el módulo entre en uso.
