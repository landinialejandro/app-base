{{-- FILE: resources/views/assets/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Activos')

@section('content')

    @php
        use App\Support\Catalogs\AssetCatalog;
    @endphp

    <x-page class="list-page">

        <x-breadcrumb :items="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Activos']]" />

        <x-page-header title="Activos">
            <a href="{{ route('assets.create') }}" class="btn btn-primary">
                Nuevo activo
            </a>
        </x-page-header>

        <x-card class="list-card">

            <form method="GET" action="{{ route('assets.index') }}" class="form list-filters">
                <div class="list-filters-grid">
                    <div class="form-group">
                        <label for="q" class="form-label">Buscar</label>
                        <input type="text" id="q" name="q" class="form-control" value="{{ request('q') }}"
                            placeholder="Nombre o código interno">
                    </div>

                    <div class="form-group">
                        <label for="party_id" class="form-label">Contacto</label>
                        <select id="party_id" name="party_id" class="form-control">
                            <option value="">Todos</option>
                            @foreach ($parties as $party)
                                <option value="{{ $party->id }}" @selected((string) request('party_id') === (string) $party->id)>
                                    {{ $party->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="kind" class="form-label">Tipo</label>
                        <select id="kind" name="kind" class="form-control">
                            <option value="">Todos</option>
                            @foreach (AssetCatalog::kindLabels() as $value => $label)
                                <option value="{{ $value }}" @selected(request('kind') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status" class="form-label">Estado</label>
                        <select id="status" name="status" class="form-control">
                            <option value="">Todos</option>
                            @foreach (AssetCatalog::statusLabels() as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="list-filters-actions">
                    <button type="submit" class="btn btn-primary">Filtrar</button>

                    <a href="{{ route('assets.index') }}" class="btn btn-secondary">
                        Limpiar
                    </a>
                </div>
            </form>

            @if ($assets->count())
                <div class="table-wrap list-scroll">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Relación</th>
                                <th>Contacto</th>
                                <th>Código</th>
                                <th>Estado</th>
                                <th>Creado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($assets as $asset)
                                <tr>
                                    <td>{{ $asset->id }}</td>
                                    <td>
                                        <a href="{{ route('assets.show', $asset) }}">
                                            {{ $asset->name }}
                                        </a>
                                    </td>
                                    <td>{{ AssetCatalog::label($asset->kind) }}</td>
                                    <td>{{ AssetCatalog::relationshipTypeLabel($asset->relationship_type) }}</td>
                                    <td>
                                        @if ($asset->party)
                                            <a href="{{ route('parties.show', $asset->party) }}">
                                                {{ $asset->party->name }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $asset->internal_code ?? '—' }}</td>
                                    <td>
                                        <span class="status-badge {{ AssetCatalog::badgeClass($asset->status) }}">
                                            {{ AssetCatalog::statusLabel($asset->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $asset->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{ $assets->links() }}
                </div>
            @else
                <p class="mb-0">No hay activos para esta empresa.</p>
            @endif
        </x-card>

    </x-page>
@endsection
