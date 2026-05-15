{{-- FILE: resources/views/shops/tabs/items-table.blade.php | V2 --}}

@php
    use App\Models\ShopItem;

    $statusLabels = [
        ShopItem::STATUS_DRAFT => 'Borrador',
        ShopItem::STATUS_PUBLISHED => 'Publicado',
        ShopItem::STATUS_HIDDEN => 'Oculto',
    ];

    $statusClasses = [
        ShopItem::STATUS_DRAFT => 'status-badge--muted',
        ShopItem::STATUS_PUBLISHED => 'status-badge--success',
        ShopItem::STATUS_HIDDEN => 'status-badge--neutral',
    ];

    $canUpdateShop = $canUpdateShop ?? false;
@endphp

@if ($items->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Nombre visible</th>
                    <th>Precio visible</th>
                    <th>Estado</th>
                    <th>Orden</th>
                    <th class="table-actions">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td>
                            {{ $item->product?->name ?? '—' }}

                            @if ($item->product?->sku)
                                <div class="table-cell-help">
                                    {{ $item->product->sku }}
                                </div>
                            @endif
                        </td>
                        <td>
                            {{ $item->displayName() }}

                            @if ($item->displayDescription())
                                <div class="table-cell-help">
                                    {{ \Illuminate\Support\Str::limit($item->displayDescription(), 90) }}
                                </div>
                            @endif
                        </td>
                        <td>
                            @if ($item->displayPrice() !== null)
                                $ {{ number_format((float) $item->displayPrice(), 2, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <span class="status-badge {{ $statusClasses[$item->status] ?? '' }}">
                                {{ $statusLabels[$item->status] ?? $item->status }}
                            </span>
                        </td>
                        <td>
                            {{ $item->sort_order ?? 0 }}
                        </td>
                        <td class="table-actions">
                            @if ($canUpdateShop)
                                <x-button-tool
                                    :href="route('shops.items.edit', [$shop, $item])"
                                    title="Editar artículo"
                                    label="Editar artículo"
                                >
                                    <x-icons.pencil />
                                </x-button-tool>

                                <x-button-tool-submit
                                    :action="route('shops.items.destroy', [$shop, $item])"
                                    method="DELETE"
                                    variant="danger"
                                    title="Ocultar artículo"
                                    label="Ocultar artículo"
                                >
                                    <x-icons.trash />
                                </x-button-tool-submit>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="empty-state">No hay artículos para mostrar en este estado.</p>
@endif