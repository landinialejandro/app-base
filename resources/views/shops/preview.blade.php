{{-- FILE: resources/views/shops/preview.blade.php | V2 --}}

@extends('layouts.app')

@section('title', 'Vista previa de tienda')

@section('content')
    @php
        use App\Models\Shop;
        use App\Models\ShopItem;

        $shopStatusLabels = [
            Shop::STATUS_DRAFT => 'Borrador',
            Shop::STATUS_ACTIVE => 'Activa',
            Shop::STATUS_INACTIVE => 'Inactiva',
        ];

        $shopStatusClasses = [
            Shop::STATUS_DRAFT => 'status-badge--muted',
            Shop::STATUS_ACTIVE => 'status-badge--success',
            Shop::STATUS_INACTIVE => 'status-badge--neutral',
        ];

        $itemStatusLabels = [
            ShopItem::STATUS_DRAFT => 'Borrador',
            ShopItem::STATUS_PUBLISHED => 'Publicado',
            ShopItem::STATUS_HIDDEN => 'Oculto',
        ];

        $itemStatusClasses = [
            ShopItem::STATUS_DRAFT => 'status-badge--muted',
            ShopItem::STATUS_PUBLISHED => 'status-badge--success',
            ShopItem::STATUS_HIDDEN => 'status-badge--neutral',
        ];

        $publicVisibleItemsCount = $publicVisibleItemsCount ?? 0;
    @endphp

    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Tiendas', 'url' => route('shops.index')],
            ['label' => $shop->name, 'url' => route('shops.show', $shop)],
            ['label' => 'Vista previa'],
        ]" />

        <x-page-header title="Vista previa de tienda">
            <x-button-back :href="route('shops.show', $shop)" />
        </x-page-header>

        <x-card>
            <div class="summary-inline-grid">
                <div class="summary-inline-card">
                    <div class="summary-inline-label">Tienda</div>
                    <div class="summary-inline-value">{{ $shop->name }}</div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Estado interno</div>
                    <div class="summary-inline-value">
                        <span class="status-badge {{ $shopStatusClasses[$shop->status] ?? '' }}">
                            {{ $shopStatusLabels[$shop->status] ?? $shop->status }}
                        </span>
                    </div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Artículos configurados</div>
                    <div class="summary-inline-value">{{ $previewItems->count() }}</div>
                </div>

                <div class="summary-inline-card">
                    <div class="summary-inline-label">Visibles públicamente</div>
                    <div class="summary-inline-value">{{ $publicVisibleItemsCount }}</div>
                </div>
            </div>

            <p class="form-help">
                Esta es una vista interna. Muestra todos los artículos configurados en la tienda e indica cuáles serían visibles en la tienda externa.
            </p>
        </x-card>

        <x-card class="list-card">
            @if ($previewItems->count())
                <div class="table-wrap list-scroll">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Producto publicado</th>
                                <th>Descripción visible</th>
                                <th>Precio visible</th>
                                <th>Estado</th>
                                <th>Visible público</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($previewItems as $item)
                                @php
                                    $product = $item->product;
                                    $isPubliclyVisible = $item->status === ShopItem::STATUS_PUBLISHED
                                        && $item->is_visible === true
                                        && $shop->isActive()
                                        && $product !== null
                                        && $product->tenant_id === $shop->tenant_id
                                        && $product->is_active === true;

                                    $reasons = [];

                                    if (! $shop->isActive()) {
                                        $reasons[] = 'La tienda no está activa.';
                                    }

                                    if ($item->status !== ShopItem::STATUS_PUBLISHED) {
                                        $reasons[] = 'El artículo no está publicado.';
                                    }

                                    if ($item->is_visible !== true) {
                                        $reasons[] = 'El artículo no está marcado como visible.';
                                    }

                                    if (! $product) {
                                        $reasons[] = 'No tiene producto asociado.';
                                    } elseif ($product->is_active !== true) {
                                        $reasons[] = 'El producto no está activo.';
                                    } elseif ($product->tenant_id !== $shop->tenant_id) {
                                        $reasons[] = 'El producto no pertenece al tenant de la tienda.';
                                    }
                                @endphp

                                <tr>
                                    <td>
                                        {{ $item->displayName() }}

                                        @if ($product?->sku)
                                            <div class="table-cell-help">
                                                {{ $product->sku }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $item->displayDescription() ?: '—' }}
                                    </td>
                                    <td>
                                        @if ($item->displayPrice() !== null)
                                            $ {{ number_format((float) $item->displayPrice(), 2, ',', '.') }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        <span class="status-badge {{ $itemStatusClasses[$item->status] ?? '' }}">
                                            {{ $itemStatusLabels[$item->status] ?? $item->status }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge {{ $isPubliclyVisible ? 'status-badge--success' : 'status-badge--muted' }}">
                                            {{ $isPubliclyVisible ? 'Sí' : 'No' }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $isPubliclyVisible ? 'Visible en tienda externa.' : implode(' ', $reasons) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="empty-state">
                    No hay artículos configurados para esta tienda.
                </p>
            @endif
        </x-card>

        <x-card>
            <p class="form-help">
                Esta vista no requiere customer externo y no habilita compras, pagos, órdenes, documentos,
                movimientos de stock, fichas ni QR.
            </p>
        </x-card>

    </x-page>
@endsection