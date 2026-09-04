-- ============================================================
-- Materiales de adecuación por aula
--
-- POR QUÉ UN INTERRUPTOR EN LA SEDE ADEMÁS DE LA TABLA
-- No toda sede con aulas de adecuación necesita equipo aparte (calculadora
-- parlante, atril, etc.) — depende de qué pida el coordinador para esa
-- convocatoria en particular. Por eso no se infiere solo de que existan
-- aulas marcadas `es_adecuacion`: alguien del personal lo activa a
-- propósito para esa sede, y así la tabla no aparece donde nadie la va a
-- llenar.
--
-- POR QUÉ POR AULA Y NO UNA SOLA FILA PARA TODA LA SEDE
-- Una sede puede tener más de un aula de adecuación, y cada una puede
-- necesitar un juego de equipo distinto (una calculadora parlante para una,
-- una tableta para otra). Una fila por aula evita mezclar el equipo de una
-- con el de la otra.
--
-- POR QUÉ TODO VARCHAR Y NO INT
-- Aunque casi todo es una cantidad, el personal a veces necesita anotar algo
-- que no es un número («no cabe», «se presta del aula 3», un código de
-- inventario). Forzar INT le quitaría esa salida.
-- ============================================================

ALTER TABLE sedes
    ADD COLUMN materiales_adecuacion TINYINT(1) NOT NULL DEFAULT 0 AFTER coord_sin_folletos;

CREATE TABLE aula_materiales_adecuacion (
    aula_id         INT NOT NULL PRIMARY KEY,
    sede_id         INT NOT NULL,
    convocatoria_id INT NOT NULL,

    calc_simple     VARCHAR(80) NULL,
    calc_parlante   VARCHAR(80) NULL,
    atril           VARCHAR(80) NULL,
    mesa            VARCHAR(80) NULL,
    pizarras        VARCHAR(80) NULL,
    audifonos       VARCHAR(80) NULL,
    tableta         VARCHAR(80) NULL,
    computadora     VARCHAR(80) NULL,
    diccionario     VARCHAR(80) NULL,
    lampara         VARCHAR(80) NULL,

    actualizado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (aula_id) REFERENCES aulas(id),
    FOREIGN KEY (sede_id) REFERENCES sedes(id),
    FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id),
    INDEX idx_materiales_sede (sede_id, convocatoria_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
