{{-- FILE: resources/views/parties/index.blade.php | V2 --}}

@extends('layouts.app')

@section('title', 'Contactos')

@section('content')

    @php
        use App\Support\Catalogs\PartyCatalog;
    @endphp

    <x-page class="list-page">

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Contactos'],
        ]" />

        <x-page-header title="Contactos">
            <a href="{{ route('parties.create') }}" class="btn btn-primary">
                Nuevo contacto
            </a>
        </x-page-header>

        <x-card class="list-card">
            @if ($parties->count())
                <div class="table-wrap list-scroll">
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
                                    <td>{{ $party->kind ? PartyCatalog::label($party->kind) : '—' }}</td>
                                    <td>
                                        <a href="{{ route('parties.show', $party) }}">
                                            {{ $party->name }}
                                        </a>
                                    </td>
                                    <td>{{ $party->email ?? '—' }}</td>
                                    <td>{{ $party->phone ?? '—' }}</td>
                                    <td>{{ $party->is_active ? 'Sí' : 'No' }}</td>
                                    <td>{{ $party->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="mb-0">No hay contactos para esta empresa.</p>
            @endif
        </x-card>

    </x-page>
@endsection