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

        $workOrderCount = $documents->where('kind', DocumentCatalog::KIND_WORK_ORDER)->count();
        $quoteCount = $documents->where('kind', DocumentCatalog::KIND_QUOTE)->count();
        $deliveryNoteCount = $documents->where('kind', DocumentCatalog::KIND_DELIVERY_NOTE)->count();
        $invoiceCount = $documents->where('kind', DocumentCatalog::KIND_INVOICE)->count();
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

                    <x-card>
                        <div class="detail-grid detail-grid--3">
                            <div class="detail-block">
                                <span class="detail-block-label">Estado</span>
                                <div class="detail-block-value">
                                    <span class="status-badge {{ OrderCatalog::badgeClass($order->status) }}">
                                        {{ OrderCatalog::label($order->status) }}
                                    </span>
                                </div>
                            </div>

                            <div class="detail-block">
                                <span class="detail-block-label">Contacto</span>
                                <div class="detail-block-value">{{ $order->party?->name ?: '—' }}</div>
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

                            <div class="detail-block">
                                <span class="detail-block-label">Fecha</span>
                                <div class="detail-block-value">{{ $order->ordered_at?->format('d/m/Y') ?: '—' }}</div>
                            </div>

                            <div class="detail-block">
                                <span class="detail-block-label">Total</span>
                                <div class="detail-block-value">${{ number_format($order->total, 2, ',', '.') }}</div>
                            </div>

                            <div class="detail-block">
                                <span class="detail-block-label">Creado por</span>
                                <div class="detail-block-value">{{ $order->creator?->name ?: '—' }}</div>
                            </div>

                            <div class="detail-block">
                                <span class="detail-block-label">Actualizado por</span>
                                <div class="detail-block-value">{{ $order->updater?->name ?: '—' }}</div>
                            </div>

                            <div class="detail-block detail-block--full">
                                <span class="detail-block-label">Notas</span>
                                <div class="detail-block-value">{{ $order->notes ?: '—' }}</div>
                            </div>
                        </div>
                    </x-card>

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
                                            <th>Subtotal</th>
                                            <th class="compact-actions-cell">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($items as $item)
                                            <tr>
                                                <td>{{ $item->position }}</td>
                                                <td>{{ ProductCatalog::label($item->kind) }}</td>
                                                <td>{{ $item->description }}</td>
                                                <td>{{ number_format($item->quantity, 2, ',', '.') }}</td>
                                                <td>${{ number_format($item->unit_price, 2, ',', '.') }}</td>
                                                <td>${{ number_format($item->subtotal, 2, ',', '.') }}</td>
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

                    <x-card>
                        @if ($documents->count())
                            <div class="summary-inline-grid">
                                <div class="summary-inline-card">
                                    <div class="summary-inline-label">Total asociados</div>
                                    <div class="summary-inline-value">{{ $documents->count() }}</div>
                                </div>

                                <div class="summary-inline-card">
                                    <div class="summary-inline-label">Órdenes de trabajo</div>
                                    <div class="summary-inline-value">{{ $workOrderCount }}</div>
                                </div>

                                <div class="summary-inline-card">
                                    <div class="summary-inline-label">Presupuestos</div>
                                    <div class="summary-inline-value">{{ $quoteCount }}</div>
                                </div>

                                <div class="summary-inline-card">
                                    <div class="summary-inline-label">Remitos</div>
                                    <div class="summary-inline-value">{{ $deliveryNoteCount }}</div>
                                </div>

                                <div class="summary-inline-card">
                                    <div class="summary-inline-label">Facturas</div>
                                    <div class="summary-inline-value">{{ $invoiceCount }}</div>
                                </div>
                            </div>
                        @else
                            <p class="mb-0">Esta orden todavía no tiene documentos asociados.</p>
                        @endif
                    </x-card>

                    <x-card class="list-card">
                        @if ($documents->count())
                            <div class="table-wrap list-scroll">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Número</th>
                                            <th>Tipo</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($documents as $document)
                                            <tr>
                                                <td>
                                                    <a href="{{ route('documents.show', $document) }}">
                                                        {{ $document->number ?: 'Sin número' }}
                                                    </a>
                                                </td>
                                                <td>{{ DocumentCatalog::label($document->kind) }}</td>
                                                <td>
                                                    <span
                                                        class="status-badge {{ DocumentCatalog::badgeClass($document->status) }}">
                                                        {{ DocumentCatalog::label($document->status) }}
                                                    </span>
                                                </td>
                                                <td>{{ $document->issued_at?->format('d/m/Y') ?: '—' }}</td>
                                                <td>${{ number_format($document->total, 2, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="mb-0">No hay documentos asociados para mostrar.</p>
                        @endif
                    </x-card>

                </div>
            </section>
        </div>

    </x-page>
@endsection
