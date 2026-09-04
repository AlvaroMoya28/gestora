-- ============================================================
-- Rol: personal extraordinario
-- Base de datos destino: gestionpaa (MariaDB)
--
-- QUIÉNES SON
-- Personal que se contrata solo para el período de embalaje. No son parte
-- del programa: entran, capturan lo de las sedes que les tocan y se van.
--
-- POR QUÉ UN ROL NUEVO Y NO «funcionario»
-- Un funcionario del PAA ve el directorio y reporta averías, y nada de eso le
-- corresponde a alguien externo. Y darles rol de administración para que
-- alcancen Ingreso de Datos les abriría además las cuentas, los activos y los
-- sobresueldos. Ninguno de los dos roles existentes describe lo que necesitan,
-- que es exactamente un módulo.
--
-- SOBRE LA CONTRASEÑA
-- Es una cuenta compartida con clave fija conocida, y a propósito NO se les
-- exige cambiarla al entrar: si la primera persona que la usa la cambiara, el
-- resto del turno se quedaría afuera. Eso vale para una cuenta de acceso
-- acotado a una pantalla de captura; no valdría para una que pudiera tocar
-- datos de personas o de dinero, y por eso este rol llega hasta donde llega.
-- ============================================================

ALTER TABLE usuarios
    MODIFY COLUMN rol ENUM('funcionario','administrador','desarrollador','extraordinario')
                  NOT NULL DEFAULT 'funcionario';

-- ------------------------------------------------------------
-- Cómo se crea la cuenta
-- ------------------------------------------------------------
-- El hash no se puede generar desde SQL, así que la cuenta se crea con:
--
--     php scripts/crear_extraordinario.php --aplicar
--
-- Ese script crea también la persona a la que se cuelga la cuenta, marcada
-- como oculta para que no salga en el directorio ni en los selectores.
