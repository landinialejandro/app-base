{{-- FILE: resources/views/attachments/partials/create-modal.blade.php | V1 --}}

@php
    $attachable = $attachable ?? null;
    $modalId = $modalId ?? 'attachments-create-modal';
    $returnTo = $returnTo ?? url()->current();
@endphp

<x-modal :id="$modalId" title="Agregar adjunto" size="lg">
    @include('attachments.partials.form', [
        'mode' => 'create',
        'attachable' => $attachable,
        'action' => route('attachments.store'),
        'method' => 'POST',
        'submitLabel' => 'Subir adjunto',
        'returnTo' => $returnTo,
    ])
</x-modal>
