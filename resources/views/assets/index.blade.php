{{-- FILE: resources/views/assets/index.blade.php --}}

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

        <x-card class="list-card">
            <form method="GET" action="{{ route('assets.index') }}" class="form list-toolbar-form">
                <div class="list-toolbar">
                    <div class="list-toolbar-main">
                        <div class="list-toolbar-filters">
                            <div class="form-group">
                                <label for="q" class="form-label">Buscar</label>
                                <input type="text" id="q" name="q" class="form-control"
                                    value="{{ request('q') }}" placeholder="Nombre o código interno">
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
                    </div>

                    <div class="list-toolbar-actions">
                        <a href="{{ route('assets.index') }}" class="btn btn-secondary">
                            Limpiar
                        </a>

                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </div>
                </div>
            </form>
        </x-card>

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
