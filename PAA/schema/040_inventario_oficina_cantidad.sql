-- ============================================================
-- Inventario de Oficina: cantidad por código.
--
-- Hasta ahora cada fila de equipo_oficina era una cosa individual con
-- código único. Pero hay artículos que se compran/entregan en cantidad y no
-- necesitan identificarse uno por uno (cables, mouses, teclados, folletos,
-- cajas de discos...): todos comparten el mismo código de barras. En vez de
-- crear una fila por unidad, se agrega `cantidad` y una misma fila absorbe
-- las entradas siguientes del mismo código (ver api/inventario_oficina.php,
-- accion=sumarCantidad).
-- ============================================================

ALTER TABLE equipo_oficina
    ADD COLUMN cantidad INT NOT NULL DEFAULT 1 AFTER nombre;

-- La ubicación de todo lo que entra acá es, por definición, la oficina de
-- informática (es "Inventario de Oficina"): se deja de pedir en el
-- formulario y se completa sola. Se normaliza lo que ya había en blanco.
UPDATE equipo_oficina
SET ubicacion = 'Oficina de Informática'
WHERE ubicacion IS NULL OR TRIM(ubicacion) = '';
