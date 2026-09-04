-- ============================================================
-- Condición física del artículo (bueno / regular / dañado).
--
-- No es lo mismo que `estado` (activo/baja): un equipo dañado sigue
-- existiendo en el inventario y hay que poder registrarlo así — la baja es
-- para cuando el equipo ya no está o se decide sacarlo del inventario, no
-- para "está roto pero sigue acá".
-- ============================================================

ALTER TABLE equipo_oficina
    ADD COLUMN condicion ENUM('bueno', 'regular', 'danado') NOT NULL DEFAULT 'bueno' AFTER cantidad;

ALTER TABLE activos
    ADD COLUMN condicion ENUM('bueno', 'regular', 'danado') NOT NULL DEFAULT 'bueno' AFTER nombre;
