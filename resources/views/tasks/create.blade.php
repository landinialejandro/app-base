{{-- FILE: resources/views/tasks/create.blade.php --}}

@extends('layouts.app')

@section('title', 'Nueva tarea')

@section('content')
    <x-page>

        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Nueva tarea">
            @if (!empty($forcedProject))
                <a href="{{ route('projects.show', $forcedProject) }}" class="btn btn-secondary">
                    Volver al proyecto
                </a>
            @else
                <a href="{{ route('tasks.index') }}" class="btn btn-secondary">
                    Volver al listado
                </a>
            @endif
        </x-page-header>

        <x-card>
            <form method="POST" action="{{ route('tasks.store') }}">
                @csrf

                @include('tasks._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Guardar tarea
                    </button>

                    @if (!empty($forcedProject))
                        <a href="{{ route('projects.show', $forcedProject) }}" class="btn btn-secondary">
                            Cancelar
                        </a>
                    @else
                        <a href="{{ route('tasks.index') }}" class="btn btn-secondary">
                            Cancelar
                        </a>
                    @endif
                </div>
            </form>
        </x-card>

    </x-page>
@endsection