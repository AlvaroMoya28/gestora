-- ============================================================
-- Módulo: Coordinadores_Directorio
-- Tablas núcleo reutilizables: areas, coordinadores, coordinador_telefonos
-- Base de datos destino: gestionpaa (MariaDB)
-- ============================================================

CREATE TABLE areas (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100) NOT NULL UNIQUE,
    orden           SMALLINT NOT NULL DEFAULT 0,
    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE coordinadores (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    area_id         INT NOT NULL,
    nombre          VARCHAR(150) NOT NULL,
    correo          VARCHAR(150) NULL,
    extension       VARCHAR(20) NULL,
    activo          TINYINT(1) NOT NULL DEFAULT 1,
    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (area_id) REFERENCES areas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE coordinador_telefonos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    coordinador_id  INT NOT NULL,
    telefono        VARCHAR(20) NOT NULL,
    FOREIGN KEY (coordinador_id) REFERENCES coordinadores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Datos actuales (migrados desde el Google Sheet "Hoja 1")
-- ============================================================

INSERT INTO areas (nombre, orden) VALUES
('Coordinación Académica', 1),
('Secretaría', 2),
('Equipo de Verbal', 3),
('Equipo de Matemática', 4),
('Adecuaciones', 5),
('Psicometría y Estadística', 6),
('Apoyo Informático', 7),
('Coordinación Administrativa', 8),
('Administrativos', 9),
('Archivista', 10),
('Mensajería y Limpieza', 11);

INSERT INTO coordinadores (area_id, nombre, correo, extension) VALUES
((SELECT id FROM areas WHERE nombre='Coordinación Académica'),   'Karen Calvo Díaz',        'karenalejandra.calvo@ucr.ac.cr',      '5445'),
((SELECT id FROM areas WHERE nombre='Secretaría'),                'Mirania Astorga Monge',   'evelyn.astorga@ucr.ac.cr',            '5725'),
((SELECT id FROM areas WHERE nombre='Equipo de Verbal'),          'Guaner Rojas Rojas',      'guaner.rojas@ucr.ac.cr',              '6035'),
((SELECT id FROM areas WHERE nombre='Equipo de Verbal'),          'Sigrid Solano Moraga',    'sigrid.solano@ucr.ac.cr',             '4126'),
((SELECT id FROM areas WHERE nombre='Equipo de Verbal'),          'Diana Martínez Alpízar',  'diana.martinez@ucr.ac.cr',            '4127'),
((SELECT id FROM areas WHERE nombre='Equipo de Verbal'),          'Nelson Pérez Rojas',      'nelson.perezrojas@ucr.ac.cr',         '6293'),
((SELECT id FROM areas WHERE nombre='Equipo de Matemática'),      'Kenner Ordoñez Lacayo',   'kenner.ordonez@ucr.ac.cr',            '1467'),
((SELECT id FROM areas WHERE nombre='Equipo de Matemática'),      'Karol Jiménez Alfaro',    'karol.jimenez@ucr.ac.cr',             '6080'),
((SELECT id FROM areas WHERE nombre='Equipo de Matemática'),      'Luis Rojas Torres',       'luismiguel.rojas@ucr.ac.cr',          '6291'),
((SELECT id FROM areas WHERE nombre='Equipo de Matemática'),      'Marisela Valverde García','marisela.valverdegarcia@ucr.ac.cr',   '1468'),
((SELECT id FROM areas WHERE nombre='Adecuaciones'),              'Laura Solano Alvarado',   'laura.solanoalvarado@ucr.ac.cr',      '6034'),
((SELECT id FROM areas WHERE nombre='Adecuaciones'),              'Monica Arias Monge',      'monica.ariasmongue@ucr.ac.cr',        '1453'),
((SELECT id FROM areas WHERE nombre='Psicometría y Estadística'), 'Danny Cerdas Núñez',      'danny.cerdas@ucr.ac.cr',              '4128'),
((SELECT id FROM areas WHERE nombre='Apoyo Informático'),         'Andrei Fallas Rojas',     'andrei.fallas@ucr.ac.cr',              '6294'),
((SELECT id FROM areas WHERE nombre='Coordinación Administrativa'),'Jenny Bolaños Valerio',  'jenny.bolanos@ucr.ac.cr',              '6998'),
((SELECT id FROM areas WHERE nombre='Administrativos'),           'Pamela Prada López',      'pinoska.prada@ucr.ac.cr',              '4385'),
((SELECT id FROM areas WHERE nombre='Administrativos'),           'Carlos Solera Aguilar',   'carlos.solera@ucr.ac.cr',              '5997'),
((SELECT id FROM areas WHERE nombre='Archivista'),                'Marisel Monestel Flores', 'marisel.monestel@ucr.ac.cr',           '1735'),
((SELECT id FROM areas WHERE nombre='Mensajería y Limpieza'),     'Rosa Víquez Rojas',       'rosa.viquez@ucr.ac.cr',                '4385');

INSERT INTO coordinador_telefonos (coordinador_id, telefono) VALUES
((SELECT id FROM coordinadores WHERE correo='karenalejandra.calvo@ucr.ac.cr'),    '8783-1319'),
((SELECT id FROM coordinadores WHERE correo='evelyn.astorga@ucr.ac.cr'),          '8376-7179'),
((SELECT id FROM coordinadores WHERE correo='evelyn.astorga@ucr.ac.cr'),          '2591-5965'),
((SELECT id FROM coordinadores WHERE correo='guaner.rojas@ucr.ac.cr'),            '8530-9294'),
((SELECT id FROM coordinadores WHERE correo='sigrid.solano@ucr.ac.cr'),           '8651-0965'),
((SELECT id FROM coordinadores WHERE correo='diana.martinez@ucr.ac.cr'),          '8520-3747'),
((SELECT id FROM coordinadores WHERE correo='nelson.perezrojas@ucr.ac.cr'),       '8348-5570'),
((SELECT id FROM coordinadores WHERE correo='kenner.ordonez@ucr.ac.cr'),          '8875-7451'),
((SELECT id FROM coordinadores WHERE correo='karol.jimenez@ucr.ac.cr'),           '8874-0425'),
((SELECT id FROM coordinadores WHERE correo='karol.jimenez@ucr.ac.cr'),           '4701-0551'),
((SELECT id FROM coordinadores WHERE correo='luismiguel.rojas@ucr.ac.cr'),        '8313-1304'),
((SELECT id FROM coordinadores WHERE correo='marisela.valverdegarcia@ucr.ac.cr'), '6044-8810'),
((SELECT id FROM coordinadores WHERE correo='laura.solanoalvarado@ucr.ac.cr'),    '8832-6878'),
((SELECT id FROM coordinadores WHERE correo='monica.ariasmongue@ucr.ac.cr'),      '8325-9520'),
((SELECT id FROM coordinadores WHERE correo='danny.cerdas@ucr.ac.cr'),            '8896-9636'),
((SELECT id FROM coordinadores WHERE correo='andrei.fallas@ucr.ac.cr'),           '8862-5039'),
((SELECT id FROM coordinadores WHERE correo='andrei.fallas@ucr.ac.cr'),           '2245-5769'),
((SELECT id FROM coordinadores WHERE correo='jenny.bolanos@ucr.ac.cr'),           '8373-3387'),
((SELECT id FROM coordinadores WHERE correo='pinoska.prada@ucr.ac.cr'),           '8897-5223'),
((SELECT id FROM coordinadores WHERE correo='carlos.solera@ucr.ac.cr'),           '8364-3063'),
((SELECT id FROM coordinadores WHERE correo='marisel.monestel@ucr.ac.cr'),        '6128-7522'),
((SELECT id FROM coordinadores WHERE correo='rosa.viquez@ucr.ac.cr'),             '8500-6685');
