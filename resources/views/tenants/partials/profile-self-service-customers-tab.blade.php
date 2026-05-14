{{-- FILE: resources/views/tenants/partials/profile-self-service-customers-tab.blade.php | V8 --}}

@php
    $selfServiceStoreCustomers = $selfServiceStoreCustomers ?? collect();
    $selfServiceCustomerStatusFilter = $selfServiceCustomerStatusFilter ?? 'all';
    $selfServiceCustomerStatusOptions = $selfServiceCustomerStatusOptions ?? [];
    $selfServiceCustomerStatusCounts = $selfServiceCustomerStatusCounts ?? [];
    $canManageSelfServiceCustomers = $canManageSelfServiceCustomers ?? false;

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
    @if($selfServiceStoreCustomers->isEmpty())
        <p class="mb-0">No hay clientes de tienda para el estado seleccionado.</p>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Cliente externo</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Party</th>
                        <th>Relación tienda</th>
                        <th>Identidad</th>
                        <th>Identidad completada</th>
                        <th>Términos</th>
                        <th>Operación</th>
                        <th>Alta relación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($selfServiceStoreCustomers as $storeCustomer)
                        @php
                            $account = $storeCustomer->account;
                            $party = $storeCustomer->party;

                            $customerLabel = $account?->display_name
                                ?: $party?->display_name
                                ?: $party?->name
                                ?: 'Cliente externo';

                            $partyLabel = $party?->display_name
                                ?: $party?->name
                                ?: ($storeCustomer->party_id ? ('Party #' . $storeCustomer->party_id) : '—');

                            $storeStatus = $storeCustomer->status;
                            $storeStatusLabel = $storeStatusLabels[$storeStatus] ?? $storeStatus;
                            $storeStatusBadgeClass = $storeStatusBadgeClasses[$storeStatus] ?? 'status-badge--pending';

                            $identityStage = $storeCustomer->identity_stage;
                            $identityStageLabel = $identityStageLabels[$identityStage] ?? $identityStage;

                            $operationEnabled = $storeCustomer->operation_enabled === true;

                            $canCompleteIdentity = $canManageSelfServiceCustomers
                                && $storeCustomer->status === 'active'
                                && $storeCustomer->identity_stage !== 'operational_identity_completed';

                            $canEnableOperation = $canManageSelfServiceCustomers
                                && $storeCustomer->status === 'active'
                                && $storeCustomer->identity_stage === 'operational_identity_completed'
                                && $storeCustomer->operation_enabled !== true;
                        @endphp

                        <tr>
                            <td>{{ $customerLabel }}</td>

                            <td>{{ $account?->email ?: '—' }}</td>

                            <td>{{ $account?->phone ?: '—' }}</td>

                            <td>{{ $partyLabel }}</td>

                            <td>
                                <span class="status-badge {{ $storeStatusBadgeClass }}">
                                    {{ $storeStatusLabel }}
                                </span>
                            </td>

                            <td>{{ $identityStageLabel }}</td>

                            <td>{{ $storeCustomer->identity_completed_at?->format('d/m/Y H:i') ?? '—' }}</td>

                            <td>{{ $storeCustomer->terms_accepted_at?->format('d/m/Y H:i') ?? '—' }}</td>

                            <td>
                                <span class="status-badge {{ $operationEnabled ? 'status-badge--done' : 'status-badge--pending' }}">
                                    {{ $operationEnabled ? 'Habilitada' : 'Bloqueada' }}
                                </span>
                            </td>

                            <td>{{ $storeCustomer->created_at?->format('d/m/Y H:i') ?? '—' }}</td>

                            <td>
                                @if($canCompleteIdentity)
                                    <form method="POST" action="{{ route('tenant.self-service-store-customers.complete-identity', ['storeCustomer' => $storeCustomer]) }}">
                                        @csrf

                                        <button type="submit" class="btn btn-secondary">
                                            Completar identidad
                                        </button>
                                    </form>
                                @elseif($canEnableOperation)
                                    <form method="POST" action="{{ route('tenant.self-service-store-customers.enable-operation', ['storeCustomer' => $storeCustomer]) }}">
                                        @csrf

                                        <button type="submit" class="btn btn-primary">
                                            Habilitar operación
                                        </button>
                                    </form>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <x-dev-component-version name="tenants.partials.profile-self-service-customers-tab" version="V8" />
</x-card>
