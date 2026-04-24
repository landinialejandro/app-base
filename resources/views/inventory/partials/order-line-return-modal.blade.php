{{-- FILE: resources/views/inventory/partials/order-line-return-modal.blade.php | V4 --}}

@php
    use App\Support\Inventory\InventoryOriginCatalog;

    $order = $order ?? null;
    $row = $row ?? [];
    $trailQuery = $trailQuery ?? [];

    $modalId = $modalId ?? 'inventory-return-line-' . ($row['order_item_id'] ?? uniqid());
    $submitFormId = $submitFormId ?? $modalId . '-form';

    $position = $row['position'] ?? '—';
    $productName = $row['product_name'] ?? 'Producto';

    $executedQuantity = (float) ($row['executed_quantity'] ?? 0);
    $maxReturnQuantity = (float) ($row['max_return_quantity'] ?? $executedQuantity);
    $currentStock = array_key_exists('current_stock', $row) ? (float) $row['current_stock'] : null;

    $productId = $row['product_id'] ?? null;
    $orderItemId = $row['order_item_id'] ?? null;
    $returnKind = $row['return_kind'] ?? null;

    $returnLabel = $row['return_label'] ?? 'Devolver';
    $returnTitle = $row['return_title'] ?? ($returnLabel . ' línea');
    $returnVerbLower = \Illuminate\Support\Str::lower($returnLabel);

    $quantityInputId = $modalId . '-quantity';
    $notesInputId = $modalId . '-notes';

    $stepAmount = '0.01';
    $defaultQuantity = number_format($maxReturnQuantity, 2, '.', '');
@endphp

<x-modal :id="$modalId" :title="$returnTitle . ' #' . $position" size="md">
    <x-slot:headerActions>
        <x-button-tool-button
            type="submit"
            :form="$submitFormId"
            variant="danger"
            :title="'Confirmar ' . \Illuminate\Support\Str::lower($returnTitle) . ' #' . $position"
            :label="'Confirmar ' . \Illuminate\Support\Str::lower($returnTitle) . ' #' . $position"
        >
            <x-icons.check />
        </x-button-tool-button>
    </x-slot:headerActions>

    <form
        id="{{ $submitFormId }}"
        action="{{ route('inventory.movements.store', $trailQuery) }}"
        method="POST"
        class="form"
    >
        @csrf

        <input type="hidden" name="product_id" value="{{ $productId }}">
        <input type="hidden" name="origin_type" value="{{ InventoryOriginCatalog::TYPE_ORDER }}">
        <input type="hidden" name="origin_id" value="{{ $order?->id }}">
        <input type="hidden" name="origin_line_type" value="{{ InventoryOriginCatalog::LINE_TYPE_ORDER_ITEM }}">
        <input type="hidden" name="origin_line_id" value="{{ $orderItemId }}">
        <input type="hidden" name="kind" value="{{ $returnKind }}">
        <input type="hidden" name="return_context" value="orders.show">
        <input type="hidden" name="return_tab" value="inventory.embedded">

        <div class="form-group">
            <label class="form-label">Producto</label>
            <input type="text" class="form-control" value="{{ $productName }}" disabled>
        </div>

        <div class="summary-inline-grid">
            <div class="summary-inline-card">
                <div class="summary-inline-label">Ejecutado neto</div>
                <div class="summary-inline-value">{{ number_format($executedQuantity, 2, ',', '.') }}</div>
            </div>

            <div class="summary-inline-card">
                <div class="summary-inline-label">Máximo a revertir</div>
                <div class="summary-inline-value">{{ number_format($maxReturnQuantity, 2, ',', '.') }}</div>
            </div>

            <div class="summary-inline-card">
                <div class="summary-inline-label">Stock actual</div>
                <div class="summary-inline-value">
                    {{ $currentStock !== null ? number_format($currentStock, 2, ',', '.') : '—' }}
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="{{ $quantityInputId }}" class="form-label">Cantidad</label>

            <div class="app-stepper">
                <button
                    type="button"
                    class="app-stepper__button btn btn-secondary btn-icon"
                    data-action="app-step-number"
                    data-step-target="#{{ $quantityInputId }}"
                    data-step-direction="down"
                    data-step-amount="{{ $stepAmount }}"
                    title="Restar cantidad"
                    aria-label="Restar cantidad"
                >
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
                    data-step-amount="{{ $stepAmount }}"
                    title="Sumar cantidad"
                    aria-label="Sumar cantidad"
                >
                    <x-icons.plus />
                </button>
            </div>

            @error('quantity')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror

            <div class="form-help">
                Podés {{ $returnVerbLower }} hasta {{ number_format($maxReturnQuantity, 2, ',', '.') }},
                que es lo ejecutado neto actual de la línea. La operación se registrará como contramovimiento.
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

            <div class="form-help">
                Se agregará trazabilidad automática del sistema junto con las notas ingresadas.
            </div>
        </div>
    </form>
</x-modal>