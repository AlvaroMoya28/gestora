-- ============================================================
-- Módulo: Ingreso de Datos / Revisión — folletos de adecuación
-- mezclados en el rango del coordinador
--
-- POR QUÉ
-- Lo mismo que pasa en un aula (ver schema/022_folletos_adecuacion_mezclados.sql)
-- le pasa al coordinador: de sus folletos, algunos pueden traer numeración de
-- adecuación en vez de seguir el consecutivo normal.
--
-- El total de folletos del coordinador (`total_folletos_coord`, el que trae
-- la base en `aulas`) no se toca: este rango es información adicional que se
-- resta del total para saber cuántos son del rango normal.
-- ============================================================

ALTER TABLE sedes
    ADD COLUMN coord_folleto_adec_desde VARCHAR(50) NULL AFTER coord_folleto_hasta,
    ADD COLUMN coord_folleto_adec_hasta VARCHAR(50) NULL AFTER coord_folleto_adec_desde;
