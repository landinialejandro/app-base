{{-- FILE: resources/views/orders/index.blade.php | V14 --}}

@extends('layouts.app')

@php
    use App\Support\Catalogs\OrderCatalog;

    $isServiceUniverse = $isServiceUniverse ?? request()->routeIs('service.orders.*');
    $currentGroup = $isServiceUniverse ? OrderCatalog::GROUP_SERVICE : request('group');
    $isServiceContext = $isServiceUniverse || $currentGroup === OrderCatalog::GROUP_SERVICE;

    $pageTitle = $isServiceContext ? 'Órdenes de servicio' : 'Órdenes';
    $createLabel = $isServiceContext ? 'Nueva orden de servicio' : 'Nueva orden';
    $emptyMessage = $isServiceContext ? 'No hay órdenes de servicio cargadas.' : 'No hay órdenes cargadas.';

    $breadcrumbItems = $isServiceUniverse
        ? [
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Servicio y mantenimiento', 'url' => route('service.index')],
            ['label' => 'Órdenes de servicio'],
        ]
        : [['label' => 'Inicio', 'url' => route('dashboard')], ['label' => $pageTitle]];
@endphp

@section('title', $pageTitle)

@section('content')

    @php
        use App\Support\Navigation\NavigationTrail;
        use App\Support\Navigation\OrderNavigationTrail;

        $indexRouteName = $isServiceUniverse ? 'service.orders.index' : 'orders.index';
        $createRouteName = $isServiceUniverse ? 'service.orders.create' : 'orders.create';
        $showRouteName = $isServiceUniverse ? 'service.orders.show' : 'orders.show';

        $trailBase = $isServiceUniverse
            ? OrderNavigationTrail::serviceOrdersBase()
            : OrderNavigationTrail::ordersBase();

        $trailQuery = NavigationTrail::toQuery($trailBase);
        $createGroup = $isServiceContext ? OrderCatalog::GROUP_SERVICE : $defaultCreateKind;
    @endphp

    <x-page class="list-page">

        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header :title="$pageTitle">
            @if ($canCreateOrders)
                <x-button-create :href="$isServiceUniverse
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
                    @unless ($isServiceUniverse)
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

    <x-dev-component-version name="orders.index" version="V14" align="right" />
@endsection
