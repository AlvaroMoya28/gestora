-- ============================================================
-- Ajustes a `sedes` después de ver los datos reales del Sheet
-- Base de datos destino: gestionpaa (MariaDB)
--
-- POR QUÉ
-- La migración 002 se escribió a partir de los nombres de columna que usaba
-- Sedes.html. Al importar el Sheet completo (1.008 centros educativos)
-- aparecieron dos cosas que ese código no dejaba ver:
--
-- 1. Los campos de contacto no son un dato limpio, son notas escritas a mano.
--    El teléfono más largo tiene 65 caracteres y dice:
--        "2730 5522 // Marcos director 8714 8116 // 8786 8721 Rosa Maria //"
--    Es decir: varios números, con el nombre de cada quién. Lo mismo pasa con
--    el correo (113 caracteres, tres direcciones con aclaraciones) y con
--    director. VARCHAR(50) era una suposición razonable sobre un teléfono, no
--    sobre esto. La importación entera abortaba con "Data too long".
--
--    No se parten en varias filas como se hizo con los teléfonos de
--    `personas`, porque acá el texto trae información que se perdería al
--    partirlo (quién es cada número). Se guardan tal cual y se limpian el día
--    que alguien pueda revisar las 1.008 filas.
--
-- 2. El Sheet trae Provincia, Cantón y Distrito, que ninguna página mostraba
--    y por eso no estaban en el diseño. Son útiles: el algoritmo de Asignación
--    de Coordinadores decide por cercanía entre la residencia de la persona y
--    la ubicación de la sede, y hasta ahora esa ubicación tenía que adivinarla
--    del texto de la dirección (funciones inferProvinciaByText y
--    determineGamByText del JS).
-- ============================================================

ALTER TABLE sedes
    MODIFY COLUMN telefono          VARCHAR(255) NULL,
    MODIFY COLUMN correo            VARCHAR(255) NULL,
    MODIFY COLUMN director          VARCHAR(255) NULL,
    MODIFY COLUMN contacto_conserje VARCHAR(255) NULL;

ALTER TABLE sedes
    ADD COLUMN provincia VARCHAR(100) NULL AFTER direccion,
    ADD COLUMN canton    VARCHAR(100) NULL AFTER provincia,
    ADD COLUMN distrito  VARCHAR(100) NULL AFTER canton,
    -- Se calcula al importar con la misma lista de cantones que usa el JS del
    -- módulo de asignación, para no repetir ese cálculo en cada consulta.
    ADD COLUMN en_gam    TINYINT(1) NOT NULL DEFAULT 0 AFTER distrito;

CREATE INDEX idx_sedes_ubicacion ON sedes (provincia, canton);
