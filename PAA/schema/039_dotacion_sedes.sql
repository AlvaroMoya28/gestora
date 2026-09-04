-- ============================================================
-- Dotación de puestos y datos completos del coordinador, por sede
--
-- ORIGEN
-- El "otro sistema" (el mismo padrón que ya alimenta codigo_externo,
-- turno, aulas_previstas, etc. desde schema/017_importacion.sql) tiene un
-- reporte de sedes más completo que el JSON que se importa cada año: trae
-- cuántos puestos de aplicador/apoyo hay, cuántos ya están nombrados y
-- cédula/universidad del coordinador. Antes solo se veía en ese otro
-- sistema; ahora se puede cargar acá para no tener que ir a buscarlo cada
-- vez que alguien pregunta si una sede ya tiene coordinador completo.
--
-- POR QUÉ SE GUARDA Y NO SE CALCULA
-- Los conteos de puestos (nombrados, ocupados, libres) los calcula el otro
-- sistema con reglas que no están acá (m.g. quién cuenta como "una
-- persona", qué pasa con suplencias). Guardarlos tal cual como llegan es
-- más honesto que tratar de recalcularlos con datos que no tenemos.
--
-- POR QUÉ "estado_dotacion" Y NO "estado"
-- La sede ya tiene su propio estado binario (`activo`). Este es un estado
-- de progreso de nombramiento ("Sin iniciar", "Pendiente", "Completo"...)
-- que no tiene nada que ver con si la sede se usa o no.
-- ============================================================

ALTER TABLE sedes
    ADD COLUMN total_puestos     SMALLINT     NOT NULL DEFAULT 0 AFTER apoyo_requerido,
    ADD COLUMN minimo_una        SMALLINT     NOT NULL DEFAULT 0 AFTER total_puestos,
    ADD COLUMN aula_nombrados    SMALLINT     NOT NULL DEFAULT 0 AFTER minimo_una,
    ADD COLUMN apoyo_nombrados   SMALLINT     NOT NULL DEFAULT 0 AFTER aula_nombrados,
    ADD COLUMN puestos_ocupados  SMALLINT     NOT NULL DEFAULT 0 AFTER apoyo_nombrados,
    ADD COLUMN puestos_libres    SMALLINT     NOT NULL DEFAULT 0 AFTER puestos_ocupados,
    ADD COLUMN una_nombrados     SMALLINT     NOT NULL DEFAULT 0 AFTER puestos_libres,
    ADD COLUMN una_faltan        SMALLINT     NOT NULL DEFAULT 0 AFTER una_nombrados,
    ADD COLUMN estado_dotacion   VARCHAR(50)  NULL AFTER una_faltan,
    ADD COLUMN coordinador_cedula      VARCHAR(50)  NULL AFTER coordinador_correo,
    ADD COLUMN coordinador_universidad VARCHAR(150) NULL AFTER coordinador_cedula;
