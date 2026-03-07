@extends('layouts.app')

@section('title', 'Nuevo proyecto')

@section('content')
    <div class="page">

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

    </div>
@endsection