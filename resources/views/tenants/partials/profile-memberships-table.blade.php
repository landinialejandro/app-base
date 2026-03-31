{{-- FILE: resources/views/tenants/partials/profile-memberships-table.blade.php | V4 --}}

<x-card>
    <div class="dashboard-section-header">
        <h2 class="dashboard-section-title">Usuarios del tenant</h2>
        <p class="dashboard-section-text">
            Gestión básica de acceso por empresa. El bloqueo afecta solo a este tenant.
        </p>
    </div>

    @if ($memberships->count())
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Owner</th>
                        <th>Roles</th>
                        <th>Agregar rol</th>
                        <th>Estado</th>
                        <th>Alta</th>
                        <th class="compact-actions-cell"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($memberships as $membership)
                        @php
                            $assignedRoleIds = $membership->roles->pluck('id')->all();
                            $assignedRoleSlugs = $membership->roles->pluck('slug')->filter()->values();
                            $hasAdminRole = $assignedRoleSlugs->contains(\App\Support\Catalogs\RoleCatalog::ADMIN);

                            $assignableRoles = collect();

                            if (!$membership->is_owner) {
                                $assignableRoles = $availableRoles->filter(function ($role) use (
                                    $assignedRoleIds,
                                    $assignedRoleSlugs,
                                ) {
                                    if (in_array($role->id, $assignedRoleIds, true)) {
                                        return false;
                                    }

                                    if (
                                        $assignedRoleSlugs->contains(\App\Support\Catalogs\RoleCatalog::ADMIN) &&
                                        $role->slug === \App\Support\Catalogs\RoleCatalog::ADMIN
                                    ) {
                                        return false;
                                    }

                                    return true;
                                });
                            }
                        @endphp

                        <tr>
                            <td>{{ $membership->user?->name ?? '—' }}</td>
                            <td>{{ $membership->user?->email ?? '—' }}</td>

                            <td>
                                @if ($membership->is_owner)
                                    <span class="status-badge status-badge--done">Sí</span>
                                @else
                                    <span class="helper-inline">No</span>
                                @endif
                            </td>

                            <td>
                                @if ($membership->is_owner)
                                    <span class="status-badge status-badge--done">Owner</span>
                                @elseif ($membership->roles->count())
                                    <div class="role-list">
                                        @foreach ($membership->roles as $role)
                                            <div class="role-row">
                                                <span>{{ $role->name }}</span>

                                                @can('detachRole', $membership)
                                                    <form method="POST"
                                                        action="{{ route('tenant.memberships.roles.detach', [$membership, $role]) }}"
                                                        data-action="app-confirm-submit"
                                                        data-confirm-message="¿Quitar este rol al usuario?">
                                                        @csrf
                                                        @method('DELETE')

                                                        <button type="submit" class="btn btn-danger btn-icon"
                                                            title="Eliminar rol" aria-label="Eliminar rol">
                                                            <x-icons.trash />
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="helper-inline">Sin rol operativo</span>
                                @endif
                            </td>

                            <td>
                                @if ($membership->is_owner)
                                    <span class="helper-inline">No editable</span>
                                @elseif ($assignableRoles->count())
                                    @can('attachRole', $membership)
                                        <form method="POST"
                                            action="{{ route('tenant.memberships.roles.attach', $membership) }}"
                                            class="inline-form inline-form-wrap">
                                            @csrf

                                            <select name="role_id" class="form-control form-control--role-select">
                                                @foreach ($assignableRoles as $role)
                                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                                @endforeach
                                            </select>

                                            <button type="submit" class="btn btn-secondary btn-icon" title="Agregar rol"
                                                aria-label="Agregar rol">
                                                <x-icons.plus />
                                            </button>
                                        </form>
                                    @else
                                        <span class="helper-inline">No editable</span>
                                    @endcan

                                    @if ($hasAdminRole)
                                        <div class="form-help">
                                            Admin es exclusivo. Si agregas otro rol, reemplazará al actual.
                                        </div>
                                    @endif
                                @else
                                    <span class="helper-inline">Sin roles disponibles</span>
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
                                @if ($membership->is_owner)
                                    <span class="helper-inline">Owner</span>
                                @elseif ($membership->status === 'blocked')
                                    @can('unblock', $membership)
                                        <form method="POST" action="{{ route('tenant.memberships.unblock', $membership) }}"
                                            data-action="app-confirm-submit"
                                            data-confirm-message="¿Rehabilitar el acceso de este usuario para esta empresa?">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary btn-icon"
                                                title="Desbloquear acceso" aria-label="Desbloquear acceso">
                                                <x-icons.unlock />
                                            </button>
                                        </form>
                                    @endcan
                                @else
                                    @can('block', $membership)
                                        <form method="POST" action="{{ route('tenant.memberships.block', $membership) }}"
                                            data-action="app-confirm-submit"
                                            data-confirm-message="¿Bloquear el acceso de este usuario para esta empresa?">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary btn-icon"
                                                title="Bloquear acceso" aria-label="Bloquear acceso">
                                                <x-icons.lock />
                                            </button>
                                        </form>
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
</x-card>
