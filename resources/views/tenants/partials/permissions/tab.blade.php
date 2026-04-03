{{-- FILE: resources/views/tenants/partials/permissions/tab.blade.php | V4 --}}

<section class="tab-panel {{ $activeTab === 'permissions' ? 'is-active' : '' }}" data-tab-panel="permissions"
    {{ $activeTab === 'permissions' ? '' : 'hidden' }}>
    <div class="tab-panel-stack">

        @include('tenants.partials.permissions.role-toolbar', [
            'selectedPermissionRole' => $selectedPermissionRole,
            'permissionRoles' => $permissionRoles,
        ])

        <x-card>
            <div class="dashboard-section-header">
                <h2 class="dashboard-section-title">Accesos por función</h2>
                <p class="dashboard-section-text">
                    Define qué puede hacer cada persona dentro de tu empresa.
                    La información sensible y la configuración importante quedan protegidas.
                </p>
            </div>

            {{-- CONTEXTO DEL TIPO DE ACCESO --}}
            <div class="access-context">

                <div class="access-context-header">
                    <span class="access-dot access-dot--{{ $selectedPermissionRole }}"></span>

                    <div>
                        <div class="access-title">
                            {{ $permissionRoles[$selectedPermissionRole] ?? $selectedPermissionRole }}
                        </div>

                        <div class="access-description">
                            @include('tenants.partials.permissions.role-description', [
                                'role' => $selectedPermissionRole,
                            ])
                        </div>
                    </div>
                </div>

                <div class="access-security-note">
                    🔒 Las configuraciones importantes y los datos sensibles están protegidos.
                </div>

            </div>

            <div class="form-help" style="margin-bottom: 1rem;">
                Si tienes dudas, conviene dar un acceso más cuidado y ampliarlo después.
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
                            'collapsed' => !$loop->first,
                        ])
                    @endforeach
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Guardar cambios
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
