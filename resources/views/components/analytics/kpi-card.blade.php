{{-- FILE: resources/views/components/analytics/kpi-card.blade.php | V2 --}}

@props([
    'title' => null,
    'value' => '—',
    'note' => null,
    'span' => 1,
])

<x-analytics.card :title="$title" :span="$span" {{ $attributes }}>
    <div class="visual-kpi">{{ $value }}</div>

    @if (filled($note))
        <div class="visual-note">{{ $note }}</div>
    @endif
</x-analytics.card>
