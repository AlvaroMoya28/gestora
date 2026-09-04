-- ============================================================
-- Módulo: Sobresueldos
-- Tablas: sobresueldos, hospedajes
-- Base de datos destino: gestionpaa (MariaDB)
--
-- Origen: tres Google Sheets distintos que SobreSueldos.html cruzaba en el
-- navegador:
--   · 1y8ledjEgvovukaSi81Vy3pPmrUb_nt2Jelyrig4pXwY  "Hoja 1"  → presupuesto por sede
--   · 1mOILkUx_Guhvg086i-t1MSHZKLhc3l7UtyC3FtoQ8DQ            → coordinadores
--   · 1G_jrXKdI8KrfjANZxf86pwatBgMWs_PS-c61vGG_h_g            → hospedaje
--
-- El de coordinadores no se migra: es el mismo personal que ya está en
-- `personas` (001 + 003). Ese cruce ahora lo hace un JOIN, no el navegador.
--
-- Columnas D..O del Sheet principal, que SobreSueldos_AppScript.gs escribía en
-- bloque con getRange(fila, 4, 1, 12):
--   D COORD_REGULAR  E COORD_ADECUACION  F APLICADOR  G APOYO  H UNA  I UCR
--   J SERVICIO_CONSERJERIA  K DIA1  L DIA2  M DIA3  N PRESUPUESTO  O DESGLOSE
--
-- Nota del Apps Script que hay que preservar: solo UCR se presupuesta en
-- sobresueldos; UNA va aparte y conserjería es un servicio separado.
-- ============================================================

CREATE TABLE sobresueldos (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    convocatoria_id         INT NOT NULL,
    sede_id                 INT NOT NULL,

    descripcion             VARCHAR(255) NULL,
    aulas                   INT NOT NULL DEFAULT 0,

    -- Personal asignado. Se guardan como texto porque en el Sheet no siempre
    -- es un número: hay celdas con anotaciones ("2 + 1 adecuación").
    coord_regular           VARCHAR(150) NULL,
    coord_adecuacion        VARCHAR(150) NULL,
    aplicador               VARCHAR(50)  NULL,
    apoyo                   VARCHAR(50)  NULL,
    una                     VARCHAR(50)  NULL,   -- no se presupuesta
    ucr                     VARCHAR(50)  NULL,   -- sí se presupuesta
    servicio_conserjeria    VARCHAR(50)  NULL,   -- servicio aparte

    dia1                    VARCHAR(50) NULL,
    dia2                    VARCHAR(50) NULL,
    dia3                    VARCHAR(50) NULL,

    presupuesto             INT NOT NULL DEFAULT 0,
    desglose                TEXT NULL,

    actualizado_en          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    creado_en               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Una sola fila de presupuesto por sede y convocatoria. En el Sheet la
    -- fila se identificaba por su número (rowIndex), que se corría solo con
    -- que alguien insertara una fila arriba — y entonces se guardaba el
    -- presupuesto en la sede equivocada.
    UNIQUE KEY uq_sobresueldo (convocatoria_id, sede_id),

    FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id),
    FOREIGN KEY (sede_id)         REFERENCES sedes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE hospedajes (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    convocatoria_id     INT NOT NULL,
    sede_id             INT NOT NULL,
    persona_id          INT NULL,
    persona_nombre      VARCHAR(150) NULL,
    lugar               VARCHAR(200) NULL,
    noches              SMALLINT NOT NULL DEFAULT 0,
    monto               DECIMAL(12,2) NOT NULL DEFAULT 0,
    observacion         TEXT NULL,
    creado_en           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id),
    FOREIGN KEY (sede_id)         REFERENCES sedes(id),
    FOREIGN KEY (persona_id)      REFERENCES personas(id),

    INDEX idx_hospedaje_sede (convocatoria_id, sede_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Datos
-- ============================================================
--     php scripts/importar_csv.php sobresueldos ruta/al/SobreSueldos.csv
--     php scripts/importar_csv.php hospedajes   ruta/al/Hospedaje.csv
