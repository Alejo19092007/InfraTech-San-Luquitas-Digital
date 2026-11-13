@extends('layouts.app')
@section('titulo', $novedad->titulo)
@section('descripcion', $novedad->resumen ?? \Illuminate\Support\Str::limit($novedad->contenido, 150))

@section('contenido')
<div class="envoltura">
    <article class="articulo">
        <p class="miga"><a href="{{ route('novedades.index') }}">Cartelera</a> / {{ $novedad->categoria }}</p>
        <div class="meta">
            <span class="categoria {{ $novedad->claseCategoria() }}">{{ $novedad->categoria }}</span>
            <time datetime="{{ $novedad->fecha_publicacion->toDateString() }}">{{ $novedad->fecha_publicacion->translatedFormat('j \d\e F \d\e Y') }}</time>
        </div>
        <h1>{{ $novedad->titulo }}</h1>
        @if ($novedad->resumen)
            <p class="resumen">{{ $novedad->resumen }}</p>
        @endif
        <div class="cuerpo">
            @foreach (preg_split('/\R{2,}/', trim($novedad->contenido)) as $parrafo)
                <p>{!! nl2br(e($parrafo)) !!}</p>
            @endforeach
        </div>

        <div class="acciones">
            <a class="boton boton-borde" href="{{ route('novedades.index') }}">Volver a la cartelera</a>
            @if (config('portal.edicion_abierta') || auth()->user()?->rol === 'admin')
                <a class="boton" href="{{ route('novedades.edit', $novedad) }}">Editar aviso</a>
                <form method="POST" action="{{ route('novedades.destroy', $novedad) }}" data-confirmar="¿Eliminar el aviso «{{ $novedad->titulo }}»? No se puede deshacer.">
                    @csrf
                    @method('DELETE')
                    <button class="boton boton-peligro" type="submit">Eliminar aviso</button>
                </form>
            @endif
        </div>
    </article>
</div>
@endsection
