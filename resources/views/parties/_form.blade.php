<div class="mb-3">
    <label for="kind" class="form-label">Tipo</label>
    <select name="kind" id="kind" class="form-control" required>
        @php
            $currentKind = old('kind', $party->kind ?? '');
        @endphp
        <option value="">Seleccionar...</option>
        <option value="client" @selected($currentKind === 'client')>Cliente</option>
        <option value="supplier" @selected($currentKind === 'supplier')>Proveedor</option>
        <option value="employee" @selected($currentKind === 'employee')>Empleado</option>
        <option value="contact" @selected($currentKind === 'contact')>Contacto</option>
        <option value="contractor" @selected($currentKind === 'contractor')>Contratista</option>
    </select>
    @error('kind')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="name" class="form-label">Nombre</label>
    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $party->name ?? '') }}"
        required>
    @error('name')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="display_name" class="form-label">Nombre visible</label>
    <input type="text" name="display_name" id="display_name" class="form-control"
        value="{{ old('display_name', $party->display_name ?? '') }}">
    @error('display_name')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="document_type" class="form-label">Tipo de documento</label>
    <input type="text" name="document_type" id="document_type" class="form-control"
        value="{{ old('document_type', $party->document_type ?? '') }}">
    @error('document_type')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="document_number" class="form-label">Número de documento</label>
    <input type="text" name="document_number" id="document_number" class="form-control"
        value="{{ old('document_number', $party->document_number ?? '') }}">
    @error('document_number')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="tax_id" class="form-label">CUIT / Tax ID</label>
    <input type="text" name="tax_id" id="tax_id" class="form-control" value="{{ old('tax_id', $party->tax_id ?? '') }}">
    @error('tax_id')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="email" class="form-label">Email</label>
    <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $party->email ?? '') }}">
    @error('email')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="phone" class="form-label">Teléfono</label>
    <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone', $party->phone ?? '') }}">
    @error('phone')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="address" class="form-label">Dirección</label>
    <input type="text" name="address" id="address" class="form-control"
        value="{{ old('address', $party->address ?? '') }}">
    @error('address')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="notes" class="form-label">Notas</label>
    <textarea name="notes" id="notes" class="form-control" rows="4">{{ old('notes', $party->notes ?? '') }}</textarea>
    @error('notes')
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="form-check mb-3">
    <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" @checked(old('is_active', $party->is_active ?? true))>
    <label for="is_active" class="form-check-label">Activo</label>
</div>

<button type="submit" class="btn btn-primary">Guardar</button>
<a href="{{ route('parties.index') }}" class="btn btn-secondary">Cancelar</a>