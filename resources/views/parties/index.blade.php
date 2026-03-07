@extends('layouts.app')

@section('title', 'Contactos')

@section('content')
    <x-page>

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Contactos'],
        ]" />

        <x-page-header title="Contactos">
            <a href="{{ route('parties.create') }}" class="btn btn-primary">
                Nuevo contacto
            </a>
        </x-page-header>

        <x-card>
            @if ($parties->isEmpty())
                <p class="mb-0">No hay contactos para esta empresa.</p>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tipo</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Activo</th>
                                <th>Creado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($parties as $party)
                                <tr>
                                    <td>{{ $party->id }}</td>
                                    <td>{{ $party->kind }}</td>
                                    <td>
                                        <a href="{{ route('parties.show', $party) }}">
                                            {{ $party->name }}
                                        </a>
                                    </td>
                                    <td>{{ $party->email }}</td>
                                    <td>{{ $party->phone }}</td>
                                    <td>{{ $party->is_active ? 'Sí' : 'No' }}</td>
                                    <td>{{ $party->created_at }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

    </x-page>
@endsection