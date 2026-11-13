@if ($paginator->hasPages())
<nav class="pagination" aria-label="Paginación">
    @if ($paginator->onFirstPage())
        <span class="disabled" aria-hidden="true">‹</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Página anterior">‹</a>
    @endif

    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="disabled">{{ $element }}</span>
        @endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" aria-label="Ir a la página {{ $page }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Página siguiente">›</a>
    @else
        <span class="disabled" aria-hidden="true">›</span>
    @endif
</nav>
@endif
