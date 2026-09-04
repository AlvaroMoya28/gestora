-- ============================================================
-- Folletos sueltos por aula: números que no entran en el rango
-- consecutivo (`folleto_desde`..`folleto_hasta`).
--
-- POR QUÉ
-- El rango consecutivo asume que los folletos de un aula vienen todos
-- seguidos. Eso deja de ser cierto cuando una sede ya está completa (rango
-- ya armado) y hay que sumarle un estudiante más a una aula: el folleto que
-- cubre a esa persona sale de otra caja, con un número que no sigue la
-- secuencia — no tiene sentido correr todo el rango de la sede por uno solo.
--
-- Se guarda como texto libre (códigos separados por coma) y no como filas
-- aparte: son pocos por aula y de una sola vez, no algo que se administre
-- con su propio CRUD.
-- ============================================================

ALTER TABLE aulas
    ADD COLUMN folletos_sueltos TEXT NULL AFTER folleto_adec_hasta;
