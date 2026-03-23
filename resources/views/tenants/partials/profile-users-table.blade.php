{{-- FILE: resources/views/tenants/partials/profile-users-table.blade.php | V1 --}}

<x-card>
    <div class="dashboard-section-header">
        <h2 class="dashboard-section-title">Usuarios del tenant</h2>
        <p class="dashboard-section-text">
            Listado de personas asociadas a esta empresa.
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
                        <th>Estado</th>
                        <th>Alta</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($memberships as $membership)
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
                                @if ($membership->status === 'blocked')
                                    <span class="status-badge status-badge--cancelled">Bloqueado</span>
                                @else
                                    <span class="status-badge status-badge--done">Activo</span>
                                @endif
                            </td>

                            <td>{{ $membership->joined_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="mb-0">No hay usuarios asociados a esta empresa.</p>
    @endif
</x-card>
