{{-- FILE: resources/views/tenants/partials/profile-memberships-table.blade.php | V10 --}}

@php
    use App\Support\Catalogs\RoleCatalog;
    use App\Support\Tenants\TenantProfileAccess;

    $tenantProfileAccess = app(TenantProfileAccess::class);
@endphp

<x-card>
    <div class="dashboard-section-header">
        <h2 class="dashboard-section-title">Usuarios del tenant</h2>
        <p class="dashboard-section-text">
            Gestioná las funciones de cada usuario dentro de la empresa. Las opciones disponibles dependen de tu nivel
            de autorización.
        </p>
    </div>

    @if ($memberships->count())
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Funciones</th>
                        <th>Estado</th>
                        <th>Alta</th>
                        <th class="compact-actions-cell"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($memberships as $membership)
                        @php
                            $membership->loadMissing('roles');

                            $assignedRoleIds = $membership->roles
                                ->pluck('id')
                                ->map(fn($roleId) => (int) $roleId)
                                ->values();

                            $assignedRoleSlugs = $membership->roles->pluck('slug')->filter()->values();

                            $hasAdminRole = $assignedRoleSlugs->contains(RoleCatalog::ADMIN);

                            $roleOptions = $availableRoles->filter(fn($role) => RoleCatalog::isAssignable($role->slug));

                            $canShowRoleForm = !$membership->is_owner;
                            $canChangeAnyRole = false;

                            $roleRows = $roleOptions->map(function ($role) use (
                                $assignedRoleIds,
                                $actorMembership,
                                $membership,
                                $tenantProfileAccess,
                                &$canChangeAnyRole,
                            ) {
                                $isAssigned = $assignedRoleIds->contains((int) $role->id);

                                $canChangeThisRole = $isAssigned
                                    ? $tenantProfileAccess->canDetachRole($actorMembership, $membership, $role)
                                    : $tenantProfileAccess->canAssignRole($actorMembership, $membership, $role->slug);

                                if ($canChangeThisRole) {
                                    $canChangeAnyRole = true;
                                }

                                return [
                                    'role' => $role,
                                    'isAssigned' => $isAssigned,
                                    'canChange' => $canChangeThisRole,
                                    'isDisabled' => !$canChangeThisRole,
                                ];
                            });

                            $roleModalId = 'membership-roles-modal-' . $membership->id;
                            $roleFormId = 'membership-roles-form-' . $membership->id;
                        @endphp

                        <tr>
                            <td>
                                <div>{{ $membership->user?->name ?? '—' }}</div>

                                @if ($membership->is_owner)
                                    <div style="margin-top: 0.35rem;">
                                        <span class="status-badge status-badge--done">Propietario</span>
                                    </div>
                                @elseif ($hasAdminRole)
                                    <div style="margin-top: 0.35rem;">
                                        <span class="status-badge status-badge--done">Administrador</span>
                                    </div>
                                @endif
                            </td>

                            <td>{{ $membership->user?->email ?? '—' }}</td>

                            <td>
                                @if ($membership->is_owner)
                                    <span class="status-badge status-badge--done">Propietario</span>
                                @elseif ($membership->roles->count())
                                    <div class="role-list">
                                        @foreach ($membership->roles as $role)
                                            <span class="status-badge status-badge--done">
                                                {{ $role->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="helper-inline">Sin función operativa</span>
                                @endif
                            </td>

                            <td>
                                @if ($membership->status === 'blocked')
                                    <span class="status-badge status-badge--cancelled">Bloqueado</span>
                                @else
                                    <span class="status-badge status-badge--done">Activo</span>
                                @endif
                            </td>

                            <td>{{ $membership->joined_at?->format('d/m/Y H:i') ?? '—' }}</td>

                            <td class="compact-actions-cell">
                                <div class="compact-actions">
                                    <form method="POST" action="{{ route('tenant.memberships.party', $membership) }}">
                                        @csrf

                                        <button type="submit" class="btn btn-secondary btn-icon" title="Más datos"
                                            aria-label="Más datos">
                                            <x-icons.user-group />
                                        </button>
                                    </form>

                                    @if ($canShowRoleForm)
                                        @can('attachRole', $membership)
                                            @if ($canChangeAnyRole)
                                                <button type="button" class="btn btn-secondary btn-icon"
                                                    title="Editar funciones" aria-label="Editar funciones"
                                                    data-action="app-modal-open" data-modal-target="#{{ $roleModalId }}">
                                                    <x-icons.list-check />
                                                </button>
                                            @endif
                                        @endcan
                                    @endif

                                    @if ($membership->is_owner)
                                        <span class="helper-inline">Owner</span>
                                    @elseif ($membership->status === 'blocked')
                                        @can('unblock', $membership)
                                            @if ($tenantProfileAccess->canManageMembershipStatus($actorMembership, $membership))
                                                <form method="POST"
                                                    action="{{ route('tenant.memberships.unblock', $membership) }}"
                                                    data-action="app-confirm-submit"
                                                    data-confirm-message="¿Rehabilitar el acceso de este usuario para esta empresa?">
                                                    @csrf

                                                    <button type="submit" class="btn btn-secondary btn-icon"
                                                        title="Desbloquear acceso" aria-label="Desbloquear acceso">
                                                        <x-icons.unlock />
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    @else
                                        @can('block', $membership)
                                            @if ($tenantProfileAccess->canManageMembershipStatus($actorMembership, $membership))
                                                <form method="POST"
                                                    action="{{ route('tenant.memberships.block', $membership) }}"
                                                    data-action="app-confirm-submit"
                                                    data-confirm-message="¿Bloquear el acceso de este usuario para esta empresa?">
                                                    @csrf

                                                    <button type="submit" class="btn btn-secondary btn-icon"
                                                        title="Bloquear acceso" aria-label="Bloquear acceso">
                                                        <x-icons.lock />
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                    @endif
                                </div>

                                @if ($canShowRoleForm)
                                    @can('attachRole', $membership)
                                        <x-modal :id="$roleModalId" title="Editar funciones" size="md">
                                            <form id="{{ $roleFormId }}" method="POST"
                                                action="{{ route('tenant.memberships.roles.sync', $membership) }}"
                                                class="form" data-action="tenant-role-sync-form">
                                                @csrf
                                                @method('PUT')

                                                <div class="dashboard-section-header">
                                                    <h2 class="dashboard-section-title">
                                                        {{ $membership->user?->name ?? 'Usuario' }}
                                                    </h2>
                                                    <p class="dashboard-section-text">
                                                        Seleccioná las funciones que tendrá este usuario dentro de la
                                                        empresa.
                                                    </p>
                                                </div>

                                                <div class="form-section">
                                                    <h2 class="section-title">Funciones disponibles</h2>

                                                    <div class="tab-panel-stack">
                                                        @foreach ($roleRows as $roleRow)
                                                            @php
                                                                $role = $roleRow['role'];
                                                                $isAssigned = $roleRow['isAssigned'];
                                                                $isDisabled = $roleRow['isDisabled'];
                                                                $isAdminOption = $role->slug === RoleCatalog::ADMIN;
                                                            @endphp

                                                            <label class="inline-form" style="align-items: center;"
                                                                data-role-option data-role-slug="{{ $role->slug }}"
                                                                data-role-admin="{{ $isAdminOption ? '1' : '0' }}">
                                                                <input type="checkbox" name="roles[]"
                                                                    value="{{ $role->id }}" data-role-checkbox
                                                                    data-role-slug="{{ $role->slug }}"
                                                                    data-role-admin="{{ $isAdminOption ? '1' : '0' }}"
                                                                    @checked($isAssigned)
                                                                    @disabled($isDisabled)>

                                                                <span>{{ $role->name }}</span>
                                                            </label>

                                                            @if ($isAssigned && $isDisabled)
                                                                <input type="hidden" name="roles[]"
                                                                    value="{{ $role->id }}">
                                                            @endif
                                                        @endforeach
                                                    </div>

                                                    <div class="form-help tenant-role-sync-message" data-role-sync-message>
                                                        @if ($hasAdminRole)
                                                            Administrador es una función exclusiva. Solo owner puede
                                                            gestionarla.
                                                        @else
                                                            Podés combinar funciones operativas compatibles. Administrador
                                                            es una función exclusiva.
                                                        @endif
                                                    </div>
                                                </div>
                                            </form>

                                            <x-slot:footer>
                                                <x-button-secondary type="button" data-action="app-modal-close"
                                                    data-modal-target="#{{ $roleModalId }}">
                                                    Cancelar
                                                </x-button-secondary>

                                                @if ($canChangeAnyRole)
                                                    <x-button-primary type="submit" form="{{ $roleFormId }}">
                                                        Guardar funciones
                                                    </x-button-primary>
                                                @endif
                                            </x-slot:footer>
                                        </x-modal>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="mb-0">No hay usuarios asociados a esta empresa.</p>
    @endif

    <x-dev-component-version name="tenants.partials.profile-memberships-table" version="V10" />
</x-card>
