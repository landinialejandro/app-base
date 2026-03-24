{{-- FILE: resources/views/components/show-summary-item-detail-block.blade.php | V2 --}}

@props(['label', 'full' => false])

<div {{ $attributes->class(['detail-block', 'detail-block--full' => $full]) }}>
    <span class="detail-block-label">{{ $label }}</span>
    <div class="detail-block-value">
        {{ $slot }}
    </div>
</div>
