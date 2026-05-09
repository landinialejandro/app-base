{{-- FILE: resources/views/assets/components/order-form-asset.blade.php | V3 --}}

@props([
    'assetOptions' => collect(),
    'currentAssetId' => '',
])

<div class="form-group">
    <label for="asset_id" class="form-label">Activo vinculado</label>
    <select name="asset_id" id="asset_id" class="form-control">
        <option value="">Sin activo vinculado</option>
        @foreach ($assetOptions as $assetOption)
            <option value="{{ $assetOption['id'] }}"
                data-source-value="{{ $assetOption['party_id'] ?? '' }}"
                @selected((string) $currentAssetId === (string) $assetOption['id'])>
                {{ $assetOption['label'] }}
                @if (!empty($assetOption['meta']))
                    · {{ $assetOption['meta'] }}
                @endif
            </option>
        @endforeach
    </select>
    <div class="form-help">
        Si se selecciona un contacto, se muestran solo los activos vinculados a ese contacto.
    </div>
    @error('asset_id')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>