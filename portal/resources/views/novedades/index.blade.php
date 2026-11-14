@extends('layouts.app')
@section('titulo', 'Noticias')

@section('contenido')
<div class="envoltura">
    <p class="miga">Estás en: <a href="{{ route('inicio') }}">Inicio</a> / Noticias</p>
    <header class="cabeza-pagina">
        <h1>Noticias</h1>
        <p>Avisos, eventos y novedades de las tres sedes.</p>
    </header>

    <div class="herramientas">
        {{-- Búsqueda y filtro en el servidor (PostgreSQL): funcionan con la paginación y sin JavaScript --}}
        <form class="buscar" method="GET" action="{{ route('novedades.index') }}" role="search">
            @if ($categoria)<input type="hidden" name="categoria" value="{{ $categoria }}">@endif
            <label class="oculto" for="buscar">Buscar noticias</label>
            <input id="buscar" type="search" name="buscar" value="{{ $buscar }}" placeholder="Buscar noticias">
            <button class="boton" type="submit">Buscar</button>
        </form>
        @if (config('portal.edicion_abierta') || auth()->user()?->rol === 'admin')
            <a class="boton boton-amarillo" href="{{ route('novedades.create') }}">Publicar noticia</a>
        @endif
    </div>

    <ul class="filtros" aria-label="Filtrar por categoría">
        <li><a class="filtro" href="{{ route('novedades.index', array_filter(['buscar' => $buscar])) }}" @if (! $categoria) aria-current="true" @endif>Todas</a></li>
        @foreach (\App\Models\Novedad::CATEGORIAS as $cat)
            <li><a class="filtro" href="{{ route('novedades.index', array_filter(['categoria' => $cat, 'buscar' => $buscar])) }}" @if ($categoria === $cat) aria-current="true" @endif>{{ $cat }}</a></li>
        @endforeach
    </ul>

    <ul class="noticias">
        @forelse ($novedades as $novedad)
            @include('partials.noticia', ['novedad' => $novedad])
        @empty
            <li class="vacio">
                @if ($buscar !== '' || $categoria)
                    No hay noticias que coincidan con la búsqueda. <a href="{{ route('novedades.index') }}">Ver todas las noticias</a>.
                @else
                    Todavía no hay noticias publicadas.
                @endif
            </li>
        @endforelse
    </ul>

    {{ $novedades->links() }}
</div>
<div class="seccion"></div>
@endsection
