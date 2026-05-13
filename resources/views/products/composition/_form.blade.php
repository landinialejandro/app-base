{{-- FILE: resources/views/products/composition/_form.blade.php | V2 --}}

@php
    $componentOptions = $componentOptions ?? collect();
    $selectedComponentProductId = old('component_product_id', $productComponent->component_product_id ?? null);
@endphp

<div class="form-group">
    <label for="component_product_id" class="form-label">Componente</label>
    <select id="component_product_id" name="component_product_id" class="form-control" required>
        <option value="">Seleccionar producto o servicio</option>
        @foreach ($componentOptions as $option)
            <option value="{{ $option->id }}" @selected((int) $selectedComponentProductId === (int) $option->id)>
                {{ $option->name }}{{ $option->sku ? ' · '.$option->sku : '' }}
            </option>
        @endforeach
    </select>
    <div class="form-help">
        Seleccioná el producto o servicio que integra esta composición.
    </div>
    @error('component_product_id')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="quantity" class="form-label">Cantidad</label>
    <input
        type="number"
        id="quantity"
        name="quantity"
        class="form-control"
        min="0.0001"
        step="0.0001"
        value="{{ old('quantity', $productComponent->quantity ?? 1) }}"
        required
    >
    @error('quantity')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="unit_label" class="form-label">Unidad</label>
    <input
        type="text"
        id="unit_label"
        name="unit_label"
        class="form-control"
        maxlength="50"
        value="{{ old('unit_label', $productComponent->unit_label ?? '') }}"
        placeholder="unidad, segundo, metro..."
    >
    @error('unit_label')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="sort_order" class="form-label">Orden</label>
    <input
        type="number"
        id="sort_order"
        name="sort_order"
        class="form-control"
        min="1"
        value="{{ old('sort_order', $productComponent->sort_order ?? 1) }}"
    >
    @error('sort_order')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label class="form-label" for="is_required">
        <input
            class="form-checkbox"
            type="checkbox"
            id="is_required"
            name="is_required"
            value="1"
            @checked(old('is_required', $productComponent->is_required ?? true))
        >
        Requerido
    </label>
    @error('is_required')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>
