{{-- FILE: resources/views/shops/items/_form.blade.php | V2 --}}

@php
    use App\Support\Catalogs\ShopCatalog;
    use App\Support\Products\ProductLinked;

    $trailQuery = $trailQuery ?? [];

    $mode = $mode ?? 'create';

    $statusLabels = ShopCatalog::itemStatusLabels();
    $storedCommercial = data_get($item ?? null, 'meta.commercial', []);
    $oldCommercial = old('commercial');
    $commercial = is_array($oldCommercial) ? $oldCommercial : $storedCommercial;

    $commercialValue = fn (string $key, mixed $default = null) => data_get($commercial, $key, $default);
    $stockPolicyMode = $commercialValue('stock_policy_mode', 'inherit');
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
            @include('products.components.linked-product', [
                'linked' => ProductLinked::forProduct($item->product, $trailQuery, 'Producto'),
            ])

            @if ($item->product?->sku)
                · {{ $item->product->sku }}
            @endif
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
            <option value="{{ $value }}" @selected(old('status', $item->status ?? ShopCatalog::defaultItemStatus()) === $value)>
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

<div class="form-section">
    <h2 class="section-title">Política comercial del artículo</h2>

    <p class="form-help">
        Estos parámetros son preparatorios. No comprometen stock, no validan disponibilidad real, no crean órdenes y no
        mueven inventario por sí mismos.
    </p>

    <div class="detail-grid">
        <div class="form-group">
            <label class="form-check">
                <input
                    class="form-checkbox"
                    type="checkbox"
                    name="commercial[allow_cart]"
                    value="1"
                    @checked((bool) $commercialValue('allow_cart', true))
                >
                <span>Permitir carrito</span>
            </label>
            @error('commercial.allow_cart')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-check">
                <input
                    class="form-checkbox"
                    type="checkbox"
                    name="commercial[allow_direct_purchase]"
                    value="1"
                    @checked((bool) $commercialValue('allow_direct_purchase', false))
                >
                <span>Permitir compra directa</span>
            </label>
            @error('commercial.allow_direct_purchase')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="commercial_stock_policy_mode">Política de stock</label>
            <select id="commercial_stock_policy_mode" name="commercial[stock_policy_mode]" class="form-control">
                <option value="inherit" @selected($stockPolicyMode === 'inherit')>Heredar de la tienda</option>
                <option value="disabled" @selected($stockPolicyMode === 'disabled')>Sin control de stock de tienda</option>
                <option value="controlled" @selected($stockPolicyMode === 'controlled')>Control específico del artículo</option>
            </select>
            <div class="form-help">
                Heredar usa la política general de la tienda cuando exista contrato real. Sin control no aplica control
                de stock de tienda. Control específico prepara una política propia para este artículo.
            </div>
            @error('commercial.stock_policy_mode')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="commercial_shop_stock_limit">Cupo máximo asignado a tienda</label>
            <input
                id="commercial_shop_stock_limit"
                class="form-control"
                name="commercial[shop_stock_limit]"
                type="number"
                min="0"
                step="0.01"
                value="{{ old('commercial.shop_stock_limit', $commercialValue('shop_stock_limit')) }}"
            >
            @error('commercial.shop_stock_limit')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="commercial_stock_target_quantity">Stock objetivo</label>
            <input
                id="commercial_stock_target_quantity"
                class="form-control"
                name="commercial[stock_target_quantity]"
                type="number"
                min="0"
                step="0.01"
                value="{{ old('commercial.stock_target_quantity', $commercialValue('stock_target_quantity')) }}"
            >
            @error('commercial.stock_target_quantity')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="commercial_stock_protected_quantity">Stock protegido</label>
            <input
                id="commercial_stock_protected_quantity"
                class="form-control"
                name="commercial[stock_protected_quantity]"
                type="number"
                min="0"
                step="0.01"
                value="{{ old('commercial.stock_protected_quantity', $commercialValue('stock_protected_quantity')) }}"
            >
            @error('commercial.stock_protected_quantity')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-check">
                <input
                    class="form-checkbox"
                    type="checkbox"
                    name="commercial[allow_target_margin]"
                    value="1"
                    @checked((bool) $commercialValue('allow_target_margin', false))
                >
                <span>Permitir margen entre stock objetivo y protegido</span>
            </label>
            @error('commercial.allow_target_margin')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="commercial_max_quantity_per_checkout">Máximo por checkout</label>
            <input
                id="commercial_max_quantity_per_checkout"
                class="form-control"
                name="commercial[max_quantity_per_checkout]"
                type="number"
                min="1"
                step="1"
                value="{{ old('commercial.max_quantity_per_checkout', $commercialValue('max_quantity_per_checkout')) }}"
            >
            @error('commercial.max_quantity_per_checkout')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>
    </div>
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
