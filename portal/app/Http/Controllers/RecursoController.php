<?php

namespace App\Http\Controllers;

use App\Models\Recurso;
use Illuminate\View\View;

class RecursoController extends Controller
{
    public function index(): View
    {
        // Pocos registros: se envían todos y la búsqueda y los filtros se hacen
        // en el navegador (public/js/portal.js), sin recargar la página.
        $recursos = Recurso::orderBy('categoria')->orderBy('titulo')->get();

        return view('recursos.index', compact('recursos'));
    }
}
