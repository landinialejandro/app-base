{{-- FILE: resources/views/self-service-sales/shop.blade.php | V13 --}}

@php
    $publicPage = true;
@endphp

@extends('layouts.app')

@section('title', 'Tienda')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/modules/self-service-sales-shop.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/self-service-sales-shop.js') }}"></script>
@endpush

@section('content')
    @php
        $activeShop = $activeShop ?? null;
        $shopItems = $shopItems ?? collect();
        $shopCatalogStatus = $shopCatalogStatus ?? 'without_active_shop';
        $cartExperienceEnabled = $cartExperienceEnabled ?? false;
    @endphp

    <x-page>
        <div
            class="shop-public-shell"
            data-shop-app
            data-cart-experience-enabled="{{ $cartExperienceEnabled ? 'true' : 'false' }}"
            data-operation-pending-message="Función no implementada todavía: operación comercial pendiente."
        >
            @include('self-service-sales.partials.shop-header', [
                'tenant' => $tenant,
                'activeShop' => $activeShop,
                'externalCustomer' => $externalCustomer,
            ])

            <main class="shop-public-main">
                @include('self-service-sales.partials.customer-status', [
                    'tenant' => $tenant,
                    'externalCustomer' => $externalCustomer,
                ])

                @include('self-service-sales.partials.product-masonry', [
                    'tenant' => $tenant,
                    'activeShop' => $activeShop,
                    'shopItems' => $shopItems,
                    'shopCatalogStatus' => $shopCatalogStatus,
                ])
            </main>

            @include('self-service-sales.partials.product-detail-modal')
            @include('self-service-sales.partials.cart-drawer')
            @include('self-service-sales.partials.checkout-panel')
            @include('self-service-sales.partials.not-implemented-modal')
            @include('self-service-sales.partials.bottom-nav', [
                'externalCustomer' => $externalCustomer,
            ])
        </div>
    </x-page>
@stop
