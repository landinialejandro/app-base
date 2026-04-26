{{-- FILE: resources/views/inventory/partials/document-line-return-modal.blade.php | V1 --}}

@php
    $row = $row ?? [];
    $trailQuery = $trailQuery ?? [];
    $modalId = $modalId ?? ('inventory-document-line-return-' . uniqid());

    $submitFormId = $modalId . '-form';

    $title = $title ?? 'Revertir';

    $action = $action ?? '#';
    $method = $method ?? 'POST';

    $position = $row['position'] ?? '—';
    $productName = $row['product_name'] ?? 'Producto';

    $pendingQuantity = (float) ($row['pending_quantity'] ?? 0);
    $executedQuantity = (float) ($row['executed_quantity'] ?? 0);
    $maxReturnQuantity = (float) ($row['max_return_quantity'] ?? $executedQuantity);
    $currentStock = array_key_exists('current_stock', $row) ? (float) $row['current_stock'] : null;

    $quantityInputId = $modalId . '-quantity';
    $notesInputId = $modalId . '-notes';

    $stepAmount = '0.01';
    $defaultQuantity = number_format($maxReturnQuantity, 2, '.', '');

    $hiddenFields = $hiddenFields ?? [];
@endphp

<x-modal :id="$modalId" :title="$title . ' #' . $position" size="md">
    <x-slot:headerActions>
        <x-button-tool-button
            type="submit"
            :form="$submitFormId"
            variant="danger"
            :title="'Confirmar reversión #' . $position"
            :label="'Confirmar reversión #' . $position"
        >
            <x-icons.check />
        </x-button-tool-button>
    </x-slot:headerActions>

    <form
        id="{{ $submitFormId }}"
        action="{{ $action }}"
        method="POST"
        class="form"
    >
        @csrf

        @if (strtoupper($method) !== 'POST')
            @method($method)
        @endif

        @foreach ($hiddenFields as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach

        <div class="form-group">
            <label class="form-label">Producto</label>
            <input type="text" class="form-control" value="{{ $productName }}" disabled>
        </div>

        <div class="summary-inline-grid">
            <div class="summary-inline-card">
                <div class="summary-inline-label">Pendiente</div>
                <div class="summary-inline-value">{{ number_format($pendingQuantity, 2, ',', '.') }}</div>
            </div>

            <div class="summary-inline-card">
                <div class="summary-inline-label">Ejecutado neto</div>
                <div class="summary-inline-value">{{ number_format($executedQuantity, 2, ',', '.') }}</div>
            </div>

            <div class="summary-inline-card">
                <div class="summary-inline-label">Stock actual</div>
                <div class="summary-inline-value">
                    {{ $currentStock !== null ? number_format($currentStock, 2, ',', '.') : '—' }}
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="{{ $quantityInputId }}" class="form-label">Cantidad a revertir</label>

            <div class="app-stepper">
                <button
                    type="button"
                    class="app-stepper__button btn btn-secondary btn-icon"
                    data-action="app-step-number"
                    data-step-target="#{{ $quantityInputId }}"
                    data-step-direction="down"
                    data-step-amount="{{ $stepAmount }}">
                    <x-icons.minus />
                </button>

                <div class="app-stepper__field">
                    <input
                        id="{{ $quantityInputId }}"
                        name="quantity"
                        type="number"
                        step="{{ $stepAmount }}"
                        min="0.01"
                        max="{{ $defaultQuantity }}"
                        class="form-control app-stepper__input"
                        value="{{ old('quantity', $defaultQuantity) }}"
                        required
                        inputmode="decimal"
                        autocomplete="off"
                        data-modal-autofocus
                    >
                </div>

                <button
                    type="button"
                    class="app-stepper__button btn btn-secondary btn-icon"
                    data-action="app-step-number"
                    data-step-target="#{{ $quantityInputId }}"
                    data-step-direction="up"
                    data-step-amount="{{ $stepAmount }}">
                    <x-icons.plus />
                </button>
            </div>

            @error('quantity')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror

            <div class="form-help">
                Podés revertir hasta {{ number_format($maxReturnQuantity, 2, ',', '.') }},
                que corresponde a lo ejecutado neto actual de la línea.
            </div>
        </div>

        <div class="form-group">
            <label for="{{ $notesInputId }}" class="form-label">Notas</label>

            <input
                id="{{ $notesInputId }}"
                name="notes"
                type="text"
                class="form-control"
                value="{{ old('notes') }}"
                placeholder="Opcional"
            >

            @error('notes')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>
    </form>
</x-modal>