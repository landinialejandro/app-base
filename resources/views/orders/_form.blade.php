<div class="form-group">
    <label for="party_id" class="form-label">Contacto</label>
    <select name="party_id" id="party_id" class="form-control">
        <option value="">Seleccionar contacto</option>
        @foreach ($parties as $party)
            <option value="{{ $party->id }}" @selected(old('party_id', $order->party_id ?? '') == $party->id)>
                {{ $party->name }}
            </option>
        @endforeach
    </select>
    @error('party_id')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="kind" class="form-label">Tipo</label>
    <select name="kind" id="kind" class="form-control" required>
        <option value="sale" @selected(old('kind', $order->kind ?? 'sale') === 'sale')>Venta</option>
        <option value="purchase" @selected(old('kind', $order->kind ?? '') === 'purchase')>Compra</option>
        <option value="service" @selected(old('kind', $order->kind ?? '') === 'service')>Servicio</option>
    </select>
    @error('kind')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="number" class="form-label">Número</label>
    <input type="text" name="number" id="number" class="form-control" value="{{ old('number', $order->number ?? '') }}">
    @error('number')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="status" class="form-label">Estado</label>
    <select name="status" id="status" class="form-control" required>
        <option value="draft" @selected(old('status', $order->status ?? 'draft') === 'draft')>Borrador</option>
        <option value="confirmed" @selected(old('status', $order->status ?? '') === 'confirmed')>Confirmada</option>
        <option value="cancelled" @selected(old('status', $order->status ?? '') === 'cancelled')>Cancelada</option>
    </select>
    @error('status')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="ordered_at" class="form-label">Fecha</label>
    <input type="date" name="ordered_at" id="ordered_at" class="form-control"
        value="{{ old('ordered_at', isset($order) && $order->ordered_at ? $order->ordered_at->format('Y-m-d') : '') }}">
    @error('ordered_at')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="notes" class="form-label">Notas</label>
    <textarea name="notes" id="notes" class="form-control" rows="4">{{ old('notes', $order->notes ?? '') }}</textarea>
    @error('notes')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>