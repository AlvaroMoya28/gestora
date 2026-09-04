-- ============================================================
-- El módulo Cronograma se retira: quedaba muy parecido a Situaciones
-- Especiales y confundía cuál de los dos consultar. Sus "hitos del
-- proceso" pasan a vivir en Situaciones Especiales como "anuncios por
-- fecha" — mismo calendario, un solo lugar.
-- ============================================================

DROP TABLE IF EXISTS hitos_proceso;

-- Anuncios por fecha: igual que un hito del cronograma, pero visto por
-- cualquiera con acceso al módulo (funcionario, administrador,
-- desarrollador) y editable solo por administración y desarrollo. No
-- cuelga de un usuario, así que va aparte de situaciones_especiales en
-- vez de forzar un usuario_id que no le corresponde.
CREATE TABLE situaciones_anuncios (
    id              INT AUTO_INCREMENT PRIMARY KEY,

    titulo          VARCHAR(150) NOT NULL,
    descripcion     TEXT NULL,
    fecha_inicio    DATE NOT NULL,
    fecha_fin       DATE NULL,   -- NULL = un solo día (fecha_inicio)

    creado_por      VARCHAR(150) NULL,
    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_situaciones_anuncios_fecha (fecha_inicio, fecha_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
