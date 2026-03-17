@if ($paginator->hasPages())
    <nav class="app-pagination" role="navigation" aria-label="Paginación">
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
