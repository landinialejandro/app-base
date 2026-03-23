{{-- FILE: resources/views/documents/index.blade.php | V5 --}}

@extends('layouts.app')

@section('title', 'Documentos')

@section('content')

    @php
        use App\Support\Catalogs\DocumentCatalog;
        use App\Support\Navigation\DocumentNavigationTrail;
        use App\Support\Navigation\NavigationTrail;

        $trailQuery = NavigationTrail::toQuery(DocumentNavigationTrail::documentsBase());
    @endphp

    <x-page class="list-page">

        <x-breadcrumb :items="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Documentos']]" />

        <x-page-header title="Documentos">
            <a href="{{ route('documents.create') }}" class="btn btn-primary">
                Nuevo documento
            </a>
        </x-page-header>

        <x-list-filters-card :action="route('documents.index')" secondary-id="documents-extra-filters">
            <x-slot:primary>
                <div class="list-filters-grid">
                    <div class="form-group">
                        <label for="q" class="form-label">Buscar</label>
                        <input type="text" id="q" name="q" class="form-control" value="{{ request('q') }}"
                            placeholder="Número de documento">
                    </div>

                    <div class="form-group">
                        <label for="status" class="form-label">Estado</label>
                        <select id="status" name="status" class="form-control">
                            <option value="">Todos</option>
                            @foreach (DocumentCatalog::statusLabels() as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </x-slot:primary>

            <x-slot:secondary>
                <div class="list-filters-grid">
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
                        <label for="asset_id" class="form-label">Activo</label>
                        <select id="asset_id" name="asset_id" class="form-control">
                            <option value="">Todos</option>
                            @foreach ($assets as $asset)
                                <option value="{{ $asset->id }}" @selected((string) request('asset_id') === (string) $asset->id)>
                                    {{ $asset->name }}
                                    @if ($asset->internal_code)
                                        — {{ $asset->internal_code }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="order_id" class="form-label">Orden</label>
                        <select id="order_id" name="order_id" class="form-control">
                            <option value="">Todas</option>
                            @foreach ($orders as $order)
                                <option value="{{ $order->id }}" @selected((string) request('order_id') === (string) $order->id)>
                                    {{ $order->number ?: 'Sin número' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="kind" class="form-label">Tipo</label>
                        <select id="kind" name="kind" class="form-control">
                            <option value="">Todos</option>
                            @foreach (DocumentCatalog::kindLabels() as $value => $label)
                                <option value="{{ $value }}" @selected(request('kind') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="issued_at" class="form-label">Fecha</label>
                        <input type="date" id="issued_at" name="issued_at" class="form-control"
                            value="{{ request('issued_at') }}">
                    </div>
                </div>
            </x-slot:secondary>
        </x-list-filters-card>

        <x-card class="list-card">
            @include('documents.partials.table', [
                'documents' => $documents,
                'showParty' => true,
                'showAsset' => true,
                'showOrder' => true,
                'emptyMessage' => 'No hay documentos cargados.',
                'trailQuery' => $trailQuery,
            ])

            @if ($documents->count())
                {{ $documents->appends(request()->query())->links() }}
            @endif
        </x-card>

    </x-page>

@endsection
