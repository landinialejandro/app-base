{{-- FILE: resources/views/documents/items/partials/embedded.blade.php | V4 --}}

@php
    use App\Support\Catalogs\DocumentItemCatalog;
    use App\Support\LineItems\LineItemViewHelper;

    $viewHelper = app(LineItemViewHelper::class);

    $document = $document ?? null;
    $items = $items ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay ítems cargados en este documento.';
    $trailQuery = $trailQuery ?? [];

    $statuses = [
        DocumentItemCatalog::STATUS_PENDING => DocumentItemCatalog::statusLabel(DocumentItemCatalog::STATUS_PENDING),
        DocumentItemCatalog::STATUS_PARTIAL => DocumentItemCatalog::statusLabel(DocumentItemCatalog::STATUS_PARTIAL),
        DocumentItemCatalog::STATUS_COMPLETED => DocumentItemCatalog::statusLabel(DocumentItemCatalog::STATUS_COMPLETED),
        DocumentItemCatalog::STATUS_CANCELLED => DocumentItemCatalog::statusLabel(DocumentItemCatalog::STATUS_CANCELLED),
    ];

    $addUrl = null;

    if ($document && auth()->user()?->can('update', $document)) {
        $addUrl = route('documents.items.create', ['document' => $document] + $trailQuery);
    }

    $summaryItems = [
        [
            'label' => 'Cantidad de ítems',
            'value' => $items->count(),
        ],
        [
            'label' => 'Subtotal',
            'value' => $viewHelper->money($document?->subtotal ?? 0),
        ],
        [
            'label' => 'Impuestos',
            'value' => $viewHelper->money($document?->tax_total ?? 0),
        ],
        [
            'label' => 'Total',
            'value' => $viewHelper->money($document?->total ?? 0),
        ],
    ];
@endphp

<x-tabs-embedded
    :items="$items"
    :statuses="$statuses"
    toolbar-label="Estados de ítems"
    table-view="documents.items.partials.table"
    :table-data="[
        'document' => $document,
        'trailQuery' => $trailQuery,
    ]"
    :empty-message="$emptyMessage"
    :add-url="$addUrl"
    add-label="Agregar ítem"
    :summary-items="$summaryItems"
/>