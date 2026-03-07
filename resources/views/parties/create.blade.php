@extends('layouts.app')

@section('title', 'Nuevo contacto')

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Contactos', 'url' => route('parties.index')],
            ['label' => 'Nuevo contacto'],
        ]" />

        <x-page-header title="Nuevo contacto" />

        <x-card>
            <form method="POST" action="{{ route('parties.store') }}" class="form">
                @csrf

                @include('parties._form', ['party' => null])

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ route('parties.index') }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </x-card>

    </x-page>
@endsection