-- ============================================================
-- Corrige el modelo de hospedaje
-- Base de datos destino: gestionpaa (MariaDB)
--
-- POR QUÉ
-- La migración 007 creó `hospedajes` suponiendo que ese Sheet registraba a
-- quién se le pagó hospedaje: persona, lugar, noches, monto.
--
-- Al portar la página quedó claro que no es eso. El Sheet
-- 1G_jrXKdI8KrfjANZxf86pwatBgMWs_PS-c61vGG_h_g es un **catálogo de tarifas**:
-- una fila por provincia + cantón + distrito con el monto que corresponde
-- pagar ahí. La página lo usa al calcular el presupuesto de una sede: se elige
-- la ubicación en tres selectores y de ahí sale la tarifa.
--
-- Es decir, no es un registro de gastos sino una tabla de referencia. Con el
-- modelo anterior no había forma de buscar la tarifa de un distrito, que es lo
-- único que la página necesita.
--
-- Se elimina la tabla equivocada en vez de dejarla al lado: está vacía (nunca
-- se llegó a importar) y mantener dos tablas parecidas con nombres casi
-- iguales es la forma más segura de que alguien escriba en la que no es.
-- ============================================================

DROP TABLE IF EXISTS hospedajes;

CREATE TABLE tarifas_hospedaje (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    provincia   VARCHAR(100) NOT NULL,
    canton      VARCHAR(100) NOT NULL,
    distrito    VARCHAR(100) NOT NULL,
    -- Monto en colones. Entero porque así viene del Sheet y así se presupuesta;
    -- no hay céntimos de por medio.
    tarifa      INT NOT NULL,
    creado_en   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Una tarifa por distrito: si el Sheet trae la misma ubicación dos veces
    -- con montos distintos, la importación lo va a rechazar en vez de dejar
    -- que la página elija cualquiera de las dos.
    UNIQUE KEY uq_tarifa_ubicacion (provincia, canton, distrito),
    INDEX idx_tarifa_provincia (provincia, canton)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Datos
-- ============================================================
--     php scripts/importar_csv.php tarifas_hospedaje archivo.csv
--
-- Columnas esperadas: Provincia, Canton (o Cantón), Distrito, Tarifa
