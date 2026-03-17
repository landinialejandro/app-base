{{-- FILE: resources/views/orders/show.blade.php --}}

@extends('layouts.app')

@section('title', 'Detalle de la orden')

@section('content')
    @php
        use App\Support\Catalogs\OrderCatalog;
        use App\Support\Catalogs\ProductCatalog;
        use App\Support\Catalogs\DocumentCatalog;

        $items = $order->items->sortBy('position');
        $documents = $order->documents->sortByDesc('id');

        $quotes = $documents->where('kind', DocumentCatalog::KIND_QUOTE)->values();
        $deliveryNotes = $documents->where('kind', DocumentCatalog::KIND_DELIVERY_NOTE)->values();
        $invoices = $documents->where('kind', DocumentCatalog::KIND_INVOICE)->values();
        $workOrders = $documents->where('kind', DocumentCatalog::KIND_WORK_ORDER)->values();
        $receipts = $documents->where('kind', DocumentCatalog::KIND_RECEIPT)->values();
        $creditNotes = $documents->where('kind', DocumentCatalog::KIND_CREDIT_NOTE)->values();

        $workOrderCount = $workOrders->count();
        $quoteCount = $quotes->count();
        $deliveryNoteCount = $deliveryNotes->count();
        $invoiceCount = $invoices->count();
    @endphp

    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Órdenes', 'url' => route('orders.index')],
            ['label' => $order->number ?: 'Sin número'],
        ]" />

        <x-page-header title="Detalle de la orden">
            <a href="{{ route('orders.edit', $order) }}" class="btn btn-primary">
                <x-icons.pencil />
                <span>Editar</span>
            </a>

            <form method="POST" action="{{ route('orders.documents.store', $order) }}" class="inline-form"
                @if ($quoteCount > 0) data-action="app-confirm-submit"
                data-confirm-message="Esta orden ya tiene {{ $quoteCount }} presupuesto(s) asociado(s). ¿Deseas crear otro?" @endif>
                @csrf
                <input type="hidden" name="kind" value="{{ DocumentCatalog::KIND_QUOTE }}">
                <button type="submit" class="btn btn-secondary">
                    {{ $quoteCount > 0 ? 'Crear otro presupuesto' : 'Crear presupuesto' }}
                </button>
            </form>

            <form method="POST" action="{{ route('orders.documents.store', $order) }}" class="inline-form"
                @if ($deliveryNoteCount > 0) data-action="app-confirm-submit"
                data-confirm-message="Esta orden ya tiene {{ $deliveryNoteCount }} remito(s) asociado(s). ¿Deseas crear otro?" @endif>
                @csrf
                <input type="hidden" name="kind" value="{{ DocumentCatalog::KIND_DELIVERY_NOTE }}">
                <button type="submit" class="btn btn-secondary">
                    {{ $deliveryNoteCount > 0 ? 'Crear otro remito' : 'Crear remito' }}
                </button>
            </form>

            <form method="POST" action="{{ route('orders.documents.store', $order) }}" class="inline-form"
                @if ($invoiceCount > 0) data-action="app-confirm-submit"
                data-confirm-message="Esta orden ya tiene {{ $invoiceCount }} factura(s) asociada(s). ¿Deseas crear otra?" @endif>
                @csrf
                <input type="hidden" name="kind" value="{{ DocumentCatalog::KIND_INVOICE }}">
                <button type="submit" class="btn btn-secondary">
                    {{ $invoiceCount > 0 ? 'Crear otra factura' : 'Crear factura' }}
                </button>
            </form>

            <form method="POST" action="{{ route('orders.documents.store', $order) }}" class="inline-form"
                @if ($workOrderCount > 0) data-action="app-confirm-submit"
                data-confirm-message="Esta orden ya tiene {{ $workOrderCount }} orden(es) de trabajo asociada(s). ¿Deseas crear otra?" @endif>
                @csrf
                <input type="hidden" name="kind" value="{{ DocumentCatalog::KIND_WORK_ORDER }}">
                <button type="submit" class="btn btn-secondary">
                    {{ $workOrderCount > 0 ? 'Crear otra orden de trabajo' : 'Crear orden de trabajo' }}
                </button>
            </form>

            <form method="POST" action="{{ route('orders.destroy', $order) }}" class="inline-form"
                data-action="app-confirm-submit"
                data-confirm-message="{{ $order->items->count()
                    ? 'Esta orden tiene ítems cargados. Si la eliminas, también se eliminarán sus ítems. ¿Deseas continuar?'
                    : '¿Deseas eliminar esta orden?' }}">
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-danger">
                    <x-icons.trash />
                    <span>Eliminar</span>
                </button>
            </form>

            <a href="{{ route('orders.index') }}" class="btn btn-secondary">
                Volver
            </a>
        </x-page-header>

        <x-card>
            <div class="summary-inline-grid">
                <div class="summary-inline-card">
                    <div class="summary-inline-label">Tipo</div>
                    <div class="summary-inline-value">{{ OrderCatalog::label($order->kind) }}</div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Número</div>
                    <div class="summary-inline-value">{{ $order->number ?: 'Sin número' }}</div>
                </div>
            </div>
        </x-card>

        <div class="tabs" data-tabs>
            <div class="tabs-nav" role="tablist" aria-label="Secciones de la orden">
                <button type="button" class="tabs-link is-active" data-tab-link="detail" role="tab"
                    aria-selected="true">
                    Detalle
                </button>

                <button type="button" class="tabs-link" data-tab-link="documents" role="tab" aria-selected="false">
                    Documentos
                    @if ($documents->count())
                        ({{ $documents->count() }})
                    @endif
                </button>
            </div>

            <section class="tab-panel is-active" data-tab-panel="detail">
                <div class="tab-panel-stack">

                    <x-page-header title="Ítems de la orden">
                        <a href="{{ route('orders.items.create', $order) }}" class="btn btn-primary">
                            Agregar ítem
                        </a>
                    </x-page-header>

                    <x-card class="list-card">
                        @if ($items->count())
                            <div class="table-wrap list-scroll">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Posición</th>
                                            <th>Tipo</th>
                                            <th>Descripción</th>
                                            <th>Cantidad</th>
                                            <th>Precio unitario</th>
                                            <th>Total línea</th>
                                            <th class="compact-actions-cell">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($items as $item)
                                            <tr>
                                                <td>{{ $item->position }}</td>
                                                <td>{{ ProductCatalog::kindLabel($item->kind) }}</td>
                                                <td>{{ $item->description }}</td>
                                                <td>{{ number_format($item->quantity, 2, ',', '.') }}</td>
                                                <td>${{ number_format($item->unit_price, 2, ',', '.') }}</td>
                                                <td>${{ number_format($item->line_total, 2, ',', '.') }}</td>
                                                <td class="compact-actions-cell">
                                                    <div class="compact-actions">
                                                        <a href="{{ route('orders.items.edit', [$order, $item]) }}"
                                                            class="btn btn-secondary btn-icon" title="Editar ítem"
                                                            aria-label="Editar ítem">
                                                            <x-icons.pencil />
                                                        </a>

                                                        <form method="POST"
                                                            action="{{ route('orders.items.destroy', [$order, $item]) }}"
                                                            class="inline-form" data-action="app-confirm-submit"
                                                            data-confirm-message="¿Deseas eliminar este ítem?">
                                                            @csrf
                                                            @method('DELETE')

                                                            <button type="submit" class="btn btn-danger btn-icon"
                                                                title="Eliminar ítem" aria-label="Eliminar ítem">
                                                                <x-icons.trash />
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="mb-0">No hay ítems cargados en esta orden.</p>
                        @endif
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

                    <div class="tabs" data-tabs>
                        <div class="tabs-nav" role="tablist" aria-label="Tipos de documentos de la orden">
                            <button type="button" class="tabs-link is-active" data-tab-link="documents-all"
                                role="tab" aria-selected="true">
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

                            <button type="button" class="tabs-link" data-tab-link="documents-delivery-notes"
                                role="tab" aria-selected="false">
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

                            <button type="button" class="tabs-link" data-tab-link="documents-work-orders"
                                role="tab" aria-selected="false">
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
                                    @include('documents.partials.table', [
                                        'documents' => $documents,
                                        'showParty' => false,
                                        'showAsset' => false,
                                        'showOrder' => false,
                                        'emptyMessage' => 'No hay documentos asociados para mostrar.',
                                    ])
                                </x-card>
                            </div>
                        </section>

                        <section class="tab-panel" data-tab-panel="documents-quotes" hidden>
                            <div class="tab-panel-stack">
                                <x-card class="list-card">
                                    @include('documents.partials.table', [
                                        'documents' => $quotes,
                                        'showParty' => false,
                                        'showAsset' => false,
                                        'showOrder' => false,
                                        'emptyMessage' => 'Esta orden no tiene presupuestos asociados.',
                                    ])
                                </x-card>
                            </div>
                        </section>

                        <section class="tab-panel" data-tab-panel="documents-delivery-notes" hidden>
                            <div class="tab-panel-stack">
                                <x-card class="list-card">
                                    @include('documents.partials.table', [
                                        'documents' => $deliveryNotes,
                                        'showParty' => false,
                                        'showAsset' => false,
                                        'showOrder' => false,
                                        'emptyMessage' => 'Esta orden no tiene remitos asociados.',
                                    ])
                                </x-card>
                            </div>
                        </section>

                        <section class="tab-panel" data-tab-panel="documents-invoices" hidden>
                            <div class="tab-panel-stack">
                                <x-card class="list-card">
                                    @include('documents.partials.table', [
                                        'documents' => $invoices,
                                        'showParty' => false,
                                        'showAsset' => false,
                                        'showOrder' => false,
                                        'emptyMessage' => 'Esta orden no tiene facturas asociadas.',
                                    ])
                                </x-card>
                            </div>
                        </section>

                        <section class="tab-panel" data-tab-panel="documents-work-orders" hidden>
                            <div class="tab-panel-stack">
                                <x-card class="list-card">
                                    @include('documents.partials.table', [
                                        'documents' => $workOrders,
                                        'showParty' => false,
                                        'showAsset' => false,
                                        'showOrder' => false,
                                        'emptyMessage' => 'Esta orden no tiene órdenes de trabajo asociadas.',
                                    ])
                                </x-card>
                            </div>
                        </section>

                        @if ($receipts->count())
                            <section class="tab-panel" data-tab-panel="documents-receipts" hidden>
                                <div class="tab-panel-stack">
                                    <x-card class="list-card">
                                        @include('documents.partials.table', [
                                            'documents' => $receipts,
                                            'showParty' => false,
                                            'showAsset' => false,
                                            'showOrder' => false,
                                            'emptyMessage' => 'Esta orden no tiene recibos asociados.',
                                        ])
                                    </x-card>
                                </div>
                            </section>
                        @endif

                        @if ($creditNotes->count())
                            <section class="tab-panel" data-tab-panel="documents-credit-notes" hidden>
                                <div class="tab-panel-stack">
                                    <x-card class="list-card">
                                        @include('documents.partials.table', [
                                            'documents' => $creditNotes,
                                            'showParty' => false,
                                            'showAsset' => false,
                                            'showOrder' => false,
                                            'emptyMessage' => 'Esta orden no tiene notas de crédito asociadas.',
                                        ])
                                    </x-card>
                                </div>
                            </section>
                        @endif
                    </div>

                </div>
            </section>
        </div>

    </x-page>
@endsection
