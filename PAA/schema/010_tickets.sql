-- ============================================================
-- Módulo: Creación de Tickets (reporte de averías)
-- Tablas: ticket_categorias, tickets, ticket_comentarios
-- Base de datos destino: gestionpaa (MariaDB)
--
-- Este módulo NO viene de un Google Sheet: `CreacionTickets.html` estaba vacío
-- y su Apps Script era un `myFunction()` sin cuerpo. Se diseña desde cero.
--
-- QUÉ RESUELVE
-- El día de la aplicación se reportan averías (un aula sin luz, un proyector
-- que no sirve, faltante de material). Hoy eso va por WhatsApp o por correo, y
-- no queda registro de qué se reportó, quién lo atendió ni si se resolvió.
--
-- Se apoya en las tablas que ya existen: la sede y el aula no se escriben como
-- texto, se referencian. Así un ticket no puede apuntar a una sede inexistente
-- y se puede preguntar "cuántas averías tuvo esta sede", que es justo lo que
-- hoy no se puede responder.
-- ============================================================

CREATE TABLE ticket_categorias (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL UNIQUE,
    -- Para agrupar en la interfaz sin depender del nombre exacto.
    icono       VARCHAR(50) NULL,
    orden       SMALLINT NOT NULL DEFAULT 0,
    activo      TINYINT(1) NOT NULL DEFAULT 1,
    creado_en   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tickets (
    id              INT AUTO_INCREMENT PRIMARY KEY,

    -- Folio visible, del tipo TK-20260821-001. Lo genera el servidor: si lo
    -- generara el navegador, dos personas reportando a la vez producirían el
    -- mismo número.
    folio           VARCHAR(30) NOT NULL UNIQUE,

    categoria_id    INT NOT NULL,

    -- Dónde ocurrió. La sede es obligatoria; el aula es opcional porque hay
    -- averías que son de la sede entera (el portón, la electricidad).
    sede_id         INT NOT NULL,
    aula_id         INT NULL,
    convocatoria_id INT NULL,

    titulo          VARCHAR(200) NOT NULL,
    descripcion     TEXT NOT NULL,

    prioridad       ENUM('baja','media','alta','critica') NOT NULL DEFAULT 'media',
    estado          ENUM('abierto','en_proceso','resuelto','cerrado') NOT NULL DEFAULT 'abierto',

    -- Quién reporta. Se guarda el nombre escrito además del enlace a
    -- `personas`, porque quien reporta desde una sede no siempre está en el
    -- catálogo, y porque el nombre del momento no debe cambiar después.
    reportado_por       VARCHAR(150) NOT NULL,
    reportado_correo    VARCHAR(150) NULL,
    reportado_persona_id INT NULL,

    -- A quién le toca resolverlo. Sí sale del catálogo: solo se le puede
    -- asignar trabajo a alguien que existe.
    asignado_persona_id INT NULL,

    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resuelto_en     TIMESTAMP NULL DEFAULT NULL,

    FOREIGN KEY (categoria_id)         REFERENCES ticket_categorias(id),
    FOREIGN KEY (sede_id)              REFERENCES sedes(id),
    FOREIGN KEY (aula_id)              REFERENCES aulas(id),
    FOREIGN KEY (convocatoria_id)      REFERENCES convocatorias(id),
    FOREIGN KEY (reportado_persona_id) REFERENCES personas(id),
    FOREIGN KEY (asignado_persona_id)  REFERENCES personas(id),

    -- Los dos filtros que va a usar la pantalla: "qué está abierto" y
    -- "qué pasó en esta sede".
    INDEX idx_tickets_estado (estado, prioridad, creado_en),
    INDEX idx_tickets_sede   (sede_id, creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seguimiento de cada ticket. Un ticket sin historial obliga a preguntar por
-- fuera qué se hizo, que es el problema que este módulo viene a resolver.
CREATE TABLE ticket_comentarios (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id       INT NOT NULL,
    autor           VARCHAR(150) NOT NULL,
    persona_id      INT NULL,
    comentario      TEXT NOT NULL,
    -- Cuando el comentario acompaña un cambio de estado, queda registrado
    -- junto con él y no como dos cosas sueltas.
    estado_nuevo    ENUM('abierto','en_proceso','resuelto','cerrado') NULL,
    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (ticket_id)  REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (persona_id) REFERENCES personas(id),
    INDEX idx_comentarios_ticket (ticket_id, creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Categorías iniciales
-- ============================================================
-- Van acá y no en el importador porque son parte del diseño del módulo, no
-- datos que cambien entre convocatorias. Se agregan más desde phpMyAdmin.
INSERT INTO ticket_categorias (nombre, icono, orden) VALUES
('Infraestructura',      'fa-building',        1),
('Electricidad',         'fa-bolt',            2),
('Mobiliario',           'fa-chair',           3),
('Equipo de cómputo',    'fa-desktop',         4),
('Material de prueba',   'fa-box-open',        5),
('Limpieza',             'fa-broom',           6),
('Seguridad',            'fa-shield-halved',   7),
('Otro',                 'fa-circle-question', 99);
