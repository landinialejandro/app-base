{{-- FILE: resources/views/documents/partials/embedded-tabs.blade.php | V3 --}}

@php
    use App\Support\Catalogs\DocumentCatalog;

    $documents = $documents ?? collect();

    $showParty = $showParty ?? false;
    $showAsset = $showAsset ?? true;
    $showOrder = $showOrder ?? true;

    $emptyMessage = $emptyMessage ?? 'No hay documentos para mostrar.';
    $allLabel = $allLabel ?? 'Todos';
    $trailQuery = $trailQuery ?? [];

    $kinds = DocumentCatalog::kindLabels();
    $tabsId = $tabsId ?? 'documents-tabs-' . uniqid();
@endphp

<div class="tabs" data-tabs>
    <div class="tabs-nav" role="tablist" aria-label="Tipos de documentos">
        <button type="button" class="tabs-link is-active" data-tab-link="{{ $tabsId }}-all" role="tab"
            aria-selected="true">
            {{ $allLabel }}
            @if ($documents->count())
                ({{ $documents->count() }})
            @endif
        </button>

        @foreach ($kinds as $value => $label)
            @php
                $kindDocuments = $documents->where('kind', $value)->values();
            @endphp

            <button type="button" class="tabs-link" data-tab-link="{{ $tabsId }}-{{ $value }}"
                role="tab" aria-selected="false">
                {{ $label }}
                @if ($kindDocuments->count())
                    ({{ $kindDocuments->count() }})
                @endif
            </button>
        @endforeach
    </div>

    <section class="tab-panel is-active" data-tab-panel="{{ $tabsId }}-all">
        <div class="tab-panel-stack">
            <x-card class="list-card">
                @include('documents.partials.table', [
                    'documents' => $documents,
                    'showParty' => $showParty,
                    'showAsset' => $showAsset,
                    'showOrder' => $showOrder,
                    'emptyMessage' => $emptyMessage,
                    'trailQuery' => $trailQuery,
                ])
            </x-card>
        </div>
    </section>

    @foreach ($kinds as $value => $label)
        @php
            $kindDocuments = $documents->where('kind', $value)->values();
        @endphp

        <section class="tab-panel" data-tab-panel="{{ $tabsId }}-{{ $value }}" hidden>
            <div class="tab-panel-stack">
                <x-card class="list-card">
                    @include('documents.partials.table', [
                        'documents' => $kindDocuments,
                        'showParty' => $showParty,
                        'showAsset' => $showAsset,
                        'showOrder' => $showOrder,
                        'emptyMessage' => "No hay documentos de tipo {$label} para mostrar.",
                        'trailQuery' => $trailQuery,
                    ])
                </x-card>
            </div>
        </section>
    @endforeach
</div>



{{-- FILE: resources/views/orders/items/partials/table.blade.php | V3 --}}

@php
    use App\Support\Catalogs\ProductCatalog;

    $order = $order ?? null;
    $items = $items ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay ítems cargados en esta orden.';
    $trailQuery = $trailQuery ?? [];
@endphp

@if ($items->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>Posición</th>
                    <th>Tipo</th>
                    <th>Descripción</th>
                    <th>Cantidad</th>
                    <th>Precio unitario</th>
                    <th>Total línea</th>
                    <th class="compact-actions-cell">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td>{{ $item->position }}</td>
                        <td>{{ ProductCatalog::kindLabel($item->kind) }}</td>
                        <td>{{ $item->description }}</td>
                        <td>{{ number_format($item->quantity, 2, ',', '.') }}</td>
                        <td>${{ number_format($item->unit_price, 2, ',', '.') }}</td>
                        <td>${{ number_format($item->subtotal, 2, ',', '.') }}</td>
                        <td class="compact-actions-cell">
                            @can('update', $order)
                                <div class="compact-actions">
                                    <a href="{{ route('orders.items.edit', ['order' => $order, 'item' => $item] + $trailQuery) }}"
                                        class="btn btn-secondary btn-icon" title="Editar ítem" aria-label="Editar ítem">
                                        <x-icons.pencil />
                                    </a>

                                    <form method="POST"
                                        action="{{ route('orders.items.destroy', ['order' => $order, 'item' => $item] + $trailQuery) }}"
                                        class="inline-form" data-action="app-confirm-submit"
                                        data-confirm-message="¿Deseas eliminar este ítem?">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="btn btn-danger btn-icon" title="Eliminar ítem"
                                            aria-label="Eliminar ítem">
                                            <x-icons.trash />
                                        </button>
                                    </form>
                                </div>
                            @else
                                —
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="mb-0">{{ $emptyMessage }}</p>
@endif



{{-- FILE: resources/views/appointments/partials/calendar-day-cell.blade.php | V1 --}}

@php
    use App\Support\Catalogs\AppointmentCatalog;

    $mode = $mode ?? 'month';
    $appointments = $day['appointments'];
    $maxVisibleAppointments = $maxVisibleAppointments ?? ($mode === 'week' ? 8 : 4);
    $visibleAppointments = $appointments->take($maxVisibleAppointments);
    $remainingCount = max($appointments->count() - $visibleAppointments->count(), 0);
    $isPastDay = $day['date']
        ->copy()
        ->startOfDay()
        ->lt(now()->startOfDay());
@endphp

<div
    class="appointment-calendar-day
        {{ $day['is_current_month'] ? 'is-current-month' : 'is-outside-month' }}
        {{ $day['is_today'] ? 'is-today' : '' }}
        {{ $isPastDay ? 'is-past-day' : '' }}
        {{ $mode === 'week' ? 'is-week-mode' : 'is-month-mode' }}">
    <div class="appointment-calendar-day-header">
        <div class="appointment-calendar-day-number">
            {{ $day['date']->day }}
        </div>

        <div class="appointment-calendar-day-actions">
            @unless ($isPastDay)
                <a href="{{ route('appointments.create', ['scheduled_date' => $day['date_key']]) }}"
                    class="appointment-calendar-add" title="Crear turno para {{ $day['date']->format('d/m/Y') }}"
                    aria-label="Crear turno para {{ $day['date']->format('d/m/Y') }}">
                    <x-icons.plus />
                </a>
            @endunless
        </div>
    </div>

    <div class="appointment-calendar-day-summary">
        @if ($appointments->count())
            {{ $appointments->count() }} {{ $appointments->count() === 1 ? 'turno' : 'turnos' }}
        @else
            Sin turnos
        @endif
    </div>

    <div class="appointment-calendar-day-list">
        @forelse ($visibleAppointments as $appointment)
            @php
                $rowTitle = AppointmentCatalog::rowTitleFor($appointment->kind, $appointment->work_mode);
                $timeLabel = $appointment->is_all_day
                    ? 'Día completo'
                    : ($appointment->starts_at && $appointment->ends_at
                        ? $appointment->starts_at->format('H:i') . ' - ' . $appointment->ends_at->format('H:i')
                        : 'Sin horario');

                $orderLabel = $appointment->order
                    ? ($appointment->order->number ?:
                    'Orden #' . $appointment->order->id)
                    : null;

                $secondaryReference = $appointment->workstation_name ?: ($appointment->asset?->name ?: null);
            @endphp

            <a href="{{ route('appointments.show', $appointment) }}"
                class="appointment-calendar-item status-accent-{{ $appointment->status }}">
                <div class="appointment-calendar-item-time">{{ $timeLabel }}</div>

                <div class="appointment-calendar-item-title">
                    {{ $appointment->title ?: $rowTitle }}
                </div>

                <div class="appointment-calendar-item-meta">
                    @if ($appointment->party)
                        <span>{{ $appointment->party->name }}</span>
                    @elseif ($appointment->assignedUser)
                        <span>{{ $appointment->assignedUser->name }}</span>
                    @endif
                </div>

                @if ($mode === 'week')
                    <div class="appointment-calendar-item-chips">
                        <span class="appointment-calendar-chip appointment-calendar-chip--status">
                            {{ AppointmentCatalog::statusLabel($appointment->status) }}
                        </span>

                        <span class="appointment-calendar-chip appointment-calendar-chip--kind">
                            {{ AppointmentCatalog::kindLabel($appointment->kind) }}
                        </span>

                        <span
                            class="appointment-calendar-chip {{ $appointment->order ? 'appointment-calendar-chip--order' : 'appointment-calendar-chip--no-order' }}">
                            {{ $appointment->order ? 'Con ' . strtolower(AppointmentCatalog::orderLabel()) : 'Sin ' . strtolower(AppointmentCatalog::orderLabel()) }}
                        </span>
                    </div>

                    <div class="appointment-calendar-item-extra">
                        @if ($orderLabel)
                            <div class="appointment-calendar-item-line">
                                <span
                                    class="appointment-calendar-item-label">{{ AppointmentCatalog::orderLabel() }}:</span>
                                <span>{{ $orderLabel }}</span>
                            </div>
                        @endif

                        @if ($secondaryReference)
                            <div class="appointment-calendar-item-line">
                                <span class="appointment-calendar-item-label">Ref.:</span>
                                <span>{{ $secondaryReference }}</span>
                            </div>
                        @endif
                    </div>
                @endif
            </a>
        @empty
            <div class="appointment-calendar-empty">
                —
            </div>
        @endforelse

        @if ($remainingCount > 0)
            <div class="appointment-calendar-more">
                +{{ $remainingCount }} más
            </div>
        @endif
    </div>
</div>
