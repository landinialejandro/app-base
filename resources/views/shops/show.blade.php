{{-- FILE: resources/views/shops/show.blade.php | V3 --}}

@extends('layouts.app')

@section('title', 'Gestión de tienda')

@section('content')
    @php
        use App\Support\Catalogs\ShopCatalog;
        use App\Support\Ui\HostTabs;
        use App\Support\Navigation\NavigationTrail;

        $items = $shop->items ?? collect();
        $selfServiceCarts = $selfServiceCarts ?? collect();
        $selfServiceOrders = $selfServiceOrders ?? collect();

        $navigationTrail = $navigationTrail ?? [];
        $trailQuery = $trailQuery ?? NavigationTrail::toQuery($navigationTrail);
        $tabsLabel = 'Secciones de gestión de tienda';

        $upcomingContracts = [
            [
                'title' => 'Clientes tienda',
                'text' => 'Contrato interno pendiente para vincular clientes externos con gestión autorizada de tienda.',
            ],
            [
                'title' => 'Pagos',
                'text' => 'Contrato interno pendiente para que Payments gobierne intentos, estados y conciliación.',
            ],
            [
                'title' => 'Stock comprometible',
                'text' => 'Contrato interno pendiente para que Inventory defina disponibilidad real y compromiso de stock.',
            ],
            [
                'title' => 'Configuración comercial',
                'text' => 'Contrato interno pendiente para reglas operativas de tienda sin decidir precio, stock ni autorización pública.',
            ],
        ];

        $tabItems = collect([
            [
                'type' => 'embedded',
                'slot' => 'tab_panels',
                'key' => 'items',
                'label' => 'Artículos',
                'priority' => 10,
                'count' => $items->count(),
                'view' => 'shops.tabs.items',
                'data' => [
                    'shop' => $shop,
                    'items' => $items,
                    'trailQuery' => $trailQuery,
                ],
            ],
            [
                'type' => 'embedded',
                'slot' => 'tab_panels',
                'key' => 'carts',
                'label' => 'Carritos externos',
                'priority' => 15,
                'count' => $selfServiceCarts->count(),
                'view' => 'shops.tabs.carts',
                'data' => [
                    'shop' => $shop,
                    'selfServiceCarts' => $selfServiceCarts,
                    'trailQuery' => $trailQuery,
                ],
            ],
            [
                'type' => 'embedded',
                'slot' => 'tab_panels',
                'key' => 'orders',
                'label' => 'Ventas / Órdenes',
                'priority' => 20,
                'count' => $selfServiceOrders->count(),
                'view' => 'shops.tabs.orders',
                'data' => [
                    'shop' => $shop,
                    'selfServiceOrders' => $selfServiceOrders,
                    'trailQuery' => $trailQuery,
                ],
            ],
        ])->values();

        $activeTab = HostTabs::activeKey($tabItems, request()->query('return_tab'));
    @endphp

    <x-page>

        <x-breadcrumb :items="NavigationTrail::toBreadcrumbItems($navigationTrail)" />

        <x-page-header title="Gestión de tienda">
            @if (! ShopCatalog::isActiveStatus($shop->status))
                @can('update', $shop)
                    <form method="POST" action="{{ route('shops.activate', $shop) }}">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            Activar tienda
                        </button>
                    </form>
                @endcan
            @endif

            <x-button-secondary :href="route('shops.preview', ['shop' => $shop] + $trailQuery)" target="_blank">
                Vista previa
            </x-button-secondary>

            @can('update', $shop)
                <x-button-edit :href="route('shops.edit', ['shop' => $shop] + $trailQuery)" />
            @endcan

            @can('delete', $shop)
                <x-button-delete
                    :action="route('shops.destroy', $shop)"
                    message="¿Deseas eliminar esta tienda?"
                />
            @endcan

            <x-button-back :href="route('shops.index')" />
        </x-page-header>

        <x-show-summary details-id="shop-more-detail">
            <x-show-summary-item label="Nombre">
                {{ $shop->name }}
            </x-show-summary-item>

            <x-show-summary-item label="Estado">
                <span class="status-badge {{ ShopCatalog::badgeClass($shop->status) }}">
                    {{ ShopCatalog::statusLabel($shop->status, $shop->status) }}
                </span>
            </x-show-summary-item>

            <x-show-summary-item label="Artículos">
                {{ $items->count() }}
            </x-show-summary-item>

            <x-slot:details>
                <x-show-summary-item-detail-block label="Publicada">
                    {{ $shop->published_at?->format('d/m/Y H:i') ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Actualizada">
                    {{ $shop->updated_at?->format('d/m/Y H:i') ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Descripción" full>
                    {{ $shop->description ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Lectura interna" full>
                    Esta pantalla pertenece al plano interno autorizado. La tienda pública exhibe el catálogo publicado;
                    la operación real se resuelve en backend mediante los módulos dueños: shops, Products, Inventory,
                    Payments, Orders, Documents y Security.
                </x-show-summary-item-detail-block>
            </x-slot:details>
        </x-show-summary>

        <x-card>
            <div class="dashboard-section-header">
                <h2 class="dashboard-section-title">Perfil operativo de tienda</h2>
                <p class="dashboard-section-text">
                    Este espacio concentra la gestión interna de la tienda. Por ahora mantiene la administración del
                    catálogo publicado y deja visibles los próximos contratos internos sin simular funcionalidad activa.
                </p>
            </div>

            <div class="summary-inline-grid">
                @foreach ($upcomingContracts as $contract)
                    <div class="summary-inline-card">
                        <span class="summary-inline-label">{{ $contract['title'] }}</span>
                        <strong>Próximo contrato interno</strong>
                        <span>{{ $contract['text'] }}</span>
                    </div>
                @endforeach
            </div>
        </x-card>

        <x-host-tabs :items="$tabItems" :active-tab="$activeTab" :label="$tabsLabel" />

    </x-page>
@endsection
