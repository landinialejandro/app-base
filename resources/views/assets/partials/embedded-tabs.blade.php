{{-- FILE: resources/views/assets/partials/embedded-tabs.blade.php --}}

@php
    use App\Support\Catalogs\AssetCatalog;

    $assets = $assets ?? collect();

    $showParty = $showParty ?? false;
    $emptyMessage = $emptyMessage ?? 'No hay activos para mostrar.';
    $allLabel = $allLabel ?? 'Todos';

    $kinds = AssetCatalog::kindLabels();
    $tabsId = $tabsId ?? 'assets-tabs-' . uniqid();
@endphp

<div class="tabs" data-tabs>
    <div class="tabs-nav" role="tablist" aria-label="Tipos de activos">
        <button type="button" class="tabs-link is-active" data-tab-link="{{ $tabsId }}-all" role="tab"
            aria-selected="true">
            {{ $allLabel }}
            @if ($assets->count())
                ({{ $assets->count() }})
            @endif
        </button>

        @foreach ($kinds as $value => $label)
            @php
                $kindAssets = $assets->where('kind', $value)->values();
            @endphp

            <button type="button" class="tabs-link" data-tab-link="{{ $tabsId }}-{{ $value }}"
                role="tab" aria-selected="false">
                {{ $label }}
                @if ($kindAssets->count())
                    ({{ $kindAssets->count() }})
                @endif
            </button>
        @endforeach
    </div>

    <section class="tab-panel is-active" data-tab-panel="{{ $tabsId }}-all">
        <div class="tab-panel-stack">
            <x-card class="list-card">
                @include('assets.partials.table', [
                    'assets' => $assets,
                    'showParty' => $showParty,
                    'emptyMessage' => $emptyMessage,
                ])
            </x-card>
        </div>
    </section>

    @foreach ($kinds as $value => $label)
        @php
            $kindAssets = $assets->where('kind', $value)->values();
        @endphp

        <section class="tab-panel" data-tab-panel="{{ $tabsId }}-{{ $value }}" hidden>
            <div class="tab-panel-stack">
                <x-card class="list-card">
                    @include('assets.partials.table', [
                        'assets' => $kindAssets,
                        'showParty' => $showParty,
                        'emptyMessage' => "No hay activos de tipo {$label} para mostrar.",
                    ])
                </x-card>
            </div>
        </section>
    @endforeach
</div>
