-- ============================================================
-- Inventario de Oficina: el equipo fijo de la oficina del PAA
-- (computadoras, laptops, periféricos, cables...), a diferencia de
-- "Activos de Préstamo" (tabla `activos`), que es lo que se presta a
-- las sedes el día de la aplicación y vuelve. Son dos inventarios
-- distintos a propósito: mezclarlos habría significado agregarle a la
-- tabla `activos` columnas que no tienen sentido para lo que se presta
-- (mantenimiento periódico, responsable fijo) y viceversa.
-- ============================================================

CREATE TABLE equipo_oficina (
    id                      INT AUTO_INCREMENT PRIMARY KEY,

    -- El que se escanea o se teclea, como `activos.codigo`.
    codigo                  VARCHAR(50)  NOT NULL UNIQUE,
    tipo                    VARCHAR(60)  NOT NULL,   -- Computadora, Laptop, Teclado, Mouse, Impresora... (texto libre)
    nombre                  VARCHAR(200) NOT NULL,
    marca                   VARCHAR(100) NULL,
    modelo                  VARCHAR(100) NULL,
    serie                   VARCHAR(100) NULL,
    ubicacion               VARCHAR(150) NULL,       -- dónde está físicamente
    responsable             VARCHAR(150) NULL,       -- a quién está asignado (texto libre, no FK)
    observaciones           TEXT NULL,

    -- Mantenimiento cada 6 meses, pensado para computadoras y laptops. Se
    -- marca solo según el tipo al crear/importar, pero queda editable por si
    -- algún día hay otro tipo de equipo que también lo necesite.
    requiere_mantenimiento  TINYINT(1) NOT NULL DEFAULT 0,
    ultimo_mantenimiento    DATE NULL,

    -- Baja: no se borra la fila (perdería el historial de mantenimientos y el
    -- rastro de que existió), se marca como dada de baja y desaparece de la
    -- lista normal.
    estado                  ENUM('activo', 'baja') NOT NULL DEFAULT 'activo',
    motivo_baja             VARCHAR(255) NULL,
    baja_en                 TIMESTAMP NULL,

    creado_por              VARCHAR(150) NULL,
    creado_en               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_equipo_oficina_estado (estado),
    INDEX idx_equipo_oficina_tipo (tipo),
    INDEX idx_equipo_oficina_mantenimiento (requiere_mantenimiento, ultimo_mantenimiento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Historial de mantenimientos. Separado de `equipo_oficina.ultimo_mantenimiento`
-- (que es un caché de la fecha más reciente, para no recalcular el historial
-- completo cada vez que se lista el inventario) igual que `ticket_comentarios`
-- lleva el historial de un ticket aparte de su estado actual.
CREATE TABLE equipo_oficina_mantenimientos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    equipo_id       INT NOT NULL,
    fecha           DATE NOT NULL,
    realizado_por   VARCHAR(150) NULL,
    observaciones   TEXT NULL,
    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (equipo_id) REFERENCES equipo_oficina(id) ON DELETE CASCADE,
    INDEX idx_equipo_oficina_mant_equipo (equipo_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
