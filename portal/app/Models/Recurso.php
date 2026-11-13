<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recurso extends Model
{
    protected $table = 'recursos';

    protected $fillable = ['titulo', 'descripcion', 'url', 'categoria', 'funciona_sin_internet'];

    protected function casts(): array
    {
        return ['funciona_sin_internet' => 'boolean'];
    }

    public const CATEGORIAS = ['Programación visual', 'Pensamiento computacional', 'Para docentes', 'Juegos educativos'];

    public function icono(): string
    {
        return match ($this->categoria) {
            'Programación visual' => '🧩',
            'Pensamiento computacional' => '🧠',
            'Para docentes' => '📚',
            default => '🎲',
        };
    }

    public function claseEtiqueta(): string
    {
        return match ($this->categoria) {
            'Programación visual' => 'tag green',
            'Para docentes' => 'tag earth',
            default => 'tag',
        };
    }
}
