@php
    $currentKind = old('kind', $party->kind ?? '');
@endphp

<div class="form-group">
    <label for="kind" class="form-label">Tipo</label>
    <select name="kind" id="kind" class="form-control" required>
        <option value="">Seleccionar...</option>
        <option value="client" @selected($currentKind === 'client')>Cliente</option>
        <option value="supplier" @selected($currentKind === 'supplier')>Proveedor</option>
        <option value="employee" @selected($currentKind === 'employee')>Empleado</option>
        <option value="contact" @selected($currentKind === 'contact')>Contacto</option>
        <option value="contractor" @selected($currentKind === 'contractor')>Contratista</option>
    </select>
</div>

<div class="form-group">
    <label for="name" class="form-label">Nombre</label>
    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $party->name ?? '') }}"
        required>
</div>

<div class="form-group">
    <label for="display_name" class="form-label">Nombre visible</label>
    <input type="text" name="display_name" id="display_name" class="form-control"
        value="{{ old('display_name', $party->display_name ?? '') }}">
</div>

<div class="form-group">
    <label for="document_type" class="form-label">Tipo de documento</label>
    <input type="text" name="document_type" id="document_type" class="form-control"
        value="{{ old('document_type', $party->document_type ?? '') }}">
</div>

<div class="form-group">
    <label for="document_number" class="form-label">Número de documento</label>
    <input type="text" name="document_number" id="document_number" class="form-control"
        value="{{ old('document_number', $party->document_number ?? '') }}">
</div>

<div class="form-group">
    <label for="tax_id" class="form-label">CUIT / Tax ID</label>
    <input type="text" name="tax_id" id="tax_id" class="form-control" value="{{ old('tax_id', $party->tax_id ?? '') }}">
</div>

<div class="form-group">
    <label for="email" class="form-label">Email</label>
    <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $party->email ?? '') }}">
</div>

<div class="form-group">
    <label for="phone" class="form-label">Teléfono</label>
    <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone', $party->phone ?? '') }}">
</div>

<div class="form-group">
    <label for="address" class="form-label">Dirección</label>
    <input type="text" name="address" id="address" class="form-control"
        value="{{ old('address', $party->address ?? '') }}">
</div>

<div class="form-group">
    <label for="notes" class="form-label">Notas</label>
    <textarea name="notes" id="notes" class="form-control" rows="4">{{ old('notes', $party->notes ?? '') }}</textarea>
</div>

<div class="form-group">
    <label for="is_active" class="form-label">Activo</label>
    <input type="checkbox" name="is_active" id="is_active" class="form-checkbox" value="1" @checked(old('is_active', $party->is_active ?? true))>
</div>