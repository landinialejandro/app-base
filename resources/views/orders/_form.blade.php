{{-- FILE: resources/views/orders/_form.blade.php | V13 --}}

@php
    use App\Support\Catalogs\OrderCatalog;

    $orderExists = isset($order) && $order->exists;
    $orderIsNumbered = $orderExists && !empty($order->number);

    $prefilledGroup = $prefilledGroup ?? old('group', $order->group ?? OrderCatalog::GROUP_SALE);
    $prefilledKind = $prefilledKind ?? old('kind', $order->kind ?? OrderCatalog::KIND_STANDARD);

    $currentStatus = old('status', $order->status ?? OrderCatalog::STATUS_DRAFT);

    $statusOptions = OrderCatalog::statusLabels();

    if ($orderExists) {
        $statusOptions = collect(OrderCatalog::statusLabels())
            ->filter(fn($label, $value) => OrderCatalog::canTransition($order->status, $value))
            ->prepend(OrderCatalog::statusLabel($order->status), $order->status)
            ->all();
    }

    $statusHelp = $orderExists
        ? 'El backend validará la transición de estado y bloqueará cambios inválidos.'
        : 'La orden comienza normalmente en borrador y su transición futura debe validarse desde backend.';

    $currentCounterpartyName = old('counterparty_name', $order->counterparty_name ?? '');
@endphp

<div class="form">
    <div class="form-group">
        <label for="counterparty_name" class="form-label">Contraparte</label>
        <input type="text" name="counterparty_name" id="counterparty_name" class="form-control"
            value="{{ $currentCounterpartyName }}" maxlength="255" required>
        <div class="form-help">Requerido. La orden conserva este dato propio.</div>
        @error('counterparty_name')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="group" class="form-label">Tipo</label>

        @if ($orderIsNumbered)
            <select class="form-control" disabled>
                @foreach (OrderCatalog::groupLabels() as $value => $label)
                    <option @selected($order->group === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <input type="hidden" name="group" value="{{ $order->group }}">
            <input type="hidden" name="kind" value="{{ old('kind', $order->kind ?? $prefilledKind) }}">
        @else
            <select name="group" id="group" class="form-control" required>
                @foreach (OrderCatalog::groupLabels() as $value => $label)
                    <option value="{{ $value }}" @selected(old('group', $order->group ?? $prefilledGroup) === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            <input type="hidden" name="kind" value="{{ old('kind', $order->kind ?? $prefilledKind) }}">
        @endif
    </div>

    <div class="form-group">
        <label for="status" class="form-label">Estado</label>
        <select name="status" id="status" class="form-control" required>
            @foreach ($statusOptions as $value => $label)
                <option value="{{ $value }}" @selected($currentStatus === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <div class="form-help">{{ $statusHelp }}</div>
        @error('status')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="ordered_at" class="form-label">Fecha</label>
        <input type="date" name="ordered_at" id="ordered_at" class="form-control"
            value="{{ old('ordered_at', isset($order) && $order->ordered_at ? $order->ordered_at->format('Y-m-d') : now()->format('Y-m-d')) }}"
            required>
    </div>

    <div class="form-group">
        <label for="notes" class="form-label">Notas</label>
        <textarea name="notes" id="notes" class="form-control" rows="4">{{ old('notes', $order->notes ?? '') }}</textarea>
    </div>
</div>

<x-dev-component-version name="orders._form" version="V13" align="right" />