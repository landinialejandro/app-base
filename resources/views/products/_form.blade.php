@csrf

<div class="mb-3">
    <label for="name" class="form-label">Nombre</label>
    <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
        value="{{ old('name', $product->name ?? '') }}" required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="sku" class="form-label">SKU</label>
    <input type="text" id="sku" name="sku" class="form-control @error('sku') is-invalid @enderror"
        value="{{ old('sku', $product->sku ?? '') }}">
    @error('sku')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="price" class="form-label">Precio</label>
    <input type="number" step="0.01" min="0" id="price" name="price"
        class="form-control @error('price') is-invalid @enderror"
        value="{{ old('price', isset($product) && $product->price !== null ? $product->price : '') }}">
    @error('price')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="description" class="form-label">Descripción</label>
    <textarea id="description" name="description" rows="4"
        class="form-control @error('description') is-invalid @enderror">{{ old('description', $product->description ?? '') }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label d-block">Estado</label>

    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
            @checked(old('is_active', $product->is_active ?? true))>
        <label class="form-check-label" for="is_active">
            Activo
        </label>
    </div>
</div>

<div class="form-actions d-flex gap-2">
    <button type="submit" class="btn btn-primary">Guardar</button>
    <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancelar</a>
</div>