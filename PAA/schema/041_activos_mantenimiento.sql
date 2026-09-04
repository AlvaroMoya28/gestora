-- ============================================================
-- Mantenimiento para Activos de Préstamo.
--
-- El Inventario de Oficina ya avisaba de mantenimiento cada 6 meses para
-- computadoras y laptops, pero sólo para lo que vive en `equipo_oficina`.
-- Ahora que ese módulo también muestra lo que hay en `activos` (laptops,
-- tablets que se prestan), hace falta el mismo control acá: quién lo
-- requiere y cuándo fue la última vez, con su propio historial — igual que
-- `equipo_oficina` / `equipo_oficina_mantenimientos`.
-- ============================================================

ALTER TABLE activos
    ADD COLUMN requiere_mantenimiento TINYINT(1) NOT NULL DEFAULT 0 AFTER disponible,
    ADD COLUMN ultimo_mantenimiento   DATE NULL AFTER requiere_mantenimiento;

CREATE TABLE activos_mantenimientos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    activo_id       INT NOT NULL,
    fecha           DATE NOT NULL,
    realizado_por   VARCHAR(150) NULL,
    observaciones   TEXT NULL,
    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (activo_id) REFERENCES activos(id) ON DELETE CASCADE,
    INDEX idx_activos_mant_activo (activo_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
