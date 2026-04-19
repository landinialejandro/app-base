{{-- FILE:resources/views/components/button-tool-submit.blade.php | V2 --}}
@props(['action', 'title', 'label' => null, 'message' => null, 'method' => 'POST', 'variant' => 'secondary'])

@php
    $classes = match ($variant) {
        'danger' => 'btn btn-danger btn-icon btn-tool',
        'primary' => 'btn btn-primary btn-icon btn-tool',
        default => 'btn btn-secondary btn-icon btn-tool',
    };
@endphp

<form method="POST" action="{{ $action }}" class="inline-form"
    @if ($message) data-action="app-confirm-submit"
        data-confirm-message="{{ $message }}" @endif>
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif

    {{ $fields ?? '' }}

    <button type="submit" class="{{ $classes }}" title="{{ $title }}" aria-label="{{ $label ?? $title }}">
        {{ $slot }}
    </button>
</form>
