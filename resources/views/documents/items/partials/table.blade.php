{{-- FILE: resources/views/documents/items/partials/table.blade.php | V9 --}}

@php
    use App\Support\Catalogs\DocumentItemCatalog;

    $document = $document ?? null;
    $items = $items ?? collect();
    $emptyMessage = $emptyMessage ?? 'No hay ítems cargados en este documento.';
    $trailQuery = $trailQuery ?? [];
@endphp

<x-line-item-table
    :parent="$document"
    :items="$items"
    :empty-message="$emptyMessage"
    :trail-query="$trailQuery"
    :catalog-class="DocumentItemCatalog::class"
    parent-param-name="document"
    edit-route="documents.items.edit"
    destroy-route="documents.items.destroy"
    row-action-host="documents.items.row"
    row-action-context-key="document"
    :parent-readonly="false"
    modal-namespace="document-items"
/>