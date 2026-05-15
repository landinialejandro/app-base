{{-- FILE: resources/views/inventory/partials/material-balance.blade.php | V1 --}}

@php
    $materialBalance = $materialBalance ?? [];
    $items = collect($materialBalance['items'] ?? [])->values();
    $materials = $items
        ->flatMap(fn($item) => collect($item['materials'] ?? [])->map(function ($material) use ($item) {
            $material['order_item_id'] = $item['order_item_id'] ?? ($material['order_item_id'] ?? null);

            return $material;
        }))
        ->values();
@endphp

@if ($materials->isNotEmpty())
    <x-card class="list-card">
        <x-slot:header>
            <h3 class="card-title">Material formal</h3>
        </x-slot:header>

        <div class="table-wrap list-scroll">
            <table class="table">
                <thead>
                    <tr>
                        <th>Línea</th>
                        <th>Material</th>
                        <th>Requerido</th>
                        <th>Entregado</th>
                        <th>Aplicado</th>
                        <th>Devuelto</th>
                        <th>Disponible</th>
                        <th>Faltante</th>
                        <th>Estado</th>
                        <th class="compact-actions-cell">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($materials as $material)
                        @php
                            $actions = collect($material['actions'] ?? [])
                                ->filter(fn($action) => ($action['is_available'] ?? false) === true)
                                ->values();
                        @endphp

                        <tr>
                            <td>{{ $material['order_item_id'] ?? '—' }}</td>
                            <td>{{ $material['product_name'] ?? '—' }}</td>
                            <td>{{ $material['required_display'] ?? '—' }}</td>
                            <td>{{ $material['delivered_display'] ?? '—' }}</td>
                            <td>{{ $material['applied_display'] ?? '—' }}</td>
                            <td>{{ $material['returned_display'] ?? '—' }}</td>
                            <td>{{ $material['available_display'] ?? '—' }}</td>
                            <td>{{ $material['missing_display'] ?? '—' }}</td>
                            <td>
                                <span class="status-badge {{ $material['consistency_badge'] ?? 'status-badge--neutral' }}">
                                    {{ $material['consistency_label'] ?? 'sin flujo formal' }}
                                </span>

                                @if (!empty($material['warning_label']))
                                    <div class="form-help">{{ $material['warning_label'] }}</div>
                                @endif
                            </td>
                            <td class="compact-actions-cell">
                                @if ($actions->isNotEmpty())
                                    <div class="compact-actions">
                                        @include('inventory.components.row-actions', [
                                            'actions' => $actions,
                                        ])
                                    </div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
@endif
