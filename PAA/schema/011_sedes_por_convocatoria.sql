-- ============================================================
-- Las sedes pasan a pertenecer a una convocatoria
-- Base de datos destino: gestionpaa (MariaDB)
--
-- POR QUÉ
-- Hasta acá `sedes` era un catálogo global con el código como identificador
-- único. Eso supone que el código 401 significa siempre el mismo lugar, y no
-- es así: las sedes de aplicación se numeran de nuevo cada año.
--
--   · En el catálogo de centros educativos, el 401 era "CINDEA SAN CARLOS".
--   · En la base de aplicación 2026, el 401 es "CTP DE PEJIBAYE".
--   · En 2027 será otro.
--
-- Con el código como clave global, importar la base de un año encima de la
-- del anterior mezclaba los datos: la sede quedaba con el nombre nuevo y el
-- teléfono y el director del centro anterior, sin ninguna señal de que eso
-- había pasado. Y no había forma de consultar "las sedes de 2025" una vez
-- cargado 2026.
--
-- Ahora la clave es convocatoria + código. Cada año trae su propia lista, las
-- de años anteriores se conservan intactas, y una tula, un ticket o un acta
-- apuntan siempre a la sede del año que les corresponde.
--
-- ⚠️ ESTA MIGRACIÓN VACÍA LAS TABLAS DE DATOS DE OPERACIÓN.
-- Es deliberado y fue pedido: se empieza de cero para cargar la base 2026
-- limpia. Lo que se borra son datos importados que se vuelven a cargar desde
-- los archivos originales. NO se toca `personas` ni `areas` (el directorio,
-- que se cargó a mano y no se recupera de ningún archivo), ni `materiales`,
-- ni `ticket_categorias`.
--
-- Antes de aplicarla, el propio scripts/migrar.php hace un respaldo.
-- ============================================================

-- ------------------------------------------------------------
-- 1. Vaciar, de adentro hacia afuera
-- ------------------------------------------------------------
-- El orden es el que exigen las llaves foráneas: primero lo que apunta a
-- otros, al final lo apuntado.

DELETE FROM ticket_comentarios;
DELETE FROM tickets;

DELETE FROM acta_materiales;
DELETE FROM actas_revision;

DELETE FROM movimientos_tula;
DELETE FROM marchamos;
DELETE FROM aulas;

DELETE FROM sobresueldos;
DELETE FROM hospedajes;
DELETE FROM asignaciones_sede;

DELETE FROM sedes;

-- Los postulantes se conservan, pero sueltan la convocatoria para poder
-- borrarla: son personas que se postularon, no datos de la aplicación.
UPDATE postulantes SET convocatoria_id = NULL;

DELETE FROM convocatorias;

-- ------------------------------------------------------------
-- 2. Cambiar la clave de `sedes`
-- ------------------------------------------------------------

ALTER TABLE sedes
    ADD COLUMN convocatoria_id INT NOT NULL AFTER id;

-- El índice único del código se llama `codigo`: MySQL nombra así el que se
-- declara con UNIQUE en la propia columna.
ALTER TABLE sedes
    DROP INDEX codigo;

ALTER TABLE sedes
    ADD UNIQUE KEY uq_sede_convocatoria (convocatoria_id, codigo);

ALTER TABLE sedes
    ADD CONSTRAINT fk_sedes_convocatoria
        FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id);

-- Buscar "la sede 401 de la convocatoria activa" es la consulta más frecuente
-- del sistema: la hacen tulas, marchamos, revisión de aulas y tickets.
CREATE INDEX idx_sedes_codigo ON sedes (codigo);

-- ------------------------------------------------------------
-- 3. Cómo se carga a partir de ahora
-- ------------------------------------------------------------
--   php scripts/importar_csv.php sedes archivo.csv --convocatoria="PAA 2026"
--   php scripts/importar_csv.php aulas archivo.csv --convocatoria="PAA 2026"
--
-- El año siguiente se repite con --convocatoria="PAA 2027" y las de 2026
-- quedan donde están, consultables con ?convocatoria_id=N.
