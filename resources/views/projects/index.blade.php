{{-- FILE: resources/views/projects/index.blade.php | V4 --}}

@extends('layouts.app')

@section('title', 'Proyectos')

@section('content')
    @php
        use App\Support\Catalogs\ProjectCatalog;
    @endphp

    <x-page class="list-page">

        <x-breadcrumb :items="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Proyectos']]" />

        <x-page-header title="Proyectos">
            <a href="{{ route('projects.create') }}" class="btn btn-success">
                Nuevo proyecto
            </a>
        </x-page-header>

        <x-list-filters-card :action="route('projects.index')">
            <x-slot:primary>
                <div class="list-filters-grid">
                    <div class="form-group">
                        <label for="q" class="form-label">Buscar</label>
                        <input type="text" id="q" name="q" class="form-control" value="{{ request('q') }}"
                            placeholder="Nombre, descripción o ID">
                    </div>

                    <div class="form-group">
                        <label for="status" class="form-label">Estado</label>
                        <select id="status" name="status" class="form-control">
                            <option value="">Todos</option>
                            @foreach (ProjectCatalog::statusLabels() as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </x-slot:primary>
        </x-list-filters-card>

        <x-card class="list-card">
            @include('projects.partials.table', [
                'projects' => $projects,
                'emptyMessage' => 'No hay proyectos para esta empresa.',
            ])

            @if ($projects->count())
                {{ $projects->links() }}
            @endif
        </x-card>

    </x-page>
@endsection
