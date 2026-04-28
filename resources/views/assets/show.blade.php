{{-- FILE: resources/views/assets/show.blade.php | V19 --}}

@extends('layouts.app')

@section('title', 'Detalle del activo')

@section('content')
    @php
        use App\Support\Modules\ModuleSurfaceRegistry;
        use App\Support\Navigation\NavigationTrail;
        use App\Support\Catalogs\AssetCatalog;
        use App\Support\Assets\AssetSurfaceService;
        use App\Support\Ui\HostTabs;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $backUrl = NavigationTrail::previousUrl($navigationTrail, route('assets.index'));
        $tabsLabel = 'Secciones del activo';

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

        $activeTab = HostTabs::activeKey($tabItems, request()->query('return_tab'));
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Detalle del activo">
            @foreach ($headerActions as $surface)
                @include($surface['view'], $surface['data'] ?? [])
            @endforeach

            @can('update', $asset)
                <x-button-edit :href="route('assets.edit', ['asset' => $asset] + $trailQuery)" />
            @endcan

            @can('delete', $asset)
                <x-button-delete :action="route('assets.destroy', ['asset' => $asset] + $trailQuery)" message="¿Eliminar activo?" />
            @endcan

            <x-button-back :href="$backUrl" />
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

        <x-host-tabs :items="$tabItems" :active-tab="$activeTab" :label="$tabsLabel" />
    </x-page>
@endsection
