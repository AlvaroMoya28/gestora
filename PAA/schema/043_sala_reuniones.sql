-- ============================================================
-- Sala de reuniones: solicitudes de préstamo y su resolución.
--
-- El flujo es: alguien pide la sala para una fecha y una franja horaria,
-- administración aprueba o rechaza, y se le avisa por correo.
--
-- EL TECLADO Y EL MOUSE
-- La sala tiene proyector, pero el teclado y el mouse se guardan en la
-- Oficina de Informática: hay que ir a retirarlos antes de la reunión y
-- devolverlos al terminar. Por eso hay cuatro columnas de `equipo_*` en vez
-- de un solo "sí/no": lo que hace falta saber no es si se prestaron, es si
-- están de vuelta y quién los tiene ahora mismo. Solo se llenan cuando la
-- reserva se aprueba y se pidió el proyector.
-- ============================================================

CREATE TABLE sala_reservas (
    id                     INT AUTO_INCREMENT PRIMARY KEY,
    folio                  VARCHAR(20) NOT NULL UNIQUE,

    -- A nombre de quién queda. El nombre se copia además de guardar el id
    -- porque la reserva es un registro histórico: si la cuenta se desactiva
    -- o cambia de nombre, el folio tiene que seguir diciendo quién pidió.
    solicitante_usuario_id INT NULL,
    solicitante_persona_id INT NULL,
    solicitante_nombre     VARCHAR(160) NOT NULL,
    solicitante_correo     VARCHAR(160) NULL,

    fecha                  DATE NOT NULL,
    hora_inicio            TIME NOT NULL,
    hora_fin               TIME NOT NULL,

    motivo                 VARCHAR(200) NOT NULL,
    informacion_adicional  TEXT NULL,
    personas               INT NOT NULL DEFAULT 1,
    requiere_proyector     TINYINT(1) NOT NULL DEFAULT 0,

    estado                 ENUM('pendiente', 'aprobada', 'rechazada', 'cancelada')
                           NOT NULL DEFAULT 'pendiente',
    respuesta_motivo       TEXT NULL,      -- por qué se aprobó o se rechazó
    resuelto_por           VARCHAR(160) NULL,
    resuelto_en            DATETIME NULL,

    -- Teclado y mouse del proyector (ver el comentario de arriba).
    equipo_retirado_en     DATETIME NULL,
    equipo_retirado_por    VARCHAR(160) NULL,
    equipo_devuelto_en     DATETIME NULL,
    equipo_devuelto_por    VARCHAR(160) NULL,

    creado_en              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_sala_fecha  (fecha, hora_inicio),
    INDEX idx_sala_estado (estado),
    INDEX idx_sala_quien  (solicitante_usuario_id, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Reglamento de uso.
--
-- Una sola fila. Se guarda en la base y no escrito en el PHP para que
-- administración pueda corregirlo sin depender de un despliegue: un
-- reglamento que hay que pedirle a informática que cambie termina
-- desactualizado y contradiciendo lo que de verdad se pide en la sala.
-- ============================================================

CREATE TABLE sala_reglamento (
    id              TINYINT NOT NULL PRIMARY KEY DEFAULT 1,
    texto           MEDIUMTEXT NOT NULL,
    actualizado_por VARCHAR(160) NULL,
    actualizado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO sala_reglamento (id, texto) VALUES (1,
'1. La sala se solicita con al menos 24 horas de anticipación. Las solicitudes del mismo día se atienden solo si la sala está libre.
2. La reserva no es válida hasta que aparezca como APROBADA en el sistema. Recibir el correo de solicitud registrada no es una aprobación.
3. La sala tiene proyector. El teclado y el mouse NO quedan en la sala: se retiran en la Oficina de Informática antes de la reunión y se devuelven ahí mismo apenas termina.
4. Quien solicita la sala es responsable del equipo mientras lo tiene. Si algo se daña o no aparece, hay que avisar de una vez a la Oficina de Informática.
5. La sala se entrega ordenada: sillas en su lugar, mesa despejada y luces apagadas.
6. No se consumen alimentos sobre la mesa de reuniones ni cerca del equipo.
7. Si la reunión se cancela, hay que cancelar también la reserva en el sistema para que la sala quede libre para alguien más.
8. Pasados 20 minutos de la hora reservada sin que nadie se presente, la sala se considera libre.
9. La sala se presta de lunes a viernes, de 7:00 a.m. a 4:00 p.m. A las 4:00 p.m. se cierra.
10. La sala no tiene aire acondicionado.');
