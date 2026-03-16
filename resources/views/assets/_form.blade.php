{{-- FILE: resources/views/assets/_form.blade.php --}}

@php
    use App\Support\Catalogs\AssetCatalog;
@endphp

@csrf

<div class="form-group">
    <label for="kind" class="form-label">Tipo</label>
    <select id="kind" name="kind" class="form-control" required>
        @foreach (AssetCatalog::kindLabels() as $value => $label)
            <option value="{{ $value }}" @selected(old('kind', $asset->kind ?? AssetCatalog::KIND_EQUIPMENT) === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    @error('kind')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="relationship_type" class="form-label">Relación</label>
    <select id="relationship_type" name="relationship_type" class="form-control" required>
        @foreach (AssetCatalog::relationshipTypeLabels() as $value => $label)
            <option value="{{ $value }}" @selected(old('relationship_type', $asset->relationship_type ?? AssetCatalog::RELATIONSHIP_OWNED) === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    @error('relationship_type')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="party_id" class="form-label">Contacto</label>
    <select id="party_id" name="party_id" class="form-control" required>
        <option value="">Seleccionar</option>
        @foreach ($parties as $party)
            <option value="{{ $party->id }}" @selected((string) old('party_id', $asset->party_id ?? '') === (string) $party->id)>
                {{ $party->name }}
            </option>
        @endforeach
    </select>
    @error('party_id')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="name" class="form-label">Nombre</label>
    <input type="text" id="name" name="name" class="form-control"
        value="{{ old('name', $asset->name ?? '') }}" required>
    @error('name')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="internal_code" class="form-label">Código interno</label>
    <input type="text" id="internal_code" name="internal_code" class="form-control"
        value="{{ old('internal_code', $asset->internal_code ?? '') }}">
    @error('internal_code')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="status" class="form-label">Estado</label>
    <select id="status" name="status" class="form-control" required>
        @foreach (AssetCatalog::statusLabels() as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $asset->status ?? AssetCatalog::STATUS_ACTIVE) === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    @error('status')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="notes" class="form-label">Notas</label>
    <textarea id="notes" name="notes" rows="4" class="form-control">{{ old('notes', $asset->notes ?? '') }}</textarea>
    @error('notes')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-actions">
    <button type="submit" class="btn btn-primary">Guardar</button>
    <a href="{{ isset($asset) ? route('assets.show', $asset) : route('assets.index') }}" class="btn btn-secondary">
        Cancelar
    </a>
</div>
