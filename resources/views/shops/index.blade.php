{{-- FILE: resources/views/shops/index.blade.php | V2 --}}

@extends('layouts.app')

@section('title', 'Tiendas')

@section('content')
    <x-page class="list-page">

        <x-breadcrumb :items="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Tiendas']]" />

        <x-page-header title="Tiendas">
            @can('create', App\Models\Shop::class)
                <x-button-create :href="route('shops.create')" label="Nueva tienda" />
            @endcan
        </x-page-header>

        <x-list-filters-card :action="route('shops.index')" secondary-id="shops-extra-filters">
            <x-slot:primary>
                <div class="list-filters-grid">
                    <div class="form-group">
                        <label for="search" class="form-label">Nombre</label>
                        <input
                            id="search"
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ $filters['search'] ?? '' }}"
                            placeholder="Buscar tienda"
                        >
                    </div>

                    <div class="form-group">
                        <label for="status" class="form-label">Estado</label>
                        <select id="status" name="status" class="form-control">
                            <option value="">Todos</option>
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </x-slot:primary>
        </x-list-filters-card>

        <x-card class="list-card">
            @include('shops.partials.table', [
                'shops' => $shops,
                'emptyMessage' => 'No hay tiendas configuradas para esta empresa.',
            ])

            @if ($shops->count())
                {{ $shops->appends(request()->query())->links() }}
            @endif
        </x-card>

    </x-page>
@endsection