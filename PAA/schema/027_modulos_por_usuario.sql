-- ============================================================
-- Excepciones de módulos por usuario
--
-- POR QUÉ
-- Hay funcionarios que necesitan entrar a un módulo puntual que su rol no
-- trae por defecto, pero son pocos como para justificar volverlos
-- administradores (demasiado permiso) y no encajan en darle ese módulo a
-- "funcionario" en general (muy pocos lo necesitan). Antes la única unidad
-- de permiso era el rol completo; esto agrega una excepción por persona sin
-- tocar el catálogo de includes/modulos.php.
--
-- POR QUÉ SOLO LA DIFERENCIA, NO LA LISTA COMPLETA
-- Se guarda "a este usuario se le agregó/quitó tal módulo", no una copia
-- entera de sus módulos. Así, si mañana cambia qué trae el rol "funcionario"
-- en general, quien tiene una excepción puntual no se queda con una lista
-- vieja congelada — sigue heredando los cambios del rol, más su excepción.
-- ============================================================

CREATE TABLE usuario_modulos (
    usuario_id INT NOT NULL,
    modulo     VARCHAR(40) NOT NULL,
    tipo       ENUM('agregado', 'quitado') NOT NULL,
    creado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (usuario_id, modulo),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
