{{-- Foto ilustrativa con su crédito. Uso: @include('partials.foto', ['clave' => 'a-clase']) --}}
@php
    $f = config('portal.fotos.'.$clave);
@endphp
<figure class="foto">
    <div class="foto-marco">
        @if (file_exists(public_path($f['archivo'])))
            <img src="{{ asset($f['archivo']) }}" alt="{{ $f['alt'] }}" width="1024" height="683" loading="{{ $carga ?? 'lazy' }}" decoding="async">
        @else
            <span class="foto-falta"><span>Foto pendiente de descarga: ejecuta <code>scripts/descargar-fotos.sh</code> en el servidor.</span></span>
        @endif
    </div>
    <figcaption>{{ $f['pie'] }} Foto: <a href="{{ $f['fuente'] }}" rel="noopener">{{ $f['autor'] }}</a>, {{ $f['licencia'] }}. Imagen ilustrativa.</figcaption>
</figure>
