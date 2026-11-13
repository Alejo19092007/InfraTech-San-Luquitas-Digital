<?php

namespace App\Http\Controllers;

use App\Models\Novedad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// CRUD de novedades: listar, ver, crear, editar y eliminar
class NovedadController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar', ''));
        $categoria = in_array($request->query('categoria'), Novedad::CATEGORIAS, true) ? $request->query('categoria') : null;

        $novedades = Novedad::query()
            ->when($categoria, fn ($q) => $q->where('categoria', $categoria))
            ->when($buscar !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('titulo', 'ilike', "%{$buscar}%")
                ->orWhere('contenido', 'ilike', "%{$buscar}%")))
            ->orderByDesc('fecha_publicacion')
            ->orderByDesc('id')
            ->paginate(6)
            ->withQueryString();

        return view('novedades.index', compact('novedades', 'buscar', 'categoria'));
    }

    public function show(Novedad $novedad): View
    {
        return view('novedades.show', compact('novedad'));
    }

    public function create(): View
    {
        return view('novedades.form', ['novedad' => new Novedad(['fecha_publicacion' => now()])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $novedad = Novedad::create($this->validar($request));

        return redirect()->route('novedades.show', $novedad)
            ->with('estado', 'Novedad publicada correctamente.');
    }

    public function edit(Novedad $novedad): View
    {
        return view('novedades.form', compact('novedad'));
    }

    public function update(Request $request, Novedad $novedad): RedirectResponse
    {
        $novedad->update($this->validar($request));

        return redirect()->route('novedades.show', $novedad)
            ->with('estado', 'Novedad actualizada correctamente.');
    }

    public function destroy(Novedad $novedad): RedirectResponse
    {
        $novedad->delete();

        return redirect()->route('novedades.index')
            ->with('estado', 'Novedad eliminada.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'titulo' => ['required', 'string', 'min:5', 'max:150'],
            'resumen' => ['nullable', 'string', 'max:300'],
            'contenido' => ['required', 'string', 'min:20'],
            'categoria' => ['required', Rule::in(Novedad::CATEGORIAS)],
            'fecha_publicacion' => ['required', 'date'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'max' => 'El campo :attribute no puede superar :max caracteres.',
            'in' => 'Selecciona una :attribute válida.',
            'date' => 'La :attribute no es una fecha válida.',
        ], [
            'titulo' => 'título',
            'contenido' => 'contenido',
            'categoria' => 'categoría',
            'fecha_publicacion' => 'fecha de publicación',
        ]);
    }
}
