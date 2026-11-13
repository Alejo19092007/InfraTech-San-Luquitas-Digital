@extends('layouts.app')
@section('titulo', $novedad->titulo)

@section('contenido')
<div class="container">
    <div class="breadcrumb"><a href="{{ route('novedades.index') }}">Novedades</a> / Detalle</div>
    <article class="article">
        <span class="{{ $novedad->claseEtiqueta() }}">{{ $novedad->categoria }}</span>
        <div class="card-meta">📅 {{ $novedad->fecha_publicacion->translatedFormat('j \d\e F \d\e Y') }} · <span>Comunidad educativa</span></div>
        <h1>{{ $novedad->titulo }}</h1>
        @if ($novedad->resumen)
            <p class="article-summary">{{ $novedad->resumen }}</p>
        @endif
        <div class="article-body">
            @foreach (preg_split('/\R{2,}/', trim($novedad->contenido)) as $parrafo)
                <p>{!! nl2br(e($parrafo)) !!}</p>
            @endforeach
        </div>
        <div class="article-actions">
            <a class="btn btn-outline" href="{{ route('novedades.index') }}">← Volver a novedades</a>
            @if (config('portal.edicion_abierta') || auth()->user()?->rol === 'admin')
                <a class="btn btn-primary" href="{{ route('novedades.edit', $novedad) }}">✎ Editar</a>
                <form class="inline-form" action="{{ route('novedades.destroy', $novedad) }}" method="POST" data-confirm-delete>
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger" type="submit">⌫ Eliminar</button>
                </form>
            @endif
        </div>
    </article>
</div>
@endsection
