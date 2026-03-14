{{-- FILE: resources/views/orders/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Detalle de la orden')

@section('content')
    @php
        use App\Support\Catalogs\OrderCatalog;
        use App\Support\Catalogs\ProductCatalog;
        use App\Support\Catalogs\DocumentCatalog;

        $documents = $order->documents->sortByDesc('id');

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
                Editar
            </a>

            <form method="POST"
                action="{{ route('orders.documents.store', $order) }}"
                class="inline-form"
                @if ($quoteCount > 0)
                    onsubmit="return confirm('Esta orden ya tiene {{ $quoteCount }} presupuesto(s) asociado(s). ¿Deseas crear otro?')"
                @endif>
                @csrf
                <input type="hidden" name="kind" value="{{ DocumentCatalog::KIND_QUOTE }}">
                <button type="submit" class="btn btn-secondary">
                    {{ $quoteCount > 0 ? 'Crear otro presupuesto' : 'Crear presupuesto' }}
                </button>
            </form>

            <form method="POST"
                action="{{ route('orders.documents.store', $order) }}"
                class="inline-form"
                @if ($deliveryNoteCount > 0)
                    onsubmit="return confirm('Esta orden ya tiene {{ $deliveryNoteCount }} remito(s) asociado(s). ¿Deseas crear otro?')"
                @endif>
                @csrf
                <input type="hidden" name="kind" value="{{ DocumentCatalog::KIND_DELIVERY_NOTE }}">
                <button type="submit" class="btn btn-secondary">
                    {{ $deliveryNoteCount > 0 ? 'Crear otro remito' : 'Crear remito' }}
                </button>
            </form>

            <form method="POST"
                action="{{ route('orders.documents.store', $order) }}"
                class="inline-form"
                @if ($invoiceCount > 0)
                    onsubmit="return confirm('Esta orden ya tiene {{ $invoiceCount }} factura(s) asociada(s). ¿Deseas crear otra?')"
                @endif>
                @csrf
                <input type="hidden" name="kind" value="{{ DocumentCatalog::KIND_INVOICE }}">
                <button type="submit" class="btn btn-secondary">
                    {{ $invoiceCount > 0 ? 'Crear otra factura' : 'Crear factura' }}
                </button>
            </form>

            <form method="POST" action="{{ route('orders.destroy', $order) }}" class="inline-form" onsubmit="return confirm(@js(
                $order->items->count()
                ? 'Esta orden tiene ítems cargados. Si la eliminas, también se eliminarán sus ítems. ¿Deseas continuar?'
                : '¿Deseas eliminar esta orden?'
            ))">
                @csrf
                @method('DELETE')

                <button type="submit" class="btn btn-danger">
                    Eliminar
                </button>
            </form>

            <a href="{{ route('orders.index') }}" class="btn btn-secondary">
                Volver
            </a>
        </x-page-header>

        <x-card>
            <div class="detail-list">
                <div>
                    <div class="detail-label">Número</div>
                    <div class="detail-value">{{ $order->number ?: 'Sin número' }}</div>
                </div>

                <div>
                    <div class="detail-label">Tipo</div>
                    <div class="detail-value">{{ OrderCatalog::label($order->kind) }}</div>
                </div>

                <div>
                    <div class="detail-label">Estado</div>
                    <div class="detail-value">{{ OrderCatalog::label($order->status) }}</div>
                </div>

                <div>
                    <div class="detail-label">Contacto</div>
                    <div class="detail-value">{{ $order->party?->name ?: '—' }}</div>
                </div>

                <div>
                    <div class="detail-label">Fecha</div>
                    <div class="detail-value">{{ $order->ordered_at?->format('d/m/Y') ?: '—' }}</div>
                </div>

                <div>
                    <div class="detail-label">Creado por</div>
                    <div class="detail-value">{{ $order->creator?->name ?: '—' }}</div>
                </div>

                <div>
                    <div class="detail-label">Actualizado por</div>
                    <div class="detail-value">{{ $order->updater?->name ?: '—' }}</div>
                </div>

                <div>
                    <div class="detail-label">Total</div>
                    <div class="detail-value">${{ number_format($order->total, 2, ',', '.') }}</div>
                </div>

                <div>
                    <div class="detail-label">Notas</div>
                    <div class="detail-value">{{ $order->notes ?: '—' }}</div>
                </div>
            </div>
        </x-card>

        <x-page-header title="Documentos asociados" />

        <x-card class="list-card">
            @if ($documents->count())
                <div class="detail-list" style="margin-bottom: 1rem;">
                    <div>
                        <div class="detail-label">Total asociados</div>
                        <div class="detail-value">{{ $documents->count() }}</div>
                    </div>

                    <div>
                        <div class="detail-label">Presupuestos</div>
                        <div class="detail-value">{{ $quoteCount }}</div>
                    </div>

                    <div>
                        <div class="detail-label">Remitos</div>
                        <div class="detail-value">{{ $deliveryNoteCount }}</div>
                    </div>

                    <div>
                        <div class="detail-label">Facturas</div>
                        <div class="detail-value">{{ $invoiceCount }}</div>
                    </div>
                </div>

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
                                    <td>{{ DocumentCatalog::label($document->status) }}</td>
                                    <td>{{ $document->issued_at?->format('d/m/Y') ?: '—' }}</td>
                                    <td>${{ number_format($document->total, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="mb-0">Esta orden todavía no tiene documentos asociados.</p>
            @endif
        </x-card>

        <x-page-header title="Ítems de la orden">
            <a href="{{ route('orders.items.create', $order) }}" class="btn btn-primary">
                Agregar ítem
            </a>
        </x-page-header>

        <x-card class="list-card">
            @if ($order->items->count())
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
                            @foreach ($order->items->sortBy('position') as $item)
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
                                                class="btn btn-secondary btn-icon" title="Editar ítem" aria-label="Editar ítem">
                                                <x-icons.pencil />
                                            </a>

                                            <form method="POST" action="{{ route('orders.items.destroy', [$order, $item]) }}"
                                                class="inline-form" onsubmit="return confirm('¿Deseas eliminar este ítem?')">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="btn btn-danger btn-icon" title="Eliminar ítem"
                                                    aria-label="Eliminar ítem">
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

    </x-page>
@endsection