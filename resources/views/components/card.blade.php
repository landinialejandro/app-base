{{-- FILE: resources/views/components/card.blade.php | v2 --}}

<div {{ $attributes->merge(['class' => 'card']) }}>
    <div class="card-body">
        {{ $slot }}
    </div>
</div>