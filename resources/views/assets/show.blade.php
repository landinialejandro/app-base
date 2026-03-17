{{-- FILE: resources/views/assets/show.blade.php --}}

@extends('layouts.app')

@section('title', 'Detalle del activo')

@section('content')

    @php
        use App\Support\Catalogs\AssetCatalog;

        $orders = $orders ?? collect();
        $documents = $documents ?? collect();
    @endphp

    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Activos', 'url' => route('assets.index')],
            ['label' => $asset->name],
        ]" />

        <x-page-header title="Detalle del activo">
            <a href="{{ route('orders.create', ['asset_id' => $asset->id]) }}" class="btn btn-secondary">
                Nueva orden
            </a>

            <a href="{{ route('assets.edit', $asset) }}" class="btn btn-primary">
                <x-icons.pencil />
                <span>Editar</span>
            </a>

            <form method="POST" action="{{ route('assets.destroy', $asset) }}" class="inline-form"
                data-action="app-confirm-submit" data-confirm-message="¿Eliminar activo?">
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-danger">
                    <x-icons.trash />
                    <span>Eliminar</span>
                </button>
            </form>

            <a href="{{ route('assets.index') }}" class="btn btn-secondary">
                Volver
            </a>
        </x-page-header>

        <x-card>
            <div class="summary-inline-grid">
                <div class="summary-inline-card">
                    <div class="summary-inline-label">Nombre</div>
                    <div class="summary-inline-value">{{ $asset->name }}</div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Contacto</div>
                    <div class="summary-inline-value">
                        @if ($asset->party)
                            <a href="{{ route('parties.show', $asset->party) }}">
                                {{ $asset->party->name }}
                            </a>
                        @else
                            —
                        @endif
                    </div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Código interno</div>
                    <div class="summary-inline-value">{{ $asset->internal_code ?: '—' }}</div>
                </div>
            </div>

            <div class="list-filters-actions">
                <button type="button" class="btn btn-secondary" data-action="app-toggle-details"
                    data-toggle-target="#asset-detail-panel" data-toggle-text-expanded="Ocultar detalle"
                    data-toggle-text-collapsed="Más detalle">
                    Más detalle
                </button>
            </div>

            <div id="asset-detail-panel" hidden>
                <div class="detail-grid detail-grid--3">
                    <div class="detail-block">
                        <span class="detail-block-label">Tipo</span>
                        <div class="detail-block-value">{{ AssetCatalog::kindLabel($asset->kind) }}</div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Estado</span>
                        <div class="detail-block-value">
                            <span class="status-badge {{ AssetCatalog::badgeClass($asset->status) }}">
                                {{ AssetCatalog::statusLabel($asset->status) }}
                            </span>
                        </div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Relación</span>
                        <div class="detail-block-value">
                            {{ AssetCatalog::relationshipTypeLabel($asset->relationship_type) }}
                        </div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Creado</span>
                        <div class="detail-block-value">{{ $asset->created_at?->format('d/m/Y H:i') ?: '—' }}</div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Actualizado</span>
                        <div class="detail-block-value">{{ $asset->updated_at?->format('d/m/Y H:i') ?: '—' }}</div>
                    </div>

                    <div class="detail-block detail-block--full">
                        <span class="detail-block-label">Notas</span>
                        <div class="detail-block-value">{{ $asset->notes ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </x-card>

        <div class="tabs" data-tabs>
            <div class="tabs-nav" role="tablist" aria-label="Secciones del activo">
                <button type="button" class="tabs-link" data-tab-link="documents" role="tab" aria-selected="false">
                    Documentos
                    @if ($documents->count())
                        ({{ $documents->count() }})
                    @endif
                </button>

                <button type="button" class="tabs-link is-active" data-tab-link="orders" role="tab"
                    aria-selected="true">
                    Órdenes
                    @if ($orders->count())
                        ({{ $orders->count() }})
                    @endif
                </button>
            </div>

            <section class="tab-panel" data-tab-panel="documents" hidden>
                <div class="tab-panel-stack">
                    @include('documents.partials.embedded-tabs', [
                        'documents' => $documents,
                        'showParty' => true,
                        'showAsset' => false,
                        'showOrder' => true,
                        'emptyMessage' => 'Este activo no tiene documentos vinculados.',
                    ])
                </div>
            </section>

            <section class="tab-panel is-active" data-tab-panel="orders">
                <div class="tab-panel-stack">
                    @include('orders.partials.embedded-tabs', [
                        'orders' => $orders,
                        'showParty' => true,
                        'showAsset' => false,
                        'emptyMessage' => 'Este activo no tiene órdenes vinculadas.',
                    ])
                </div>
            </section>
        </div>

    </x-page>
@endsection
