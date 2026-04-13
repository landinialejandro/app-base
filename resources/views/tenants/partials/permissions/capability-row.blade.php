{{-- FILE: resources/views/tenants/partials/permissions/capability-row.blade.php | V12 --}}

@php
    use App\Support\Catalogs\ModuleCatalog;
    use App\Support\Catalogs\PermissionScopeCatalog;

    $oldEnabled = old("permissions.$module.$capability.enabled");
    $enabled = $oldEnabled !== null ? (bool) $oldEnabled : (bool) ($meta['enabled'] ?? false);

    $scope = old("permissions.$module.$capability.scope", $meta['scope'] ?? null);
    $executionMode = old("permissions.$module.$capability.execution_mode", $meta['execution_mode'] ?? 'manual');

    $constraints = $meta['constraints'] ?? [];
    $oldAllowedKinds = old("permissions.$module.$capability.constraints.allowed_kinds");
    $selectedAllowedKinds = is_array($oldAllowedKinds)
        ? array_values(array_unique(array_filter($oldAllowedKinds)))
        : array_values(array_unique(array_filter($constraints['allowed_kinds'] ?? [])));

    $scopeOptions = $scopeOptionsByModuleCapability[$module][$capability] ?? [];
    $showScope = !empty($scopeOptions);

    $constraintOptions = $constraintOptionsByModuleCapability[$module][$capability] ?? [];
    $allowedKindOptions = $constraintOptions['allowed_kinds'] ?? [];
    $showAllowedKinds = !empty($allowedKindOptions);

    $selectedScopeHelp = match ($scope) {
        PermissionScopeCatalog::TENANT_ALL
            => 'Puede trabajar con toda la información de este módulo dentro de la empresa.',
        PermissionScopeCatalog::OWN_ASSIGNED => 'Solo puede trabajar con los registros que tenga asignados.',
        PermissionScopeCatalog::LIMITED => 'Tiene acceso parcial según la lógica específica de este módulo.',
        default => $showScope
            ? 'Define sobre qué información podrá usar esta acción.'
            : 'Esta acción no requiere un alcance adicional.',
    };

    $executionModeLabel = match ($executionMode) {
        'manual' => 'Automático según el sistema',
        default => ucfirst((string) $executionMode),
    };

    $isSensitive = in_array($capability, ['delete'], true);

    $allowedKindsTitle = match ($module) {
        ModuleCatalog::ORDERS => 'Tipos de orden permitidos para esta acción',
        ModuleCatalog::PARTIES => 'Tipos de contacto permitidos para esta acción',
        default => 'Tipos permitidos para esta acción',
    };

    $allowedKindsHelp = match ($module) {
        ModuleCatalog::ORDERS => 'La acción solo se permitirá sobre órdenes de los tipos seleccionados.',
        ModuleCatalog::PARTIES => 'La acción solo se permitirá sobre contactos de los tipos seleccionados.',
        default => 'La acción solo se permitirá sobre los tipos seleccionados.',
    };

    $showExecutionMode = $enabled;
@endphp

<tr>
    <td>
        <div>
            {{ $capabilityLabel }}

            @if ($isSensitive)
                <div class="form-help" style="color: #b91c1c;">
                    Acción sensible
                </div>
            @endif
        </div>
    </td>

    <td>
        <label class="inline-form">
            <input type="checkbox" name="permissions[{{ $module }}][{{ $capability }}][enabled]" value="1"
                {{ $enabled ? 'checked' : '' }}>
            <span>Permitir</span>
        </label>

        @error("permissions.$module.$capability.enabled")
            <div class="form-help is-error">{{ $message }}</div>
        @enderror
    </td>

    <td>
        @if ($showScope && count($scopeOptions) > 1)
            <select name="permissions[{{ $module }}][{{ $capability }}][scope]" class="form-control">
                <option value="">Seleccionar alcance</option>

                @foreach ($scopeOptions as $scopeValue => $scopeLabel)
                    <option value="{{ $scopeValue }}" @selected($scope === $scopeValue)>
                        {{ $scopeLabel }}
                    </option>
                @endforeach
            </select>

            <div class="form-help" data-permission-scope-help
                data-scope-help-default="Define sobre qué información podrá usar esta acción."
                data-scope-help-tenant_all="Puede trabajar con toda la información de este módulo dentro de la empresa."
                data-scope-help-own_assigned="Solo puede trabajar con los registros que tenga asignados."
                data-scope-help-limited="Tiene acceso parcial según la lógica específica de este módulo.">
                {{ $selectedScopeHelp }}
            </div>

            @error("permissions.$module.$capability.scope")
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        @elseif ($showScope && count($scopeOptions) === 1)
            @php
                $onlyScope = array_key_first($scopeOptions);
            @endphp

            <span class="helper-inline">
                {{ $scopeOptions[$onlyScope] }}
            </span>

            <input type="hidden" name="permissions[{{ $module }}][{{ $capability }}][scope]"
                value="{{ $onlyScope }}">

            @error("permissions.$module.$capability.scope")
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        @endif

        @if ($showAllowedKinds)
            <div style="margin-top: 0.75rem;">
                <div class="form-help" style="margin-bottom: 0.5rem;">
                    {{ $allowedKindsTitle }}
                </div>

                <div class="inline-form inline-form-wrap">
                    @foreach ($allowedKindOptions as $kindValue => $kindLabel)
                        <label class="inline-form">
                            <input type="checkbox"
                                name="permissions[{{ $module }}][{{ $capability }}][constraints][allowed_kinds][]"
                                value="{{ $kindValue }}" @checked(in_array($kindValue, $selectedAllowedKinds, true))>
                            <span>{{ $kindLabel }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="form-help">
                    {{ $allowedKindsHelp }}
                </div>

                @error("permissions.$module.$capability.constraints.allowed_kinds")
                    <div class="form-help is-error">{{ $message }}</div>
                @enderror
            </div>
        @endif
    </td>

    <td>
        @if ($showExecutionMode)
            <span class="helper-inline">{{ $executionModeLabel }}</span>
        @else
            <span class="helper-inline">No aplica</span>
        @endif

        <input type="hidden" name="permissions[{{ $module }}][{{ $capability }}][execution_mode]"
            value="{{ $executionMode }}">
    </td>
</tr>
