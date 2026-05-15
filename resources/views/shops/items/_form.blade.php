{{-- FILE: resources/views/shops/items/_form.blade.php | V1 --}}

@php
    use App\Models\ShopItem;

    $mode = $mode ?? 'create';

    $statusLabels = [
        ShopItem::STATUS_DRAFT => 'Borrador',
        ShopItem::STATUS_PUBLISHED => 'Publicado',
        ShopItem::STATUS_HIDDEN => 'Oculto',
    ];
@endphp

@if ($mode === 'create')
    <div class="form-group">
        <label class="form-label" for="product_id">Producto</label>
        <select id="product_id" name="product_id" class="form-control" required>
            <option value="">Seleccionar producto</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" @selected((int) old('product_id') === (int) $product->id)>
                    {{ $product->name }}{{ $product->sku ? ' · '.$product->sku : '' }}
                </option>
            @endforeach
        </select>
        <div class="form-help">
            El producto pertenece al catálogo maestro. La tienda solo define cómo se publica.
        </div>
        @error('product_id')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>
@else
    <div class="form-group">
        <label class="form-label">Producto</label>
        <div class="form-control" aria-readonly="true">
            {{ $item->product?->name ?? '—' }}{{ $item->product?->sku ? ' · '.$item->product->sku : '' }}
        </div>
    </div>
@endif

<div class="form-group">
    <label class="form-label" for="display_name">Nombre visible</label>
    <input
        id="display_name"
        class="form-control"
        name="display_name"
        type="text"
        value="{{ old('display_name', $item->display_name ?? '') }}"
        placeholder="Usar nombre del producto"
    >
    @error('display_name')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label class="form-label" for="display_description">Descripción visible</label>
    <textarea id="display_description" class="form-control" name="display_description" rows="4"
        placeholder="Usar descripción del producto">{{ old('display_description', $item->display_description ?? '') }}</textarea>
    @error('display_description')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label class="form-label" for="status">Estado</label>
    <select id="status" name="status" class="form-control">
        @foreach ($statusLabels as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $item->status ?? ShopItem::STATUS_DRAFT) === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    <div class="form-help">
        Solo los artículos publicados se muestran en la tienda externa.
    </div>
    @error('status')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label class="form-label" for="use_product_price">Precio</label>

    <label class="form-check">
        <input
            id="use_product_price"
            type="checkbox"
            name="use_product_price"
            value="1"
            @checked(old('use_product_price', $item->use_product_price ?? true))
        >
        <span>Usar precio del producto</span>
    </label>

    <input
        id="price"
        class="form-control"
        name="price"
        type="number"
        min="0"
        step="0.01"
        value="{{ old('price', $item->price ?? '') }}"
        placeholder="Precio publicado manual"
    >

    <div class="form-help">
        Si usás el precio del producto, el precio manual se ignora.
    </div>

    @error('use_product_price')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror

    @error('price')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label class="form-label" for="sort_order">Orden</label>
    <input
        id="sort_order"
        class="form-control"
        name="sort_order"
        type="number"
        min="0"
        step="1"
        value="{{ old('sort_order', $item->sort_order ?? 0) }}"
    >
    @error('sort_order')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>