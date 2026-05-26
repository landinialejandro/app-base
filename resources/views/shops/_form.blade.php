{{-- FILE: resources/views/shops/_form.blade.php | V2 --}}

@php
    use App\Support\Catalogs\ShopCatalog;

    $statusLabels = ShopCatalog::statusLabels();
    $storedCommercial = data_get($shop ?? null, 'meta.commercial', []);
    $oldCommercial = old('commercial');
    $commercial = is_array($oldCommercial) ? $oldCommercial : $storedCommercial;

    $commercialValue = fn (string $key, mixed $default = false) => data_get($commercial, $key, $default);
    $providerTarget = $commercialValue('provider_target', 'simulated');
@endphp

<div class="form-group">
    <label class="form-label" for="name">Nombre</label>
    <input
        id="name"
        class="form-control"
        name="name"
        type="text"
        value="{{ old('name', $shop->name ?? '') }}"
        required
    >
    @error('name')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label class="form-label" for="status">Estado</label>
    <select id="status" name="status" class="form-control">
        @foreach ($statusLabels as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $shop->status ?? ShopCatalog::defaultStatus()) === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    <div class="form-help">
        Solo una tienda puede estar activa por empresa. Al activar una tienda, las demás quedarán inactivas.
    </div>
    @error('status')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label class="form-label" for="description">Descripción</label>
    <textarea id="description" class="form-control" name="description" rows="5">{{ old('description', $shop->description ?? '') }}</textarea>
    <div class="form-help">
        Uso interno para describir el alcance de esta configuración de tienda.
    </div>
    @error('description')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-section">
    <h2 class="section-title">Configuración comercial general</h2>

    <p class="form-help">
        Estos parámetros son preparatorios. No activan por sí solos Orders, Payments, Inventory, provider real ni compra
        directa.
    </p>

    <div class="detail-grid">
        <div class="form-group">
            <label class="form-check">
                <input
                    class="form-checkbox"
                    type="checkbox"
                    name="commercial[checkout_enabled]"
                    value="1"
                    @checked((bool) $commercialValue('checkout_enabled'))
                >
                <span>Habilitar checkout de tienda</span>
            </label>
            @error('commercial.checkout_enabled')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-check">
                <input
                    class="form-checkbox"
                    type="checkbox"
                    name="commercial[direct_purchase_enabled]"
                    value="1"
                    @checked((bool) $commercialValue('direct_purchase_enabled'))
                >
                <span>Habilitar compra directa</span>
            </label>
            @error('commercial.direct_purchase_enabled')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-check">
                <input
                    class="form-checkbox"
                    type="checkbox"
                    name="commercial[stock_control_enabled]"
                    value="1"
                    @checked((bool) $commercialValue('stock_control_enabled'))
                >
                <span>Usar control de stock para tienda</span>
            </label>
            @error('commercial.stock_control_enabled')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-check">
                <input
                    class="form-checkbox"
                    type="checkbox"
                    name="commercial[allow_stock_margin]"
                    value="1"
                    @checked((bool) $commercialValue('allow_stock_margin'))
                >
                <span>Permitir margen entre stock objetivo y stock protegido</span>
            </label>
            @error('commercial.allow_stock_margin')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="commercial_provider_target">Provider objetivo</label>
            <select id="commercial_provider_target" name="commercial[provider_target]" class="form-control">
                <option value="simulated" @selected($providerTarget === 'simulated')>Entorno simulado</option>
                <option value="mercado_pago" @selected($providerTarget === 'mercado_pago')>Mercado Pago</option>
                <option value="modo" @selected($providerTarget === 'modo')>MODO</option>
            </select>
            @error('commercial.provider_target')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>
