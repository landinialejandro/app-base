{{-- FILE: resources/views/assets/show.blade.php | V15 --}}

@extends('layouts.app')

@section('title', 'Detalle del activo')

@section('content')
    @php
        use App\Models\Appointment;
        use App\Support\Auth\TenantModuleAccess;
        use App\Support\Catalogs\AssetCatalog;
        use App\Support\Catalogs\ModuleCatalog;
        use App\Support\Catalogs\OrderCatalog;
        use App\Support\Navigation\NavigationTrail;
        use App\Support\Parties\PartyLinkedAction;

        $appointments = $appointments ?? collect();
        $orders = $orders ?? collect();
        $documents = $documents ?? collect();
        $attachments = $asset->attachments ?? collect();

        $tenant = app('tenant');
        $user = auth()->user();

        $supportsPartiesModule = TenantModuleAccess::isEnabled(ModuleCatalog::PARTIES, $tenant);
        $supportsAppointmentsModule =
            $supportsAppointmentsModule ?? TenantModuleAccess::isEnabled(ModuleCatalog::APPOINTMENTS, $tenant);
        $supportsOrdersModule = TenantModuleAccess::isEnabled(ModuleCatalog::ORDERS, $tenant);
        $supportsDocumentsModule = TenantModuleAccess::isEnabled(ModuleCatalog::DOCUMENTS, $tenant);

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $backUrl = NavigationTrail::previousUrl($navigationTrail, route('assets.index'));

        $canCreateAppointment = $supportsAppointmentsModule && $user && $user->can('create', Appointment::class);

        $partyAction = PartyLinkedAction::forParty($asset->party, $trailQuery, 'Contacto');

        $defaultTab = $supportsAppointmentsModule
            ? 'appointments'
            : ($supportsOrdersModule
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

            @if ($canCreateAppointment)
                <a href="{{ route('appointments.create', ['asset_id' => $asset->id, 'party_id' => $asset->party_id] + $trailQuery) }}"
                    class="btn btn-secondary">
                    <x-icons.plus />
                    <span>Agendar turno</span>
                </a>
            @endif

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
                        @if ($supportsAppointmentsModule)
                            <button type="button"
                                class="tabs-link {{ $defaultTab === 'appointments' ? 'is-active' : '' }}"
                                data-tab-link="appointments" role="tab"
                                aria-selected="{{ $defaultTab === 'appointments' ? 'true' : 'false' }}">
                                Turnos
                                @if ($appointments->count())
                                    ({{ $appointments->count() }})
                                @endif
                            </button>
                        @endif

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

            @if ($supportsAppointmentsModule)
                <section class="tab-panel {{ $defaultTab === 'appointments' ? 'is-active' : '' }}"
                    data-tab-panel="appointments" @if ($defaultTab !== 'appointments') hidden @endif>
                    <div class="tab-panel-stack">
                        @include('appointments.partials.embedded-tabs', [
                            'appointments' => $appointments,
                            'supportsPartiesModule' => $supportsPartiesModule,
                            'supportsAssetsModule' => false,
                            'supportsOrdersModule' => $supportsOrdersModule,
                            'emptyMessage' => 'Este activo no tiene turnos vinculados.',
                            'tabsId' => 'asset-appointments-tabs',
                            'createBaseQuery' => [
                                'asset_id' => $asset->id,
                                'party_id' => $asset->party_id,
                            ],
                            'trailQuery' => $trailQuery,
                        ])
                    </div>
                </section>
            @endif

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
