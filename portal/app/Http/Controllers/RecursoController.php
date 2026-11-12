<?php

namespace App\Http\Controllers;

use App\Models\Recurso;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecursoController extends Controller
{
    public function index(Request $request): View
    {
        $categoria = $request->query('categoria');

        $recursos = Recurso::query()
            ->when(in_array($categoria, Recurso::CATEGORIAS, true), fn ($q) => $q->where('categoria', $categoria))
            ->orderBy('categoria')
            ->orderBy('titulo')
            ->get()
            ->groupBy('categoria');

        return view('recursos.index', compact('recursos', 'categoria'));
    }
}
