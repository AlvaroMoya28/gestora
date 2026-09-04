-- ============================================================
-- Módulo: Sedes
-- Tabla núcleo reutilizable: sedes
-- Base de datos destino: gestionpaa (MariaDB)
--
-- Origen: Google Sheet 171wt_clBFunAZCJnta3ke3IOL5ihQ3lsfqwm0-5RS90, "Hoja1",
-- que Sedes.html leía por opensheet.elk.sh con las columnas:
--   idSede, NombreSede, ContactoConserje, Telefono, Direccion, Email,
--   Director, Descripcion, ImagenURL
--
-- POR QUÉ ESTA TABLA VA PRIMERO
-- Casi todos los demás módulos identifican la sede por el mismo número
-- (la columna SEDE del Sheet de tulas, el "sede" de SobreSueldos, el destino
-- de las actas de Revisión de Aulas). Hoy ese número es una cadena suelta
-- repetida en cada Sheet, sin garantía de que exista. Al centralizarlo acá y
-- referenciarlo con llave foránea, deja de ser posible registrar un aula, un
-- marchamo o un sobresueldo contra una sede que no existe.
-- ============================================================

CREATE TABLE sedes (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    -- El "idSede" del Sheet. Es VARCHAR y no INT a propósito: en los Sheets
    -- aparece a veces con ceros a la izquierda ("07") y conviene conservarlo
    -- tal cual para que los códigos de barras y los cruces sigan calzando.
    codigo              VARCHAR(20)  NOT NULL UNIQUE,
    nombre              VARCHAR(200) NOT NULL,
    director            VARCHAR(150) NULL,
    contacto_conserje   VARCHAR(150) NULL,
    telefono            VARCHAR(50)  NULL,
    correo              VARCHAR(150) NULL,
    direccion           TEXT         NULL,
    descripcion         TEXT         NULL,
    imagen_url          VARCHAR(500) NULL,
    -- Se dan de baja, no se borran: hay actas y préstamos históricos que
    -- apuntan a sedes que ya no se usan.
    activo              TINYINT(1)   NOT NULL DEFAULT 1,
    creado_en           TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sedes_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Datos
-- ============================================================
-- Las filas NO van acá: el contenido del Sheet se carga con
--
--     php scripts/importar_csv.php sedes ruta/al/archivo.csv
--
-- Se hace así, y no con INSERTs escritos a mano en esta migración, porque el
-- catálogo de sedes cambia entre convocatorias: si los datos vivieran dentro
-- de la migración habría que editar un archivo ya aplicado (que por diseño
-- nunca se vuelve a correr) cada vez que entra o sale una sede.
-- El importador es idempotente: vuelve a correrse cuantas veces haga falta y
-- actualiza las sedes existentes por `codigo` en vez de duplicarlas.
