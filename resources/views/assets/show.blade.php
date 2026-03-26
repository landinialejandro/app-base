{{-- FILE: resources/views/assets/show.blade.php | V8 --}}

@extends('layouts.app')

@section('title', 'Detalle del activo')

@section('content')
    @php
        use App\Support\Catalogs\AssetCatalog;
        use App\Support\Catalogs\OrderCatalog;
        use App\Support\Navigation\NavigationTrail;

        $orders = $orders ?? collect();
        $documents = $documents ?? collect();
        $attachments = $asset->attachments ?? collect();

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $backUrl = NavigationTrail::previousUrl($navigationTrail, route('assets.index'));
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

            @can('create', App\Models\Appointment::class)
                <a href="{{ route('appointments.create', ['asset_id' => $asset->id, 'party_id' => $asset->party_id] + $trailQuery) }}"
                    class="btn btn-secondary">
                    <x-icons.plus />
                    <span>Agendar turno</span>
                </a>
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
                @if ($asset->party)
                    <a href="{{ route('parties.show', ['party' => $asset->party] + $trailQuery) }}">
                        {{ $asset->party->name }}
                    </a>
                @else
                    —
                @endif
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
                        <button type="button" class="tabs-link is-active" data-tab-link="orders" role="tab"
                            aria-selected="true">
                            Órdenes
                            @if ($orders->count())
                                ({{ $orders->count() }})
                            @endif
                        </button>

                        <button type="button" class="tabs-link" data-tab-link="documents" role="tab"
                            aria-selected="false">
                            Documentos
                            @if ($documents->count())
                                ({{ $documents->count() }})
                            @endif
                        </button>

                        <button type="button" class="tabs-link" data-tab-link="attachments" role="tab"
                            aria-selected="false">
                            Adjuntos
                            @if ($attachments->count())
                                ({{ $attachments->count() }})
                            @endif
                        </button>
                    </x-horizontal-scroll>
                </x-slot:tabs>
            </x-tab-toolbar>

            <section class="tab-panel" data-tab-panel="documents" hidden>
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

            <section class="tab-panel" data-tab-panel="attachments" hidden>
                <div class="tab-panel-stack">
                    <x-tab-toolbar label="Acciones de adjuntos del activo">
                        <x-slot:tabs>
                            <span class="tab-toolbar-title">Adjuntos del activo</span>
                        </x-slot:tabs>

                        <x-slot:actions>
                            <a href="{{ route(
                                'attachments.create',
                                [
                                    'attachable_type' => 'asset',
                                    'attachable_id' => $asset->id,
                                    'return_to' => url()->current(),
                                ] + $trailQuery,
                            ) }}"
                                class="btn btn-success">
                                <x-icons.plus />
                                <span>Agregar adjunto</span>
                            </a>
                        </x-slot:actions>
                    </x-tab-toolbar>

                    <x-card class="list-card">
                        @include('attachments.partials.table', [
                            'attachments' => $attachments,
                            'trailQuery' => $trailQuery,
                            'returnTo' => url()->current(),
                        ])
                    </x-card>
                </div>
            </section>

            <section class="tab-panel is-active" data-tab-panel="orders">
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
        </div>

    </x-page>
@endsection
