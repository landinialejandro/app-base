{{-- FILE: resources/views/documents/_form.blade.php | V4 --}}

@php
    use App\Support\Catalogs\DocumentCatalog;

    $documentExists = isset($document) && $document->exists;
    $documentIsNumbered = $documentExists && !empty($document->number);

    $boundOrder = $order ?? ($document->order ?? null);

    $currentOrderId = old('order_id', $boundOrder?->id ?? ($document->order_id ?? ''));
    $currentPartyId = old('party_id', $boundOrder?->party_id ?? ($document->party_id ?? ''));
    $currentAssetId = old('asset_id', $boundOrder?->asset_id ?? ($document->asset_id ?? ''));

    $visibleKinds = collect(DocumentCatalog::kindLabels())->reject(
        fn($label, $value) => $value === DocumentCatalog::KIND_WORK_ORDER,
    );
@endphp

<div class="form" data-action="app-party-asset-sync" data-party-select="#party_id" data-asset-select="#asset_id">
    <div class="form-group">
        <label for="party_id" class="form-label">Contacto</label>
        <select name="party_id" id="party_id" class="form-control" required>
            <option value="">Seleccionar contacto</option>
            @foreach ($parties as $party)
                <option value="{{ $party->id }}" @selected($currentPartyId == $party->id)>
                    {{ $party->name }}
                </option>
            @endforeach
        </select>
        @error('party_id')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="order_id" class="form-label">Orden asociada</label>
        <select name="order_id" id="order_id" class="form-control">
            <option value="">Sin orden asociada</option>
            @foreach ($orders as $orderOption)
                <option value="{{ $orderOption->id }}" @selected($currentOrderId == $orderOption->id)>
                    {{ $orderOption->number ?: 'Orden #' . $orderOption->id }}
                    @if ($orderOption->party)
                        — {{ $orderOption->party->name }}
                    @endif
                </option>
            @endforeach
        </select>
        <div class="form-help">
            Si seleccionas una orden, el contacto y el activo deben corresponder a esa orden.
        </div>
        @error('order_id')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="asset_id" class="form-label">Activo</label>
        <select name="asset_id" id="asset_id" class="form-control">
            <option value="">Sin activo asociado</option>
            @foreach ($assets as $asset)
                <option value="{{ $asset->id }}" data-party-id="{{ $asset->party_id }}" @selected($currentAssetId == $asset->id)>
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
        <div class="form-help">
            Si seleccionas un activo, debe corresponder al contacto elegido. Si el documento está asociado a una orden,
            se usará el activo de esa orden.
        </div>
        @error('asset_id')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="kind" class="form-label">Tipo</label>

        @if ($documentIsNumbered)
            <select id="kind" class="form-control" disabled>
                @foreach (DocumentCatalog::kindLabels() as $value => $label)
                    <option value="{{ $value }}" @selected(old('kind', $document->kind) === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>

            <input type="hidden" name="kind" value="{{ old('kind', $document->kind) }}">

            <div class="form-help">El tipo no puede cambiarse una vez numerado el documento.</div>
        @else
            <select name="kind" id="kind" class="form-control" required>
                @foreach ($visibleKinds as $value => $label)
                    <option value="{{ $value }}" @selected(old('kind', $document->kind ?? DocumentCatalog::KIND_QUOTE) === $value)>
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

        @if ($documentExists)
            <input type="text" class="form-control" value="{{ $document->number ?: 'Se asignará al guardar' }}"
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
            @foreach (DocumentCatalog::statusLabels() as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $document->status ?? DocumentCatalog::STATUS_DRAFT) === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('status')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="issued_at" class="form-label">Fecha de emisión</label>
        <input type="date" name="issued_at" id="issued_at" class="form-control"
            value="{{ old('issued_at', isset($document) && $document->issued_at ? $document->issued_at->format('Y-m-d') : now()->format('Y-m-d')) }}"
            required>

        <div class="form-help">
            Para facturas no se permiten fechas futuras. Si el documento está asociado a una orden, la fecha no puede
            ser anterior a la de esa orden.
        </div>

        @error('issued_at')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="notes" class="form-label">Notas</label>
        <textarea name="notes" id="notes" class="form-control" rows="4">{{ old('notes', $document->notes ?? '') }}</textarea>
        @error('notes')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>
</div>
