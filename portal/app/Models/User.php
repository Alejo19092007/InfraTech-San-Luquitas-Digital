<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLES = ['admin', 'profesor', 'acudiente', 'estudiante'];

    public const ETIQUETAS_ROL = [
        'admin' => 'Administración',
        'profesor' => 'Docente',
        'acudiente' => 'Acudiente',
        'estudiante' => 'Estudiante',
    ];

    protected $fillable = [
        'name', 'email', 'password', 'rol', 'grado', 'telefono_whatsapp', 'telefono_verificado_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'telefono_verificado_at' => 'datetime',
            'password' => 'hashed',
            'grado' => 'integer',
        ];
    }

    public function esRol(string ...$roles): bool
    {
        return in_array($this->rol, $roles, true);
    }

    public function etiquetaRol(): string
    {
        return self::ETIQUETAS_ROL[$this->rol] ?? $this->rol;
    }

    public function tieneWhatsappVerificado(): bool
    {
        return $this->telefono_whatsapp !== null && $this->telefono_verificado_at !== null;
    }

    /** Hijos de un acudiente. */
    public function hijos(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'acudiente_estudiante', 'acudiente_id', 'estudiante_id');
    }

    /** Acudientes de un estudiante. */
    public function acudientes(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'acudiente_estudiante', 'estudiante_id', 'acudiente_id');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionProfesor::class, 'profesor_id');
    }

    public function tareas(): HasMany
    {
        return $this->hasMany(Tarea::class, 'estudiante_id');
    }

    /** @return list<int> Grados que dicta un profesor. */
    public function gradosQueDicta(): array
    {
        return $this->asignaciones->pluck('grado')->map(fn ($g) => (int) $g)->sort()->values()->all();
    }

    /** Primer nombre, para saludar. */
    public function primerNombre(): string
    {
        return explode(' ', trim($this->name))[0];
    }
}
