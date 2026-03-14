{{-- FILE: resources/views/orders/_form.blade.php --}}

@php
use App\Support\Catalogs\OrderCatalog;

$orderExists = isset($order) && $order->exists;
$orderIsNumbered = $orderExists && !empty($order->number);
@endphp

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
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="kind" class="form-label">Tipo</label>

    @if ($orderIsNumbered)
        <select id="kind" class="form-control" disabled>
            @foreach (OrderCatalog::kindLabels() as $value => $label)
                <option value="{{ $value }}" @selected(old('kind', $order->kind) === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>

        <input type="hidden" name="kind" value="{{ old('kind', $order->kind) }}">

        <div class="form-help">El tipo no puede cambiarse una vez numerada la orden.</div>
    @else
        <select name="kind" id="kind" class="form-control" required>
            @foreach (OrderCatalog::kindLabels() as $value => $label)
                <option value="{{ $value }}"
                    @selected(old('kind', $order->kind ?? OrderCatalog::KIND_SALE) === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    @endif

    @error('kind')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label class="form-label">Número</label>

    @if ($orderExists)
        <input type="text" class="form-control" value="{{ $order->number ?: 'Se asignará al guardar' }}" disabled>
        <div class="form-help">La numeración es automática y no editable.</div>
    @else
        <input type="text" class="form-control" value="Se asignará automáticamente al guardar" disabled>
        <div class="form-help">El número se genera automáticamente por tenant, tipo y punto de venta.</div>
    @endif
</div>

<div class="form-group">
    <label for="status" class="form-label">Estado</label>
    <select name="status" id="status" class="form-control" required>
        @foreach (OrderCatalog::statusLabels() as $value => $label)
            <option value="{{ $value }}"
                @selected(old('status', $order->status ?? OrderCatalog::STATUS_DRAFT) === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    @error('status')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="ordered_at" class="form-label">Fecha</label>
    <input
        type="date"
        name="ordered_at"
        id="ordered_at"
        class="form-control"
        value="{{ old('ordered_at', isset($order) && $order->ordered_at ? $order->ordered_at->format('Y-m-d') : '') }}">

    @error('ordered_at')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label for="notes" class="form-label">Notas</label>
    <textarea
        name="notes"
        id="notes"
        class="form-control"
        rows="4">{{ old('notes', $order->notes ?? '') }}</textarea>

    @error('notes')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>