{{-- FILE: resources/views/components/button-tool-submit.blade.php | V3 --}}

@props(['action', 'title', 'label' => null, 'message' => null, 'method' => 'POST', 'variant' => 'secondary'])

@php
    $classes = \App\Support\Ui\ButtonToolStyle::classes($variant);
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