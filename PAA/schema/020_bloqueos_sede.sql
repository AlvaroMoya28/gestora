-- ============================================================
-- Quién está trabajando en cada sede
-- Base de datos destino: gestionpaa (MariaDB)
--
-- QUÉ RESUELVE
-- El día del embalaje hay varias personas metiendo datos al mismo tiempo, cada
-- una con su tanda de sedes. Si dos abren la misma, la segunda pisa lo que
-- escribió la primera sin que ninguna se entere hasta que los folletos no
-- calcen.
--
-- POR QUÉ NO ALCANZA CON SABER EL USUARIO
-- Todo el personal extraordinario entra con LA MISMA cuenta compartida. Eso es
-- deliberado —son decenas de personas por un fin de semana y no tiene sentido
-- crearles una cuenta a cada una— pero significa que el usuario de la sesión no
-- distingue a nadie: si el bloqueo se comparara por nombre de cuenta, todos
-- serían «la misma persona» y no bloquearía nunca.
--
-- Por eso se guardan tres cosas distintas:
--   · `sesion`  — identifica al NAVEGADOR. Es lo único que de verdad separa a
--                 una persona de otra cuando comparten la cuenta, y es lo que
--                 se compara para decidir si hay choque.
--   · `quien`   — el nombre que la persona escribió al entrar. Sirve para
--                 avisar «la sede 401 la tiene Ana», que es lo que hace que el
--                 aviso se pueda resolver hablando en vez de esperando.
--   · `usuario` — la cuenta. Queda para la bitácora, no para comparar.
--
-- ESTO NO ES UN CANDADO DURO
-- Es un aviso. Se puede entrar igual a una sede tomada —a veces la persona se
-- fue, o se le cerró el navegador— y por eso el bloqueo vence solo. Un candado
-- que no se pueda abrir termina con media bodega esperando a que alguien vuelva
-- de almorzar.
-- ============================================================

-- ------------------------------------------------------------
-- Se rehace, no se adapta
-- ------------------------------------------------------------
-- En algunos servidores esta tabla ya existía: se creó a mano, con otra forma
-- (`usuario`, `segmento`, `timestamp`) y sin migración que la respaldara. Por
-- eso el módulo devolvía 500 al listar las sedes: la tabla estaba, pero sin las
-- columnas que el código consulta.
--
-- Se borra y se vuelve a crear en vez de irle agregando columnas, y eso acá NO
-- es destructivo: lo único que guarda es «quién está mirando qué en este
-- momento». Vence solo a los quince minutos y se vuelve a escribir en cuanto
-- alguien abre una sede. No hay ningún dato que perder.
--
-- Con cualquier otra tabla esto sería inaceptable. La diferencia es que esta no
-- tiene memoria: es estado de un rato, no información.
DROP TABLE IF EXISTS sedes_bloqueos;

CREATE TABLE sedes_bloqueos (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    convocatoria_id     INT NOT NULL,
    sede_id             INT NOT NULL,

    sesion              CHAR(32)     NOT NULL,
    quien               VARCHAR(80)  NOT NULL,
    usuario             VARCHAR(100) NOT NULL,
    segmento            SMALLINT     NOT NULL DEFAULT 1,

    -- Se refresca con cada latido del navegador. Un bloqueo sin latidos
    -- recientes se considera vencido: ver `MINUTOS_BLOQUEO` en el endpoint.
    visto_en            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    creado_en           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Una sede, un bloqueo. Es lo que permite tomarla con un solo INSERT ...
    -- ON DUPLICATE KEY y no con leer-y-después-escribir, que entre dos personas
    -- apretando a la vez deja pasar a las dos.
    UNIQUE KEY uq_bloqueo_sede (convocatoria_id, sede_id),

    FOREIGN KEY (convocatoria_id) REFERENCES convocatorias(id),
    FOREIGN KEY (sede_id)         REFERENCES sedes(id) ON DELETE CASCADE,

    INDEX idx_bloqueo_visto (visto_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
