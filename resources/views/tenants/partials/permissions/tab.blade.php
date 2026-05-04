{{-- FILE: resources/views/tenants/partials/permissions/tab.blade.php | V5 --}}

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

            @if (!($canEditSelectedPermissionRole ?? false))
                <div class="form-help" style="margin-bottom: 1rem;">
                    Este rol se muestra en modo lectura. No tenés autorización para modificar sus permisos.
                </div>
            @else
                <div class="form-help" style="margin-bottom: 1rem;">
                    Si tienes dudas, conviene dar un acceso más cuidado y ampliarlo después.
                </div>
            @endif

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
                            'isReadonly' => !($canEditSelectedPermissionRole ?? false),
                        ])
                    @endforeach
                </div>

                <div class="form-actions">
                    @if ($canEditSelectedPermissionRole ?? false)
                        <x-button-primary type="submit">
                            Guardar cambios
                        </x-button-primary>
                    @endif

                    <x-button-secondary :href="route('tenant.profile.show', [
                        'tab' => 'permissions',
                        'role' => $selectedPermissionRole,
                    ])">
                        {{ ($canEditSelectedPermissionRole ?? false) ? 'Cancelar' : 'Volver' }}
                    </x-button-secondary>
                </div>
            </form>
        </x-card>
    </div>
</section>