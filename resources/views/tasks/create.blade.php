{{-- FILE: resources/views/tasks/create.blade.php | V4 --}}

@extends('layouts.app')

@section('title', 'Nueva tarea')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $cancelUrl = NavigationTrail::previousUrl(
            $navigationTrail,
            !empty($forcedProject) ? route('projects.show', $forcedProject) : route('tasks.index'),
        );
    @endphp

    <x-page>

        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Nueva tarea" />

        <x-card>
            <form method="POST" action="{{ route('tasks.store', $trailQuery) }}" class="form">
                @csrf

                @include('tasks._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Guardar tarea
                    </button>

                    <a href="{{ $cancelUrl }}" class="btn btn-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection
