{{-- FILE: resources/views/parties/show.blade.php --}}

@extends('layouts.app')

@section('title', 'Detalle del contacto')

@section('content')

    @php
        use App\Support\Catalogs\PartyCatalog;
        use App\Support\Catalogs\AssetCatalog;
        use App\Support\Catalogs\DocumentCatalog;

        $documents = $documents ?? collect();
        $assets = $assets ?? collect();

        $quotes = $documents->where('kind', DocumentCatalog::KIND_QUOTE)->values();
        $deliveryNotes = $documents->where('kind', DocumentCatalog::KIND_DELIVERY_NOTE)->values();
        $invoices = $documents->where('kind', DocumentCatalog::KIND_INVOICE)->values();
        $workOrders = $documents->where('kind', DocumentCatalog::KIND_WORK_ORDER)->values();
        $receipts = $documents->where('kind', DocumentCatalog::KIND_RECEIPT)->values();
        $creditNotes = $documents->where('kind', DocumentCatalog::KIND_CREDIT_NOTE)->values();
    @endphp

    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Contactos', 'url' => route('parties.index')],
            ['label' => $party->name],
        ]" />

        <x-page-header title="Detalle del contacto">
            <a href="{{ route('parties.edit', $party) }}" class="btn btn-primary">
                <x-icons.pencil />
                <span>Editar</span>
            </a>

            <form method="POST" action="{{ route('parties.destroy', $party) }}" class="inline-form"
                data-action="app-confirm-submit" data-confirm-message="¿Eliminar contacto?">
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-danger">
                    <x-icons.trash />
                    <span>Eliminar</span>
                </button>
            </form>

            <a href="{{ route('parties.index') }}" class="btn btn-secondary">
                Volver
            </a>
        </x-page-header>

        <x-card>
            <div class="summary-inline-grid">
                <div class="summary-inline-card">
                    <div class="summary-inline-label">Tipo</div>
                    <div class="summary-inline-value">{{ PartyCatalog::label($party->kind) }}</div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Nombre</div>
                    <div class="summary-inline-value">{{ $party->name }}</div>
                </div>
            </div>
        </x-card>

        <x-card>
            <div class="detail-grid detail-grid--3">
                <div class="detail-block">
                    <span class="detail-block-label">Nombre visible</span>
                    <div class="detail-block-value">{{ $party->display_name ?: '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Tipo documento</span>
                    <div class="detail-block-value">{{ $party->document_type ?: '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Número documento</span>
                    <div class="detail-block-value">{{ $party->document_number ?: '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">CUIT / Tax ID</span>
                    <div class="detail-block-value">{{ $party->tax_id ?: '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Email</span>
                    <div class="detail-block-value">{{ $party->email ?: '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Teléfono</span>
                    <div class="detail-block-value">{{ $party->phone ?: '—' }}</div>
                </div>

                <div class="detail-block detail-block--full">
                    <span class="detail-block-label">Dirección</span>
                    <div class="detail-block-value">{{ $party->address ?: '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Activo</span>
                    <div class="detail-block-value">{{ $party->is_active ? 'Sí' : 'No' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Creado</span>
                    <div class="detail-block-value">{{ $party->created_at?->format('d/m/Y H:i') ?: '—' }}</div>
                </div>

                <div class="detail-block">
                    <span class="detail-block-label">Actualizado</span>
                    <div class="detail-block-value">{{ $party->updated_at?->format('d/m/Y H:i') ?: '—' }}</div>
                </div>

                <div class="detail-block detail-block--full">
                    <span class="detail-block-label">Notas</span>
                    <div class="detail-block-value">{{ $party->notes ?: '—' }}</div>
                </div>
            </div>
        </x-card>

        <div class="tabs" data-tabs>
            <div class="tabs-nav" role="tablist" aria-label="Secciones del contacto">
                <button type="button" class="tabs-link is-active" data-tab-link="documents" role="tab"
                    aria-selected="true">
                    Documentos
                    @if ($documents->count())
                        ({{ $documents->count() }})
                    @endif
                </button>

                <button type="button" class="tabs-link" data-tab-link="assets" role="tab" aria-selected="false">
                    Activos
                    @if ($assets->count())
                        ({{ $assets->count() }})
                    @endif
                </button>
            </div>

            <section class="tab-panel is-active" data-tab-panel="documents">
                <div class="tab-panel-stack">

                    <div class="tabs" data-tabs>
                        <div class="tabs-nav" role="tablist" aria-label="Tipos de documentos del contacto">
                            <button type="button" class="tabs-link is-active" data-tab-link="documents-all" role="tab"
                                aria-selected="true">
                                Todos
                                @if ($documents->count())
                                    ({{ $documents->count() }})
                                @endif
                            </button>

                            <button type="button" class="tabs-link" data-tab-link="documents-quotes" role="tab"
                                aria-selected="false">
                                Presupuestos
                                @if ($quotes->count())
                                    ({{ $quotes->count() }})
                                @endif
                            </button>

                            <button type="button" class="tabs-link" data-tab-link="documents-delivery-notes" role="tab"
                                aria-selected="false">
                                Remitos
                                @if ($deliveryNotes->count())
                                    ({{ $deliveryNotes->count() }})
                                @endif
                            </button>

                            <button type="button" class="tabs-link" data-tab-link="documents-invoices" role="tab"
                                aria-selected="false">
                                Facturas
                                @if ($invoices->count())
                                    ({{ $invoices->count() }})
                                @endif
                            </button>

                            <button type="button" class="tabs-link" data-tab-link="documents-work-orders" role="tab"
                                aria-selected="false">
                                Órdenes de trabajo
                                @if ($workOrders->count())
                                    ({{ $workOrders->count() }})
                                @endif
                            </button>

                            @if ($receipts->count())
                                <button type="button" class="tabs-link" data-tab-link="documents-receipts"
                                    role="tab" aria-selected="false">
                                    Recibos ({{ $receipts->count() }})
                                </button>
                            @endif

                            @if ($creditNotes->count())
                                <button type="button" class="tabs-link" data-tab-link="documents-credit-notes"
                                    role="tab" aria-selected="false">
                                    Notas de crédito ({{ $creditNotes->count() }})
                                </button>
                            @endif
                        </div>

                        <section class="tab-panel is-active" data-tab-panel="documents-all">
                            <div class="tab-panel-stack">
                                <x-card class="list-card">
                                    @include('documents.partials.embedded-table', [
                                        'documents' => $documents,
                                        'showParty' => false,
                                        'showAsset' => true,
                                        'showOrder' => true,
                                        'emptyMessage' => 'Este contacto no tiene documentos vinculados.',
                                    ])
                                </x-card>
                            </div>
                        </section>

                        <section class="tab-panel" data-tab-panel="documents-quotes" hidden>
                            <div class="tab-panel-stack">
                                <x-card class="list-card">
                                    @include('documents.partials.embedded-table', [
                                        'documents' => $quotes,
                                        'showParty' => false,
                                        'showAsset' => true,
                                        'showOrder' => true,
                                        'emptyMessage' => 'Este contacto no tiene presupuestos vinculados.',
                                    ])
                                </x-card>
                            </div>
                        </section>

                        <section class="tab-panel" data-tab-panel="documents-delivery-notes" hidden>
                            <div class="tab-panel-stack">
                                <x-card class="list-card">
                                    @include('documents.partials.embedded-table', [
                                        'documents' => $deliveryNotes,
                                        'showParty' => false,
                                        'showAsset' => true,
                                        'showOrder' => true,
                                        'emptyMessage' => 'Este contacto no tiene remitos vinculados.',
                                    ])
                                </x-card>
                            </div>
                        </section>

                        <section class="tab-panel" data-tab-panel="documents-invoices" hidden>
                            <div class="tab-panel-stack">
                                <x-card class="list-card">
                                    @include('documents.partials.embedded-table', [
                                        'documents' => $invoices,
                                        'showParty' => false,
                                        'showAsset' => true,
                                        'showOrder' => true,
                                        'emptyMessage' => 'Este contacto no tiene facturas vinculadas.',
                                    ])
                                </x-card>
                            </div>
                        </section>

                        <section class="tab-panel" data-tab-panel="documents-work-orders" hidden>
                            <div class="tab-panel-stack">
                                <x-card class="list-card">
                                    @include('documents.partials.embedded-table', [
                                        'documents' => $workOrders,
                                        'showParty' => false,
                                        'showAsset' => true,
                                        'showOrder' => true,
                                        'emptyMessage' => 'Este contacto no tiene órdenes de trabajo vinculadas.',
                                    ])
                                </x-card>
                            </div>
                        </section>

                        @if ($receipts->count())
                            <section class="tab-panel" data-tab-panel="documents-receipts" hidden>
                                <div class="tab-panel-stack">
                                    <x-card class="list-card">
                                        @include('documents.partials.embedded-table', [
                                            'documents' => $receipts,
                                            'showParty' => false,
                                            'showAsset' => true,
                                            'showOrder' => true,
                                            'emptyMessage' => 'Este contacto no tiene recibos vinculados.',
                                        ])
                                    </x-card>
                                </div>
                            </section>
                        @endif

                        @if ($creditNotes->count())
                            <section class="tab-panel" data-tab-panel="documents-credit-notes" hidden>
                                <div class="tab-panel-stack">
                                    <x-card class="list-card">
                                        @include('documents.partials.embedded-table', [
                                            'documents' => $creditNotes,
                                            'showParty' => false,
                                            'showAsset' => true,
                                            'showOrder' => true,
                                            'emptyMessage' =>
                                                'Este contacto no tiene notas de crédito vinculadas.',
                                        ])
                                    </x-card>
                                </div>
                            </section>
                        @endif
                    </div>

                </div>
            </section>

            <section class="tab-panel" data-tab-panel="assets" hidden>
                <div class="tab-panel-stack">

                    <x-card>
                        <div class="dashboard-section-header">
                            <h2 class="dashboard-section-title">Activos vinculados</h2>
                            <p class="dashboard-section-text">
                                Activos actualmente asociados a este contacto.
                            </p>
                        </div>

                        @if ($assets->count())
                            <div class="table-wrap list-scroll">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Tipo</th>
                                            <th>Relación</th>
                                            <th>Código</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($assets as $asset)
                                            <tr>
                                                <td>{{ $asset->id }}</td>
                                                <td>
                                                    <a href="{{ route('assets.show', $asset) }}">
                                                        {{ $asset->name }}
                                                    </a>
                                                </td>
                                                <td>{{ AssetCatalog::kindLabel($asset->kind) }}</td>
                                                <td>{{ AssetCatalog::relationshipTypeLabel($asset->relationship_type) }}
                                                </td>
                                                <td>{{ $asset->internal_code ?? '—' }}</td>
                                                <td>
                                                    <span
                                                        class="status-badge {{ AssetCatalog::badgeClass($asset->status) }}">
                                                        {{ AssetCatalog::statusLabel($asset->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="mb-0">Este contacto no tiene activos vinculados.</p>
                        @endif
                    </x-card>

                </div>
            </section>
        </div>

    </x-page>
@endsection
