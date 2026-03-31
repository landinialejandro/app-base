{{-- FILE: resources/views/parties/index.blade.php | V10 --}}

@extends('layouts.app')

@section('title', 'Contactos')

@section('content')

    @php
        use App\Support\Catalogs\PartyCatalog;
        use App\Support\Navigation\NavigationTrail;

        $trailQuery = NavigationTrail::toQuery(
            NavigationTrail::base([
                NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
                NavigationTrail::makeNode('parties.index', null, 'Contactos', route('parties.index')),
            ]),
        );
    @endphp

    <x-page class="list-page">

        <x-breadcrumb :items="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Contactos']]" />

        <x-page-header title="Contactos">
            @can('create', App\Models\Party::class)
                <a href="{{ route('parties.create', $trailQuery) }}" class="btn btn-success">
                    Nuevo contacto
                </a>
            @endcan
        </x-page-header>

        <x-list-filters-card :action="route('parties.index')">
            <x-slot:primary>
                <div class="list-filters-grid">
                    <div class="form-group">
                        <label for="q" class="form-label">Buscar</label>
                        <input type="text" id="q" name="q" class="form-control" value="{{ request('q') }}"
                            placeholder="Nombre, email, teléfono, documento, CUIT o ID">
                    </div>

                    <div class="form-group">
                        <label for="kind" class="form-label">Tipo</label>
                        <select id="kind" name="kind" class="form-control">
                            <option value="">Todos</option>
                            @foreach (PartyCatalog::kindLabels() as $value => $label)
                                <option value="{{ $value }}" @selected(request('kind') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </x-slot:primary>
        </x-list-filters-card>

        <x-card class="list-card">
            @include('parties.partials.table', [
                'parties' => $parties,
                'emptyMessage' => 'No hay contactos para esta empresa.',
                'trailQuery' => $trailQuery,
            ])

            @if ($parties->count())
                {{ $parties->links() }}
            @endif
        </x-card>

    </x-page>
@endsection
