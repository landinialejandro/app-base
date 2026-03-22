{{-- FILE: resources/views/documents/partials/embedded-tabs.blade.php | V3 --}}

@php
    use App\Support\Catalogs\DocumentCatalog;

    $documents = $documents ?? collect();
    $showParty = $showParty ?? true;
    $showAsset = $showAsset ?? true;
    $showOrder = $showOrder ?? true;
    $emptyMessage = $emptyMessage ?? 'No hay documentos para mostrar.';
    $allLabel = $allLabel ?? 'Todos';
    $tabsId = $tabsId ?? 'documents-tabs';
    $trailQuery = $trailQuery ?? [];

    $documentsByKind = [
        'all' => $documents,
        DocumentCatalog::KIND_QUOTE => $documents->where('kind', DocumentCatalog::KIND_QUOTE)->values(),
        DocumentCatalog::KIND_DELIVERY_NOTE => $documents->where('kind', DocumentCatalog::KIND_DELIVERY_NOTE)->values(),
        DocumentCatalog::KIND_INVOICE => $documents->where('kind', DocumentCatalog::KIND_INVOICE)->values(),
        DocumentCatalog::KIND_WORK_ORDER => $documents->where('kind', DocumentCatalog::KIND_WORK_ORDER)->values(),
    ];

    $tabLabels = [
        'all' => $allLabel,
        DocumentCatalog::KIND_QUOTE => DocumentCatalog::label(DocumentCatalog::KIND_QUOTE),
        DocumentCatalog::KIND_DELIVERY_NOTE => DocumentCatalog::label(DocumentCatalog::KIND_DELIVERY_NOTE),
        DocumentCatalog::KIND_INVOICE => DocumentCatalog::label(DocumentCatalog::KIND_INVOICE),
        DocumentCatalog::KIND_WORK_ORDER => DocumentCatalog::label(DocumentCatalog::KIND_WORK_ORDER),
    ];
@endphp

<div class="tabs" data-tabs id="{{ $tabsId }}">
    <div class="tabs-nav" role="tablist" aria-label="Tipos de documentos">
        @foreach ($tabLabels as $tabKey => $tabLabel)
            @php
                $count = $documentsByKind[$tabKey]->count();
                $isActive = $loop->first;
            @endphp

            <button type="button" class="tabs-link {{ $isActive ? 'is-active' : '' }}"
                data-tab-link="{{ $tabsId }}-{{ $tabKey }}" role="tab"
                aria-selected="{{ $isActive ? 'true' : 'false' }}">
                {{ $tabLabel }}
                @if ($count > 0)
                    ({{ $count }})
                @endif
            </button>
        @endforeach
    </div>

    @foreach ($tabLabels as $tabKey => $tabLabel)
        @php
            $tabDocuments = $documentsByKind[$tabKey];
            $isActive = $loop->first;
        @endphp

        <section class="tab-panel {{ $isActive ? 'is-active' : '' }}"
            data-tab-panel="{{ $tabsId }}-{{ $tabKey }}" @if (!$isActive) hidden @endif>
            <x-card class="list-card">
                @include('documents.partials.table', [
                    'documents' => $tabDocuments,
                    'showParty' => $showParty,
                    'showAsset' => $showAsset,
                    'showOrder' => $showOrder,
                    'emptyMessage' => $emptyMessage,
                    'trailQuery' => $trailQuery,
                ])
            </x-card>
        </section>
    @endforeach
</div>
