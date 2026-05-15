{{-- FILE: resources/views/shops/partials/table.blade.php | V2 --}}

@php
    use App\Models\Shop;

    $statusLabels = [
        Shop::STATUS_DRAFT => 'Borrador',
        Shop::STATUS_ACTIVE => 'Activa',
        Shop::STATUS_INACTIVE => 'Inactiva',
    ];

    $statusClasses = [
        Shop::STATUS_DRAFT => 'status-badge--muted',
        Shop::STATUS_ACTIVE => 'status-badge--success',
        Shop::STATUS_INACTIVE => 'status-badge--neutral',
    ];
@endphp

@if ($shops->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Estado</th>
                    <th>Artículos</th>
                    <th>Publicada</th>
                    <th>Actualizada</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($shops as $shop)
                    <tr>
                        <td>
                            <a href="{{ route('shops.show', $shop) }}">
                                {{ $shop->name }}
                            </a>

                            @if ($shop->description)
                                <div class="table-cell-help">
                                    {{ \Illuminate\Support\Str::limit($shop->description, 90) }}
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="status-badge {{ $statusClasses[$shop->status] ?? '' }}">
                                {{ $statusLabels[$shop->status] ?? $shop->status }}
                            </span>
                        </td>
                        <td>
                            {{ $shop->items_count ?? 0 }}
                        </td>
                        <td>
                            {{ $shop->published_at?->format('d/m/Y H:i') ?: '—' }}
                        </td>
                        <td>
                            {{ $shop->updated_at?->format('d/m/Y H:i') ?: '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="empty-state">{{ $emptyMessage ?? 'No hay tiendas para mostrar.' }}</p>
@endif