@extends('layouts.app')

@section('title', 'Nuevo proyecto')

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Proyectos', 'url' => route('projects.index')],
            ['label' => 'Nuevo proyecto'],
        ]" />

        <x-page-header title="Nuevo proyecto" />

        <x-card>
            <form method="POST" action="{{ route('projects.store') }}" class="form">
                @csrf

                @include('projects._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ route('projects.index') }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection