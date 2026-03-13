{{-- FILE: resources/views/documents/_form.blade.php --}}

@php
    use App\Support\Catalogs\DocumentCatalog;
@endphp

<div class="form-group">
    <label for="party_id" class="form-label">Contacto</label>
    <select name="party_id" id="party_id" class="form-control">
        <option value="">Seleccionar contacto</option>
        @foreach ($parties as $party)
            <option value="{{ $party->id }}" @selected(old('party_id', $document->party_id ?? '') == $party->id)>
                {{ $party->name }}
            </option>
        @endforeach
    </select>
    @error('party_id')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="order_id" class="form-label">Orden asociada</label>
    <select name="order_id" id="order_id" class="form-control">
        <option value="">Sin orden asociada</option>
        @foreach ($orders as $order)
            <option value="{{ $order->id }}" @selected(old('order_id', $document->order_id ?? '') == $order->id)>
                {{ $order->number ?: 'Sin número' }}
            </option>
        @endforeach
    </select>
    @error('order_id')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="kind" class="form-label">Tipo</label>
    <select name="kind" id="kind" class="form-control" required>
        @foreach (DocumentCatalog::kindLabels() as $value => $label)
            <option value="{{ $value }}" @selected(old('kind', $document->kind ?? DocumentCatalog::KIND_QUOTE) === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    @error('kind')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="number" class="form-label">Número</label>
    <input type="text" name="number" id="number" class="form-control"
        value="{{ old('number', $document->number ?? '') }}">
    @error('number')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="status" class="form-label">Estado</label>
    <select name="status" id="status" class="form-control" required>
        @foreach (DocumentCatalog::statusLabels() as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $document->status ?? DocumentCatalog::STATUS_DRAFT) === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    @error('status')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="issued_at" class="form-label">Fecha de emisión</label>
    <input type="date" name="issued_at" id="issued_at" class="form-control"
        value="{{ old('issued_at', isset($document) && $document->issued_at ? $document->issued_at->format('Y-m-d') : '') }}">
    @error('issued_at')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="notes" class="form-label">Notas</label>
    <textarea name="notes" id="notes" class="form-control"
        rows="4">{{ old('notes', $document->notes ?? '') }}</textarea>
    @error('notes')
        <div class="form-help">{{ $message }}</div>
    @enderror
</div>