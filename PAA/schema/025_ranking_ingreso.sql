-- ============================================================
-- Ranking de Ingreso de Datos: quién ha avanzado más
--
-- POR QUÉ
-- Todo el personal extraordinario entra con la MISMA cuenta (ver el
-- encabezado de schema/020_bloqueos_sede.sql), y ya se distingue a cada
-- persona por el nombre que escribe en "Soy: ___" (ver `quien` en
-- IngresoDatos/index.php). Esto reutiliza esa misma identidad para armar un
-- ranking amistoso de sedes completadas, sin inventar cuentas nuevas.
--
-- DOS TABLAS, NO UNA
-- `ingreso_participacion` es un registro vivo: a qué sedes le puso la mano
-- cada quien, se actualiza en cada guardado. `ingreso_logros` es un hecho
-- que ya pasó y no cambia: el momento en que una sede quedó completa y quién
-- se lleva el crédito. Si se mezclaran en una sola tabla, "quién trabajó en
-- esto" y "quién se ganó el punto" competirían por las mismas columnas.
--
-- POR QUÉ SE REPARTE
-- Una sede casi siempre pasa por más de una persona (una hace folletos, otra
-- arma las tulas). Cuando la sede queda completa, el crédito se reparte en
-- partes iguales entre TODAS las personas que aparecen en
-- `ingreso_participacion` para esa sede — no se lo lleva entero quien dio el
-- último clic.
-- ============================================================

CREATE TABLE ingreso_participacion (
    sede_id         INT NOT NULL,
    convocatoria_id INT NOT NULL,
    quien           VARCHAR(80) NOT NULL,
    ultima_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (sede_id, convocatoria_id, quien),
    FOREIGN KEY (sede_id) REFERENCES sedes(id),
    FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ingreso_logros (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    sede_id         INT NOT NULL,
    convocatoria_id INT NOT NULL,
    quien           VARCHAR(80) NOT NULL,

    -- 1 dividido entre cuántas personas participaron en esta sede. La suma de
    -- las fracciones de una misma sede da 1 — es un logro, no varios.
    fraccion        DECIMAL(5,4) NOT NULL,

    completada_en   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (sede_id) REFERENCES sedes(id),
    FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id),
    INDEX idx_logros_quien (quien, completada_en),
    INDEX idx_logros_sede (sede_id, convocatoria_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
