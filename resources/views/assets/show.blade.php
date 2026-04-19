{{-- FILE: resources/views/assets/show.blade.php | V19 --}}

@extends('layouts.app')

@section('title', 'Detalle del activo')

@section('content')
    @php
        use App\Support\Assets\AssetSurfaceService;
        use App\Support\Catalogs\AssetCatalog;
        use App\Support\Modules\ModuleSurfaceRegistry;
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $backUrl = NavigationTrail::previousUrl($navigationTrail, route('assets.index'));

        $hostPack = app(AssetSurfaceService::class)->hostPack('assets.show', $asset, [
            'trailQuery' => $trailQuery,
        ]);

        $embedded = collect(app(ModuleSurfaceRegistry::class)->embeddedFor('assets.show', $hostPack))->values();
        $linked = collect(app(ModuleSurfaceRegistry::class)->linkedFor('assets.show', $hostPack))->values();

        $headerActions = $linked->where('slot', 'header_actions')->values();
        $summaryItems = $linked->where('slot', 'summary_items')->values();
        $detailItems = $embedded->where('slot', 'detail_items')->values();
        $tabItems = $embedded
            ->where(fn($item) => ($item['slot'] ?? null) === 'tab_panels')
            ->sortBy(fn(array $item) => $item['priority'] ?? 999)
            ->values();
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Detalle del activo">
            @foreach ($headerActions as $surface)
                @include($surface['view'], $surface['data'] ?? [])
            @endforeach

            @can('update', $asset)
                <a href="{{ route('assets.edit', ['asset' => $asset] + $trailQuery) }}" class="btn btn-primary">
                    <x-icons.pencil />
                    <span>Editar</span>
                </a>
            @endcan

            @can('delete', $asset)
                <form method="POST" action="{{ route('assets.destroy', ['asset' => $asset] + $trailQuery) }}" class="inline-form"
                    data-action="app-confirm-submit" data-confirm-message="¿Eliminar activo?">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger">
                        <x-icons.trash />
                        <span>Eliminar</span>
                    </button>
                </form>
            @endcan

            <a href="{{ $backUrl }}" class="btn btn-secondary btn-icon" title="Volver" aria-label="Volver">
                <x-icons.chevron-left />
            </a>
        </x-page-header>

        <x-show-summary details-id="asset-detail-panel">
            @foreach ($summaryItems as $surface)
                <x-show-summary-item :label="$surface['label'] ?? 'Relacionado'">
                    @include($surface['view'], $surface['data'] ?? [])
                </x-show-summary-item>
            @endforeach

            <x-show-summary-item label="Nombre">
                {{ $asset->name }}
            </x-show-summary-item>

            <x-show-summary-item label="Código interno">
                {{ $asset->internal_code ?: '—' }}
            </x-show-summary-item>

            <x-slot:details>
                @foreach ($detailItems as $surface)
                    <x-show-summary-item-detail-block :label="$surface['label'] ?? 'Relacionado'">
                        @include($surface['view'], $surface['data'] ?? [])
                    </x-show-summary-item-detail-block>
                @endforeach

                <x-show-summary-item-detail-block label="Tipo">
                    {{ AssetCatalog::kindLabel($asset->kind) }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Estado">
                    <span class="status-badge {{ AssetCatalog::badgeClass($asset->status) }}">
                        {{ AssetCatalog::statusLabel($asset->status) }}
                    </span>
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Relación">
                    {{ AssetCatalog::relationshipTypeLabel($asset->relationship_type) }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Creado">
                    {{ $asset->created_at?->format('d/m/Y H:i') ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Actualizado">
                    {{ $asset->updated_at?->format('d/m/Y H:i') ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Notas" full>
                    {{ $asset->notes ?: '—' }}
                </x-show-summary-item-detail-block>
            </x-slot:details>
        </x-show-summary>

        @if ($tabItems->isNotEmpty())
            <div class="tabs" data-tabs>
                <x-tab-toolbar label="Secciones del activo">
                    <x-slot:tabs>
                        <x-horizontal-scroll label="Secciones del activo">
                            @foreach ($tabItems as $tabItem)
                                <button type="button" class="tabs-link {{ $loop->first ? 'is-active' : '' }}"
                                    data-tab-link="{{ $tabItem['key'] }}" role="tab"
                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                    {{ $tabItem['label'] ?? $tabItem['key'] }}

                                    @if (array_key_exists('count', $tabItem) && (int) $tabItem['count'] > 0)
                                        ({{ $tabItem['count'] }})
                                    @endif
                                </button>
                            @endforeach
                        </x-horizontal-scroll>
                    </x-slot:tabs>
                </x-tab-toolbar>

                @foreach ($tabItems as $tabItem)
                    <section class="tab-panel {{ $loop->first ? 'is-active' : '' }}"
                        data-tab-panel="{{ $tabItem['key'] }}" @unless ($loop->first) hidden @endunless>
                        <div class="tab-panel-stack">
                            @include($tabItem['view'], $tabItem['data'] ?? [])
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </x-page>
@endsection
