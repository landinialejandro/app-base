{{-- FILE: resources/views/parties/_form.blade.php --}}

@php
    use App\Support\Catalogs\PartyCatalog;
@endphp

@csrf

<div class="form-group">
    <label for="kind" class="form-label">Tipo</label>
    <select name="kind" id="kind" class="form-control" required>
        @foreach (PartyCatalog::kindLabels() as $value => $label)
            <option value="{{ $value }}"
                @selected(old('kind', $party->kind ?? PartyCatalog::KIND_CUSTOMER) === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    @error('kind')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="name" class="form-label">Nombre</label>
    <input
        type="text"
        id="name"
        name="name"
        class="form-control"
        value="{{ old('name', $party->name ?? '') }}"
        required>
    @error('name')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="display_name" class="form-label">Nombre visible</label>
    <input
        type="text"
        id="display_name"
        name="display_name"
        class="form-control"
        value="{{ old('display_name', $party->display_name ?? '') }}">
    @error('display_name')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="document_type" class="form-label">Tipo de documento</label>
    <input
        type="text"
        id="document_type"
        name="document_type"
        class="form-control"
        value="{{ old('document_type', $party->document_type ?? '') }}">
    @error('document_type')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="document_number" class="form-label">Número de documento</label>
    <input
        type="text"
        id="document_number"
        name="document_number"
        class="form-control"
        value="{{ old('document_number', $party->document_number ?? '') }}">
    @error('document_number')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="tax_id" class="form-label">CUIT / ID fiscal</label>
    <input
        type="text"
        id="tax_id"
        name="tax_id"
        class="form-control"
        value="{{ old('tax_id', $party->tax_id ?? '') }}">
    @error('tax_id')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="email" class="form-label">Email</label>
    <input
        type="email"
        id="email"
        name="email"
        class="form-control"
        value="{{ old('email', $party->email ?? '') }}">
    @error('email')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="phone" class="form-label">Teléfono</label>
    <input
        type="text"
        id="phone"
        name="phone"
        class="form-control"
        value="{{ old('phone', $party->phone ?? '') }}">
    @error('phone')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="address" class="form-label">Dirección</label>
    <input
        type="text"
        id="address"
        name="address"
        class="form-control"
        value="{{ old('address', $party->address ?? '') }}">
    @error('address')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="notes" class="form-label">Notas</label>
    <textarea
        id="notes"
        name="notes"
        rows="4"
        class="form-control">{{ old('notes', $party->notes ?? '') }}</textarea>
    @error('notes')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label class="form-label d-block">Activo</label>

    <div class="form-check">
        <input
            class="form-check-input"
            type="checkbox"
            id="is_active"
            name="is_active"
            value="1"
            @checked(old('is_active', $party->is_active ?? true))>

        <label class="form-check-label" for="is_active">
            Sí
        </label>
    </div>

    @error('is_active')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>
