{{-- FILE: resources/views/parties/components/order-form-party.blade.php | V2 --}}

@props([
    'partyOptions' => collect(),
    'currentPartyId' => '',
])

<div class="form-group">
    <label for="party_id" class="form-label">Contacto vinculado</label>
    <select name="party_id" id="party_id" class="form-control">
        <option value="">Sin contacto vinculado</option>
        @foreach ($partyOptions as $partyOption)
            <option value="{{ $partyOption['id'] }}" @selected((string) $currentPartyId === (string) $partyOption['id'])>
                {{ $partyOption['label'] }}
                @if (!empty($partyOption['meta']))
                    · {{ $partyOption['meta'] }}
                @endif
            </option>
        @endforeach
    </select>
    <div class="form-help">
        Opcional. Si se selecciona un contacto, la orden conservará su referencia como dato propio.
    </div>
    @error('party_id')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>