<?php

namespace Database\Seeders;

use App\Models\Novedad;
use Illuminate\Database\Seeder;

class NovedadSeeder extends Seeder
{
    public function run(): void
    {
        // Las noticias antiguas de ejemplo (de cuando eran tres sedes) se reemplazan una sola vez;
        // después, si ya hay datos, no se pisa nada de lo que escriba la escuela.
        $antiguas = Novedad::whereIn('titulo', [
            'Nueva sala de informática en la sede principal',
            'Jornada de mingas por la escuela',
            'Taller para docentes: enseñar con Scratch',
            'Prueba de registro desde el portal',   // novedad de prueba de una verificación
        ]);
        $habia = $antiguas->exists();
        $antiguas->delete();
        if (! $habia && Novedad::exists()) {
            return;
        }

        $novedades = [
            ['Abrimos el aula virtual San Luquitas Digital', 'Tecnología', 30,
             'Los grados 4.º y 5.º ya pueden ingresar al curso "Mis primeros pasos en programación".',
             "Desde esta semana los estudiantes de 4.º y 5.º cuentan con el aula virtual San Luquitas Digital.\n\nEl curso tiene tres unidades: qué es un algoritmo, pensamiento computacional y programación visual con Scratch. Cada estudiante recibe su usuario en la sala de informática."],
            ['Feria de la ciencia y la tecnología rural', 'Eventos', 21,
             'El 28 de noviembre presentamos los proyectos del año en la escuela.',
             "Invitamos a toda la comunidad educativa a la feria anual. Los estudiantes mostrarán sus animaciones hechas en Scratch, la huerta escolar y el proyecto de reciclaje.\n\nHabrá almuerzo comunitario organizado por la asociación de padres."],
            ['Entrega de boletines del tercer periodo', 'Académico', 15,
             'Reunión de acudientes el viernes a las 8:00 a. m.',
             "Los acudientes recibirán los boletines del tercer periodo de manos de los docentes. Recuerden traer el carné de acudiente.\n\nQuienes no puedan asistir pueden consultar las tareas pendientes de sus hijos por WhatsApp."],
            ['Nueva sala de informática para la escuela', 'Tecnología', 10,
             'Llegaron 12 equipos donados para la enseñanza de programación.',
             "Gracias a la gestión de la rectoría y al apoyo de la alcaldía municipal, la escuela cuenta con 12 computadores nuevos conectados al servidor local del colegio.\n\nEl servidor permite usar el aula virtual aunque falle el internet de la vereda."],
            ['Minga de padres y madres en la escuela', 'Comunidad', 6,
             'Las familias adecuaron el camino de acceso y pintaron el aula multigrado.',
             'Agradecemos a las familias que participaron en la minga del sábado. Se limpió el camino de acceso, se pintó el aula multigrado y se arregló el techo de la cocina escolar.'],
            ['Taller de Scratch para docentes', 'Académico', 2,
             'Capacitación de dos tardes para los 6 docentes de la escuela.',
             "Los docentes participarán en un taller sobre cómo planear clases de pensamiento computacional con Scratch y actividades desconectadas (sin computador).\n\nEl material quedará disponible en el aula virtual."],
            ['Recordatorio: uso responsable de internet', 'General', 1,
             'Pautas básicas de seguridad digital para estudiantes y familias.',
             "No compartas tu contraseña del aula virtual, no publiques datos personales y avisa a un adulto si algo en internet te hace sentir incómodo.\n\nEn la sección de recursos encontrarás material sobre ciudadanía digital."],
        ];

        foreach ($novedades as [$titulo, $categoria, $diasAtras, $resumen, $contenido]) {
            Novedad::updateOrCreate(['titulo' => $titulo], [
                'categoria' => $categoria,
                'fecha_publicacion' => now()->subDays($diasAtras)->toDateString(),
                'resumen' => $resumen,
                'contenido' => $contenido,
            ]);
        }
    }
}
