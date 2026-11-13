@extends('layouts.app')
@section('titulo', 'Recursos educativos')

@section('contenido')
<div class="container page-intro">
    <span class="eyebrow">Aprender a nuestro ritmo</span>
    <h1>Recursos educativos</h1>
    <p>Actividades para explorar, practicar y enseñar. Algunos materiales se pueden usar sin conexión a internet.</p>
</div>

<div class="container">
    <div class="toolbar">
        <div class="search-box">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg>
            <input data-search type="search" placeholder="Buscar recursos..." aria-label="Buscar recursos">
        </div>
    </div>

    <div class="filters" aria-label="Filtrar recursos">
        <button class="filter-chip" data-filter="Todas" aria-pressed="true">Todos</button>
        @foreach (\App\Models\Recurso::CATEGORIAS as $cat)
            <button class="filter-chip" data-filter="{{ $cat }}" aria-pressed="false">{{ $cat }}</button>
        @endforeach
    </div>

    <div class="resource-grid">
        @foreach ($recursos as $recurso)
            <article class="card resource-card" data-filter-card data-category="{{ $recurso->categoria }}"
                     data-search="{{ $recurso->titulo }} {{ $recurso->descripcion }}">
                <div>
                    <span class="card-icon">{{ $recurso->icono() }}</span>
                    <span class="{{ $recurso->claseEtiqueta() }}">{{ $recurso->categoria }}</span>
                    <h3>{{ $recurso->titulo }}</h3>
                    <p>{{ $recurso->descripcion }}</p>
                </div>
                <div class="resource-bottom">
                    @if ($recurso->funciona_sin_internet)
                        <span class="offline">✓ Funciona sin internet</span>
                    @else
                        <span></span>
                    @endif
                    <a class="btn btn-primary btn-small" href="{{ $recurso->url }}" target="_blank" rel="noopener noreferrer">Abrir recurso ↗</a>
                </div>
            </article>
        @endforeach
        <div class="empty-state" data-empty-state @if ($recursos->isNotEmpty()) hidden @endif>No encontramos recursos con ese filtro. Prueba con otra categoría.</div>
    </div>
</div>
@endsection
