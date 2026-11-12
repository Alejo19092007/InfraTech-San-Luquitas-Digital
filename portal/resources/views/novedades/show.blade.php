@extends('layouts.app')
@section('titulo', $novedad->titulo)

@section('contenido')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('novedades.index') }}">Novedades</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ \Illuminate\Support\Str::limit($novedad->titulo, 40) }}</li>
    </ol>
</nav>

<article class="card"><div class="card-body p-4">
    <span class="badge etiqueta mb-2">{{ $novedad->categoria }}</span>
    <h1 class="h3">{{ $novedad->titulo }}</h1>
    <p class="text-muted small">Publicada el {{ $novedad->fecha_publicacion->format('d/m/Y') }}</p>
    @if ($novedad->resumen)<p class="lead">{{ $novedad->resumen }}</p>@endif
    <div style="white-space: pre-line">{{ $novedad->contenido }}</div>
</div></article>

@if (config('portal.edicion_abierta') || auth()->user()?->rol === 'admin')
    <div class="d-flex gap-2 mt-3">
        <a href="{{ route('novedades.edit', $novedad) }}" class="btn btn-outline-primary">Editar</a>
        <form method="POST" action="{{ route('novedades.destroy', $novedad) }}"
              onsubmit="return confirm('¿Eliminar esta novedad? Esta acción no se puede deshacer.');">
            @csrf
            @method('DELETE')
            <button class="btn btn-outline-danger">Eliminar</button>
        </form>
    </div>
@endif
@endsection
