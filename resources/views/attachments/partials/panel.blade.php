{{-- FILE: resources/views/attachments/partials/panel.blade.php | V5 --}}

@php
    $attachable = $attachable ?? null;
    $attachments = ($attachments ?? collect())->values();
    $returnTo = $returnTo ?? url()->current();
@endphp

@include('attachments.partials.embedded-tabs', [
    'attachments' => $attachments,
    'attachable' => $attachable,
    'returnTo' => $returnTo,
])
