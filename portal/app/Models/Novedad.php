<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Novedad extends Model
{
    // Laravel pluralizaría en inglés ("novedads"); se fija el nombre real
    protected $table = 'novedades';

    protected $fillable = ['titulo', 'resumen', 'contenido', 'categoria', 'fecha_publicacion'];

    protected function casts(): array
    {
        return ['fecha_publicacion' => 'date'];
    }

    public const CATEGORIAS = ['General', 'Académico', 'Eventos', 'Comunidad', 'Tecnología'];

    // Clase CSS del punto de color de la categoría (cat-academico, cat-eventos…)
    public function claseCategoria(): string
    {
        return 'cat-'.Str::slug($this->categoria);
    }
}
