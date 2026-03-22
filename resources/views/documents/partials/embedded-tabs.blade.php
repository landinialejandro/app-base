{{-- FILE: resources/views/documents/partials/embedded-tabs.blade.php | V3 --}}

@php
    use App\Support\Catalogs\DocumentCatalog;

    $documents = $documents ?? collect();

    $showParty = $showParty ?? true;
    $showAsset = $showAsset ?? true;
    $showOrder = $showOrder ?? true;

    $emptyMessage = $emptyMessage ?? 'No hay documentos para mostrar.';
    $allLabel = $allLabel ?? 'Todos';

    $kinds = [
        DocumentCatalog::KIND_QUOTE => DocumentCatalog::label(DocumentCatalog::KIND_QUOTE),
        DocumentCatalog::KIND_DELIVERY_NOTE => DocumentCatalog::label(DocumentCatalog::KIND_DELIVERY_NOTE),
        DocumentCatalog::KIND_INVOICE => DocumentCatalog::label(DocumentCatalog::KIND_INVOICE),
        DocumentCatalog::KIND_WORK_ORDER => DocumentCatalog::label(DocumentCatalog::KIND_WORK_ORDER),
    ];

    $tabsId = $tabsId ?? 'documents-tabs-' . uniqid();
    $trailQuery = $trailQuery ?? [];
@endphp

<div class="tabs" data-tabs>
    <div class="tabs-nav" role="tablist" aria-label="Tipos de documentos">
        <button type="button" class="tabs-link is-active" data-tab-link="{{ $tabsId }}-all" role="tab"
            aria-selected="true">
            {{ $allLabel }}
            @if ($documents->count())
                ({{ $documents->count() }})
            @endif
        </button>

        @foreach ($kinds as $value => $label)
            @php
                $kindDocuments = $documents->where('kind', $value)->values();
            @endphp

            <button type="button" class="tabs-link" data-tab-link="{{ $tabsId }}-{{ $value }}"
                role="tab" aria-selected="false">
                {{ $label }}
                @if ($kindDocuments->count())
                    ({{ $kindDocuments->count() }})
                @endif
            </button>
        @endforeach
    </div>

    <section class="tab-panel is-active" data-tab-panel="{{ $tabsId }}-all">
        <div class="tab-panel-stack">
            <x-card class="list-card">
                @include('documents.partials.table', [
                    'documents' => $documents,
                    'showParty' => $showParty,
                    'showAsset' => $showAsset,
                    'showOrder' => $showOrder,
                    'emptyMessage' => $emptyMessage,
                    'trailQuery' => $trailQuery,
                ])
            </x-card>
        </div>
    </section>

    @foreach ($kinds as $value => $label)
        @php
            $kindDocuments = $documents->where('kind', $value)->values();
        @endphp

        <section class="tab-panel" data-tab-panel="{{ $tabsId }}-{{ $value }}" hidden>
            <div class="tab-panel-stack">
                <x-card class="list-card">
                    @include('documents.partials.table', [
                        'documents' => $kindDocuments,
                        'showParty' => $showParty,
                        'showAsset' => $showAsset,
                        'showOrder' => $showOrder,
                        'emptyMessage' => "No hay documentos de tipo {$label} para mostrar.",
                        'trailQuery' => $trailQuery,
                    ])
                </x-card>
            </div>
        </section>
    @endforeach
</div>
