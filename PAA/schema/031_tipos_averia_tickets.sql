-- ============================================================
-- Reduce el "tipo de avería" de tickets a las 4 categorías reales que se
-- usan: Avería de equipo, Problema de red, Equipo de cómputo, Otro.
--
-- Las demás (Infraestructura, Electricidad, Mobiliario, Material de prueba,
-- Limpieza, Seguridad) se desactivan en vez de borrarse: los tickets ya
-- reportados con esas categorías las necesitan por la llave foránea
-- categoria_id, y desactivarlas (activo = 0) ya las saca del selector sin
-- tocar el historial — es el mismo filtro que usa catalogos() en
-- api/tickets.php.
-- ============================================================

UPDATE ticket_categorias
SET activo = 0
WHERE nombre IN ('Infraestructura', 'Electricidad', 'Mobiliario', 'Material de prueba', 'Limpieza', 'Seguridad');

INSERT INTO ticket_categorias (nombre, icono, orden)
SELECT 'Avería de equipo', 'fa-triangle-exclamation', 1
WHERE NOT EXISTS (SELECT 1 FROM ticket_categorias WHERE nombre = 'Avería de equipo');

INSERT INTO ticket_categorias (nombre, icono, orden)
SELECT 'Problema de red', 'fa-wifi', 2
WHERE NOT EXISTS (SELECT 1 FROM ticket_categorias WHERE nombre = 'Problema de red');

UPDATE ticket_categorias SET orden = 3,  activo = 1 WHERE nombre = 'Equipo de cómputo';
UPDATE ticket_categorias SET orden = 99, activo = 1 WHERE nombre = 'Otro';
