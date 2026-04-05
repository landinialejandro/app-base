{{-- FILE: resources/views/tenants/partials/permissions/capability-row.blade.php | V9 --}}

@php
    $enabled = (bool) ($meta['enabled'] ?? false);
    $scope = $meta['scope'] ?? null;
    $executionMode = $meta['execution_mode'] ?? 'manual';

    $scopeOptions = $scopeOptionsByModuleCapability[$module][$capability] ?? [];
    $showScope = !empty($scopeOptions);

    $selectedScopeHelp = match ($scope) {
        \App\Support\Catalogs\PermissionScopeCatalog::TENANT_ALL
            => 'Puede trabajar con toda la información de este módulo dentro de la empresa.',
        \App\Support\Catalogs\PermissionScopeCatalog::OWN_ASSIGNED
            => 'Solo puede trabajar con los registros que tenga asignados.',
        \App\Support\Catalogs\PermissionScopeCatalog::LIMITED
            => 'Tiene acceso parcial según la lógica específica de este módulo.',
        default => $showScope
            ? 'Define sobre qué información podrá usar esta acción.'
            : 'Esta acción no requiere un alcance adicional.',
    };

    $executionModeLabel = match ($executionMode) {
        'manual' => 'Automático según el sistema',
        default => ucfirst((string) $executionMode),
    };

    $isSensitive = in_array($capability, ['delete'], true);
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
    </td>

    <td>
        @if ($showScope)
            <select name="permissions[{{ $module }}][{{ $capability }}][scope]" class="form-control"
                data-permission-scope-select>
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
        @else
            <span class="helper-inline">Sin alcance adicional</span>

            @if ($enabled)
                <div class="form-help">
                    Esta acción no requiere restricciones extra.
                </div>
            @endif

            <input type="hidden" name="permissions[{{ $module }}][{{ $capability }}][scope]" value="">
        @endif
    </td>

    <td>
        <span class="helper-inline">{{ $executionModeLabel }}</span>
        <input type="hidden" name="permissions[{{ $module }}][{{ $capability }}][execution_mode]"
            value="{{ $executionMode }}">
    </td>
</tr>
