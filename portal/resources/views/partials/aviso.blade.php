{{-- Un aviso de la cartelera. Uso: @include('partials.aviso', ['novedad' => $novedad]) --}}
<li class="aviso">
    <time class="fecha" datetime="{{ $novedad->fecha_publicacion->toDateString() }}">
        <span class="fecha-dia">{{ $novedad->fecha_publicacion->format('j') }}</span>
        <span class="fecha-mes">{{ $novedad->fecha_publicacion->translatedFormat('M Y') }}</span>
    </time>
    <div>
        <span class="categoria {{ $novedad->claseCategoria() }}">{{ $novedad->categoria }}</span>
        <h3><a href="{{ route('novedades.show', $novedad) }}">{{ $novedad->titulo }}</a></h3>
        <p>{{ $novedad->resumen ?? \Illuminate\Support\Str::limit($novedad->contenido, 160) }}</p>
    </div>
</li>
