@if ($paginator->hasPages())
    <nav aria-label="Paginación">
        <ul class="paginacion">
            @if (! $paginator->onFirstPage())
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev">« Anterior</a></li>
            @endif
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span>{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li aria-current="page"><span>{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente »</a></li>
            @endif
        </ul>
    </nav>
@endif
