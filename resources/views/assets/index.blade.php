{{-- FILE: resources/views/assets/index.blade.php --}}

@extends('layouts.app')

@section('title', 'Activos')

@section('content')

    @php
        use App\Support\Catalogs\AssetCatalog;
    @endphp

    <x-page class="list-page">

        <x-breadcrumb :items="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Activos'],
        ]" />

        <x-page-header title="Activos">
            <a href="{{ route('assets.create') }}" class="btn btn-primary">
                Nuevo activo
            </a>
        </x-page-header>

        <x-card class="list-card">
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
                                    <td>{{ $asset->party?->name ?? '—' }}</td>
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