{{-- FILE: resources/views/parties/_form.blade.php | V3 --}}

@php
    use App\Support\Catalogs\PartyCatalog;

    $party = $party ?? null;
    $allowedKinds = $allowedKinds ?? array_keys(PartyCatalog::kindLabels());
    $defaultKind = $defaultKind ?? null;

    $currentKind = old(
        'kind',
        $party->kind ??
            ($defaultKind ??
                (in_array(PartyCatalog::KIND_CUSTOMER, $allowedKinds, true)
                    ? PartyCatalog::KIND_CUSTOMER
                    : $allowedKinds[0] ?? null)),
    );
@endphp

<div class="form-group">
    <label for="kind" class="form-label">Tipo</label>
    <select name="kind" id="kind" class="form-control" required>
        @foreach (PartyCatalog::kindLabels() as $value => $label)
            @continue(!in_array($value, $allowedKinds, true))

            <option value="{{ $value }}" @selected($currentKind === $value)>
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
        value="{{ old('name', $party->name ?? '') }}" required>
    @error('name')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="display_name" class="form-label">Nombre visible</label>
    <input type="text" id="display_name" name="display_name" class="form-control"
        value="{{ old('display_name', $party->display_name ?? '') }}">
    @error('display_name')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="document_type" class="form-label">Tipo de documento</label>
    <input type="text" id="document_type" name="document_type" class="form-control"
        value="{{ old('document_type', $party->document_type ?? '') }}">
    @error('document_type')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="document_number" class="form-label">Número de documento</label>
    <input type="text" id="document_number" name="document_number" class="form-control"
        value="{{ old('document_number', $party->document_number ?? '') }}">
    @error('document_number')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="tax_id" class="form-label">CUIT / ID fiscal</label>
    <input type="text" id="tax_id" name="tax_id" class="form-control"
        value="{{ old('tax_id', $party->tax_id ?? '') }}">
    @error('tax_id')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="email" class="form-label">Email</label>
    <input type="email" id="email" name="email" class="form-control"
        value="{{ old('email', $party->email ?? '') }}">
    @error('email')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="phone" class="form-label">Teléfono</label>
    <input type="text" id="phone" name="phone" class="form-control"
        value="{{ old('phone', $party->phone ?? '') }}">
    @error('phone')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="address" class="form-label">Dirección</label>
    <input type="text" id="address" name="address" class="form-control"
        value="{{ old('address', $party->address ?? '') }}">
    @error('address')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="notes" class="form-label">Notas</label>
    <textarea id="notes" name="notes" rows="4" class="form-control">{{ old('notes', $party->notes ?? '') }}</textarea>
    @error('notes')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label class="form-label" for="is_active">
        <input class="form-checkbox" type="checkbox" id="is_active" name="is_active" value="1"
            @checked(old('is_active', $party->is_active ?? true))>
        Activo
    </label>
    @error('is_active')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>
