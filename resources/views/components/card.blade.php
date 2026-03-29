{{-- FILE: resources/views/components/card.blade.php | V3 --}}

<div {{ $attributes->class(['card']) }}>
    <div class="card-body">
        {{ $slot }}
    </div>
</div>
