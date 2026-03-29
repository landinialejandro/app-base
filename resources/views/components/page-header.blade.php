{{-- FILE: resources/views/components/page-header.blade.php | V2 --}}

@props([
    'title' => null,
    'vertical' => false,
])

<div {{ $attributes->class(['page-header', 'vertical' => $vertical]) }}>
    @if ($title)
        <h1 class="page-title">{{ $title }}</h1>
    @endif

    @if (trim((string) $slot) !== '')
        <div class="page-actions">
            {{ $slot }}
        </div>
    @endif
</div>
