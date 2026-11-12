@extends('layouts.app')
@section('titulo', 'Recursos educativos')

@section('contenido')
<h1 class="h3">Recursos educativos</h1>
<p class="text-muted">Herramientas seleccionadas para aprender programación y pensamiento computacional en primaria.</p>

<div class="mb-4 d-flex flex-wrap gap-2">
    <a href="{{ route('recursos.index') }}" class="btn btn-sm {{ $categoria ? 'btn-outline-success' : 'btn-success' }}">Todos</a>
    @foreach (\App\Models\Recurso::CATEGORIAS as $cat)
        <a href="{{ route('recursos.index', ['categoria' => $cat]) }}" class="btn btn-sm {{ $categoria === $cat ? 'btn-success' : 'btn-outline-success' }}">{{ $cat }}</a>
    @endforeach
</div>

@forelse ($recursos as $grupo => $items)
    <h2 class="h5 mt-4 mb-3">{{ $grupo }}</h2>
    <div class="row g-3">
        @foreach ($items as $recurso)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100"><div class="card-body d-flex flex-column">
                    <h3 class="h6">{{ $recurso->titulo }}</h3>
                    <p class="small flex-grow-1">{{ $recurso->descripcion }}</p>
                    @if ($recurso->funciona_sin_internet)
                        <span class="badge text-bg-warning mb-2 align-self-start">Funciona sin internet</span>
                    @endif
                    <a href="{{ $recurso->url }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-success align-self-start">Abrir recurso ↗</a>
                </div></div>
            </div>
        @endforeach
    </div>
@empty
    <p class="text-muted">No hay recursos en esta categoría.</p>
@endforelse
@endsection
