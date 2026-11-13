<?php

// Configuración propia del Portal San Luquitas
return [
    'institucion' => 'Institución Educativa San Luquitas',
    'municipio' => 'Vereda El Arrayán, zona rural',
    'moodle_url' => env('MOODLE_URL', 'https://moodle.sanluquitas.local'),
    'whatsapp' => env('PORTAL_WHATSAPP', '300 000 0000'),   // número ficticio de demostración

    // true mientras no exista el login con roles (ver PuedeEditarContenido)
    'edicion_abierta' => (bool) env('PORTAL_EDICION_ABIERTA', true),

    /*
     | Fotografías ilustrativas. La institución es ficticia: estas fotos son de
     | escuelas rurales reales de Colombia, publicadas con licencia Creative
     | Commons. Se descargan una vez con scripts/descargar-fotos.sh y luego se
     | sirven desde el propio servidor (funcionan sin internet).
     | Licencia NC-ND: uso no comercial y sin modificar (no se recortan: se
     | muestran en su proporción original 3:2).
     */
    'fotos' => [
        'a-clase' => [
            'archivo' => 'img/fotos/estudiante-corre-a-clase.jpg',
            'alt' => 'Un niño con uniforme escolar corre por un camino rural hacia su escuela',
            'pie' => 'Camino a clase en la zona rural de San José, Colombia.',
            'autor' => 'Charlotte Kesl / Banco Mundial',
            'licencia' => 'CC BY-NC-ND 2.0',
            'fuente' => 'https://www.flickr.com/photos/worldbank/4725004476',
        ],
        'sala-computo' => [
            'archivo' => 'img/fotos/clase-de-computadores.jpg',
            'alt' => 'Estudiantes en una clase de computadores en un colegio rural',
            'pie' => 'Clase de computadores en el colegio rural San José, La Ceja del Tambo, Antioquia.',
            'autor' => 'Charlotte Kesl / Banco Mundial',
            'licencia' => 'CC BY-NC-ND 2.0',
            'fuente' => 'https://www.flickr.com/photos/worldbank/4725033296',
        ],
        'estudiantes' => [
            'archivo' => 'img/fotos/estudiantes-colegio-rural.jpg',
            'alt' => 'Estudiantes de un colegio rural de Antioquia',
            'pie' => 'Estudiantes del colegio rural San José, La Ceja, Antioquia.',
            'autor' => 'Charlotte Kesl / Banco Mundial',
            'licencia' => 'CC BY-NC-ND 2.0',
            'fuente' => 'https://www.flickr.com/photos/worldbank/4699804389',
        ],
        'vereda' => [
            'archivo' => 'img/fotos/estudiante-zona-rural.jpg',
            'alt' => 'Estudiante en una zona rural de La Ceja del Tambo',
            'pie' => 'Estudiante en la zona rural de La Ceja del Tambo, Colombia.',
            'autor' => 'Charlotte Kesl / Banco Mundial',
            'licencia' => 'CC BY-NC-ND 2.0',
            'fuente' => 'https://www.flickr.com/photos/worldbank/4725000160',
        ],
        'paisaje' => [
            'archivo' => 'img/fotos/paisaje-rural.jpg',
            'alt' => 'Paisaje rural de Colombia con montañas y cultivos',
            'pie' => 'Paisaje rural colombiano.',
            'autor' => 'Neil Palmer (CIAT)',
            'licencia' => 'CC BY-SA 2.0',
            'fuente' => 'https://commons.wikimedia.org/wiki/File:NP_rural_colombia_(5867722386).jpg',
        ],
    ],
];
