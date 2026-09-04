-- ============================================================
-- Situaciones Especiales: cada funcionario, administrador o
-- desarrollador registra sus propias ausencias/cambios de horario
-- (vacaciones, incapacidad, reunión, cambio de horario, trabajo
-- remoto, cita médica, permiso, otro), con quién es tomado de su
-- sesión —no se escribe a mano— y un rango de fechas para que cubra
-- tanto un día suelto como varios seguidos.
--
-- No se ata a una convocatoria: es agenda de personas, no del proceso
-- de aplicación, así que sigue teniendo sentido consultarla aunque
-- cambie el año de la prueba.
-- ============================================================

CREATE TABLE situaciones_especiales (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT NOT NULL,
    nombre          VARCHAR(150) NOT NULL,   -- snapshot: quién es, sin depender de un JOIN para listar

    tipo            ENUM(
                        'vacaciones', 'incapacidad', 'reunion', 'cambio_horario',
                        'trabajo_remoto', 'cita_medica', 'permiso', 'otro'
                    ) NOT NULL,
    descripcion     TEXT NULL,

    fecha_inicio    DATE NOT NULL,
    fecha_fin       DATE NULL,   -- NULL = un solo día (fecha_inicio)

    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    INDEX idx_situaciones_fecha (fecha_inicio, fecha_fin),
    INDEX idx_situaciones_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
