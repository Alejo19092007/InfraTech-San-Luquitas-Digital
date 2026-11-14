<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tarea extends Model
{
    public const ESTADOS = ['pendiente', 'entregada'];

    public const ORIGENES = ['whatsapp', 'web'];

    protected $fillable = [
        'estudiante_id', 'creada_por_id', 'materia', 'descripcion', 'fecha_entrega', 'estado', 'origen', 'entregada_at',
    ];

    protected function casts(): array
    {
        return [
            'fecha_entrega' => 'date',
            'entregada_at' => 'datetime',
        ];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    public function creadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creada_por_id');
    }

    public function estaPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function estaVencida(): bool
    {
        return $this->estaPendiente() && $this->fecha_entrega->lt(now()->startOfDay());
    }

    public function marcarEntregada(): void
    {
        $this->forceFill(['estado' => 'entregada', 'entregada_at' => now()])->save();
    }

    /** Tareas que una persona tiene permiso de ver (sección 7.4 del contexto). */
    public function scopeVisiblesPara(Builder $consulta, User $usuario): Builder
    {
        return match ($usuario->rol) {
            'admin' => $consulta,
            'estudiante' => $consulta->where('estudiante_id', $usuario->id),
            'acudiente' => $consulta->whereIn('estudiante_id', $usuario->hijos()->select('users.id')),
            'profesor' => $consulta->whereIn(
                'estudiante_id',
                User::query()->select('id')->where('rol', 'estudiante')
                    ->whereIn('grado', $usuario->asignaciones()->select('grado'))
            ),
            default => $consulta->whereRaw('1 = 0'),
        };
    }
}
