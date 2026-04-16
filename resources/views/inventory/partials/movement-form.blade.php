{{-- FILE: resources/views/inventory/partials/movement-form.blade.php | V2 --}}

@php
    use App\Support\Inventory\InventoryMovementService;

    $action = $action ?? null;
    $products = ($products ?? collect())->values();
    $submitLabel = $submitLabel ?? 'Registrar movimiento';

    $availableKinds = collect($availableKinds ?? InventoryMovementService::kinds())->values();
    $fixedKind = $fixedKind ?? null;

    if ($fixedKind) {
        $availableKinds = collect([$fixedKind]);
    }

    $selectedProductId = $selectedProductId ?? ($products->count() === 1 ? $products->first()->id : null);
    $selectedProduct = $selectedProductId ? $products->firstWhere('id', $selectedProductId) : null;

    $productFieldId = $productFieldId ?? 'inventory_product_id';
    $kindFieldId = $kindFieldId ?? 'inventory_kind';
    $quantityFieldId = $quantityFieldId ?? 'inventory_quantity';
    $notesFieldId = $notesFieldId ?? 'inventory_notes';

    $orderId = $orderId ?? null;
    $documentId = $documentId ?? null;
    $returnContext = $returnContext ?? null;
@endphp

@if ($action && $products->count() && $availableKinds->count())
    <form action="{{ $action }}" method="POST" class="form">
        @csrf

        @if ($selectedProduct)
            <div class="form-group">
                <label class="form-label">Producto</label>
                <input type="text" class="form-control" value="{{ $selectedProduct->name }}" disabled>
                <input type="hidden" name="product_id" value="{{ $selectedProduct->id }}">
            </div>
        @else
            <div class="form-group">
                <label for="{{ $productFieldId }}" class="form-label">Producto</label>
                <select id="{{ $productFieldId }}" name="product_id" class="form-control" required>
                    <option value="">Seleccionar producto</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected(old('product_id', $selectedProductId) == $product->id)>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
                @error('product_id')
                    <div class="form-help is-error">{{ $message }}</div>
                @enderror
            </div>
        @endif

        @if ($fixedKind)
            <div class="form-group">
                <label class="form-label">Tipo de movimiento</label>
                <input type="text" class="form-control" value="{{ ucfirst($fixedKind) }}" disabled>
                <input type="hidden" name="kind" value="{{ $fixedKind }}">
            </div>
        @elseif ($availableKinds->count() === 1)
            <input type="hidden" name="kind" value="{{ $availableKinds->first() }}">
        @else
            <div class="form-group">
                <label for="{{ $kindFieldId }}" class="form-label">Tipo de movimiento</label>
                <select id="{{ $kindFieldId }}" name="kind" class="form-control" required>
                    <option value="">Seleccionar tipo</option>
                    @foreach ($availableKinds as $kind)
                        <option value="{{ $kind }}" @selected(old('kind') === $kind)>
                            {{ ucfirst($kind) }}
                        </option>
                    @endforeach
                </select>
                @error('kind')
                    <div class="form-help is-error">{{ $message }}</div>
                @enderror
            </div>
        @endif

        <div class="form-group">
            <label for="{{ $quantityFieldId }}" class="form-label">Cantidad</label>
            <input id="{{ $quantityFieldId }}" name="quantity" type="number" step="0.01" min="0.01"
                class="form-control" value="{{ old('quantity') }}" required>
            @error('quantity')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="{{ $notesFieldId }}" class="form-label">Notas</label>
            <input id="{{ $notesFieldId }}" name="notes" type="text" class="form-control"
                value="{{ old('notes') }}" placeholder="Opcional">
            @error('notes')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>

        @if ($orderId)
            <input type="hidden" name="order_id" value="{{ $orderId }}">
        @endif

        @if ($documentId)
            <input type="hidden" name="document_id" value="{{ $documentId }}">
        @endif

        @if ($returnContext)
            <input type="hidden" name="return_context" value="{{ $returnContext }}">
        @endif

        <div class="form-actions">
            <button type="submit" class="btn btn-secondary">{{ $submitLabel }}</button>
        </div>
    </form>
@endif
