-- ============================================================
-- Observaciones de una sede, durante el embalaje
--
-- POR QUÉ NO ES UN CAMPO DE TEXTO EN `sedes`
-- Una observación se agrega en cualquier paso (folletos, coordinador, tulas,
-- marchamos) y tiene que sumarse a las anteriores, no reemplazarlas: si
-- alguien anota algo al armar los folletos y otra persona anota algo más al
-- armar las tulas, las dos tienen que quedar. Un solo campo de texto obligaría
-- a leer lo que ya había para no borrarlo por accidente. Con una fila por
-- observación no hay ese riesgo, y de paso queda quién la escribió y cuándo.
--
-- Vive por sede + convocatoria, igual que el resto del embalaje: se lee y se
-- agrega desde Ingreso de Datos (en cualquier paso, siempre visible) y se
-- imprime en el acta de Revisión, antes del espacio de observaciones a mano.
-- ============================================================

CREATE TABLE sede_observaciones (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    sede_id         INT NOT NULL,
    convocatoria_id INT NOT NULL,
    quien           VARCHAR(80) NOT NULL,
    texto           VARCHAR(500) NOT NULL,
    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (sede_id) REFERENCES sedes(id),
    FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id),
    INDEX idx_observaciones_sede (sede_id, convocatoria_id, creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
