{{-- FILE: resources/views/parties/index.blade.php | V3 --}}

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

            <form method="GET" action="{{ route('parties.index') }}" class="form list-filters">
                <div class="list-filters-grid">
                    <div class="form-group">
                        <label for="q" class="form-label">Buscar</label>
                        <input type="text" id="q" name="q" class="form-control" value="{{ request('q') }}"
                            placeholder="Nombre, email, teléfono, documento, CUIT o ID">
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

                    <div class="form-group">
                        <label for="is_active" class="form-label">Activo</label>
                        <select id="is_active" name="is_active" class="form-control">
                            <option value="">Todos</option>
                            <option value="1" @selected(request('is_active') === '1')>Sí</option>
                            <option value="0" @selected(request('is_active') === '0')>No</option>
                        </select>
                    </div>
                </div>

                <div class="list-filters-actions">
                    <button type="submit" class="btn btn-primary">Filtrar</button>

                    <a href="{{ route('parties.index') }}" class="btn btn-secondary">
                        Limpiar
                    </a>
                </div>
            </form>

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

                    {{ $parties->links() }}
                </div>
            @else
                <p class="mb-0">No hay contactos para esta empresa.</p>
            @endif
        </x-card>

    </x-page>
@endsection
