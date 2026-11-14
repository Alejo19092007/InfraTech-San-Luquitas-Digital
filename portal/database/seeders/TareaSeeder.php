<?php

namespace Database\Seeders;

use App\Models\Tarea;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Tareas de ejemplo (solo si la tabla está vacía), con fechas relativas a hoy. */
class TareaSeeder extends Seeder
{
    public function run(): void
    {
        if (Tarea::exists()) {
            return;
        }

        $personas = User::whereIn('email', [
            'est.ana@sanluquitas.local', 'est.sofia@sanluquitas.local', 'est.juan@sanluquitas.local',
            'est.mateo@sanluquitas.local', 'docente.martha@sanluquitas.local', 'docente.jorge@sanluquitas.local',
            'acudiente.luz@sanluquitas.local',
        ])->get()->keyBy('email');

        if ($personas->count() < 7) {
            return;
        }

        $ana = $personas['est.ana@sanluquitas.local'];
        $sofia = $personas['est.sofia@sanluquitas.local'];
        $juan = $personas['est.juan@sanluquitas.local'];
        $mateo = $personas['est.mateo@sanluquitas.local'];
        $martha = $personas['docente.martha@sanluquitas.local'];
        $jorge = $personas['docente.jorge@sanluquitas.local'];
        $luz = $personas['acudiente.luz@sanluquitas.local'];

        $filas = [
            [$ana, $ana, 'Matemáticas', 'Página 32, ejercicios 1 al 5', 2, 'whatsapp', 'pendiente'],
            [$ana, $martha, 'Ciencias naturales', 'Dibujar el ciclo del agua y rotular las partes', 4, 'web', 'pendiente'],
            [$ana, $ana, 'Lenguaje', 'Leer el cuento «El árbol generoso» y escribir un resumen', -1, 'web', 'pendiente'],
            [$sofia, $luz, 'Matemáticas', 'Practicar la tabla del 6', 1, 'whatsapp', 'pendiente'],
            [$sofia, $jorge, 'Matemáticas', 'Resolver las sumas de la ficha 4', 3, 'web', 'pendiente'],
            [$juan, $martha, 'Ciencias naturales', 'Traer una planta para observar sus partes', 2, 'web', 'pendiente'],
            [$juan, $juan, 'Sociales', 'Mapa de la vereda con sus caminos', -3, 'whatsapp', 'entregada'],
            [$mateo, $martha, 'Ciencias naturales', 'Traer una planta para observar sus partes', 2, 'web', 'pendiente'],
        ];

        foreach ($filas as [$estudiante, $autor, $materia, $descripcion, $dias, $origen, $estado]) {
            Tarea::create([
                'estudiante_id' => $estudiante->id,
                'creada_por_id' => $autor->id,
                'materia' => $materia,
                'descripcion' => $descripcion,
                'fecha_entrega' => now()->addDays($dias)->toDateString(),
                'estado' => $estado,
                'origen' => $origen,
                'entregada_at' => $estado === 'entregada' ? now()->subDay() : null,
            ]);
        }
    }
}
