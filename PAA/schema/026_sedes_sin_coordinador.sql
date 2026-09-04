-- ============================================================
-- Sedes sin folletos de coordinador
--
-- POR QUÉ
-- El paso 3 (folletos del coordinador) se daba por completo únicamente
-- cuando `coord_folleto_desde` tenía algo. Pero hay sedes donde el
-- coordinador sencillamente NO recibe folletos aparte — no es un dato que
-- falte, es que no existe. Esas sedes se quedaban en "proceso" para
-- siempre, aunque ya tuvieran todo lo demás (aulas, tulas, marchamos)
-- completo, porque no había forma de distinguir "no lo han llenado" de
-- "no hay nada que llenar".
--
-- Esta columna guarda esa segunda situación de forma explícita: la marca
-- alguien a propósito (ver guardar_coordinador() en api/ingreso_datos.php),
-- no se infiere.
-- ============================================================

ALTER TABLE sedes
    ADD COLUMN coord_sin_folletos TINYINT(1) NOT NULL DEFAULT 0 AFTER coord_folleto_adec_hasta;

-- Arreglo de una sola vez para lo que ya estaba capturado: las sedes que a
-- hoy tienen aulas, tulas y marchamos completos y SOLO les falta el
-- folleto del coordinador quedan marcadas como "no tiene" automáticamente,
-- para que salgan como completadas sin que alguien tenga que reabrir cada
-- una a mano. De acá en adelante la marca la pone quien captura, no esta
-- consulta.
UPDATE sedes s
SET coord_sin_folletos = 1
WHERE (s.coord_folleto_desde IS NULL OR s.coord_folleto_desde = '')
  AND EXISTS (
      SELECT 1 FROM aulas a WHERE a.sede_id = s.id AND a.convocatoria_id = s.convocatoria_id
  )
  AND NOT EXISTS (
      SELECT 1 FROM aulas a
      WHERE a.sede_id = s.id AND a.convocatoria_id = s.convocatoria_id
        AND (a.folleto_desde IS NULL OR a.folleto_desde = '')
  )
  AND EXISTS (
      SELECT 1 FROM tulas t WHERE t.sede_id = s.id AND t.convocatoria_id = s.convocatoria_id
  )
  AND (
      SELECT COUNT(*) FROM marchamos m
      INNER JOIN tulas t ON t.id = m.tula_id
      WHERE t.sede_id = s.id AND t.convocatoria_id = s.convocatoria_id
  ) >= 2 * (
      SELECT COUNT(*) FROM tulas t WHERE t.sede_id = s.id AND t.convocatoria_id = s.convocatoria_id
  );
