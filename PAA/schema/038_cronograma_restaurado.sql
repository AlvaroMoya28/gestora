-- ============================================================
-- Cronograma vuelve como módulo aparte (036 lo había fusionado en
-- Situaciones Especiales como "anuncios por fecha" y dropeó esta misma
-- tabla). Se recrea igual que en 034_cronograma.sql: la migración 034 ya
-- quedó marcada como aplicada, así que no se vuelve a ejecutar sola.
-- ============================================================

CREATE TABLE hitos_proceso (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    convocatoria_id INT NOT NULL,

    titulo          VARCHAR(150) NOT NULL,
    descripcion     TEXT NULL,
    fecha_inicio    DATE NOT NULL,
    fecha_fin       DATE NULL,   -- NULL = hito de un solo día (fecha_inicio)
    color           VARCHAR(20) NOT NULL DEFAULT 'azul',

    creado_por      VARCHAR(150) NULL,
    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id),
    INDEX idx_hitos_convocatoria_fecha (convocatoria_id, fecha_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
