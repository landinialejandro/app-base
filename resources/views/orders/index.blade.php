{{-- FILE: resources/views/orders/index.blade.php | V3 --}}

@extends('layouts.app')

@section('title', 'Órdenes')

@section('content')

    @php
        use App\Support\Catalogs\OrderCatalog;
    @endphp

    <x-page class="list-page">

        <x-breadcrumb :items="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Órdenes']]" />

        <x-page-header title="Órdenes">
            <a href="{{ route('orders.create') }}" class="btn btn-primary">
                Nueva orden
            </a>
        </x-page-header>

        <x-list-filters-card :action="route('orders.index')" secondary-id="orders-extra-filters">
            <x-slot:primary>
                <div class="list-filters-grid">
                    <div class="form-group">
                        <label for="q" class="form-label">Buscar</label>
                        <input type="text" id="q" name="q" class="form-control" value="{{ request('q') }}"
                            placeholder="Número de orden">
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
                        <label for="party_id" class="form-label">Contacto</label>
                        <select id="party_id" name="party_id" class="form-control">
                            <option value="">Todos</option>
                            @foreach ($parties as $party)
                                <option value="{{ $party->id }}" @selected((string) request('party_id') === (string) $party->id)>
                                    {{ $party->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="asset_id" class="form-label">Activo</label>
                        <select id="asset_id" name="asset_id" class="form-control">
                            <option value="">Todos</option>
                            @foreach ($assets as $asset)
                                <option value="{{ $asset->id }}" @selected((string) request('asset_id') === (string) $asset->id)>
                                    {{ $asset->name }}
                                    @if ($asset->internal_code)
                                        — {{ $asset->internal_code }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="kind" class="form-label">Tipo</label>
                        <select id="kind" name="kind" class="form-control">
                            <option value="">Todos</option>
                            @foreach (OrderCatalog::kindLabels() as $value => $label)
                                <option value="{{ $value }}" @selected(request('kind') === $value)>
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
                'showParty' => true,
                'showAsset' => true,
                'emptyMessage' => 'No hay órdenes cargadas.',
            ])

            @if ($orders->count())
                {{ $orders->links() }}
            @endif
        </x-card>

    </x-page>

@endsection
