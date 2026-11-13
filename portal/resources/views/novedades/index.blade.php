@extends('layouts.app')
@section('titulo', 'Cartelera')

@section('contenido')
<div class="envoltura">
    <header class="cabeza-pagina">
        <h1>Cartelera</h1>
        <p>Avisos, eventos y noticias de las tres sedes, del más reciente al más antiguo.</p>
    </header>

    <div class="herramientas">
        {{-- Búsqueda y filtro en el servidor (PostgreSQL): funcionan con la paginación y sin JavaScript --}}
        <form class="buscar" method="GET" action="{{ route('novedades.index') }}" role="search">
            @if ($categoria)<input type="hidden" name="categoria" value="{{ $categoria }}">@endif
            <label class="oculto" for="buscar">Buscar en la cartelera</label>
            <input id="buscar" type="search" name="buscar" value="{{ $buscar }}" placeholder="Buscar un aviso">
            <button class="boton" type="submit">Buscar</button>
        </form>
        @if (config('portal.edicion_abierta') || auth()->user()?->rol === 'admin')
            <a class="boton boton-maiz" href="{{ route('novedades.create') }}">Publicar un aviso</a>
        @endif
    </div>

    <ul class="filtros" aria-label="Filtrar por categoría">
        <li><a class="filtro" href="{{ route('novedades.index', array_filter(['buscar' => $buscar])) }}" @if (! $categoria) aria-current="true" @endif>Todas</a></li>
        @foreach (\App\Models\Novedad::CATEGORIAS as $cat)
            <li><a class="filtro" href="{{ route('novedades.index', array_filter(['categoria' => $cat, 'buscar' => $buscar])) }}" @if ($categoria === $cat) aria-current="true" @endif>{{ $cat }}</a></li>
        @endforeach
    </ul>

    <ol class="cartelera">
        @forelse ($novedades as $novedad)
            @include('partials.aviso', ['novedad' => $novedad])
        @empty
            <li class="vacio">
                @if ($buscar !== '' || $categoria)
                    No hay avisos que coincidan. <a href="{{ route('novedades.index') }}">Ver todos los avisos</a>.
                @else
                    Todavía no hay avisos publicados.
                @endif
            </li>
        @endforelse
    </ol>

    {{ $novedades->links() }}
</div>
@endsection
