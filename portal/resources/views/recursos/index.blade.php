@extends('layouts.app')
@section('titulo', 'Recursos educativos')

@section('contenido')
<div class="envoltura">
    <header class="cabeza-pagina">
        <h1>Recursos para aprender a programar</h1>
        <p>Juegos, actividades y guías para clase o para la casa. Los marcados “Sin internet” se pueden usar sin conexión.</p>
    </header>

    <div class="herramientas">
        <div class="buscar buscar-solo">
            <label class="oculto" for="buscar-recurso">Buscar recursos</label>
            <input id="buscar-recurso" type="search" data-buscar placeholder="Buscar un recurso">
        </div>
        <p class="contador" data-contador aria-live="polite">{{ $recursos->count() }} recursos</p>
    </div>

    <ul class="filtros" aria-label="Filtrar por categoría">
        <li><button class="filtro" type="button" data-filtro="todas" aria-pressed="true">Todas</button></li>
        @foreach (\App\Models\Recurso::CATEGORIAS as $cat)
            <li><button class="filtro" type="button" data-filtro="{{ $cat }}" aria-pressed="false">{{ $cat }}</button></li>
        @endforeach
    </ul>

    <ul class="recursos">
        @foreach ($recursos as $recurso)
            <li class="recurso {{ $recurso->claseCategoria() }}" data-recurso data-categoria="{{ $recurso->categoria }}">
                <span class="categoria">{{ $recurso->categoria }}</span>
                <h3>{{ $recurso->titulo }}</h3>
                <p>{{ $recurso->descripcion }}</p>
                <div class="pie">
                    <a class="enlace" href="{{ $recurso->url }}" target="_blank" rel="noopener noreferrer">Abrir {{ $recurso->titulo }}<span class="oculto"> (se abre en otra pestaña)</span></a>
                    @if ($recurso->funciona_sin_internet)
                        <span class="sin-internet"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>Sin internet</span>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
    <p class="vacio" data-vacio @if ($recursos->isNotEmpty()) hidden @endif>No hay recursos con ese filtro. Prueba con otra palabra o elige “Todas”.</p>
</div>
@endsection
