{{-- FILE: resources/views/documents/items/partials/embedded.blade.php | V1 --}}

@php
    $document = $document ?? null;
    $items = $items ?? collect();
    $trailQuery = $trailQuery ?? [];
@endphp

<div class="tab-panel-stack">

    <x-tab-toolbar label="Ítems del documento">
        <x-slot:tabs>
            <span class="tab-toolbar-title">
                Ítems
                @if ($items->count())
                    ({{ $items->count() }})
                @endif
            </span>
        </x-slot:tabs>

        <x-slot:actions>
            @can('update', $document)
                <x-button-create :href="route('documents.items.create', ['document' => $document] + $trailQuery)" label="Agregar ítem" class="btn-sm" />
            @endcan
        </x-slot:actions>
    </x-tab-toolbar>

    <x-card class="list-card">
        @include('documents.items.partials.table', [
            'document' => $document,
            'items' => $items,
            'trailQuery' => $trailQuery,
        ])
    </x-card>

    {{-- RESUMEN ECONÓMICO --}}
    <x-card>
        <div class="summary-inline-grid">
            <div class="summary-inline-card">
                <div class="summary-inline-label">Subtotal</div>
                <div class="summary-inline-value">
                    ${{ number_format($document->subtotal, 2, ',', '.') }}
                </div>
            </div>

            <div class="summary-inline-card">
                <div class="summary-inline-label">Impuestos</div>
                <div class="summary-inline-value">
                    ${{ number_format($document->tax_total, 2, ',', '.') }}
                </div>
            </div>

            <div class="summary-inline-card">
                <div class="summary-inline-label">Total</div>
                <div class="summary-inline-value">
                    ${{ number_format($document->total, 2, ',', '.') }}
                </div>
            </div>
        </div>
    </x-card>

</div>
