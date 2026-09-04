-- ============================================================
-- Módulos: Tulas, Asignación de Marchamos, Revisión de Aulas, Administración
-- Tablas núcleo: convocatorias, aulas, marchamos, movimientos_tula
-- Base de datos destino: gestionpaa (MariaDB)
--
-- Origen: Google Sheet 1KEcA3znFL3SEkZD7j4OK7sN3FcrNpDSuZbjaWHw5IOs (gid=0).
-- Columnas del Sheet:
--   SEDE, NOMBRE, CONVOCATORIA, FECHA, NUMERO_AULA_EXAMEN, FORMULA, ASIGNADOS,
--   Desde, Hasta, grupo_embalaje, desde_coord, hasta_coord, "Personas por aula",
--   total, Cordinador, Turno, "TOTAL CAJAS", "TOTAL AULAS",
--   "TOTAL FOLLETOS COORD.", "TOTAL FOLLETOS", Adecuaciones, estado,
--   codigo_barras, MARCHAMO1, MARCHAMO2, "correo coordinador"
--
-- POR QUÉ UNA SOLA TABLA PARA CUATRO MÓDULOS
-- Hoy los cuatro leen ese mismo Sheet y cada uno lo interpreta a su manera:
-- Tulas mira `estado` y `codigo_barras`, Marchamos escribe MARCHAMO1/2,
-- Revisión de Aulas busca `correo coordinador`, y Administración cuenta filas.
-- Es una sola entidad —el aula de examen de una sede en una convocatoria—
-- y acá se modela como tal. Los módulos dejan de ser dueños de "su" copia.
-- ============================================================

-- ------------------------------------------------------------
-- Convocatoria: la aplicación de la prueba en una fecha dada.
-- En el Sheet era la columna CONVOCATORIA repetida en cada fila.
-- ------------------------------------------------------------
CREATE TABLE convocatorias (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    nombre              VARCHAR(100) NOT NULL UNIQUE,
    fecha_aplicacion    DATE         NULL,
    -- Solo una convocatoria está "activa" a la vez: es la que abren por
    -- defecto las páginas de Tulas y Marchamos el día de la aplicación.
    activa              TINYINT(1)   NOT NULL DEFAULT 0,
    creado_en           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Aula de examen (la "tula" es el bulto de material que le corresponde).
-- ------------------------------------------------------------
CREATE TABLE aulas (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    convocatoria_id         INT NOT NULL,
    sede_id                 INT NOT NULL,
    numero_aula             VARCHAR(20)  NOT NULL,

    -- Datos de embalaje y folletos, tal como venían del Sheet
    formula                 VARCHAR(50)  NULL,
    asignados               INT          NOT NULL DEFAULT 0,
    folleto_desde           VARCHAR(50)  NULL,
    folleto_hasta           VARCHAR(50)  NULL,
    grupo_embalaje          VARCHAR(50)  NULL,
    coord_desde             VARCHAR(50)  NULL,
    coord_hasta             VARCHAR(50)  NULL,
    personas_por_aula       VARCHAR(50)  NULL,
    total_folletos          INT          NOT NULL DEFAULT 0,
    total_folletos_coord    VARCHAR(50)  NULL,
    total_cajas             VARCHAR(50)  NULL,
    total_aulas             VARCHAR(50)  NULL,
    adecuaciones            VARCHAR(100) NULL,
    turno                   VARCHAR(50)  NULL,

    -- El Sheet traía el nombre y el correo del coordinador escritos a mano en
    -- cada fila. Se conservan como texto (así se puede importar sin depender
    -- de que la persona ya exista) pero se agrega el enlace a `personas`, que
    -- es de donde el sistema nuevo debe resolver el correo al enviar algo.
    coordinador_nombre      VARCHAR(150) NULL,
    coordinador_correo      VARCHAR(150) NULL,
    persona_id              INT          NULL,

    -- Identificador que se escanea con la pistola de código de barras
    codigo_barras           VARCHAR(60)  NULL,

    -- Lista blanca de estados, la misma que validaba el Apps Script.
    -- Como ENUM, el motor rechaza cualquier otro valor: la validación deja de
    -- depender de que el código se acuerde de comprobarla.
    estado                  ENUM('disponible','prestado','devuelto')
                            NOT NULL DEFAULT 'disponible',

    creado_en               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Una sede no puede tener dos veces la misma aula en la misma convocatoria.
    -- En el Sheet esto se rompía con solo copiar una fila.
    UNIQUE KEY uq_aula (convocatoria_id, sede_id, numero_aula),
    -- El código de barras debe ser único dentro de la convocatoria: es lo que
    -- se escanea para prestar o devolver, y un duplicado haría ambiguo el escaneo.
    UNIQUE KEY uq_codigo_barras (convocatoria_id, codigo_barras),

    FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id),
    FOREIGN KEY (sede_id)         REFERENCES sedes(id),
    FOREIGN KEY (persona_id)      REFERENCES personas(id),

    INDEX idx_aulas_estado (convocatoria_id, estado),
    INDEX idx_aulas_sede   (sede_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Marchamos (bridas) que sellan cada tula.
-- ------------------------------------------------------------
-- En el Sheet eran dos columnas, MARCHAMO1 y MARCHAMO2, y el Apps Script
-- tenía que recorrer toda la hoja para detectar que un mismo código no
-- estuviera puesto en dos aulas (funciones marchamosUsados_/choqueDeMarchamo_).
-- Acá esa regla la impone el índice único: el motor rechaza el duplicado sin
-- que nadie tenga que recordarlo.
CREATE TABLE marchamos (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    aula_id             INT NOT NULL,
    -- Desnormalizado a propósito: es lo que permite exigir que el código sea
    -- único dentro de la convocatoria con un solo índice.
    convocatoria_id     INT NOT NULL,
    posicion            TINYINT NOT NULL,          -- 1 = MARCHAMO1, 2 = MARCHAMO2
    codigo              VARCHAR(50) NOT NULL,
    asignado_en         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_marchamo_aula   (aula_id, posicion),
    UNIQUE KEY uq_marchamo_codigo (convocatoria_id, codigo),
    CONSTRAINT ck_marchamo_posicion CHECK (posicion IN (1, 2)),

    FOREIGN KEY (aula_id)         REFERENCES aulas(id) ON DELETE CASCADE,
    FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Bitácora de salidas y entradas de tulas.
-- ------------------------------------------------------------
-- El Sheet solo guardaba el estado actual: al devolver una tula se perdía
-- para siempre el registro de cuándo salió y quién la recibió. Con esta tabla
-- `aulas.estado` pasa a ser un resumen de la última fila de acá.
CREATE TABLE movimientos_tula (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    aula_id             INT NOT NULL,
    -- Qué se hizo: sacar la tula de bodega o recibirla de vuelta.
    tipo                ENUM('salida','entrada') NOT NULL,
    estado_resultante   ENUM('disponible','prestado','devuelto') NOT NULL,
    -- Quién la entregó/recibió. Texto libre porque en el mostrador no siempre
    -- es alguien que esté en `personas`.
    responsable         VARCHAR(150) NULL,
    observacion         TEXT NULL,
    registrado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE CASCADE,
    INDEX idx_mov_aula (aula_id, registrado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Comprobantes enviados por correo (Tulas y Revisión de Aulas)
-- ------------------------------------------------------------
-- Reemplaza los contadores diarios que el Apps Script guardaba en
-- PropertiesService para no pasarse de la cuota de Gmail. En PHP la cuota deja
-- de aplicar, pero el registro sigue siendo útil: deja constancia de a quién
-- se le envió qué y permite mantener un tope diario contra el abuso.
CREATE TABLE correos_enviados (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    modulo              VARCHAR(50)  NOT NULL,     -- 'tulas', 'revision_aulas', 'activos'
    referencia          VARCHAR(100) NULL,         -- folio, boleta o número de sede
    destinatario        VARCHAR(150) NOT NULL,
    asunto              VARCHAR(255) NULL,
    exitoso             TINYINT(1)   NOT NULL DEFAULT 1,
    error               VARCHAR(255) NULL,
    enviado_en          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_correos_dia (modulo, enviado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Datos
-- ============================================================
-- El contenido del Sheet se carga con el importador, que crea la convocatoria
-- y las sedes que falten:
--
--     php scripts/importar_csv.php aulas ruta/al/tulas.csv
--
-- Ver scripts/importar_csv.php para el detalle de las columnas esperadas.
