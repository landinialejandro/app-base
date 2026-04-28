{{-- FILE: resources/views/components/status-badge.blade.php | V1 --}}

@props([
    'catalog',
    'status',
    'defaultLabel' => '—',
    'defaultClass' => 'status-badge--neutral',
])

@php
    $label = method_exists($catalog, 'statusLabel')
        ? $catalog::statusLabel($status, $defaultLabel)
        : $defaultLabel;

    $badgeClass = method_exists($catalog, 'badgeClass')
        ? $catalog::badgeClass($status, $defaultClass)
        : $defaultClass;
@endphp

<span {{ $attributes->merge(['class' => 'status-badge '.$badgeClass]) }}>
    {{ $label }}
</span>