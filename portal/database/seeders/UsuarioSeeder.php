<?php

namespace Database\Seeders;

use App\Models\AsignacionProfesor;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Usuarios FICTICIOS para la demostración (1 admin, 2 docentes, 3 acudientes y 5 estudiantes).
 * Solo crea los que no existen: nunca pisa lo que la administración haya cambiado.
 * Los números +57300000000x son de mentira y vienen verificados para poder probar el bot
 * sin Meta con `php artisan whatsapp:simular`.
 */
class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $clave = config('portal.clave_demo');

        $crear = function (string $correo, string $nombre, string $rol, ?int $grado = null, ?string $telefono = null) use ($clave): User {
            return User::firstOrCreate(
                ['email' => $correo],
                [
                    'name' => $nombre,
                    'password' => $clave,
                    'rol' => $rol,
                    'grado' => $grado,
                    'telefono_whatsapp' => $telefono,
                    'telefono_verificado_at' => $telefono ? now() : null,
                ]
            );
        };

        $crear('secretaria@sanluquitas.local', 'Secretaría San Luquitas', 'admin');

        $martha = $crear('docente.martha@sanluquitas.local', 'Martha Rojas', 'profesor', null, '+573000000011');
        $jorge = $crear('docente.jorge@sanluquitas.local', 'Jorge Pardo', 'profesor', null, '+573000000012');

        if ($martha->wasRecentlyCreated) {
            foreach ([4, 5] as $grado) {
                AsignacionProfesor::create(['profesor_id' => $martha->id, 'grado' => $grado, 'materia' => 'Ciencias naturales']);
            }
        }
        if ($jorge->wasRecentlyCreated) {
            foreach ([1, 2, 3] as $grado) {
                AsignacionProfesor::create(['profesor_id' => $jorge->id, 'grado' => $grado, 'materia' => 'Matemáticas']);
            }
        }

        $ana = $crear('est.ana@sanluquitas.local', 'Ana Pérez', 'estudiante', 5, '+573000000001');
        $sofia = $crear('est.sofia@sanluquitas.local', 'Sofía Pérez', 'estudiante', 3);
        $juan = $crear('est.juan@sanluquitas.local', 'Juan Gómez', 'estudiante', 4, '+573000000002');
        $mateo = $crear('est.mateo@sanluquitas.local', 'Mateo Ortiz', 'estudiante', 4);
        $crear('est.camila@sanluquitas.local', 'Camila Díaz', 'estudiante', 5);

        $luz = $crear('acudiente.luz@sanluquitas.local', 'Luz Marina Pérez', 'acudiente', null, '+573000000021');
        $pedro = $crear('acudiente.pedro@sanluquitas.local', 'Pedro Gómez', 'acudiente');
        $rosa = $crear('acudiente.rosa@sanluquitas.local', 'Rosa Ortiz', 'acudiente');

        foreach ([[$luz, [$ana, $sofia]], [$pedro, [$juan]], [$rosa, [$mateo]]] as [$acudiente, $hijos]) {
            if ($acudiente->wasRecentlyCreated) {
                $acudiente->hijos()->sync(collect($hijos)->pluck('id'));
            }
        }
    }
}
