-- ============================================================
-- La tula pasa a ser una entidad propia
-- Base de datos destino: gestionpaa (MariaDB)
--
-- POR QUÉ
-- Hasta acá el modelo suponía que una tula era una fila de `aulas`: los
-- marchamos colgaban del aula y el estado de préstamo también. Eso no es lo
-- que pasa en la bodega.
--
-- La TULA es el bulto que se arma y se sella. El personal decide cuántas
-- hacer y qué aulas mete en cada una: la tula 1 puede llevar las aulas 1 y 2,
-- la tula 2 el aula 3, y la última los folletos del coordinador. El par de
-- marchamos se le pone al BULTO, no a cada aula que va adentro, y lo que sale
-- y entra de la bodega es el bulto entero.
--
-- Con el modelo anterior no había forma de decir "estas dos aulas viajan
-- juntas": cada una llevaba su propio par de marchamos, que en la práctica
-- eran el mismo número repetido, y devolver una sin la otra era posible
-- aunque físicamente fueran el mismo saco.
-- ============================================================

-- ------------------------------------------------------------
-- 1. El bulto
-- ------------------------------------------------------------
CREATE TABLE tulas (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    convocatoria_id     INT NOT NULL,
    sede_id             INT NOT NULL,

    -- Número dentro de la sede: "tula 1 de 4". Es lo que se escribe en la
    -- etiqueta y lo que la gente dice en voz alta.
    numero              SMALLINT NOT NULL,

    -- La última tula de una sede suele llevar los folletos del coordinador,
    -- que no pertenecen a ningún aula.
    lleva_folletos_coord TINYINT(1) NOT NULL DEFAULT 0,

    -- El estado se mueve acá desde `aulas`: lo que sale y entra es el bulto.
    estado              ENUM('disponible','prestado','devuelto')
                        NOT NULL DEFAULT 'disponible',

    -- Se escanea para prestar y devolver. Puede quedar vacío hasta que se
    -- imprima la etiqueta.
    codigo_barras       VARCHAR(60) NULL,

    observacion         TEXT NULL,
    creado_en           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_tula_sede (convocatoria_id, sede_id, numero),
    UNIQUE KEY uq_tula_codigo (convocatoria_id, codigo_barras),

    FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id),
    FOREIGN KEY (sede_id)         REFERENCES sedes(id),

    INDEX idx_tulas_estado (convocatoria_id, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 2. Qué aulas van en cada bulto
-- ------------------------------------------------------------
CREATE TABLE tula_aulas (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    tula_id     INT NOT NULL,
    aula_id     INT NOT NULL,
    orden       SMALLINT NOT NULL DEFAULT 0,

    -- Un aula va en una sola tula: si estuviera en dos, no se sabría cuál
    -- bulto abrir para llegar a ella.
    UNIQUE KEY uq_aula_en_tula (aula_id),

    FOREIGN KEY (tula_id) REFERENCES tulas(id) ON DELETE CASCADE,
    FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE CASCADE,
    INDEX idx_tula_aulas (tula_id, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 3. Los marchamos pasan a colgar de la tula
-- ------------------------------------------------------------
-- Se rehacen las tablas en vez de convertirlas con ALTER: los marchamos que
-- había venían de la importación del Sheet viejo, donde el mismo par se
-- repetía en cada aula del bulto, así que convertirlos daría duplicados. Se
-- vuelven a asignar desde la pantalla, que es donde se escanean.
--
-- Recrear también evita depender de los nombres que MySQL le pone solo a las
-- llaves foráneas (marchamos_ibfk_1 y compañía), que cambian según el orden
-- en que se declararon y harían fallar la migración en otra copia de la base.
DROP TABLE IF EXISTS marchamos;

CREATE TABLE marchamos (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    tula_id             INT NOT NULL,
    convocatoria_id     INT NOT NULL,
    posicion            TINYINT NOT NULL,          -- 1 = salida, 2 = retorno
    codigo              VARCHAR(50) NOT NULL,
    asignado_en         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_marchamo_tula   (tula_id, posicion),
    -- Un código no puede estar en dos bultos de la misma convocatoria.
    UNIQUE KEY uq_marchamo_codigo (convocatoria_id, codigo),
    CONSTRAINT ck_marchamo_posicion CHECK (posicion IN (1, 2)),

    FOREIGN KEY (tula_id)         REFERENCES tulas(id) ON DELETE CASCADE,
    FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 4. Movimientos: también por bulto
-- ------------------------------------------------------------
DROP TABLE IF EXISTS movimientos_tula;

CREATE TABLE movimientos_tula (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    tula_id             INT NOT NULL,
    tipo                ENUM('salida','entrada') NOT NULL,
    estado_resultante   ENUM('disponible','prestado','devuelto') NOT NULL,
    responsable         VARCHAR(150) NULL,
    observacion         TEXT NULL,
    registrado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (tula_id) REFERENCES tulas(id) ON DELETE CASCADE,
    INDEX idx_mov_tula (tula_id, registrado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 5. Aulas de adecuación
-- ------------------------------------------------------------
-- El rango de folletos se define aparte para las aulas de adecuación, así que
-- hay que poder distinguirlas. Antes solo estaba la columna `adecuaciones`
-- con texto libre venido del Sheet.
ALTER TABLE aulas
    ADD COLUMN es_adecuacion TINYINT(1) NOT NULL DEFAULT 0 AFTER adecuaciones;

-- Las que traían algo escrito en `adecuaciones` se marcan como tales.
UPDATE aulas
SET es_adecuacion = 1
WHERE adecuaciones IS NOT NULL
  AND TRIM(adecuaciones) <> ''
  AND LOWER(TRIM(adecuaciones)) NOT IN ('no', '0', 'ninguna', 'n/a');

-- ------------------------------------------------------------
-- 6. Folletos del coordinador: son de la sede, no del aula
-- ------------------------------------------------------------
-- En el Sheet estaban repetidos en cada fila de aula (desde_coord,
-- hasta_coord), lo que permitía que dos aulas de la misma sede dijeran cosas
-- distintas sobre el mismo dato.
ALTER TABLE sedes
    ADD COLUMN coord_folleto_desde VARCHAR(50) NULL AFTER en_gam,
    ADD COLUMN coord_folleto_hasta VARCHAR(50) NULL AFTER coord_folleto_desde;

-- Se toma lo que ya venía en las aulas, si había algo.
UPDATE sedes s
INNER JOIN (
    SELECT sede_id,
           MAX(coord_desde) AS desde,
           MAX(coord_hasta) AS hasta
    FROM aulas
    WHERE coord_desde IS NOT NULL OR coord_hasta IS NOT NULL
    GROUP BY sede_id
) a ON a.sede_id = s.id
SET s.coord_folleto_desde = a.desde,
    s.coord_folleto_hasta = a.hasta;

-- ------------------------------------------------------------
-- 7. Registro de la revisión de una sede
-- ------------------------------------------------------------
-- Marca en qué punto del proceso va cada sede, para poder retomarlo y para
-- saber qué falta sin tener que abrir cada pantalla.
CREATE TABLE revisiones_sede (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    convocatoria_id     INT NOT NULL,
    sede_id             INT NOT NULL,

    -- Hasta dónde llegó: 1 sede, 2 folletos por aula, 3 folletos del
    -- coordinador, 4 armado de tulas, 5 marchamos.
    paso                TINYINT NOT NULL DEFAULT 1,
    completada          TINYINT(1) NOT NULL DEFAULT 0,

    revisado_por        VARCHAR(150) NULL,
    persona_id          INT NULL,
    observaciones       TEXT NULL,

    creado_en           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_revision_sede (convocatoria_id, sede_id),
    FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id),
    FOREIGN KEY (sede_id)         REFERENCES sedes(id),
    FOREIGN KEY (persona_id)      REFERENCES personas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
