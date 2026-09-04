-- ============================================================
-- Estudiantes y traslado de sede
-- Base de datos destino: gestionpaa (MariaDB)
--
-- QUÉ RESUELVE
-- El sistema conocía las sedes, las aulas y quién las coordina, pero no a la
-- gente que se sienta a hacer la prueba. Eso alcanzaba mientras la asignación
-- de estudiantes viviera en el otro sistema y acá solo se embalara material.
--
-- Dejó de alcanzar el día que una sede avisó que no presta las instalaciones
-- para el turno del domingo por la mañana, con las aulas ya asignadas. Mover
-- esos estudiantes a otra sede es un cambio de datos, no de material: hay que
-- saber quiénes son, en qué aula estaban y en cuál quedan.
--
-- LA REGLA QUE NO SE ROMPE
-- El número de fórmula NO cambia nunca. Es lo que identifica al estudiante y
-- lo que aparece impreso en su documentación; un traslado mueve el lugar, no
-- a la persona. Todo lo demás de la fila se conserva igual.
--
-- POR QUÉ NO VAN EN `personas` NI EN `padron_personas`
-- `personas` es el directorio del personal del PAA (19 filas). `padron_personas`
-- es la gente contratada para aplicar la prueba (875 en 2026). Estos son decenas
-- de miles de estudiantes por convocatoria. Son tres poblaciones sin ninguna
-- relación entre sí y mezclarlas volvería inservibles las dos primeras.
-- ============================================================

-- ------------------------------------------------------------
-- Estudiantes asignados a una sede y un aula
-- ------------------------------------------------------------
CREATE TABLE estudiantes (
    id                  BIGINT AUTO_INCREMENT PRIMARY KEY,
    convocatoria_id     INT NOT NULL,

    -- LA IDENTIDAD ES EL NÚMERO DE CÓMPUTO, NO LA FÓRMULA.
    --
    -- Vale la pena dejarlo escrito porque el nombre engaña. En el archivo de
    -- 2026 hay 56 737 estudiantes y la columna FORMULA tiene diez valores
    -- —del 1 al 10—: es la versión del cuadernillo que le toca a cada quien,
    -- no un identificador. El que identifica es `compute_0005`, con 56 737
    -- valores distintos, uno por persona.
    --
    -- Tomar la fórmula como identidad hace que un índice único colapse a toda
    -- la población en diez filas.
    identificacion      VARCHAR(40)  NOT NULL,

    -- La versión del examen. NO se toca en un traslado: la persona se lleva su
    -- cuadernillo a la sede nueva.
    formula             VARCHAR(60)  NULL,

    nombre              VARCHAR(200) NULL,   -- el de la persona, no el de la sede
    formulario          VARCHAR(60)  NULL,   -- tipo de inscripción (AD1, AD2, AD3)

    -- Dónde le toca HOY. Es lo único que un traslado modifica.
    sede_codigo         VARCHAR(20)  NOT NULL,
    sede_id             INT          NULL,
    numero_aula         VARCHAR(20)  NOT NULL,

    -- El turno viene en la columna CONVOCATORIA del archivo, con valores como
    -- «3-M-DOMINGO». No es la convocatoria del sistema (el año): son cosas
    -- distintas con el mismo nombre, y conviene no confundirlas.
    turno               VARCHAR(50)  NULL,

    fecha_examen        DATE         NULL,
    -- La fecha tal como venía, con la hora incluida («03/10/2026  8:00AM»).
    -- Se conserva aparte porque al exportar hay que devolverla igual, y porque
    -- va atada al turno: si alguien cambia de turno, le cambia la hora.
    fecha_texto         VARCHAR(40)  NULL,

    -- El nombre de la sede escrito como lo escribe el otro sistema
    -- («1401M CTP DE PEJIBAYE - PÉREZ ZELEDÓN»), que no es como lo escribe
    -- `sedes.nombre`. Se guarda por estudiante y se actualiza al trasladar,
    -- copiándolo de alguien que ya esté en la sede de destino. Así el archivo
    -- exportado sale con el formato que el otro sistema espera leer, en vez de
    -- con dos columnas que se contradicen.
    sede_nombre_texto   VARCHAR(200) NULL,

    -- Dónde le tocaba al importar. Se escribe una sola vez y ya no se toca:
    -- es lo que permite responder «¿de dónde venía esta persona?» meses después,
    -- aunque la haya movido más de un traslado.
    sede_origen         VARCHAR(20)  NOT NULL,
    aula_origen         VARCHAR(20)  NOT NULL,
    turno_origen        VARCHAR(50)  NULL,

    trasladado          TINYINT(1)   NOT NULL DEFAULT 0,

    -- La fila del archivo tal como vino, en JSON.
    --
    -- POR QUÉ SE GUARDA COMPLETA
    -- El archivo trae columnas que este sistema no interpreta y no tiene por
    -- qué interpretar. Al exportar hay que devolver un archivo con LAS MISMAS
    -- columnas, o el otro sistema no lo puede leer. Sin esta copia habría que
    -- inventar el resto de los campos, que es exactamente como se pierden datos
    -- en una migración.
    fila_original       TEXT NULL,

    creado_en           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Una persona no puede estar dos veces en la misma convocatoria. Es también
    -- lo que hace que volver a subir el archivo actualice en vez de duplicar.
    UNIQUE KEY uq_estudiante (convocatoria_id, identificacion),

    FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id),
    -- Sin CASCADE: borrar una sede con estudiantes adentro tiene que fallar y
    -- no llevárselos en silencio.
    FOREIGN KEY (sede_id) REFERENCES sedes(id),

    -- «Los estudiantes de la sede 401 del turno de la mañana» es la consulta
    -- del módulo de traslado, y la que se hace contra decenas de miles de filas.
    INDEX idx_est_ubicacion (convocatoria_id, sede_codigo, turno),
    INDEX idx_est_aula (convocatoria_id, sede_codigo, numero_aula),
    INDEX idx_est_sede (sede_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Traslados
-- ------------------------------------------------------------
-- La cabecera de la operación: qué sede se movió a cuál y con qué criterio.
CREATE TABLE traslados (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    convocatoria_id     INT NOT NULL,

    sede_origen_id      INT NOT NULL,
    turno_origen        VARCHAR(50)  NULL,
    sede_destino_id     INT NOT NULL,
    turno_destino       VARCHAR(50)  NULL,

    capacidad_aula      SMALLINT NOT NULL,   -- 25, 30, lo que se haya decidido
    aulas_destino       SMALLINT NOT NULL,
    estudiantes         INT      NOT NULL,
    aula_inicial        SMALLINT NOT NULL DEFAULT 1,

    -- Por qué se hizo. En una operación de emergencia esto es lo primero que
    -- alguien va a querer leer el año siguiente.
    motivo              VARCHAR(255) NULL,

    sede_creada         TINYINT(1) NOT NULL DEFAULT 0,
    aulas_sincronizadas TINYINT(1) NOT NULL DEFAULT 0,

    hecho_por           VARCHAR(100) NOT NULL,
    hecho_en            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revertido_por       VARCHAR(100) NULL,
    revertido_en        TIMESTAMP NULL,

    FOREIGN KEY (convocatoria_id)  REFERENCES convocatorias(id),
    FOREIGN KEY (sede_origen_id)   REFERENCES sedes(id),
    FOREIGN KEY (sede_destino_id)  REFERENCES sedes(id),

    INDEX idx_traslados_fecha (hecho_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- El antes y el después de cada estudiante.
--
-- POR QUÉ FILA POR FILA Y NO SOLO EL RESUMEN
-- Dos razones. La primera es poder exportar «solo lo que cambió», que es lo
-- que el otro sistema necesita recibir. La segunda es poder deshacerlo: si el
-- traslado se hizo con la capacidad equivocada, sin este detalle la única
-- salida sería volver a importar el archivo entero y perder cualquier otro
-- cambio hecho desde entonces.
CREATE TABLE traslado_detalle (
    id                  BIGINT AUTO_INCREMENT PRIMARY KEY,
    traslado_id         INT    NOT NULL,
    estudiante_id       BIGINT NOT NULL,

    sede_antes          VARCHAR(20) NOT NULL,
    aula_antes          VARCHAR(20) NOT NULL,
    turno_antes         VARCHAR(50) NULL,

    sede_despues        VARCHAR(20) NOT NULL,
    aula_despues        VARCHAR(20) NOT NULL,
    turno_despues       VARCHAR(50) NULL,

    FOREIGN KEY (traslado_id)   REFERENCES traslados(id) ON DELETE CASCADE,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,

    INDEX idx_detalle_traslado (traslado_id),
    INDEX idx_detalle_estudiante (estudiante_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Cargas del padrón de estudiantes
-- ------------------------------------------------------------
CREATE TABLE cargas_estudiantes (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    convocatoria_id     INT NOT NULL,
    archivo             VARCHAR(255) NOT NULL,

    filas_leidas        INT NOT NULL DEFAULT 0,
    nuevos              INT NOT NULL DEFAULT 0,
    actualizados        INT NOT NULL DEFAULT 0,
    descartados         INT NOT NULL DEFAULT 0,

    columnas            TEXT NULL,   -- qué columnas traía y cómo se mapearon
    cargado_por         VARCHAR(100) NOT NULL,
    cargado_en          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id),
    INDEX idx_cargas_fecha (cargado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SOBRE BORRAR UN AÑO
-- ============================================================
-- api/importacion.php borra una convocatoria por pasos explícitos. Estas tres
-- tablas hay que sumarlas ahí, en este orden y ANTES de `sedes`:
--
--     traslado_detalle → traslados → estudiantes → cargas_estudiantes
--
-- `estudiantes` tiene llave foránea a `sedes` sin CASCADE justamente para que,
-- si alguien se olvida de agregarlas, el borrado falle en vez de dejar filas
-- apuntando a sedes que ya no existen.
