{{-- FILE:resources/views/components/button-create.blade.php --}}
@props(['href', 'label' => 'Nuevo'])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'btn btn-success']) }}>
    <span>{{ $label }}</span>
</a>
