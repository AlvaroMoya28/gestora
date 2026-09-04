-- ============================================================
-- Sala de reuniones: origen y tipo de cada fila.
--
-- `origen` separa lo que pidió un funcionario por el formulario ('solicitud',
-- pasa por aprobación) de lo que administración escribió directo en el
-- calendario ('manual', ya queda aprobado — es administración anotando algo,
-- no pidiendo permiso).
--
-- `tipo` separa una reserva de verdad ('reserva': alguien va a usar la sala,
-- sea por el formulario o porque administración la anotó a mano por un pedido
-- que llegó por fuera del sistema) de un bloqueo ('bloqueo': la sala no se
-- puede usar — arreglos, por ejemplo — y no hay a quién avisarle nada).
-- ============================================================

ALTER TABLE sala_reservas
    ADD COLUMN origen ENUM('solicitud', 'manual') NOT NULL DEFAULT 'solicitud' AFTER solicitante_correo,
    ADD COLUMN tipo   ENUM('reserva', 'bloqueo')  NOT NULL DEFAULT 'reserva'   AFTER origen;
