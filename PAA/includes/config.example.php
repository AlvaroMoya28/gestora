<?php
/**
 * PLANTILLA de configuración. Copiar como `config.php` y llenar los valores
 * reales. `config.php` está en .gitignore: las credenciales NUNCA se suben
 * al repositorio.
 *
 *   cp includes/config.example.php includes/config.php
 */

return [
    'db_host' => 'servicio_bd_haproxy_bd',
    'db_name' => 'gestionpaa',
    'db_user' => 'gestionpaa',
    'db_pass' => 'PONER_LA_CLAVE_AQUI',

    // Opcionales. Dejar vacíos para el servidor de producción, que conecta
    // por TCP en el puerto por defecto. Sirven para correr una copia local
    // de la base durante el desarrollo.
    'db_port'   => null,   // ej. 3307
    'db_socket' => null,   // ej. '/tmp/db2/m.sock' (si se usa, ignora el host)

    // ------------------------------------------------------------------
    // CORREO — UNA CUENTA POR MÓDULO
    // ------------------------------------------------------------------
    //
    // Cada módulo puede tener su propia cuenta, y esto NO es una comodidad:
    // no todas las cuentas sirven para todo.
    //
    //   · gtipaaucr@gmail.com es la cuenta de soporte del programa y está
    //     reservada a los tickets de avería: es la dirección a la que la gente
    //     responde cuando reporta algo roto.
    //
    //   · Las actas de revisión y los comprobantes de tulas van a
    //     coordinadores de centros educativos. Son documentos institucionales;
    //     salir de un @gmail.com de soporte los hace ver como lo que no son.
    //     Esperan la cuenta institucional del Centro de Informática.
    //
    // Un módulo sin cuenta NO envía, y lo dice con un mensaje claro. Es a
    // propósito: es mejor que avise a que use la primera cuenta que encuentre
    // y mande un acta desde la dirección equivocada.
    //
    // La clave 'general' se usa para los módulos que no tengan la suya. Hoy no
    // se define ninguna general justamente para que ningún módulo herede por
    // descuido la cuenta de tickets.

    'correo_cuentas' => [

        'tickets' => [
            // Si se omite 'remitente', se usa el propio usuario. Gmail reescribe
            // el remitente cuando no coincide con quien autentica, así que
            // ponerlos distintos no sirve de nada.
            'remitente' => 'Soporte PAA <gtipaaucr@gmail.com>',
            'smtp' => [
                'host'      => 'smtp.gmail.com',
                'puerto'    => 587,
                'seguridad' => 'tls',
                'usuario'   => 'gtipaaucr@gmail.com',
                // OJO: NO es la contraseña con la que se entra a la cuenta.
                // Gmail dejó de aceptarlas en 2022. Hay que generar una
                // «contraseña de aplicación» de 16 letras en:
                //   myaccount.google.com/apppasswords
                // Solo aparece si la cuenta tiene verificación en dos pasos.
                'clave'     => 'PONER_LA_CONTRASENA_DE_APLICACION',
            ],
        ],

        // Sala de Reuniones: mismo caso que tickets, avisos internos del
        // programa (a quien solicita y a Informática), no documentos para
        // coordinadores. Usa la misma cuenta de soporte; si el Centro de
        // Informática entrega una cuenta propia para esto, se le pone acá su
        // propio bloque igual que a 'tickets'.
        'sala_reuniones' => [
            'remitente' => 'Sala de Reuniones PAA <gtipaaucr@gmail.com>',
            'smtp' => [
                'host'      => 'smtp.gmail.com',
                'puerto'    => 587,
                'seguridad' => 'tls',
                'usuario'   => 'gtipaaucr@gmail.com',
                'clave'     => 'PONER_LA_CONTRASENA_DE_APLICACION',
            ],
        ],

        // Cuando el Centro de Informática entregue la cuenta institucional,
        // estos tres se destraban agregando sus bloques acá. El relay responde
        // en smtp.ucr.ac.cr:25 con STARTTLS pero exige credenciales.
        //
        // 'revision' => ['remitente' => '...', 'smtp' => [...]],
        // 'tulas'    => ['remitente' => '...', 'smtp' => [...]],
        // 'activos'  => ['remitente' => '...', 'smtp' => [...]],
    ],

    // ------------------------------------------------------------------
    // CORREO — forma anterior, una sola cuenta para todo
    // ------------------------------------------------------------------
    // Se sigue respetando si está presente y no hay 'correo_cuentas', para que
    // un servidor con la configuración vieja no se quede sin correo de golpe.
    // En instalaciones nuevas usá 'correo_cuentas'.
    // El sistema manda correo en tres momentos: la boleta de préstamo de
    // equipo, el comprobante de tulas y el acta de revisión de aulas.
    //
    // El servidor (acceso01) NO tiene programa de correo instalado: no existe
    // /usr/sbin/sendmail, así que mail() de PHP devuelve false siempre. Por eso
    // el bloque 'correo_smtp' de abajo no es opcional — sin él no sale ningún
    // correo. Para comprobarlo: php scripts/probar_correo.php

    // Tiene que ser la MISMA dirección con la que se autentica abajo. Gmail
    // reescribe el remitente si no coincide, y el relay de la UCR rechaza el
    // envío.
    'correo_remitente' => 'PAA UCR <gtipaaucr@gmail.com>',

    'correo_smtp' => [
        // Cuenta que ya se usaba para esto en la época de Google Sheets, así
        // que los coordinadores reconocen la dirección.
        'host'      => 'smtp.gmail.com',
        'puerto'    => 587,
        'seguridad' => 'tls',
        'usuario'   => 'gtipaaucr@gmail.com',

        // OJO: NO es la contraseña con la que se entra a la cuenta. Gmail dejó
        // de aceptarlas en 2022. Hay que generar una «contraseña de
        // aplicación» de 16 letras en:
        //   myaccount.google.com → Seguridad → Contraseñas de aplicaciones
        // La opción solo aparece si la cuenta tiene activada la verificación
        // en dos pasos.
        'clave'     => 'PONER_LA_CONTRASENA_DE_APLICACION',
    ],

    // ALTERNATIVA: el relay institucional. Responde en smtp.ucr.ac.cr:25 con
    // STARTTLS y anuncia AUTH. Conviene si en algún momento molesta que los
    // correos salgan de una dirección @gmail.com, o si se pasa del tope de
    // Gmail (500 destinatarios por día; el sistema ya se limita a 300 en
    // MAX_CORREOS_DIA). Habría que pedirle al Centro de Informática una cuenta
    // de servicio, o que autoricen la IP del servidor:
    //
    //     'correo_smtp' => [
    //         'host'      => 'smtp.ucr.ac.cr',
    //         'puerto'    => 25,
    //         'seguridad' => 'tls',
    //         'usuario'   => '',
    //         'clave'     => '',
    //     ],
];
