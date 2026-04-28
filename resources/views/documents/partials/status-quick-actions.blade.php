{{-- FILE: resources/views/documents/partials/status-quick-actions.blade.php | V1 --}}

@php
    use App\Support\Catalogs\DocumentCatalog;

    $document = $document ?? null;
    $trailQuery = $trailQuery ?? [];

    if (!$document) {
        return;
    }

    $actions = [];

    if (
        $document->status !== DocumentCatalog::STATUS_APPROVED &&
        DocumentCatalog::canTransition($document->status, DocumentCatalog::STATUS_APPROVED)
    ) {
        $actions[] = [
            'status' => DocumentCatalog::STATUS_APPROVED,
            'title' => $document->status === DocumentCatalog::STATUS_CLOSED
                ? 'Reabrir documento'
                : 'Aprobar documento',
            'aria' => $document->status === DocumentCatalog::STATUS_CLOSED
                ? 'Reabrir documento'
                : 'Aprobar documento',
            'class' => 'btn btn-warning btn-icon',
            'message' => $document->status === DocumentCatalog::STATUS_CLOSED
                ? '¿Reabrir el documento y volverlo al estado Aprobado?'
                : '¿Cambiar el estado del documento a Aprobado?',
            'icon' => 'check',
        ];
    }

    if (
        $document->status !== DocumentCatalog::STATUS_CLOSED &&
        DocumentCatalog::canTransition($document->status, DocumentCatalog::STATUS_CLOSED)
    ) {
        $actions[] = [
            'status' => DocumentCatalog::STATUS_CLOSED,
            'title' => 'Cerrar documento',
            'aria' => 'Cerrar documento',
            'class' => 'btn btn-success btn-icon',
            'message' => '¿Cambiar el estado del documento a Cerrado?',
            'icon' => 'circle-check',
        ];
    }

    if (
        $document->status !== DocumentCatalog::STATUS_CANCELLED &&
        DocumentCatalog::canTransition($document->status, DocumentCatalog::STATUS_CANCELLED)
    ) {
        $actions[] = [
            'status' => DocumentCatalog::STATUS_CANCELLED,
            'title' => 'Cancelar documento',
            'aria' => 'Cancelar documento',
            'class' => 'btn btn-danger btn-icon',
            'message' => '¿Cambiar el estado del documento a Cancelado?',
            'icon' => 'x',
        ];
    }
@endphp

@if (!empty($actions))
    <div class="compact-actions compact-actions--end u-w-full u-mt-2">
        @foreach ($actions as $action)
            <form method="POST" action="{{ route('documents.status.update', ['document' => $document] + $trailQuery) }}"
                class="inline-form" data-action="app-confirm-submit" data-confirm-message="{{ $action['message'] }}">
                @csrf

                <input type="hidden" name="status" value="{{ $action['status'] }}">

                <button type="submit" class="{{ $action['class'] }}" title="{{ $action['title'] }}"
                    aria-label="{{ $action['aria'] }}">
                    @if ($action['icon'] === 'check')
                        <x-icons.check />
                    @elseif ($action['icon'] === 'circle-check')
                        <x-icons.circle-check />
                    @elseif ($action['icon'] === 'x')
                        <x-icons.x />
                    @endif
                </button>
            </form>
        @endforeach
    </div>
@endif