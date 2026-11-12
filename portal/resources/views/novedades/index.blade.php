@extends('layouts.app')
@section('titulo', 'Novedades')

@section('contenido')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Novedades</h1>
    @if (config('portal.edicion_abierta') || auth()->user()?->rol === 'admin')
        <a href="{{ route('novedades.create') }}" class="btn btn-success">+ Nueva novedad</a>
    @endif
</div>

<form method="GET" action="{{ route('novedades.index') }}" class="row g-2 mb-4" role="search">
    <div class="col-sm-8 col-md-6">
        <input type="search" name="buscar" value="{{ $buscar }}" class="form-control" placeholder="Buscar por título o contenido">
    </div>
    <div class="col-auto">
        <button class="btn btn-outline-secondary">Buscar</button>
        @if ($buscar !== '')<a href="{{ route('novedades.index') }}" class="btn btn-link">Limpiar</a>@endif
    </div>
</form>

<div class="row g-3">
    @forelse ($novedades as $novedad)
        <div class="col-md-6">
            <article class="card h-100"><div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between mb-2">
                    <span class="badge etiqueta">{{ $novedad->categoria }}</span>
                    <small class="text-muted">{{ $novedad->fecha_publicacion->format('d/m/Y') }}</small>
                </div>
                <h2 class="h5"><a href="{{ route('novedades.show', $novedad) }}" class="text-decoration-none">{{ $novedad->titulo }}</a></h2>
                <p class="flex-grow-1">{{ $novedad->resumen ?? \Illuminate\Support\Str::limit($novedad->contenido, 160) }}</p>
                <a href="{{ route('novedades.show', $novedad) }}" class="small">Leer más →</a>
            </div></article>
        </div>
    @empty
        <p class="text-muted">No se encontraron novedades.</p>
    @endforelse
</div>

<div class="mt-4">{{ $novedades->links() }}</div>
@endsection
