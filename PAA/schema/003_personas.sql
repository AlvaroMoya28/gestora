-- ============================================================
-- Renombra `coordinadores` a `personas` (y sus teléfonos)
-- Base de datos destino: gestionpaa (MariaDB)
--
-- POR QUÉ
-- El módulo 1 creó la tabla `coordinadores` porque era lo único que había.
-- Al entrar los módulos siguientes queda claro que es el catálogo de personal
-- del programa, no solo de coordinadores:
--
--   · Activos tiene una hoja "Personal" (Área, Persona, Correo, Teléfono 1,
--     Teléfono 2) que es exactamente la misma estructura, con las mismas
--     personas repetidas.
--   · Tulas y Revisión de Aulas necesitan resolver el correo del coordinador
--     de una sede contra un catálogo confiable.
--
-- Duplicar la tabla para Activos sería repetir el problema que venimos a
-- resolver (los mismos nombres y correos en varios lugares, desincronizados).
-- Renombrarla es más barato ahora que dentro de tres módulos: hoy solo la usa
-- api/coordinadores.php.
--
-- RENAME TABLE conserva los datos, los índices y las llaves foráneas: las 19
-- personas y sus 22 teléfonos siguen intactos después de esto.
--
-- La página Coordinadores_Directorio/ y su endpoint siguen llamándose igual
-- (es el nombre que la gente del PAA usa para esa herramienta); lo que cambia
-- es la tabla que hay debajo.
-- ============================================================

RENAME TABLE coordinadores TO personas;
RENAME TABLE coordinador_telefonos TO persona_telefonos;

-- La columna de la llave foránea también arrastraba el nombre viejo.
ALTER TABLE persona_telefonos
    CHANGE COLUMN coordinador_id persona_id INT NOT NULL;

-- ------------------------------------------------------------
-- Campos que el catálogo necesita para servir también a Activos
-- ------------------------------------------------------------
ALTER TABLE personas
    -- La hoja "Personal" de Activos guarda dos teléfonos por persona igual que
    -- el directorio, así que esos siguen en persona_telefonos. Lo que no
    -- existía era distinguir para qué módulo se dio de alta a alguien: el
    -- directorio muestra a todo el personal, pero el selector de préstamos de
    -- Activos solo debería ofrecer a quien puede retirar equipo.
    ADD COLUMN puede_retirar_activos TINYINT(1) NOT NULL DEFAULT 1 AFTER activo;

-- El correo pasa a ser único: es la llave con la que los módulos cruzan
-- personas entre sí (el Sheet de tulas trae "correo coordinador" suelto).
-- Se permiten varios NULL, que es lo que hace falta para el personal sin correo.
ALTER TABLE personas
    ADD UNIQUE KEY uq_personas_correo (correo);
