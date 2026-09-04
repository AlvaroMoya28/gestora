-- ============================================================
-- Bitácora: qué se hizo en el sistema, no solo quién entró
-- Base de datos destino: gestionpaa (MariaDB)
--
-- POR QUÉ
-- La migración 013 creó `ingresos`, que anota quién entró y cuándo. Eso
-- responde "¿quién estuvo?", pero no "¿quién borró el avance de la sede 47?".
-- Cuando algo aparece cambiado y nadie recuerda haberlo hecho —que es el caso
-- real que motiva esto— el registro de ingresos no alcanza: dice que ocho
-- personas entraron ese día y nada más.
--
-- Esta tabla guarda las acciones que MODIFICAN datos. Las consultas no se
-- registran a propósito: serían la enorme mayoría de las filas y enterrarían
-- justo lo que se quiere encontrar.
-- ============================================================

CREATE TABLE actividad (
    -- BIGINT y no INT: son varias filas por persona y por día durante la
    -- aplicación de la prueba, y esta tabla se conserva entre convocatorias.
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,

    usuario_id      INT NULL,

    -- El nombre de usuario se copia acá, además de la llave foránea.
    --
    -- Parece redundante y no lo es: si una cuenta se elimina, la llave queda
    -- en NULL y la fila se volvería anónima — justo la fila que interesaría
    -- revisar. Una bitácora que se borra sola cuando se borra al responsable
    -- no sirve para nada.
    usuario_texto   VARCHAR(100) NULL,

    -- De qué endpoint vino: 'revision', 'activos', 'usuarios'…
    modulo          VARCHAR(50) NOT NULL,

    -- La acción tal como la despacha el endpoint: 'guardarTulas', 'eliminar'…
    accion          VARCHAR(60) NOT NULL,

    -- Sobre qué se actuó, en texto corto: "sede 401", "tula 3", "usuario juan.perez".
    detalle         VARCHAR(255) NULL,

    -- Los intentos fallidos también se guardan: alguien probando algo que no
    -- le corresponde es exactamente lo que se quiere poder ver después.
    exitoso         TINYINT(1) NOT NULL DEFAULT 1,

    ip              VARCHAR(45) NULL,   -- 45 = IPv6 con notación mixta
    ocurrido_en     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,

    -- La consulta normal es "lo último primero", con o sin filtro de módulo o
    -- de persona. Estos tres índices cubren esos caminos.
    INDEX idx_actividad_fecha (ocurrido_en),
    INDEX idx_actividad_usuario (usuario_id, ocurrido_en),
    INDEX idx_actividad_modulo (modulo, ocurrido_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Índice que le faltaba a `ingresos`
-- ------------------------------------------------------------
-- La 013 indexó solo la fecha. La pantalla de bitácora también pregunta
-- "todos los ingresos de esta persona" y "quiénes entran más", que sin esto
-- recorren la tabla entera.
ALTER TABLE ingresos
    ADD INDEX idx_ingresos_usuario (usuario_id, ocurrido_en);

-- ============================================================
-- SOBRE PODER BORRAR LA BITÁCORA
-- ============================================================
-- El módulo permite limpiar registros viejos, porque si no la tabla crece sin
-- control y la pantalla se vuelve inútil.
--
-- Pero el borrado NUNCA es silencioso: limpiar deja su propia fila en
-- `actividad` diciendo quién limpió, cuántas filas quitó y hasta qué fecha.
-- Una bitácora que se puede vaciar sin dejar rastro no es una bitácora — es
-- una lista que alguien puede dejar como quiera antes de que la revisen.
-- Por eso esa fila se escribe DESPUÉS del DELETE, para que no se borre a sí
-- misma.
