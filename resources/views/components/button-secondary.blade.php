{{-- FILE:resources/views/components/button-secondary.blade.php --}}

@props(['href', 'label' => null])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'btn btn-secondary']) }}>
    {{ $slot->isEmpty() ? $label : $slot }}
</a>
