@extends('layouts.app')

@section('title', 'Editar contacto')

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Contactos', 'url' => route('parties.index')],
            ['label' => $party->name, 'url' => route('parties.show', $party)],
            ['label' => 'Editar'],
        ]" />

        <x-page-header title="Editar contacto" />

        <x-card>
            <form method="POST" action="{{ route('parties.update', $party) }}" class="form">
                @csrf
                @method('PUT')

                @include('parties._form', ['party' => $party])

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="{{ route('parties.show', $party) }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection