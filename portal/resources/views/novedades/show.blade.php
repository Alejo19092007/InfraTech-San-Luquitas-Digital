@extends('layouts.app')
@section('titulo', $novedad->titulo)
@section('descripcion', $novedad->resumen ?? \Illuminate\Support\Str::limit($novedad->contenido, 150))

@section('contenido')
<div class="envoltura">
    <p class="miga">Estás en: <a href="{{ route('inicio') }}">Inicio</a> / <a href="{{ route('novedades.index') }}">Noticias</a> / {{ \Illuminate\Support\Str::limit($novedad->titulo, 50) }}</p>

    <div class="articulo-rejilla">
        <article class="articulo">
            <div class="meta">
                <span class="etiqueta {{ $novedad->claseCategoria() }}">{{ $novedad->categoria }}</span>
                <time datetime="{{ $novedad->fecha_publicacion->toDateString() }}">Publicado el {{ $novedad->fecha_publicacion->translatedFormat('j \d\e F \d\e Y') }}</time>
            </div>
            <h1>{{ $novedad->titulo }}</h1>
            @if ($novedad->resumen)
                <p class="resumen">{{ $novedad->resumen }}</p>
            @endif

            @include('partials.foto', ['clave' => $novedad->foto(), 'carga' => 'eager'])

            <div class="cuerpo">
                @foreach (preg_split('/\R{2,}/', trim($novedad->contenido)) as $parrafo)
                    <p>{!! nl2br(e($parrafo)) !!}</p>
                @endforeach
            </div>

            <div class="acciones">
                <a class="boton boton-borde" href="{{ route('novedades.index') }}">Volver a noticias</a>
                @if (config('portal.edicion_abierta') || auth()->user()?->rol === 'admin')
                    <a class="boton" href="{{ route('novedades.edit', $novedad) }}">Editar</a>
                    <form method="POST" action="{{ route('novedades.destroy', $novedad) }}" data-confirmar="¿Eliminar la noticia «{{ $novedad->titulo }}»? Esta acción no se puede deshacer.">
                        @csrf
                        @method('DELETE')
                        <button class="boton boton-peligro" type="submit">Eliminar</button>
                    </form>
                @endif
            </div>
        </article>

        <aside class="lateral" aria-label="Otras noticias">
            <h2>Otras noticias</h2>
            <ul>
                @forelse ($recientes as $otra)
                    <li>
                        <a href="{{ route('novedades.show', $otra) }}">{{ $otra->titulo }}</a>
                        <time datetime="{{ $otra->fecha_publicacion->toDateString() }}">{{ $otra->fecha_publicacion->translatedFormat('j \d\e F \d\e Y') }}</time>
                    </li>
                @empty
                    <li>No hay más noticias.</li>
                @endforelse
            </ul>
        </aside>
    </div>
</div>
@endsection
