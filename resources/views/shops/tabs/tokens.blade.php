{{-- FILE: resources/views/shops/tabs/tokens.blade.php | V2 --}}

@php
    $tokenConsumptionSummary = $tokenConsumptionSummary ?? [];
    $pockets = collect($tokenConsumptionSummary['pockets'] ?? []);
    $attempts = collect($tokenConsumptionSummary['attempts'] ?? []);
    $movements = collect($tokenConsumptionSummary['movements'] ?? []);
    $consumptionPoints = collect($tokenConsumptionSummary['consumption_points'] ?? []);
    $metrics = $tokenConsumptionSummary['metrics'] ?? [];

    $formatNumber = function ($value): string {
        $number = (float) ($value ?? 0);

        if (floor($number) === $number) {
            return (string) (int) $number;
        }

        return rtrim(rtrim(number_format($number, 2, ',', '.'), '0'), ',');
    };
@endphp

<x-card class="list-card">
    <div class="content-section-header">
        <h2 class="content-section-title">Fichas</h2>
        <p class="content-section-text">
            Esta lectura pertenece al plano interno autorizado de la tienda. Muestra fichas, pockets y consumos
            vinculables a productos publicados en esta tienda. self_service_sales conserva el ownership del pocket y
            del consumo externo.
        </p>
    </div>

    <div class="summary-inline-grid">
        <div class="summary-inline-card">
            <span class="summary-inline-label">Pockets activos</span>
            <strong>{{ $metrics['pockets_count'] ?? 0 }}</strong>
            <span>Contexto por producto publicado en esta tienda.</span>
        </div>
        <div class="summary-inline-card">
            <span class="summary-inline-label">Fichas disponibles</span>
            <strong>{{ $formatNumber($metrics['available_quantity'] ?? 0) }}</strong>
            <span>Saldo lógico vigente del pocket externo.</span>
        </div>
        <div class="summary-inline-card">
            <span class="summary-inline-label">Attempts pendientes</span>
            <strong>{{ $metrics['pending_attempts_count'] ?? 0 }}</strong>
            <span>Intenciones registradas sin confirmación.</span>
        </div>
        <div class="summary-inline-card">
            <span class="summary-inline-label">Attempts confirmados</span>
            <strong>{{ $metrics['confirmed_attempts_count'] ?? 0 }}</strong>
            <span>Consumos simulados confirmados.</span>
        </div>
        <div class="summary-inline-card">
            <span class="summary-inline-label">Fichas acreditadas</span>
            <strong>{{ $formatNumber($metrics['purchase_credit_quantity'] ?? 0) }}</strong>
            <span>Movimientos purchase_credit vinculables.</span>
        </div>
        <div class="summary-inline-card">
            <span class="summary-inline-label">Fichas consumidas</span>
            <strong>{{ $formatNumber($metrics['consumption_quantity'] ?? 0) }}</strong>
            <span>Movimientos consumption vinculables.</span>
        </div>
        <div class="summary-inline-card">
            <span class="summary-inline-label">Puntos de consumo</span>
            <strong>{{ $metrics['consumption_points_count'] ?? 0 }}</strong>
            <span>Puntos configurados para esta tienda.</span>
        </div>
        <div class="summary-inline-card">
            <span class="summary-inline-label">Puntos con pendientes</span>
            <strong>{{ $metrics['points_with_pending_attempts_count'] ?? 0 }}</strong>
            <span>Puntos con intenciones de uso sin confirmar.</span>
        </div>
        <div class="summary-inline-card">
            <span class="summary-inline-label">Puntos con confirmados</span>
            <strong>{{ $metrics['points_with_confirmed_attempts_count'] ?? 0 }}</strong>
            <span>Puntos con consumos simulados confirmados.</span>
        </div>
    </div>

    @if ($pockets->isEmpty() && $attempts->isEmpty() && $movements->isEmpty())
        <p class="empty-state">Todavía no hay pockets, attempts ni movements vinculables a productos publicados en esta tienda.</p>
    @else
        <div class="content-section-header">
            <h3 class="content-section-title">Pockets</h3>
        </div>

        @if ($pockets->isEmpty())
            <p class="empty-state">No hay pockets activos vinculables a esta tienda.</p>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Producto</th>
                            <th>SKU</th>
                            <th>Disponible</th>
                            <th>Segundos por ficha</th>
                            <th>Minutos totales</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pockets as $pocket)
                            <tr>
                                <td>{{ $pocket['customer_label'] }}</td>
                                <td>{{ $pocket['product_name'] }}</td>
                                <td>{{ $pocket['sku'] ?: '—' }}</td>
                                <td>{{ $formatNumber($pocket['quantity_available']) }}</td>
                                <td>{{ $pocket['unit_seconds_snapshot'] ?? '—' }}</td>
                                <td>{{ $pocket['total_minutes'] !== null ? $formatNumber($pocket['total_minutes']) : '—' }}</td>
                                <td>{{ $pocket['status'] ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="content-section-header">
            <h3 class="content-section-title">Consumos por punto</h3>
            <p class="content-section-text">
                Lectura interna read-only de attempts y consumos asociados a puntos de consumo de esta tienda.
            </p>
        </div>

        @if ($consumptionPoints->isEmpty())
            <p class="empty-state">Todavía no hay puntos de consumo vinculados a esta tienda.</p>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Punto</th>
                            <th>Código</th>
                            <th>Estado</th>
                            <th>Attempts pendientes</th>
                            <th>Attempts confirmados</th>
                            <th>Fichas consumidas</th>
                            <th>Segundos consumidos</th>
                            <th>Minutos consumidos</th>
                            <th>Último consumo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($consumptionPoints as $point)
                            <tr>
                                <td>{{ $point['name'] }}</td>
                                <td>{{ $point['code'] ?: '—' }}</td>
                                <td>{{ $point['status'] ?: '—' }}</td>
                                <td>{{ $point['pending_attempts_count'] }}</td>
                                <td>{{ $point['confirmed_attempts_count'] }}</td>
                                <td>{{ $formatNumber($point['consumed_quantity']) }}</td>
                                <td>{{ $formatNumber($point['consumed_seconds']) }}</td>
                                <td>{{ $formatNumber($point['consumed_minutes']) }}</td>
                                <td>{{ $point['last_movement_at']?->format('d/m/Y H:i') ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="content-section-header">
            <h3 class="content-section-title">Attempts recientes</h3>
            <p class="content-section-text">
                Gateway simulado indica llamada a controlador simulado. No representa hardware real.
            </p>
        </div>

        @if ($attempts->isEmpty())
            <p class="empty-state">No hay attempts recientes vinculables a esta tienda.</p>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Attempt</th>
                            <th>Customer</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Segundos</th>
                            <th>Minutos</th>
                            <th>Estado</th>
                            <th>Gateway simulado</th>
                            <th>Response</th>
                            <th>External ID</th>
                            <th>Punto</th>
                            <th>Confirmado</th>
                            <th>Fallido</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($attempts as $attempt)
                            <tr>
                                <td>#{{ $attempt['id'] }}</td>
                                <td>{{ $attempt['customer_label'] }}</td>
                                <td>{{ $attempt['product_name'] }}</td>
                                <td>{{ $formatNumber($attempt['quantity']) }}</td>
                                <td>{{ $attempt['total_seconds'] ?? '—' }}</td>
                                <td>{{ $attempt['total_minutes'] !== null ? $formatNumber($attempt['total_minutes']) : '—' }}</td>
                                <td>
                                    <span class="status-badge {{ $attempt['status_badge_class'] ?? 'status-badge--neutral' }}">
                                        {{ $attempt['status_label'] ?? '—' }}
                                    </span>
                                </td>
                                <td>
                                    {{ $attempt['calls_external_controller'] ? 'Sí' : 'No' }}
                                    @if ($attempt['controller_request_present'])
                                        <div class="table-cell-help">Request registrado</div>
                                    @endif
                                </td>
                                <td>
                                    {{ $attempt['provider_target'] ?: '—' }}
                                    @if ($attempt['response_status'])
                                        <div class="table-cell-help">{{ $attempt['response_status'] }}</div>
                                    @endif
                                </td>
                                <td>{{ $attempt['external_consumption_id'] ?: '—' }}</td>
                                <td>{{ $attempt['consumption_point_label'] ?: '—' }}</td>
                                <td>{{ $attempt['confirmed_at']?->format('d/m/Y H:i') ?: '—' }}</td>
                                <td>{{ $attempt['failed_at']?->format('d/m/Y H:i') ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="content-section-header">
            <h3 class="content-section-title">Movements recientes</h3>
            <p class="content-section-text">
                El movement consumption es descuento lógico del pocket; la referencia de gateway es evidencia simulada.
            </p>
        </div>

        @if ($movements->isEmpty())
            <p class="empty-state">No hay movements recientes vinculables a esta tienda.</p>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Movement</th>
                            <th>Customer</th>
                            <th>Producto</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Saldo posterior</th>
                            <th>Origen</th>
                            <th>Gateway simulado</th>
                            <th>Response</th>
                            <th>External ID</th>
                            <th>Punto</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($movements as $movement)
                            <tr>
                                <td>#{{ $movement['id'] }}</td>
                                <td>{{ $movement['customer_label'] }}</td>
                                <td>{{ $movement['product_name'] }}</td>
                                <td>{{ $movement['movement_type'] ?: '—' }}</td>
                                <td>{{ $formatNumber($movement['quantity']) }}</td>
                                <td>{{ $formatNumber($movement['balance_after']) }}</td>
                                <td>
                                    {{ $movement['source_type'] ?: '—' }}
                                    @if ($movement['source_id'])
                                        #{{ $movement['source_id'] }}
                                    @endif
                                </td>
                                <td>
                                    {{ $movement['calls_external_controller'] ? 'Sí' : 'No' }}
                                    @if ($movement['consumes_balance'])
                                        <div class="table-cell-help">Descuento lógico</div>
                                    @endif
                                </td>
                                <td>{{ $movement['controller_response_status'] ?: '—' }}</td>
                                <td>{{ $movement['external_consumption_id'] ?: '—' }}</td>
                                <td>{{ $movement['consumption_point_label'] ?: '—' }}</td>
                                <td>{{ $movement['created_at']?->format('d/m/Y H:i') ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</x-card>
