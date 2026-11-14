<?php

namespace Database\Seeders;

use App\Models\Recurso;
use Illuminate\Database\Seeder;

class RecursoSeeder extends Seeder
{
    public function run(): void
    {
        if (Recurso::exists()) {
            return;
        }

        $recursos = [
            ['Scratch', 'Programación visual', 'https://scratch.mit.edu', true,
             'Crea historias, juegos y animaciones con bloques. Tiene versión de escritorio que funciona sin internet.'],
            ['ScratchJr', 'Programación visual', 'https://www.scratchjr.org', true,
             'Versión para los más pequeños, disponible como aplicación para tabletas.'],
            ['Code.org – Curso Express', 'Pensamiento computacional', 'https://studio.code.org/s/express', false,
             'Rompecabezas de programación por niveles, en español, con videos cortos.'],
            ['La Hora del Código', 'Juegos educativos', 'https://hourofcode.com/es', false,
             'Actividades de una hora con personajes conocidos para iniciarse en la programación.'],
            ['CS Unplugged', 'Pensamiento computacional', 'https://www.csunplugged.org/es/', true,
             'Actividades de computación sin computador: ideales cuando no hay equipos o conexión.'],
            ['Lightbot', 'Juegos educativos', 'https://lightbot.com', false,
             'Juego de lógica en el que se programa un robot para encender casillas.'],
            ['Programamos – Recursos para docentes', 'Para docentes', 'https://programamos.es', false,
             'Planes de clase y fichas para enseñar programación en primaria.'],
            ['Colombia Aprende', 'Para docentes', 'https://www.colombiaaprende.edu.co', false,
             'Portal educativo del Ministerio de Educación Nacional con contenidos digitales.'],
        ];

        foreach ($recursos as [$titulo, $categoria, $url, $offline, $descripcion]) {
            Recurso::create([
                'titulo' => $titulo,
                'categoria' => $categoria,
                'url' => $url,
                'funciona_sin_internet' => $offline,
                'descripcion' => $descripcion,
            ]);
        }
    }
}
