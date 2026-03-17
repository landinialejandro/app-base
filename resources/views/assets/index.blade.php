{{-- FILE: resources/views/assets/index.blade.php | V2 --}}

@extends('layouts.app')

@section('title', 'Activos')

@section('content')

    @php
        use App\Support\Catalogs\AssetCatalog;
    @endphp

    <x-page class="list-page">

        <x-breadcrumb :items="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Activos']]" />

        <x-page-header title="Activos">
            <a href="{{ route('assets.create') }}" class="btn btn-primary">
                Nuevo activo
            </a>
        </x-page-header>

        <x-list-filters-card :action="route('assets.index')">
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
        </x-list-filters-card>

        <x-card class="list-card">
            @include('assets.partials.table', [
                'assets' => $assets,
                'showParty' => true,
                'emptyMessage' => 'No hay activos para esta empresa.',
            ])

            @if ($assets->count())
                {{ $assets->links() }}
            @endif
        </x-card>

    </x-page>
@endsection
