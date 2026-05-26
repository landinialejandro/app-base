{{-- FILE: resources/views/shops/tabs/items-table.blade.php | V6 --}}

@php
    use App\Support\Catalogs\ShopCatalog;
    use App\Support\Products\ProductLinked;
    use App\Support\Shops\ShopItemCommercialPolicyResolver;

    $canUpdateShop = $canUpdateShop ?? false;
    $trailQuery = $trailQuery ?? [];
    $commercialPolicyResolver = app(ShopItemCommercialPolicyResolver::class);
@endphp

@if ($items->count())
    <div class="table-wrap list-scroll">
        <table class="table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Nombre visible</th>
                    <th>Precio visible</th>
                    <th>Política comercial</th>
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
                            @php
                                $commercialPolicy = $commercialPolicyResolver->resolve($item);
                            @endphp

                            <div>
                                Carrito: {{ $commercialPolicy['allow_cart'] ? 'Sí' : 'No' }}
                            </div>
                            <div class="table-cell-help">
                                Compra directa: {{ $commercialPolicy['allow_direct_purchase'] ? 'Sí' : 'No' }}
                            </div>
                            <div class="table-cell-help">
                                Stock: {{ $commercialPolicy['labels']['stock_policy_mode'] }}
                            </div>
                            <div class="table-cell-help">
                                Cupo tienda: {{ $commercialPolicy['shop_stock_limit'] ?? '—' }}
                            </div>
                            <div class="table-cell-help">
                                Máx. checkout: {{ $commercialPolicy['max_quantity_per_checkout'] ?? '—' }}
                            </div>

                            @if ($commercialPolicy['stock_target_quantity'] !== null)
                                <div class="table-cell-help">
                                    Objetivo: {{ $commercialPolicy['stock_target_quantity'] }}
                                </div>
                            @endif

                            @if ($commercialPolicy['stock_protected_quantity'] !== null)
                                <div class="table-cell-help">
                                    Protegido: {{ $commercialPolicy['stock_protected_quantity'] }}
                                </div>
                            @endif

                            @if ($commercialPolicy['allow_target_margin'])
                                <div class="table-cell-help">
                                    Margen: Sí
                                </div>
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
