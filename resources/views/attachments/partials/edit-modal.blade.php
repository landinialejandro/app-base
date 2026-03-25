{{-- FILE: resources/views/attachments/partials/edit-modal.blade.php | V1 --}}

@php
    $attachment = $attachment ?? null;
    $attachable = $attachable ?? null;
    $modalId = $modalId ?? 'attachment-edit-modal-' . $attachment->id;
    $returnTo = $returnTo ?? url()->current();
@endphp

@if ($attachment)
    <x-modal :id="$modalId" title="Editar adjunto" size="lg">
        @include('attachments.partials.form', [
            'mode' => 'edit',
            'attachment' => $attachment,
            'attachable' => $attachable,
            'action' => route('attachments.update', $attachment),
            'method' => 'PUT',
            'submitLabel' => 'Guardar cambios',
            'returnTo' => $returnTo,
        ])
    </x-modal>
@endif
