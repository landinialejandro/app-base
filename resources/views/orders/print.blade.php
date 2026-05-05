{{-- FILE: resources/views/orders/print.blade.php | V6 --}}

@extends('layouts.print')

@php
    use App\Support\Catalogs\OrderCatalog;

    $items = $order->items->sortBy('position')->values();

    $title = match ($order->group) {
        OrderCatalog::GROUP_SALE => 'Orden de venta',
        OrderCatalog::GROUP_PURCHASE => 'Orden de compra',
        OrderCatalog::GROUP_SERVICE => 'Orden de servicio',
        default => 'Orden',
    };
@endphp

@section('title', $title)

@section('content')
    <div class="print-title-row">
        <div class="print-title-main">
            <h2 class="print-title">{{ $title }}</h2>
            <div class="print-subtitle">{{ $order->number ?: 'Sin número' }}</div>
        </div>

        <div class="print-title-badge">
            <span class="print-badge">
                {{ OrderCatalog::statusLabel($order->status) }}
            </span>
        </div>
    </div>

    <section class="print-section">
        <h3 class="print-section-title">Datos principales</h3>

        <table class="print-grid-table" role="presentation">
            <tr>
                <td>
                    <div class="print-block">
                        <div class="print-block-label">Número</div>
                        <div class="print-block-value">{{ $order->number ?: '—' }}</div>
                    </div>
                </td>
                <td>
                    <div class="print-block">
                        <div class="print-block-label">Fecha</div>
                        <div class="print-block-value">{{ $order->ordered_at?->format('d/m/Y') ?: '—' }}</div>
                    </div>
                </td>
                <td>
                    <div class="print-block">
                        <div class="print-block-label">Tipo</div>
                        <div class="print-block-value">{{ OrderCatalog::groupLabel($order->group) }}</div>
                    </div>
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <div class="print-block">
                        <div class="print-block-label">Contraparte</div>
                        <div class="print-block-value">{{ $order->displayCounterpartyName() }}</div>
                    </div>
                </td>
                <td>
                    <div class="print-block">
                        <div class="print-block-label">Total</div>
                        <div class="print-block-value">{{ number_format((float) $order->total, 2, ',', '.') }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </section>

    <section class="print-section">
        <h3 class="print-section-title">Ítems</h3>

        <table class="print-table">
            <thead>
                <tr>
                    <th style="width: 48px;">Pos.</th>
                    <th>Descripción</th>
                    <th style="width: 80px;" class="is-right">Cant.</th>
                    <th style="width: 110px;" class="is-right">Precio unit.</th>
                    <th style="width: 110px;" class="is-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ $item->position }}</td>
                        <td>{{ $item->description }}</td>
                        <td class="is-right">{{ number_format((float) $item->quantity, 2, ',', '.') }}</td>
                        <td class="is-right">{{ number_format((float) $item->unit_price, 2, ',', '.') }}</td>
                        <td class="is-right">{{ number_format((float) $item->subtotal, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No hay ítems cargados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="print-totals">
            <tr>
                <td>Total</td>
                <td class="is-right">{{ number_format((float) $order->total, 2, ',', '.') }}</td>
            </tr>
        </table>
    </section>

    <section class="print-section">
        <h3 class="print-section-title">Notas</h3>
        <div class="print-notes">{{ $order->notes ?: '—' }}</div>
    </section>
@endsection