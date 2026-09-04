-- ============================================================
-- Módulo: Creación de Tickets — ajuste de prioridad
--
-- La prioridad deja de pedirse al reportar la avería: la fija el
-- administrador después, según qué tan urgente sea. Mientras nadie la
-- asigne, el ticket queda "sin prioridad" en vez de heredar un valor por
-- defecto que nadie decidió.
-- ============================================================

ALTER TABLE tickets
    MODIFY prioridad ENUM('baja','media','alta','critica') NULL DEFAULT NULL;
