-- ============================================================
-- Repara `estudiantes`: la identidad es el número de cómputo
-- Base de datos destino: gestionpaa (MariaDB)
--
-- POR QUÉ EXISTE ESTA MIGRACIÓN Y NO SE CORRIGIÓ LA 018
-- La 018 se escribió suponiendo que el número de fórmula identificaba a cada
-- estudiante. El archivo real de 2026 lo desmintió: FORMULA tiene diez valores
-- —del 1 al 10, la versión del cuadernillo— para 56 737 personas. Quien
-- identifica es el número de cómputo.
--
-- La 018 se corrigió en su propio archivo, pero eso no alcanza: `migrar.php`
-- lleva la cuenta por NOMBRE DE ARCHIVO, así que en cualquier base donde la
-- 018 ya se hubiera aplicado, la versión corregida nunca vuelve a correr. La
-- tabla se queda con el índice único sobre la columna equivocada y sin las dos
-- columnas nuevas, y la carga del padrón falla con un error de columna
-- desconocida.
--
-- Ese fue el error de método: una migración aplicada no se edita, se encadena.
--
-- ESTA MIGRACIÓN ES IDEMPOTENTE
-- Deja la tabla en la forma correcta tanto si se aplicó la 018 vieja como si se
-- aplicó la corregida. En el segundo caso no cambia nada. Por eso usa
-- `IF NOT EXISTS` / `IF EXISTS`, que MariaDB acepta en ALTER TABLE.
--
-- NO BORRA NADA. Si ya hubiera estudiantes cargados con el esquema viejo, lo
-- que había ahí eran diez filas en vez de la población entera (el índice único
-- sobre la fórmula colapsaba todo), así que conviene volver a subir el padrón
-- después de aplicarla.
-- ============================================================

-- ------------------------------------------------------------
-- 1. Las columnas que faltaban
-- ------------------------------------------------------------
-- La fecha tal como venía, con la hora pegada («03/10/2026  8:00AM»), y el
-- nombre de la sede con el formato del otro sistema («1401M CTP DE PEJIBAYE -
-- PÉREZ ZELEDÓN», que no es como lo escribe `sedes.nombre`). Las dos se
-- exportan de vuelta y las dos cambian cuando alguien se traslada.
ALTER TABLE estudiantes
    ADD COLUMN IF NOT EXISTS fecha_texto       VARCHAR(40)  NULL AFTER fecha_examen,
    ADD COLUMN IF NOT EXISTS sede_nombre_texto VARCHAR(200) NULL AFTER fecha_texto;

-- ------------------------------------------------------------
-- 2. Quién identifica a quién
-- ------------------------------------------------------------
-- El índice único tiene que soltar la fórmula ANTES de que la columna pueda
-- volverse anulable, y la identificación tiene que ser obligatoria antes de
-- poder sostener el índice nuevo. De ahí el orden.
ALTER TABLE estudiantes
    DROP INDEX IF EXISTS uq_estudiante;

ALTER TABLE estudiantes
    MODIFY COLUMN identificacion VARCHAR(40) NOT NULL,
    MODIFY COLUMN formula        VARCHAR(60) NULL;

ALTER TABLE estudiantes
    ADD UNIQUE KEY uq_estudiante (convocatoria_id, identificacion);
