<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    // Color de la etiqueta en el diseño (clases .tag, .tag.green, .tag.earth)
    public function claseEtiqueta(): string
    {
        return match ($this->categoria) {
            'Académico', 'Tecnología' => 'tag green',
            'Comunidad' => 'tag earth',
            default => 'tag',
        };
    }
}
