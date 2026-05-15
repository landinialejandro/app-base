{{-- FILE: resources/views/orders/index.blade.php | V15 --}}

@extends('layouts.app')

@php
    use App\Support\Catalogs\OrderCatalog;

    $isServiceUniverse = $isServiceUniverse ?? request()->routeIs('service.orders.*');
    $isProductionUniverse = $isProductionUniverse ?? request()->routeIs('production.orders.*');

    $currentGroup = match (true) {
        $isServiceUniverse => OrderCatalog::GROUP_SERVICE,
        $isProductionUniverse => OrderCatalog::GROUP_PRODUCTION,
        default => request('group'),
    };

    $isServiceContext = $isServiceUniverse || $currentGroup === OrderCatalog::GROUP_SERVICE;
    $isProductionContext = $isProductionUniverse || $currentGroup === OrderCatalog::GROUP_PRODUCTION;

    $pageTitle = match (true) {
        $isServiceContext => 'Órdenes de servicio',
        $isProductionContext => 'Órdenes de producción',
        default => 'Órdenes',
    };

    $createLabel = match (true) {
        $isServiceContext => 'Nueva orden de servicio',
        $isProductionContext => 'Nueva orden de producción',
        default => 'Nueva orden',
    };

    $emptyMessage = match (true) {
        $isServiceContext => 'No hay órdenes de servicio cargadas.',
        $isProductionContext => 'No hay órdenes de producción cargadas.',
        default => 'No hay órdenes cargadas.',
    };

    $breadcrumbItems = match (true) {
        $isServiceUniverse => [
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Servicio y mantenimiento', 'url' => route('service.index')],
            ['label' => 'Órdenes de servicio'],
        ],
        $isProductionUniverse => [
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Producción', 'url' => route('production.index')],
            ['label' => 'Órdenes de producción'],
        ],
        default => [
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => $pageTitle],
        ],
    };
@endphp

@section('title', $pageTitle)

@section('content')

    @php
        use App\Support\Navigation\NavigationTrail;
        use App\Support\Navigation\OrderNavigationTrail;

        $indexRouteName = match (true) {
            $isServiceUniverse => 'service.orders.index',
            $isProductionUniverse => 'production.orders.index',
            default => 'orders.index',
        };

        $createRouteName = match (true) {
            $isServiceUniverse => 'service.orders.create',
            $isProductionUniverse => 'production.orders.create',
            default => 'orders.create',
        };

        $showRouteName = match (true) {
            $isServiceUniverse => 'service.orders.show',
            $isProductionUniverse => 'production.orders.show',
            default => 'orders.show',
        };

        $trailBase = match (true) {
            $isServiceUniverse => OrderNavigationTrail::serviceOrdersBase(),
            $isProductionUniverse => OrderNavigationTrail::productionOrdersBase(),
            default => OrderNavigationTrail::ordersBase(),
        };

        $trailQuery = NavigationTrail::toQuery($trailBase);

        $createGroup = match (true) {
            $isServiceContext => OrderCatalog::GROUP_SERVICE,
            $isProductionContext => OrderCatalog::GROUP_PRODUCTION,
            default => $defaultCreateKind,
        };
    @endphp

    <x-page class="list-page">

        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header :title="$pageTitle">
            @if ($canCreateOrders)
                <x-button-create :href="$isServiceUniverse || $isProductionUniverse
                    ? route($createRouteName, $trailQuery)
                    : route($createRouteName, array_merge($trailQuery, ['group' => $createGroup]))" :label="$createLabel" />
            @endif
        </x-page-header>

        <x-list-filters-card :action="route($indexRouteName)" secondary-id="orders-extra-filters">
            <x-slot:primary>
                <div class="list-filters-grid">
                    <div class="form-group">
                        <label for="q" class="form-label">Buscar</label>
                        <input type="text" id="q" name="q" class="form-control" value="{{ request('q') }}"
                            placeholder="Número o contraparte">
                    </div>

                    <div class="form-group">
                        <label for="status" class="form-label">Estado</label>
                        <select id="status" name="status" class="form-control">
                            <option value="">Todos</option>
                            @foreach (OrderCatalog::statusLabels() as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </x-slot:primary>

            <x-slot:secondary>
                <div class="list-filters-grid">
                    @unless ($isServiceUniverse || $isProductionUniverse)
                        <div class="form-group">
                            <label for="group" class="form-label">Tipo</label>
                            <select id="group" name="group" class="form-control">
                                <option value="">Todos</option>
                                @foreach (OrderCatalog::groupLabels() as $value => $label)
                                    <option value="{{ $value }}" @selected(request('group') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endunless

                    <div class="form-group">
                        <label for="ordered_at" class="form-label">Fecha</label>
                        <input type="date" id="ordered_at" name="ordered_at" class="form-control"
                            value="{{ request('ordered_at') }}">
                    </div>
                </div>
            </x-slot:secondary>
        </x-list-filters-card>

        <x-card class="list-card">
            @include('orders.partials.table', [
                'orders' => $orders,
                'showCounterparty' => true,
                'showAsset' => true,
                'emptyMessage' => $emptyMessage,
                'trailQuery' => $trailQuery,
                'showRouteName' => $showRouteName,
            ])

            @if ($orders->count())
                {{ $orders->appends(request()->query())->links() }}
            @endif
        </x-card>

    </x-page>

    <x-dev-component-version name="orders.index" version="V15" align="right" />
@endsection