<div class="form-group">
    <label for="product_id" class="form-label">Producto</label>
    <select name="product_id" id="product_id" class="form-control">
        <option value="">Seleccionar producto o servicio</option>
        @foreach ($products as $product)
            <option value="{{ $product->id }}" data-kind="{{ $product->kind }}" data-description="{{ $product->name }}"
                data-price="{{ $product->price }}" @selected(old('product_id', $item->product_id ?? '') == $product->id)>
                {{ $product->name }}
            </option>
        @endforeach
    </select>
    @error('product_id')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="position" class="form-label">Posición</label>
    <input type="number" name="position" id="position" class="form-control" min="1"
        value="{{ old('position', $item->position ?? '') }}">
    @error('position')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="kind" class="form-label">Tipo</label>
    <select name="kind" id="kind" class="form-control" required>
        <option value="product" @selected(old('kind', $item->kind ?? 'product') === 'product')>Producto</option>
        <option value="service" @selected(old('kind', $item->kind ?? '') === 'service')>Servicio</option>
    </select>
    @error('kind')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="description" class="form-label">Descripción</label>
    <input type="text" name="description" id="description" class="form-control"
        value="{{ old('description', $item->description ?? '') }}" required>
    @error('description')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="quantity" class="form-label">Cantidad</label>
    <input type="number" name="quantity" id="quantity" class="form-control" step="0.01" min="0.01"
        value="{{ old('quantity', $item->quantity ?? 1) }}" required>
    @error('quantity')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="unit_price" class="form-label">Precio unitario</label>
    <input type="number" name="unit_price" id="unit_price" class="form-control" step="0.01" min="0"
        value="{{ old('unit_price', $item->unit_price ?? 0) }}" required>
    @error('unit_price')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const productSelect = document.getElementById('product_id');
        const kindField = document.getElementById('kind');
        const descriptionField = document.getElementById('description');
        const priceField = document.getElementById('unit_price');

        if (!productSelect || !kindField || !descriptionField || !priceField) {
            return;
        }

        function applySelectedProductData(force = false) {
            const selected = productSelect.options[productSelect.selectedIndex];

            if (!selected || !selected.value) {
                return;
            }

            const kind = selected.dataset.kind || '';
            const description = selected.dataset.description || '';
            const price = selected.dataset.price || '';

            if (kind && (force || !kindField.value)) {
                kindField.value = kind;
            }

            if (description && (force || !descriptionField.value)) {
                descriptionField.value = description;
            }

            if (price !== '' && (force || !priceField.value || Number(priceField.value) === 0)) {
                priceField.value = price;
            }
        }

        productSelect.addEventListener('change', function () {
            applySelectedProductData(true);
        });

        applySelectedProductData(false);
    });
</script>