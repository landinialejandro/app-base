@if ($paginator->hasPages())
    <nav class="app-pagination" role="navigation" aria-label="Paginación">
        <div class="app-pagination-summary">
            <span>
                Mostrando
                <strong>{{ $paginator->firstItem() ?? 0 }}</strong>
                a
                <strong>{{ $paginator->lastItem() ?? 0 }}</strong>
                de
                <strong>{{ $paginator->total() }}</strong>
                resultados
            </span>
        </div>

        <div class="app-pagination-links">
            @if ($paginator->onFirstPage())
                <span class="app-pagination-link is-disabled" aria-disabled="true" aria-label="Anterior">
                    <x-icons.chevron-left />
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="app-pagination-link" rel="prev"
                    aria-label="Anterior">
                    <x-icons.chevron-left />
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="app-pagination-link is-disabled" aria-disabled="true">
                        {{ $element }}
                    </span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="app-pagination-link is-active" aria-current="page">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="app-pagination-link"
                                aria-label="Ir a la página {{ $page }}">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="app-pagination-link" rel="next"
                    aria-label="Siguiente">
                    <x-icons.chevron-right />
                </a>
            @else
                <span class="app-pagination-link is-disabled" aria-disabled="true" aria-label="Siguiente">
                    <x-icons.chevron-right />
                </span>
            @endif
        </div>
    </nav>
@endif
