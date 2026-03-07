@props([
    'title' => null,
    'vertical' => null,
])

<div class="page-header {{ $vertical }}">

    @if ($title)
        <h1 class="page-title">{{ $title }}</h1>
    @endif

    @if (trim($slot) !== '')
        <div class="page-actions">
            {{ $slot }}
        </div>
    @endif

</div>