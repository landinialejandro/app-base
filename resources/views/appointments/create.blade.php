@extends('layouts.app')

@section('title', 'Nuevo turno')

@section('content')
    <x-page>
        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Turnos', 'url' => route('appointments.index')],
            ['label' => 'Nuevo turno'],
        ]" />

        <x-page-header title="Nuevo turno">
            <a href="{{ route('appointments.index') }}" class="btn btn-secondary">
                Volver al listado
            </a>
        </x-page-header>

        <x-card>
            <form method="POST" action="{{ route('appointments.store') }}">
                @csrf

                @include('appointments._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Guardar turno
                    </button>

                    <a href="{{ route('appointments.index') }}" class="btn btn-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </x-card>
    </x-page>
@endsection
