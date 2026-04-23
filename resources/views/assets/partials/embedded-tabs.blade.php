{{-- FILE: resources/views/assets/partials/embedded-tabs.blade.php | V3 --}}

@php
    use App\Support\Catalogs\AssetCatalog;

    $assets = $assets ?? collect();

    $showParty = $showParty ?? false;
    $emptyMessage = $emptyMessage ?? 'No hay activos para mostrar.';
    $allLabel = $allLabel ?? 'Todos';

    $kinds = AssetCatalog::kindLabels();
    $tabsId = $tabsId ?? 'assets-tabs-' . uniqid();
    $trailQuery = $trailQuery ?? [];
    $createBaseQuery = $createBaseQuery ?? [];
@endphp

<div class="tabs" data-tabs>
    @php
        $toolbarAction = null;
    @endphp

    @can('create', App\Models\Asset::class)
        @php
            $toolbarAction = route('assets.create', $createBaseQuery + $trailQuery);
        @endphp
    @endcan

    <x-tab-toolbar label="Tipos de activos">
        <x-slot:tabs>
            <x-horizontal-scroll label="Tipos de activos">
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
            </x-horizontal-scroll>
        </x-slot:tabs>

        <x-slot:actions>
            @if ($toolbarAction)
                <x-button-create :href="$toolbarAction" label="Nuevo activo" class="btn-sm" />
            @endif
        </x-slot:actions>
    </x-tab-toolbar>

    <section class="tab-panel is-active" data-tab-panel="{{ $tabsId }}-all">
        <div class="tab-panel-stack">
            <x-card class="list-card">
                @include('assets.partials.table', [
                    'assets' => $assets,
                    'showParty' => $showParty,
                    'emptyMessage' => $emptyMessage,
                    'trailQuery' => $trailQuery,
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
                        'trailQuery' => $trailQuery,
                    ])
                </x-card>
            </div>
        </section>
    @endforeach
</div>
