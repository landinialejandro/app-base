{{-- FILE: resources/views/documents/print.blade.php | V1 --}}
@extends('layouts.print')

@php
    use App\Support\Catalogs\DocumentCatalog;

    $items = $document->items->sortBy('position')->values();

    $title = match ($document->kind) {
        DocumentCatalog::KIND_QUOTE => 'Presupuesto',
        DocumentCatalog::KIND_INVOICE => 'Factura',
        DocumentCatalog::KIND_DELIVERY_NOTE => 'Remito',
        DocumentCatalog::KIND_WORK_ORDER => 'Orden de trabajo',
        DocumentCatalog::KIND_RECEIPT => 'Recibo',
        DocumentCatalog::KIND_CREDIT_NOTE => 'Nota de crédito',
        default => 'Documento',
    };
@endphp

@section('title', $title)

@section('content')
    <div class="print-title-row">
        <div>
            <h2 class="print-title">{{ $title }}</h2>
            <div class="print-subtitle">{{ $document->number ?: 'Sin número' }}</div>
        </div>

        <div class="print-badge">
            {{ DocumentCatalog::statusLabel($document->status) }}
        </div>
    </div>

    <section class="print-section">
        <h3 class="print-section-title">Datos principales</h3>

        <div class="print-grid">
            <div class="print-block">
                <div class="print-block-label">Número</div>
                <div class="print-block-value">{{ $document->number ?: '—' }}</div>
            </div>

            <div class="print-block">
                <div class="print-block-label">Fecha</div>
                <div class="print-block-value">{{ $document->issued_at?->format('d/m/Y') ?: '—' }}</div>
            </div>

            <div class="print-block">
                <div class="print-block-label">Tipo</div>
                <div class="print-block-value">{{ DocumentCatalog::label($document->kind) }}</div>
            </div>

            <div class="print-block">
                <div class="print-block-label">Contacto</div>
                <div class="print-block-value">{{ $document->party?->name ?: '—' }}</div>
            </div>

            <div class="print-block">
                <div class="print-block-label">Orden asociada</div>
                <div class="print-block-value">
                    {{ $document->order?->number ?: ($document->order ? 'Orden #' . $document->order->id : '—') }}
                </div>
            </div>

            <div class="print-block">
                <div class="print-block-label">Activo</div>
                <div class="print-block-value">{{ $document->asset?->name ?: '—' }}</div>
            </div>
        </div>
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
                    <th style="width: 110px;" class="is-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    <tr>
                        <td>{{ $item->position }}</td>
                        <td>{{ $item->description }}</td>
                        <td class="is-right">{{ number_format((float) $item->quantity, 2, ',', '.') }}</td>
                        <td class="is-right">{{ number_format((float) $item->unit_price, 2, ',', '.') }}</td>
                        <td class="is-right">{{ number_format((float) $item->line_total, 2, ',', '.') }}</td>
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
                <td>Subtotal</td>
                <td class="is-right">{{ number_format((float) $document->subtotal, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Impuestos</td>
                <td class="is-right">{{ number_format((float) $document->tax_total, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Total</td>
                <td class="is-right">{{ number_format((float) $document->total, 2, ',', '.') }}</td>
            </tr>
        </table>
    </section>

    <section class="print-section">
        <h3 class="print-section-title">Notas</h3>
        <div class="print-notes">{{ $document->notes ?: '—' }}</div>
    </section>
@endsection
