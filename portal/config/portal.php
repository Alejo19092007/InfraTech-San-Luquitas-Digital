<?php

// Configuración propia del Portal San Luquitas
return [
    'institucion' => 'Institución Educativa San Luquitas',
    'lema' => 'Educación rural con calidad humana y tecnología',
    'municipio' => 'Vereda El Arrayán, zona rural',
    // Datos de contacto ficticios (caso académico)
    'direccion' => 'Vereda El Arrayán, km 12 vía al casco urbano',
    'telefono' => '(607) 000 0000',
    'correo' => 'secretaria@sanluquitas.local',
    'horario' => 'Lunes a viernes, 7:00 a. m. a 1:00 p. m.',
    'moodle_url' => env('MOODLE_URL', 'https://moodle.sanluquitas.local'),
    'whatsapp' => env('PORTAL_WHATSAPP', '300 000 0000'),   // número ficticio de demostración

    // Contraseña de las cuentas ficticias de demostración (UsuarioSeeder). Cámbiala en el .env.
    'clave_demo' => env('PORTAL_CLAVE_DEMO') ?: 'SanLuquitas#2026',

    // Las noticias solo las edita el rol admin (ver PuedeEditarContenido). true las deja abiertas, solo para demostraciones.
    'edicion_abierta' => (bool) env('PORTAL_EDICION_ABIERTA', false),

    /*
     | Fotografías ilustrativas. La institución es ficticia: las fotos son de zonas
     | rurales y escuelas reales de Colombia, de Wikimedia Commons (licencias Creative
     | Commons o dominio público). scripts/descargar-fotos.sh las baja una vez al
     | servidor y escribe autor y licencia en public/img/fotos/creditos.json; así se
     | sirven sin internet y cada foto lleva su crédito.
     */
    // Foto ilustrativa que acompaña cada noticia según su categoría
    'foto_por_categoria' => [
        'General' => 'paisaje',
        'Académico' => 'a-clase',
        'Eventos' => 'estudiantes',
        'Comunidad' => 'vereda',
        'Tecnología' => 'sala-computo',
    ],

    'fotos' => (function () {
        $base = [
            'paisaje' => ['archivo' => 'img/fotos/paisaje-rural.jpg', 'alt' => 'Paisaje rural de Colombia', 'pie' => 'Paisaje rural colombiano.'],
            'estudiantes' => ['archivo' => 'img/fotos/estudiantes.jpg', 'alt' => 'Estudiantes de una escuela rural', 'pie' => 'Estudiantes en una escuela rural de Colombia.'],
            'a-clase' => ['archivo' => 'img/fotos/a-clase.jpg', 'alt' => 'Niños camino a la escuela', 'pie' => 'Camino a la escuela en una zona rural.'],
            'vereda' => ['archivo' => 'img/fotos/vereda.jpg', 'alt' => 'Vereda y vida campesina', 'pie' => 'Vida campesina en Colombia.'],
            'sala-computo' => ['archivo' => 'img/fotos/sala-computo.jpg', 'alt' => 'Estudiantes con computadores', 'pie' => 'Estudiantes con computadores en una escuela.'],
        ];
        $ruta = public_path('img/fotos/creditos.json');
        $creditos = is_file($ruta) ? (json_decode(file_get_contents($ruta), true) ?: []) : [];
        foreach ($base as $clave => &$f) {
            $c = $creditos[$clave] ?? [];
            $f += ['autor' => '', 'licencia' => '', 'fuente' => ''];
            foreach (['autor', 'licencia', 'fuente'] as $k) {
                $f[$k] = $c[$k] ?? $f[$k];
            }
            $f['alt'] = $c['alt'] ?? $f['alt'];
        }
        return $base;
    })(),
];
