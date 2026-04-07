{{-- FILE: resources/views/orders/_form.blade.php | V6 --}}

@php
    use App\Support\Catalogs\OrderCatalog;

    $orderExists = isset($order) && $order->exists;
    $orderIsNumbered = $orderExists && !empty($order->number);

    $prefilledAsset = $prefilledAsset ?? null;
    $prefilledPartyId = $prefilledPartyId ?? null;
    $fromAsset = $fromAsset ?? false;
    $prefilledTask = $prefilledTask ?? null;
    $prefilledAppointment = $prefilledAppointment ?? null;

    $prefilledKind = $prefilledKind ?? old('kind', $order->kind ?? OrderCatalog::KIND_SALE);

    $currentTaskId = old('task_id', $order->task_id ?? ($prefilledTask?->id ?? ''));
    $currentTaskName = old('task_name', $prefilledTask?->name ?? ($order->task?->name ?? ''));

    $currentAppointmentId = old('appointment_id', $prefilledAppointment?->id ?? '');
    $currentAppointmentLabel = old(
        'appointment_label',
        $prefilledAppointment?->title ?? ($currentAppointmentId ? 'Turno #' . $currentAppointmentId : ''),
    );

    $lockedByExistingAsset = $orderExists && !empty($order->asset_id);
    $lockPartyAndAsset = $fromAsset || $lockedByExistingAsset;

    $supportsAssetsModule = $supportsAssetsModule ?? true;
@endphp

<div class="form" data-action="app-party-asset-sync" data-party-select="#party_id"
    @if ($supportsAssetsModule) data-asset-select="#asset_id" @endif>

    {{-- TAREA --}}
    @if ($currentTaskId)
        <div class="form-group">
            <label class="form-label">Tarea origen</label>
            <input type="text" class="form-control" value="{{ $currentTaskName }}" disabled>
            <input type="hidden" name="task_id" value="{{ $currentTaskId }}">
            <div class="form-help">Cada tarea puede tener una sola orden asociada.</div>
        </div>
    @endif

    {{-- TURNO --}}
    @if ($currentAppointmentId)
        <div class="form-group">
            <label class="form-label">Turno origen</label>
            <input type="text" class="form-control" value="{{ $currentAppointmentLabel }}" disabled>
            <input type="hidden" name="appointment_id" value="{{ $currentAppointmentId }}">
        </div>
    @endif

    {{-- CONTACTO --}}
    <div class="form-group">
        <label for="party_id" class="form-label">Contacto</label>

        @if ($lockPartyAndAsset)
            <select class="form-control" disabled>
                @foreach ($parties as $party)
                    <option value="{{ $party->id }}" @selected(old('party_id', $order->party_id ?? ($prefilledPartyId ?? '')) == $party->id)>
                        {{ $party->name }}
                    </option>
                @endforeach
            </select>

            <input type="hidden" name="party_id"
                value="{{ old('party_id', $order->party_id ?? ($prefilledPartyId ?? '')) }}">

            <div class="form-help">
                {{ $fromAsset
                    ? 'El contacto se toma automáticamente del activo.'
                    : 'No puede modificarse porque la orden está vinculada a un activo.' }}
            </div>
        @else
            <select name="party_id" id="party_id" class="form-control" required>
                <option value="">Seleccionar contacto</option>
                @foreach ($parties as $party)
                    <option value="{{ $party->id }}" @selected(old('party_id', $order->party_id ?? ($prefilledPartyId ?? '')) == $party->id)>
                        {{ $party->name }}
                    </option>
                @endforeach
            </select>
        @endif
    </div>

    {{-- ACTIVO (CONDICIONAL) --}}
    @if ($supportsAssetsModule)
        <div class="form-group">
            <label for="asset_id" class="form-label">Activo</label>

            @if ($lockPartyAndAsset)
                <select class="form-control" disabled>
                    <option value="">Sin activo asociado</option>
                    @foreach ($assets as $asset)
                        <option value="{{ $asset->id }}" @selected(old('asset_id', $order->asset_id ?? ($prefilledAsset->id ?? '')) == $asset->id)>
                            {{ $asset->name }}
                        </option>
                    @endforeach
                </select>

                <input type="hidden" name="asset_id"
                    value="{{ old('asset_id', $order->asset_id ?? ($prefilledAsset->id ?? '')) }}">
            @else
                <select name="asset_id" id="asset_id" class="form-control">
                    <option value="">Sin activo asociado</option>
                    @foreach ($assets as $asset)
                        <option value="{{ $asset->id }}" data-party-id="{{ $asset->party_id }}"
                            @selected(old('asset_id', $order->asset_id ?? ($prefilledAsset->id ?? '')) == $asset->id)>
                            {{ $asset->name }}
                        </option>
                    @endforeach
                </select>
            @endif
        </div>
    @endif

    {{-- TIPO --}}
    <div class="form-group">
        <label for="kind" class="form-label">Tipo</label>

        @if ($fromAsset || $currentTaskId)
            <select class="form-control" disabled>
                @foreach (OrderCatalog::kindLabels() as $value => $label)
                    <option @selected(OrderCatalog::KIND_SERVICE === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            <input type="hidden" name="kind" value="{{ OrderCatalog::KIND_SERVICE }}">
        @elseif ($orderIsNumbered)
            <select class="form-control" disabled>
                @foreach (OrderCatalog::kindLabels() as $value => $label)
                    <option @selected($order->kind === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            <input type="hidden" name="kind" value="{{ $order->kind }}">
        @else
            <select name="kind" id="kind" class="form-control" required>
                @foreach (OrderCatalog::kindLabels() as $value => $label)
                    <option value="{{ $value }}" @selected(old('kind', $order->kind ?? $prefilledKind) === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        @endif
    </div>

    {{-- RESTO (SIN CAMBIOS) --}}
    <div class="form-group">
        <label for="status" class="form-label">Estado</label>
        <select name="status" id="status" class="form-control" required>
            @foreach (OrderCatalog::statusLabels() as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $order->status ?? OrderCatalog::STATUS_DRAFT) === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
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
