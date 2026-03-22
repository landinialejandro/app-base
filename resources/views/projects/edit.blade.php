{{-- FILE: resources/views/projects/edit.blade.php | V3 --}}
@extends('layouts.app')

@section('title', 'Editar proyecto')

@section('content')
    @php
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $cancelUrl = NavigationTrail::previousUrl($navigationTrail, route('projects.show', ['project' => $project]));
    @endphp

    <x-page>

        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Editar proyecto" />

        <x-card>
            <form method="POST" action="{{ route('projects.update', ['project' => $project] + $trailQuery) }}" class="form">
                @csrf
                @method('PUT')

                @include('projects._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="{{ $cancelUrl }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection
