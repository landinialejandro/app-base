{{-- FILE: resources/views/tenants/partials/permissions/capability-row.blade.php | V6 --}}

@php
    $enabled = (bool) ($meta['enabled'] ?? false);
    $scope = $meta['scope'] ?? null;
    $executionMode = $meta['execution_mode'] ?? 'manual';

    $scopeOptions = \App\Support\Catalogs\PermissionScopeCatalog::optionsForCapability($capability);
    $showScope = !empty($scopeOptions);

    $selectedScopeHelp = match ($scope) {
        \App\Support\Catalogs\PermissionScopeCatalog::TENANT_ALL
            => 'Puede trabajar con todos los registros de este módulo dentro de la empresa.',
        \App\Support\Catalogs\PermissionScopeCatalog::ALL
            => 'Tiene acceso total sin una restricción adicional de alcance.',
        \App\Support\Catalogs\PermissionScopeCatalog::OWN_ASSIGNED
            => 'Solo puede trabajar con registros que estén bajo su responsabilidad.',
        \App\Support\Catalogs\PermissionScopeCatalog::LIMITED
            => 'Tiene un acceso parcial según la lógica interna del módulo.',
        default => 'Selecciona el alcance que tendrá este rol dentro del módulo.',
    };

    $executionModeLabel = match ($executionMode) {
        'manual' => 'Manual',
        default => ucfirst((string) $executionMode),
    };
@endphp

<tr>
    <td>
        <div>{{ $capabilityLabel }}</div>
    </td>

    <td>
        <label class="inline-form">
            <input type="checkbox" name="permissions[{{ $module }}][{{ $capability }}][enabled]" value="1"
                {{ $enabled ? 'checked' : '' }}>
            <span>Permitido</span>
        </label>
    </td>

    <td>
        @if ($showScope)
            <select name="permissions[{{ $module }}][{{ $capability }}][scope]" class="form-control"
                data-permission-scope-select>
                <option value="">Sin alcance especial</option>

                @foreach ($scopeOptions as $scopeValue => $scopeLabel)
                    <option value="{{ $scopeValue }}" @selected($scope === $scopeValue)>
                        {{ $scopeLabel }}
                    </option>
                @endforeach
            </select>

            @if ($enabled)
                <div class="form-help" data-permission-scope-help
                    data-scope-help-default="Selecciona el alcance que tendrá este rol dentro del módulo."
                    data-scope-help-all="Tiene acceso total sin una restricción adicional de alcance."
                    data-scope-help-tenant_all="Puede trabajar con todos los registros de este módulo dentro de la empresa."
                    data-scope-help-own_assigned="Solo puede trabajar con registros que estén bajo su responsabilidad."
                    data-scope-help-limited="Tiene un acceso parcial según la lógica interna del módulo.">
                    {{ $selectedScopeHelp }}
                </div>
            @endif
        @else
            <span class="helper-inline">No aplica</span>
            <input type="hidden" name="permissions[{{ $module }}][{{ $capability }}][scope]" value="">
        @endif
    </td>

    <td>
        <span class="helper-inline">{{ $executionModeLabel }}</span>
        <input type="hidden" name="permissions[{{ $module }}][{{ $capability }}][execution_mode]"
            value="{{ $executionMode }}">
    </td>
</tr>
