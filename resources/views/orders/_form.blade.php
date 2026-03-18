{{-- FILE: resources/views/orders/_form.blade.php --}}

@php
    use App\Support\Catalogs\OrderCatalog;

    $orderExists = isset($order) && $order->exists;
    $orderIsNumbered = $orderExists && !empty($order->number);

    $prefilledAsset = $prefilledAsset ?? null;
    $prefilledPartyId = $prefilledPartyId ?? null;
    $fromAsset = $fromAsset ?? false;
    $prefilledTask = $prefilledTask ?? null;
    $prefilledKind = $prefilledKind ?? old('kind', $order->kind ?? OrderCatalog::KIND_SALE);

    $lockedByExistingAsset = $orderExists && !empty($order->asset_id);
    $lockPartyAndAsset = $fromAsset || $lockedByExistingAsset;

    $currentTaskId = old('task_id', $order->task_id ?? ($prefilledTask?->id ?? ''));
    $currentTaskName = $prefilledTask?->name ?? ($order->task?->name ?? '');
@endphp

<div data-action="app-party-asset-sync" data-party-select="#party_id" data-asset-select="#asset_id">

    @if ($currentTaskId)
        <div class="form-group">
            <label class="form-label">Tarea origen</label>
            <input type="text" class="form-control" value="{{ $currentTaskName }}" disabled>
            <input type="hidden" name="task_id" value="{{ $currentTaskId }}">
            <div class="form-help">Cada tarea puede tener una sola orden asociada.</div>
            @error('task_id')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>
    @endif

    <div class="form-group">
        <label for="party_id" class="form-label">Contacto</label>

        @if ($lockPartyAndAsset)
            <select id="party_id_display" class="form-control" disabled>
                @foreach ($parties as $party)
                    <option value="{{ $party->id }}" @selected(old('party_id', $order->party_id ?? ($prefilledPartyId ?? '')) == $party->id)>
                        {{ $party->name }}
                    </option>
                @endforeach
            </select>

            <input type="hidden" name="party_id"
                value="{{ old('party_id', $order->party_id ?? ($prefilledPartyId ?? '')) }}">

            @if ($fromAsset)
                <div class="form-help">El contacto se toma automáticamente del activo seleccionado.</div>
            @else
                <div class="form-help">Esta orden ya quedó vinculada a un activo. Para preservar la trazabilidad, el
                    contacto no puede modificarse.</div>
            @endif
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

        @error('party_id')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="asset_id" class="form-label">Activo</label>

        @if ($lockPartyAndAsset)
            <select id="asset_id_display" class="form-control" disabled>
                <option value="">Sin activo asociado</option>
                @foreach ($assets as $asset)
                    <option value="{{ $asset->id }}" @selected(old('asset_id', $order->asset_id ?? ($prefilledAsset->id ?? '')) == $asset->id)>
                        {{ $asset->name }}
                        @if ($asset->internal_code)
                            — {{ $asset->internal_code }}
                        @endif
                        @if ($asset->party)
                            — {{ $asset->party->name }}
                        @endif
                    </option>
                @endforeach
            </select>

            <input type="hidden" name="asset_id"
                value="{{ old('asset_id', $order->asset_id ?? ($prefilledAsset->id ?? '')) }}">

            @if ($fromAsset)
                <div class="form-help">El activo se toma automáticamente desde el contexto de origen.</div>
            @else
                <div class="form-help">Esta orden ya quedó vinculada a un activo. Para preservar la trazabilidad, el
                    activo no puede modificarse.</div>
            @endif
        @else
            <select name="asset_id" id="asset_id" class="form-control">
                <option value="">Sin activo asociado</option>
                @foreach ($assets as $asset)
                    <option value="{{ $asset->id }}" data-party-id="{{ $asset->party_id }}"
                        @selected(old('asset_id', $order->asset_id ?? ($prefilledAsset->id ?? '')) == $asset->id)>
                        {{ $asset->name }}
                        @if ($asset->internal_code)
                            — {{ $asset->internal_code }}
                        @endif
                        @if ($asset->party)
                            — {{ $asset->party->name }}
                        @endif
                    </option>
                @endforeach
            </select>
            <div class="form-help">Si seleccionas un activo, debe corresponder al contacto elegido.</div>
        @endif

        @error('asset_id')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="kind" class="form-label">Tipo</label>

        @if ($fromAsset || $currentTaskId)
            <select id="kind_display" class="form-control" disabled>
                @foreach (OrderCatalog::kindLabels() as $value => $label)
                    <option value="{{ $value }}" @selected(OrderCatalog::KIND_SERVICE === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            <input type="hidden" name="kind" value="{{ OrderCatalog::KIND_SERVICE }}">
            <div class="form-help">Las órdenes creadas desde una tarea o un activo se generan como órdenes de servicio.
            </div>
        @elseif ($orderIsNumbered)
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
                    <option value="{{ $value }}" @selected(old('kind', $order->kind ?? ($prefilledKind ?? OrderCatalog::KIND_SALE)) === $value)>
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
            <input type="text" class="form-control" value="{{ $order->number ?: 'Se asignará al guardar' }}"
                disabled>
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
                <option value="{{ $value }}" @selected(old('status', $order->status ?? OrderCatalog::STATUS_DRAFT) === $value)>
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
        <input type="date" name="ordered_at" id="ordered_at" class="form-control"
            value="{{ old('ordered_at', isset($order) && $order->ordered_at ? $order->ordered_at->format('Y-m-d') : now()->format('Y-m-d')) }}"
            required>

        <div class="form-help">Si no se modifica, se usará la fecha actual. Se permite hasta 30 días hacia el futuro.
        </div>

        @error('ordered_at')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="notes" class="form-label">Notas</label>
        <textarea name="notes" id="notes" class="form-control" rows="4">{{ old('notes', $order->notes ?? '') }}</textarea>

        @error('notes')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>
</div>
