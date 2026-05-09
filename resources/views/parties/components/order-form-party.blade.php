{{-- FILE: resources/views/parties/components/order-form-party.blade.php | V3 --}}

@props([
    'partyOptions' => collect(),
    'currentPartyId' => '',
])

<div class="form-group">
    <label for="party_id" class="form-label">Contacto vinculado</label>
    <select name="party_id" id="party_id" class="form-control" required>
        <option value="">Seleccionar contacto</option>
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
        Si se selecciona un contacto, la contraparte se completa automáticamente desde el sistema.
    </div>
    @error('party_id')
        <div class="form-help is-error">{{ $message }}</div>
    @enderror
</div>