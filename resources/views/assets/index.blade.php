{{-- FILE: resources/views/assets/index.blade.php | V6 --}}

@extends('layouts.app')

@section('title', 'Activos')

@section('content')

    @php
        use App\Support\Catalogs\AssetCatalog;
        use App\Support\Navigation\AssetNavigationTrail;
        use App\Support\Navigation\NavigationTrail;

        $trailQuery = NavigationTrail::toQuery(AssetNavigationTrail::assetsBase());
    @endphp

    <x-page class="list-page">

        <x-breadcrumb :items="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Activos']]" />

        <x-page-header title="Activos">
            @can('create', App\Models\Asset::class)
                <a href="{{ route('assets.create', $trailQuery) }}" class="btn btn-success">
                    Nuevo activo
                </a>
            @endcan
        </x-page-header>

        <x-list-filters-card :action="route('assets.index')" secondary-id="assets-extra-filters">
            <x-slot:primary>
                <div class="list-filters-grid">
                    <div class="form-group">
                        <label for="q" class="form-label">Buscar</label>
                        <input type="text" id="q" name="q" class="form-control" value="{{ request('q') }}"
                            placeholder="Nombre o código interno">
                    </div>

                    <div class="form-group">
                        <label for="kind" class="form-label">Tipo</label>
                        <select id="kind" name="kind" class="form-control">
                            <option value="">Todos</option>
                            @foreach (AssetCatalog::kindLabels() as $value => $label)
                                <option value="{{ $value }}" @selected(request('kind') === $value)>
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
                        <label for="status" class="form-label">Estado</label>
                        <select id="status" name="status" class="form-control">
                            <option value="">Todos</option>
                            @foreach (AssetCatalog::statusLabels() as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </x-slot:secondary>
        </x-list-filters-card>

        <x-card class="list-card">
            @include('assets.partials.table', [
                'assets' => $assets,
                'showParty' => true,
                'emptyMessage' => 'No hay activos para esta empresa.',
                'trailQuery' => $trailQuery,
            ])

            @if ($assets->count())
                {{ $assets->appends(request()->query())->links() }}
            @endif
        </x-card>

    </x-page>
@endsection
