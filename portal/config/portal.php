<?php

// Configuración propia del Portal San Luquitas
return [
    'institucion' => 'Institución Educativa San Luquitas',
    'moodle_url' => env('MOODLE_URL', 'https://moodle.sanluquitas.local'),

    // true mientras no exista el login con roles (ver PuedeEditarContenido)
    'edicion_abierta' => (bool) env('PORTAL_EDICION_ABIERTA', true),
];
