-- ============================================================
-- Chat de soporte entre cada funcionario (o Personal Extraordinario) y
-- administración
--
-- POR QUÉ
-- El personal extraordinario y los funcionarios trabajan fuera de la oficina
-- el día de la aplicación y hoy solo pueden avisar de un problema por
-- WhatsApp o llamando, sin que quede registro de qué se preguntó ni quién
-- contestó.
--
-- NO ES UN CANAL ÚNICO. Cada persona con cuenta propia tiene su propia
-- conversación con "administración" en conjunto: cualquier administrador o
-- desarrollador puede contestarle, y del otro lado se ve igual sin importar
-- cuál de ellos escribió. La conversación se identifica por el `usuario_id`
-- de quien NO administra — así que del lado del funcionario no hay que
-- elegir con quién habla, siempre es la misma.
--
-- `chat_visto` guarda, por persona y por conversación, hasta qué momento ya
-- se leyó. Un administrador tiene una fila por cada conversación que abrió;
-- un funcionario o Personal Extraordinario, una sola (la suya).
-- ============================================================

CREATE TABLE chat_mensajes (
    id               INT AUTO_INCREMENT PRIMARY KEY,

    -- El usuario_id de la persona NO administradora dueña de la conversación
    -- (el funcionario, o la cuenta compartida de Personal Extraordinario).
    -- Todos los mensajes de esa conversación —los suyos y las respuestas de
    -- cualquier administrador— comparten este mismo valor.
    conversacion_id  INT NOT NULL,

    -- Quién escribió ESTE mensaje en particular.
    usuario_id       INT NULL,

    -- Si quien escribió es del lado de administración. Se guarda aparte del
    -- rol de `usuario_id` porque los roles pueden cambiar con el tiempo, y
    -- este dato es del momento en que se mandó el mensaje.
    es_admin         TINYINT(1) NOT NULL DEFAULT 0,

    -- Nombre ya resuelto para mostrar (con el nombre que la persona escribió
    -- en cuentas compartidas, ver usuario_texto_con_quien() en
    -- includes/bitacora.php). No se recalcula después.
    autor            VARCHAR(100) NOT NULL,

    mensaje          TEXT NOT NULL,
    creado_en        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (conversacion_id) REFERENCES usuarios(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    INDEX idx_chat_conversacion (conversacion_id, creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE chat_visto (
    usuario_id       INT NOT NULL,
    conversacion_id  INT NOT NULL,
    visto_en         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (usuario_id, conversacion_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (conversacion_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
