{{-- FILE: resources/views/shops/tabs/items-table.blade.php | V4 --}}

@php
    use App\Support\Catalogs\ShopCatalog;
    use App\Support\Products\ProductLinked;

    $canUpdateShop = $canUpdateShop ?? false;
    $trailQuery = $trailQuery ?? [];
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
                            @include('products.components.linked-product', [
                                'linked' => ProductLinked::forProduct($item->product, $trailQuery, 'Producto'),
                            ])

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
                            <span class="status-badge {{ ShopCatalog::itemBadgeClass($item->status) }}">
                                {{ ShopCatalog::itemStatusLabel($item->status, $item->status) }}
                            </span>
                        </td>
                        <td>
                            {{ $item->sort_order ?? 0 }}
                        </td>
                        <td class="table-actions">
                            @if ($canUpdateShop)
                                <x-button-tool
                                    :href="route('shops.items.edit', ['shop' => $shop, 'item' => $item] + $trailQuery)"
                                    title="Editar artículo"
                                    label="Editar artículo"
                                >
                                    <x-icons.pencil />
                                </x-button-tool>

                                @php
                                    $isPublished = ShopCatalog::isItemPublishedStatus($item->status);
                                    $nextStatus = ShopCatalog::nextItemToggleStatus($item->status);
                                    $visibilityActionTitle = $isPublished ? 'Ocultar artículo' : 'Publicar artículo';
                                @endphp

                                <x-button-tool-submit
                                    :action="route('shops.items.update', ['shop' => $shop, 'item' => $item] + $trailQuery)"
                                    method="PUT"
                                    variant="secondary"
                                    :title="$visibilityActionTitle"
                                    :label="$visibilityActionTitle"
                                >
                                    <x-slot:fields>
                                        <input type="hidden" name="status" value="{{ $nextStatus }}">
                                    </x-slot:fields>

                                    @if ($isPublished)
                                        <x-icons.eye />
                                    @else
                                        <x-icons.eye-slash />
                                    @endif
                                </x-button-tool-submit>

                                <x-button-tool-submit
                                    :action="route('shops.items.destroy', ['shop' => $shop, 'item' => $item] + $trailQuery)"
                                    method="DELETE"
                                    variant="danger"
                                    title="Eliminar artículo de la tienda"
                                    label="Eliminar artículo de la tienda"
                                    message="¿Eliminar este artículo de la tienda?"
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