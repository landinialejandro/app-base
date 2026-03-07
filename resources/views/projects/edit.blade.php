@extends('layouts.app')

@section('title', 'Editar proyecto')

@section('content')
    <div class="page">

        <x-page-header title="Editar proyecto" />

        <x-card>
            <form method="POST" action="{{ route('projects.update', $project) }}" class="form">
                @csrf
                @method('PUT')

                @include('projects._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>

        </x-card>
    </div>
@endsection