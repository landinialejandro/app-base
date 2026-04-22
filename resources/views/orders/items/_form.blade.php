{{-- FILE: resources/views/orders/items/_form.blade.php | V5 --}}

@php
    use App\Support\Catalogs\ProductCatalog;
    use App\Support\Catalogs\OrderItemCatalog;
    use App\Support\Inventory\OrderItemStatusService;

    $supportsProductsModule = $supportsProductsModule ?? true;

    $itemExists = isset($item) && $item->exists;

    $executedQuantity = $itemExists ? app(OrderItemStatusService::class)->executedQuantity($item) : 0.0;
    $lineStatus = $itemExists ? ($item->status ?: OrderItemCatalog::STATUS_PENDING) : OrderItemCatalog::STATUS_PENDING;
    $lineStatusLabel = OrderItemCatalog::statusLabel($lineStatus);
    $lineStatusBadge = OrderItemCatalog::badgeClass($lineStatus);
@endphp

<div class="form"
    @if ($supportsProductsModule) data-action="app-product-autofill" data-product-select="#product_id"
    data-kind-field="#kind" data-description-field="#description" data-price-field="#unit_price" @endif>

    @if ($itemExists)
        <div class="summary-inline-grid">
            <div class="summary-inline-card">
                <div class="summary-inline-label">Estado de línea</div>
                <div class="summary-inline-value">
                    <span class="status-badge {{ $lineStatusBadge }}">
                        {{ $lineStatusLabel }}
                    </span>
                </div>
            </div>

            <div class="summary-inline-card">
                <div class="summary-inline-label">Ejecutado neto</div>
                <div class="summary-inline-value">{{ number_format($executedQuantity, 2, ',', '.') }}</div>
            </div>
        </div>
    @endif

    @if ($supportsProductsModule)
        <div class="form-group">
            <label for="product_id" class="form-label">Producto</label>
            <select name="product_id" id="product_id" class="form-control">
                <option value="">Seleccionar producto o servicio</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" data-kind="{{ $product->kind }}"
                        data-description="{{ $product->name }}" data-price="{{ $product->price }}"
                        @selected(old('product_id', $item->product_id ?? '') == $product->id)>
                        {{ $product->name }}
                    </option>
                @endforeach
            </select>
            <div class="form-help">
                Opcional. Si seleccionas un producto, se completan automáticamente tipo, descripción y precio.
            </div>
            @error('product_id')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>
    @endif

    <div class="form-group">
        <label for="position" class="form-label">Posición</label>
        <input type="number" name="position" id="position" class="form-control" min="1"
            value="{{ old('position', $item->position ?? '') }}">
        @error('position')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="kind" class="form-label">Tipo</label>
        <select name="kind" id="kind" class="form-control" required>
            @foreach (ProductCatalog::kindLabels() as $value => $label)
                <option value="{{ $value }}" @selected(old('kind', $item->kind ?? ProductCatalog::KIND_PRODUCT) === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('kind')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="description" class="form-label">Descripción</label>
        <input type="text" name="description" id="description" class="form-control"
            value="{{ old('description', $item->description ?? '') }}" required>
        @error('description')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="quantity" class="form-label">Cantidad</label>
        <input type="number" name="quantity" id="quantity" class="form-control" step="0.01"
            min="{{ $itemExists ? number_format($executedQuantity, 2, '.', '') : '0.01' }}"
            value="{{ old('quantity', $item->quantity ?? 1) }}" required>
        @error('quantity')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror

        @if ($itemExists && $executedQuantity > 0)
            <div class="form-help">
                Ya hay {{ number_format($executedQuantity, 2, ',', '.') }} ejecutados netos en esta línea.
                La cantidad no puede quedar por debajo de ese valor.
            </div>
        @endif
    </div>

    <div class="form-group">
        <label for="unit_price" class="form-label">Precio unitario</label>
        <input type="number" name="unit_price" id="unit_price" class="form-control" step="0.01" min="0"
            value="{{ old('unit_price', $item->unit_price ?? 0) }}" required>
        @error('unit_price')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>
</div>
