@if ($paginator->hasPages())
<nav class="paginacion" aria-label="Páginas de la cartelera">
    @if ($paginator->onFirstPage())
        <span class="desactivado" aria-hidden="true">Anterior</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>
    @endif

    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="desactivado">{{ $element }}</span>
        @endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" aria-label="Página {{ $page }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente</a>
    @else
        <span class="desactivado" aria-hidden="true">Siguiente</span>
    @endif
</nav>
@endif
