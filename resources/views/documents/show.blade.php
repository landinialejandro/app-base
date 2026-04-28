{{-- FILE: resources/views/documents/show.blade.php | V18 --}}

@extends('layouts.app')

@section('title', 'Detalle del documento')

@section('content')
    @php
        use App\Support\Modules\ModuleSurfaceRegistry;
        use App\Support\Navigation\NavigationTrail;
        use App\Support\Catalogs\DocumentCatalog;
        use App\Support\Documents\DocumentSurfaceService;

        $items = $document->items->sortBy('position')->values();

        $pageTitle = match ($document->kind) {
            DocumentCatalog::KIND_QUOTE => 'Detalle del presupuesto',
            DocumentCatalog::KIND_INVOICE => 'Detalle de la factura',
            DocumentCatalog::KIND_DELIVERY_NOTE => 'Detalle del remito',
            DocumentCatalog::KIND_WORK_ORDER => 'Detalle de la orden de trabajo',
            DocumentCatalog::KIND_RECEIPT => 'Detalle del recibo',
            DocumentCatalog::KIND_CREDIT_NOTE => 'Detalle del recibo',
            default => 'Detalle del documento',
        };

        $detailsId = 'document-more-detail';
        $tabsLabel = 'Secciones del documento';

        $breadcrumbItems = NavigationTrail::toBreadcrumbItems($navigationTrail);
        $trailQuery = NavigationTrail::toQuery($navigationTrail);
        $backUrl = NavigationTrail::previousUrl($navigationTrail, route('documents.index'));

        $hostPack = app(DocumentSurfaceService::class)->hostPack('documents.show', $document, [
            'trailQuery' => $trailQuery,
        ]);

        $embedded = collect(app(ModuleSurfaceRegistry::class)->embeddedFor('documents.show', $hostPack))->values();
        $linked = collect(app(ModuleSurfaceRegistry::class)->linkedFor('documents.show', $hostPack))->values();

        $headerActions = $linked->where('slot', 'header_actions')->values();
        $summaryItems = $linked->where('slot', 'summary_items')->values();
        $detailItems = $linked->where('slot', 'detail_items')->values();

        $hostTabItems = collect([
            [
                'type' => 'embedded',
                'slot' => 'tab_panels',
                'key' => 'items',
                'label' => 'Ítems',
                'priority' => 10,
                'count' => $items->count(),
                'view' => 'documents.items.partials.embedded',
                'data' => [
                    'document' => $document,
                    'items' => $items,
                    'trailQuery' => $trailQuery,
                ],
            ],
        ]);

        $surfaceTabItems = $embedded->where(fn($item) => ($item['slot'] ?? null) === 'tab_panels')->values();

        $tabItems = $hostTabItems->concat($surfaceTabItems)->sortBy(fn($item) => $item['priority'] ?? 999)->values();
        $requestedTab = (string) request()->query('return_tab', '');
        $availableTabKeys = $tabItems->pluck('key')->filter()->values()->all();

        $activeTab = in_array($requestedTab, $availableTabKeys, true)
            ? $requestedTab
            : $tabItems->first()['key'] ?? null;
    @endphp

    <x-page>
        <x-breadcrumb :items="$breadcrumbItems" />

        <x-page-header :title="$pageTitle">
            @foreach ($headerActions as $surface)
                @include($surface['view'], $surface['data'] ?? [])
            @endforeach

            @can('update', $document)
                <x-button-edit :href="route('documents.edit', ['document' => $document] + $trailQuery)" />
            @endcan

            @can('delete', $document)
                <x-button-delete :action="route('documents.destroy', ['document' => $document] + $trailQuery)" :message="$items->count()
                    ? 'Este documento tiene ítems cargados. Si lo eliminas, también se eliminarán sus ítems. ¿Deseas continuar?'
                    : '¿Deseas eliminar este documento?'" />
            @endcan

            <x-button-secondary :href="route('documents.print', ['document' => $document])" target="_blank">
                Imprimir
            </x-button-secondary>

            <x-button-secondary :href="route('documents.pdf', ['document' => $document])">
                Descargar PDF
            </x-button-secondary>

            <x-button-back :href="$backUrl" />
        </x-page-header>

        <x-show-summary details-id="{{ $detailsId }}">
            @foreach ($summaryItems as $surface)
                <x-show-summary-item :label="$surface['label'] ?? 'Relacionado'">
                    @include($surface['view'], $surface['data'] ?? [])
                </x-show-summary-item>
            @endforeach

            <x-show-summary-item label="Fecha">
                {{ $document->issued_at?->format('d/m/Y') ?: '—' }}
            </x-show-summary-item>

            <x-show-summary-item label="Número">
                {{ $document->number ?: 'Sin número' }}
            </x-show-summary-item>
            <x-show-summary-item label="Estado">
                <x-status-badge :catalog="DocumentCatalog::class" :status="$document->status" />

                @can('changeStatus', $document)
                    <x-status-transition-actions :record="$document" :catalog="DocumentCatalog::class" route-name="documents.status.update"
                        route-param="document" :trail-query="$trailQuery" resource-label="el documento" approved-label="Aprobado"
                        closed-label="Cerrado" cancelled-label="Cancelado" />
                @endcan
            </x-show-summary-item>

            <x-slot:details>
                @foreach ($detailItems as $detailItem)
                    <x-show-summary-item-detail-block :label="$detailItem['label'] ?? 'Relacionado'">
                        @include($detailItem['view'], $detailItem['data'] ?? [])
                    </x-show-summary-item-detail-block>
                @endforeach

                <x-show-summary-item-detail-block label="Tipo">
                    {{ DocumentCatalog::label($document->kind) }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Fecha de vencimiento">
                    {{ $document->due_at?->format('d/m/Y') ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Moneda">
                    {{ $document->currency_code ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Notas" full>
                    {{ $document->notes ?: '—' }}
                </x-show-summary-item-detail-block>

                <x-show-summary-item-detail-block label="Trazabilidad" full>
                    Creado: {{ $document->created_at?->format('d/m/Y H:i') ?? '—' }}<br>
                    Actualizado: {{ $document->updated_at?->format('d/m/Y H:i') ?? '—' }}
                </x-show-summary-item-detail-block>
            </x-slot:details>
        </x-show-summary>

        @if ($tabItems->isNotEmpty())
            <div class="tabs" data-tabs>
                <x-tab-toolbar :label="$tabsLabel">
                    <x-slot:tabs>
                        <x-horizontal-scroll :label="$tabsLabel">
                            @foreach ($tabItems as $tabItem)
                                @php
                                    $isActive = ($tabItem['key'] ?? null) === $activeTab;
                                @endphp

                                <button type="button" class="tabs-link {{ $isActive ? 'is-active' : '' }}"
                                    data-tab-link="{{ $tabItem['key'] }}" role="tab"
                                    aria-selected="{{ $isActive ? 'true' : 'false' }}">
                                    {{ $tabItem['label'] ?? $tabItem['key'] }}

                                    @if (array_key_exists('count', $tabItem) && (int) $tabItem['count'] > 0)
                                        ({{ $tabItem['count'] }})
                                    @endif
                                </button>
                            @endforeach
                        </x-horizontal-scroll>
                    </x-slot:tabs>
                </x-tab-toolbar>

                @foreach ($tabItems as $tabItem)
                    @php
                        $isActive = ($tabItem['key'] ?? null) === $activeTab;
                    @endphp

                    <section class="tab-panel {{ $isActive ? 'is-active' : '' }}" data-tab-panel="{{ $tabItem['key'] }}"
                        @unless ($isActive) hidden @endunless>
                        <div class="tab-panel-stack">
                            @include($tabItem['view'], $tabItem['data'] ?? [])
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </x-page>
@endsection
