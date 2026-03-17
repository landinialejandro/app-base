{{-- FILE: resources/views/parties/index.blade.php | V6 --}}

@extends('layouts.app')

@section('title', 'Contactos')

@section('content')

    @php
        use App\Support\Catalogs\PartyCatalog;
    @endphp

    <x-page class="list-page">

        <x-breadcrumb :items="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Contactos']]" />

        <x-page-header title="Contactos">
            <a href="{{ route('parties.create') }}" class="btn btn-primary">
                Nuevo contacto
            </a>
        </x-page-header>

        <x-card class="list-card">
            <form method="GET" action="{{ route('parties.index') }}" class="form list-toolbar-form">
                <div class="list-toolbar">
                    <div class="list-toolbar-main">
                        <div class="list-toolbar-filters">
                            <div class="form-group">
                                <label for="q" class="form-label">Buscar</label>
                                <input type="text" id="q" name="q" class="form-control"
                                    value="{{ request('q') }}" placeholder="Nombre, email, teléfono, documento, CUIT o ID">
                            </div>

                            <div class="form-group">
                                <label for="kind" class="form-label">Tipo</label>
                                <select id="kind" name="kind" class="form-control">
                                    <option value="">Todos</option>
                                    @foreach (PartyCatalog::kindLabels() as $value => $label)
                                        <option value="{{ $value }}" @selected(request('kind') === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="list-toolbar-actions">
                        <a href="{{ route('parties.index') }}" class="btn btn-secondary">
                            Limpiar
                        </a>

                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </div>
                </div>
            </form>
        </x-card>

        <x-card class="list-card">
            @if ($parties->count())
                <div class="table-wrap list-scroll">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Activo</th>
                                <th>Tipo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($parties as $party)
                                <tr>
                                    <td>{{ $party->id }}</td>
                                    <td>
                                        <a href="{{ route('parties.show', $party) }}">
                                            {{ $party->name }}
                                        </a>
                                    </td>
                                    <td>{{ $party->email ?? '—' }}</td>
                                    <td>{{ $party->phone ?? '—' }}</td>
                                    <td>{{ $party->is_active ? 'Sí' : 'No' }}</td>
                                    <td>{{ $party->kind ? PartyCatalog::label($party->kind) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{ $parties->links() }}
            @else
                <p class="mb-0">No hay contactos para esta empresa.</p>
            @endif
        </x-card>

    </x-page>
@endsection
