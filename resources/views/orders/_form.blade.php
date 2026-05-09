{{-- FILE: resources/views/orders/_form.blade.php | V22 --}}

@php
    use App\Support\Catalogs\OrderCatalog;

    $groupLocked = $groupLocked ?? false;

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

    $currentCounterpartyReference = old('counterparty_reference', $order->counterparty_reference ?? '');
    $currentAssetReference = old('asset_reference', $order->asset_reference ?? '');

    $relationshipBoundary = $relationshipBoundary ?? [
        'party' => [
            'mode' => 'manual',
            'surface' => null,
        ],
        'asset' => [
            'mode' => 'manual',
            'surface' => null,
        ],
    ];

    $partyBoundary = $relationshipBoundary['party'] ?? ['mode' => 'manual', 'surface' => null];
    $assetBoundary = $relationshipBoundary['asset'] ?? ['mode' => 'manual', 'surface' => null];

    $partyMode = $partyBoundary['mode'] ?? 'manual';
    $assetMode = $assetBoundary['mode'] ?? 'manual';

    $partyOptions = $partyBoundary['surface']['data']['partyOptions'] ?? collect();
    $assetOptions = $assetBoundary['surface']['data']['assetOptions'] ?? collect();

    $hasManagedPartyOptions = $partyMode === 'external'
        && !empty($partyBoundary['surface']['view'])
        && is_countable($partyOptions)
        && count($partyOptions) > 0;

    $hasManagedAssetOptions = $assetMode === 'external'
        && !empty($assetBoundary['surface']['view'])
        && is_countable($assetOptions)
        && count($assetOptions) > 0;

    $lockGroupField = $orderIsNumbered || $groupLocked;
    $lockedGroup = $orderExists ? $order->group : $prefilledGroup;
    $lockedKind = $orderExists ? $order->kind : $prefilledKind;
@endphp

<div class="form" data-action="app-linked-select-sync" data-source-select="#party_id" data-target-select="#asset_id">
    @if ($hasManagedPartyOptions)
        @include($partyBoundary['surface']['view'], $partyBoundary['surface']['data'] ?? [])
        <input type="hidden" name="counterparty_reference" value="{{ $currentCounterpartyReference }}">
    @else
        <div class="form-group">
            <label for="counterparty_reference" class="form-label">Contraparte</label>
            <input type="text" name="counterparty_reference" id="counterparty_reference" class="form-control"
                value="{{ $currentCounterpartyReference }}" maxlength="255" required>
            <div class="form-help">
                Requerida cuando no hay contacto gestionado disponible.
            </div>
            @error('counterparty_reference')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>
    @endif

    @if ($hasManagedAssetOptions)
        @include($assetBoundary['surface']['view'], $assetBoundary['surface']['data'] ?? [])
        <input type="hidden" name="asset_reference" value="{{ $currentAssetReference }}">
    @else
        <div class="form-group">
            <label for="asset_reference" class="form-label">Referencia de activo</label>
            <input type="text" name="asset_reference" id="asset_reference" class="form-control"
                value="{{ $currentAssetReference }}" maxlength="255">
            <div class="form-help">
                Opcional cuando no hay activo gestionado disponible.
            </div>
            @error('asset_reference')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>
    @endif

    <div class="form-group">
        <label for="group" class="form-label">Tipo</label>

        @if ($lockGroupField)
            <select class="form-control" disabled>
                @foreach (OrderCatalog::groupLabels() as $value => $label)
                    <option @selected($lockedGroup === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <input type="hidden" name="group" value="{{ $lockedGroup }}">
            <input type="hidden" name="kind" value="{{ old('kind', $lockedKind) }}">
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

<x-dev-component-version name="orders._form" version="V22" align="right" />