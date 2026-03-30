{{-- FILE: resources/views/products/_form.blade.php | V2 --}}

@php
    use App\Support\Catalogs\ProductCatalog;
@endphp

<div class="form-group">
    <label for="kind" class="form-label">Tipo</label>
    <select id="kind" name="kind" class="form-control" required>
        @foreach (ProductCatalog::kindLabels() as $value => $label)
            <option value="{{ $value }}" @selected(old('kind', $product->kind ?? ProductCatalog::KIND_PRODUCT) === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    @error('kind')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="name" class="form-label">Nombre</label>
    <input type="text" id="name" name="name" class="form-control"
        value="{{ old('name', $product->name ?? '') }}" required>
    @error('name')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="sku" class="form-label">SKU</label>
    <input type="text" id="sku" name="sku" class="form-control"
        value="{{ old('sku', $product->sku ?? '') }}">
    @error('sku')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="price" class="form-label">Precio</label>
    <input type="number" step="0.01" min="0" id="price" name="price" class="form-control"
        value="{{ old('price', isset($product) && $product->price !== null ? $product->price : '') }}">
    @error('price')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="unit_label" class="form-label">Unidad</label>
    <input type="text" id="unit_label" name="unit_label" class="form-control"
        value="{{ old('unit_label', $product->unit_label ?? 'unidad') }}" required>
    @error('unit_label')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="description" class="form-label">Descripción</label>
    <textarea id="description" name="description" rows="4" class="form-control">{{ old('description', $product->description ?? '') }}</textarea>
    @error('description')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label class="form-label" for="is_active">
        <input class="form-checkbox" type="checkbox" id="is_active" name="is_active" value="1"
            @checked(old('is_active', $product->is_active ?? true))>
        Activo
    </label>
    @error('is_active')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>
