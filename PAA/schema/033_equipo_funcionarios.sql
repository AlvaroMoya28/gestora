-- ============================================================
-- Equipo de Funcionarios: qué computadora, teclado, UPS, etc. tiene
-- asignado cada funcionario del PAA, a diferencia de "Inventario de
-- Oficina" (tabla `equipo_oficina`), que es el equipo fijo del edificio
-- sin dueño particular. Aquí cada fila de equipo cuelga de una persona
-- concreta (`personas`, la misma tabla del directorio), porque el
-- objetivo es poder responder "¿qué tiene Fulano?" y "¿cuándo se revisó
-- por última vez lo de Fulano?", no solo "¿qué hay en la oficina?".
-- ============================================================

CREATE TABLE equipo_funcionario (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    persona_id          INT NOT NULL,

    tipo                VARCHAR(60)  NOT NULL,   -- Computadora, Laptop, Teclado, Mouse, UPS, Monitor... (texto libre)
    nombre              VARCHAR(200) NOT NULL,
    marca               VARCHAR(100) NULL,
    modelo              VARCHAR(100) NULL,
    serie               VARCHAR(100) NULL,
    observaciones       TEXT NULL,

    fecha_asignacion    DATE NULL,

    -- Igual que en Inventario de Oficina: no se borra la fila al quitarle
    -- el equipo a alguien, se marca retirado, para no perder de quién fue
    -- ni desde cuándo, y para que el historial (`equipo_funcionario_historial`)
    -- siga apuntando a algo que existe.
    estado              ENUM('activo', 'retirado') NOT NULL DEFAULT 'activo',
    motivo_retiro       VARCHAR(255) NULL,
    retirado_en         TIMESTAMP NULL,

    creado_por          VARCHAR(150) NULL,
    creado_en           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (persona_id) REFERENCES personas(id),
    INDEX idx_equipo_funcionario_persona (persona_id, estado),
    INDEX idx_equipo_funcionario_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bitácora por funcionario: cada alta, edición o retiro de un equipo, y
-- cada sondeo, queda como una fila aquí. Es lo que se le muestra a
-- administración como "historial" al abrir a una persona: no solo el
-- estado actual, sino todo lo que le ha pasado a su equipo con el tiempo.
CREATE TABLE equipo_funcionario_historial (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    persona_id      INT NOT NULL,
    equipo_id       INT NULL,   -- NULL cuando el evento es del funcionario en general (p.ej. un sondeo), no de un equipo puntual

    tipo_evento     ENUM('equipo_agregado', 'equipo_editado', 'equipo_retirado', 'equipo_reactivado', 'sondeo') NOT NULL,
    descripcion     TEXT NOT NULL,
    realizado_por   VARCHAR(150) NULL,
    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (persona_id) REFERENCES personas(id),
    FOREIGN KEY (equipo_id) REFERENCES equipo_funcionario(id) ON DELETE SET NULL,
    INDEX idx_equipo_funcionario_hist_persona (persona_id, creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sondeos mensuales: ¿se le preguntó a la persona si su equipo está bien o
-- necesitó arreglos? Separado del historial general (aunque cada sondeo
-- también deja una fila ahí) porque el contador de "cuándo toca el
-- próximo sondeo" necesita poder buscar nada más que sondeos, sin tener
-- que filtrar entre altas, ediciones y retiros.
CREATE TABLE equipo_funcionario_sondeos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    persona_id      INT NOT NULL,
    fecha           DATE NOT NULL,
    resultado       ENUM('bien', 'arreglos') NOT NULL,
    observaciones   TEXT NULL,
    realizado_por   VARCHAR(150) NULL,
    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (persona_id) REFERENCES personas(id),
    INDEX idx_equipo_funcionario_sondeo_persona (persona_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
