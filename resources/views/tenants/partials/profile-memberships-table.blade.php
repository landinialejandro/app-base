{{-- FILE: resources/views/tenants/partials/profile-memberships-table.blade.php --}}

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
                            $assignableRoles = $availableRoles->filter(
                                fn($role) => !in_array($role->id, $assignedRoleIds, true),
                            );
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
                                @if ($membership->roles->count())
                                    <div style="display:flex; flex-direction:column; gap:0.5rem;">
                                        @foreach ($membership->roles as $role)
                                            <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                                                <span>{{ $role->name }}</span>

                                                <form method="POST"
                                                    action="{{ route('tenant.memberships.roles.detach', [$membership, $role]) }}">
                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="submit" class="btn btn-secondary btn-sm">
                                                        Quitar
                                                    </button>
                                                </form>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="helper-inline">Sin roles</span>
                                @endif
                            </td>

                            <td>
                                @if ($assignableRoles->count())
                                    <form method="POST"
                                        action="{{ route('tenant.memberships.roles.attach', $membership) }}"
                                        class="inline-form"
                                        style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
                                        @csrf

                                        <select name="role_id" class="form-control" style="min-width: 180px;">
                                            @foreach ($assignableRoles as $role)
                                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                                            @endforeach
                                        </select>

                                        <button type="submit" class="btn btn-secondary">
                                            Agregar
                                        </button>
                                    </form>
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
                                    <form method="POST"
                                        action="{{ route('tenant.memberships.unblock', $membership) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary">
                                            Rehabilitar
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('tenant.memberships.block', $membership) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary">
                                            Bloquear
                                        </button>
                                    </form>
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
