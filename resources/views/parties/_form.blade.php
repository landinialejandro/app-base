{{-- FILE: resources/views/parties/_form.blade.php | V7 --}}

@php
    use App\Support\Catalogs\PartyCatalog;

    $party = $party ?? null;
    $allowedKinds = $allowedKinds ?? array_keys(PartyCatalog::kindLabels());
    $canManageEmployeeContacts = $canManageEmployeeContacts ?? false;

    $currentKind = old(
        'kind',
        $party->kind ?? null,
    );

    $currentRoles = old(
        'roles',
        $party ? $party->roles->pluck('role')->all() : [PartyCatalog::ROLE_CUSTOMER],
    );

    $employeeLocked = $party ? $party->hasActiveMembership() : false;
    $showEmployeeRelationship = $canManageEmployeeContacts || $employeeLocked;

    if ($employeeLocked && ! in_array(PartyCatalog::ROLE_EMPLOYEE, $currentRoles, true)) {
        $currentRoles[] = PartyCatalog::ROLE_EMPLOYEE;
    }

    $commonRoleLabels = collect(PartyCatalog::roleLabels())
        ->filter(function ($label, $value) use ($showEmployeeRelationship) {
            if ($value === PartyCatalog::ROLE_EMPLOYEE) {
                return $showEmployeeRelationship;
            }

            return true;
        });
@endphp

<div
    data-action="app-progressive-form"
    data-progressive-kind-name="kind"
    data-progressive-role-name="roles[]"
>
    <div class="form-group" data-progressive-section="kind">
        <span class="form-label">¿Qué tipo de contacto vas a agregar?</span>

        @if ($employeeLocked)
            <label class="form-label" style="display:block; margin-top:6px;">
                <input
                    class="form-checkbox"
                    type="radio"
                    name="kind"
                    value="{{ PartyCatalog::KIND_PERSON }}"
                    checked
                    disabled
                >
                Persona
            </label>

            <input type="hidden" name="kind" value="{{ PartyCatalog::KIND_PERSON }}">

            <div class="form-help">
                Este contacto está vinculado a un usuario activo de la empresa. Por eso debe permanecer como persona.
            </div>
        @else
            @foreach (PartyCatalog::kindLabels() as $value => $label)
                @continue(! in_array($value, $allowedKinds, true))

                <label class="form-label" style="display:block; margin-top:6px;">
                    <input
                        class="form-checkbox"
                        type="radio"
                        name="kind"
                        value="{{ $value }}"
                        @checked($currentKind === $value)
                    >
                    {{ $label }}
                </label>
            @endforeach
        @endif

        @error('kind')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-group" data-progressive-section="relationship" hidden>
        <span class="form-label">¿Qué relación tiene con tu empresa?</span>

        @foreach ($commonRoleLabels as $value => $label)
            @php
                $lockedEmployeeRole = $employeeLocked && $value === PartyCatalog::ROLE_EMPLOYEE;
            @endphp

            <label class="form-label" style="display:block; margin-top:6px;">
                <input
                    class="form-checkbox"
                    type="checkbox"
                    name="roles[]"
                    value="{{ $value }}"
                    @checked(in_array($value, $currentRoles, true))
                    @disabled($lockedEmployeeRole)
                >
                {{ $label }}
            </label>

            @if ($lockedEmployeeRole)
                <input type="hidden" name="roles[]" value="{{ PartyCatalog::ROLE_EMPLOYEE }}">
            @endif
        @endforeach

        @if ($employeeLocked)
            <div class="form-help">
                La relación colaborador se mantiene mientras este contacto siga vinculado a un usuario activo de la empresa.
            </div>
        @elseif ($showEmployeeRelationship)
            <div class="form-help">
                Podés marcar más de una opción. Usá colaborador solo para personas que trabajan con la empresa, aunque no tengan usuario en el sistema.
            </div>
        @else
            <div class="form-help">
                Podés marcar más de una opción. Por ejemplo, un contacto puede ser cliente y proveedor.
            </div>
        @endif

        @error('roles')
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </div>

    <div data-progressive-section="details" hidden>
        <div class="form-group">
            <label for="name" class="form-label">Nombre</label>
            <input type="text" id="name" name="name" class="form-control"
                value="{{ old('name', $party->name ?? '') }}" required>
            @error('name')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="display_name" class="form-label">Nombre visible</label>
            <input type="text" id="display_name" name="display_name" class="form-control"
                value="{{ old('display_name', $party->display_name ?? '') }}">
            @error('display_name')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="document_type" class="form-label">Tipo de documento</label>
            <input type="text" id="document_type" name="document_type" class="form-control"
                value="{{ old('document_type', $party->document_type ?? '') }}">
            @error('document_type')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="document_number" class="form-label">Número de documento</label>
            <input type="text" id="document_number" name="document_number" class="form-control"
                value="{{ old('document_number', $party->document_number ?? '') }}">
            @error('document_number')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="tax_id" class="form-label">CUIT / ID fiscal</label>
            <input type="text" id="tax_id" name="tax_id" class="form-control"
                value="{{ old('tax_id', $party->tax_id ?? '') }}">
            @error('tax_id')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" name="email" class="form-control"
                value="{{ old('email', $party->email ?? '') }}" @readonly($employeeLocked)>

            @if ($employeeLocked)
                <div class="form-help">
                    Este email está vinculado a un usuario activo de la empresa. No se edita desde esta pantalla.
                </div>
            @endif

            @error('email')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="phone" class="form-label">Teléfono</label>
            <input type="text" id="phone" name="phone" class="form-control"
                value="{{ old('phone', $party->phone ?? '') }}">
            @error('phone')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="address" class="form-label">Dirección</label>
            <input type="text" id="address" name="address" class="form-control"
                value="{{ old('address', $party->address ?? '') }}">
            @error('address')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label for="notes" class="form-label">Notas</label>
            <textarea id="notes" name="notes" rows="4" class="form-control">{{ old('notes', $party->notes ?? '') }}</textarea>
            @error('notes')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="is_active">
                <input class="form-checkbox" type="checkbox" id="is_active" name="is_active" value="1"
                    @checked(old('is_active', $party->is_active ?? true))>
                Activo
            </label>
            @error('is_active')
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<x-dev-component-version name="parties._form" version="V7" />