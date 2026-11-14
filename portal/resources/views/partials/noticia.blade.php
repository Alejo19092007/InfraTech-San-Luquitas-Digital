{{-- Tarjeta de noticia con foto. Uso: @include('partials.noticia', ['novedad' => $novedad]) --}}
@php
    $f = config('portal.fotos.'.$novedad->foto());
@endphp
<li class="noticia">
    <a class="noticia-imagen" href="{{ route('novedades.show', $novedad) }}" tabindex="-1" aria-hidden="true">
        @if (file_exists(public_path($f['archivo'])))
            <img src="{{ asset($f['archivo']) }}" alt="" width="1024" height="683" loading="lazy" decoding="async">
        @else
            <span class="sin-foto"></span>
        @endif
    </a>
    <div class="noticia-cuerpo">
        <div class="noticia-meta">
            <span class="etiqueta {{ $novedad->claseCategoria() }}">{{ $novedad->categoria }}</span>
            <time datetime="{{ $novedad->fecha_publicacion->toDateString() }}">{{ $novedad->fecha_publicacion->translatedFormat('j \d\e F \d\e Y') }}</time>
        </div>
        <h3><a href="{{ route('novedades.show', $novedad) }}">{{ $novedad->titulo }}</a></h3>
        <p>{{ $novedad->resumen ?? \Illuminate\Support\Str::limit($novedad->contenido, 130) }}</p>
        <a class="leer" href="{{ route('novedades.show', $novedad) }}">Leer más<span class="oculto">: {{ $novedad->titulo }}</span></a>
    </div>
</li>
