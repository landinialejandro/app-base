@php
    use App\Support\Catalogs\AppointmentCatalog;

    $defaultAssignedUserId = $defaultAssignedUserId ?? old('assigned_user_id', auth()->id());
    $isForeignAppointmentForAdmin = $isForeignAppointmentForAdmin ?? false;
    $currentKind = old('kind', $appointment->kind ?? AppointmentCatalog::KIND_SERVICE);
    $currentReferenceLabel = AppointmentCatalog::referenceLabelForKind($currentKind);
@endphp

<div data-action="app-appointment-party-asset-sync app-appointment-kind-sync">

    <div class="form-group">
        <label for="party_id" class="form-label">{{ AppointmentCatalog::contactLabel() }}</label>
        <select name="party_id" id="party_id" class="form-control">
            <option value="">Sin {{ strtolower(AppointmentCatalog::contactLabel()) }}</option>
            @foreach ($parties as $party)
                <option value="{{ $party->id }}" @selected((string) old('party_id', $appointment->party_id ?? '') === (string) $party->id)>
                    {{ $party->name }}
                </option>
            @endforeach
        </select>
        @error('party_id')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="asset_id" class="form-label">{{ AppointmentCatalog::assetLabel() }}</label>
        <select name="asset_id" id="asset_id" class="form-control">
            <option value="">Sin {{ strtolower(AppointmentCatalog::assetLabel()) }}</option>
            @foreach ($assets as $asset)
                <option value="{{ $asset->id }}" data-party-id="{{ $asset->party_id }}" @selected((string) old('asset_id', $appointment->asset_id ?? '') === (string) $asset->id)>
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
        <div class="form-help">Si eliges un {{ strtolower(AppointmentCatalog::contactLabel()) }}, se filtrarán los
            {{ strtolower(AppointmentCatalog::assetLabel()) }}s vinculados.</div>
        @error('asset_id')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="scheduled_date" class="form-label">Cuándo</label>
        <input type="date" name="scheduled_date" id="scheduled_date" class="form-control"
            value="{{ old('scheduled_date', isset($appointment->scheduled_date) ? $appointment->scheduled_date->format('Y-m-d') : now()->format('Y-m-d')) }}"
            required>
        @error('scheduled_date')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="starts_at" class="form-label">Hora de inicio</label>
        <input type="datetime-local" name="starts_at" id="starts_at" class="form-control"
            value="{{ old('starts_at', isset($appointment->starts_at) ? $appointment->starts_at->format('Y-m-d\TH:i') : '') }}">
        <div class="form-help">Opcional. Si cargas inicio y el fin está vacío, se completará automáticamente con +2
            horas.</div>
        @error('starts_at')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="ends_at" class="form-label">Hora de fin</label>
        <input type="datetime-local" name="ends_at" id="ends_at" class="form-control"
            value="{{ old('ends_at', isset($appointment->ends_at) ? $appointment->ends_at->format('Y-m-d\TH:i') : '') }}">
        @error('ends_at')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="assigned_user_id" class="form-label">{{ AppointmentCatalog::assignedUserLabel() }}</label>
        <select name="assigned_user_id" id="assigned_user_id" class="form-control" required>
            <option value="">Seleccionar</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected((string) old('assigned_user_id', $appointment->assigned_user_id ?? $defaultAssignedUserId) === (string) $user->id)>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>
        @error('assigned_user_id')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="order_id" class="form-label">{{ AppointmentCatalog::orderLabel() }}</label>
        <select name="order_id" id="order_id" class="form-control">
            <option value="">Sin {{ strtolower(AppointmentCatalog::orderLabel()) }}</option>
            @foreach ($orders as $order)
                <option value="{{ $order->id }}" @selected((string) old('order_id', $appointment->order_id ?? '') === (string) $order->id)>
                    {{ $order->number ?: 'Orden #' . $order->id }}
                    @if ($order->party)
                        — {{ $order->party->name }}
                    @endif
                </option>
            @endforeach
        </select>
        @error('order_id')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="status" class="form-label">Estado operativo</label>
        <select name="status" id="status" class="form-control" required>
            @foreach (AppointmentCatalog::statusLabels() as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $appointment->status ?? AppointmentCatalog::STATUS_SCHEDULED) === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('status')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="kind" class="form-label">Tipo</label>
        <select name="kind" id="kind" class="form-control" required>
            @foreach (AppointmentCatalog::kindLabels() as $value => $label)
                <option value="{{ $value }}" @selected($currentKind === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('kind')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="work_mode" class="form-label">{{ AppointmentCatalog::workPlaceLabel() }}</label>
        <select name="work_mode" id="work_mode" class="form-control">
            @foreach (AppointmentCatalog::workModeLabels() as $value => $label)
                <option value="{{ $value }}" @selected(old('work_mode', $appointment->work_mode ?? (AppointmentCatalog::suggestedWorkModeForKind($currentKind) ?? AppointmentCatalog::WORK_MODE_IN_SHOP)) === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('work_mode')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="workstation_name" id="workstation_name_label"
            class="form-label">{{ $currentReferenceLabel }}</label>
        <input type="text" name="workstation_name" id="workstation_name" class="form-control"
            value="{{ old('workstation_name', $appointment->workstation_name ?? '') }}"
            placeholder="Completa la referencia del lugar">
        @error('workstation_name')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="title" class="form-label">Título</label>
        <input type="text" name="title" id="title" class="form-control"
            value="{{ old('title', $appointment->title ?? '') }}" placeholder="Opcional">
        @error('title')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label class="form-check">
            <input type="checkbox" name="is_all_day" value="1" @checked((bool) old('is_all_day', $appointment->is_all_day ?? false))>
            <span>Ocupa el día completo</span>
        </label>
        @error('is_all_day')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group">
        <label for="notes" class="form-label">Notas</label>
        <textarea name="notes" id="notes" class="form-control" rows="4">{{ old('notes', $appointment->notes ?? '') }}</textarea>
        @error('notes')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    @if ($isForeignAppointmentForAdmin)
        <div class="form-group">
            <label class="form-check">
                <input type="checkbox" name="confirm_foreign_appointment_edit" value="1"
                    @checked(old('confirm_foreign_appointment_edit') === '1')>
                <span>Confirmo que estoy modificando un turno asignado a otro colaborador.</span>
            </label>
            @error('confirm_foreign_appointment_edit')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>
    @endif

</div>
