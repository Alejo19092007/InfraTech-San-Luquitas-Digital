<?php

namespace App\Http\Controllers;

use App\Models\Novedad;
use App\Models\Recurso;
use Illuminate\View\View;

class PaginaController extends Controller
{
    public function inicio(): View
    {
        return view('paginas.inicio', [
            'novedades' => Novedad::orderByDesc('fecha_publicacion')->limit(3)->get(),
            'totalRecursos' => Recurso::count(),
        ]);
    }

    public function institucional(): View
    {
        return view('paginas.institucional');
    }
}
