{{-- FILE: resources/views/orders/index.blade.php | V11 --}}

@extends('layouts.app')

@section('title', 'Órdenes')

@section('content')

    @php
        use App\Support\Catalogs\OrderCatalog;
        use App\Support\Navigation\NavigationTrail;
        use App\Support\Navigation\OrderNavigationTrail;

        $trailQuery = NavigationTrail::toQuery(OrderNavigationTrail::ordersBase());
    @endphp

    <x-page class="list-page">

        <x-breadcrumb :items="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Órdenes']]" />

        <x-page-header title="Órdenes">
            @if ($canCreateOrders)
                <x-button-create :href="route('orders.create', array_merge($trailQuery, ['group' => $defaultCreateKind]))" label="Nueva orden" />
            @endif
        </x-page-header>

        <x-list-filters-card :action="route('orders.index')" secondary-id="orders-extra-filters">
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
                'showAsset' => false,
                'emptyMessage' => 'No hay órdenes cargadas.',
                'trailQuery' => $trailQuery,
            ])

            @if ($orders->count())
                {{ $orders->appends(request()->query())->links() }}
            @endif
        </x-card>

    </x-page>

    <x-dev-component-version name="orders.index" version="V11" align="right" />
@endsection