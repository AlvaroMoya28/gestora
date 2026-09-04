-- ============================================================
-- Avisos del sistema: notificaciones para todo el mundo (menos personal
-- extraordinario, igual que la campanita — ver includes/notificaciones.php),
-- del estilo "hay una actualización del sistema" o "el módulo X va a estar
-- en mantenimiento". Se crean desde Anuncios (junto al correo, o solos, sin
-- mandar nada por correo) y se leen desde la campanita y desde
-- Notificaciones/index.php ("ver todas").
--
-- Es una tabla aparte de `correos_enviados` (que es el registro de entregas
-- de correo, una fila por destinatario, sin el cuerpo completo) y de
-- `situaciones_anuncios` (que son avisos atados a un rango de fechas para el
-- calendario de Situaciones Especiales, no notificaciones puntuales).
-- ============================================================

CREATE TABLE anuncios_sistema (
    id              INT AUTO_INCREMENT PRIMARY KEY,

    titulo          VARCHAR(200) NOT NULL,
    mensaje         TEXT NOT NULL,

    -- Mismo vocabulario que usa la campanita para colorear (alta/media/baja).
    nivel           ENUM('alta', 'media', 'baja') NOT NULL DEFAULT 'media',

    creado_por      VARCHAR(150) NOT NULL,
    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_anuncios_sistema_fecha (creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
