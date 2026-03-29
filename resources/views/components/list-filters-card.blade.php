{{-- FILE: resources/views/components/list-filters-card.blade.php | V3 --}}

@props([
    'action',
    'method' => 'GET',
    'secondaryId' => 'extra-filters-' . uniqid(),
    'toggleLabel' => 'Más filtros',
    'toggleLabelExpanded' => 'Menos filtros',
    'clearUrl' => null,
])

<x-card {{ $attributes->class(['list-card']) }}>
    <form method="{{ strtoupper($method) === 'GET' ? 'GET' : 'POST' }}" action="{{ $action }}"
        class="form list-filters-shell">
        @if (strtoupper($method) !== 'GET')
            @csrf
        @endif

        <div class="list-filters-layout">
            <div class="list-filters-main">
                @isset($primary)
                    <div class="list-filters-primary">
                        {{ $primary }}
                    </div>
                @endisset

                @isset($secondary)
                    <div id="{{ $secondaryId }}" class="list-filters-secondary" hidden>
                        {{ $secondary }}
                    </div>
                @endisset
            </div>

            <div class="list-filters-side">
                <button type="submit" class="btn btn-primary">Filtrar</button>

                <a href="{{ $clearUrl ?: $action }}" class="btn btn-secondary">Limpiar</a>

                @isset($secondary)
                    <button type="button" class="btn btn-secondary" data-action="app-toggle-details"
                        data-toggle-target="#{{ $secondaryId }}" data-toggle-text-collapsed="{{ $toggleLabel }}"
                        data-toggle-text-expanded="{{ $toggleLabelExpanded }}">
                        {{ $toggleLabel }}
                    </button>
                @endisset
            </div>
        </div>
    </form>
</x-card>
