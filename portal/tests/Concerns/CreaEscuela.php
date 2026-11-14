<?php

namespace Tests\Concerns;

use App\Models\AsignacionProfesor;
use App\Models\Tarea;
use App\Models\User;

/** Arma una escuelita de prueba: 1 admin, 1 docente (grados 4 y 5), 1 acudiente con 2 hijos y estudiantes. */
trait CreaEscuela
{
    protected User $admin;
    protected User $docente;
    protected User $acudiente;
    protected User $ana;      // grado 5, hija del acudiente
    protected User $juan;     // grado 4, hijo del acudiente
    protected User $mateo;    // grado 4, sin acudiente

    protected function crearEscuela(): void
    {
        $this->admin = $this->usuario('admin', 'Secretaría', '+573000000099');
        $this->docente = $this->usuario('profesor', 'Martha Rojas', '+573000000011');
        AsignacionProfesor::create(['profesor_id' => $this->docente->id, 'grado' => 4, 'materia' => 'Ciencias']);
        AsignacionProfesor::create(['profesor_id' => $this->docente->id, 'grado' => 5, 'materia' => 'Ciencias']);

        $this->ana = $this->usuario('estudiante', 'Ana Pérez', '+573000000001', 5);
        $this->juan = $this->usuario('estudiante', 'Juan Pérez', '+573000000002', 4);
        $this->mateo = $this->usuario('estudiante', 'Mateo Ortiz', null, 4);

        $this->acudiente = $this->usuario('acudiente', 'Luz Pérez', '+573000000021');
        $this->acudiente->hijos()->sync([$this->ana->id, $this->juan->id]);
    }

    protected function usuario(string $rol, string $nombre, ?string $telefono = null, ?int $grado = null, bool $verificado = true): User
    {
        static $n = 0;
        $n++;

        return User::create([
            'name' => $nombre,
            'email' => "usuario{$n}@sanluquitas.local",
            'password' => 'clave-de-prueba-1',
            'rol' => $rol,
            'grado' => $grado,
            'telefono_whatsapp' => $telefono,
            'telefono_verificado_at' => $telefono && $verificado ? now() : null,
        ]);
    }

    protected function tarea(User $estudiante, string $materia = 'Matemáticas', string $estado = 'pendiente', int $dias = 3, string $origen = 'web'): Tarea
    {
        return Tarea::create([
            'estudiante_id' => $estudiante->id,
            'creada_por_id' => $estudiante->id,
            'materia' => $materia,
            'descripcion' => 'Descripción de '.$materia,
            'fecha_entrega' => now()->addDays($dias)->toDateString(),
            'estado' => $estado,
            'origen' => $origen,
        ]);
    }
}
