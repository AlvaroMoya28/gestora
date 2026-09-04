-- ============================================================
-- Módulo: Activos (préstamo de equipo)
-- Tablas: activos, prestamos
-- Base de datos destino: gestionpaa (MariaDB)
--
-- Origen: Google Sheet 1Q_EQc1eoTmBdeJ5QDkK2Ri2r8qG9rj-9mr5whu-0b7s, con las
-- hojas "Activos", "Personal" y "Prestamos" que manejaba Activos.gs:
--   Activos   : Codigo, Nombre, Serie/Placa, IMEI, Accesorios, Estado/Obs, Disponible
--   Personal  : Área, Persona, Correo, Teléfono 1, Teléfono 2
--   Prestamos : Boleta, FechaHora, Codigo, Nombre, Serie/Placa, IMEI, Accesorios,
--               Estado/Obs, Funcionario, Observacion, EstadoPrestamo
--
-- La hoja "Personal" NO se migra a una tabla propia: es el mismo catálogo que
-- `personas` (ver 003_personas.sql). Ahí estaba el peor duplicado del sistema
-- viejo — las mismas personas cargadas por separado en dos Sheets.
-- ============================================================

CREATE TABLE activos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    codigo          VARCHAR(50)  NOT NULL UNIQUE,   -- el que se escanea o teclea
    nombre          VARCHAR(200) NOT NULL,
    serie_placa     VARCHAR(100) NULL,
    imei            VARCHAR(50)  NULL,
    accesorios      TEXT         NULL,
    estado_obs      TEXT         NULL,              -- "Estado/Obs" del Sheet

    -- En el Sheet esto era la columna "Disponible" con los valores SI / SÍ / NO,
    -- y el Apps Script tenía que normalizar tildes y mayúsculas en cada lectura
    -- (función clave_()). Como booleano el problema desaparece.
    disponible      TINYINT(1)   NOT NULL DEFAULT 1,

    -- Baja lógica: un activo dado de baja no debe romper el historial de
    -- préstamos que lo referencia.
    activo          TINYINT(1)   NOT NULL DEFAULT 1,

    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_activos_disponible (disponible, activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE prestamos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    -- Número de boleta visible para el usuario. En el Sheet se calculaba como
    -- "última fila de la hoja", que se rompe apenas alguien borra una fila o
    -- dos personas prestan a la vez. Acá es una columna con AUTO_INCREMENT
    -- propio garantizado por el UNIQUE.
    boleta          INT NOT NULL UNIQUE,
    activo_id       INT NOT NULL,
    persona_id      INT NULL,

    -- Se guarda también el nombre tal como se escribió en la boleta: si la
    -- persona cambia de nombre o se da de baja, la boleta histórica no cambia.
    funcionario     VARCHAR(150) NOT NULL,
    observacion     TEXT NULL,

    estado          ENUM('prestado','devuelto') NOT NULL DEFAULT 'prestado',
    prestado_en     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    devuelto_en     TIMESTAMP NULL DEFAULT NULL,

    FOREIGN KEY (activo_id)  REFERENCES activos(id),
    FOREIGN KEY (persona_id) REFERENCES personas(id),

    INDEX idx_prestamos_estado (estado, prestado_en),
    INDEX idx_prestamos_activo (activo_id, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Secuencia propia para el número de boleta.
-- Se usa una tabla de un solo renglón en vez de MAX(boleta)+1 porque MAX+1 es
-- una condición de carrera: dos préstamos simultáneos sacarían la misma boleta.
CREATE TABLE secuencia_boletas (
    id              TINYINT PRIMARY KEY DEFAULT 1,
    ultimo          INT NOT NULL DEFAULT 0,
    CONSTRAINT ck_secuencia_unica CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO secuencia_boletas (id, ultimo) VALUES (1, 0);

-- ============================================================
-- Datos
-- ============================================================
--     php scripts/importar_csv.php activos  ruta/al/Activos.csv
--     php scripts/importar_csv.php personal ruta/al/Personal.csv
--
-- La hoja "Prestamos" es un historial; si se quiere conservar, se importa de
-- último (necesita que los activos ya existan):
--     php scripts/importar_csv.php prestamos ruta/al/Prestamos.csv
