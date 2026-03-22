{{-- FILE: resources/views/orders/show.blade.php | V3 --}}

@extends('layouts.app')

@section('title', 'Detalle de la orden')

@section('content')
    @php
        use App\Support\Catalogs\DocumentCatalog;
        use App\Support\Catalogs\OrderCatalog;

        $items = $order->items->sortBy('position')->values();
        $documents = $order->documents->sortByDesc('id')->values();

        $orderDetailTitle = match ($order->kind) {
            OrderCatalog::KIND_SALE => 'Detalle de la orden de venta',
            OrderCatalog::KIND_PURCHASE => 'Detalle de la orden de compra',
            OrderCatalog::KIND_SERVICE => 'Detalle de la orden de servicio',
            default => 'Detalle de la orden',
        };

        $quoteCount = $documents->where('kind', DocumentCatalog::KIND_QUOTE)->count();
        $deliveryNoteCount = $documents->where('kind', DocumentCatalog::KIND_DELIVERY_NOTE)->count();
        $invoiceCount = $documents->where('kind', DocumentCatalog::KIND_INVOICE)->count();
        $workOrderCount = $documents->where('kind', DocumentCatalog::KIND_WORK_ORDER)->count();

        $contextRouteParams = $navigationContext
            ? ['context_type' => $navigationContext['type'], 'context_id' => $navigationContext['id']]
            : [];

        $orderLabel = $order->number ?: 'Orden #' . $order->id;

        $breadcrumbItems = [['label' => 'Inicio', 'url' => route('dashboard')]];

        if (($navigationContext['type'] ?? null) === 'appointment') {
            $breadcrumbItems[] = ['label' => 'Turnos', 'url' => route('appointments.index')];
            $breadcrumbItems[] = ['label' => $navigationContext['label'], 'url' => $navigationContext['url']];
            $breadcrumbItems[] = ['label' => $orderLabel];
        } else {
            $breadcrumbItems[] = ['label' => 'Órdenes', 'url' => route('orders.index')];
            $breadcrumbItems[] = ['label' => $orderLabel];
        }

        $backUrl =
            ($navigationContext['type'] ?? null) === 'appointment' ? $navigationContext['url'] : route('orders.index');
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header :title="$orderDetailTitle">
            @can('update', $order)
                <a href="{{ route('orders.edit', ['order' => $order] + $contextRouteParams) }}" class="btn btn-primary">
                    <x-icons.pencil />
                    <span>Editar</span>
                </a>
            @endcan

            @can('delete', $order)
                <form method="POST" action="{{ route('orders.destroy', ['order' => $order] + $contextRouteParams) }}"
                    class="inline-form" data-action="app-confirm-submit"
                    data-confirm-message="{{ $items->count()
                        ? 'Esta orden tiene ítems cargados. Si la eliminas, también se eliminarán sus ítems. ¿Deseas continuar?'
                        : '¿Deseas eliminar esta orden?' }}">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger">
                        <x-icons.trash />
                        <span>Eliminar</span>
                    </button>
                </form>
            @endcan

            @if ($order->task)
                <a href="{{ route('tasks.show', $order->task) }}" class="btn btn-secondary">
                    Ver tarea
                </a>
            @endif

            <a href="{{ $backUrl }}" class="btn btn-secondary">
                {{ ($navigationContext['type'] ?? null) === 'appointment' ? 'Volver al turno' : 'Volver' }}
            </a>
        </x-page-header>

        <x-card>
            <div class="summary-inline-grid">
                <div class="summary-inline-card">
                    <div class="summary-inline-label">Contacto</div>
                    <div class="summary-inline-value">{{ $order->party?->name ?: '—' }}</div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Fecha</div>
                    <div class="summary-inline-value">{{ $order->ordered_at?->format('d/m/Y') ?: '—' }}</div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Número</div>
                    <div class="summary-inline-value">{{ $order->number ?: 'Sin número' }}</div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Tarea origen</div>
                    <div class="summary-inline-value">
                        @if ($order->task)
                            <a href="{{ route('tasks.show', $order->task) }}">
                                {{ $order->task->name }}
                            </a>
                        @else
                            —
                        @endif
                    </div>
                </div>
            </div>

            <div class="list-filters-actions">
                <button type="button" class="btn btn-secondary" data-action="app-toggle-details"
                    data-toggle-target="#order-more-detail" data-toggle-text-collapsed="Más detalle"
                    data-toggle-text-expanded="Menos detalle">
                    Más detalle
                </button>
            </div>

            <div id="order-more-detail" hidden>
                <div class="detail-grid detail-grid--3">
                    <div class="detail-block">
                        <span class="detail-block-label">Tipo</span>
                        <div class="detail-block-value">{{ OrderCatalog::label($order->kind) }}</div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Estado</span>
                        <div class="detail-block-value">
                            <span class="status-badge {{ OrderCatalog::badgeClass($order->status) }}">
                                {{ OrderCatalog::statusLabel($order->status) }}
                            </span>
                        </div>
                    </div>

                    <div class="detail-block">
                        <span class="detail-block-label">Activo</span>
                        <div class="detail-block-value">
                            @if ($order->asset)
                                <a href="{{ route('assets.show', $order->asset) }}">
                                    {{ $order->asset->name }}
                                </a>
                            @else
                                —
                            @endif
                        </div>
                    </div>

                    <div class="detail-block detail-block--full">
                        <span class="detail-block-label">Notas</span>
                        <div class="detail-block-value">{{ $order->notes ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </x-card>

        <div class="tabs" data-tabs>
            <div class="tabs-nav" role="tablist" aria-label="Secciones secundarias de la orden">
                <button type="button" class="tabs-link is-active" data-tab-link="items" role="tab"
                    aria-selected="true">
                    Ítems
                    @if ($items->count())
                        ({{ $items->count() }})
                    @endif
                </button>

                <button type="button" class="tabs-link" data-tab-link="documents" role="tab" aria-selected="false">
                    Documentos
                    @if ($documents->count())
                        ({{ $documents->count() }})
                    @endif
                </button>
            </div>

            <section class="tab-panel is-active" data-tab-panel="items">
                <div class="tab-panel-stack">
                    <x-page-header title="Ítems de la orden">
                        @can('update', $order)
                            <a href="{{ route('orders.items.create', ['order' => $order] + $contextRouteParams) }}"
                                class="btn btn-primary">
                                Agregar ítem
                            </a>
                        @endcan
                    </x-page-header>

                    <x-card class="list-card">
                        @include('orders.items.partials.table', [
                            'order' => $order,
                            'items' => $items,
                            'emptyMessage' => 'No hay ítems cargados en esta orden.',
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
                                <div class="summary-inline-label">Total orden</div>
                                <div class="summary-inline-value">${{ number_format($order->total, 2, ',', '.') }}</div>
                            </div>
                        </div>
                    </x-card>
                </div>
            </section>

            <section class="tab-panel" data-tab-panel="documents" hidden>
                <div class="tab-panel-stack">
                    <x-page-header title="Documentos de la orden">
                        <form method="POST"
                            action="{{ route('orders.documents.store', ['order' => $order] + $contextRouteParams) }}"
                            class="inline-form"
                            @if ($quoteCount > 0) data-action="app-confirm-submit"
                            data-confirm-message="Esta orden ya tiene {{ $quoteCount }} presupuesto(s) asociado(s). ¿Deseas crear otro?" @endif>
                            @csrf
                            <input type="hidden" name="kind" value="{{ DocumentCatalog::KIND_QUOTE }}">
                            <button type="submit" class="btn btn-secondary">
                                {{ $quoteCount > 0 ? 'Crear otro presupuesto' : 'Crear presupuesto' }}
                            </button>
                        </form>

                        <form method="POST"
                            action="{{ route('orders.documents.store', ['order' => $order] + $contextRouteParams) }}"
                            class="inline-form"
                            @if ($deliveryNoteCount > 0) data-action="app-confirm-submit"
                            data-confirm-message="Esta orden ya tiene {{ $deliveryNoteCount }} remito(s) asociado(s). ¿Deseas crear otro?" @endif>
                            @csrf
                            <input type="hidden" name="kind" value="{{ DocumentCatalog::KIND_DELIVERY_NOTE }}">
                            <button type="submit" class="btn btn-secondary">
                                {{ $deliveryNoteCount > 0 ? 'Crear otro remito' : 'Crear remito' }}
                            </button>
                        </form>

                        <form method="POST"
                            action="{{ route('orders.documents.store', ['order' => $order] + $contextRouteParams) }}"
                            class="inline-form"
                            @if ($invoiceCount > 0) data-action="app-confirm-submit"
                            data-confirm-message="Esta orden ya tiene {{ $invoiceCount }} factura(s) asociada(s). ¿Deseas crear otra?" @endif>
                            @csrf
                            <input type="hidden" name="kind" value="{{ DocumentCatalog::KIND_INVOICE }}">
                            <button type="submit" class="btn btn-secondary">
                                {{ $invoiceCount > 0 ? 'Crear otra factura' : 'Crear factura' }}
                            </button>
                        </form>

                        <form method="POST"
                            action="{{ route('orders.documents.store', ['order' => $order] + $contextRouteParams) }}"
                            class="inline-form"
                            @if ($workOrderCount > 0) data-action="app-confirm-submit"
                            data-confirm-message="Esta orden ya tiene {{ $workOrderCount }} orden(es) de trabajo asociada(s). ¿Deseas crear otra?" @endif>
                            @csrf
                            <input type="hidden" name="kind" value="{{ DocumentCatalog::KIND_WORK_ORDER }}">
                            <button type="submit" class="btn btn-secondary">
                                {{ $workOrderCount > 0 ? 'Crear otra orden de trabajo' : 'Crear orden de trabajo' }}
                            </button>
                        </form>
                    </x-page-header>

                    @include('documents.partials.embedded-tabs', [
                        'documents' => $documents,
                        'showParty' => false,
                        'showAsset' => false,
                        'showOrder' => false,
                        'emptyMessage' => 'No hay documentos asociados para mostrar.',
                        'allLabel' => 'Todos',
                        'tabsId' => 'order-documents-tabs',
                        'contextRouteParams' => $contextRouteParams,
                    ])
                </div>
            </section>
        </div>
    </x-page>
@endsection
