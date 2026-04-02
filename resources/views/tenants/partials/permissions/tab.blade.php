{{-- FILE: resources/views/tenants/partials/permissions/tab.blade.php | V3 --}}

<section class="tab-panel {{ $activeTab === 'permissions' ? 'is-active' : '' }}" data-tab-panel="permissions"
    {{ $activeTab === 'permissions' ? '' : 'hidden' }}>
    <div class="tab-panel-stack">
        @include('tenants.partials.permissions.role-toolbar', [
            'selectedPermissionRole' => $selectedPermissionRole,
            'permissionRoles' => $permissionRoles,
        ])

        <x-card>
            <div class="dashboard-section-header">
                <h2 class="dashboard-section-title">Permisos por rol</h2>
                <p class="dashboard-section-text">
                    Aquí defines qué puede hacer cada rol dentro de esta empresa.
                    Los cambios afectan a todos los usuarios que tengan ese rol.
                </p>
            </div>

            <div class="form-help" style="margin-bottom: 1rem;">
                Revisa con cuidado los permisos de edición y eliminación. Si tienes dudas, conviene dar menos acceso y
                ampliarlo después.
            </div>

            <form method="POST" action="{{ route('tenant.profile.permissions.update') }}" class="form">
                @csrf
                @method('PUT')

                <input type="hidden" name="role" value="{{ $selectedPermissionRole }}">

                <div class="tab-panel-stack">
                    @foreach ($permissionMatrix as $module => $capabilities)
                        @include('tenants.partials.permissions.module-card', [
                            'module' => $module,
                            'moduleLabel' => $moduleLabels[$module] ?? $module,
                            'capabilities' => $capabilities,
                            'capabilityLabels' => $capabilityLabels,
                        ])
                    @endforeach
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Guardar permisos
                    </button>

                    <a href="{{ route('tenant.profile.show', ['tab' => 'permissions', 'role' => $selectedPermissionRole]) }}"
                        class="btn btn-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </x-card>
    </div>
</section>
