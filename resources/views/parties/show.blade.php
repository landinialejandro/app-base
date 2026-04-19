{{-- FILE: resources/views/parties/show.blade.php | V13 --}}

@extends('layouts.app')

@section('title', 'Detalle del contacto')

@section('content')
    @php
        use App\Support\Catalogs\PartyCatalog;
        use App\Support\Modules\ModuleSurfaceRegistry;
        use App\Support\Navigation\NavigationTrail;
        use App\Support\Parties\PartySurfaceService;

        $assets = $assets ?? collect();
        $orders = $orders ?? collect();
        $documents = $documents ?? collect();

        $supportsAppointmentsModule = $supportsAppointmentsModule ?? false;
        $supportsAssetsModule = $supportsAssetsModule ?? false;
        $supportsOrdersModule = $supportsOrdersModule ?? false;
        $supportsDocumentsModule = $supportsDocumentsModule ?? false;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $backUrl = NavigationTrail::previousUrl($navigationTrail, route('parties.index'));

        $hostPack = app(PartySurfaceService::class)->hostPack('parties.show', $party, [
            'trailQuery' => $trailQuery,
        ]);

        $embeddedTabs = $supportsAppointmentsModule
            ? collect(app(ModuleSurfaceRegistry::class)->embeddedFor('parties.show', $hostPack))->values()
            : collect();

        $embeddedDefaultTab = $embeddedTabs->first()['key'] ?? null;

        $defaultTab =
            $embeddedDefaultTab ?:
            ($supportsAssetsModule
                ? 'assets'
                : ($supportsOrdersModule
                    ? 'orders'
                    : ($supportsDocumentsModule
                        ? 'documents'
                        : null)));
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Detalle del contacto">
            @can('update', $party)
                <a href="{{ route('parties.edit', ['party' => $party] + $trailQuery) }}" class="btn btn-primary">
                    <x-icons.pencil />
                    <span>Editar</span>
                </a>
            @endcan

            @can('delete', $party)
                <form method="POST" action="{{ route('parties.destroy', ['party' => $party] + $trailQuery) }}" class="inline-form"
                    data-action="app-confirm-submit" data-confirm-message="¿Eliminar contacto?">
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

        <x-show-summary details-id="party-detail-panel">
            <x-show-summary-item label="Nombre">
                {{ $party->name }}
            </x-show-summary-item>

            <x-show-summary-item label="Teléfono">
                {{ $party->phone ?: '—' }}
            </x-show-summary-item>

            <x-show-summary-item label="Email">
                {{ $party->email ?: '—' }}
            </x-show-summary-item>

            <x-slot:details>
                <x-show-summary-item-detail-block label="Tipo">
                    {{ PartyCatalog::label($party->kind) }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Nombre visible">
                    {{ $party->display_name ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Tipo documento">
                    {{ $party->document_type ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Número documento">
                    {{ $party->document_number ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="CUIT / Tax ID">
                    {{ $party->tax_id ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Activo">
                    <span class="status-badge {{ $party->is_active ? 'status-badge--done' : 'status-badge--cancelled' }}">
                        {{ $party->is_active ? 'Sí' : 'No' }}
                    </span>
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Dirección" full>
                    {{ $party->address ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Creado">
                    {{ $party->created_at?->format('d/m/Y H:i') ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Actualizado">
                    {{ $party->updated_at?->format('d/m/Y H:i') ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Notas" full>
                    {{ $party->notes ?: '—' }}
                </x-show-summary-item-detail-block>
            </x-slot:details>
        </x-show-summary>

        @if ($defaultTab)
            <div class="tabs" data-tabs>
                <x-tab-toolbar label="Relaciones del contacto">
                    <x-slot:tabs>
                        <x-horizontal-scroll label="Relaciones del contacto">
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

                            @if ($supportsAssetsModule)
                                <button type="button" class="tabs-link {{ $defaultTab === 'assets' ? 'is-active' : '' }}"
                                    data-tab-link="assets" role="tab"
                                    aria-selected="{{ $defaultTab === 'assets' ? 'true' : 'false' }}">
                                    Activos
                                    @if ($assets->count())
                                        ({{ $assets->count() }})
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
                                <button type="button"
                                    class="tabs-link {{ $defaultTab === 'documents' ? 'is-active' : '' }}"
                                    data-tab-link="documents" role="tab"
                                    aria-selected="{{ $defaultTab === 'documents' ? 'true' : 'false' }}">
                                    Documentos
                                    @if ($documents->count())
                                        ({{ $documents->count() }})
                                    @endif
                                </button>
                            @endif
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

                @if ($supportsAssetsModule)
                    <section class="tab-panel {{ $defaultTab === 'assets' ? 'is-active' : '' }}" data-tab-panel="assets"
                        @if ($defaultTab !== 'assets') hidden @endif>
                        <div class="tab-panel-stack">
                            @include('assets.partials.embedded-tabs', [
                                'assets' => $assets,
                                'showParty' => false,
                                'emptyMessage' => 'Este contacto no tiene activos vinculados.',
                                'tabsId' => 'party-assets-tabs',
                                'createBaseQuery' => [
                                    'party_id' => $party->id,
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
                                'showParty' => false,
                                'showAsset' => true,
                                'emptyMessage' => 'Este contacto no tiene órdenes vinculadas.',
                                'tabsId' => 'party-orders-tabs',
                                'createBaseQuery' => [
                                    'party_id' => $party->id,
                                ],
                                'trailQuery' => $trailQuery,
                            ])
                        </div>
                    </section>
                @endif

                @if ($supportsDocumentsModule)
                    <section class="tab-panel {{ $defaultTab === 'documents' ? 'is-active' : '' }}"
                        data-tab-panel="documents" @if ($defaultTab !== 'documents') hidden @endif>
                        <div class="tab-panel-stack">
                            @include('documents.partials.embedded-tabs', [
                                'documents' => $documents,
                                'showParty' => false,
                                'showAsset' => true,
                                'showOrder' => true,
                                'emptyMessage' => 'Este contacto no tiene documentos vinculados.',
                                'tabsId' => 'party-documents-tabs',
                                'createBaseQuery' => [
                                    'party_id' => $party->id,
                                ],
                                'trailQuery' => $trailQuery,
                            ])
                        </div>
                    </section>
                @endif
            </div>
        @endif

    </x-page>
@endsection
