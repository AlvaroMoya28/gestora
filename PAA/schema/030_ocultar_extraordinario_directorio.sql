-- ============================================================
-- Personal Extraordinario no pertenece al directorio de Funcionarios PAA
--
-- POR QUÉ
-- El directorio ("Funcionarios PAA") es para contactar a alguien puntual:
-- nombre, correo, extensión. Personal Extraordinario es una cuenta
-- COMPARTIDA entre varias personas físicas (ver el encabezado de
-- schema/016_rol_extraordinario.sql) — no hay "alguien puntual" a quién
-- llamar ahí, así que no tiene sentido que aparezca.
--
-- api/usuarios.php ya oculta esto para altas y cambios de rol nuevos
-- (ver crear() y cambiar_rol()); esta es la corrección de una sola vez
-- para lo que ya estaba dado de alta antes de ese cambio.
-- ============================================================

UPDATE personas p
INNER JOIN usuarios u ON u.persona_id = p.id
SET p.oculto = 1
WHERE u.rol = 'extraordinario' AND p.oculto = 0;
