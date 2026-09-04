-- ============================================================
-- Horarios: el horario semanal recurrente de cada funcionario o
-- administrativo (de lunes a viernes, por franjas de hora con su
-- modalidad presencial/virtual). No se ata a una fecha concreta —es la
-- semana típica de esa persona—, así que al consultar "esta semana" o
-- "la del 15 de setiembre" se lee el mismo horario recurrente y se le
-- superponen encima las situaciones especiales (schema 035) que caigan
-- justo en esos días.
-- ============================================================

CREATE TABLE horarios_franjas (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT NOT NULL,

    dia_semana      ENUM('lunes', 'martes', 'miercoles', 'jueves', 'viernes') NOT NULL,
    hora_inicio     TIME NOT NULL,
    hora_fin        TIME NOT NULL,
    modalidad       ENUM('presencial', 'virtual') NOT NULL,

    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    INDEX idx_horarios_usuario_dia (usuario_id, dia_semana)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
