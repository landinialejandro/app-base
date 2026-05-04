{{-- FILE: resources/views/tenants/partials/permissions/capability-row.blade.php | V14 --}}

@php
    use App\Support\Catalogs\ModuleCatalog;
    use App\Support\Catalogs\PermissionScopeCatalog;

    $isReadonly = $isReadonly ?? false;

    $oldEnabled = old("permissions.$module.$capability.enabled");
    $enabled = $oldEnabled !== null ? (bool) $oldEnabled : (bool) ($meta['enabled'] ?? false);

    $scope = old("permissions.$module.$capability.scope", $meta['scope'] ?? null);
    $executionMode = old("permissions.$module.$capability.execution_mode", $meta['execution_mode'] ?? 'manual');

    $constraints = $meta['constraints'] ?? [];

    $oldAllowedKinds = old("permissions.$module.$capability.constraints.allowed_kinds");
    $selectedAllowedKinds = is_array($oldAllowedKinds)
        ? array_values(array_unique(array_filter($oldAllowedKinds)))
        : array_values(array_unique(array_filter($constraints['allowed_kinds'] ?? [])));

    $oldAllowedPartyRoles = old("permissions.$module.$capability.constraints.allowed_party_roles");
    $selectedAllowedPartyRoles = is_array($oldAllowedPartyRoles)
        ? array_values(array_unique(array_filter($oldAllowedPartyRoles)))
        : array_values(array_unique(array_filter($constraints['allowed_party_roles'] ?? [])));

    $scopeOptions = $scopeOptionsByModuleCapability[$module][$capability] ?? [];
    $showScope = !empty($scopeOptions);

    $constraintOptions = $constraintOptionsByModuleCapability[$module][$capability] ?? [];

    $allowedKindOptions = $constraintOptions['allowed_kinds'] ?? [];
    $showAllowedKinds = !empty($allowedKindOptions);

    $allowedPartyRoleOptions = $constraintOptions['allowed_party_roles'] ?? [];
    $showAllowedPartyRoles = !empty($allowedPartyRoleOptions);

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

    $allowedKindsTitle = 'Tipos de orden permitidos para esta acción';
    $allowedKindsHelp = 'La acción solo se permitirá sobre órdenes de los tipos seleccionados.';

    $allowedPartyRolesTitle = 'Relaciones con la empresa permitidas';
    $allowedPartyRolesHelp = 'La acción solo se permitirá sobre contactos con las relaciones seleccionadas.';

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
        @if ($isReadonly)
            <span class="helper-inline">{{ $enabled ? 'Permitido' : 'No permitido' }}</span>
        @else
            <label class="inline-form">
                <input type="checkbox" name="permissions[{{ $module }}][{{ $capability }}][enabled]" value="1"
                    {{ $enabled ? 'checked' : '' }}>
                <span>Permitir</span>
            </label>

            @error("permissions.$module.$capability.enabled")
                <div class="form-help is-error">{{ $message }}</div>
            @enderror
        @endif
    </td>

    <td>
        @if ($showScope && count($scopeOptions) > 1)
            @if ($isReadonly)
                <span class="helper-inline">
                    {{ $scopeOptions[$scope] ?? '—' }}
                </span>
            @else
                <select name="permissions[{{ $module }}][{{ $capability }}][scope]" class="form-control">
                    <option value="">Seleccionar alcance</option>

                    @foreach ($scopeOptions as $scopeValue => $scopeLabel)
                        <option value="{{ $scopeValue }}" @selected($scope === $scopeValue)>
                            {{ $scopeLabel }}
                        </option>
                    @endforeach
                </select>
            @endif

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

            @if (! $isReadonly)
                <input type="hidden" name="permissions[{{ $module }}][{{ $capability }}][scope]"
                    value="{{ $onlyScope }}">
            @endif

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
                        @if ($isReadonly)
                            @if (in_array($kindValue, $selectedAllowedKinds, true))
                                <span class="status-badge status-badge--done">{{ $kindLabel }}</span>
                            @endif
                        @else
                            <label class="inline-form">
                                <input type="checkbox"
                                    name="permissions[{{ $module }}][{{ $capability }}][constraints][allowed_kinds][]"
                                    value="{{ $kindValue }}" @checked(in_array($kindValue, $selectedAllowedKinds, true))>
                                <span>{{ $kindLabel }}</span>
                            </label>
                        @endif
                    @endforeach

                    @if ($isReadonly && empty($selectedAllowedKinds))
                        <span class="helper-inline">Sin tipos restringidos</span>
                    @endif
                </div>

                <div class="form-help">
                    {{ $allowedKindsHelp }}
                </div>

                @error("permissions.$module.$capability.constraints.allowed_kinds")
                    <div class="form-help is-error">{{ $message }}</div>
                @enderror
            </div>
        @endif

        @if ($showAllowedPartyRoles)
            <div style="margin-top: 0.75rem;">
                <div class="form-help" style="margin-bottom: 0.5rem;">
                    {{ $allowedPartyRolesTitle }}
                </div>

                <div class="inline-form inline-form-wrap">
                    @foreach ($allowedPartyRoleOptions as $roleValue => $roleLabel)
                        @if ($isReadonly)
                            @if (in_array($roleValue, $selectedAllowedPartyRoles, true))
                                <span class="status-badge status-badge--done">{{ $roleLabel }}</span>
                            @endif
                        @else
                            <label class="inline-form">
                                <input type="checkbox"
                                    name="permissions[{{ $module }}][{{ $capability }}][constraints][allowed_party_roles][]"
                                    value="{{ $roleValue }}" @checked(in_array($roleValue, $selectedAllowedPartyRoles, true))>
                                <span>{{ $roleLabel }}</span>
                            </label>
                        @endif
                    @endforeach

                    @if ($isReadonly && empty($selectedAllowedPartyRoles))
                        <span class="helper-inline">Sin relaciones restringidas</span>
                    @endif
                </div>

                <div class="form-help">
                    {{ $allowedPartyRolesHelp }}
                </div>

                @error("permissions.$module.$capability.constraints.allowed_party_roles")
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

        @if (! $isReadonly)
            <input type="hidden" name="permissions[{{ $module }}][{{ $capability }}][execution_mode]"
                value="{{ $executionMode }}">
        @endif
    </td>
</tr>