{{-- FILE: resources/views/parties/show.blade.php | V10 --}}

@extends('layouts.app')

@section('title', 'Detalle del contacto')

@section('content')
    @php
        use App\Support\Catalogs\PartyCatalog;
        use App\Support\Navigation\NavigationTrail;

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $backUrl = NavigationTrail::previousUrl($navigationTrail, route('parties.index'));

        $assets = $assets ?? collect();
        $orders = $orders ?? collect();
        $documents = $documents ?? collect();

        $supportsAssetsModule = $supportsAssetsModule ?? false;
        $supportsOrdersModule = $supportsOrdersModule ?? false;
        $supportsDocumentsModule = $supportsDocumentsModule ?? false;

        $tabs = collect([
            $supportsAssetsModule ? 'assets' : null,
            $supportsOrdersModule ? 'orders' : null,
            $supportsDocumentsModule ? 'documents' : null,
        ])
            ->filter()
            ->values();

        $defaultTab = $tabs->first();
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header title="Detalle del contacto">
            @can('update', $party)
                <a href="{{ route('parties.edit', ['party' => $party] + $trailQuery) }}" class="btn btn-primary">
                    <x-icons.pencil />
                    <span>Editar</span>
                </a>
            @endcan

            @can('delete', $party)
                <form method="POST" action="{{ route('parties.destroy', ['party' => $party] + $trailQuery) }}" class="inline-form"
                    data-action="app-confirm-submit" data-confirm-message="¿Eliminar contacto?">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger">
                        <x-icons.trash />
                        <span>Eliminar</span>
                    </button>
                </form>
            @endcan

            <a href="{{ $backUrl }}" class="btn btn-secondary">
                <x-icons.chevron-left />
                <span>Volver</span>
            </a>
        </x-page-header>

        <x-show-summary details-id="party-detail-panel">
            <x-show-summary-item label="Nombre">
                {{ $party->name }}
            </x-show-summary-item>

            <x-show-summary-item label="Teléfono">
                {{ $party->phone ?: '—' }}
            </x-show-summary-item>

            <x-show-summary-item label="Email">
                {{ $party->email ?: '—' }}
            </x-show-summary-item>

            <x-slot:details>
                <x-show-summary-item-detail-block label="Tipo">
                    {{ PartyCatalog::label($party->kind) }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Nombre visible">
                    {{ $party->display_name ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Tipo documento">
                    {{ $party->document_type ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Número documento">
                    {{ $party->document_number ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="CUIT / Tax ID">
                    {{ $party->tax_id ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Activo">
                    <span class="status-badge {{ $party->is_active ? 'status-badge--done' : 'status-badge--cancelled' }}">
                        {{ $party->is_active ? 'Sí' : 'No' }}
                    </span>
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Dirección" full>
                    {{ $party->address ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Creado">
                    {{ $party->created_at?->format('d/m/Y H:i') ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Actualizado">
                    {{ $party->updated_at?->format('d/m/Y H:i') ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Notas" full>
                    {{ $party->notes ?: '—' }}
                </x-show-summary-item-detail-block>
            </x-slot:details>
        </x-show-summary>

        @if ($defaultTab)
            <div class="tabs" data-tabs>
                <x-tab-toolbar label="Relaciones del contacto">
                    <x-slot:tabs>
                        <x-horizontal-scroll label="Relaciones del contacto">
                            @if ($supportsAssetsModule)
                                <button type="button" class="tabs-link {{ $defaultTab === 'assets' ? 'is-active' : '' }}"
                                    data-tab-link="assets" role="tab"
                                    aria-selected="{{ $defaultTab === 'assets' ? 'true' : 'false' }}">
                                    Activos
                                    @if ($assets->count())
                                        ({{ $assets->count() }})
                                    @endif
                                </button>
                            @endif

                            @if ($supportsOrdersModule)
                                <button type="button" class="tabs-link {{ $defaultTab === 'orders' ? 'is-active' : '' }}"
                                    data-tab-link="orders" role="tab"
                                    aria-selected="{{ $defaultTab === 'orders' ? 'true' : 'false' }}">
                                    Órdenes
                                    @if ($orders->count())
                                        ({{ $orders->count() }})
                                    @endif
                                </button>
                            @endif

                            @if ($supportsDocumentsModule)
                                <button type="button"
                                    class="tabs-link {{ $defaultTab === 'documents' ? 'is-active' : '' }}"
                                    data-tab-link="documents" role="tab"
                                    aria-selected="{{ $defaultTab === 'documents' ? 'true' : 'false' }}">
                                    Documentos
                                    @if ($documents->count())
                                        ({{ $documents->count() }})
                                    @endif
                                </button>
                            @endif
                        </x-horizontal-scroll>
                    </x-slot:tabs>
                </x-tab-toolbar>

                @if ($supportsAssetsModule)
                    <section class="tab-panel {{ $defaultTab === 'assets' ? 'is-active' : '' }}" data-tab-panel="assets"
                        @if ($defaultTab !== 'assets') hidden @endif>
                        <div class="tab-panel-stack">
                            <x-card>
                                @include('assets.partials.table', [
                                    'assets' => $assets,
                                    'showParty' => false,
                                    'emptyMessage' => 'Este contacto no tiene activos vinculados.',
                                    'trailQuery' => $trailQuery,
                                ])
                            </x-card>
                        </div>
                    </section>
                @endif

                @if ($supportsOrdersModule)
                    <section class="tab-panel {{ $defaultTab === 'orders' ? 'is-active' : '' }}" data-tab-panel="orders"
                        @if ($defaultTab !== 'orders') hidden @endif>
                        <div class="tab-panel-stack">
                            <x-card>
                                @if ($orders->count())
                                    <div class="table-wrap">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Número</th>
                                                    <th>Tipo</th>
                                                    <th>Estado</th>
                                                    <th>Fecha</th>
                                                    <th>Activo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($orders as $order)
                                                    @php
                                                        $canViewOrder =
                                                            auth()->user() && auth()->user()->can('view', $order);
                                                        $canViewAsset =
                                                            $order->asset &&
                                                            auth()->user() &&
                                                            auth()->user()->can('view', $order->asset);
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            @if ($canViewOrder)
                                                                <a
                                                                    href="{{ route('orders.show', ['order' => $order] + $trailQuery) }}">
                                                                    {{ $order->number ?: 'Orden #' . $order->id }}
                                                                </a>
                                                            @else
                                                                {{ $order->number ?: 'Orden #' . $order->id }}
                                                            @endif
                                                        </td>
                                                        <td>{{ \App\Support\Catalogs\OrderCatalog::kindLabel($order->kind) }}
                                                        </td>
                                                        <td>{{ \App\Support\Catalogs\OrderCatalog::statusLabel($order->status) }}
                                                        </td>
                                                        <td>{{ $order->ordered_at?->format('d/m/Y') ?: '—' }}</td>
                                                        <td>
                                                            @if ($canViewAsset)
                                                                <a
                                                                    href="{{ route('assets.show', ['asset' => $order->asset] + $trailQuery) }}">
                                                                    {{ $order->asset->name }}
                                                                </a>
                                                            @elseif ($order->asset)
                                                                {{ $order->asset->name }}
                                                            @else
                                                                —
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="mb-0">Este contacto no tiene órdenes vinculadas.</p>
                                @endif
                            </x-card>
                        </div>
                    </section>
                @endif

                @if ($supportsDocumentsModule)
                    <section class="tab-panel {{ $defaultTab === 'documents' ? 'is-active' : '' }}"
                        data-tab-panel="documents" @if ($defaultTab !== 'documents') hidden @endif>
                        <div class="tab-panel-stack">
                            <x-card>
                                @if ($documents->count())
                                    <div class="table-wrap">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Número</th>
                                                    <th>Tipo</th>
                                                    <th>Estado</th>
                                                    <th>Fecha</th>
                                                    <th>Activo</th>
                                                    <th>Orden</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($documents as $document)
                                                    @php
                                                        $canViewDocument =
                                                            auth()->user() && auth()->user()->can('view', $document);
                                                        $canViewAsset =
                                                            $document->asset &&
                                                            auth()->user() &&
                                                            auth()->user()->can('view', $document->asset);
                                                        $canViewOrder =
                                                            $document->order &&
                                                            auth()->user() &&
                                                            auth()->user()->can('view', $document->order);
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            @if ($canViewDocument)
                                                                <a
                                                                    href="{{ route('documents.show', ['document' => $document] + $trailQuery) }}">
                                                                    {{ $document->number ?: 'Documento #' . $document->id }}
                                                                </a>
                                                            @else
                                                                {{ $document->number ?: 'Documento #' . $document->id }}
                                                            @endif
                                                        </td>
                                                        <td>{{ \App\Support\Catalogs\DocumentCatalog::kindLabel($document->kind) }}
                                                        </td>
                                                        <td>{{ \App\Support\Catalogs\DocumentCatalog::statusLabel($document->status) }}
                                                        </td>
                                                        <td>{{ $document->issued_at?->format('d/m/Y') ?: '—' }}</td>
                                                        <td>
                                                            @if ($canViewAsset)
                                                                <a
                                                                    href="{{ route('assets.show', ['asset' => $document->asset] + $trailQuery) }}">
                                                                    {{ $document->asset->name }}
                                                                </a>
                                                            @elseif ($document->asset)
                                                                {{ $document->asset->name }}
                                                            @else
                                                                —
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if ($canViewOrder)
                                                                <a
                                                                    href="{{ route('orders.show', ['order' => $document->order] + $trailQuery) }}">
                                                                    {{ $document->order->number ?: 'Orden #' . $document->order->id }}
                                                                </a>
                                                            @elseif ($document->order)
                                                                {{ $document->order->number ?: 'Orden #' . $document->order->id }}
                                                            @else
                                                                —
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <p class="mb-0">Este contacto no tiene documentos vinculados.</p>
                                @endif
                            </x-card>
                        </div>
                    </section>
                @endif
            </div>
        @endif

    </x-page>
@endsection
