-- ============================================================
-- Módulo: Ingreso de Datos / Revisión — folletos de adecuación
-- mezclados dentro de un aula normal
--
-- POR QUÉ
-- Un aula normal a veces trae, dentro de sus mismos folletos, un grupo con
-- numeración de adecuación (p. ej. de sus 25 folletos, 20 son del 1 al 20 y
-- los otros 5 son AD-21 a AD-25). Eso no es lo mismo que un aula ENTERA de
-- adecuación (columna `es_adecuacion`, que ya existía): acá es un aula
-- normal con un sub-rango de otra serie adentro, y no siempre pasa.
--
-- El total de folletos del aula (`total_folletos`, el que trae la base) NO
-- se toca: este rango es información adicional. Cuántos son del rango
-- normal se calcula restando este rango del total, no reemplazando nada.
-- ============================================================

ALTER TABLE aulas
    ADD COLUMN folleto_adec_desde VARCHAR(50) NULL AFTER folleto_hasta,
    ADD COLUMN folleto_adec_hasta VARCHAR(50) NULL AFTER folleto_adec_desde;
