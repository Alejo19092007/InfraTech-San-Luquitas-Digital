<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsignacionProfesor extends Model
{
    protected $table = 'asignaciones_profesor';

    public $timestamps = false;

    protected $fillable = ['profesor_id', 'grado', 'materia'];
}
