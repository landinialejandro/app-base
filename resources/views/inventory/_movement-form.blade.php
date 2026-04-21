{{-- FILE: resources/views/inventory/_movement-form.blade.php | V1 --}}

@php
    use App\Support\Inventory\InventoryMovementService;

    $product = $product ?? null;

    $kindLabels = [
        InventoryMovementService::KIND_INGRESAR => 'Ingreso manual',
        InventoryMovementService::KIND_CONSUMIR => 'Egreso manual',
    ];

    $availableKinds = [InventoryMovementService::KIND_INGRESAR, InventoryMovementService::KIND_CONSUMIR];
@endphp

@if ($product)
    <div class="form-group">
        <label class="form-label">Artículo</label>
        <input type="text" class="form-control" value="{{ $product->name }}" disabled>
    </div>

    <div class="form-group">
        <label for="kind" class="form-label">Tipo de movimiento</label>
        <select id="kind" name="kind" class="form-control" required>
            <option value="">Seleccionar tipo</option>
            @foreach ($availableKinds as $kind)
                <option value="{{ $kind }}" @selected(old('kind') === $kind)>
                    {{ $kindLabels[$kind] ?? ucfirst($kind) }}
                </option>
            @endforeach
        </select>
        @error('kind')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="quantity" class="form-label">Cantidad</label>
        <input id="quantity" name="quantity" type="number" step="0.01" min="0.01" class="form-control"
            value="{{ old('quantity') }}" required>
        @error('quantity')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="notes" class="form-label">Motivo / notas</label>
        <input id="notes" name="notes" type="text" class="form-control" value="{{ old('notes') }}"
            placeholder="Opcional">
        @error('notes')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>
@endif
