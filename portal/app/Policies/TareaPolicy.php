<?php

namespace App\Policies;

use App\Models\Tarea;
use App\Models\User;

/**
 * Permisos de las tareas (sección 7.4 del contexto).
 *
 *  - Estudiante: ve y marca las suyas.
 *  - Acudiente: ve y marca las de sus hijos.
 *  - Profesor: ve y crea las de los grados que dicta (no las marca: lo hace quien entrega).
 *  - Admin: ve todo.
 */
class TareaPolicy
{
    public function viewAny(User $usuario): bool
    {
        return $usuario->esRol(...User::ROLES);
    }

    public function view(User $usuario, Tarea $tarea): bool
    {
        return Tarea::query()->visiblesPara($usuario)->whereKey($tarea->id)->exists();
    }

    public function create(User $usuario): bool
    {
        return $usuario->esRol('estudiante', 'acudiente', 'profesor');
    }

    /** Marcar como entregada. */
    public function update(User $usuario, Tarea $tarea): bool
    {
        return $usuario->esRol('estudiante', 'acudiente') && $this->view($usuario, $tarea);
    }

    /** ¿Puede crear una tarea para este estudiante concreto? */
    public function crearPara(User $usuario, User $estudiante): bool
    {
        return match ($usuario->rol) {
            'estudiante' => $usuario->is($estudiante),
            'acudiente' => $usuario->hijos()->whereKey($estudiante->id)->exists(),
            'profesor' => $estudiante->grado !== null && in_array((int) $estudiante->grado, $usuario->gradosQueDicta(), true),
            default => false,
        };
    }
}
