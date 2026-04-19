{{-- FILE: resources/views/assets/show.blade.php | V17 --}}

@extends('layouts.app')

@section('title', 'Detalle del activo')

@section('content')
    @php
        use App\Support\Assets\AssetSurfaceService;
        use App\Support\Auth\TenantModuleAccess;
        use App\Support\Catalogs\AssetCatalog;
        use App\Support\Catalogs\ModuleCatalog;
        use App\Support\Catalogs\OrderCatalog;
        use App\Support\Modules\ModuleSurfaceRegistry;
        use App\Support\Navigation\NavigationTrail;
        use App\Support\Parties\PartyLinkedAction;

        $orders = $orders ?? collect();
        $documents = $documents ?? collect();
        $attachments = $asset->attachments ?? collect();

        $tenant = app('tenant');

        $supportsPartiesModule = TenantModuleAccess::isEnabled(ModuleCatalog::PARTIES, $tenant);
        $supportsAppointmentsModule =
            $supportsAppointmentsModule ?? TenantModuleAccess::isEnabled(ModuleCatalog::APPOINTMENTS, $tenant);
        $supportsOrdersModule = TenantModuleAccess::isEnabled(ModuleCatalog::ORDERS, $tenant);
        $supportsDocumentsModule = TenantModuleAccess::isEnabled(ModuleCatalog::DOCUMENTS, $tenant);

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $backUrl = NavigationTrail::previousUrl($navigationTrail, route('assets.index'));

        $partyAction = PartyLinkedAction::forParty($asset->party, $trailQuery, 'Contacto');

        $hostPack = app(AssetSurfaceService::class)->hostPack('assets.show', $asset, [
            'trailQuery' => $trailQuery,
        ]);

        $embeddedTabs = $supportsAppointmentsModule
            ? collect(app(ModuleSurfaceRegistry::class)->embeddedFor('assets.show', $hostPack))->values()
            : collect();

        $embeddedDefaultTab = $embeddedTabs->first()['key'] ?? null;

        $defaultTab =
            $embeddedDefaultTab ?:
            ($supportsOrdersModule
                ? 'orders'
                : ($supportsDocumentsModule
                    ? 'documents'
                    : 'attachments'));
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Detalle del activo">

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

            <a href="{{ $backUrl }}" class="btn btn-secondary">
                <x-icons.chevron-left />
                <span>Volver</span>
            </a>
        </x-page-header>

        <x-show-summary details-id="asset-detail-panel">
            <x-show-summary-item label="Nombre">
                {{ $asset->name }}
            </x-show-summary-item>

            <x-show-summary-item label="Contacto">
                @include('parties.components.linked-party-action', [
                    'action' => $partyAction,
                    'variant' => 'summary',
                ])
            </x-show-summary-item>

            <x-show-summary-item label="Código interno">
                {{ $asset->internal_code ?: '—' }}
            </x-show-summary-item>

            <x-slot:details>
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

        <div class="tabs" data-tabs>
            <x-tab-toolbar label="Secciones del activo">
                <x-slot:tabs>
                    <x-horizontal-scroll label="Secciones del activo">
                        @foreach ($embeddedTabs as $embeddedTab)
                            <button type="button"
                                class="tabs-link {{ $defaultTab === $embeddedTab['key'] ? 'is-active' : '' }}"
                                data-tab-link="{{ $embeddedTab['key'] }}" role="tab"
                                aria-selected="{{ $defaultTab === $embeddedTab['key'] ? 'true' : 'false' }}">
                                {{ $embeddedTab['label'] ?? $embeddedTab['key'] }}
                                @if (array_key_exists('count', $embeddedTab) && (int) $embeddedTab['count'] > 0)
                                    ({{ $embeddedTab['count'] }})
                                @endif
                            </button>
                        @endforeach

                        @if ($supportsOrdersModule)
                            <button type="button" class="tabs-link {{ $defaultTab === 'orders' ? 'is-active' : '' }}"
                                data-tab-link="orders" role="tab"
                                aria-selected="{{ $defaultTab === 'orders' ? 'true' : 'false' }}">
                                Órdenes
                                @if ($orders->count())
                                    ({{ $orders->count() }})
                                @endif
                            </button>
                        @endif

                        @if ($supportsDocumentsModule)
                            <button type="button" class="tabs-link {{ $defaultTab === 'documents' ? 'is-active' : '' }}"
                                data-tab-link="documents" role="tab"
                                aria-selected="{{ $defaultTab === 'documents' ? 'true' : 'false' }}">
                                Documentos
                                @if ($documents->count())
                                    ({{ $documents->count() }})
                                @endif
                            </button>
                        @endif

                        <button type="button" class="tabs-link {{ $defaultTab === 'attachments' ? 'is-active' : '' }}"
                            data-tab-link="attachments" role="tab"
                            aria-selected="{{ $defaultTab === 'attachments' ? 'true' : 'false' }}">
                            Adjuntos
                            @if ($attachments->count())
                                ({{ $attachments->count() }})
                            @endif
                        </button>
                    </x-horizontal-scroll>
                </x-slot:tabs>
            </x-tab-toolbar>

            @foreach ($embeddedTabs as $embeddedTab)
                <section class="tab-panel {{ $defaultTab === $embeddedTab['key'] ? 'is-active' : '' }}"
                    data-tab-panel="{{ $embeddedTab['key'] }}" @if ($defaultTab !== $embeddedTab['key']) hidden @endif>
                    <div class="tab-panel-stack">
                        @include($embeddedTab['view'], $embeddedTab['data'] ?? [])
                    </div>
                </section>
            @endforeach

            @if ($supportsOrdersModule)
                <section class="tab-panel {{ $defaultTab === 'orders' ? 'is-active' : '' }}" data-tab-panel="orders"
                    @if ($defaultTab !== 'orders') hidden @endif>
                    <div class="tab-panel-stack">
                        @include('orders.partials.embedded-tabs', [
                            'orders' => $orders,
                            'showParty' => true,
                            'showAsset' => false,
                            'emptyMessage' => 'Este activo no tiene órdenes vinculadas.',
                            'tabsId' => 'asset-orders-tabs',
                            'createBaseQuery' => [
                                'asset_id' => $asset->id,
                                'kind' => OrderCatalog::KIND_SERVICE,
                            ],
                            'trailQuery' => $trailQuery,
                        ])
                    </div>
                </section>
            @endif

            @if ($supportsDocumentsModule)
                <section class="tab-panel {{ $defaultTab === 'documents' ? 'is-active' : '' }}" data-tab-panel="documents"
                    @if ($defaultTab !== 'documents') hidden @endif>
                    <div class="tab-panel-stack">
                        @include('documents.partials.embedded-tabs', [
                            'documents' => $documents,
                            'showParty' => true,
                            'showAsset' => false,
                            'showOrder' => true,
                            'emptyMessage' => 'Este activo no tiene documentos vinculados.',
                            'tabsId' => 'asset-documents-tabs',
                            'createBaseQuery' => [
                                'asset_id' => $asset->id,
                                'party_id' => $asset->party_id,
                            ],
                            'trailQuery' => $trailQuery,
                        ])
                    </div>
                </section>
            @endif

            <section class="tab-panel {{ $defaultTab === 'attachments' ? 'is-active' : '' }}" data-tab-panel="attachments"
                @if ($defaultTab !== 'attachments') hidden @endif>
                <div class="tab-panel-stack">
                    @include('attachments.partials.embedded', [
                        'attachments' => $attachments,
                        'attachable' => $asset,
                        'attachableType' => 'asset',
                        'attachableId' => $asset->id,
                        'trailQuery' => $trailQuery,
                        'navigationTrail' => $navigationTrail,
                        'tabsId' => 'asset-attachments-tabs',
                        'createLabel' => 'Agregar adjunto',
                    ])
                </div>
            </section>
        </div>

    </x-page>
@endsection
