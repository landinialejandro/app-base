{{-- FILE: resources/views/appointments/partials/embedded-tabs.blade.php | V2 --}}

@php
    use App\Models\Appointment;
    use App\Support\Catalogs\AppointmentCatalog;

    $appointments = $appointments ?? collect();

    $supportsPartiesModule = $supportsPartiesModule ?? false;
    $supportsAssetsModule = $supportsAssetsModule ?? false;
    $supportsOrdersModule = $supportsOrdersModule ?? false;

    $emptyMessage = $emptyMessage ?? 'No hay turnos para mostrar.';
    $allLabel = $allLabel ?? 'Todos';

    $statuses = AppointmentCatalog::statusLabels();
    $tabsId = $tabsId ?? 'appointments-tabs-' . uniqid();
    $trailQuery = $trailQuery ?? [];
    $createBaseQuery = $createBaseQuery ?? [];

    $canCreateAppointments = auth()->user()?->can('create', Appointment::class) ?? false;
@endphp

<div class="tabs" data-tabs>
    @php
        $toolbarAction = null;

        if ($canCreateAppointments) {
            $toolbarAction = route('appointments.create', $createBaseQuery + $trailQuery);
        }
    @endphp

    <x-tab-toolbar label="Estados de los turnos">
        <x-slot:tabs>
            <x-horizontal-scroll label="Estados de los turnos">
                <button type="button" class="tabs-link is-active" data-tab-link="{{ $tabsId }}-all" role="tab"
                    aria-selected="true">
                    {{ $allLabel }}
                    @if ($appointments->count())
                        ({{ $appointments->count() }})
                    @endif
                </button>

                @foreach ($statuses as $value => $label)
                    @php
                        $statusAppointments = $appointments->where('status', $value)->values();
                    @endphp

                    <button type="button" class="tabs-link" data-tab-link="{{ $tabsId }}-{{ $value }}"
                        role="tab" aria-selected="false">
                        {{ $label }}
                        @if ($statusAppointments->count())
                            ({{ $statusAppointments->count() }})
                        @endif
                    </button>
                @endforeach
            </x-horizontal-scroll>
        </x-slot:tabs>

        <x-slot:actions>
            @if ($toolbarAction)
                <x-button-create :href="$toolbarAction" label="Nuevo turno" class="btn-sm" />
            @endif
        </x-slot:actions>
    </x-tab-toolbar>

    <section class="tab-panel is-active" data-tab-panel="{{ $tabsId }}-all">
        <div class="tab-panel-stack">
            <x-card class="list-card">
                @include('appointments.partials.table', [
                    'appointments' => $appointments,
                    'emptyMessage' => $emptyMessage,
                    'supportsPartiesModule' => $supportsPartiesModule,
                    'supportsAssetsModule' => $supportsAssetsModule,
                    'supportsOrdersModule' => $supportsOrdersModule,
                    'trailQuery' => $trailQuery,
                ])
            </x-card>
        </div>
    </section>

    @foreach ($statuses as $value => $label)
        @php
            $statusAppointments = $appointments->where('status', $value)->values();
        @endphp

        <section class="tab-panel" data-tab-panel="{{ $tabsId }}-{{ $value }}" hidden>
            <div class="tab-panel-stack">
                <x-card class="list-card">
                    @include('appointments.partials.table', [
                        'appointments' => $statusAppointments,
                        'emptyMessage' => "No hay turnos en estado {$label} para mostrar.",
                        'supportsPartiesModule' => $supportsPartiesModule,
                        'supportsAssetsModule' => $supportsAssetsModule,
                        'supportsOrdersModule' => $supportsOrdersModule,
                        'trailQuery' => $trailQuery,
                    ])
                </x-card>
            </div>
        </section>
    @endforeach
</div>
