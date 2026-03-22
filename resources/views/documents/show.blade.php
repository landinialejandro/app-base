{{-- FILE: resources/views/documents/show.blade.php | V6 --}}

@extends('layouts.app')

@php
    use App\Support\Catalogs\DocumentCatalog;

    $items = $document->items->sortBy('position')->values();

    $documentDetailTitle = match ($document->kind) {
        DocumentCatalog::KIND_QUOTE => 'Detalle del presupuesto',
        DocumentCatalog::KIND_INVOICE => 'Detalle de la factura',
        DocumentCatalog::KIND_DELIVERY_NOTE => 'Detalle del remito',
        DocumentCatalog::KIND_WORK_ORDER => 'Detalle de la orden de trabajo',
        DocumentCatalog::KIND_RECEIPT => 'Detalle del recibo',
        DocumentCatalog::KIND_CREDIT_NOTE => 'Detalle de la nota de crédito',
        default => 'Detalle del documento',
    };

    $canUpdateDocument = auth()->user()->can('update', $document);
    $canDeleteDocument = auth()->user()->can('delete', $document);

    $contextRouteParams = $navigationContext
        ? ['context_type' => $navigationContext['type'], 'context_id' => $navigationContext['id']]
        : [];

    $documentLabel = $document->number ?: 'Documento #' . $document->id;
    $orderLabel = $document->order ? ($document->order->number ?: 'Orden #' . $document->order->id) : null;

    $breadcrumbItems = [['label' => 'Inicio', 'url' => route('dashboard')]];

    if (($navigationContext['type'] ?? null) === 'appointment' && $document->order) {
        $breadcrumbItems[] = ['label' => 'Turnos', 'url' => route('appointments.index')];
        $breadcrumbItems[] = ['label' => $navigationContext['label'], 'url' => $navigationContext['url']];
        $breadcrumbItems[] = [
            'label' => $orderLabel,
            'url' => route('orders.show', ['order' => $document->order] + $contextRouteParams),
        ];
        $breadcrumbItems[] = ['label' => $documentLabel];
    } elseif ($document->order) {
        $breadcrumbItems[] = ['label' => 'Órdenes', 'url' => route('orders.index')];
        $breadcrumbItems[] = [
            'label' => $orderLabel,
            'url' => route('orders.show', ['order' => $document->order] + $contextRouteParams),
        ];
        $breadcrumbItems[] = ['label' => $documentLabel];
    } else {
        $breadcrumbItems[] = ['label' => 'Documentos', 'url' => route('documents.index')];
        $breadcrumbItems[] = ['label' => $documentLabel];
    }

    $backUrl = $document->order
        ? route('orders.show', ['order' => $document->order] + $contextRouteParams)
        : (($navigationContext['type'] ?? null) === 'appointment'
            ? $navigationContext['url']
            : route('documents.index'));
@endphp

@section('title', $documentDetailTitle)

@section('content')
    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header :title="$documentDetailTitle">
            @if ($canUpdateDocument)
                <a href="{{ route('documents.edit', ['document' => $document] + $contextRouteParams) }}"
                    class="btn btn-primary">
                    <x-icons.pencil />
                    <span>Editar</span>
                </a>
            @endif

            @if ($canDeleteDocument)
                <form method="POST"
                    action="{{ route('documents.destroy', ['document' => $document] + $contextRouteParams) }}"
                    class="inline-form" data-action="app-confirm-submit"
                    data-confirm-message="{{ $items->count()
                        ? 'Este documento tiene ítems cargados. Si lo eliminas, también se eliminarán sus ítems. ¿Deseas continuar?'
                        : '¿Deseas eliminar este documento?' }}">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger">
                        <x-icons.trash />
                        <span>Eliminar</span>
                    </button>
                </form>
            @endif

            <a href="{{ $backUrl }}" class="btn btn-secondary">
                {{ $document->order ? 'Volver a la orden' : 'Volver' }}
            </a>
        </x-page-header>

        <x-card>
            <div class="summary-inline-grid">
                <div class="summary-inline-card">
                    <div class="summary-inline-label">Contacto</div>
                    <div class="summary-inline-value">{{ $document->party?->name ?: '—' }}</div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Fecha</div>
                    <div class="summary-inline-value">{{ $document->issued_at?->format('d/m/Y') ?: '—' }}</div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Número</div>
                    <div class="summary-inline-value">{{ $document->number ?: 'Sin número' }}</div>
                </div>
            </div>

            <div class="list-filters-actions">
                <button type="button" class="btn btn-secondary" data-action="app-toggle-details"
                    data-toggle-target="#document-more-detail" data-toggle-text-collapsed="Más detalle"
                    data-toggle-text-expanded="Menos detalle">
                    Más detalle
                </button>
            </div>

            <div id="document-more-detail" hidden>
                <div class="detail-grid detail-grid--3">
                    <div class="detail-block">
                        <span class="detail-block-label">Tipo</span>
                        <div class="detail-block-value">{{ DocumentCatalog::label($document->kind) }}</div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Estado</span>
                        <div class="detail-block-value">
                            <span class="status-badge {{ DocumentCatalog::badgeClass($document->status) }}">
                                {{ DocumentCatalog::statusLabel($document->status) }}
                            </span>
                        </div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Orden asociada</span>
                        <div class="detail-block-value">
                            @if ($document->order)
                                <a href="{{ route('orders.show', ['order' => $document->order] + $contextRouteParams) }}">
                                    {{ $orderLabel }}
                                </a>
                            @else
                                —
                            @endif
                        </div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Activo</span>
                        <div class="detail-block-value">
                            @if ($document->asset)
                                <a href="{{ route('assets.show', $document->asset) }}">
                                    {{ $document->asset->name }}
                                </a>
                            @else
                                —
                            @endif
                        </div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Fecha de vencimiento</span>
                        <div class="detail-block-value">{{ $document->due_at?->format('d/m/Y') ?: '—' }}</div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Moneda</span>
                        <div class="detail-block-value">{{ $document->currency_code ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </x-card>

        <div class="tabs" data-tabs>
            <div class="tabs-nav" role="tablist" aria-label="Secciones secundarias del documento">
                <button type="button" class="tabs-link is-active" data-tab-link="items" role="tab"
                    aria-selected="true">
                    Ítems
                    @if ($items->count())
                        ({{ $items->count() }})
                    @endif
                </button>

                <button type="button" class="tabs-link" data-tab-link="amounts" role="tab" aria-selected="false">
                    Importes
                </button>

                <button type="button" class="tabs-link" data-tab-link="trace" role="tab" aria-selected="false">
                    Notas y trazabilidad
                </button>
            </div>

            <section class="tab-panel is-active" data-tab-panel="items">
                <div class="tab-panel-stack">
                    <x-page-header title="Ítems del documento">
                        @if ($canUpdateDocument)
                            <a href="{{ route('documents.items.create', ['document' => $document] + $contextRouteParams) }}"
                                class="btn btn-primary">
                                Agregar ítem
                            </a>
                        @endif
                    </x-page-header>

                    <x-card class="list-card">
                        @include('documents.items.partials.table', [
                            'document' => $document,
                            'items' => $items,
                            'emptyMessage' => 'No hay ítems cargados en este documento.',
                            'contextRouteParams' => $contextRouteParams,
                        ])
                    </x-card>

                    <x-card>
                        <div class="summary-inline-grid">
                            <div class="summary-inline-card">
                                <div class="summary-inline-label">Cantidad de ítems</div>
                                <div class="summary-inline-value">{{ $items->count() }}</div>
                            </div>

                            <div class="summary-inline-card">
                                <div class="summary-inline-label">Total documento</div>
                                <div class="summary-inline-value">${{ number_format($document->total, 2, ',', '.') }}</div>
                            </div>
                        </div>
                    </x-card>
                </div>
            </section>

            <section class="tab-panel" data-tab-panel="amounts" hidden>
                <div class="tab-panel-stack">
                    <x-card>
                        <div class="detail-grid">
                            <div class="detail-block">
                                <span class="detail-block-label">Subtotal</span>
                                <div class="detail-block-value">${{ number_format($document->subtotal, 2, ',', '.') }}
                                </div>
                            </div>

                            <div class="detail-block">
                                <span class="detail-block-label">Impuestos</span>
                                <div class="detail-block-value">${{ number_format($document->tax_total, 2, ',', '.') }}
                                </div>
                            </div>

                            <div class="detail-block">
                                <span class="detail-block-label">Total</span>
                                <div class="detail-block-value">${{ number_format($document->total, 2, ',', '.') }}</div>
                            </div>
                        </div>
                    </x-card>
                </div>
            </section>

            <section class="tab-panel" data-tab-panel="trace" hidden>
                <div class="tab-panel-stack">
                    <x-card>
                        <div class="detail-grid">
                            <div class="detail-block detail-block--full">
                                <span class="detail-block-label">Notas</span>
                                <div class="detail-block-value">{{ $document->notes ?: '—' }}</div>
                            </div>

                            <div class="detail-block">
                                <span class="detail-block-label">Creado por</span>
                                <div class="detail-block-value">{{ $document->creator?->name ?: '—' }}</div>
                            </div>

                            <div class="detail-block">
                                <span class="detail-block-label">Actualizado por</span>
                                <div class="detail-block-value">{{ $document->updater?->name ?: '—' }}</div>
                            </div>

                            <div class="detail-block">
                                <span class="detail-block-label">Actualizado el</span>
                                <div class="detail-block-value">{{ $document->updated_at?->format('d/m/Y H:i') ?: '—' }}
                                </div>
                            </div>
                        </div>
                    </x-card>
                </div>
            </section>
        </div>
    </x-page>
@endsection
