-- ============================================================
-- Permiso para autorizar la edición de datos base en Ingreso de Datos
-- (cantidad de aulas, estudiantes por aula, fórmula, tipo).
--
-- No se usa el rol para esto: administración y desarrollo ya lo tienen
-- automáticamente (ver autoriza_datos_base() en includes/sesion.php), pero
-- hay personas puntuales que necesitan poder autorizar este cambio concreto
-- sin volverse administradoras de todo el sistema. Carlos Solera Aguilar es
-- el primer caso.
-- ============================================================

ALTER TABLE usuarios
    ADD COLUMN autoriza_datos_base TINYINT(1) NOT NULL DEFAULT 0 AFTER rol;

UPDATE usuarios u
INNER JOIN personas p ON p.id = u.persona_id
SET u.autoriza_datos_base = 1
WHERE p.correo = 'carlos.solera@ucr.ac.cr';

-- Para dar el permiso a alguien más adelante, sin esperar otra migración:
--
--     UPDATE usuarios u INNER JOIN personas p ON p.id = u.persona_id
--     SET u.autoriza_datos_base = 1
--     WHERE p.correo = 'correo.de.la.persona@ucr.ac.cr';
