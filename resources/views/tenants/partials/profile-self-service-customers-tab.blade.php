{{-- FILE: resources/views/tenants/partials/profile-self-service-customers-tab.blade.php | V5 --}}

@php
    $selfServiceCustomerRegistrations = $selfServiceCustomerRegistrations ?? collect();
    $selfServiceCustomerStatusFilter = $selfServiceCustomerStatusFilter ?? 'all';
    $selfServiceCustomerStatusOptions = $selfServiceCustomerStatusOptions ?? [];
    $selfServiceCustomerStatusCounts = $selfServiceCustomerStatusCounts ?? [];

    $statusLabels = [
        'pending' => 'Pendiente',
        'confirmed' => 'Confirmado',
        'expired' => 'Vencido',
        'cancelled' => 'Cancelado',
    ];

    $statusBadgeClasses = [
        'pending' => 'status-badge--pending',
        'confirmed' => 'status-badge--done',
        'expired' => 'status-badge--expired',
        'cancelled' => 'status-badge--cancelled',
    ];

    $storeStatusLabels = [
        'active' => 'Activa',
        'blocked' => 'Bloqueada',
        'cancelled' => 'Cancelada',
    ];

    $storeStatusBadgeClasses = [
        'active' => 'status-badge--done',
        'blocked' => 'status-badge--expired',
        'cancelled' => 'status-badge--cancelled',
    ];

    $identityStageLabels = [
        'email_confirmed' => 'Email confirmado',
        'operational_identity_completed' => 'Identidad operativa completa',
    ];
@endphp

<x-tab-toolbar>
    <x-slot:tabs>
        <div class="tabs-nav" role="tablist" aria-label="Estados de clientes tienda">
            @foreach($selfServiceCustomerStatusOptions as $value => $label)
                @php
                    $isActiveStatus = $selfServiceCustomerStatusFilter === $value;

                    $statusUrl = route('tenant.profile.show', [
                        'tab' => 'self_service_customers',
                        'self_service_customer_status' => $value,
                    ]);
                @endphp

                <a href="{{ $statusUrl }}"
                    class="tabs-link {{ $isActiveStatus ? 'is-active' : '' }}"
                    aria-current="{{ $isActiveStatus ? 'page' : 'false' }}">
                    {{ $label }}

                    @if(array_key_exists($value, $selfServiceCustomerStatusCounts) && (int) $selfServiceCustomerStatusCounts[$value] > 0)
                        <span class="tabs-link__count">
                            ({{ (int) $selfServiceCustomerStatusCounts[$value] }})
                        </span>
                    @endif
                </a>
            @endforeach
        </div>
    </x-slot:tabs>
</x-tab-toolbar>

<x-card>
    @if($selfServiceCustomerRegistrations->isEmpty())
        <p class="mb-0">No hay solicitudes de clientes para el estado seleccionado.</p>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Estado</th>
                        <th>Cliente</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Cuenta externa</th>
                        <th>Relación tienda</th>
                        <th>Identidad</th>
                        <th>Operación</th>
                        <th>Solicitud</th>
                        <th>Confirmación</th>
                        <th>Party</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($selfServiceCustomerRegistrations as $registration)
                        @php
                            $status = $registration->status;

                            if ($registration->status === 'pending' && $registration->isExpired()) {
                                $status = 'expired';
                            }

                            $account = $registration->account;
                            $storeCustomer = $registration->storeCustomer;

                            $partyLabel = $registration->party?->name
                                ?? ($registration->party_id ? ('Party #' . $registration->party_id) : '—');

                            $statusBadgeClass = $statusBadgeClasses[$status] ?? 'status-badge--pending';

                            $accountLabel = $account
                                ? ('Cuenta #' . $account->id)
                                : '—';

                            $accountStatusLabel = $account?->status
                                ? ucfirst($account->status)
                                : null;

                            $storeStatus = $storeCustomer?->status;
                            $storeStatusLabel = $storeStatus
                                ? ($storeStatusLabels[$storeStatus] ?? $storeStatus)
                                : '—';

                            $storeStatusBadgeClass = $storeStatus
                                ? ($storeStatusBadgeClasses[$storeStatus] ?? 'status-badge--pending')
                                : 'status-badge--pending';

                            $identityStage = $storeCustomer?->identity_stage
                                ?? (($registration->meta ?? [])['identity_stage'] ?? null);

                            $identityStageLabel = $identityStage
                                ? ($identityStageLabels[$identityStage] ?? $identityStage)
                                : '—';

                            $operationEnabled = $storeCustomer?->operation_enabled === true;
                        @endphp

                        <tr>
                            <td>
                                <span class="status-badge {{ $statusBadgeClass }}">
                                    {{ $statusLabels[$status] ?? $status }}
                                </span>
                            </td>

                            <td>{{ $registration->display_name ?: $registration->name }}</td>

                            <td>{{ $registration->email ?: '—' }}</td>

                            <td>{{ $registration->phone ?: '—' }}</td>

                            <td>
                                <div>{{ $accountLabel }}</div>

                                @if($accountStatusLabel)
                                    <small>{{ $accountStatusLabel }}</small>
                                @endif
                            </td>

                            <td>
                                @if($storeCustomer)
                                    <span class="status-badge {{ $storeStatusBadgeClass }}">
                                        {{ $storeStatusLabel }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>

                            <td>{{ $identityStageLabel }}</td>

                            <td>
                                @if($storeCustomer)
                                    <span class="status-badge {{ $operationEnabled ? 'status-badge--done' : 'status-badge--pending' }}">
                                        {{ $operationEnabled ? 'Habilitada' : 'Bloqueada' }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>

                            <td>{{ $registration->created_at?->format('d/m/Y H:i') ?? '—' }}</td>

                            <td>{{ $registration->confirmed_at?->format('d/m/Y H:i') ?? '—' }}</td>

                            <td>{{ $partyLabel }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <x-dev-component-version name="tenants.partials.profile-self-service-customers-tab" version="V5" />
</x-card>