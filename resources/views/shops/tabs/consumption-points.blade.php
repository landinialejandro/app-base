{{-- FILE: resources/views/shops/tabs/consumption-points.blade.php | V1 --}}

@php
    use App\Models\ShopConsumptionPoint;
    use Illuminate\Support\Str;

    $consumptionPoints = $consumptionPoints ?? collect();
    $canUpdateShop = auth()->user()?->can('update', $shop) === true;
@endphp

<x-card class="list-card">
    <div class="content-section-header">
        <h2 class="content-section-title">Puntos de consumo</h2>
        <p class="content-section-text">
            Estos puntos pertenecen al plano interno autorizado de la tienda. Preparan futuros QR de consumo. No
            habilitan hardware real ni consumo por sí mismos.
        </p>
    </div>

    @if ($canUpdateShop)
        <form method="POST" action="{{ route('shops.consumption_points.store', $shop) }}" class="stacked-form">
            @csrf

            <div class="form-grid">
                <label>
                    <span>Nombre</span>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" placeholder="Box 1">
                </label>

                <label>
                    <span>Código opcional</span>
                    <input type="text" name="code" value="{{ old('code') }}" maxlength="80" placeholder="BOX-1">
                </label>

                <label>
                    <span>Estado</span>
                    <select name="status">
                        <option value="{{ ShopConsumptionPoint::STATUS_ACTIVE }}">Activo</option>
                        <option value="{{ ShopConsumptionPoint::STATUS_INACTIVE }}">Inactivo</option>
                    </select>
                </label>

                <label>
                    <span>Orden</span>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" step="1">
                </label>
            </div>

            <label>
                <span>Descripción opcional</span>
                <textarea name="description" rows="2" maxlength="1000" placeholder="Punto lógico de consumo para pruebas.">{{ old('description') }}</textarea>
            </label>

            <button type="submit" class="btn btn-primary">Crear punto de consumo</button>
        </form>
    @endif

    @if ($consumptionPoints->isEmpty())
        <p class="empty-state">Todavía no hay puntos de consumo configurados para esta tienda.</p>
    @else
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Código</th>
                        <th>Estado</th>
                        <th>Token futuro</th>
                        <th>Lectura QR futura</th>
                        <th>Orden</th>
                        @if ($canUpdateShop)
                            <th>Actualizar</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($consumptionPoints as $point)
                        <tr>
                            @if ($canUpdateShop)
                                @php
                                    $formId = 'consumption-point-'.$point->id;
                                @endphp
                                <td>
                                    <form id="{{ $formId }}" method="POST" action="{{ route('shops.consumption_points.update', ['shop' => $shop, 'point' => $point]) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="name" value="{{ old('name', $point->name) }}" required maxlength="255">
                                        <textarea name="description" rows="2" maxlength="1000">{{ old('description', $point->description) }}</textarea>
                                    </form>
                                </td>
                                <td>
                                    <input form="{{ $formId }}" type="text" name="code" value="{{ old('code', $point->code) }}" maxlength="80">
                                </td>
                                <td>
                                    <select form="{{ $formId }}" name="status">
                                        <option value="{{ ShopConsumptionPoint::STATUS_ACTIVE }}" @selected($point->status === ShopConsumptionPoint::STATUS_ACTIVE)>Activo</option>
                                        <option value="{{ ShopConsumptionPoint::STATUS_INACTIVE }}" @selected($point->status === ShopConsumptionPoint::STATUS_INACTIVE)>Inactivo</option>
                                    </select>
                                </td>
                                <td>{{ Str::of($point->public_token)->limit(11, '...') }}</td>
                                <td>QR público pendiente de ruta futura.</td>
                                <td>
                                    <input form="{{ $formId }}" type="number" name="sort_order" value="{{ old('sort_order', $point->sort_order) }}" min="0" step="1">
                                </td>
                                <td>
                                    <button form="{{ $formId }}" type="submit" class="btn btn-secondary">Guardar</button>
                                </td>
                            @else
                                <td>
                                    {{ $point->displayName() }}
                                    <div class="table-cell-help">{{ $point->description ?: 'Sin descripción.' }}</div>
                                </td>
                                <td>{{ $point->code ?: '—' }}</td>
                                <td>{{ $point->status === ShopConsumptionPoint::STATUS_ACTIVE ? 'Activo' : 'Inactivo' }}</td>
                                <td>{{ Str::of($point->public_token)->limit(11, '...') }}</td>
                                <td>QR público pendiente de ruta futura.</td>
                                <td>{{ $point->sort_order }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-card>
