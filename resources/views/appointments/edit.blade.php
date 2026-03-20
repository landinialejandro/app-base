@extends('layouts.app')

@section('title', 'Editar turno')

@section('content')
    <x-page>
        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Turnos', 'url' => route('appointments.index')],
            [
                'label' => $appointment->title ?: 'Turno #' . $appointment->id,
                'url' => route('appointments.show', $appointment),
            ],
            ['label' => 'Editar'],
        ]" />

        <x-page-header title="Editar turno">
            <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-secondary">
                Volver al detalle
            </a>
        </x-page-header>

        <x-card>
            <form method="POST" action="{{ route('appointments.update', $appointment) }}">
                @csrf
                @method('PUT')

                @include('appointments._form')

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Guardar cambios
                    </button>

                    <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </x-card>
    </x-page>
@endsection
