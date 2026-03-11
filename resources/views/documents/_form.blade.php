{{-- FILE: resources/views/documents/_form.blade.php --}}
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
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="order_id" class="form-label">Orden asociada</label>
    <select name="order_id" id="order_id" class="form-control">
        <option value="">Sin orden asociada</option>
        @foreach ($orders as $order)
            <option value="{{ $order->id }}" @selected(old('order_id', $document->order_id ?? ($selectedOrderId ?? '')) == $order->id)>
                {{ $order->number ?: 'Sin número' }}
            </option>
        @endforeach
    </select>
    @error('order_id')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="kind" class="form-label">Tipo</label>
    <select name="kind" id="kind" class="form-control" required>
        <option value="quote" @selected(old('kind', $document->kind ?? 'quote') === 'quote')>Presupuesto</option>
        <option value="invoice" @selected(old('kind', $document->kind ?? '') === 'invoice')>Factura</option>
        <option value="delivery_note" @selected(old('kind', $document->kind ?? '') === 'delivery_note')>Remito</option>
        <option value="work_order" @selected(old('kind', $document->kind ?? '') === 'work_order')>Orden de trabajo
        </option>
        <option value="receipt" @selected(old('kind', $document->kind ?? '') === 'receipt')>Recibo</option>
        <option value="credit_note" @selected(old('kind', $document->kind ?? '') === 'credit_note')>Nota de crédito
        </option>
    </select>
    @error('kind')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="number" class="form-label">Número</label>
    <input type="text" name="number" id="number" class="form-control"
        value="{{ old('number', $document->number ?? '') }}">
    @error('number')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="status" class="form-label">Estado</label>
    <select name="status" id="status" class="form-control" required>
        <option value="draft" @selected(old('status', $document->status ?? 'draft') === 'draft')>Borrador</option>
        <option value="issued" @selected(old('status', $document->status ?? '') === 'issued')>Emitido</option>
        <option value="cancelled" @selected(old('status', $document->status ?? '') === 'cancelled')>Cancelado</option>
    </select>
    @error('status')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="issued_at" class="form-label">Fecha de emisión</label>
    <input type="date" name="issued_at" id="issued_at" class="form-control"
        value="{{ old('issued_at', isset($document) && $document->issued_at ? $document->issued_at->format('Y-m-d') : '') }}">
    @error('issued_at')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="due_at" class="form-label">Fecha de vencimiento</label>
    <input type="date" name="due_at" id="due_at" class="form-control"
        value="{{ old('due_at', isset($document) && $document->due_at ? $document->due_at->format('Y-m-d') : '') }}">
    @error('due_at')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="currency_code" class="form-label">Moneda</label>
    <input type="text" name="currency_code" id="currency_code" class="form-control"
        value="{{ old('currency_code', $document->currency_code ?? '') }}">
    @error('currency_code')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="notes" class="form-label">Notas</label>
    <textarea name="notes" id="notes" class="form-control"
        rows="4">{{ old('notes', $document->notes ?? '') }}</textarea>
    @error('notes')
        <div class="text-danger mt-1">{{ $message }}</div>
    @enderror
</div>