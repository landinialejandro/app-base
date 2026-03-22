{{-- FILE: resources/views/tasks/edit.blade.php | V3 --}}

@extends('layouts.app')

@section('title', 'Editar tarea')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $cancelUrl = NavigationTrail::previousUrl(
            $navigationTrail,
            $task->project ? route('projects.show', $task->project) : route('tasks.show', $task),
        );
    @endphp

    <x-page>

        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Editar tarea">
            <a href="{{ $cancelUrl }}" class="btn btn-secondary">
                Cancelar
            </a>
        </x-page-header>

        <x-card>
            <form method="POST" action="{{ route('tasks.update', ['task' => $task] + $trailQuery) }}">
                @csrf
                @method('PUT')

                @include('tasks._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Guardar cambios
                    </button>

                    <a href="{{ $cancelUrl }}" class="btn btn-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection
