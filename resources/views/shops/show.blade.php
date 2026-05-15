{{-- FILE: resources/views/shops/show.blade.php | V2 --}}

@extends('layouts.app')

@section('title', 'Detalle de tienda')

@section('content')
    @php
        use App\Models\Shop;
        use App\Support\Ui\HostTabs;

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

        $items = $shop->items ?? collect();

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
                ],
            ],
        ])->values();

        $activeTab = HostTabs::activeKey($tabItems, request()->query('return_tab'));
    @endphp

    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Tiendas', 'url' => route('shops.index')],
            ['label' => $shop->name],
        ]" />

        <x-page-header title="Detalle de tienda">
            @if ($shop->status !== Shop::STATUS_ACTIVE)
                @can('update', $shop)
                    <form method="POST" action="{{ route('shops.activate', $shop) }}">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            Activar tienda
                        </button>
                    </form>
                @endcan
            @endif

            <x-button-secondary :href="route('shops.preview', $shop)" target="_blank">
                Vista previa
            </x-button-secondary>

            @can('update', $shop)
                <x-button-edit :href="route('shops.edit', $shop)" />
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
                <span class="status-badge {{ $statusClasses[$shop->status] ?? '' }}">
                    {{ $statusLabels[$shop->status] ?? $shop->status }}
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

                <x-show-summary-item-detail-block label="Lectura externa" full>
                    Esta tienda configura qué catálogo se publica hacia la tienda externa. No genera compras, pagos,
                    órdenes, documentos, movimientos de stock, fichas ni QR.
                </x-show-summary-item-detail-block>
            </x-slot:details>
        </x-show-summary>

        <x-host-tabs :items="$tabItems" :active-tab="$activeTab" label="Secciones de la tienda" />

    </x-page>
@endsection