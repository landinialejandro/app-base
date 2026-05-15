{{-- FILE: resources/views/shops/_form.blade.php | V1 --}}

@php
    use App\Models\Shop;

    $statusLabels = [
        Shop::STATUS_DRAFT => 'Borrador',
        Shop::STATUS_ACTIVE => 'Activa',
        Shop::STATUS_INACTIVE => 'Inactiva',
    ];
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
            <option value="{{ $value }}" @selected(old('status', $shop->status ?? Shop::STATUS_DRAFT) === $value)>
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