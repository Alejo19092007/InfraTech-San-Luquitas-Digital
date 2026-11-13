@extends('layouts.app')
@section('titulo', 'Novedades')

@section('contenido')
<div class="container page-intro">
    <span class="eyebrow">Siempre al día</span>
    <h1>Novedades de nuestra escuela</h1>
    <p>Información importante, actividades y logros de estudiantes y familias.</p>
</div>

<div class="container">
    {{-- La búsqueda y el filtro se resuelven en el servidor (PostgreSQL), así funcionan con la paginación --}}
    <form class="toolbar" method="GET" action="{{ route('novedades.index') }}" role="search">
        @if ($categoria)<input type="hidden" name="categoria" value="{{ $categoria }}">@endif
        <div class="search-box">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/></svg>
            <input type="search" name="buscar" value="{{ $buscar }}" placeholder="Buscar una novedad y presiona Enter..." aria-label="Buscar novedades">
        </div>
        @if (config('portal.edicion_abierta') || auth()->user()?->rol === 'admin')
            <a class="btn btn-primary" href="{{ route('novedades.create') }}">＋ Nueva novedad</a>
        @endif
    </form>

    <div class="filters" aria-label="Filtrar novedades">
        <a class="filter-chip" href="{{ route('novedades.index', array_filter(['buscar' => $buscar])) }}" aria-pressed="{{ $categoria ? 'false' : 'true' }}">Todas</a>
        @foreach (\App\Models\Novedad::CATEGORIAS as $cat)
            <a class="filter-chip" href="{{ route('novedades.index', array_filter(['categoria' => $cat, 'buscar' => $buscar])) }}" aria-pressed="{{ $categoria === $cat ? 'true' : 'false' }}">{{ $cat }}</a>
        @endforeach
    </div>

    <div class="news-grid">
        @forelse ($novedades as $novedad)
            <article class="card news-card">
                <div>
                    <span class="{{ $novedad->claseEtiqueta() }}">{{ $novedad->categoria }}</span>
                    <div class="card-meta">📅 {{ $novedad->fecha_publicacion->translatedFormat('j M Y') }}</div>
                    <h3>{{ $novedad->titulo }}</h3>
                    <p>{{ $novedad->resumen ?? \Illuminate\Support\Str::limit($novedad->contenido, 140) }}</p>
                </div>
                <div class="card-bottom"><a class="card-link" href="{{ route('novedades.show', $novedad) }}">Leer más →</a></div>
            </article>
        @empty
            <div class="empty-state">No encontramos novedades con esa búsqueda. Prueba con otra palabra o categoría.</div>
        @endforelse
    </div>

    {{ $novedades->links() }}
</div>
@endsection
